<?php
$pageTitle = 'Criar Conta';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: /vortex/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $role     = in_array($_POST['role'] ?? '', ['student', 'teacher']) ? $_POST['role'] : 'student';

    if (!$name || !$email || !$password || !$confirm) {
        $error = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'E-mail invalido.';
    } elseif (strlen($password) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($password !== $confirm) {
        $error = 'As senhas nao conferem.';
    } else {
        $r = registerUser($name, $email, $password, $role);
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
    <img src="https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?w=900&q=85&auto=format&fit=crop" alt="" class="auth-visual-img">
    <div class="auth-visual-content">
      <div class="auth-visual-icon">
        <i class="fa-solid fa-rocket"></i>
      </div>
      <h2 class="auth-visual-title">Comece sua aventura hoje!</h2>
      <p class="auth-visual-text">Junte-se a MathPlay e transforme a forma como voce aprende Matematica. E gratuito, intuitivo e gamificado!</p>
      <div class="auth-visual-features">
        <div class="auth-visual-feature"><i class="fa-solid fa-circle-check"></i> 2 jogos interativos de alta qualidade</div>
        <div class="auth-visual-feature"><i class="fa-solid fa-circle-check"></i> Sistema completo de medalhas e XP</div>
        <div class="auth-visual-feature"><i class="fa-solid fa-circle-check"></i> Questoes geradas por IA personalizada</div>
      </div>
    </div>
  </div>

  <div class="auth-form-wrap">
    <div class="auth-form-inner">
      <a href="/vortex/index.php" class="auth-logo">
        <img src="/vortex/logo/logo.png" alt="Logo MathPlay" class="auth-logo-img">
        <span class="auth-logo-text">MathPlay</span>
      </a>
      <h1 class="auth-title">Criar conta gratis</h1>
      <p class="auth-subtitle">Preencha os dados abaixo para iniciar sua jornada.</p>

      <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="form-group">
          <label class="form-label" for="name">Nome completo</label>
          <input type="text" id="name" name="name" class="form-control" placeholder="Seu nome completo"
            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required aria-required="true">
        </div>
        <div class="form-group">
          <label class="form-label" for="email">E-mail</label>
          <input type="email" id="email" name="email" class="form-control" placeholder="seu@email.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email" aria-required="true">
        </div>
        <div class="form-group">
          <label class="form-label" for="password">Senha <span class="form-label-hint">(min. 6 caracteres)</span></label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Crie uma senha forte" required minlength="6" aria-required="true">
        </div>
        <div class="form-group">
          <label class="form-label" for="confirm">Confirmar senha</label>
          <input type="password" id="confirm" name="confirm" class="form-control" placeholder="Repita a senha" required aria-required="true">
        </div>
        <div class="form-group">
          <label class="form-label">Tipo de conta</label>
          <div class="radio-group">
            <label class="radio-card <?= (($_POST['role'] ?? 'student') === 'student') ? 'selected' : '' ?>">
              <input type="radio" name="role" value="student" <?= (($_POST['role'] ?? 'student') === 'student') ? 'checked' : '' ?>
                onchange="document.querySelectorAll('.radio-card').forEach(c=>c.classList.remove('selected'));this.closest('.radio-card').classList.add('selected')">
              <i class="fa-solid fa-graduation-cap"></i> Aluno
            </label>
            <label class="radio-card <?= (($_POST['role'] ?? '') === 'teacher') ? 'selected' : '' ?>">
              <input type="radio" name="role" value="teacher" <?= (($_POST['role'] ?? '') === 'teacher') ? 'checked' : '' ?>
                onchange="document.querySelectorAll('.radio-card').forEach(c=>c.classList.remove('selected'));this.closest('.radio-card').classList.add('selected')">
              <i class="fa-solid fa-chalkboard-user"></i> Professor
            </label>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">
          <i class="fa-solid fa-rocket"></i> Criar Minha Conta
        </button>
      </form>
      <p class="auth-footer-text">
        Ja tem conta? <a href="/vortex/login.php" class="auth-footer-link">Entrar agora</a>
      </p>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>