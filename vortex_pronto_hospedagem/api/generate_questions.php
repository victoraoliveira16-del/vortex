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

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'JSON invalido.']);
    exit;
}
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? '');
if (!$csrfToken || !verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sessao invalida. Recarregue a pagina e tente novamente.']);
    exit;
}

$teacherUser = getCurrentUser();
$teacherId   = (int)$teacherUser['id'];
$teacherName = $teacherUser['name'] ?? 'Professor';

$rawTopic = $data['topic'] ?? null;
$rawGameId = $data['game_id'] ?? null;
$rawStudentId = $data['student_id'] ?? null;
$rawClassId = $data['class_id'] ?? null;
if (
    !is_string($rawTopic) || trim($rawTopic) === ''
    || (!is_int($rawGameId) && !is_string($rawGameId))
    || (($rawStudentId === null || $rawStudentId === '') === ($rawClassId === null || $rawClassId === ''))
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Selecione um aluno ou uma turma e informe um tópico válido.']);
    exit;
}

$topic = trim($rawTopic);
$gameId = filter_var($rawGameId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($gameId === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Jogo inválido.']);
    exit;
}

$gameStmt = $pdo->prepare('SELECT id, topic FROM games WHERE id = ?');
$gameStmt->execute([$gameId]);
$game = $gameStmt->fetch();
if (!$game || $game['topic'] !== $topic) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'O tópico não corresponde ao jogo selecionado.']);
    exit;
}
$topic = $game['topic'];

$targetStudentId = null;
$targetStudent = null;
$classId = null;
$classStudents = [];
if ($rawStudentId !== null && $rawStudentId !== '') {
    if (!is_int($rawStudentId) && !is_string($rawStudentId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Identificador de aluno inválido.']);
        exit;
    }
    $targetStudentId = filter_var($rawStudentId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($targetStudentId === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Identificador de aluno inválido.']);
        exit;
    }
    $stmtS = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ? AND role = "student"');
    $stmtS->execute([$targetStudentId]);
    $targetStudent = $stmtS->fetch();
    if (!$targetStudent) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Aluno não encontrado.']);
        exit;
    }
    $targets = [$targetStudent];
} else {
    if (!is_int($rawClassId) && !is_string($rawClassId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Identificador de turma inválido.']);
        exit;
    }
    $classId = filter_var($rawClassId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($classId === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Identificador de turma inválido.']);
        exit;
    }
    $stmt = $pdo->prepare('SELECT id, name FROM classes WHERE id = ?');
    $stmt->execute([$classId]);
    $class = $stmt->fetch();
    if (!$class) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Turma não encontrada.']);
        exit;
    }
    $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE class_id = ? AND role = "student" ORDER BY name');
    $stmt->execute([$classId]);
    $classStudents = $stmt->fetchAll();
    if (!$classStudents) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'A turma selecionada não possui alunos.']);
        exit;
    }
    $targets = $classStudents;
}

$_envCfg = file_exists(__DIR__ . '/../.env') ? (parse_ini_file(__DIR__ . '/../.env') ?: []) : [];
$GEMINI_KEY = trim((string)($_envCfg['GEMINI_API_KEY'] ?? ''));

$questions = null;

// Análise de estatísticas para calibrar a dificuldade
if ($targetStudentId !== null) {
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
    $classStudentIds = array_column($classStudents, 'id');
    $placeholders = implode(',', array_fill(0, count($classStudentIds), '?'));
    $stmtStats = $pdo->prepare('
        SELECT SUM(gs.wrong_answers) AS e, SUM(gs.correct_answers) AS a, 
               SUM(gs.wrong_answers + gs.correct_answers) AS t 
        FROM game_sessions gs 
        JOIN games g ON g.id = gs.game_id 
        WHERE g.topic = ? AND gs.user_id IN (' . $placeholders . ')
    ');
    $stmtStats->execute(array_merge([$topic], $classStudentIds));
    $stats = $stmtStats->fetch();
    $totalAttempts = (int)($stats['t'] ?? 0);
    $errorRate = $totalAttempts > 0 ? round((int)$stats['e'] / $totalAttempts * 100) : 40;

    $promptContext = 'A turma ' . $class['name'] . ' errou em media ' . $errorRate . '% das questoes sobre "' . $topic . '". Gere 3 questoes de multipla escolha em portugues do Brasil para uma recuperacao individual por aluno desta turma.';
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

        $caBundle = ini_get('curl.cainfo');
        if (!$caBundle || !is_file($caBundle)) {
            $laragonCaBundle = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'cacert.pem';
            $caBundle = is_file($laragonCaBundle) ? $laragonCaBundle : null;
        }

        $ch = curl_init($url);
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload)
        ];
        if ($caBundle) {
            $curlOptions[CURLOPT_CAINFO] = $caBundle;
        }
        curl_setopt_array($ch, $curlOptions);
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
    $tagLabel = '[Recuperação]';

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

$validQuestions = [];
foreach (is_array($questions) ? $questions : [] as $question) {
    if (!is_array($question)) continue;
    $answer = strtoupper(trim((string)($question['resposta_correta'] ?? '')));
    $values = [
        'pergunta' => trim((string)($question['pergunta'] ?? '')),
        'opcao_a' => trim((string)($question['opcao_a'] ?? '')),
        'opcao_b' => trim((string)($question['opcao_b'] ?? '')),
        'opcao_c' => trim((string)($question['opcao_c'] ?? '')),
        'opcao_d' => trim((string)($question['opcao_d'] ?? '')),
        'resposta_correta' => $answer,
        'explicacao' => trim((string)($question['explicacao'] ?? '')),
    ];
    if (
        $values['pergunta'] === '' || $values['opcao_a'] === '' || $values['opcao_b'] === ''
        || $values['opcao_c'] === '' || $values['opcao_d'] === ''
        || !in_array($answer, ['A', 'B', 'C', 'D'], true)
    ) {
        continue;
    }
    $validQuestions[] = $values;
}

if (!$validQuestions) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Não foi possível gerar questões válidas para esta recuperação.']);
    exit;
}

$insertRecovery = $pdo->prepare("
    INSERT INTO recoveries (student_id, teacher_id, topic, game_id, status)
    VALUES (?, ?, ?, ?, 'pending')
");
$insertQuestion = $pdo->prepare('
    INSERT INTO questions
    (game_id, topic, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, difficulty, is_ai_generated, target_user_id, recovery_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "easy", 1, ?, ?)
');
$notifyStudent = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message) VALUES (?, "ai", ?, ?)');
$totalQuestions = 0;

$pdo->beginTransaction();
try {
    foreach ($targets as $student) {
        $insertRecovery->execute([(int)$student['id'], $teacherId, $topic, $gameId]);
        $recoveryId = (int)$pdo->lastInsertId();
        foreach ($validQuestions as $question) {
            $insertQuestion->execute([
                $gameId,
                $topic,
                $question['pergunta'],
                $question['opcao_a'],
                $question['opcao_b'],
                $question['opcao_c'],
                $question['opcao_d'],
                $question['resposta_correta'],
                $question['explicacao'],
                (int)$student['id'],
                $recoveryId,
            ]);
            $totalQuestions++;
        }
        $notifyStudent->execute([
            (int)$student['id'],
            'Recuperação atribuída: ' . $topic,
            "O professor {$teacherName} enviou uma recuperação individual de {$topic} com " . count($validQuestions) . ' questões. Acesse a aba Recuperação para realizá-la.',
        ]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

echo json_encode([
    'success' => true,
    'generated' => $totalQuestions,
    'questions_per_student' => count($validQuestions),
    'notified_students' => count($targets),
    'is_individual' => $targetStudentId !== null,
    'student_id' => $targetStudentId,
    'student_name' => $targetStudent['name'] ?? null,
    'class_id' => $classId,
    'class_name' => $class['name'] ?? null,
    'topic' => $topic,
]);
