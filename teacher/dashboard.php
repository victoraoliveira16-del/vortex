<?php
$pageTitle = 'Painel do Professor';
require_once __DIR__ . '/../includes/auth.php';
requireTeacher();

$classActionMessage = '';
$classActionType = 'danger';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['create_class', 'move_student'], true)) {
  if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $classActionMessage = 'Token de segurança inválido. Recarregue a página e tente novamente.';
  } elseif ($_POST['action'] === 'create_class') {
    $className = preg_replace('/\s+/', ' ', trim((string)($_POST['class_name'] ?? '')));
    if ($className === '' || mb_strlen($className) > 20) {
      $classActionMessage = 'Informe um nome de turma com até 20 caracteres.';
    } else {
      try {
        $pdo->prepare('INSERT INTO classes (name) VALUES (?)')->execute([$className]);
        $_SESSION['teacher_class_action_message'] = 'Turma criada com sucesso.';
        $_SESSION['teacher_class_action_type'] = 'success';
        header('Location: ' . BASE_URL . '/teacher/dashboard.php');
        exit;
      } catch (PDOException $e) {
        if ($e->getCode() !== '23000') {
          throw $e;
        }
        $classActionMessage = 'Já existe uma turma com esse nome.';
      }
    }
  } else {
    $studentId = filter_var($_POST['student_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $rawClassId = $_POST['class_id'] ?? '';
    $classId = $rawClassId === '' ? null : filter_var($rawClassId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($studentId === false || ($rawClassId !== '' && $classId === false)) {
      $classActionMessage = 'Aluno ou turma inválidos.';
    } else {
      $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND role = "student"');
      $stmt->execute([$studentId]);
      if (!$stmt->fetch()) {
        $classActionMessage = 'Aluno não encontrado.';
      } else {
        $pdo->beginTransaction();
        try {
          if ($classId !== null) {
            $stmt = $pdo->prepare('SELECT id, name, max_students FROM classes WHERE id = ? FOR UPDATE');
            $stmt->execute([$classId]);
            $class = $stmt->fetch();
            if (!$class) {
              $pdo->rollBack();
              $classActionMessage = 'Turma não encontrada.';
            } else {
              $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE class_id = ? AND role = "student" AND id <> ?');
              $stmt->execute([$classId, $studentId]);
              $studentCount = (int)$stmt->fetchColumn();
              if ($studentCount >= (int)$class['max_students']) {
                $pdo->rollBack();
                $classActionMessage = "A turma {$class['name']} está cheia ({$studentCount}/{$class['max_students']}). Escolha outra turma.";
              }
            }
          }

          if (!$classActionMessage) {
            $stmt = $pdo->prepare('UPDATE users SET class_id = ? WHERE id = ? AND role = "student"');
            $stmt->execute([$classId, $studentId]);
            $pdo->commit();
            $_SESSION['teacher_class_action_message'] = 'Turma do aluno atualizada.';
            $_SESSION['teacher_class_action_type'] = 'success';
            header('Location: ' . BASE_URL . '/teacher/dashboard.php');
            exit;
          }
        } catch (Throwable $e) {
          if ($pdo->inTransaction()) {
            $pdo->rollBack();
          }
          throw $e;
        }
      }
    }
  }
}

$classActionMessage = $_SESSION['teacher_class_action_message'] ?? $classActionMessage;
$classActionType = $_SESSION['teacher_class_action_type'] ?? $classActionType;
unset($_SESSION['teacher_class_action_message'], $_SESSION['teacher_class_action_type']);

$classStmt = $pdo->query('
  SELECT c.id, c.name, c.max_students, COUNT(u.id) AS student_count
  FROM classes c
  LEFT JOIN users u ON u.class_id = c.id AND u.role = "student"
  GROUP BY c.id
  ORDER BY c.name
');
$classes = $classStmt->fetchAll();
$selectedClassId = filter_var($_GET['class_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($selectedClassId === false) {
  $selectedClassId = null;
}

$user = getCurrentUser();

// Alertas de desempenho nao lidos
$stmt = $pdo->prepare('SELECT * FROM notifications WHERE type="alert" AND is_read=0 ORDER BY created_at DESC LIMIT 20');
$stmt->execute();
$alerts = $stmt->fetchAll();

// Marcar como lido apenas para o professor atual
$pdo->prepare('UPDATE notifications SET is_read=1 WHERE type="alert" AND user_id=?')->execute([$user['id']]);

// Todos os alunos com estatisticas
$stmt = $pdo->prepare('
  SELECT u.id, u.name, u.email, u.level, u.xp, u.avatar_color, u.profile_photo,
    u.class_id, c.name AS class_name,
    COUNT(gs.id) AS total_games,
    COALESCE(SUM(gs.correct_answers), 0) AS total_correct,
    COALESCE(SUM(gs.wrong_answers), 0) AS total_wrong,
    COALESCE(SUM(gs.score), 0) AS total_score,
    MAX(gs.played_at) AS last_played
  FROM users u
  LEFT JOIN game_sessions gs ON gs.user_id = u.id
  LEFT JOIN classes c ON c.id = u.class_id
  WHERE u.role = "student"
    AND (? IS NULL OR u.class_id = ?)
  GROUP BY u.id, c.name
  ORDER BY total_score DESC
');
$stmt->execute([$selectedClassId, $selectedClassId]);
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
      <a href="<?= BASE_URL ?>/teacher/reports.php" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-file-lines"></i> Relatórios Detalhados
      </a>
    </div>
  </div>

  <?php if ($classActionMessage): ?>
    <div class="alert alert-<?= htmlspecialchars($classActionType) ?>" role="<?= $classActionType === 'success' ? 'status' : 'alert' ?>">
      <i class="fa-solid <?= $classActionType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
      <span><?= htmlspecialchars($classActionMessage) ?></span>
    </div>
  <?php endif; ?>

  <section class="card teacher-classes-panel" aria-labelledby="classes-heading">
    <div class="card-header">
      <h2 class="section-heading" id="classes-heading"><i class="fa-solid fa-users"></i> Turmas</h2>
    </div>
    <div class="card-body">
      <form method="POST" class="teacher-class-create-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" value="create_class">
        <label class="form-label" for="class_name">Criar nova turma</label>
        <input class="form-control" id="class_name" name="class_name" maxlength="20" placeholder="Ex.: 8 A" required>
        <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-plus"></i> Criar turma</button>
      </form>
      <form method="GET" class="teacher-class-filter-form">
        <label class="form-label" for="class-filter">Filtrar alunos por turma</label>
        <select class="form-control" id="class-filter" name="class_id" onchange="this.form.submit()">
          <option value="">Todas as turmas</option>
          <?php foreach ($classes as $class): ?>
            <option value="<?= (int)$class['id'] ?>" <?= (int)$selectedClassId === (int)$class['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($class['name']) ?> (<?= (int)$class['student_count'] ?>/<?= (int)$class['max_students'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
  </section>

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
  <div class="alert-section" id="alertSection" aria-live="polite">
    <h2 class="section-heading text-danger">
      <i class="fa-solid fa-triangle-exclamation text-danger"></i>
      Alertas de Dificuldade Ativos
      <span class="alert-count-badge" id="alertCountBadge"><?= count($alerts) ?></span>
    </h2>
    <div id="alertList">
    <?php foreach ($alerts as $idx => $a): ?>
    <div class="alert-item alert-animated" data-idx="<?= $idx ?>" style="--delay:<?= $idx * 80 ?>ms">
      <div class="alert-item-icon"><i class="fa-solid fa-bell"></i></div>
      <div style="flex:1;min-width:0;">
        <div class="alert-item-title"><?= htmlspecialchars($a['title']) ?></div>
        <div class="alert-item-msg"><?= htmlspecialchars($a['message']) ?></div>
        <div class="alert-item-time"><?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></div>
      </div>
      <button class="alert-dismiss-btn" onclick="dismissAlert(this)" title="Dispensar alerta" aria-label="Dispensar alerta">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <?php endforeach; ?>
    </div>
    <div id="alertsAllDone" style="display:none;">
      <div class="alert alert-success mb-2" role="status">
        <i class="fa-solid fa-circle-check"></i>
        <span>Todos os alertas foram revisados!</span>
      </div>
    </div>
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
    <h2 class="section-heading"><i class="fa-solid fa-medal"></i> Ranking e Acompanhamento de Alunos</h2>
    <span class="section-hint"><i class="fa-solid fa-circle-info"></i> Clique em "Aplicar" para direcionar recuperação a um aluno específico.</span>
  </div>
  <div class="card">
    <div class="card-body-flush overflow-x-auto">
      <table class="student-table" aria-label="Ranking e acompanhamento individual de alunos">
        <thead>
          <tr>
            <th scope="col">Posição</th>
            <th scope="col">Aluno</th>
            <th scope="col">Turma</th>
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
                  <small class="student-email"><?= htmlspecialchars($s['email']) ?></small>
                  <?php if ($danger): ?>
                    <div class="student-danger-badge">
                      <i class="fa-solid fa-triangle-exclamation"></i> Taxa de erro > 60%
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td>
              <div><?= htmlspecialchars($s['class_name'] ?? 'Sem turma') ?></div>
              <form method="POST" class="student-class-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="move_student">
                <input type="hidden" name="student_id" value="<?= (int)$s['id'] ?>">
                <select class="form-control" name="class_id" aria-label="Turma de <?= htmlspecialchars($s['name']) ?>">
                  <option value="">Sem turma</option>
                  <?php foreach ($classes as $class): ?>
                    <option value="<?= (int)$class['id'] ?>" <?= (int)$s['class_id'] === (int)$class['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($class['name']) ?> (<?= (int)$class['student_count'] ?>/<?= (int)$class['max_students'] ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-secondary btn-sm" type="submit">Salvar</button>
              </form>
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
                      onclick="selectStudentForRecovery(<?= (int)$s['id'] ?>, <?= htmlspecialchars(json_encode($s['name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>, <?= $acc ?>)"
                      title="Aplicar recuperação individual para <?= htmlspecialchars($s['name']) ?>"
                      aria-label="Aplicar recuperação individual para <?= htmlspecialchars($s['name']) ?>">
                <i class="fa-solid fa-bullseye"></i> Aplicar
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($students)): ?>
          <tr>
            <td colspan="11" class="table-empty-td">
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
        <p class="ai-generator-desc">O motor pedagógico analisa o histórico de acertos e erros para formular questões personalizadas. Escolha um aluno ou selecione uma turma inteira; cada aluno receberá uma recuperação individual.</p>
      </div>
      <span class="ai-mode-pill" id="aiModeIndicator">
        <i class="fa-solid fa-user-graduate"></i> Escolha o destinatário
      </span>
    </div>

    <!-- Alerta dinâmico de Aluno Selecionado -->
    <div id="targetStudentNotice" class="target-student-notice d-none" role="status" aria-live="polite">
      <div class="target-notice-content">
        <i class="fa-solid fa-bullseye target-notice-icon"></i>
        <div>
          <strong id="targetStudentNoticeName">Aluno Selecionado</strong>
          <span id="targetStudentNoticeDesc">A recuperação será direcionada exclusivamente para este aluno.</span>
        </div>
      </div>
      <button type="button" class="btn btn-sm btn-ghost-danger" onclick="clearSelectedStudent()" title="Limpar destinatário selecionado">
        <i class="fa-solid fa-xmark"></i> Limpar seleção
      </button>
    </div>

    <div class="ai-generator-form-grid">
      <!-- Seletor de Destinatário -->
      <div class="ai-field-group">
        <label for="studentSelect" class="ai-field-label">
          <i class="fa-solid fa-user-graduate"></i> Destinatário:
        </label>
        <select id="studentSelect" class="form-control ai-select-full" onchange="onStudentSelectChange(this)" aria-label="Destinatário da recuperação">
          <option value="">Selecione um aluno ou uma turma inteira</option>
          <optgroup label="Alunos Individuais">
            <?php foreach ($students as $stu):
              $sTotal = $stu['total_correct'] + $stu['total_wrong'];
              $sAcc = $sTotal > 0 ? round($stu['total_correct'] / $sTotal * 100) : 0;
            ?>
              <option value="student:<?= (int)$stu['id'] ?>" data-name="<?= htmlspecialchars($stu['name']) ?>" data-email="<?= htmlspecialchars($stu['email']) ?>" data-acc="<?= $sAcc ?>">
                <?= htmlspecialchars($stu['name']) ?> — <?= htmlspecialchars($stu['email']) ?>
              </option>
            <?php endforeach; ?>
          </optgroup>
          <optgroup label="Turma inteira: escolha a turma">
            <?php foreach ($classes as $class): ?>
              <option value="class:<?= (int)$class['id'] ?>" data-name="<?= htmlspecialchars($class['name']) ?>" <?= (int)$class['student_count'] === 0 ? 'disabled' : '' ?>>
                Turma <?= htmlspecialchars($class['name']) ?> (<?= (int)$class['student_count'] ?>/<?= (int)$class['max_students'] ?> alunos)
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
    var token = getCsrfToken();
    var res = await fetch('<?= BASE_URL ?>/api/monthly_recovery_check.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token
      },
      body: JSON.stringify({ force: '1', csrf_token: token })
    });
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

  if(!val){
    notice.classList.add('d-none');
    indicator.innerHTML = '<i class="fa-solid fa-users"></i> Selecione o destinatário';
    indicator.className = 'ai-mode-pill';
    btnText.textContent = 'Gerar Recuperação';
  } else {
    var opt = sel.options[sel.selectedIndex];
    var isClass = val.indexOf('class:') === 0;
    var name = opt.getAttribute('data-name') || opt.textContent;
    var acc = opt.getAttribute('data-acc') || '0';

    document.getElementById('targetStudentNoticeName').textContent = isClass ? 'Recuperação da turma ' + name : 'Recuperação individual: ' + name;
    document.getElementById('targetStudentNoticeDesc').textContent = isClass
      ? 'Será criada uma recuperação separada e privada para cada aluno desta turma.'
      : 'E-mail: ' + (opt.getAttribute('data-email') || '') + '. Aproveitamento atual: ' + acc + '%.';
    notice.classList.remove('d-none');

    indicator.innerHTML = isClass ? '<i class="fa-solid fa-users"></i> Recuperação por turma' : '<i class="fa-solid fa-user-check"></i> Recuperação individual';
    indicator.className = isClass ? 'ai-mode-pill' : 'ai-mode-pill individual';
    btnText.textContent = isClass ? 'Aplicar para turma ' + name : 'Aplicar para ' + name.split(' ')[0];
  }
}

function selectStudentForRecovery(id, name, acc){
  var sel = document.getElementById('studentSelect');
  sel.value = 'student:' + id;
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
  sel.value = '';
  onStudentSelectChange(sel);
  showToast('Selecione um aluno ou uma turma para atribuir a recuperação.', 'info');
}

async function generateAI(){
  var topic     = document.getElementById('topicSelect').value;
  var gameId    = parseInt(document.getElementById('gameIdInput').value) || 1;
  var recipient = document.getElementById('studentSelect').value;
  var btn       = document.getElementById('btnGenerate');

  btn.disabled = true;
  var originalHtml = btn.innerHTML;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processando...';

  if(!recipient){
    showToast('Selecione um aluno ou uma turma.', 'warning');
    btn.disabled = false;
    btn.innerHTML = originalHtml;
    return;
  }
  var recipientParts = recipient.split(':');
  var isIndiv = recipientParts[0] === 'student';
  var classId = recipientParts[0] === 'class' ? parseInt(recipientParts[1], 10) : null;
  var studentId = isIndiv ? parseInt(recipientParts[1], 10) : null;
  showToast(isIndiv ? 'Formulando recuperação individual com IA...' : 'Formulando uma recuperação por aluno da turma...', 'info');

  try {
    var token = getCsrfToken();
    var res = await fetch('<?= BASE_URL ?>/api/generate_questions.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token
      },
      body: JSON.stringify({
        topic: topic, 
        game_id: gameId, 
        student_id: studentId,
        class_id: classId,
        csrf_token: token 
      })
    });
    var data = await res.json();
    if(data.success){
      var msg = data.is_individual
        ? 'Recuperação criada para ' + (data.student_name || 'o aluno') + ' com ' + data.generated + ' questões.'
        : 'Recuperações criadas para ' + data.notified_students + ' aluno(s), cada uma com ' + data.questions_per_student + ' questões.';
      showToast(msg, 'success', 6500);

      setTimeout(function(){ window.location.reload(); }, 2500);
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
function dismissAlert(btn) {
  var item  = btn.closest('.alert-item');
  var list  = document.getElementById('alertList');
  if (!item || !list) return;

  // Trigger slide-out animation
  item.classList.add('dismissing');

  item.addEventListener('animationend', function () {
    item.remove();

    // Update the count badge
    var remaining = list.querySelectorAll('.alert-item').length;
    var badge = document.getElementById('alertCountBadge');
    if (badge) {
      badge.textContent = remaining;
      badge.style.transform = 'scale(1.4)';
      setTimeout(function () { badge.style.transform = ''; }, 250);
    }

    // If no alerts left, show "all done" message and hide section heading
    if (remaining === 0) {
      var done = document.getElementById('alertsAllDone');
      if (done) {
        done.style.display = 'block';
        done.style.opacity = '0';
        done.style.transition = 'opacity 0.4s ease';
        requestAnimationFrame(function () { done.style.opacity = '1'; });
      }
      var section = document.getElementById('alertSection');
      if (section) {
        var heading = section.querySelector('.section-heading');
        if (heading) {
          heading.style.transition = 'opacity 0.3s ease';
          heading.style.opacity = '0';
          setTimeout(function () { heading.style.display = 'none'; }, 300);
        }
      }
    }
  }, { once: true });
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
