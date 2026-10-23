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
    $message = $lastError instanceof PDOException ? $lastError->getMessage() : 'Verifique as credenciais do MySQL e se o banco existe.';
    $isConnRefused = strpos($message, '2002') !== false || strpos($message, 'refused') !== false;
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Conexão com Banco de Dados — MathPlay</title>
      <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0F172A; color: #F8FAFC; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 1.5rem; }
        .card { background: #1E293B; border: 1px solid #334155; border-radius: 16px; padding: 2rem; max-width: 600px; box-shadow: 0 10px 25px rgba(0,0,0,0.4); }
        h2 { color: #EF4444; margin-top: 0; font-size: 1.35rem; display: flex; align-items: center; gap: 0.5rem; }
        p { color: #94A3B8; line-height: 1.6; font-size: 0.95rem; }
        .code-box { background: #0B132B; padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.88rem; color: #38BDF8; margin: 1rem 0; word-break: break-all; line-height: 1.6; }
        .tip-box { background: rgba(59, 130, 246, 0.12); border-left: 4px solid #3B82F6; padding: 0.85rem 1rem; border-radius: 4px; font-size: 0.88rem; color: #E2E8F0; margin-top: 1rem; line-height: 1.5; }
        code { background: rgba(255,255,255,0.08); padding: 0.15rem 0.4rem; border-radius: 4px; color: #F59E0B; }
      </style>
    </head>
    <body>
      <div class="card">
        <h2>⚠️ Falha na Conexão com o Banco de Dados</h2>
        <p>O sistema não conseguiu se conectar ao MySQL com as configurações atuais:</p>
        <div class="code-box">
          <strong>Servidor:</strong> <?= htmlspecialchars(DB_HOST) ?>:<?= htmlspecialchars(DB_PORT) ?><br>
          <strong>Banco:</strong> <?= htmlspecialchars(DB_NAME) ?><br>
          <strong>Usuário:</strong> <?= htmlspecialchars(DB_USER) ?><br>
          <strong>Erro:</strong> <?= htmlspecialchars($message) ?>
        </div>
        <div class="tip-box">
          <strong>Como resolver:</strong><br>
          <?php if (DB_HOST === '127.0.0.1'): ?>
            • Se estiver no seu computador (Laragon), verifique se o MySQL está iniciado no painel do Laragon.<br>
            • Se estiver na <strong>InfinityFree</strong>, abra o arquivo <code>config.php</code> e preencha <code>DB_HOST</code> com o Hostname MySQL do painel (ex: <code>sql305.infinityfree.com</code>) e a senha da sua conta.
          <?php else: ?>
            • Na hospedagem InfinityFree, certifique-se de que o Hostname MySQL (ex: <code>sql305.infinityfree.com</code>), usuário <code><?= htmlspecialchars(DB_USER) ?></code> e senha da conta estão preenchidos corretamente no arquivo <code>config.php</code>.
          <?php endif; ?>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}