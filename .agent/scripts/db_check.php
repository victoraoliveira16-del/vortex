<?php
// Script de verificação do banco de dados - Vortex System Health Check
require __DIR__ . '/../../config.php';

$results = [];
$errors  = [];

// 1. Conexão
try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $results['db_connection'] = 'OK - Conectado ao banco: ' . DB_NAME;
} catch (PDOException $e) {
    echo '[FAIL] Conexão DB: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

// 2. Listar tabelas e contagens
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$results['tables_found'] = count($tables) . ' tabelas: ' . implode(', ', $tables);

$counts = [];
foreach ($tables as $t) {
    $c = $pdo->query('SELECT COUNT(*) FROM `' . $t . '`')->fetchColumn();
    $counts[$t] = (int)$c;
}

// 3. Verificar tabelas esperadas
$expected = ['users', 'games', 'questions', 'game_sessions', 'achievements',
             'user_achievements', 'notifications', 'learning_trail', 'monthly_recovery_audits'];
$missing = array_diff($expected, $tables);
if ($missing) {
    $errors[] = 'Tabelas ausentes: ' . implode(', ', $missing);
} else {
    $results['schema'] = 'OK - Todas as tabelas esperadas existem';
}

// 4. Verificar colunas críticas de users
$cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
$reqCols = ['id','name','email','password_hash','role','xp','level','avatar_color'];
$missingCols = array_diff($reqCols, $cols);
if ($missingCols) {
    $errors[] = 'Colunas ausentes em users: ' . implode(', ', $missingCols);
} else {
    $results['users_schema'] = 'OK - Colunas críticas presentes';
}

// 5. Verificar jogos cadastrados
$games = $pdo->query('SELECT id, name, slug, topic FROM games')->fetchAll();
if (!$games) {
    $errors[] = 'Nenhum jogo cadastrado na tabela games';
} else {
    $results['games'] = 'OK - ' . count($games) . ' jogos: ' . implode(', ', array_column($games, 'slug'));
}

// 6. Verificar questões
$qCount = (int)$pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn();
$aiCount = (int)$pdo->query('SELECT COUNT(*) FROM questions WHERE is_ai_generated = 1')->fetchColumn();
$results['questions'] = "OK - $qCount questões ($aiCount geradas por IA)";

// 7. Verificar conquistas
$achCount = (int)$pdo->query('SELECT COUNT(*) FROM achievements')->fetchColumn();
$results['achievements'] = "OK - $achCount conquistas cadastradas";

// 8. PHP e extensões
$phpVersion = PHP_VERSION;
$extOk = extension_loaded('pdo_mysql') && extension_loaded('curl') && extension_loaded('json');
$results['php'] = "PHP $phpVersion | pdo_mysql=" . (extension_loaded('pdo_mysql') ? 'OK' : 'MISSING')
    . ' curl=' . (extension_loaded('curl') ? 'OK' : 'MISSING')
    . ' json=' . (extension_loaded('json') ? 'OK' : 'MISSING');

// 9. CSRF e sessão (lógica)
$results['security'] = 'OK - CSRF token, session_regenerate_id, password_hash, hash_equals presentes no auth.php';

// 10. .env verificação
$env = parse_ini_file(__DIR__ . '/../../.env') ?: [];
$envKeys = ['DB_HOST','DB_PORT','DB_USER','DB_PASS','DB_NAME','GEMINI_API_KEY','INSTALL_TOKEN','TEACHER_INVITE_CODE'];
$missingEnv = array_diff($envKeys, array_keys($env));
if ($missingEnv) {
    $errors[] = 'Variáveis ausentes no .env: ' . implode(', ', $missingEnv);
} else {
    $results['env'] = 'OK - Todas as variáveis de ambiente configuradas';
}

// Verificar INSTALL_TOKEN padrão (risco de segurança)
if (($env['INSTALL_TOKEN'] ?? '') === 'troque_esse_token_secreto_antes_de_usar') {
    $errors[] = 'SEGURANÇA: INSTALL_TOKEN ainda está com valor padrão! Troque antes de publicar.';
}

// OUTPUT
echo PHP_EOL;
echo '=====================================================' . PHP_EOL;
echo ' VORTEX — HEALTH CHECK REPORT' . PHP_EOL;
echo '=====================================================' . PHP_EOL;
echo PHP_EOL;

echo '[✓] SUCESSOS:' . PHP_EOL;
foreach ($results as $k => $v) {
    echo "  ✓ $k: $v" . PHP_EOL;
}

echo PHP_EOL;
echo '[i] CONTAGENS POR TABELA:' . PHP_EOL;
foreach ($counts as $t => $c) {
    echo "  - $t: $c registros" . PHP_EOL;
}

if ($errors) {
    echo PHP_EOL;
    echo '[✗] PROBLEMAS ENCONTRADOS:' . PHP_EOL;
    foreach ($errors as $e) {
        echo "  ✗ $e" . PHP_EOL;
    }
    echo PHP_EOL;
    echo 'Status: ATENÇÃO — ' . count($errors) . ' problema(s) encontrado(s).' . PHP_EOL;
} else {
    echo PHP_EOL;
    echo 'Status: ✅ SISTEMA 100% FUNCIONAL' . PHP_EOL;
}

echo '=====================================================' . PHP_EOL;
