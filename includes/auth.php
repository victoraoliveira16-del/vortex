<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(string $redirect = '/vortex/login.php'): void {
    if (!isLoggedIn()) { header('Location: ' . $redirect); exit; }
}

function requireTeacher(): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'teacher') { header('Location: /vortex/dashboard.php'); exit; }
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    global $pdo;
    $s = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $s->execute([$_SESSION['user_id']]);
    return $s->fetch() ?: null;
}

function loginUser(string $email, string $password): array {
    global $pdo;
    $s = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $s->execute([trim($email)]);
    $user = $s->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'E-mail ou senha incorretos.'];
    }
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    return ['success' => true, 'role' => $user['role']];
}

function registerUser(string $name, string $email, string $password, string $role): array {
    global $pdo;
    $s = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $s->execute([trim($email)]);
    if ($s->fetch()) return ['success' => false, 'message' => 'Este e-mail já está cadastrado.'];
    if (strlen($password) < 6) return ['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres.'];
    $hash   = password_hash($password, PASSWORD_DEFAULT);
    $colors = ['#4F46E5','#7C3AED','#DB2777','#059669','#D97706','#DC2626','#0284C7'];
    $color  = $colors[array_rand($colors)];
    $s = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, avatar_color) VALUES (?, ?, ?, ?, ?)');
    $s->execute([trim($name), trim($email), $hash, $role, $color]);
    $userId = (int)$pdo->lastInsertId();
    $_SESSION['user_id']   = $userId;
    $_SESSION['user_name'] = trim($name);
    $_SESSION['user_role'] = $role;
    return ['success' => true, 'role' => $role];
}

function logout(): void {
    session_destroy();
    header('Location: /vortex/index.php');
    exit;
}

function addXP(int $userId, int $xp): void {
    global $pdo;
    $pdo->prepare('UPDATE users SET xp = xp + ? WHERE id = ?')->execute([$xp, $userId]);
    $s = $pdo->prepare('SELECT xp FROM users WHERE id = ?');
    $s->execute([$userId]);
    $u = $s->fetch();
    $newLevel = max(1, (int)floor(($u['xp'] ?? 0) / 100) + 1);
    $pdo->prepare('UPDATE users SET level = ? WHERE id = ?')->execute([$newLevel, $userId]);
}

function checkAndUnlockAchievements(int $userId): array {
    global $pdo;
    $s = $pdo->prepare('SELECT xp, level FROM users WHERE id = ?');
    $s->execute([$userId]);
    $user = $s->fetch();
    $s = $pdo->prepare('SELECT COUNT(*) AS c FROM game_sessions WHERE user_id = ?');
    $s->execute([$userId]);
    $gamesPlayed = (int)$s->fetch()['c'];
    $checks = [
        'games_played' => $gamesPlayed,
        'xp_total'     => (int)($user['xp'] ?? 0),
        'level'        => (int)($user['level'] ?? 1),
    ];
    $s = $pdo->prepare('SELECT * FROM achievements');
    $s->execute();
    $allAch  = $s->fetchAll();
    $unlocked = [];
    foreach ($allAch as $ach) {
        $type = $ach['condition_type'];
        if (isset($checks[$type]) && $checks[$type] >= (int)$ach['condition_value']) {
            $ex = $pdo->prepare('SELECT 1 FROM user_achievements WHERE user_id = ? AND achievement_id = ?');
            $ex->execute([$userId, $ach['id']]);
            if (!$ex->fetch()) {
                $pdo->prepare('INSERT INTO user_achievements (user_id, achievement_id) VALUES (?, ?)')->execute([$userId, $ach['id']]);
                $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'achievement', ?, ?)")->execute([
                    $userId, 'Conquista Desbloqueada!', 'Você desbloqueou: ' . $ach['name'] . ' — ' . $ach['description']
                ]);
                $unlocked[] = $ach;
            }
        }
    }
    return $unlocked;
}
