<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sessao expirada. Faca login novamente.']);
    exit;
}

$data = !empty($data) ? $data : (json_decode(file_get_contents('php://input'), true) ?? []);

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? '');
if (!$csrfToken || !verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sessao invalida. Recarregue a pagina e tente novamente.']);
    exit;
}

$rawQuestionId = trim((string)($data['question_id'] ?? ''));
$chosen        = trim((string)($data['chosen'] ?? ''));
$hintUsed      = !empty($data['hint_used']);
$gameId        = (int)($data['game_id'] ?? 0);
$recoveryId = null;
$rawRecoveryId = $data['recovery_id'] ?? null;
if ($rawRecoveryId !== null && $rawRecoveryId !== '') {
    $recoveryId = filter_var($rawRecoveryId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($recoveryId === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Recuperação inválida.']);
        exit;
    }
    if (($_SESSION['user_role'] ?? '') !== 'student') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Somente o aluno destinatário pode fazer esta recuperação.']);
        exit;
    }
    $recoveryStmt = $pdo->prepare("SELECT id FROM recoveries WHERE id = ? AND student_id = ? AND status = 'pending'");
    $recoveryStmt->execute([$recoveryId, (int)$_SESSION['user_id']]);
    if (!$recoveryStmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Esta recuperação não está disponível para sua conta.']);
        exit;
    }
}

if ($rawQuestionId === '' || $chosen === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}
if ($gameId < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Jogo inválido.']);
    exit;
}

$q = null;
$isDatabaseQuestion = false;

// 1. Verifica se a questão foi gerada dinamicamente nesta sessão
if ($recoveryId === null && isset($_SESSION['dynamic_questions'][$rawQuestionId])) {
    $q = $_SESSION['dynamic_questions'][$rawQuestionId];
    $q['id'] = $rawQuestionId;
}

// 2. Se for ID numérico, busca no banco de dados
if (!$q && is_numeric($rawQuestionId)) {
    $stmt = $pdo->prepare('SELECT id, game_id, correct_answer, explanation, is_ai_generated, target_user_id, recovery_id FROM questions WHERE id = ?');
    $stmt->execute([(int)$rawQuestionId]);
    $q = $stmt->fetch();
    $isDatabaseQuestion = (bool)$q;
}

// 3. Fallback estático para questões padrão
if ($recoveryId === null && !$q && is_numeric($rawQuestionId)) {
    $intId = (int)$rawQuestionId;
    $fallbackAnswers = [
        1 => ['correct_answer' => 'B', 'explanation' => '1/2 multiplicado por 2 = 2/2 = 1 xicara inteira!'],
        2 => ['correct_answer' => 'C', 'explanation' => 'Joao consumiu 3 de um total de 8 partes, o que representa 3/8 da pizza.'],
        3 => ['correct_answer' => 'C', 'explanation' => 'Calcular a metade equivale a dividir por 2: (3/4) / 2 = 3/8.'],
        4 => ['correct_answer' => 'A', 'explanation' => 'O copo inteiro representa 3/3. Subtraindo a parte usada: 3/3 - 2/3 = 1/3.'],
        5 => ['correct_answer' => 'B', 'explanation' => '1/4 + 1/4 = 2/4. Simplificando a fracao por 2, obtemos 1/2.'],
        6 => ['correct_answer' => 'B', 'explanation' => '3/4 - 1/4 = 2/4 = 1/2.'],
        7 => ['correct_answer' => 'A', 'explanation' => '3/5 - 1/5 = 2/5 de litro.'],
        8 => ['correct_answer' => 'B', 'explanation' => 'Dividindo numerador e denominador por 2, 2/4 = 1/2.'],
        9 => ['correct_answer' => 'B', 'explanation' => 'A area do retangulo e calculada por base x altura: 5 x 8 = 40 m2.'],
        10 => ['correct_answer' => 'C', 'explanation' => 'O perimetro do quadrado e dado por 4 x lado: 4 x 6 = 24 metros.'],
        11 => ['correct_answer' => 'C', 'explanation' => 'Perimetro = 2 x (4 + 3) = 2 x 7 = 14 metros de cerca.'],
        12 => ['correct_answer' => 'B', 'explanation' => 'A area do quadrado e lado ao quadrado (L^2). Raiz quadrada de 25 = 5 metros.'],
        13 => ['correct_answer' => 'C', 'explanation' => 'Area total da sala = 10 x 4 = 40 m2. Logo, sao necessarias 40 placas de 1m2.'],
        14 => ['correct_answer' => 'C', 'explanation' => 'Area do triangulo = (base x altura) / 2 = (8 x 5) / 2 = 40 / 2 = 20 m2.'],
        15 => ['correct_answer' => 'C', 'explanation' => 'Area do circulo = pi x r^2 = 3 x (3^2) = 3 x 9 = 27 m2.'],
        16 => ['correct_answer' => 'A', 'explanation' => 'Area total: 10 x 6 = 60. Area recortada: 4 x 3 = 12. Area util: 60 - 12 = 48 m2.']
    ];
    if (isset($fallbackAnswers[$intId])) {
        $q = $fallbackAnswers[$intId];
    }
}

if (!$q) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Questão não encontrada']);
    exit;
}

if ($isDatabaseQuestion) {
    $rawQuestionId = (string)(int)$q['id'];
    if ($recoveryId !== null) {
        if (
            (int)($q['recovery_id'] ?? 0) !== $recoveryId
            || (int)($q['target_user_id'] ?? 0) !== (int)$_SESSION['user_id']
            || (int)($q['game_id'] ?? 0) !== $gameId
        ) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Esta questão não pertence à sua recuperação.']);
            exit;
        }
    } elseif (
        !empty($q['recovery_id'])
        || !empty($q['is_ai_generated'])
        || (!empty($q['target_user_id']) && (int)$q['target_user_id'] !== (int)$_SESSION['user_id'])
    ) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Esta questão está atribuída a outro aluno.']);
        exit;
    }
}

$normChosen  = str_replace(',', '.', $chosen);
$normCorrect = str_replace(',', '.', trim($q['correct_answer']));

$isCorrect = false;
if (strcasecmp($normChosen, $normCorrect) === 0) {
    $isCorrect = true;
} elseif (is_numeric($normChosen) && is_numeric($normCorrect)) {
    $isCorrect = (abs((float)$normChosen - (float)$normCorrect) < 0.0001);
}

$points = 0;
if ($isCorrect) {
    $points = $hintUsed ? 45 : 50; // teto máximo por questão: 50pts
}

// Acumula somente respostas autenticadas para o registro da partida.
if (!isset($_SESSION['game_answers'][$gameId])) {
    $_SESSION['game_answers'][$gameId] = ['score' => 0, 'correct' => 0, 'wrong' => 0, 'ids' => [], 'recovery_id' => $recoveryId];
}
$ga = &$_SESSION['game_answers'][$gameId];
if (($ga['recovery_id'] ?? null) !== $recoveryId) {
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => 'A sessão da partida não corresponde a esta recuperação. Recarregue a atividade.']);
    exit;
}
if (!in_array($rawQuestionId, $ga['ids'])) {
    $ga['ids'][] = $rawQuestionId;
    $ga['score'] += $points;
    if ($isCorrect) { $ga['correct']++; } else { $ga['wrong']++; }
}

echo json_encode([
    'success'        => true,
    'correct'        => $isCorrect,
    'points'         => $points,
    'explanation'    => $q['explanation'] ?? '',
    'correct_letter' => $q['correct_answer'],
]);
