<?php
if (session_status() === PHP_SESSION_NONE) {
    // Se o usuário marcou "manter conectado", o cookie dura 30 dias; senão, dura até fechar a janela
    $cookieLifetime = !empty($_COOKIE['vortex_remember']) ? (30 * 86400) : 0;
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
               || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => $cookieLifetime,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
require_once __DIR__ . '/db.php';

// Auto-login via cookie seguro de "Manter conectado" se a sessão tiver expirado
try {
    if (!isset($_SESSION['user_id']) && !empty($_COOKIE['vortex_remember']) && $pdo) {
        $rememberData = json_decode(base64_decode($_COOKIE['vortex_remember']), true);
        if (!empty($rememberData['uid']) && !empty($rememberData['hash'])) {
            $s = $pdo->prepare('SELECT * FROM users WHERE id = ?');
            $s->execute([(int)$rememberData['uid']]);
            $u = $s->fetch();
            if ($u && hash_equals($rememberData['hash'], hash('sha256', $u['password_hash'] . 'vortex_salt_2026'))) {
                $_SESSION['user_id']        = $u['id'];
                $_SESSION['user_name']      = $u['name'];
                $_SESSION['user_role']      = $u['role'];
                $_SESSION['remember_me']    = true;
                $_SESSION['just_logged_in'] = true;
            } else {
                setcookie('vortex_remember', '', time() - 3600, '/');
            }
        }
    }
} catch (Throwable $e) {}

function ensureProfilePhotoColumn(): void {
    global $pdo;
    static $checked = false;
    if ($checked || !$pdo) return;
    $checked = true;

    try {
        $column = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_photo'")->fetch();
        if (!$column) {
            $pdo->exec('ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL');
        }
    } catch (Throwable $e) {}
}

function ensureAvatarColorColumn(): void {
    global $pdo;
    static $checked = false;
    if ($checked || !$pdo) return;
    $checked = true;

    try {
        $column = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar_color'")->fetch();
        if (!$column) {
            $pdo->exec("ALTER TABLE users ADD COLUMN avatar_color VARCHAR(7) NOT NULL DEFAULT '#2563EB'");
        }
    } catch (Throwable $e) {}
}

function ensureTargetUserIdColumn(): void {
    global $pdo;
    static $checked = false;
    if ($checked || !$pdo) return;
    $checked = true;

    try {
        $column = $pdo->query("SHOW COLUMNS FROM questions LIKE 'target_user_id'")->fetch();
        if (!$column) {
            $pdo->exec('ALTER TABLE questions ADD COLUMN target_user_id INT NULL DEFAULT NULL AFTER is_ai_generated');
        }

        $foreignKey = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'questions' AND COLUMN_NAME = 'target_user_id' AND REFERENCED_TABLE_NAME = 'users'")->fetch();
        if (!$foreignKey) {
            $pdo->exec('ALTER TABLE questions ADD CONSTRAINT fk_questions_target_user FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL');
        }
    } catch (Throwable $e) {}
}

function ensureClassRecoverySchema(): void {
    global $pdo;
    static $checked = false;
    if ($checked || !$pdo) return;
    $checked = true;

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS classes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(20) NOT NULL UNIQUE,
            max_students INT NOT NULL DEFAULT 30,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("INSERT IGNORE INTO classes (name) VALUES ('7 A'), ('7 B')");

        $classColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'class_id'")->fetch();
        if (!$classColumn) {
            $pdo->exec('ALTER TABLE users ADD COLUMN class_id INT NULL');
        }
        $classForeignKey = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'class_id' AND REFERENCED_TABLE_NAME = 'classes'")->fetch();
        if (!$classForeignKey) {
            $pdo->exec('ALTER TABLE users ADD CONSTRAINT fk_users_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL');
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS recoveries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            teacher_id INT NULL,
            topic VARCHAR(100) NOT NULL,
            game_id INT NULL,
            status ENUM('pending','completed') NOT NULL DEFAULT 'pending',
            score INT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL DEFAULT NULL,
            CONSTRAINT fk_recoveries_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_recoveries_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $targetColumn = $pdo->query("SHOW COLUMNS FROM questions LIKE 'target_user_id'")->fetch();
        if (!$targetColumn) {
            $pdo->exec('ALTER TABLE questions ADD COLUMN target_user_id INT NULL DEFAULT NULL');
        }
        $recoveryColumn = $pdo->query("SHOW COLUMNS FROM questions LIKE 'recovery_id'")->fetch();
        if (!$recoveryColumn) {
            $pdo->exec('ALTER TABLE questions ADD COLUMN recovery_id INT NULL');
        }
        $recoveryForeignKey = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'questions' AND COLUMN_NAME = 'recovery_id' AND REFERENCED_TABLE_NAME = 'recoveries'")->fetch();
        if (!$recoveryForeignKey) {
            $pdo->exec('ALTER TABLE questions ADD CONSTRAINT fk_questions_recovery FOREIGN KEY (recovery_id) REFERENCES recoveries(id) ON DELETE CASCADE');
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS monthly_recovery_audits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            audit_month VARCHAR(7) NOT NULL UNIQUE,
            executed_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            students_audited INT NOT NULL DEFAULT 0,
            students_in_recovery INT NOT NULL DEFAULT 0,
            details_json TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Throwable $e) {}
}

function ensureAchievementsColorColumn(): void {
    global $pdo;
    static $checked = false;
    if ($checked || !$pdo) return;
    $checked = true;

    try {
        $cols = array_column($pdo->query("SHOW COLUMNS FROM achievements")->fetchAll(), 'Field');
        if (!in_array('color', $cols)) {
            $pdo->exec("ALTER TABLE achievements ADD COLUMN color VARCHAR(7) NOT NULL DEFAULT '#4F46E5' AFTER icon");
            $colorMap = ['games_played' => '#4F46E5', 'xp_total' => '#F59E0B', 'level' => '#10B981'];
            foreach ($pdo->query('SELECT id, condition_type FROM achievements')->fetchAll() as $a) {
                $color = $colorMap[$a['condition_type']] ?? '#4F46E5';
                $pdo->prepare('UPDATE achievements SET color = ? WHERE id = ?')->execute([$color, $a['id']]);
            }
        }
    } catch (Throwable $e) {}
}

function ensureDefaultDataExists(): void {
    global $pdo;
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        $c = $pdo->query("SELECT count(*) FROM games")->fetchColumn();
        if ($c == 0) {
            $pdo->exec("
                INSERT INTO games (id, slug, name, topic, description) VALUES
                (1, 'fractions', 'Chef das Fracoes', 'Fracoes e Razoes', 'Monte receitas dividindo ingredientes na proporcao certa!'),
                (2, 'geometry', 'Construtor de Cidades', 'Geometria Plana', 'Construa sua metropole calculando areas e perimetros!'),
                (3, 'mental-math', 'Calculadora Mental', 'Calculo Mental Agil', 'Desafie sua mente com calculos rapidos!');
            ");
        }
    } catch (Exception $e) {}
}

try {
    ensureProfilePhotoColumn();
    ensureAvatarColorColumn();
    ensureTargetUserIdColumn();
    ensureClassRecoverySchema();
    ensureAchievementsColorColumn();
    ensureDefaultDataExists();
} catch (Throwable $e) {}

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

function isTeacher(): bool {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'teacher';
}

function requireLogin(?string $redirect = null): void {
    if ($redirect === null) {
        $redirect = (defined('BASE_URL') ? BASE_URL : '') . '/login.php';
    }
    if (!isLoggedIn() || getCurrentUser() === null) {
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);
        header('Location: ' . $redirect);
        exit;
    }
}

function requireTeacher(): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'teacher') {
        header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/dashboard.php');
        exit;
    }
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    global $pdo;
    $s = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $s->execute([$_SESSION['user_id']]);
    return $s->fetch() ?: null;
}

function loginUser(string $email, string $password, bool $rememberMe = false): array {
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
    $_SESSION['user_id']        = $user['id'];
    $_SESSION['user_name']      = $user['name'];
    $_SESSION['user_role']      = $user['role'];
    $_SESSION['remember_me']    = $rememberMe;
    $_SESSION['just_logged_in'] = true;

    $cookieLifetime = $rememberMe ? (30 * 86400) : 0;
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
               || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    setcookie(session_name(), session_id(), [
        'expires'  => $rememberMe ? (time() + $cookieLifetime) : 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    if ($rememberMe) {
        $rememberData = base64_encode(json_encode([
            'uid'  => $user['id'],
            'hash' => hash('sha256', $user['password_hash'] . 'vortex_salt_2026')
        ]));
        setcookie('vortex_remember', $rememberData, [
            'expires'  => time() + $cookieLifetime,
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        if (isset($_COOKIE['vortex_remember'])) {
            setcookie('vortex_remember', '', time() - 3600, '/');
        }
    }

    return ['success' => true, 'role' => $user['role']];
}

function registerUser(string $name, string $email, string $password, string $role, string $inviteCode = '', ?int $classId = null): array {
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
    if ($role === 'student' && (!$classId || $classId < 1)) {
        return ['success' => false, 'message' => 'Selecione uma turma para a conta de aluno.'];
    }

    $hash   = password_hash($password, PASSWORD_DEFAULT);
    $colors = ['#2563EB','#0D9488','#DB2777','#059669','#D97706','#DC2626','#0284C7'];
    $color  = $colors[array_rand($colors)];
    try {
        $pdo->beginTransaction();
        if ($role === 'student') {
            $classStmt = $pdo->prepare('SELECT id, name, max_students FROM classes WHERE id = ? FOR UPDATE');
            $classStmt->execute([$classId]);
            $class = $classStmt->fetch();
            if (!$class) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'A turma selecionada não existe. Escolha outra turma.'];
            }

            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE class_id = ? AND role = ?');
            $countStmt->execute([$classId, 'student']);
            $studentCount = (int)$countStmt->fetchColumn();
            if ($studentCount >= (int)$class['max_students']) {
                $pdo->rollBack();
                return ['success' => false, 'message' => "A turma {$class['name']} está cheia ({$studentCount}/{$class['max_students']}). Escolha outra turma."];
            }
        }

        $s = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, avatar_color, class_id) VALUES (?, ?, ?, ?, ?, ?)');
        $s->execute([trim($name), trim($email), $hash, $role, $color, $role === 'student' ? $classId : null]);
        $userId = (int)$pdo->lastInsertId();
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e->getCode() === '23000') {
            return ['success' => false, 'message' => 'Este e-mail já está cadastrado.'];
        }
        throw $e;
    }

    // Regenera sessão após cadastro (anti-fixation)
    session_regenerate_id(true);
    $_SESSION['user_id']        = $userId;
    $_SESSION['user_name']      = trim($name);
    $_SESSION['user_role']      = $role;
    $_SESSION['remember_me']    = false;
    $_SESSION['just_logged_in'] = true;
    return ['success' => true, 'role' => $role];
}

function logout(?string $target = null): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        session_destroy();
    }
    if (isset($_COOKIE['vortex_remember'])) {
        setcookie('vortex_remember', '', time() - 3600, '/');
    }
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    $to = $target ?: ((defined('BASE_URL') ? BASE_URL : '') . '/index.php');
    header('Location: ' . $to);
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
    $countRow = $s->fetch();
    $gamesPlayed = (int)($countRow['c'] ?? 0);
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
