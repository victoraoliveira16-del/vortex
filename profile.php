<?php
$pageTitle = 'Meu Perfil';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = getCurrentUser();
$xpPct = min(100, ($user['xp'] % 100));

$stmt = $pdo->prepare('SELECT COUNT(*) AS total, SUM(score) AS pts, SUM(correct_answers) AS hits, SUM(wrong_answers) AS misses FROM game_sessions WHERE user_id=?');
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();
$accuracy = ($stats['hits'] + $stats['misses']) > 0 ? round($stats['hits'] / ($stats['hits'] + $stats['misses']) * 100) : 0;

$stmt = $pdo->prepare('SELECT a.* FROM user_achievements ua JOIN achievements a ON a.id=ua.achievement_id WHERE ua.user_id=?');
$stmt->execute([$user['id']]);
$myAch = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT a.*, (SELECT 1 FROM user_achievements ua WHERE ua.user_id=? AND ua.achievement_id=a.id) AS unlocked FROM achievements a');
$stmt->execute([$user['id']]);
$allAch = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="profile-layout">
  <div class="card profile-header-card">
    <div class="profile-banner-wrap">
      <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=1200&q=85&auto=format&fit=crop" alt="" class="profile-banner-img">
    </div>
    <div class="profile-header-content">
      <div class="avatar avatar-lg" style="background:<?= htmlspecialchars($user['avatar_color']) ?>;" aria-label="Avatar">
        <?= mb_strtoupper(mb_substr($user['name'], 0, 1)) ?>
      </div>
      <h1 class="profile-header-title"><?= htmlspecialchars($user['name']) ?></h1>
      <p class="profile-header-subtitle">
        <span><i class="fa-solid <?= $user['role'] === 'teacher' ? 'fa-chalkboard-user' : 'fa-graduation-cap' ?>"></i> <?= $user['role'] === 'teacher' ? 'Professora' : 'Aluno' ?></span>
        <span>&middot;</span>
        <span>Membro desde <?= date('M/Y', strtotime($user['created_at'])) ?></span>
      </p>
      
      <div class="profile-level-wrap">
        <div class="level-badge"><i class="fa-solid fa-star"></i> Nivel <?= $user['level'] ?></div>
        <div class="xp-label">
          <span class="xp-current"><?= $user['xp'] ?> XP</span>
          <span class="xp-next">Proximo nivel: <?= $user['level'] * 100 ?> XP</span>
        </div>
        <div class="xp-bar-wrap">
          <div class="xp-bar-fill" style="width:<?= $xpPct ?>%;"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon stat-icon-primary"><i class="fa-solid fa-gamepad"></i></div>
      <div>
        <div class="stat-value"><?= (int)$stats['total'] ?></div>
        <div class="stat-label">Partidas</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-icon-warning"><i class="fa-solid fa-bolt"></i></div>
      <div>
        <div class="stat-value"><?= $user['xp'] ?></div>
        <div class="stat-label">XP Total</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-icon-success"><i class="fa-solid fa-bullseye"></i></div>
      <div>
        <div class="stat-value"><?= $accuracy ?>%</div>
        <div class="stat-label">Precisao</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-icon-danger"><i class="fa-solid fa-trophy"></i></div>
      <div>
        <div class="stat-value"><?= count($myAch) ?></div>
        <div class="stat-label">Conquistas</div>
      </div>
    </div>
  </div>

  <h2 class="section-heading"><i class="fa-solid fa-medal"></i> Todas as Conquistas</h2>
  <div class="achievements-grid">
    <?php foreach ($allAch as $a): $locked = !$a['unlocked']; ?>
    <div class="achievement-card <?= $locked ? 'locked' : '' ?>" title="<?= $locked ? 'Conquista Bloqueada' : 'Conquista Desbloqueada!' ?>">
      <div class="achievement-icon-wrap" style="background:<?= htmlspecialchars($a['color']) ?>;">
        <i class="fa-solid <?= match($a['condition_type']){'games_played'=>'fa-gamepad','score_fractions'=>'fa-utensils','games_played_geometry'=>'fa-city','xp_total'=>'fa-bolt','level'=>'fa-crown',default=>'fa-trophy'} ?>"></i>
      </div>
      <div class="achievement-name"><?= htmlspecialchars($a['name']) ?></div>
      <div class="achievement-desc"><?= $locked ? 'Bloqueada' : htmlspecialchars($a['description']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="profile-actions">
    <a href="/vortex/dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Voltar ao Dashboard</a>
    <a href="/vortex/games/index.php" class="btn btn-primary"><i class="fa-solid fa-gamepad"></i> Explorar Jogos</a>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
