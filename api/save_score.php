<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo nao permitido. Use POST.']);
    exit;
}

if (!isLoggedIn()) {
    // Se não estiver logado, não quebra a interface, apenas informa
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nao autenticado']);
    exit;
}

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$userId = (int)$_SESSION['user_id'];
$gameId = (int)($data['game_id'] ?? 0);
$time   = (int)($data['time_spent'] ?? 0);
$diff   = in_array($data['difficulty'] ?? '', ['easy','medium','hard']) ? $data['difficulty'] : 'easy';

if (!$gameId) {
    echo json_encode(['success' => false, 'error' => 'game_id invalido']);
    exit;
}

$stmt = $pdo->prepare('SELECT slug, topic FROM games WHERE id = ?');
$stmt->execute([$gameId]);
$game = $stmt->fetch();

$isEndless = ($game && $game['slug'] === 'mental-math');
$maxScoreCap = $isEndless ? 50000 : 400;

// Score calculado pelo servidor (ou fallback seguro com teto apropriado ao modo de jogo)
$ga      = $_SESSION['game_answers'][$gameId] ?? null;
$score   = $ga['score']   ?? min($maxScoreCap, max(0, (int)($data['score'] ?? 0)));
$correct = $ga['correct'] ?? max(0, (int)($data['correct'] ?? 0));
$wrong   = $ga['wrong']   ?? max(0, (int)($data['wrong'] ?? 0));
$score   = min($score, $maxScoreCap);

$pdo->prepare(
    'INSERT INTO game_sessions (user_id, game_id, score, correct_answers, wrong_answers, time_spent, difficulty)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
)->execute([$userId, $gameId, $score, $correct, $wrong, $time, $diff]);

$xpGained = max(5, (int)round($score * 0.1));
addXP($userId, $xpGained);
checkAndUnlockAchievements($userId);

if ($game) {
    $total = $correct + $wrong;
    $pct   = $total > 0 ? (int)round($correct / $total * 100) : 0;
    $pdo->prepare(
        'INSERT INTO learning_trail (user_id, topic, progress_pct) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE progress_pct = GREATEST(progress_pct, VALUES(progress_pct))'
    )->execute([$userId, $game['topic'], $pct]);

    if ($total > 0 && ($wrong / $total) > 0.6) {
        $ts = $pdo->prepare('SELECT id FROM users WHERE role = "teacher"');
        $ts->execute();
        foreach ($ts->fetchAll() as $t) {
            $pdo->prepare(
                'INSERT INTO notifications (user_id, type, title, message) VALUES (?, "alert", "Alerta de Desempenho", ?)'
            )->execute([$t['id'], 'Aluno ID ' . $userId . ' errou mais de 60% em: ' . $game['topic']]);
        }
    }
}

unset($_SESSION['game_answers'][$gameId]);
$stmt = $pdo->prepare('SELECT level FROM users WHERE id = ?');
$stmt->execute([$userId]);
$newLevel = (int)$stmt->fetchColumn();
echo json_encode(['success' => true, 'xp_gained' => $xpGained, 'level' => $newLevel, 'score' => $score]);

