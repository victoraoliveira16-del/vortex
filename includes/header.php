<?php
require_once __DIR__ . '/auth.php';
$pageTitle = $pageTitle ?? 'MathPlay Solutions';
$pageStyles = $pageStyles ?? match (true) {
  str_ends_with($_SERVER['SCRIPT_NAME'] ?? '', '/index.php') && str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/games/') => ['games.css'],
  str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/games/') => ['games.css'],
  str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/teacher/') => ['teacher.css'],
  str_ends_with($_SERVER['SCRIPT_NAME'] ?? '', '/login.php') || str_ends_with($_SERVER['SCRIPT_NAME'] ?? '', '/register.php') => ['auth.css'],
  str_ends_with($_SERVER['SCRIPT_NAME'] ?? '', '/dashboard.php') => ['dashboard.css'],
  str_ends_with($_SERVER['SCRIPT_NAME'] ?? '', '/profile.php') => ['profile.css'],
  default => ['landing.css'],
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="MathPlay Solutions - Plataforma educacional gamificada para Matematica">
  <title><?= htmlspecialchars($pageTitle) ?> — MathPlay Solutions</title>
  
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Lexend:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- AOS Animations -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
  
  <!-- Modular Custom CSS -->
  <link rel="stylesheet" href="/vortex/assets/css/style.css">
  <?php foreach ($pageStyles as $pageStyle): ?>
    <link rel="stylesheet" href="/vortex/assets/css/<?= htmlspecialchars($pageStyle) ?>">
  <?php endforeach; ?>
  <meta name="csrf-token" content="<?= generateCsrfToken() ?>">
</head>
<body>

<!-- Accessibility Bar -->
<div class="a11y-bar" role="toolbar" aria-label="Ferramentas de Acessibilidade">
  <div class="a11y-inner">
    <span class="a11y-label">Acessibilidade:</span>
    <button onclick="adjustFont(-2)" class="a11y-btn" title="Diminuir fonte" aria-label="Diminuir fonte">A-</button>
    <button onclick="adjustFont(2)"  class="a11y-btn" title="Aumentar fonte" aria-label="Aumentar fonte">A+</button>
    <button onclick="toggleDark()"   class="a11y-btn" id="darkBtn"     title="Modo escuro"><i class="fa-solid fa-moon"></i></button>
    <button onclick="toggleContrast()" class="a11y-btn" id="contrastBtn" title="Alto contraste"><i class="fa-solid fa-circle-half-stroke"></i></button>
    <button onclick="toggleDyslexia()" class="a11y-btn" id="dyslexiaBtn" title="Fonte dislexia">Aa</button>
  </div>
</div>

<!-- Navigation Bar -->
<nav class="navbar" role="navigation" aria-label="Navegacao principal">
  <div class="nav-container">
    <a href="/vortex/index.php" class="nav-logo" aria-label="MathPlay - Inicio">
      <img src="/vortex/assets/images/logo.png" alt="Logo MathPlay" class="nav-logo-img">
      <span class="nav-logo-text">MathPlay</span>
    </a>
    <button class="hamburger" id="hamburger" aria-label="Menu" aria-expanded="false" aria-controls="navLinks">
      <span></span><span></span><span></span>
    </button>
    <ul class="nav-links" id="navLinks" role="menubar">
      <li role="none"><a href="/vortex/index.php"       class="nav-link" role="menuitem"><i class="fa-solid fa-house"></i> Home</a></li>
      <li role="none"><a href="/vortex/games/index.php" class="nav-link" role="menuitem"><i class="fa-solid fa-gamepad"></i> Jogos</a></li>
      <?php if (isset($_SESSION['user_id'])): ?>
        <li role="none"><a href="/vortex/dashboard.php" class="nav-link" role="menuitem"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
        <li role="none"><a href="/vortex/profile.php"   class="nav-link" role="menuitem"><i class="fa-solid fa-user"></i> Perfil</a></li>
        <?php if (($_SESSION['user_role'] ?? '') === 'teacher'): ?>
          <li role="none"><a href="/vortex/teacher/dashboard.php" class="nav-link nav-teacher" role="menuitem"><i class="fa-solid fa-chalkboard-user"></i> Professor</a></li>
        <?php endif; ?>
        <li role="none"><a href="/vortex/includes/logout.php" class="nav-link nav-logout" role="menuitem"><i class="fa-solid fa-right-from-bracket"></i> Sair</a></li>
      <?php else: ?>
        <li role="none"><a href="/vortex/login.php" class="btn btn-primary btn-sm" role="menuitem"><i class="fa-solid fa-right-to-bracket"></i> Entrar</a></li>
      <?php endif; ?>
    </ul>
  </div>
</nav>

<main id="main-content" tabindex="-1">