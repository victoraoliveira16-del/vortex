<?php
$pageTitle = 'Relatório de Níveis dos Alunos';
require_once __DIR__ . '/../includes/auth.php';
requireTeacher();

// Todos os alunos com totais globais
$stmt = $pdo->prepare('
    SELECT u.id, u.name, u.email, u.level, u.xp, u.avatar_color, u.profile_photo,
        COUNT(gs.id)                              AS total_games,
        COALESCE(SUM(gs.correct_answers), 0)      AS total_correct,
        COALESCE(SUM(gs.wrong_answers),   0)      AS total_wrong,
        COALESCE(SUM(gs.score),           0)      AS total_score,
        MAX(gs.played_at)                         AS last_played
    FROM users u
    LEFT JOIN game_sessions gs ON gs.user_id = u.id
    WHERE u.role = "student"
    GROUP BY u.id
    ORDER BY u.name
');
$stmt->execute();
$students = $stmt->fetchAll();

// Desempenho por jogo de cada aluno
$stmtGames = $pdo->prepare('
    SELECT gs.user_id, g.name AS game_name, g.slug,
        COUNT(gs.id)                          AS plays,
        COALESCE(SUM(gs.correct_answers), 0)  AS hits,
        COALESCE(SUM(gs.wrong_answers),   0)  AS misses,
        COALESCE(AVG(gs.score),           0)  AS avg_score,
        MAX(gs.played_at)                     AS last_played
    FROM game_sessions gs
    JOIN games g ON g.id = gs.game_id
    WHERE gs.user_id IN (SELECT id FROM users WHERE role = "student")
    GROUP BY gs.user_id, gs.game_id
    ORDER BY g.name
');
$stmtGames->execute();
$gameRows = $stmtGames->fetchAll();

$gamesByStudent = [];
foreach ($gameRows as $gr) {
    $gamesByStudent[$gr['user_id']][] = $gr;
}

// Tópicos disponíveis para reforço
$topicOptions = [
    ['value' => 'Fracoes e Razoes',  'label' => 'Chef das Frações (Frações e Razões)', 'game_id' => 1],
    ['value' => 'Geometria Plana',   'label' => 'Construtor de Cidades (Geometria)',    'game_id' => 2],
];

/**
 * Retorna os dados do nível pedagógico (1-4).
 */
function pedagogicLevel(int $acc): array {
    return match(true) {
        $acc >= 80 => [
            'num'    => 4,
            'label'  => 'Muito Acima',
            'desc'   => 'O aluno demonstra domínio sólido do conteúdo e está muito acima da média esperada.',
            'color'  => '#059669',
            'bg'     => 'rgba(5,150,105,.12)',
            'border' => 'rgba(5,150,105,.4)',
            'icon'   => 'fa-trophy',
        ],
        $acc >= 60 => [
            'num'    => 3,
            'label'  => 'Sabe um Pouco Mais',
            'desc'   => 'O aluno já superou o básico e demonstra evolução consistente nos conteúdos.',
            'color'  => '#2563EB',
            'bg'     => 'rgba(37,99,235,.1)',
            'border' => 'rgba(37,99,235,.35)',
            'icon'   => 'fa-chart-line',
        ],
        $acc >= 40 => [
            'num'    => 2,
            'label'  => 'Sabe o Básico',
            'desc'   => 'O aluno tem noção inicial do conteúdo, mas ainda precisa de reforço para consolidar o aprendizado.',
            'color'  => '#D97706',
            'bg'     => 'rgba(217,119,6,.1)',
            'border' => 'rgba(217,119,6,.4)',
            'icon'   => 'fa-seedling',
        ],
        default => [
            'num'    => 1,
            'label'  => 'Não Sabe Nada',
            'desc'   => 'O aluno apresenta grande dificuldade. Intervenção pedagógica imediata é recomendada.',
            'color'  => '#DC2626',
            'bg'     => 'rgba(220,38,38,.1)',
            'border' => 'rgba(220,38,38,.4)',
            'icon'   => 'fa-triangle-exclamation',
        ],
    };
}

// Distribuição geral por nível
$levelCount = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
foreach ($students as $s) {
    $total = $s['total_correct'] + $s['total_wrong'];
    $acc   = $total > 0 ? (int)round($s['total_correct'] / $total * 100) : 0;
    $lv    = pedagogicLevel($acc);
    $levelCount[$lv['num']]++;
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ── Modal de Aplicar Reforço ─────────────────────────────── -->
<div id="reinforceModal" class="rmodal-backdrop" role="dialog" aria-modal="true" aria-labelledby="rmodalTitle" style="display:none;">
  <div class="rmodal">
    <div class="rmodal-header">
      <div class="rmodal-title-wrap">
        <div class="rmodal-icon">
          <i class="fa-solid fa-bullseye"></i>
        </div>
        <div>
          <h2 class="rmodal-title" id="rmodalTitle">Aplicar Reforço</h2>
          <p class="rmodal-subtitle" id="rmodalStudentName">Aluno selecionado</p>
        </div>
      </div>
      <button type="button" class="rmodal-close" onclick="closeModal()" aria-label="Fechar">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="rmodal-body">
      <!-- Nível atual do aluno -->
      <div class="rmodal-level-row" id="rmodalLevelRow">
        <!-- preenchido via JS -->
      </div>

      <div class="rmodal-field">
        <label class="rmodal-label" for="rmodalTopic">
          <i class="fa-solid fa-layer-group"></i> Escolha o tópico de reforço:
        </label>
        <select id="rmodalTopic" class="form-control">
          <?php foreach ($topicOptions as $t): ?>
          <option value="<?= htmlspecialchars($t['value']) ?>" data-game="<?= (int)$t['game_id'] ?>">
            <?= htmlspecialchars($t['label']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="rmodal-info">
        <i class="fa-solid fa-circle-info"></i>
        <span>A IA vai gerar questões personalizadas com base no desempenho do aluno neste tópico. O aluno receberá uma notificação no painel dele para praticar.</span>
      </div>
    </div>

    <div class="rmodal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal()">
        <i class="fa-solid fa-xmark"></i> Cancelar
      </button>
      <button type="button" class="btn btn-primary" id="rmodalApplyBtn" onclick="applyReinforce()">
        <i class="fa-solid fa-wand-magic-sparkles"></i>
        <span id="rmodalApplyLabel">Aplicar Reforço ao Aluno</span>
      </button>
    </div>
  </div>
</div>

<style>
/* ── Modal ─────────────────────────────────────────── */
.rmodal-backdrop {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.65);
  z-index: 9999;
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
  backdrop-filter: blur(4px);
}
.rmodal {
  background: var(--surface, #1a1a2e);
  border: 1.5px solid var(--border, rgba(255,255,255,.12));
  border-radius: 18px;
  width: 100%;
  max-width: 520px;
  box-shadow: 0 24px 60px rgba(0,0,0,.5);
  animation: rmodalIn .22s ease;
}
@keyframes rmodalIn {
  from { opacity:0; transform:translateY(16px) scale(.97); }
  to   { opacity:1; transform:none; }
}
.rmodal-header {
  display: flex; align-items: center; justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--border, rgba(255,255,255,.08));
}
.rmodal-title-wrap { display: flex; align-items: center; gap: .85rem; }
.rmodal-icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: rgba(79,70,229,.15);
  color: #4F46E5;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem; flex-shrink: 0;
}
.rmodal-title   { font-size: 1.1rem; font-weight: 800; margin: 0; }
.rmodal-subtitle{ font-size: .82rem; color: var(--text-muted); margin: 0; }
.rmodal-close {
  width: 34px; height: 34px; border-radius: 8px;
  border: none; background: transparent;
  color: var(--text-muted); cursor: pointer; font-size: 1rem;
  display: flex; align-items: center; justify-content: center;
  transition: background .15s;
}
.rmodal-close:hover { background: rgba(255,255,255,.08); color: var(--text, #fff); }
.rmodal-body   { padding: 1.5rem; display: flex; flex-direction: column; gap: 1.1rem; }
.rmodal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--border, rgba(255,255,255,.08));
  display: flex; gap: .75rem; justify-content: flex-end;
}
.rmodal-field  { display: flex; flex-direction: column; gap: .4rem; }
.rmodal-label  { font-size: .85rem; font-weight: 700; display: flex; align-items: center; gap: .4rem; }
.rmodal-info {
  display: flex; gap: .65rem; align-items: flex-start;
  background: rgba(79,70,229,.08);
  border: 1px solid rgba(79,70,229,.25);
  border-radius: 10px;
  padding: .75rem 1rem;
  font-size: .8rem;
  color: var(--text-muted);
}
.rmodal-info i { color: #4F46E5; flex-shrink: 0; margin-top: .1rem; }
.rmodal-level-row {
  display: flex; align-items: center; gap: .85rem;
  padding: .85rem 1rem;
  border-radius: 12px;
  border: 1.5px solid;
}

/* ── Report-specific styles ────────────────────────── */
.report-header-bar {
  display: flex; align-items: flex-start; justify-content: space-between;
  gap: 1rem; flex-wrap: wrap; margin-bottom: 1.75rem;
}
.report-title-block h1 { font-size: 1.6rem; font-weight: 800; margin: 0 0 .25rem; }
.report-title-block p  { color: var(--text-muted); margin: 0; font-size: .9rem; }
.level-legend {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; margin-bottom: 2rem;
}
.level-legend-card {
  border-radius: 12px; padding: 1rem 1.1rem; border: 1.5px solid;
  display: flex; align-items: center; gap: .85rem;
}
.legend-icon {
  width: 42px; height: 42px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
}
.legend-num  { font-size: 1.4rem; font-weight: 900; line-height: 1; }
.legend-name { font-size: .8rem; font-weight: 700; margin-top: .1rem; }
.legend-range{ font-size: .72rem; color: var(--text-muted); }
.dist-grid {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; margin-bottom: 2rem;
}
.dist-card { border-radius: 12px; padding: .9rem 1rem; border: 1.5px solid; text-align: center; }
.dist-count { font-size: 2rem; font-weight: 900; line-height: 1; }
.dist-label { font-size: .78rem; color: var(--text-muted); margin-top: .2rem; }
.student-report-card {
  border-radius: 14px;
  border: 1.5px solid var(--border, rgba(255,255,255,.1));
  background: var(--surface, #1a1a2e);
  margin-bottom: 1.5rem;
  overflow: hidden;
}
.src-header {
  display: flex; align-items: center; justify-content: space-between;
  gap: 1rem; padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--border, rgba(255,255,255,.08));
  flex-wrap: wrap;
}
.src-header-right { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.src-identity { display: flex; align-items: center; gap: .85rem; }
.src-meta     { font-size: .8rem; color: var(--text-muted); margin-top: .15rem; }
.level-pill {
  display: inline-flex; align-items: center; gap: .5rem;
  border-radius: 999px; padding: .45rem 1rem;
  font-size: .85rem; font-weight: 700; border: 1.5px solid;
}
/* ── Apply button ───────────────── */
.btn-apply-reinforce {
  display: inline-flex; align-items: center; gap: .45rem;
  border-radius: 10px; padding: .5rem 1rem;
  font-size: .82rem; font-weight: 700;
  background: rgba(79,70,229,.12);
  color: #4F46E5;
  border: 1.5px solid rgba(79,70,229,.35);
  cursor: pointer;
  transition: background .18s, border-color .18s, transform .12s;
  white-space: nowrap;
}
.btn-apply-reinforce:hover {
  background: rgba(79,70,229,.22);
  border-color: rgba(79,70,229,.6);
  transform: translateY(-1px);
}
.btn-apply-reinforce:active { transform: translateY(0); }
/* progress rows */
.src-progress-row {
  display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--border, rgba(255,255,255,.08)); flex-wrap: wrap;
}
.src-acc-big   { font-size: 2.4rem; font-weight: 900; line-height: 1; flex-shrink: 0; }
.src-acc-label { font-size: .75rem; color: var(--text-muted); }
.src-level-desc{ flex: 1; min-width: 180px; }
.src-level-title{ font-weight: 700; font-size: .95rem; }
.src-level-sub  { font-size: .8rem; color: var(--text-muted); margin-top: .2rem; }
.src-bar-wrap  { flex: 1; min-width: 160px; }
.src-bar-track { height: 10px; border-radius: 999px; background: var(--surface-2, rgba(255,255,255,.06)); overflow: hidden; }
.src-bar-fill  { height: 100%; border-radius: 999px; transition: width .5s; }
.src-stats-row {
  display: flex; gap: 1.5rem; padding: .75rem 1.25rem;
  font-size: .8rem; color: var(--text-muted); flex-wrap: wrap;
  border-bottom: 1px solid var(--border, rgba(255,255,255,.08));
}
.src-stats-row span { display: flex; align-items: center; gap: .35rem; }
.src-games { padding: .75rem 1.25rem 1rem; }
.src-games-title {
  font-size: .78rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .05em; color: var(--text-muted); margin-bottom: .65rem;
}
.src-game-row {
  display: flex; align-items: center; gap: .85rem; padding: .5rem 0;
  border-bottom: 1px solid var(--border, rgba(255,255,255,.05)); flex-wrap: wrap;
}
.src-game-row:last-child { border-bottom: none; }
.src-game-name { flex: 1; min-width: 130px; font-size: .85rem; font-weight: 600; }
.src-game-bar-wrap { flex: 2; min-width: 120px; }
.src-game-bar-track { height: 7px; border-radius: 999px; background: var(--surface-2, rgba(255,255,255,.06)); overflow: hidden; }
.src-game-bar-fill  { height: 100%; border-radius: 999px; }
.src-game-pct  { font-size: .82rem; font-weight: 700; width: 42px; text-align: right; flex-shrink: 0; }
.rec-box {
  margin: .5rem 1.25rem 1rem; border-radius: 10px; padding: .75rem 1rem;
  border: 1.5px solid; display: flex; gap: .65rem; align-items: flex-start; font-size: .82rem;
}
.rec-box i { flex-shrink: 0; margin-top: .1rem; }
@media print {
  .teacher-actions, .no-print, .btn-apply-reinforce, .rmodal-backdrop { display: none !important; }
  .student-report-card { break-inside: avoid; }
}
@media (max-width: 640px) {
  .level-legend, .dist-grid { grid-template-columns: 1fr 1fr; }
}
</style>

<div class="teacher-layout">
  <!-- Header -->
  <div class="report-header-bar">
    <div class="report-title-block">
      <h1><i class="fa-solid fa-chart-bar"></i> Relatório de Nível Pedagógico</h1>
      <p>Classificação dos alunos em 4 níveis de domínio &mdash; gerado em <?= date('d/m/Y \à\s H:i') ?></p>
    </div>
    <div class="teacher-actions no-print">
      <a href="<?= BASE_URL ?>/teacher/dashboard.php" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Painel Geral
      </a>
      <button onclick="window.print()" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-print"></i> Imprimir
      </button>
    </div>
  </div>

  <!-- Legenda dos 4 Níveis -->
  <div class="level-legend">
    <?php
    $legends = [
      ['num'=>1,'label'=>'Não Sabe Nada',     'range'=>'0–39%',   'color'=>'#DC2626','bg'=>'rgba(220,38,38,.1)',  'border'=>'rgba(220,38,38,.4)',  'icon'=>'fa-triangle-exclamation'],
      ['num'=>2,'label'=>'Sabe o Básico',      'range'=>'40–59%',  'color'=>'#D97706','bg'=>'rgba(217,119,6,.1)', 'border'=>'rgba(217,119,6,.4)',  'icon'=>'fa-seedling'],
      ['num'=>3,'label'=>'Sabe um Pouco Mais', 'range'=>'60–79%',  'color'=>'#2563EB','bg'=>'rgba(37,99,235,.1)', 'border'=>'rgba(37,99,235,.35)', 'icon'=>'fa-chart-line'],
      ['num'=>4,'label'=>'Muito Acima',        'range'=>'80–100%', 'color'=>'#059669','bg'=>'rgba(5,150,105,.12)','border'=>'rgba(5,150,105,.4)',  'icon'=>'fa-trophy'],
    ];
    foreach ($legends as $l): ?>
    <div class="level-legend-card" style="background:<?= $l['bg'] ?>;border-color:<?= $l['border'] ?>;">
      <div class="legend-icon" style="background:<?= $l['color'] ?>22;color:<?= $l['color'] ?>;">
        <i class="fa-solid <?= $l['icon'] ?>"></i>
      </div>
      <div>
        <div class="legend-num" style="color:<?= $l['color'] ?>;">Nível <?= $l['num'] ?></div>
        <div class="legend-name"><?= $l['label'] ?></div>
        <div class="legend-range"><?= $l['range'] ?> de aproveitamento</div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Distribuição da Turma -->
  <?php if (!empty($students)): ?>
  <h2 class="section-heading"><i class="fa-solid fa-users"></i> Distribuição da Turma</h2>
  <div class="dist-grid">
    <?php foreach ($legends as $l):
      $n = $levelCount[$l['num']]; ?>
    <div class="dist-card" style="background:<?= $l['bg'] ?>;border-color:<?= $l['border'] ?>;">
      <div class="dist-count" style="color:<?= $l['color'] ?>;"><?= $n ?></div>
      <div class="dist-label">Nível <?= $l['num'] ?> &mdash; <?= $l['label'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Lista de Alunos -->
  <h2 class="section-heading"><i class="fa-solid fa-user-graduate"></i> Relatório Individual por Aluno</h2>

  <?php if (empty($students)): ?>
  <div class="card empty-state-card">
    <div class="empty-state-icon"><i class="fa-solid fa-chart-column"></i></div>
    <h3 class="empty-state-title">Nenhum aluno cadastrado ainda</h3>
    <p class="empty-state-text">Os alunos precisam jogar pelo menos uma partida para aparecerem aqui.</p>
  </div>
  <?php else: ?>

  <?php foreach ($students as $s):
    $total  = $s['total_correct'] + $s['total_wrong'];
    $acc    = $total > 0 ? (int)round($s['total_correct'] / $total * 100) : 0;
    $lv     = pedagogicLevel($acc);
    $games  = $gamesByStudent[$s['id']] ?? [];
    $rec = match($lv['num']) {
        1 => 'Recomenda-se aplicar recuperação intensiva com questões básicas geradas por IA. Considere acompanhamento individualizado.',
        2 => 'Aluno em fase de consolidação. Reforce os fundamentos com atividades de reforço direcionadas aos tópicos com mais erros.',
        3 => 'Bom progresso! Desafie o aluno com questões de nível intermediário para acelerar a evolução para o nível 4.',
        4 => 'Desempenho excelente. Proponha atividades de extensão e desafios avançados para manter o engajamento.',
    };
    // dados para o modal passados via data-attributes
    $studentJson = htmlspecialchars(json_encode([
        'id'    => (int)$s['id'],
        'name'  => $s['name'],
        'acc'   => $acc,
        'level' => $lv['num'],
        'label' => $lv['label'],
        'color' => $lv['color'],
        'bg'    => $lv['bg'],
        'border'=> $lv['border'],
        'icon'  => $lv['icon'],
    ]), ENT_QUOTES);
  ?>
  <div class="student-report-card">
    <!-- Identidade -->
    <div class="src-header">
      <div class="src-identity">
        <div class="avatar avatar-md" style="background:<?= htmlspecialchars($s['avatar_color'] ?? '#4F46E5') ?>;">
          <?php if (!empty($s['profile_photo'])): ?>
            <img src="<?= htmlspecialchars($s['profile_photo']) ?>" alt="">
          <?php else: ?>
            <?= mb_strtoupper(mb_substr($s['name'], 0, 1)) ?>
          <?php endif; ?>
        </div>
        <div>
          <div style="font-weight:800;font-size:1rem;"><?= htmlspecialchars($s['name']) ?></div>
          <div class="src-meta">
            Nível <?= (int)$s['level'] ?> &middot; <?= (int)$s['xp'] ?> XP
            <?= $s['last_played'] ? '&middot; Última partida: ' . date('d/m/Y', strtotime($s['last_played'])) : '' ?>
          </div>
        </div>
      </div>
      <div class="src-header-right">
        <!-- Level pill -->
        <div class="level-pill" style="color:<?= $lv['color'] ?>;background:<?= $lv['bg'] ?>;border-color:<?= $lv['border'] ?>;">
          <i class="fa-solid <?= $lv['icon'] ?>"></i>
          Nível <?= $lv['num'] ?> &mdash; <?= $lv['label'] ?>
        </div>
        <!-- Botão Aplicar Reforço -->
        <button
          class="btn-apply-reinforce no-print"
          onclick="openModal(<?= $studentJson ?>)"
          title="Selecionar reforço para <?= htmlspecialchars($s['name']) ?>">
          <i class="fa-solid fa-bullseye"></i> Aplicar Reforço
        </button>
      </div>
    </div>

    <!-- Aproveitamento global -->
    <div class="src-progress-row">
      <div>
        <div class="src-acc-big" style="color:<?= $lv['color'] ?>;"><?= $acc ?>%</div>
        <div class="src-acc-label">Aproveitamento<br>Global</div>
      </div>
      <div class="src-level-desc">
        <div class="src-level-title" style="color:<?= $lv['color'] ?>;">
          <i class="fa-solid <?= $lv['icon'] ?>"></i> <?= $lv['label'] ?>
        </div>
        <div class="src-level-sub"><?= $lv['desc'] ?></div>
      </div>
      <div class="src-bar-wrap">
        <div style="font-size:.72rem;color:var(--text-muted);margin-bottom:.3rem;">Progresso</div>
        <div class="src-bar-track">
          <div class="src-bar-fill" style="width:<?= $acc ?>%;background:<?= $lv['color'] ?>;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.7rem;color:var(--text-muted);margin-top:.2rem;">
          <span>0%</span><span>100%</span>
        </div>
      </div>
    </div>

    <!-- Stats rápidos -->
    <div class="src-stats-row">
      <span><i class="fa-solid fa-gamepad" style="color:#4F46E5;"></i> <?= (int)$s['total_games'] ?> partidas</span>
      <span><i class="fa-solid fa-check" style="color:#059669;"></i> <?= (int)$s['total_correct'] ?> acertos</span>
      <span><i class="fa-solid fa-xmark" style="color:#DC2626;"></i> <?= (int)$s['total_wrong'] ?> erros</span>
      <span><i class="fa-solid fa-star" style="color:#F59E0B;"></i> <?= number_format($s['total_score']) ?> pts</span>
    </div>

    <!-- Desempenho por jogo -->
    <?php if (!empty($games)): ?>
    <div class="src-games">
      <div class="src-games-title">Desempenho por módulo</div>
      <?php foreach ($games as $g):
        $gt   = $g['hits'] + $g['misses'];
        $gAcc = $gt > 0 ? (int)round($g['hits'] / $gt * 100) : 0;
        $gLv  = pedagogicLevel($gAcc);
      ?>
      <div class="src-game-row">
        <div class="src-game-name"><?= htmlspecialchars($g['game_name']) ?></div>
        <div class="src-game-bar-wrap">
          <div class="src-game-bar-track">
            <div class="src-game-bar-fill" style="width:<?= $gAcc ?>%;background:<?= $gLv['color'] ?>;"></div>
          </div>
        </div>
        <div class="src-game-pct" style="color:<?= $gLv['color'] ?>;"><?= $gAcc ?>%</div>
        <div>
          <span class="level-pill" style="font-size:.72rem;padding:.25rem .7rem;color:<?= $gLv['color'] ?>;background:<?= $gLv['bg'] ?>;border-color:<?= $gLv['border'] ?>;">
            <i class="fa-solid <?= $gLv['icon'] ?>"></i> Nível <?= $gLv['num'] ?>
          </span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Recomendação pedagógica -->
    <div class="rec-box" style="background:<?= $lv['bg'] ?>;border-color:<?= $lv['border'] ?>;color:<?= $lv['color'] ?>;">
      <i class="fa-solid fa-lightbulb"></i>
      <div>
        <strong>Recomendação pedagógica:</strong>
        <span style="color:var(--text);"> <?= $rec ?></span>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
var _modal = {
  studentId:   null,
  studentName: null,
};

function openModal(student) {
  _modal.studentId   = student.id;
  _modal.studentName = student.name;

  // Header info
  document.getElementById('rmodalStudentName').textContent = student.name;

  // Level row
  var levelRow = document.getElementById('rmodalLevelRow');
  levelRow.style.cssText = 'background:' + student.bg + ';border-color:' + student.border + ';';
  levelRow.innerHTML =
    '<i class="fa-solid ' + student.icon + '" style="color:' + student.color + ';font-size:1.4rem;flex-shrink:0;"></i>' +
    '<div>' +
      '<div style="font-weight:800;color:' + student.color + ';">Nível ' + student.level + ' — ' + student.label + '</div>' +
      '<div style="font-size:.78rem;color:var(--text-muted);">Aproveitamento atual: ' + student.acc + '%</div>' +
    '</div>';

  document.getElementById('rmodalApplyLabel').textContent = 'Aplicar Reforço para ' + student.name.split(' ')[0];
  document.getElementById('reinforceModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('reinforceModal').style.display = 'none';
  document.body.style.overflow = '';
}

// Fechar ao clicar fora
document.getElementById('reinforceModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

// Fechar com ESC
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeModal();
});

async function applyReinforce() {
  if (!_modal.studentId) return;

  var topicSel = document.getElementById('rmodalTopic');
  var opt      = topicSel.options[topicSel.selectedIndex];
  var topic    = topicSel.value;
  var gameId   = parseInt(opt.getAttribute('data-game')) || 1;
  var btn      = document.getElementById('rmodalApplyBtn');
  var firstName = _modal.studentName.split(' ')[0];

  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Gerando com IA...';

  try {
    var token = getCsrfToken ? getCsrfToken() : '';
    var res   = await fetch('<?= BASE_URL ?>/api/generate_questions.php', {
      method:  'POST',
      headers: {
        'Content-Type':  'application/json',
        'X-CSRF-Token':  token
      },
      body: JSON.stringify({
        topic:      topic,
        game_id:    gameId,
        student_id: _modal.studentId,
        csrf_token: token
      })
    });

    var data = await res.json();

    if (data.success) {
      closeModal();
      showToast(
        'Reforço aplicado com sucesso! ' + data.generated + ' questões enviadas para ' + firstName + '. ' +
        'O aluno será notificado no painel.',
        'success',
        7000
      );
    } else {
      showToast('Erro ao aplicar reforço: ' + (data.error || 'Falha desconhecida'), 'error');
    }
  } catch (e) {
    showToast('Erro de comunicação com o servidor.', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> <span id="rmodalApplyLabel">Aplicar Reforço para ' + firstName + '</span>';
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
