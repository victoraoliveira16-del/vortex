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

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? '');
if (!$csrfToken || !verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sessao invalida. Recarregue a pagina e tente novamente.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$topic  = trim((string)($data['topic'] ?? 'Fracoes e Razoes'));
$gameId = (int)($data['game_id'] ?? 1);

$_envCfg = file_exists(__DIR__ . '/../.env') ? (parse_ini_file(__DIR__ . '/../.env') ?: []) : [];
$GEMINI_KEY = trim((string)($_envCfg['GEMINI_API_KEY'] ?? ''));

$questions = null;

if (!empty($GEMINI_KEY) && $GEMINI_KEY !== 'YOUR_GEMINI_API_KEY_HERE') {
    if (function_exists('curl_init')) {
        $stmt = $pdo->prepare('SELECT SUM(wrong_answers) AS e, SUM(correct_answers) AS a, SUM(wrong_answers+correct_answers) AS t FROM game_sessions gs JOIN games g ON g.id=gs.game_id WHERE gs.user_id=? AND g.topic=?');
        $stmt->execute([$userId, $topic]);
        $stats = $stmt->fetch();
        $errorRate = (int)($stats['t'] ?? 0) > 0 ? round((int)$stats['e'] / (int)$stats['t'] * 100) : 50;
        $prompt = 'O aluno errou ' . $errorRate . '% das questoes sobre "' . $topic . '". Gere 3 questoes de multipla escolha em portugues do Brasil. Responda somente com JSON puro em array, sem explicacao extra: [{"pergunta":"","opcao_a":"","opcao_b":"","opcao_c":"","opcao_d":"","resposta_correta":"A","explicacao":""}]';

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . urlencode($GEMINI_KEY);
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
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload)
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
                            if (is_array($parsed)) {
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

// Fallback de reforço pedagógico quando a API key não estiver configurada
if (!$questions) {
    if ($topic === 'Geometria Plana' || $gameId === 2) {
        $questions = [
            [
                'pergunta' => '[Reforço] Um triângulo equilátero possui lados medindo 7cm. Qual é o seu perímetro total?',
                'opcao_a' => '14 cm',
                'opcao_b' => '21 cm',
                'opcao_c' => '28 cm',
                'opcao_d' => '49 cm',
                'resposta_correta' => 'B',
                'explicacao' => 'O perímetro do triângulo equilátero é a soma de seus 3 lados iguais: 7 + 7 + 7 = 21 cm.'
            ],
            [
                'pergunta' => '[Reforço] Qual é a área de um retângulo de base 12m e altura 5m?',
                'opcao_a' => '34 m²',
                'opcao_b' => '60 m²',
                'opcao_c' => '17 m²',
                'opcao_d' => '50 m²',
                'resposta_correta' => 'B',
                'explicacao' => 'A área do retângulo é calculada por base × altura: 12 × 5 = 60 m².'
            ],
            [
                'pergunta' => '[Reforço] Um quadrado tem perímetro de 32 metros. Quanto mede cada um dos seus lados?',
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
                'pergunta' => '[Reforço] Se você dividir uma barra de chocolate em 10 pedaços e comer 4, qual fração representa a parte que sobrou?',
                'opcao_a' => '4/10',
                'opcao_b' => '6/10 (ou 3/5)',
                'opcao_c' => '1/2',
                'opcao_d' => '2/5',
                'resposta_correta' => 'B',
                'explicacao' => 'O total é 10/10. Subtraindo a parte consumida: 10/10 - 4/10 = 6/10, simplificando por 2 fica 3/5.'
            ],
            [
                'pergunta' => '[Reforço] Qual fração é equivalente a 3/4?',
                'opcao_a' => '6/8',
                'opcao_b' => '6/4',
                'opcao_c' => '3/8',
                'opcao_d' => '9/16',
                'resposta_correta' => 'A',
                'explicacao' => 'Multiplicando numerador e denominador por 2: 3/4 = (3×2)/(4×2) = 6/8.'
            ],
            [
                'pergunta' => '[Reforço] Qual é o resultado da soma 2/5 + 1/5?',
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

$inserted = 0;
foreach ($questions as $q) {
    if (empty($q['pergunta'])) continue;
    $pdo->prepare('INSERT INTO questions (game_id, topic, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, is_ai_generated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)')
        ->execute([
            $gameId,
            $topic,
            $q['pergunta'],
            $q['opcao_a'] ?? '',
            $q['opcao_b'] ?? '',
            $q['opcao_c'] ?? '',
            $q['opcao_d'] ?? '',
            strtoupper($q['resposta_correta'] ?? 'A'),
            $q['explicacao'] ?? ''
        ]);
    $inserted++;
}

$pdo->prepare('INSERT INTO notifications (user_id, type, title, message) VALUES (?, "ai", "Reforco Personalizado Disponivel", ?)')
    ->execute([$userId, 'Geradas ' . $inserted . ' questoes de reforco sobre ' . $topic . ' com foco pedagogico!']);

echo json_encode(['success' => true, 'generated' => $inserted]);

