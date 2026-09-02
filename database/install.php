<?php
// MathPlay Solutions — Instalador Automático do Banco de Dados
$pageTitle = 'Instalador do Banco de Dados';

$message = '';
$status = '';

if (isset($_POST['install']) || php_sapi_name() === 'cli') {
    try {
        $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        $sqlPath = __DIR__ . '/mathplay.sql';
        if (!file_exists($sqlPath)) {
            throw new Exception('Arquivo mathplay.sql não encontrado no diretório database.');
        }
        
        $sql = file_get_contents($sqlPath);
        $pdo->exec($sql);
        
        $message = 'Banco de dados `mathplay` instalado e populado com sucesso!';
        $status = 'success';
    } catch (Exception $e) {
        $message = 'Erro ao instalar banco: ' . $e->getMessage();
        $status = 'danger';
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
  <link rel="stylesheet" href="/vortex/assets/css/auth.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg);padding:2rem;">
  <div class="card" style="max-width:540px;width:100%;padding:2.5rem;text-align:center;">
    <div style="width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 1.5rem;">
      <i class="fa-solid fa-database"></i>
    </div>
    
    <h1 style="font-size:1.6rem;font-weight:800;margin-bottom:0.5rem;color:var(--text);">Instalador do Banco de Dados</h1>
    <p style="color:var(--text-muted);font-size:0.92rem;margin-bottom:2rem;">
      Clique no botão abaixo para criar o banco <code>mathplay</code> e inserir todas as tabelas, conquistas e questões automaticamente.
    </p>

    <?php if ($message): ?>
      <div class="alert alert-<?= $status ?>" style="margin-bottom:2rem;">
        <i class="fa-solid <?= $status === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
        <span><?= htmlspecialchars($message) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($status === 'success'): ?>
      <div style="display:flex;gap:1rem;justify-content:center;">
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
