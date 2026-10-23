<?php
$pageTitle = 'Criar Conta';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';
$classId = null;
$classStmt = $pdo->query('
    SELECT c.id, c.name, c.max_students, COUNT(u.id) AS student_count
    FROM classes c
    LEFT JOIN users u ON u.class_id = c.id AND u.role = "student"
    GROUP BY c.id
    ORDER BY c.name
');
$classes = $classStmt->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificação CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Recarregue a página e tente novamente.';
    }
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm'] ?? '';
    $role       = in_array($_POST['role'] ?? '', ['student', 'teacher']) ? $_POST['role'] : 'student';
    $inviteCode = trim($_POST['invite_code'] ?? '');
    $rawClassId = $_POST['class_id'] ?? '';
    if ($role === 'student') {
        $parsedClassId = filter_var($rawClassId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $classId = $parsedClassId === false ? null : $parsedClassId;
    }

    if (!$error) {
        if (!$name || !$email || !$password || !$confirm) {
            $error = 'Preencha todos os campos.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'E-mail invalido.';
        } elseif (strlen($password) < 6) {
            $error = 'A senha deve ter pelo menos 6 caracteres.';
        } elseif ($password !== $confirm) {
            $error = 'As senhas nao conferem.';
        } elseif ($role === 'student' && !$classId) {
            $error = 'Selecione uma turma para sua conta.';
        } else {
            $r = registerUser($name, $email, $password, $role, $inviteCode, $classId);
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
    <img src="https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?w=900&q=85&auto=format&fit=crop" alt="" class="auth-visual-img">
    <div class="auth-visual-content">
      <div class="auth-visual-icon">
        <i class="fa-solid fa-rocket"></i>
      </div>
      <h2 class="auth-visual-title">Comece sua aventura hoje!</h2>
      <p class="auth-visual-text">Junte-se a MathPlay e transforme a forma como voce aprende Matematica. E gratuito, intuitivo e gamificado!</p>
      <div class="auth-visual-features">
        <div class="auth-visual-feature"><i class="fa-solid fa-circle-check"></i> 3 jogos interativos de alta qualidade</div>
        <div class="auth-visual-feature"><i class="fa-solid fa-circle-check"></i> Sistema completo de medalhas e XP</div>
        <div class="auth-visual-feature"><i class="fa-solid fa-circle-check"></i> Questoes geradas por IA personalizada</div>
      </div>
    </div>
  </div>

  <div class="auth-form-wrap">
    <div class="auth-form-inner">
      <a href="<?= BASE_URL ?>/index.php" class="auth-logo">
        <img src="<?= BASE_URL ?>/assets/images/logo-clean.png" alt="Logo MathPlay" class="auth-logo-img">
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

      <form method="POST" autocomplete="off" novalidate>
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <div class="form-group">
          <label class="form-label" for="name">Nome completo</label>
          <input type="text" id="name" name="name" class="form-control" placeholder="Seu nome completo"
            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required autocomplete="off" aria-required="true">
        </div>
        <div class="form-group">
          <label class="form-label" for="email">E-mail</label>
          <input type="email" id="email" name="email" class="form-control" placeholder="seu@email.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="off" aria-required="true">
        </div>
        <div class="form-group">
          <label class="form-label" for="password">Senha <span class="form-label-hint">(min. 6 caracteres)</span></label>
          <div class="password-input-wrap">
            <input type="password" id="password" name="password" class="form-control" placeholder="Crie uma senha forte" required minlength="6" autocomplete="new-password" aria-required="true">
            <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', this)" aria-label="Mostrar senha" title="Mostrar senha">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="confirm">Confirmar senha</label>
          <div class="password-input-wrap">
            <input type="password" id="confirm" name="confirm" class="form-control" placeholder="Repita a senha" required autocomplete="new-password" aria-required="true">
            <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('confirm', this)" aria-label="Mostrar senha" title="Mostrar senha">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Tipo de conta</label>
          <div class="radio-group">
            <label class="radio-card <?= (($_POST['role'] ?? 'student') === 'student') ? 'selected' : '' ?>">
              <input type="radio" name="role" value="student" <?= (($_POST['role'] ?? 'student') === 'student') ? 'checked' : '' ?>
                onchange="document.querySelectorAll('.radio-card').forEach(c=>c.classList.remove('selected'));this.closest('.radio-card').classList.add('selected');toggleClassField()">
              <i class="fa-solid fa-graduation-cap"></i> Aluno
            </label>
            <label class="radio-card <?= (($_POST['role'] ?? '') === 'teacher') ? 'selected' : '' ?>">
              <input type="radio" name="role" value="teacher" <?= (($_POST['role'] ?? '') === 'teacher') ? 'checked' : '' ?>
                onchange="document.querySelectorAll('.radio-card').forEach(c=>c.classList.remove('selected'));this.closest('.radio-card').classList.add('selected');document.getElementById('invite-wrap').style.display='block';toggleClassField()">
              <i class="fa-solid fa-chalkboard-user"></i> Professor
            </label>
          </div>
        </div>

        <div class="form-group" id="class-wrap" style="display:<?= (($_POST['role'] ?? 'student') === 'student') ? 'block' : 'none' ?>;">
          <label class="form-label" for="class_id">Turma</label>
          <select class="form-control" id="class_id" name="class_id" <?= (($_POST['role'] ?? 'student') === 'student') ? 'required' : 'disabled' ?>>
            <option value="">Selecione sua turma</option>
            <?php foreach ($classes as $class):
              $classFull = (int)$class['student_count'] >= (int)$class['max_students'];
            ?>
              <option value="<?= (int)$class['id'] ?>" <?= (int)$classId === (int)$class['id'] ? 'selected' : '' ?> <?= $classFull ? 'disabled' : '' ?>>
                <?= htmlspecialchars($class['name']) ?> (<?= (int)$class['student_count'] ?>/<?= (int)$class['max_students'] ?>)<?= $classFull ? ' — cheia' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Código de convite — aparece só quando Professor for selecionado -->
        <div class="form-group" id="invite-wrap" style="display:<?= (($_POST['role'] ?? '') === 'teacher') ? 'block' : 'none' ?>;">
          <label class="form-label" for="invite_code">
            Código de Convite <span class="form-label-hint">(obrigatório para Professores)</span>
          </label>
          <input type="text" id="invite_code" name="invite_code" class="form-control"
            placeholder="Digite o código fornecido pela instituição"
            value="<?= htmlspecialchars($_POST['invite_code'] ?? '') ?>">
        </div>

        <button type="submit" class="btn btn-primary btn-block">
          <i class="fa-solid fa-rocket"></i> Criar Minha Conta
        </button>
      </form>
      <!-- Ocultar campo convite quando Aluno estiver selecionado -->
      <script>
        document.querySelectorAll('input[name="role"]').forEach(function(r){
          if(r.value==='student'){
            r.addEventListener('change',function(){
              document.getElementById('invite-wrap').style.display='none';
            });
          }
        });
        function toggleClassField(){
          var isStudent = document.querySelector('input[name="role"]:checked').value === 'student';
          var wrap = document.getElementById('class-wrap');
          var field = document.getElementById('class_id');
          wrap.style.display = isStudent ? 'block' : 'none';
          field.disabled = !isStudent;
          field.required = isStudent;
        }

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
      <p class="auth-footer-text">
        Ja tem conta? <a href="<?= BASE_URL ?>/login.php" class="auth-footer-link">Entrar agora</a>
      </p>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>