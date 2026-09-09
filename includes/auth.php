<?php
if (session_status() === PHP_SESSION_NONE) {
    // Sessão segura: cookie httponly + samesite lax
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false, // mudar para true em HTTPS/produção
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
require_once __DIR__ . '/db.php';

function ensureProfilePhotoColumn(): void {
    global $pdo;
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $column = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_photo'")->fetch();
    if (!$column) {
        $pdo->exec('ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL');
    }
}

function ensureAvatarColorColumn(): void {
    global $pdo;
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $column = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar_color'")->fetch();
    if (!$column) {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar_color VARCHAR(7) NOT NULL DEFAULT '#4F46E5'");
    }
}

ensureProfilePhotoColumn();
ensureAvatarColorColumn();

// ─── CSRF ────────────────────────────────────────────────────────────────────

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ─── RATE LIMIT (login brute-force) ──────────────────────────────────────────

function checkLoginRateLimit(): ?string {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'rl_' . md5($ip);
    $now = time();
    $window = 900; // 15 minutos
    $maxAttempts = 5;

    $data = $_SESSION[$key] ?? ['count' => 0, 'start' => $now];
    if ($now - $data['start'] > $window) {
        $data = ['count' => 0, 'start' => $now]; // reset janela
    }
    if ($data['count'] >= $maxAttempts) {
        $wait = $window - ($now - $data['start']);
        return 'Muitas tentativas. Aguarde ' . ceil($wait / 60) . ' minuto(s) para tentar novamente.';
    }
    return null;
}

function recordFailedLogin(): void {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'rl_' . md5($ip);
    $now = time();
    $window = 900;

    $data = $_SESSION[$key] ?? ['count' => 0, 'start' => $now];
    if ($now - $data['start'] > $window) {
        $data = ['count' => 0, 'start' => $now];
    }
    $data['count']++;
    $_SESSION[$key] = $data;
}

function clearLoginRateLimit(): void {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'rl_' . md5($ip);
    unset($_SESSION[$key]);
}

// ─── AUTH ─────────────────────────────────────────────────────────────────────

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(string $redirect = '/vortex/login.php'): void {
    if (!isLoggedIn() || getCurrentUser() === null) {
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);
        header('Location: ' . $redirect);
        exit;
    }
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
        recordFailedLogin();
        return ['success' => false, 'message' => 'E-mail ou senha incorretos.'];
    }
    // Login bem-sucedido: regenera ID de sessão (anti-fixation)
    session_regenerate_id(true);
    clearLoginRateLimit();
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    return ['success' => true, 'role' => $user['role']];
}

function registerUser(string $name, string $email, string $password, string $role, string $inviteCode = ''): array {
    global $pdo;

    // Validar código de convite para professores
    if ($role === 'teacher') {
        $_envCfg = parse_ini_file(__DIR__ . '/../.env') ?: [];
        $validCode = $_envCfg['TEACHER_INVITE_CODE'] ?? '';
        if (!$validCode || !hash_equals($validCode, $inviteCode)) {
            return ['success' => false, 'message' => 'Código de convite inválido para conta de Professor.'];
        }
    }

    $s = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $s->execute([trim($email)]);
    if ($s->fetch()) return ['success' => false, 'message' => 'Este e-mail já está cadastrado.'];
    if (strlen($password) < 6) return ['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres.'];

    $hash   = password_hash($password, PASSWORD_DEFAULT);
    $colors = ['#4F46E5','#0D9488','#DB2777','#059669','#D97706','#DC2626','#0284C7'];
    $color  = $colors[array_rand($colors)];
    $s = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, avatar_color) VALUES (?, ?, ?, ?, ?)');
    $s->execute([trim($name), trim($email), $hash, $role, $color]);
    $userId = (int)$pdo->lastInsertId();

    // Regenera sessão após cadastro (anti-fixation)
    session_regenerate_id(true);
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

