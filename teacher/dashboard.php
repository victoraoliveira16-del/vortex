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

// Executar ou recuperar a auditoria do mês atual
require_once __DIR__ . '/../includes/recovery_audit.php';
$currentMonth = date('Y-m');
$monthlyAudit = runMonthlyRecoveryAudit($pdo, $currentMonth, false);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="teacher-layout">
  <div class="teacher-header">
    <div>
      <h1 class="teacher-title">
        <i class="fa-solid fa-chalkboard-user"></i> Painel Pedagógico do Professor
      </h1>
      <p class="teacher-subtitle">Acompanhe métricas de desempenho, alertas de lacuna de aprendizado e acione reforço individual ou para a turma.</p>
    </div>
    <div class="teacher-actions">
      <a href="/vortex/teacher/reports.php" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-file-lines"></i> Relatórios Detalhados
      </a>
    </div>
  </div>

  <!-- Banner de Auditoria Mensal Automática -->
  <div class="monthly-audit-banner">
    <div class="monthly-audit-header">
      <div class="monthly-audit-title-wrap">
        <div class="monthly-audit-icon <?= ($monthlyAudit['students_in_recovery'] > 0) ? 'warning' : 'success' ?>">
          <i class="fa-solid <?= ($monthlyAudit['students_in_recovery'] > 0) ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>"></i>
        </div>
        <div>
          <div class="monthly-audit-badge">
            <i class="fa-solid fa-clock-rotate-left"></i> Rotina Mensal Automática &middot; Mês <?= date('m/Y', strtotime($monthlyAudit['audit_month'] . '-01')) ?>
          </div>
          <h2 class="monthly-audit-heading">
            <?= $monthlyAudit['students_in_recovery'] > 0 
                ? "{$monthlyAudit['students_in_recovery']} aluno(s) em recuperação identificados neste mês" 
                : "Todos os {$monthlyAudit['students_audited']} alunos auditados estão com bom rendimento!" ?>
          </h2>
          <p class="monthly-audit-desc">
            Última verificação executada em <?= date('d/m/Y \à\s H:i', strtotime($monthlyAudit['executed_at'])) ?>.
            <?= $monthlyAudit['students_in_recovery'] > 0 
                ? "Atividades de recuperação já foram formuladas e notificadas para cada aluno com rendimento crítico." 
                : "Nenhum estudante com taxa de erro acumulada superior a 50% nos últimos 30 dias." ?>
          </p>
        </div>
      </div>
      <div class="monthly-audit-actions">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="reRunMonthlyAudit()" id="btnReaudit">
          <i class="fa-solid fa-arrows-rotate"></i> Reauditar Agora
        </button>
      </div>
    </div>

    <?php if (!empty($monthlyAudit['details'])): ?>
      <div class="monthly-audit-list">
        <span class="monthly-audit-list-title"><i class="fa-solid fa-user-xmark text-danger"></i> Alunos em recuperação este mês:</span>
        <div class="monthly-chips">
          <?php foreach ($monthlyAudit['details'] as $item): ?>
            <span class="monthly-chip">
              <strong><?= htmlspecialchars($item['student_name']) ?></strong>
              <span class="monthly-chip-topic"><?= htmlspecialchars($item['priority_topic']) ?> (<?= $item['overall_error'] ?>% erro)</span>
              <button type="button" class="btn-chip-action" onclick="selectStudentForRecovery(<?= (int)$item['student_id'] ?>, '<?= htmlspecialchars(addslashes($item['student_name'])) ?>', <?= (100 - (int)$item['overall_error']) ?>)" title="Gerar reforço adicional">
                <i class="fa-solid fa-arrow-right"></i>
              </button>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
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
    <span>Nenhum alerta de dificuldade pendente. Todos os alunos estão com índice de aproveitamento adequado!</span>
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
  <h2 class="section-heading"><i class="fa-solid fa-chart-pie"></i> Desempenho por Módulo de Jogo</h2>
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
        <span class="game-stat-row-label">Média de Pontos:</span>
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
  <div class="section-header-flex">
    <h2 class="section-heading"><i class="fa-solid fa-ranking-star"></i> Ranking e Acompanhamento de Alunos</h2>
    <span class="section-hint"><i class="fa-solid fa-info-circle"></i> Clique em "Aplicar" para direcionar recuperação a um aluno específico.</span>
  </div>
  <div class="card">
    <div class="card-body-flush overflow-x-auto">
      <table class="student-table" aria-label="Ranking e acompanhamento individual de alunos">
        <thead>
          <tr>
            <th scope="col">Posição</th>
            <th scope="col">Aluno</th>
            <th scope="col">Nível</th>
            <th scope="col">XP</th>
            <th scope="col">Partidas</th>
            <th scope="col">Acertos</th>
            <th scope="col">Aproveitamento</th>
            <th scope="col">Pontuação</th>
            <th scope="col">Última Partida</th>
            <th scope="col" style="text-align: center;">Recuperação</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $i => $s):
            $total = $s['total_correct'] + $s['total_wrong'];
            $acc = $total > 0 ? round($s['total_correct'] / $total * 100) : 0;
            $danger = $total > 0 && ($s['total_wrong'] / $total) > 0.6;
          ?>
          <tr class="<?= $danger ? 'student-danger-row' : '' ?>" id="student-row-<?= (int)$s['id'] ?>">
            <td>
              <span class="rank-badge <?= $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : 'rank-other')) ?>">
                <?= $i + 1 ?>&deg;
              </span>
            </td>
            <td>
              <div class="align-center" style="display:flex;gap:0.65rem;">
                <div class="avatar avatar-sm" style="background:<?= htmlspecialchars($s['avatar_color'] ?? '#4F46E5') ?>;">
                  <?php if (!empty($s['profile_photo'])): ?><img src="<?= htmlspecialchars($s['profile_photo']) ?>" alt="Foto de <?= htmlspecialchars($s['name']) ?>"><?php else: ?>
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
            <td style="text-align: center;">
              <button type="button" 
                      class="btn-recovery-trigger"
                      onclick="selectStudentForRecovery(<?= (int)$s['id'] ?>, '<?= htmlspecialchars(addslashes($s['name'])) ?>', <?= $acc ?>)"
                      title="Aplicar recuperação individual para <?= htmlspecialchars($s['name']) ?>"
                      aria-label="Aplicar recuperação individual para <?= htmlspecialchars($s['name']) ?>">
                <i class="fa-solid fa-bullseye"></i> Aplicar
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($students)): ?>
          <tr>
            <td colspan="10" class="table-empty-td">
              Nenhum aluno cadastrado no sistema ainda.
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Gerador de Questoes de Reforco com IA -->
  <div class="ai-generator-card" id="aiGeneratorCard">
    <div class="ai-generator-header">
      <div>
        <h2 class="ai-generator-title">
          <i class="fa-solid fa-brain text-primary"></i> Gerador de Questões de Reforço e Recuperação
        </h2>
        <p class="ai-generator-desc">O motor pedagógico analisa o histórico de acertos e erros para formular questões personalizadas — você pode aplicar para um <strong>aluno específico</strong> ou para a <strong>turma inteira</strong>.</p>
      </div>
      <span class="ai-mode-pill" id="aiModeIndicator">
        <i class="fa-solid fa-users"></i> Modo Coletivo
      </span>
    </div>

    <!-- Alerta dinâmico de Aluno Selecionado -->
    <div id="targetStudentNotice" class="target-student-notice d-none" role="status" aria-live="polite">
      <div class="target-notice-content">
        <i class="fa-solid fa-bullseye-arrow target-notice-icon"></i>
        <div>
          <strong id="targetStudentNoticeName">Aluno Selecionado</strong>
          <span id="targetStudentNoticeDesc">A recuperação será direcionada exclusivamente para este aluno.</span>
        </div>
      </div>
      <button type="button" class="btn btn-sm btn-ghost-danger" onclick="clearSelectedStudent()" title="Remover seleção individual e voltar para a turma inteira">
        <i class="fa-solid fa-xmark"></i> Cancelar Individual
      </button>
    </div>

    <div class="ai-generator-form-grid">
      <!-- Seletor de Destinatário -->
      <div class="ai-field-group">
        <label for="studentSelect" class="ai-field-label">
          <i class="fa-solid fa-user-graduate"></i> Destinatário:
        </label>
        <select id="studentSelect" class="form-control ai-select-full" onchange="onStudentSelectChange(this)" aria-label="Destinatário da recuperação">
          <option value="all">👥 Toda a Turma (Reforço Geral)</option>
          <optgroup label="Alunos Individuais">
            <?php foreach ($students as $stu):
              $sTotal = $stu['total_correct'] + $stu['total_wrong'];
              $sAcc = $sTotal > 0 ? round($stu['total_correct'] / $sTotal * 100) : 0;
            ?>
              <option value="<?= (int)$stu['id'] ?>" data-name="<?= htmlspecialchars($stu['name']) ?>" data-acc="<?= $sAcc ?>">
                👤 <?= htmlspecialchars($stu['name']) ?> (Aprov: <?= $sAcc ?>%)
              </option>
            <?php endforeach; ?>
          </optgroup>
        </select>
      </div>

      <!-- Seletor de Tópico -->
      <div class="ai-field-group">
        <label for="topicSelect" class="ai-field-label">
          <i class="fa-solid fa-layer-group"></i> Conteúdo / Tópico:
        </label>
        <select id="topicSelect" class="form-control ai-select-full" onchange="onTopicChange(this)" aria-label="Tópico matemático">
          <option value="Fracoes e Razoes" data-game="1">Chef das Frações (Frações e Razões)</option>
          <option value="Geometria Plana" data-game="2">Construtor de Cidades (Geometria Plana)</option>
        </select>
      </div>

      <input type="hidden" id="gameIdInput" value="1">

      <!-- Botão de Ação -->
      <div class="ai-field-action">
        <button onclick="generateAI()" class="btn btn-primary btn-generate-ai" id="btnGenerate">
          <i class="fa-solid fa-wand-magic-sparkles"></i>
          <span id="btnGenerateText">Gerar e Aplicar Reforço</span>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
async function reRunMonthlyAudit(){
  var btn = document.getElementById('btnReaudit');
  btn.disabled = true;
  var origHtml = btn.innerHTML;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Auditando...';
  try {
    var res = await fetch('/vortex/api/monthly_recovery_check.php?force=1');
    var data = await res.json();
    if(data.success){
      showToast('Auditoria mensal reexecutada! ' + data.students_in_recovery + ' aluno(s) em recuperação.', 'success');
      setTimeout(function(){ window.location.reload(); }, 1000);
    } else {
      showToast('Erro ao reauditar: ' + (data.error || 'Falha'), 'error');
    }
  } catch(e){
    showToast('Erro de comunicação ao executar auditoria', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = origHtml;
  }
}

function onTopicChange(sel){
  var opt = sel.options[sel.selectedIndex];
  var gid = opt.getAttribute('data-game') || '1';
  document.getElementById('gameIdInput').value = gid;
}

function onStudentSelectChange(sel){
  var val = sel.value;
  var notice = document.getElementById('targetStudentNotice');
  var indicator = document.getElementById('aiModeIndicator');
  var btnText = document.getElementById('btnGenerateText');

  if(val === 'all' || !val){
    notice.classList.add('d-none');
    indicator.innerHTML = '<i class="fa-solid fa-users"></i> Modo Coletivo';
    indicator.className = 'ai-mode-pill';
    btnText.textContent = 'Gerar Reforço para Turma';
  } else {
    var opt = sel.options[sel.selectedIndex];
    var name = opt.getAttribute('data-name') || opt.textContent;
    var acc = opt.getAttribute('data-acc') || '0';

    document.getElementById('targetStudentNoticeName').textContent = 'Recuperação Individual: ' + name;
    document.getElementById('targetStudentNoticeDesc').textContent = 'Aproveitamento atual: ' + acc + '%. O aluno receberá uma notificação exclusiva no painel dele.';
    notice.classList.remove('d-none');

    indicator.innerHTML = '<i class="fa-solid fa-user-check"></i> Recuperação Individual';
    indicator.className = 'ai-mode-pill individual';
    btnText.textContent = 'Aplicar Recuperação para ' + name.split(' ')[0];
  }
}

function selectStudentForRecovery(id, name, acc){
  var sel = document.getElementById('studentSelect');
  sel.value = id;
  onStudentSelectChange(sel);

  var card = document.getElementById('aiGeneratorCard');
  card.scrollIntoView({ behavior: 'smooth', block: 'center' });
  card.classList.add('highlight-pulse');
  setTimeout(function(){
    card.classList.remove('highlight-pulse');
  }, 1800);

  showToast('Aluno ' + name + ' selecionado para recuperação individual.', 'info');
}

function clearSelectedStudent(){
  var sel = document.getElementById('studentSelect');
  sel.value = 'all';
  onStudentSelectChange(sel);
  showToast('Modo alterado para toda a turma.', 'info');
}

async function generateAI(){
  var topic     = document.getElementById('topicSelect').value;
  var gameId    = parseInt(document.getElementById('gameIdInput').value) || 1;
  var studentId = document.getElementById('studentSelect').value;
  var btn       = document.getElementById('btnGenerate');

  btn.disabled = true;
  var originalHtml = btn.innerHTML;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processando...';

  var isIndiv = (studentId !== 'all' && studentId !== '');
  showToast(isIndiv ? 'Formulando recuperação individual com IA...' : 'Processando questões de reforço para a turma...', 'info');

  try {
    var token = getCsrfToken();
    var res = await fetch('/vortex/api/generate_questions.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token
      },
      body: JSON.stringify({ 
        topic: topic, 
        game_id: gameId, 
        student_id: studentId,
        csrf_token: token 
      })
    });
    var data = await res.json();
    if(data.success){
      var msg = data.is_individual 
        ? 'Sucesso! Geradas ' + data.generated + ' questões de recuperação para ' + (data.student_name || 'o aluno') + '!'
        : 'Sucesso! Geradas ' + data.generated + ' questões de reforço para toda a turma (' + data.notified_students + ' alunos notificados)!';
      showToast(msg, 'success', 6500);

      if(data.is_individual){
        setTimeout(function(){
          clearSelectedStudent();
        }, 3000);
      }
    } else {
      showToast('Aviso: ' + (data.error || 'Falha ao processar recuperação'), 'error');
    }
  } catch(e){
    showToast('Erro de comunicação ao gerar recuperação', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalHtml;
  }
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
