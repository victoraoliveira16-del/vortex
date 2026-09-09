<?php
$pageTitle = 'Painel do Professor';
require_once __DIR__ . '/../includes/auth.php';
requireTeacher();

$user = getCurrentUser();

// Alertas de desempenho nao lidos
$stmt = $pdo->prepare('SELECT * FROM notifications WHERE type="alert" AND is_read=0 ORDER BY created_at DESC LIMIT 20');
$stmt->execute();
$alerts = $stmt->fetchAll();

// Marcar como lido
$pdo->prepare('UPDATE notifications SET is_read=1 WHERE type="alert"')->execute();

// Todos os alunos com estatisticas
$stmt = $pdo->prepare('
  SELECT u.id, u.name, u.email, u.level, u.xp, u.avatar_color, u.profile_photo,
    COUNT(gs.id) AS total_games,
    COALESCE(SUM(gs.correct_answers), 0) AS total_correct,
    COALESCE(SUM(gs.wrong_answers), 0) AS total_wrong,
    COALESCE(SUM(gs.score), 0) AS total_score,
    MAX(gs.played_at) AS last_played
  FROM users u
  LEFT JOIN game_sessions gs ON gs.user_id = u.id
  WHERE u.role = "student"
  GROUP BY u.id
  ORDER BY total_score DESC
');
$stmt->execute();
$students = $stmt->fetchAll();

// Desempenho por jogo
$stmt = $pdo->prepare('
  SELECT g.name, g.slug, COUNT(gs.id) AS plays,
    COALESCE(SUM(gs.correct_answers), 0) AS hits,
    COALESCE(SUM(gs.wrong_answers), 0) AS misses,
    COALESCE(AVG(gs.score), 0) AS avg_score
  FROM games g
  LEFT JOIN game_sessions gs ON gs.game_id = g.id
  GROUP BY g.id
');
$stmt->execute();
$gameStats = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="teacher-layout">
  <div class="teacher-header">
    <div>
      <h1 class="teacher-title">
        <i class="fa-solid fa-chalkboard-user"></i> Painel Pedagogico do Professor
      </h1>
      <p class="teacher-subtitle">Acompanhe metricas de desempenho, alertas de lacuna de aprendizado e acione reforco com IA.</p>
    </div>
    <div class="teacher-actions">
      <a href="/vortex/teacher/reports.php" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-file-lines"></i> Relatorios Detalhados
      </a>
    </div>
  </div>

  <!-- Alertas de Desempenho -->
  <?php if (!empty($alerts)): ?>
  <div class="alert-section" aria-live="polite">
    <h2 class="section-heading text-danger">
      <i class="fa-solid fa-triangle-exclamation text-danger"></i> Alertas de Dificuldade Ativos (<?= count($alerts) ?>)
    </h2>
    <?php foreach ($alerts as $a): ?>
    <div class="alert-item">
      <div class="alert-item-icon"><i class="fa-solid fa-bell"></i></div>
      <div>
        <div class="alert-item-title"><?= htmlspecialchars($a['title']) ?></div>
        <div class="alert-item-msg"><?= htmlspecialchars($a['message']) ?></div>
        <div class="alert-item-time"><?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="alert alert-success mb-4" role="status">
    <i class="fa-solid fa-circle-check"></i>
    <span>Nenhum alerta de dificuldade pendente. Todos os alunos estao com indice de aproveitamento adequado!</span>
  </div>
  <?php endif; ?>

  <!-- Stats Grid -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon stat-icon-primary"><i class="fa-solid fa-user-graduate"></i></div>
      <div>
        <div class="stat-value"><?= count($students) ?></div>
        <div class="stat-label">Alunos Cadastrados</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-icon-success"><i class="fa-solid fa-gamepad"></i></div>
      <div>
        <div class="stat-value"><?= array_sum(array_column($students, 'total_games')) ?></div>
        <div class="stat-label">Partidas Jogadas</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-icon-warning"><i class="fa-solid fa-circle-check"></i></div>
      <div>
        <div class="stat-value"><?= array_sum(array_column($students, 'total_correct')) ?></div>
        <div class="stat-label">Acertos Totais</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon stat-icon-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <div>
        <div class="stat-value"><?= count($alerts) ?></div>
        <div class="stat-label">Alertas de Erro</div>
      </div>
    </div>
  </div>

  <!-- Desempenho por Jogo -->
  <h2 class="section-heading"><i class="fa-solid fa-chart-pie"></i> Desempenho por Modulo de Jogo</h2>
  <div class="game-stats-grid">
    <?php foreach ($gameStats as $g):
      $total = $g['hits'] + $g['misses'];
      $acc = $total > 0 ? round($g['hits'] / $total * 100) : 0;
    ?>
    <div class="game-stat-card">
      <h3 class="game-stat-card-title">
        <i class="fa-solid <?= match($g['slug']){ 'fractions'=>'fa-utensils', 'geometry'=>'fa-city', 'mental-math'=>'fa-calculator', default=>'fa-gamepad' } ?> text-primary"></i>
        <?= htmlspecialchars($g['name']) ?>
      </h3>
      <div class="game-stat-row">
        <span class="game-stat-row-label">Partidas Realizadas:</span>
        <span class="game-stat-row-val"><?= $g['plays'] ?></span>
      </div>
      <div class="game-stat-row">
        <span class="game-stat-row-label">Acertos:</span>
        <span class="game-stat-row-val hits-cell"><i class="fa-solid fa-check"></i> <?= $g['hits'] ?></span>
      </div>
      <div class="game-stat-row">
        <span class="game-stat-row-label">Erros:</span>
        <span class="game-stat-row-val misses-cell"><i class="fa-solid fa-xmark"></i> <?= $g['misses'] ?></span>
      </div>
      <div class="game-stat-row">
        <span class="game-stat-row-label">Aproveitamento:</span>
        <span class="game-stat-row-val"><?= $acc ?>%</span>
      </div>
      <div class="game-stat-row">
        <span class="game-stat-row-label">Media de Pontos:</span>
        <span class="game-stat-row-val"><?= round($g['avg_score']) ?> pts</span>
      </div>
      <div class="mt-2">
        <div class="xp-bar-wrap">
          <div class="xp-bar-fill" style="width:<?= $acc ?>%;"></div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Ranking e Desempenho dos Alunos -->
  <h2 class="section-heading"><i class="fa-solid fa-ranking-star"></i> Ranking e Acompanhamento de Alunos</h2>
  <div class="card">
    <div class="card-body-flush overflow-x-auto">
      <table class="student-table" aria-label="Ranking e desempenho de alunos">
        <thead>
          <tr>
            <th>Posicao</th>
            <th>Aluno</th>
            <th>Nivel</th>
            <th>XP</th>
            <th>Partidas</th>
            <th>Acertos</th>
            <th>Aproveitamento</th>
            <th>Pontuacao</th>
            <th>Ultima Partida</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $i => $s):
            $total = $s['total_correct'] + $s['total_wrong'];
            $acc = $total > 0 ? round($s['total_correct'] / $total * 100) : 0;
            $danger = $total > 0 && ($s['total_wrong'] / $total) > 0.6;
          ?>
          <tr class="<?= $danger ? 'student-danger-row' : '' ?>">
            <td>
              <span class="rank-badge <?= $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : 'rank-other')) ?>">
                <?= $i + 1 ?>&deg;
              </span>
            </td>
            <td>
              <div class="align-center" style="display:flex;gap:0.65rem;">
                <div class="avatar avatar-sm" style="background:<?= htmlspecialchars($s['avatar_color'] ?? '#4F46E5') ?>;">
                  <?php if (!empty($s['profile_photo'])): ?><img src="<?= htmlspecialchars($s['profile_photo']) ?>" alt=""><?php else: ?>
                    <?= mb_strtoupper(mb_substr($s['name'], 0, 1)) ?>
                  <?php endif; ?>
                </div>
                <div>
                  <div class="font-bold"><?= htmlspecialchars($s['name']) ?></div>
                  <?php if ($danger): ?>
                    <div class="student-danger-badge">
                      <i class="fa-solid fa-triangle-exclamation"></i> Taxa de erro > 60%
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td><span class="level-badge"><i class="fa-solid fa-star"></i> Nv <?= $s['level'] ?></span></td>
            <td><strong><?= $s['xp'] ?></strong></td>
            <td><?= $s['total_games'] ?></td>
            <td><span class="hits-cell"><i class="fa-solid fa-check"></i> <?= $s['total_correct'] ?></span></td>
            <td>
              <span class="font-bold"><?= $acc ?>%</span>
            </td>
            <td><span class="score-cell"><?= number_format($s['total_score']) ?></span></td>
            <td class="date-cell"><?= $s['last_played'] ? date('d/m H:i', strtotime($s['last_played'])) : '&mdash;' ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($students)): ?>
          <tr>
            <td colspan="9" class="table-empty-td">
              Nenhum aluno cadastrado no sistema ainda.
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Gerar Questoes de Reforco com IA -->
  <div class="ai-generator-card">
    <h2 class="ai-generator-title">
      <i class="fa-solid fa-brain text-primary"></i> Gerador de Questoes de Reforco com IA
    </h2>
    <p class="ai-generator-desc">O motor de IA analisa o historico de erros dos alunos e formula questoes de fixacao contextualizadas para a turma.</p>
    <div class="ai-generator-form">
      <select id="topicSelect" class="form-control ai-select" aria-label="Topico matematico">
        <option value="Fracoes e Razoes">Fracoes e Razoes</option>
        <option value="Geometria Plana">Geometria Plana</option>
      </select>
      <input type="number" id="gameIdInput" placeholder="Game ID" value="1" class="form-control ai-input-id" aria-label="ID do Jogo">
      <button onclick="generateAI()" class="btn btn-primary">
        <i class="fa-solid fa-wand-magic-sparkles"></i> Gerar Reforco com IA
      </button>
    </div>
  </div>
</div>

<script>
async function generateAI(){
  var topic  = document.getElementById('topicSelect').value;
  var gameId = parseInt(document.getElementById('gameIdInput').value) || 1;
  showToast('Processando solicitacao com Claude AI...', 'info');
  try {
    var token = getCsrfToken();
    var res = await fetch('/vortex/api/generate_questions.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token
      },
      body: JSON.stringify({ topic: topic, game_id: gameId, csrf_token: token })
    });
    var data = await res.json();
    if(data.success){
      showToast('Sucesso! Geradas ' + data.generated + ' questoes de reforco para ' + topic, 'success', 6000);
    } else {
      showToast('Aviso: ' + (data.error || 'Falha ao conectar na IA Claude'), 'error');
    }
  } catch(e){
    showToast('Erro de comunicacao com a API de IA', 'error');
  }
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
