<?php
// Configuracao de ambiente e banco de dados
$envPath = __DIR__ . '/.env';
$env = file_exists($envPath) ? (parse_ini_file($envPath) ?: []) : [];

// Detecta se esta rodando em localhost (Laragon) ou hospedagem de producao (InfinityFree / ByetHost)
$isLocalhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'])
    || (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']))
    || strpos(__DIR__, 'laragon') !== false;

if ($isLocalhost) {
    // Ambiente Local (Laragon / PC)
    define('DB_HOST', $env['DB_HOST'] ?? '127.0.0.1');
    define('DB_PORT', $env['DB_PORT'] ?? '3306');
    define('DB_USER', $env['DB_USER'] ?? 'root');
    define('DB_PASS', $env['DB_PASS'] ?? 'mysql');
    define('DB_NAME', $env['DB_NAME'] ?? 'mathplay');
} else {
    // Ambiente de Hospedagem (InfinityFree / ByetHost)
    // Se existir .env na hospedagem, tem prioridade; senao usa as constantes abaixo:
    define('DB_HOST', $env['DB_HOST'] ?? 'sql305.infinityfree.com'); // Pegue o hostname exato na aba "MySQL Databases" do painel
    define('DB_PORT', $env['DB_PORT'] ?? '3306');
    define('DB_USER', $env['DB_USER'] ?? 'if0_43234203');
    define('DB_PASS', $env['DB_PASS'] ?? ''); // Preencha com sua senha do painel InfinityFree
    define('DB_NAME', $env['DB_NAME'] ?? 'if0_43234203_mathplay');
}

// URL Base automatica: funciona tanto no Laragon (/vortex) quanto na raiz da hospedagem ('')
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $baseDir = preg_replace('#/(games|teacher|api|includes)$#', '', $scriptDir);
    $baseUrl = ($baseDir === '/' || $baseDir === '.' || $baseDir === '') ? '' : rtrim($baseDir, '/');
    define('BASE_URL', $baseUrl);
}
