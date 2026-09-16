<?php
/**
 * MathPlay Solutions — Módulo de Auditoria Mensal de Recuperação
 * Responsável por verificar periodicamente alunos com dificuldades e acionar recuperação automática.
 */

require_once __DIR__ . '/db.php';

function runMonthlyRecoveryAudit(PDO $pdo, ?string $targetMonth = null, bool $force = false): array {
    $auditMonth = $targetMonth ?: date('Y-m');

    // 1. Verificar se a auditoria deste mês já foi executada
    $stmtCheck = $pdo->prepare('SELECT * FROM monthly_recovery_audits WHERE audit_month = ?');
    $stmtCheck->execute([$auditMonth]);
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existing && !$force) {
        return [
            'already_run'          => true,
            'audit_month'          => $auditMonth,
            'executed_at'          => $existing['executed_at'],
            'students_audited'     => (int)$existing['students_audited'],
            'students_in_recovery' => (int)$existing['students_in_recovery'],
            'details'              => json_decode($existing['details_json'] ?? '[]', true) ?: []
        ];
    }

    // 2. Coletar dados de sessões do mês selecionado (ou últimos 30 dias se for o mês corrente)
    $stmtSessions = $pdo->prepare("
        SELECT 
            u.id AS student_id,
            u.name AS student_name,
            u.email AS student_email,
            g.id AS game_id,
            g.name AS game_name,
            g.topic AS topic,
            COUNT(gs.id) AS total_sessions,
            COALESCE(SUM(gs.correct_answers), 0) AS total_correct,
            COALESCE(SUM(gs.wrong_answers), 0) AS total_wrong,
            COALESCE(AVG(gs.score), 0) AS avg_score,
            MAX(gs.played_at) AS last_played
        FROM users u
        JOIN game_sessions gs ON gs.user_id = u.id
        JOIN games g ON g.id = gs.game_id
        WHERE u.role = 'student'
          AND DATE_FORMAT(gs.played_at, '%Y-%m') = ?
        GROUP BY u.id, g.id
        ORDER BY u.name, g.name
    ");
    $stmtSessions->execute([$auditMonth]);
    $rows = $stmtSessions->fetchAll(PDO::FETCH_ASSOC);

    // Se não houver partidas no mês do calendário exato, busca nos últimos 30 dias móveis
    if (empty($rows)) {
        $stmtSessions30 = $pdo->query("
            SELECT 
                u.id AS student_id,
                u.name AS student_name,
                u.email AS student_email,
                g.id AS game_id,
                g.name AS game_name,
                g.topic AS topic,
                COUNT(gs.id) AS total_sessions,
                COALESCE(SUM(gs.correct_answers), 0) AS total_correct,
                COALESCE(SUM(gs.wrong_answers), 0) AS total_wrong,
                COALESCE(AVG(gs.score), 0) AS avg_score,
                MAX(gs.played_at) AS last_played
            FROM users u
            JOIN game_sessions gs ON gs.user_id = u.id
            JOIN games g ON g.id = gs.game_id
            WHERE u.role = 'student'
              AND gs.played_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY u.id, g.id
            ORDER BY u.name, g.name
        ");
        $rows = $stmtSessions30->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Agrupar e avaliar cada aluno
    $studentsMap = [];
    foreach ($rows as $r) {
        $sid = (int)$r['student_id'];
        if (!isset($studentsMap[$sid])) {
            $studentsMap[$sid] = [
                'id'            => $sid,
                'name'          => $r['student_name'],
                'email'         => $r['student_email'],
                'total_correct' => 0,
                'total_wrong'   => 0,
                'topics'        => []
            ];
        }

        $studentsMap[$sid]['total_correct'] += (int)$r['total_correct'];
        $studentsMap[$sid]['total_wrong']   += (int)$r['total_wrong'];

        $subTotal = (int)$r['total_correct'] + (int)$r['total_wrong'];
        $accuracy = $subTotal > 0 ? round(((int)$r['total_correct'] / $subTotal) * 100) : 0;
        $errorRate = $subTotal > 0 ? round(((int)$r['total_wrong'] / $subTotal) * 100) : 0;

        $studentsMap[$sid]['topics'][] = [
            'game_id'      => (int)$r['game_id'],
            'game_name'    => $r['game_name'],
            'topic'        => $r['topic'],
            'sessions'     => (int)$r['total_sessions'],
            'correct'      => (int)$r['total_correct'],
            'wrong'        => (int)$r['total_wrong'],
            'accuracy'     => $accuracy,
            'error_rate'   => $errorRate,
            'needs_topic_recovery' => ($errorRate >= 50 || $accuracy < 60)
        ];
    }

    $inRecoveryList = [];

    // Preparar statements para notificações e questões automáticas
    $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)');
    $checkQuestStmt = $pdo->prepare('
        SELECT COUNT(*) FROM questions 
        WHERE is_ai_generated = 1 
          AND target_user_id = ? 
          AND topic = ? 
          AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ');
    $insertQuestStmt = $pdo->prepare('
        INSERT INTO questions 
        (game_id, topic, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, difficulty, is_ai_generated, target_user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "easy", 1, ?)
    ');

    foreach ($studentsMap as $sid => $stu) {
        $overallTotal = $stu['total_correct'] + $stu['total_wrong'];
        $overallAccuracy = $overallTotal > 0 ? round(($stu['total_correct'] / $overallTotal) * 100) : 0;
        $overallErrorRate = $overallTotal > 0 ? round(($stu['total_wrong'] / $overallTotal) * 100) : 0;

        // Identificar tópicos críticos
        $criticalTopics = array_filter($stu['topics'], fn($t) => $t['needs_topic_recovery']);

        if (!empty($criticalTopics) || ($overallTotal > 0 && $overallAccuracy < 60)) {
            // Aluno qualificado para recuperação mensal!
            $worstTopic = !empty($criticalTopics) ? array_values($criticalTopics)[0] : $stu['topics'][0];

            $inRecoveryList[] = [
                'student_id'       => $sid,
                'student_name'     => $stu['name'],
                'student_email'    => $stu['email'],
                'overall_accuracy' => $overallAccuracy,
                'overall_error'    => $overallErrorRate,
                'priority_topic'   => $worstTopic['topic'],
                'game_id'          => $worstTopic['game_id']
            ];

            // A) Notificar o aluno sobre a recuperação mensal
            $msgStudent = "No fechamento mensal ({$auditMonth}), seu aproveitamento em {$worstTopic['topic']} foi de {$worstTopic['accuracy']}%. Liberamos questões de reforço no seu painel para você recuperar sua média!";
            $notifStmt->execute([$sid, 'ai', "📅 Auditoria Mensal: Recuperação em {$worstTopic['topic']}", $msgStudent]);

            // B) Gerar questões automáticas de recuperação caso ainda não existam no mês
            $checkQuestStmt->execute([$sid, $worstTopic['topic']]);
            $hasQuestions = (int)$checkQuestStmt->fetchColumn() > 0;

            if (!$hasQuestions) {
                // Inserir 3 questões pedagógicas sob medida para o aluno
                $sampleQuestions = getMonthlyRecoveryFallbackQuestions($worstTopic['topic'], $stu['name']);
                foreach ($sampleQuestions as $q) {
                    $insertQuestStmt->execute([
                        $worstTopic['game_id'],
                        $worstTopic['topic'],
                        $q['pergunta'],
                        $q['opcao_a'],
                        $q['opcao_b'],
                        $q['opcao_c'],
                        $q['opcao_d'],
                        $q['resposta_correta'],
                        $q['explicacao'],
                        $sid
                    ]);
                }
            }
        }
    }

    // 4. Notificar todos os professores com o balanço consolidado
    $teachersStmt = $pdo->query("SELECT id, name FROM users WHERE role = 'teacher'");
    $teachers = $teachersStmt->fetchAll(PDO::FETCH_ASSOC);

    $countRecovery = count($inRecoveryList);
    $monthFormatLabel = date('m/Y', strtotime($auditMonth . '-01'));

    if ($countRecovery > 0) {
        $names = implode(', ', array_map(fn($item) => $item['student_name'] . ' (' . $item['priority_topic'] . ')', array_slice($inRecoveryList, 0, 4)));
        if ($countRecovery > 4) $names .= " e mais " . ($countRecovery - 4) . " aluno(s)";

        $teacherTitle = "📅 Auditoria Mensal ({$monthFormatLabel}): {$countRecovery} em Recuperação";
        $teacherMsg   = "O diagnóstico mensal identificou {$countRecovery} aluno(s) com aproveitamento abaixo de 60%: {$names}. As atividades de recuperação individual foram geradas e atribuídas automaticamente.";
    } else {
        $teacherTitle = "📅 Auditoria Mensal ({$monthFormatLabel}): Desempenho OK";
        $teacherMsg   = "A auditoria mensal analisou " . count($studentsMap) . " aluno(s) e nenhum apresentou rendimento crítico abaixo de 60%. Toda a turma está dentro da meta esperada!";
    }

    foreach ($teachers as $t) {
        $notifStmt->execute([$t['id'], 'alert', $teacherTitle, $teacherMsg]);
    }

    // 5. Gravar ou atualizar o registro na tabela monthly_recovery_audits
    $detailsJson = json_encode($inRecoveryList, JSON_UNESCAPED_UNICODE);
    $saveStmt = $pdo->prepare("
        INSERT INTO monthly_recovery_audits (audit_month, executed_at, students_audited, students_in_recovery, details_json)
        VALUES (?, NOW(), ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            executed_at = NOW(),
            students_audited = VALUES(students_audited),
            students_in_recovery = VALUES(students_in_recovery),
            details_json = VALUES(details_json)
    ");
    $saveStmt->execute([$auditMonth, count($studentsMap), $countRecovery, $detailsJson]);

    return [
        'already_run'          => false,
        'audit_month'          => $auditMonth,
        'executed_at'          => date('Y-m-d H:i:s'),
        'students_audited'     => count($studentsMap),
        'students_in_recovery' => $countRecovery,
        'details'              => $inRecoveryList
    ];
}

function getMonthlyRecoveryFallbackQuestions(string $topic, string $studentName): array {
    $tag = "[Recuperação Mensal • {$studentName}]";
    if ($topic === 'Geometria Plana') {
        return [
            [
                'pergunta' => "{$tag} Uma piscina retangular possui 8 metros de comprimento e 4 metros de largura. Qual é a sua área total em m²?",
                'opcao_a' => '24 m²',
                'opcao_b' => '32 m²',
                'opcao_c' => '16 m²',
                'opcao_d' => '64 m²',
                'resposta_correta' => 'B',
                'explicacao' => 'A área do retângulo é dada por base × altura: 8 × 4 = 32 m².'
            ],
            [
                'pergunta' => "{$tag} Para cercar uma horta quadrada com lado medindo 9 metros, quantos metros de arame de cerca são necessários?",
                'opcao_a' => '18 metros',
                'opcao_b' => '27 metros',
                'opcao_c' => '36 metros',
                'opcao_d' => '81 metros',
                'resposta_correta' => 'C',
                'explicacao' => 'O perímetro do quadrado é 4 × lado: 4 × 9 = 36 metros.'
            ],
            [
                'pergunta' => "{$tag} Um canteiro triangular possui base de 6 metros e altura de 4 metros. Qual é a sua área?",
                'opcao_a' => '12 m²',
                'opcao_b' => '24 m²',
                'opcao_c' => '10 m²',
                'opcao_d' => '14 m²',
                'resposta_correta' => 'A',
                'explicacao' => 'A área do triângulo é (base × altura) ÷ 2: (6 × 4) ÷ 2 = 24 ÷ 2 = 12 m².'
            ]
        ];
    }

    return [
        [
            'pergunta' => "{$tag} Uma turma tem 20 alunos e 1/4 deles tirou nota máxima. Quantos alunos tiraram nota máxima?",
            'opcao_a' => '4 alunos',
            'opcao_b' => '5 alunos',
            'opcao_c' => '8 alunos',
            'opcao_d' => '10 alunos',
            'resposta_correta' => 'B',
            'explicacao' => '1/4 de 20 é obtido dividindo 20 por 4: 20 ÷ 4 = 5 alunos.'
        ],
        [
            'pergunta' => "{$tag} Qual fração abaixo é equivalente à fração 2/5?",
            'opcao_a' => '4/10',
            'opcao_b' => '2/10',
            'opcao_c' => '5/2',
            'opcao_d' => '6/12',
            'resposta_correta' => 'A',
            'explicacao' => 'Multiplicando numerador e denominador por 2: 2/5 = (2×2)/(5×2) = 4/10.'
        ],
        [
            'pergunta' => "{$tag} Se você resolver 3/8 de uma lista pela manhã e 2/8 à tarde, qual fração da lista você já completou?",
            'opcao_a' => '5/16',
            'opcao_b' => '6/8',
            'opcao_c' => '5/8',
            'opcao_d' => '1/8',
            'resposta_correta' => 'C',
            'explicacao' => 'Com o mesmo denominador, soma-se os numeradores: 3/8 + 2/8 = 5/8 da lista.'
        ]
    ];
}
