<?php
$pageTitle = 'Jogos';
require_once __DIR__ . '/../includes/auth.php';

$stmt = $pdo->prepare('SELECT * FROM games ORDER BY id');
$stmt->execute();
$games = $stmt->fetchAll();

$userStats = [];
if (isLoggedIn()) {
    $stmt = $pdo->prepare('SELECT game_id, MAX(score) AS best, COUNT(*) AS plays FROM game_sessions WHERE user_id=? GROUP BY game_id');
    $stmt->execute([$_SESSION['user_id']]);
    foreach ($stmt->fetchAll() as $s) {
        $userStats[$s['game_id']] = $s;
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container catalog-page">
  <div class="section-header" data-aos="fade-up">
    <span class="section-tag">Biblioteca de Jogos</span>
    <h1 class="section-title">Escolha sua aventura</h1>
    <p class="section-subtitle">Três jogos interativos e gamificados para dominar conteúdos de Matemática do Ensino Fundamental II.</p>
  </div>

  <?php if (!isLoggedIn()): ?>
  <div class="alert alert-info max-w-md mb-5" role="alert">
    <i class="fa-solid fa-circle-info"></i>
    <span><a href="/vortex/login.php" class="font-bold text-primary">Entre na sua conta</a> para salvar seu progresso, subir de nivel e ganhar XP!</span>
  </div>
  <?php endif; ?>

  <div class="game-grid">
    <?php
    $imgs = [
        'fractions'   => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800&q=85&auto=format&fit=crop',
        'geometry'    => 'https://images.unsplash.com/photo-1486325212027-8081e485255e?w=800&q=85&auto=format&fit=crop',
        'mental-math' => 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?w=800&q=85&auto=format&fit=crop'
    ];
    $icons = [
        'fractions'   => 'fa-utensils',
        'geometry'    => 'fa-city',
        'mental-math' => 'fa-calculator'
    ];
    $cardClasses = [
        'fractions'   => 'game-card-fractions',
        'geometry'    => 'game-card-geometry',
        'mental-math' => 'game-card-mental'
    ];

    foreach ($games as $g):
        $stats    = $userStats[$g['id']] ?? null;
        $url      = '/vortex/games/' . $g['slug'] . '.php';
        $img      = $imgs[$g['slug']] ?? 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=800&q=80';
        $icon     = $icons[$g['slug']] ?? 'fa-gamepad';
        $cardCls  = $cardClasses[$g['slug']] ?? '';
    ?>
    <article class="game-card <?= $cardCls ?>" tabindex="0" role="button" aria-label="Jogar <?= htmlspecialchars($g['name']) ?>"
      onclick="window.location='<?= $url ?>'"
      onkeypress="if(event.key==='Enter')window.location='<?= $url ?>'"
      data-aos="fade-up">
      <img src="<?= $img ?>" alt="<?= htmlspecialchars($g['name']) ?>" class="game-card-img" loading="lazy">
      <div class="game-card-gradient"></div>
      <div class="game-card-body">
        <div class="game-card-icon-wrap">
          <i class="fa-solid <?= $icon ?>"></i>
        </div>
        <h2 class="game-card-title"><?= htmlspecialchars($g['name']) ?></h2>
        <p class="game-card-desc"><?= htmlspecialchars($g['description']) ?></p>

        <?php if ($stats): ?>
          <div class="catalog-stats-badge">
            <span><i class="fa-solid fa-gamepad"></i> <?= $stats['plays'] ?> partidas</span>
            <span><i class="fa-solid fa-trophy"></i> Recorde: <?= $stats['best'] ?> pts</span>
          </div>
        <?php endif; ?>

        <a href="<?= $url ?>" class="btn game-btn-play">
          <i class="fa-solid fa-play"></i> Jogar Agora
        </a>
      </div>
      <div class="game-card-footer">
        <span class="game-difficulty">
          <i class="fa-solid fa-bolt text-warning"></i> 15s por Questão
        </span>
        <span>
          <?php if($g['slug'] === 'fractions'): ?>
            <i class="fa-solid fa-lightbulb"></i> Dicas + Leitura em Voz
          <?php elseif($g['slug'] === 'geometry'): ?>
            <i class="fa-solid fa-city"></i> Construção Skyline Visual
          <?php else: ?>
            <i class="fa-solid fa-calculator"></i> Calculadora + Combos Rápidos
          <?php endif; ?>
        </span>
      </div>
    </article>
    <?php endforeach; ?>
  </div>

  <!-- AI Adaptive Banner -->
  <div class="catalog-ai-banner" data-aos="zoom-in">
    <div class="catalog-ai-icon">
      <i class="fa-solid fa-brain"></i>
    </div>
    <h3 class="catalog-ai-title">Inteligencia Artificial Adaptativa</h3>
    <p class="catalog-ai-desc">Apos cada partida, o sistema analisa os erros mais frequentes e aciona a IA Claude para formular novos exercicios sob medida para o seu desenvolvimento.</p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
