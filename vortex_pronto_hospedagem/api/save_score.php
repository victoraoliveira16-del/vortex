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
$rawGameId = $data['game_id'] ?? null;
$rawTime = $data['time_spent'] ?? 0;
$gameId = (is_int($rawGameId) || is_string($rawGameId))
    ? filter_var($rawGameId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;
$time = (is_int($rawTime) || is_string($rawTime))
    ? filter_var($rawTime, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 86400]])
    : false;
$diff = $data['difficulty'] ?? 'easy';
$rawRecoveryId = $data['recovery_id'] ?? null;
$recoveryId = null;
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? '');

if (!$csrfToken || !verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sessão inválida. Recarregue a página e tente novamente.']);
    exit;
}

if ($rawRecoveryId !== null && $rawRecoveryId !== '') {
    $recoveryId = filter_var($rawRecoveryId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($recoveryId === false || ($_SESSION['user_role'] ?? '') !== 'student') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Recuperação inválida ou acesso não autorizado.']);
        exit;
    }
}

if ($gameId === false || $time === false || !in_array($diff, ['easy', 'medium', 'hard'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados da partida inválidos.']);
    exit;
}

$stmt = $pdo->prepare('SELECT slug, topic FROM games WHERE id = ?');
$stmt->execute([$gameId]);
$game = $stmt->fetch();
if (!$game) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Jogo não encontrado.']);
    exit;
}

$isEndless = ($game && $game['slug'] === 'mental-math');
$maxScoreCap = $isEndless ? 50000 : 400;

// Nivel anterior do aluno antes do ganho de XP
$uBefore = $pdo->prepare('SELECT level, xp FROM users WHERE id = ?');
$uBefore->execute([$userId]);
$oldUser = $uBefore->fetch();
$oldLevel = (int)($oldUser['level'] ?? 1);

// Score calculado pelo servidor (ou fallback seguro com teto apropriado ao modo de jogo)
$ga      = $_SESSION['game_answers'][$gameId] ?? null;
if (
    !is_array($ga)
    || !isset($ga['score'], $ga['correct'], $ga['wrong'], $ga['ids'])
    || !is_array($ga['ids'])
    || count($ga['ids']) === 0
    || count(array_unique(array_map('strval', $ga['ids']))) !== count($ga['ids'])
    || !is_int($ga['score'])
    || !is_int($ga['correct'])
    || !is_int($ga['wrong'])
    || $ga['score'] < 0
    || $ga['correct'] < 0
    || $ga['wrong'] < 0
    || $ga['correct'] + $ga['wrong'] !== count($ga['ids'])
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nenhuma resposta válida foi registrada para esta partida.']);
    exit;
}

if ($recoveryId !== null) {
    if (($ga['recovery_id'] ?? null) !== $recoveryId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Não há respostas válidas para esta recuperação.']);
        exit;
    }

    $pdo->beginTransaction();
    $recoveryStmt = $pdo->prepare("SELECT id, game_id FROM recoveries WHERE id = ? AND student_id = ? AND status = 'pending' FOR UPDATE");
    $recoveryStmt->execute([$recoveryId, $userId]);
    $recovery = $recoveryStmt->fetch();
    if (!$recovery || (int)$recovery['game_id'] !== $gameId) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Esta recuperação não está disponível para sua conta.']);
        exit;
    }

    $answeredIds = array_values(array_unique(array_map('intval', $ga['ids'] ?? [])));
    $questionCountStmt = $pdo->prepare('SELECT COUNT(*) FROM questions WHERE recovery_id = ? AND target_user_id = ?');
    $questionCountStmt->execute([$recoveryId, $userId]);
    $expectedCount = (int)$questionCountStmt->fetchColumn();
    if (
        $expectedCount < 1
        || count($answeredIds) !== $expectedCount
        || (int)($ga['correct'] ?? 0) + (int)($ga['wrong'] ?? 0) !== $expectedCount
    ) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Responda todas as questões antes de concluir a recuperação.']);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($answeredIds), '?'));
    $ownershipStmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE recovery_id = ? AND target_user_id = ? AND id IN ({$placeholders})");
    $ownershipStmt->execute(array_merge([$recoveryId, $userId], $answeredIds));
    if ((int)$ownershipStmt->fetchColumn() !== $expectedCount) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'As respostas não correspondem a esta recuperação.']);
        exit;
    }
} elseif (($ga['recovery_id'] ?? null) !== null) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Respostas de recuperação não podem ser usadas em uma partida comum.']);
    exit;
}

$score   = (int)$ga['score'];
$correct = (int)$ga['correct'];
$wrong   = (int)$ga['wrong'];
$score   = min($score, $maxScoreCap);

$total = $correct + $wrong;
$accuracy = $total > 0 ? ($correct / $total) : 0;

// Sistema Turbinado de Recompensas de Fase
$baseCompletionXP = 50; // Garantido por terminar a fase
$perfBonusXP = 0;
$perfBonusScore = 0;
$stars = 1;

if ($isEndless) {
    if ($correct >= 12 && $accuracy >= 0.70) {
        $stars = 3;
        $perfBonusXP = 150;
        $perfBonusScore = 100;
    } elseif ($correct >= 6 && $accuracy >= 0.50) {
        $stars = 2;
        $perfBonusXP = 75;
        $perfBonusScore = 50;
    } else {
        $stars = 1;
        $perfBonusXP = 30;
    }
} else {
    if ($accuracy >= 0.80 || ($total > 0 && $wrong === 0)) {
        $stars = 3;
        $perfBonusXP = 150;
        $perfBonusScore = 100;
    } elseif ($accuracy >= 0.50) {
        $stars = 2;
        $perfBonusXP = 75;
        $perfBonusScore = 50;
    } else {
        $stars = 1;
        $perfBonusXP = 30;
    }
}

// Incrementa o score com o bônus de desempenho
$score = min($maxScoreCap, $score + $perfBonusScore);
$rawXP = (int)round($score * 0.15);
$xpGained = $baseCompletionXP + $perfBonusXP + $rawXP;

$pdo->prepare(
    'INSERT INTO game_sessions (user_id, game_id, score, correct_answers, wrong_answers, time_spent, difficulty)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
)->execute([$userId, $gameId, $score, $correct, $wrong, $time, $diff]);

addXP($userId, $xpGained);
$unlockedAchievements = checkAndUnlockAchievements($userId);

if ($game) {
    $total = $correct + $wrong;
    $pct   = $total > 0 ? (int)round($correct / $total * 100) : 0;
    $pdo->prepare(
        'INSERT INTO learning_trail (user_id, topic, progress_pct) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE progress_pct = GREATEST(progress_pct, VALUES(progress_pct))'
    )->execute([$userId, $game['topic'], $pct]);

    if ($total > 0 && ($wrong / $total) > 0.6) {
        $uStmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
        $uStmt->execute([$userId]);
        $studentName = $uStmt->fetchColumn() ?: ('Aluno #' . $userId);

        $ts = $pdo->prepare('SELECT id FROM users WHERE role = "teacher"');
        $ts->execute();
        foreach ($ts->fetchAll() as $t) {
            $pdo->prepare(
                'INSERT INTO notifications (user_id, type, title, message) VALUES (?, "alert", "Alerta de Desempenho", ?)'
            )->execute([$t['id'], "O aluno {$studentName} teve mais de 60% de erros no conteúdo: {$game['topic']}. Recomenda-se aplicar reforço pedagógico."]);
        }
    }
}

if ($recoveryId !== null) {
    $recoveryScore = $total > 0 ? (int)round(($correct / $total) * 100) : 0;
    $completeStmt = $pdo->prepare("UPDATE recoveries SET status = 'completed', score = ?, completed_at = CURRENT_TIMESTAMP WHERE id = ? AND student_id = ? AND status = 'pending'");
    $completeStmt->execute([$recoveryScore, $recoveryId, $userId]);
    if ($completeStmt->rowCount() !== 1) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'A recuperação já foi concluída.']);
        exit;
    }
    $pdo->commit();
}

unset($_SESSION['game_answers'][$gameId]);

$stmt = $pdo->prepare('SELECT level, xp FROM users WHERE id = ?');
$stmt->execute([$userId]);
$newUser = $stmt->fetch();
$newLevel = (int)($newUser['level'] ?? 1);
$currentXP = (int)($newUser['xp'] ?? 0);
$levelUp = ($newLevel > $oldLevel);

echo json_encode([
    'success'         => true,
    'recovery_score'  => $recoveryId !== null ? $recoveryScore : null,
    'xp_gained'       => $xpGained,
    'base_xp'         => $baseCompletionXP,
    'bonus_xp'        => $perfBonusXP,
    'bonus_score'     => $perfBonusScore,
    'stars'           => $stars,
    'score'           => $score,
    'level'           => $newLevel,
    'old_level'       => $oldLevel,
    'level_up'        => $levelUp,
    'current_xp'      => $currentXP,
    'achievements'    => $unlockedAchievements
]);
