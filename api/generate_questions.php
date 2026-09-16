<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo nao permitido. Use POST.']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nao autenticado']);
    exit;
}

// Apenas professores (ou administradores) podem disparar reforço pedagógico
if (!isTeacher()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Apenas professores podem aplicar recuperacao/reforco.']);
    exit;
}

$data      = json_decode(file_get_contents('php://input'), true) ?? [];
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? '');
if (!$csrfToken || !verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sessao invalida. Recarregue a pagina e tente novamente.']);
    exit;
}

$teacherUser = getCurrentUser();
$teacherId   = (int)$teacherUser['id'];
$teacherName = $teacherUser['name'] ?? 'Professor';

$topic  = trim((string)($data['topic'] ?? 'Fracoes e Razoes'));
$gameId = (int)($data['game_id'] ?? 1);

// Aluno alvo específico (opcional)
$rawStudentId    = $data['student_id'] ?? null;
$targetStudentId = null;
$targetStudent   = null;

if (!empty($rawStudentId) && $rawStudentId !== 'all') {
    $stmtS = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ? AND role = "student"');
    $stmtS->execute([(int)$rawStudentId]);
    $targetStudent = $stmtS->fetch();
    if ($targetStudent) {
        $targetStudentId = (int)$targetStudent['id'];
    }
}

$_envCfg = file_exists(__DIR__ . '/../.env') ? (parse_ini_file(__DIR__ . '/../.env') ?: []) : [];
$GEMINI_KEY = trim((string)($_envCfg['GEMINI_API_KEY'] ?? ''));

$questions = null;

// Análise de estatísticas para calibrar a dificuldade
if ($targetStudentId) {
    // Estatísticas específicas do aluno selecionado
    $stmtStats = $pdo->prepare('
        SELECT SUM(gs.wrong_answers) AS e, SUM(gs.correct_answers) AS a, 
               SUM(gs.wrong_answers + gs.correct_answers) AS t 
        FROM game_sessions gs 
        JOIN games g ON g.id = gs.game_id 
        WHERE gs.user_id = ? AND g.topic = ?
    ');
    $stmtStats->execute([$targetStudentId, $topic]);
    $stats = $stmtStats->fetch();
    $totalAttempts = (int)($stats['t'] ?? 0);
    $errorRate = $totalAttempts > 0 ? round((int)$stats['e'] / $totalAttempts * 100) : 50;

    $promptContext = 'O aluno "' . $targetStudent['name'] . '" errou ' . $errorRate . '% das questoes sobre "' . $topic . '". Gere 3 questoes de multipla escolha em portugues do Brasil com foco pedagogico para recuperacao individual.';
} else {
    // Estatísticas gerais da turma
    $stmtStats = $pdo->prepare('
        SELECT SUM(gs.wrong_answers) AS e, SUM(gs.correct_answers) AS a, 
               SUM(gs.wrong_answers + gs.correct_answers) AS t 
        FROM game_sessions gs 
        JOIN games g ON g.id = gs.game_id 
        WHERE g.topic = ?
    ');
    $stmtStats->execute([$topic]);
    $stats = $stmtStats->fetch();
    $totalAttempts = (int)($stats['t'] ?? 0);
    $errorRate = $totalAttempts > 0 ? round((int)$stats['e'] / $totalAttempts * 100) : 40;

    $promptContext = 'A turma errou em media ' . $errorRate . '% das questoes sobre "' . $topic . '". Gere 3 questoes de multipla escolha em portugues do Brasil para reforco coletivo.';
}

// Tentativa de chamada à API do Gemini
if (!empty($GEMINI_KEY) && $GEMINI_KEY !== 'YOUR_GEMINI_API_KEY_HERE') {
    if (function_exists('curl_init')) {
        $prompt = $promptContext . ' Responda somente com JSON puro em array, sem markdown, sem explicacao extra: [{"pergunta":"","opcao_a":"","opcao_b":"","opcao_c":"","opcao_d":"","resposta_correta":"A","explicacao":""}]';

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . urlencode($GEMINI_KEY);
        $payload = [
            'contents' => [[
                'parts' => [[
                    'text' => $prompt
                ]]
            ]]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload)
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
            $apiData = json_decode($response, true);
            if (isset($apiData['candidates']) && is_array($apiData['candidates'])) {
                foreach ($apiData['candidates'] as $candidate) {
                    $parts = $candidate['content']['parts'] ?? [];
                    foreach ($parts as $part) {
                        $jsonText = $part['text'] ?? '';
                        if (!empty($jsonText)) {
                            $jsonText = preg_replace('/```(?:json)?\s*/i', '', trim($jsonText));
                            $jsonText = rtrim($jsonText, "\n\t ");
                            $parsed = json_decode($jsonText, true);
                            if (is_array($parsed) && count($parsed) > 0) {
                                $questions = $parsed;
                                break 2;
                            }
                        }
                    }
                }
            }
        }
    }
}

// Fallback pedagógico contextualizado caso a API do Gemini esteja offline/ocupada
if (!$questions) {
    $tagLabel = $targetStudent ? ('[Recuperação • ' . $targetStudent['name'] . ']') : '[Reforço Pedagógico]';

    if ($topic === 'Geometria Plana' || $gameId === 2) {
        $questions = [
            [
                'pergunta' => "{$tagLabel} Um triângulo equilátero possui lados medindo 7cm. Qual é o seu perímetro total?",
                'opcao_a' => '14 cm',
                'opcao_b' => '21 cm',
                'opcao_c' => '28 cm',
                'opcao_d' => '49 cm',
                'resposta_correta' => 'B',
                'explicacao' => 'O perímetro do triângulo equilátero é a soma de seus 3 lados iguais: 7 + 7 + 7 = 21 cm.'
            ],
            [
                'pergunta' => "{$tagLabel} Qual é a área de um retângulo com base de 12m e altura de 5m?",
                'opcao_a' => '34 m²',
                'opcao_b' => '60 m²',
                'opcao_c' => '17 m²',
                'opcao_d' => '50 m²',
                'resposta_correta' => 'B',
                'explicacao' => 'A área do retângulo é calculada por base × altura: 12 × 5 = 60 m².'
            ],
            [
                'pergunta' => "{$tagLabel} Um quadrado tem perímetro de 32 metros. Quanto mede cada um dos seus lados?",
                'opcao_a' => '8 metros',
                'opcao_b' => '16 metros',
                'opcao_c' => '4 metros',
                'opcao_d' => '64 metros',
                'resposta_correta' => 'A',
                'explicacao' => 'Como o quadrado tem 4 lados iguais: 32 ÷ 4 = 8 metros.'
            ]
        ];
    } else {
        $questions = [
            [
                'pergunta' => "{$tagLabel} Se você dividir uma barra de chocolate em 10 pedaços e comer 4, qual fração representa a parte restante?",
                'opcao_a' => '4/10',
                'opcao_b' => '6/10 (ou 3/5)',
                'opcao_c' => '1/2',
                'opcao_d' => '2/5',
                'resposta_correta' => 'B',
                'explicacao' => 'O total é 10/10. Subtraindo a parte consumida: 10/10 - 4/10 = 6/10, simplificando por 2 fica 3/5.'
            ],
            [
                'pergunta' => "{$tagLabel} Qual fração abaixo é equivalente a 3/4?",
                'opcao_a' => '6/8',
                'opcao_b' => '6/4',
                'opcao_c' => '3/8',
                'opcao_d' => '9/16',
                'resposta_correta' => 'A',
                'explicacao' => 'Multiplicando numerador e denominador por 2: 3/4 = (3×2)/(4×2) = 6/8.'
            ],
            [
                'pergunta' => "{$tagLabel} Qual é o resultado exato da adição 2/5 + 1/5?",
                'opcao_a' => '3/10',
                'opcao_b' => '3/5',
                'opcao_c' => '2/25',
                'opcao_d' => '1/5',
                'resposta_correta' => 'B',
                'explicacao' => 'Com denominadores iguais, mantemos o denominador 5 e somamos os numeradores: 2 + 1 = 3/5.'
            ]
        ];
    }
}

// Salvar as questões no banco com vinculação individual ou geral
$inserted = 0;
$insertStmt = $pdo->prepare('
    INSERT INTO questions 
    (game_id, topic, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, difficulty, is_ai_generated, target_user_id) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "easy", 1, ?)
');

foreach ($questions as $q) {
    if (empty($q['pergunta'])) continue;
    $insertStmt->execute([
        $gameId,
        $topic,
        $q['pergunta'],
        $q['opcao_a'] ?? '',
        $q['opcao_b'] ?? '',
        $q['opcao_c'] ?? '',
        $q['opcao_d'] ?? '',
        strtoupper($q['resposta_correta'] ?? 'A'),
        $q['explicacao'] ?? '',
        $targetStudentId // NULL se for para toda a turma, ou ID do aluno específico
    ]);
    $inserted++;
}

$notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message) VALUES (?, "ai", ?, ?)');
$notifiedCount = 0;

if ($targetStudentId && $targetStudent) {
    // Notifica APENAS o aluno específico selecionado pelo professor
    $stuTitle = "🎯 Recuperação Atribuída: {$topic}";
    $stuMsg   = "O {$teacherName} preparou uma atividade de recuperação individual em {$topic} especialmente para você. {$inserted} questões já estão liberadas no seu Treino de Reforço!";
    $notifStmt->execute([$targetStudentId, $stuTitle, $stuMsg]);
    $notifiedCount = 1;

    // Confirmação para o professor
    $confirmTitle = "Recuperação Individual Ativada";
    $confirmMsg   = "Recuperação de {$topic} aplicada com sucesso para o aluno {$targetStudent['name']}! {$inserted} questões geradas.";
    $notifStmt->execute([$teacherId, $confirmTitle, $confirmMsg]);

} else {
    // Notifica toda a turma (reforço coletivo)
    $stmtStudents = $pdo->prepare('
        SELECT u.id, u.name,
               COALESCE(SUM(gs.wrong_answers), 0) AS misses,
               COALESCE(SUM(gs.correct_answers), 0) AS hits
        FROM users u
        LEFT JOIN game_sessions gs ON gs.user_id = u.id AND gs.game_id = ?
        WHERE u.role = "student"
        GROUP BY u.id, u.name
    ');
    $stmtStudents->execute([$gameId]);
    $students = $stmtStudents->fetchAll();

    foreach ($students as $stu) {
        $hasErrors = (int)$stu['misses'] > 0;
        $title = $hasErrors ? "🎯 Reforço Recomendado: {$topic}" : "✨ Desafio de Fixação: {$topic}";
        $msg   = $hasErrors
            ? "Identificamos pontos de melhoria em {$topic}. {$inserted} novas questões de reforço preparadas para você dominar o assunto!"
            : "Novas questões de fixação com IA sobre {$topic} foram liberadas no seu Treino de Reforço!";
        $notifStmt->execute([(int)$stu['id'], $title, $msg]);
        $notifiedCount++;
    }

    // Confirmação para o professor
    $confirmTitle = "Reforço Coletivo Enviado";
    $confirmMsg   = "Reforço de {$topic} ativado para toda a turma! {$inserted} questões salvas e {$notifiedCount} alunos notificados.";
    $notifStmt->execute([$teacherId, $confirmTitle, $confirmMsg]);
}

echo json_encode([
    'success'           => true,
    'generated'         => $inserted,
    'notified_students' => $notifiedCount,
    'is_individual'     => !empty($targetStudentId),
    'student_id'        => $targetStudentId,
    'student_name'      => $targetStudent ? $targetStudent['name'] : null,
    'topic'             => $topic
]);
