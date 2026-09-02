<?php
$pageTitle = 'Entrar';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: /vortex/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificação CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Recarregue a página e tente novamente.';
    }
    // Rate limit anti brute-force
    if (!$error && ($rl = checkLoginRateLimit())) {
        $error = $rl;
    }
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) {
        $error = 'Preencha todos os campos.';
    } else {
        $r = loginUser($email, $password);
        if ($r['success']) {
            header('Location: ' . ($r['role'] === 'teacher' ? '/vortex/teacher/dashboard.php' : '/vortex/dashboard.php'));
            exit;
        } else {
            $error = $r['message'];
        }
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
  <div class="auth-visual" aria-hidden="true">
    <img src="https://images.unsplash.com/photo-1509062522246-3755977927d7?w=900&q=85&auto=format&fit=crop" alt="" class="auth-visual-img">
    <div class="auth-visual-content">
      <div class="auth-visual-icon">
        <i class="fa-solid fa-graduation-cap"></i>
      </div>
      <h2 class="auth-visual-title">Bem-vindo de volta, explorador!</h2>
      <p class="auth-visual-text">Continue sua jornada matematica de onde parou. Suas conquistas, medalhas e progresso estao salvos esperando por voce.</p>
      <div class="auth-visual-features">
        <div class="auth-visual-feature"><i class="fa-solid fa-circle-check"></i> Progresso e niveis salvos automaticamente</div>
        <div class="auth-visual-feature"><i class="fa-solid fa-circle-check"></i> Conquistas e medalhas para desbloquear</div>
        <div class="auth-visual-feature"><i class="fa-solid fa-circle-check"></i> Desafios adaptativos com IA Claude</div>
      </div>
    </div>
  </div>

  <div class="auth-form-wrap">
    <div class="auth-form-inner">
      <a href="/vortex/index.php" class="auth-logo">
        <img src="/vortex/assets/images/logo.png" alt="Logo MathPlay" class="auth-logo-img">
        <span class="auth-logo-text">MathPlay</span>
      </a>
      <h1 class="auth-title">Entrar na plataforma</h1>
      <p class="auth-subtitle">Use suas credenciais para acessar sua conta de jogador.</p>

      <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" autocomplete="off" novalidate>
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <div class="form-group">
          <label class="form-label" for="email">E-mail</label>
          <input type="email" id="email" name="email" class="form-control" placeholder="seu@email.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="off" aria-required="true">
        </div>
        <div class="form-group">
          <label class="form-label" for="password">Senha</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Sua senha" required autocomplete="new-password" aria-required="true">
        </div>
        <button type="submit" class="btn btn-primary btn-block">
          <i class="fa-solid fa-right-to-bracket"></i> Entrar
        </button>
      </form>
      <p class="auth-footer-text">
        Nao tem conta? <a href="/vortex/register.php" class="auth-footer-link">Cadastre-se gratis</a>
      </p>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>