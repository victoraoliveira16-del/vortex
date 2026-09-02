<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$questionId = (int)($data['question_id'] ?? 0);
$chosen     = strtoupper(trim($data['chosen'] ?? ''));
$hintUsed   = !empty($data['hint_used']);
$gameId     = (int)($data['game_id'] ?? 0);

if (!$questionId || !in_array($chosen, ['A','B','C','D','TIMEOUT'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

// Busca gabarito no banco
$stmt = $pdo->prepare('SELECT id, correct_answer, explanation FROM questions WHERE id = ?');
$stmt->execute([$questionId]);
$q = $stmt->fetch();

if (!$q) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Questão não encontrada']);
    exit;
}

$isCorrect = ($chosen === $q['correct_answer']);
$points    = 0;
if ($isCorrect) {
    $points = $hintUsed ? 45 : 50; // teto máximo por questão: 50pts
}

// Se o usuário estiver autenticado, acumula na sessão para anti-cheat do save_score
if (isLoggedIn() && $gameId > 0) {
    if (!isset($_SESSION['game_answers'][$gameId])) {
        $_SESSION['game_answers'][$gameId] = ['score' => 0, 'correct' => 0, 'wrong' => 0, 'ids' => []];
    }
    $ga = &$_SESSION['game_answers'][$gameId];
    if (!in_array($questionId, $ga['ids'])) {
        $ga['ids'][]  = $questionId;
        $ga['score'] += $points;
        if ($isCorrect) { $ga['correct']++; } else { $ga['wrong']++; }
    }
}

echo json_encode([
    'success'        => true,
    'correct'        => $isCorrect,
    'points'         => $points,
    'explanation'    => $q['explanation'] ?? '',
    'correct_letter' => $q['correct_answer'],
]);

