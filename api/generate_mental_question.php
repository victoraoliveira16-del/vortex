<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sessao invalida']);
    exit;
}

$data = !empty($data) ? $data : (json_decode(file_get_contents('php://input'), true) ?? []);
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? '');
if (!$csrfToken || !verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sessao invalida']);
    exit;
}

$gameId = (int)($data['game_id'] ?? 3);
$level = max(1, (int)($data['level'] ?? 1));

$questionText = '';
$answer = 0;
$difficulty = 'easy';
$levelName = 'Fácil';

if ($level === 1) {
    // NÍVEL 1: FÁCIL (Questões 1 a 3) - Números pequenos (2 a 15), tabuada básica
    $difficulty = 'easy';
    $levelName = 'Fácil';
    $op = random_int(1, 4);
    if ($op === 1) { // Adição simples
        $a = random_int(2, 15);
        $b = random_int(2, 12);
        $questionText = "$a + $b";
        $answer = $a + $b;
    } elseif ($op === 2) { // Subtração simples sem negativos
        $b = random_int(2, 10);
        $a = $b + random_int(2, 12);
        $questionText = "$a - $b";
        $answer = $a - $b;
    } elseif ($op === 3) { // Multiplicação básica do 2 ao 5
        $a = random_int(2, 5);
        $b = random_int(2, 9);
        $questionText = "$a × $b";
        $answer = $a * $b;
    } else { // Divisão exata simples
        $divisor = random_int(2, 5);
        $quotient = random_int(2, 8);
        $dividend = $divisor * $quotient;
        $questionText = "$dividend ÷ $divisor";
        $answer = $quotient;
    }
    $explanation = "Nível Fácil: $questionText = $answer.";
} elseif ($level === 2) {
    // NÍVEL 2: MÉDIO (Questões 4 a 6) - Dois dígitos moderados, tabuada do 6 ao 9
    $difficulty = 'medium';
    $levelName = 'Médio';
    $op = random_int(1, 4);
    if ($op === 1) { // Adição moderada
        $a = random_int(15, 45);
        $b = random_int(12, 35);
        $questionText = "$a + $b";
        $answer = $a + $b;
    } elseif ($op === 2) { // Subtração moderada
        $b = random_int(15, 40);
        $a = $b + random_int(10, 35);
        $questionText = "$a - $b";
        $answer = $a - $b;
    } elseif ($op === 3) { // Multiplicação do 6 ao 9
        $a = random_int(6, 9);
        $b = random_int(4, 9);
        $questionText = "$a × $b";
        $answer = $a * $b;
    } else { // Divisão exata do 6 ao 9
        $divisor = random_int(6, 9);
        $quotient = random_int(3, 9);
        $dividend = $divisor * $quotient;
        $questionText = "$dividend ÷ $divisor";
        $answer = $quotient;
    }
    $explanation = "Nível Médio: $questionText = $answer.";
} elseif ($level === 3) {
    // NÍVEL 3: DIFÍCIL (Questões 7 a 10) - Cálculos com números maiores
    $difficulty = 'hard';
    $levelName = 'Difícil';
    $op = random_int(1, 4);
    if ($op === 1) { // Adição com números maiores
        $a = random_int(45, 95);
        $b = random_int(25, 85);
        $questionText = "$a + $b";
        $answer = $a + $b;
    } elseif ($op === 2) { // Subtração com centenas
        $b = random_int(35, 75);
        $a = $b + random_int(40, 85);
        $questionText = "$a - $b";
        $answer = $a - $b;
    } elseif ($op === 3) { // Multiplicação com 2 dígitos
        $candidates = [
            [12, random_int(4, 8)],
            [13, random_int(3, 6)],
            [14, random_int(3, 5)],
            [15, random_int(3, 6)],
            [25, random_int(2, 4)],
            [20, random_int(3, 7)]
        ];
        $chosen = $candidates[array_rand($candidates)];
        $a = $chosen[0];
        $b = $chosen[1];
        $questionText = "$a × $b";
        $answer = $a * $b;
    } else { // Divisão com dividendos maiores
        $divPairs = [
            [84, 4, 21], [96, 3, 32], [75, 3, 25], [100, 4, 25],
            [90, 6, 15], [80, 5, 16], [72, 4, 18], [65, 5, 13]
        ];
        $pair = $divPairs[array_rand($divPairs)];
        $questionText = "{$pair[0]} ÷ {$pair[1]}";
        $answer = $pair[2];
    }
    $explanation = "Nível Difícil: $questionText = $answer.";
} elseif ($level === 4) {
    // NÍVEL 4: ESPECIALISTA (Questões 11 a 15)
    $difficulty = 'hard';
    $levelName = 'Especialista';
    $op = random_int(1, 4);
    if ($op === 1) {
        $a = random_int(85, 175);
        $b = random_int(45, 135);
        $questionText = "$a + $b";
        $answer = $a + $b;
    } elseif ($op === 2) {
        $b = random_int(65, 140);
        $a = $b + random_int(50, 160);
        $questionText = "$a - $b";
        $answer = $a - $b;
    } elseif ($op === 3) {
        $candidates = [[15, 6], [15, 8], [25, 4], [25, 6], [16, 5], [18, 4], [14, 6], [30, 4], [12, 12]];
        $c = $candidates[array_rand($candidates)];
        $questionText = "{$c[0]} × {$c[1]}";
        $answer = $c[0] * $c[1];
    } else {
        $divPairs = [[144, 12, 12], [150, 6, 25], [180, 6, 30], [240, 6, 40], [200, 8, 25], [210, 7, 30]];
        $p = $divPairs[array_rand($divPairs)];
        $questionText = "{$p[0]} ÷ {$p[1]}";
        $answer = $p[2];
    }
    $explanation = "Nível Especialista: $questionText = $answer.";
} elseif ($level === 5) {
    // NÍVEL 5: MESTRE (Questões 16 a 20)
    $difficulty = 'hard';
    $levelName = 'Mestre';
    $op = random_int(1, 4);
    if ($op === 1) {
        $a = random_int(150, 380);
        $b = random_int(120, 290);
        $questionText = "$a + $b";
        $answer = $a + $b;
    } elseif ($op === 2) {
        $b = random_int(120, 280);
        $a = $b + random_int(100, 320);
        $questionText = "$a - $b";
        $answer = $a - $b;
    } elseif ($op === 3) {
        $candidates = [[35, 4], [45, 3], [15, 12], [11, 15], [50, 5], [25, 8], [40, 6]];
        $c = $candidates[array_rand($candidates)];
        $questionText = "{$c[0]} × {$c[1]}";
        $answer = $c[0] * $c[1];
    } else {
        $divPairs = [[360, 9, 40], [350, 7, 50], [400, 8, 50], [280, 4, 70], [300, 15, 20], [225, 15, 15]];
        $p = $divPairs[array_rand($divPairs)];
        $questionText = "{$p[0]} ÷ {$p[1]}";
        $answer = $p[2];
    }
    $explanation = "Nível Mestre: $questionText = $answer.";
} else {
    // NÍVEL 6+: LENDA DA MATEMÁTICA (Questões 21+)
    $difficulty = 'hard';
    $levelName = "Lenda (Nível $level)";
    $op = random_int(1, 4);
    if ($op === 1) {
        $a = random_int(250, 600);
        $b = random_int(200, 550);
        $questionText = "$a + $b";
        $answer = $a + $b;
    } elseif ($op === 2) {
        $b = random_int(250, 500);
        $a = $b + random_int(200, 500);
        $questionText = "$a - $b";
        $answer = $a - $b;
    } elseif ($op === 3) {
        $candidates = [[75, 4], [125, 2], [15, 15], [50, 8], [60, 7], [25, 12], [80, 5]];
        $c = $candidates[array_rand($candidates)];
        $questionText = "{$c[0]} × {$c[1]}";
        $answer = $c[0] * $c[1];
    } else {
        $divPairs = [[500, 25, 20], [600, 15, 40], [480, 12, 40], [720, 8, 90], [800, 20, 40], [630, 9, 70]];
        $p = $divPairs[array_rand($divPairs)];
        $questionText = "{$p[0]} ÷ {$p[1]}";
        $answer = $p[2];
    }
    $explanation = "Nível Lenda: $questionText = $answer.";
}

$stmt = $pdo->prepare('INSERT INTO questions (game_id, topic, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, difficulty, is_ai_generated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)');
$stmt->execute([$gameId, 'Operacoes Basicas', $questionText, '', '', '', '', (string)$answer, $explanation, $difficulty]);

$question = [
    'id' => (int)$pdo->lastInsertId(),
    'question_text' => $questionText,
    'difficulty' => $difficulty,
    'level' => $level,
    'level_name' => $levelName
];
echo json_encode(['success' => true, 'question' => $question]);
