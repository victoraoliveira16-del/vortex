<?php
require_once __DIR__ . '/../config.php';

$attempts = [];
$seen = [];
foreach ([DB_USER, 'root'] as $user) {
    foreach ([DB_PASS, '', 'mysql'] as $pass) {
        $key = $user . "\0" . $pass;
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $attempts[] = [$user, $pass];
    }
}

$pdo = null;
$lastError = null;
foreach ($attempts as [$user, $pass]) {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        break;
    } catch (PDOException $e) {
        $lastError = $e;
    }
}

if ($pdo === null) {
    http_response_code(500);
    $message = $lastError instanceof PDOException ? $lastError->getMessage() : 'Verifique as credenciais do MySQL e se o banco mathplay existe.';
    die('<h2>Erro de conexao com o banco de dados.</h2><p>' . htmlspecialchars($message) . '</p>');
}