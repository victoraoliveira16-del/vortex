<?php
$pageTitle = 'Entrar';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error       = '';
$activeRole  = $_POST['selected_role'] ?? 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Recarregue a página e tente novamente.';
    }
    if (!$error && ($rl = checkLoginRateLimit())) {
        $error = $rl;
    }

    $email        = $_POST['email'] ?? '';
    $password     = $_POST['password'] ?? '';
    $selectedRole = in_array($_POST['selected_role'] ?? '', ['student', 'teacher']) ? $_POST['selected_role'] : 'student';
    $rememberMe   = !empty($_POST['remember_me']);

    if (!$error) {
        if (!$email || !$password) {
            $error = 'Preencha todos os campos.';
        } else {
            // Passa o role esperado — loginUser só cria sessão/cookie se bater
            $r = loginUser($email, $password, $rememberMe, $selectedRole);
            if ($r['success']) {
                header('Location: ' . BASE_URL . '/index.php');
                exit;
            } else {
                $error = $r['message'];
            }
        }
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
  <div class="auth-visual" aria-hidden="true">
    <img
      src="<?= $activeRole === 'teacher'
        ? 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?w=900&q=85&auto=format&fit=crop'
        : 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=900&q=85&auto=format&fit=crop' ?>"
      alt=""
      class="auth-visual-img"
      id="auth-bg-img">
    <div class="auth-visual-content">
      <div class="auth-visual-icon">
        <i class="fa-solid <?= $activeRole === 'teacher' ? 'fa-chalkboard-user' : 'fa-graduation-cap' ?>"></i>
      </div>
      <h2 class="auth-visual-title" id="auth-visual-title">
        <?= $activeRole === 'teacher' ? 'Área do Professor' : 'Bem-vindo de volta, explorador!' ?>
      </h2>
      <p class="auth-visual-text" id="auth-visual-text">
        <?= $activeRole === 'teacher'
          ? 'Gerencie turmas, acompanhe o progresso dos alunos e atribua reforços com inteligência artificial.'
          : 'Continue sua jornada matemática de onde parou. Suas conquistas, medalhas e progresso estão salvos.' ?>
      </p>
      <div class="auth-visual-features">
        <?php if ($activeRole === 'teacher'): ?>
          <div class="auth-visual-feature"><i class="fa-solid fa-circle-check"></i> Painel de gestão de alunos</div>
          <div class="auth-visual-feature"><i class="fa-solid fa-circle-check"></i> Atribuição de recuperação individual</div>
          <div class="auth-visual-feature"><i class="fa-solid fa-circle-check"></i> Relatórios de desempenho por turma</div>
        <?php else: ?>
          <div class="auth-visual-feature"><i class="fa-solid fa-circle-check"></i> Progresso e níveis salvos automaticamente</div>
          <div class="auth-visual-feature"><i class="fa-solid fa-circle-check"></i> Conquistas e medalhas para desbloquear</div>
          <div class="auth-visual-feature"><i class="fa-solid fa-circle-check"></i> Desafios adaptativos com IA Claude</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="auth-form-wrap">
    <div class="auth-form-inner">
      <a href="<?= BASE_URL ?>/index.php" class="auth-logo">
        <img src="<?= BASE_URL ?>/assets/images/logo-clean.png" alt="Logo MathPlay" class="auth-logo-img">
        <span class="auth-logo-text">MathPlay</span>
      </a>

      <!-- Seleção de perfil -->
      <div class="role-selector" role="group" aria-label="Selecione seu perfil">
        <button type="button"
          class="role-btn <?= $activeRole === 'student' ? 'role-btn--active' : '' ?>"
          data-role="student"
          aria-pressed="<?= $activeRole === 'student' ? 'true' : 'false' ?>">
          <i class="fa-solid fa-graduation-cap"></i>
          <span>Aluno</span>
        </button>
        <button type="button"
          class="role-btn <?= $activeRole === 'teacher' ? 'role-btn--active role-btn--teacher' : '' ?>"
          data-role="teacher"
          aria-pressed="<?= $activeRole === 'teacher' ? 'true' : 'false' ?>">
          <i class="fa-solid fa-chalkboard-user"></i>
          <span>Professor</span>
        </button>
      </div>

      <h1 class="auth-title" id="auth-form-title">
        <?= $activeRole === 'teacher' ? 'Entrar como Professor' : 'Entrar como Aluno' ?>
      </h1>
      <p class="auth-subtitle" id="auth-form-subtitle">
        <?= $activeRole === 'teacher'
          ? 'Acesse o painel docente com suas credenciais institucionais.'
          : 'Use suas credenciais para acessar sua conta de jogador.' ?>
      </p>

      <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" autocomplete="off" novalidate id="login-form">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="selected_role" id="selected_role" value="<?= htmlspecialchars($activeRole) ?>">

        <div class="form-group">
          <label class="form-label" for="email">E-mail</label>
          <input type="email" id="email" name="email" class="form-control"
            placeholder="seu@email.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required autocomplete="off" aria-required="true">
        </div>
        <div class="form-group">
          <label class="form-label" for="password">Senha</label>
          <div class="password-input-wrap">
            <input type="password" id="password" name="password" class="form-control"
              placeholder="Sua senha"
              required autocomplete="new-password" aria-required="true">
            <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', this)" aria-label="Mostrar senha" title="Mostrar senha">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="form-group remember-me-wrap">
          <label class="remember-me-label" for="remember_me">
            <input type="checkbox" name="remember_me" id="remember_me" value="1" class="remember-me-checkbox" <?= !empty($_POST['remember_me']) ? 'checked' : '' ?>>
            <span>Manter login conectado</span>
          </label>
        </div>

        <button type="submit" class="btn btn-primary btn-block" id="submit-btn">
          <i class="fa-solid fa-right-to-bracket"></i>
          <span id="submit-label">
            <?= $activeRole === 'teacher' ? 'Entrar como Professor' : 'Entrar como Aluno' ?>
          </span>
        </button>
      </form>

      <p class="auth-footer-text">
        Não tem conta? <a href="<?= BASE_URL ?>/register.php" class="auth-footer-link">Cadastre-se grátis</a>
      </p>
    </div>
  </div>
</div>

<style>
/* ── Role Selector ────────────────────────────────── */
.role-selector {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  background: var(--surface-2, #1e1e2e);
  border-radius: 12px;
  padding: 0.35rem;
}
.role-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.65rem 1rem;
  border: none;
  border-radius: 9px;
  background: transparent;
  color: var(--text-muted, #888);
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s, color 0.2s, box-shadow 0.2s;
}
.role-btn:hover {
  background: rgba(255,255,255,0.06);
  color: var(--text, #fff);
}
.role-btn--active {
  background: var(--primary, #4F46E5);
  color: #fff;
  box-shadow: 0 2px 12px rgba(79, 70, 229, 0.35);
}
.role-btn--teacher.role-btn--active {
  background: #0D9488;
  box-shadow: 0 2px 12px rgba(13, 148, 136, 0.35);
}
</style>

<script>
(function () {
  const teacherImg = 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?w=900&q=85&auto=format&fit=crop';
  const studentImg = 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=900&q=85&auto=format&fit=crop';

  const content = {
    student: {
      title: 'Bem-vindo de volta, explorador!',
      text:  'Continue sua jornada matemática de onde parou. Suas conquistas, medalhas e progresso estão salvos.',
      formTitle:    'Entrar como Aluno',
      formSubtitle: 'Use suas credenciais para acessar sua conta de jogador.',
      btnLabel:     'Entrar como Aluno',
      icon:         'fa-graduation-cap',
    },
    teacher: {
      title: 'Área do Professor',
      text:  'Gerencie turmas, acompanhe o progresso dos alunos e atribua reforços com inteligência artificial.',
      formTitle:    'Entrar como Professor',
      formSubtitle: 'Acesse o painel docente com suas credenciais institucionais.',
      btnLabel:     'Entrar como Professor',
      icon:         'fa-chalkboard-user',
    },
  };

  document.querySelectorAll('.role-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const role = this.dataset.role;
      const c    = content[role];

      // Update hidden input
      document.getElementById('selected_role').value = role;

      // Toggle active state on buttons
      document.querySelectorAll('.role-btn').forEach(function (b) {
        b.classList.remove('role-btn--active', 'role-btn--teacher');
        b.setAttribute('aria-pressed', 'false');
      });
      this.classList.add('role-btn--active');
      if (role === 'teacher') this.classList.add('role-btn--teacher');
      this.setAttribute('aria-pressed', 'true');

      // Update sidebar image
      var bgImg = document.getElementById('auth-bg-img');
      if (bgImg) {
        bgImg.style.opacity = '0';
        bgImg.style.transition = 'opacity 0.3s';
        setTimeout(function () {
          bgImg.src = role === 'teacher' ? teacherImg : studentImg;
          bgImg.style.opacity = '1';
        }, 300);
      }

      // Update sidebar icon
      var sideIcon = document.querySelector('.auth-visual-icon i');
      if (sideIcon) {
        sideIcon.className = 'fa-solid ' + c.icon;
      }

      // Update sidebar text
      var vTitle = document.getElementById('auth-visual-title');
      var vText  = document.getElementById('auth-visual-text');
      if (vTitle) vTitle.textContent = c.title;
      if (vText)  vText.textContent  = c.text;

      // Update form heading
      var fTitle    = document.getElementById('auth-form-title');
      var fSubtitle = document.getElementById('auth-form-subtitle');
      if (fTitle)    fTitle.textContent    = c.formTitle;
      if (fSubtitle) fSubtitle.textContent = c.formSubtitle;

      // Update submit button label
      var submitLabel = document.getElementById('submit-label');
      if (submitLabel) submitLabel.textContent = c.btnLabel;
    });
  });
})();
function togglePasswordVisibility(inputId, btn) {
  var input = document.getElementById(inputId);
  if (!input) return;
  var icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
    btn.setAttribute('aria-label', 'Ocultar senha');
    btn.setAttribute('title', 'Ocultar senha');
  } else {
    input.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
    btn.setAttribute('aria-label', 'Mostrar senha');
    btn.setAttribute('title', 'Mostrar senha');
  }
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>