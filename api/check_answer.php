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

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? '');
if (!$csrfToken || !verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sessao invalida. Recarregue a pagina e tente novamente.']);
    exit;
}

$questionId = (int)($data['question_id'] ?? 0);
$chosen     = trim((string)($data['chosen'] ?? ''));
$hintUsed   = !empty($data['hint_used']);
$gameId     = (int)($data['game_id'] ?? 0);

if (!$questionId || $chosen === '') {
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
    $_SESSION['game_answers'][$gameId] = ['score' => 0, 'correct' => 0, 'wrong' => 0, 'ids' => []];
}
$ga = &$_SESSION['game_answers'][$gameId];
if (!in_array($questionId, $ga['ids'])) {
    $ga['ids'][] = $questionId;
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

