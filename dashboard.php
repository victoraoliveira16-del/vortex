<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = getCurrentUser();
$xpForNextLevel = ($user['level']) * 100;
$xpProgress = $user['xp'] % 100;
$xpPct = min(100, round($xpProgress / 100 * 100));

// Stats
$stmt = $pdo->prepare('SELECT COUNT(*) AS total, SUM(score) AS pts, SUM(correct_answers) AS hits, SUM(wrong_answers) AS misses FROM game_sessions WHERE user_id = ?');
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();

// Recent sessions
$stmt = $pdo->prepare('SELECT gs.*, g.name AS game_name, g.slug FROM game_sessions gs JOIN games g ON g.id=gs.game_id WHERE gs.user_id=? ORDER BY gs.played_at DESC LIMIT 5');
$stmt->execute([$user['id']]);
$sessions = $stmt->fetchAll();

// All achievements for grid display
$stmt = $pdo->prepare('SELECT a.*, (SELECT 1 FROM user_achievements ua WHERE ua.user_id=? AND ua.achievement_id=a.id) AS unlocked FROM achievements a');
$stmt->execute([$user['id']]);
$allAch = $stmt->fetchAll();

// Notifications
$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$user['id']]);
$notifs = $stmt->fetchAll();

// Mark notifications as read
$pdo->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$user['id']]);

// Trail progress
$stmt = $pdo->prepare('SELECT * FROM learning_trail WHERE user_id=?');
$stmt->execute([$user['id']]);
$trails = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="dashboard-layout">
  <!-- SIDEBAR -->
  <aside class="dashboard-sidebar" aria-label="Perfil do jogador">
    <div class="profile-card">
      <div class="profile-banner"></div>
      <div class="profile-avatar-wrap">
        <div class="avatar" style="background:<?= htmlspecialchars($user['avatar_color']) ?>;" aria-hidden="true">
          <?php if (!empty($user['profile_photo'])): ?><img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt=""><?php else: ?>
            <?= mb_strtoupper(mb_substr($user['name'], 0, 1)) ?>
          <?php endif; ?>
        </div>
      </div>
      <div class="profile-info">
        <div class="profile-name"><?= htmlspecialchars($user['name']) ?></div>
        <div class="profile-role"><?= $user['role'] === 'teacher' ? 'Professora' : 'Aluno' ?></div>
        <div class="level-badge"><i class="fa-solid fa-star"></i> Nivel <?= $user['level'] ?></div>
        <div class="xp-label">
          <span class="xp-current"><?= $user['xp'] ?> XP</span>
          <span class="xp-next">Proximo: <?= $xpForNextLevel ?> XP</span>
        </div>
        <div class="xp-bar-wrap">
          <div class="xp-bar-fill" style="width:<?= $xpPct ?>%;" role="progressbar" aria-valuenow="<?= $xpPct ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
      </div>
    </div>

    <!-- Quick Launch -->
    <div class="card">
      <div class="card-header">
        <h3 class="section-heading"><i class="fa-solid fa-bolt"></i> Jogos Rapidos</h3>
      </div>
      <div class="card-body">
        <div class="flex-col gap-md">
          <a href="/vortex/games/mental-math.php" class="quick-game-btn quick-game-fractions">
            <div class="quick-game-icon"><i class="fa-solid fa-calculator"></i></div>
            <span>Calculadora Mental</span>
          </a>
          <a href="/vortex/games/fractions.php" class="quick-game-btn quick-game-fractions">
            <div class="quick-game-icon"><i class="fa-solid fa-utensils"></i></div>
            <span>Chef das Fracoes</span>
          </a>
          <a href="/vortex/games/geometry.php" class="quick-game-btn quick-game-geometry">
            <div class="quick-game-icon"><i class="fa-solid fa-city"></i></div>
            <span>Construtor de Cidades</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Notifications Widget -->
    <?php if (!empty($notifs)): ?>
    <div class="card">
      <div class="card-header">
        <h3 class="section-heading"><i class="fa-solid fa-bell"></i> Notificacoes</h3>
      </div>
      <div class="card-body card-body-flush">
        <div class="notification-list">
          <?php foreach ($notifs as $n): ?>
          <div class="notification-item <?= !$n['is_read'] ? 'unread' : '' ?>">
            <div class="notification-icon-wrap <?= htmlspecialchars($n['type']) ?>">
              <i class="fa-solid <?= match($n['type']){'achievement'=>'fa-trophy','ai'=>'fa-brain','alert'=>'fa-triangle-exclamation',default=>'fa-circle-info'} ?>"></i>
            </div>
            <div>
              <div class="notification-title"><?= htmlspecialchars($n['title']) ?></div>
              <div class="notification-msg"><?= htmlspecialchars($n['message']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </aside>

  <!-- MAIN CONTENT -->
  <div class="dashboard-main">
    <div class="dash-welcome">
      <h1 class="dash-welcome-title">Ola, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>!</h1>
      <p class="dash-welcome-sub">Continue sua jornada matematica e conquiste novos recordes hoje.</p>
    </div>

    <!-- STATS CARDS -->
    <div class="stats-grid" aria-label="Estatisticas gerais">
      <div class="stat-card">
        <div class="stat-icon stat-icon-primary" aria-hidden="true"><i class="fa-solid fa-gamepad"></i></div>
        <div>
          <div class="stat-value"><?= (int)$stats['total'] ?></div>
          <div class="stat-label">Partidas Jogadas</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon stat-icon-warning" aria-hidden="true"><i class="fa-solid fa-bolt"></i></div>
        <div>
          <div class="stat-value"><?= $user['xp'] ?></div>
          <div class="stat-label">XP Acumulado</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon stat-icon-success" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></div>
        <div>
          <div class="stat-value"><?= (int)$stats['hits'] ?></div>
          <div class="stat-label">Acertos Totais</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon stat-icon-danger" aria-hidden="true"><i class="fa-solid fa-award"></i></div>
        <div>
          <div class="stat-value"><?= $user['level'] ?></div>
          <div class="stat-label">Nivel Atual</div>
        </div>
      </div>
    </div>

    <!-- CONQUISTAS -->
    <div>
      <h2 class="section-heading"><i class="fa-solid fa-trophy"></i> Suas Conquistas</h2>
      <div class="achievements-grid" aria-label="Conquistas desbloqueadas">
        <?php 
        $achColors = [
          'games_played' => '#4F46E5',
          'xp_total'     => '#F59E0B',
          'level'        => '#10B981',
        ];
        foreach ($allAch as $a): 
          $locked   = empty($a['unlocked']); 
          $achColor = $a['color'] ?? ($achColors[$a['condition_type'] ?? ''] ?? '#4F46E5');
          $achIcon  = !empty($a['icon']) ? $a['icon'] : match($a['condition_type'] ?? ''){
            'games_played'          => 'fa-gamepad',
            'score_fractions'       => 'fa-utensils',
            'games_played_geometry' => 'fa-city',
            'xp_total'              => 'fa-bolt',
            'level'                 => 'fa-crown',
            default                 => 'fa-trophy'
          };
        ?>
        <div class="achievement-card <?= $locked ? 'locked' : '' ?>" title="<?= $locked ? 'Conquista Bloqueada' : 'Conquista Desbloqueada!' ?>">
          <div class="achievement-icon-wrap" style="background:<?= htmlspecialchars($achColor) ?>;">
            <i class="fa-solid <?= htmlspecialchars($achIcon) ?>"></i>
          </div>
          <div class="achievement-name"><?= htmlspecialchars($a['name']) ?></div>
          <div class="achievement-desc"><?= $locked ? 'Bloqueada' : htmlspecialchars($a['description']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- TRILHA DE APRENDIZADO -->
    <?php if (!empty($trails)): ?>
    <div class="mb-4">
      <h2 class="section-heading"><i class="fa-solid fa-graduation-cap"></i> Trilha de Aprendizado</h2>
      <div class="card">
        <div class="card-body">
          <?php foreach ($trails as $t): ?>
          <div class="trail-item">
            <div class="trail-topic">
              <span class="trail-topic-name"><?= htmlspecialchars($t['topic']) ?></span>
              <span class="trail-pct"><?= $t['progress_pct'] ?>%</span>
            </div>
            <div class="xp-bar-wrap">
              <div class="xp-bar-fill" style="width:<?= $t['progress_pct'] ?>%;" role="progressbar" aria-valuenow="<?= $t['progress_pct'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- HISTORICO DE PARTIDAS -->
    <?php if (!empty($sessions)): ?>
    <div>
      <h2 class="section-heading"><i class="fa-solid fa-clock-rotate-left"></i> Historico Recente</h2>
      <div class="card">
        <div class="card-body-flush overflow-x-auto">
          <table class="student-table" aria-label="Historico de partidas">
            <thead>
              <tr>
                <th>Jogo</th>
                <th>Pontuacao</th>
                <th>Acertos</th>
                <th>Erros</th>
                <th>Dificuldade</th>
                <th>Data</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sessions as $s): ?>
              <tr>
                <td><strong><?= htmlspecialchars($s['game_name']) ?></strong></td>
                <td><span class="score-cell"><?= $s['score'] ?> pts</span></td>
                <td><span class="hits-cell"><i class="fa-solid fa-check"></i> <?= $s['correct_answers'] ?></span></td>
                <td><span class="misses-cell"><i class="fa-solid fa-xmark"></i> <?= $s['wrong_answers'] ?></span></td>
                <td><?= ucfirst((string)($s['difficulty'] ?? 'easy')) ?></td>
                <td class="date-cell"><?= date('d/m/Y H:i', strtotime($s['played_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="card empty-state-card">
      <div class="empty-state-icon">
        <i class="fa-solid fa-gamepad"></i>
      </div>
      <h3 class="empty-state-title">Ainda nao jogou nenhuma partida!</h3>
      <p class="empty-state-text">Escolha um dos jogos interativos e comece a pontuar para desbloquear conquistas.</p>
      <a href="/vortex/games/index.php" class="btn btn-primary">
        <i class="fa-solid fa-play"></i> Explorar Jogos
      </a>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
