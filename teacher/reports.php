<?php
$pageTitle = 'Relatorios Pedagogicos';
require_once __DIR__ . '/../includes/auth.php';
requireTeacher();

// Buscar todos os alunos com suas sessoes agrupadas por jogo
$stmt = $pdo->prepare('
  SELECT u.id, u.name, u.email, u.level, u.xp, u.avatar_color,
    g.name AS game_name, g.topic, g.slug,
    COUNT(gs.id) AS plays,
    COALESCE(SUM(gs.correct_answers), 0) AS hits,
    COALESCE(SUM(gs.wrong_answers), 0) AS misses,
    COALESCE(AVG(gs.score), 0) AS avg_score,
    MAX(gs.played_at) AS last_played
  FROM users u
  JOIN game_sessions gs ON gs.user_id = u.id
  JOIN games g ON g.id = gs.game_id
  WHERE u.role = "student"
  GROUP BY u.id, g.id
  ORDER BY u.name, g.name
');
$stmt->execute();
$rows = $stmt->fetchAll();

// Agrupar dados por aluno
$byStudent = [];
foreach ($rows as $r) {
    $byStudent[$r['id']]['name'] = $r['name'];
    $byStudent[$r['id']]['level'] = $r['level'];
    $byStudent[$r['id']]['xp'] = $r['xp'];
    $byStudent[$r['id']]['avatar_color'] = $r['avatar_color'] ?? '#4F46E5';
    $byStudent[$r['id']]['games'][] = $r;
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="teacher-layout">
  <div class="teacher-header">
    <div>
      <h1 class="teacher-title">
        <i class="fa-solid fa-file-lines"></i> Relatorios Pedagogicos Individuais
      </h1>
      <p class="teacher-subtitle">Acompanhamento detalhado de aproveitamento por estudante e modulo curricular.</p>
    </div>
    <div class="teacher-actions">
      <a href="/vortex/teacher/dashboard.php" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Painel Geral
      </a>
      <button onclick="window.print()" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-print"></i> Imprimir Relatorio
      </button>
    </div>
  </div>

  <?php if (empty($byStudent)): ?>
  <div class="card empty-state-card">
    <div class="empty-state-icon">
      <i class="fa-solid fa-chart-column"></i>
    </div>
    <h3 class="empty-state-title">Nenhum dado consolidado no momento</h3>
    <p class="empty-state-text">Os alunos cadastrados ainda nao iniciaram sessoes de jogo na plataforma.</p>
  </div>
  <?php else: ?>

  <?php foreach ($byStudent as $sid => $student): ?>
  <div class="card mb-3">
    <div class="card-header">
      <div class="align-center" style="display:flex;gap:0.85rem;">
        <div class="avatar avatar-md" style="background:<?= htmlspecialchars($student['avatar_color']) ?>;">
          <?= mb_strtoupper(mb_substr($student['name'], 0, 1)) ?>
        </div>
        <div>
          <div class="student-name-title"><?= htmlspecialchars($student['name']) ?></div>
          <div class="student-meta-sub">
            Nivel <?= $student['level'] ?> &middot; <?= $student['xp'] ?> XP
          </div>
        </div>
      </div>
      <div>
        <span class="level-badge"><i class="fa-solid fa-graduation-cap"></i> Estudante</span>
      </div>
    </div>
    <div class="card-body-flush overflow-x-auto">
      <table class="student-table" aria-label="Desempenho do aluno por jogo">
        <thead>
          <tr>
            <th>Modulo / Topico</th>
            <th>Partidas</th>
            <th>Acertos</th>
            <th>Erros</th>
            <th>Aproveitamento</th>
            <th>Media de Pontos</th>
            <th>Ultima Atividade</th>
            <th>Diagnostico</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($student['games'] as $g):
            $total = $g['hits'] + $g['misses'];
            $acc = $total > 0 ? round($g['hits'] / $total * 100) : 0;
            $danger = $total > 0 && ($g['misses'] / $total) > 0.6;
          ?>
          <tr class="<?= $danger ? 'student-danger-row' : '' ?>">
            <td>
              <div class="game-title-sm"><?= htmlspecialchars($g['game_name']) ?></div>
              <div class="game-topic-sub"><?= htmlspecialchars($g['topic']) ?></div>
            </td>
            <td><?= $g['plays'] ?></td>
            <td><span class="hits-cell"><i class="fa-solid fa-check"></i> <?= $g['hits'] ?></span></td>
            <td><span class="misses-cell"><i class="fa-solid fa-xmark"></i> <?= $g['misses'] ?></span></td>
            <td>
              <span class="percent-bold"><?= $acc ?>%</span>
            </td>
            <td><span class="score-cell"><?= round($g['avg_score']) ?> pts</span></td>
            <td class="date-cell"><?= date('d/m/Y H:i', strtotime($g['last_played'])) ?></td>
            <td>
              <?php if ($danger): ?>
                <span class="status-badge status-badge-danger">
                  <i class="fa-solid fa-triangle-exclamation"></i> Requer Atencao
                </span>
              <?php elseif ($acc >= 80): ?>
                <span class="status-badge status-badge-ok">
                  <i class="fa-solid fa-circle-check"></i> Dominado
                </span>
              <?php else: ?>
                <span class="status-badge status-badge-warn">
                  <i class="fa-solid fa-chart-line"></i> Em Evolucao
                </span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
