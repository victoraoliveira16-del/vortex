<?php
// MathPlay Solutions — Instalador Automático do Banco de Dados
$pageTitle = 'Instalador do Banco de Dados';

$message = '';
$status = '';

$envPath = __DIR__ . '/../.env';
$_env = file_exists($envPath) ? (parse_ini_file($envPath) ?: []) : [];

$username = $_env['DB_USER'] ?? 'root';
$password = $_env['DB_PASS'] ?? '';

$credentialCandidates = [];
$seen = [];
foreach ([$username, 'root'] as $user) {
    foreach ([$password, '', 'mysql'] as $pass) {
        $key = $user . "\0" . $pass;
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $credentialCandidates[] = [$user, $pass];
    }
}

if (isset($_POST['install']) || php_sapi_name() === 'cli') {
    $sqlPath = __DIR__ . '/mathplay.sql';
    if (!file_exists($sqlPath)) {
        $message = 'Arquivo mathplay.sql não encontrado no diretório database.';
        $status = 'danger';
    } else {
        $lastError = null;
        foreach ($credentialCandidates as [$candidateUser, $candidatePass]) {
            try {
                $pdo = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', $candidateUser, $candidatePass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

                $sql = file_get_contents($sqlPath);
                $pdo->exec($sql);

                $message = 'Banco de dados `mathplay` instalado e populado com sucesso!';
                $status = 'success';
                break;
            } catch (Exception $e) {
                $lastError = $e;
            }
        }

        if ($status !== 'success' && $lastError) {
            $message = 'Erro ao instalar banco: ' . $lastError->getMessage();
            $status = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Instalador do Banco de Dados — MathPlay Solutions</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="/vortex/assets/css/style.css">
  <link rel="stylesheet" href="/vortex/assets/css/install.css">
</head>
<body class="install-page">
  <div class="card install-card">
    <div class="install-icon">
      <i class="fa-solid fa-database"></i>
    </div>
    
    <h1 class="install-title">Instalador do Banco de Dados</h1>
    <p class="install-description">
      Clique no botão abaixo para criar o banco <code>mathplay</code> e inserir todas as tabelas, conquistas e questões automaticamente.
    </p>

    <?php if ($message): ?>
      <div class="alert alert-<?= $status ?> install-alert">
        <i class="fa-solid <?= $status === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
        <span><?= htmlspecialchars($message) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($status === 'success'): ?>
      <div class="install-actions">
        <a href="/vortex/index.php" class="btn btn-primary">
          <i class="fa-solid fa-house"></i> Ir para a Página Inicial
        </a>
        <a href="/vortex/login.php" class="btn btn-secondary">
          <i class="fa-solid fa-right-to-bracket"></i> Fazer Login
        </a>
      </div>
    <?php else: ?>
      <form method="POST">
        <button type="submit" name="install" value="1" class="btn btn-primary btn-block btn-lg">
          <i class="fa-solid fa-play"></i> Instalar / Recriar Banco de Dados
        </button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
