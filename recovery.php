<?php
$pageTitle = 'Minhas Recuperações';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

if (!isLoggedIn() || ($_SESSION['user_role'] ?? '') !== 'student') {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT r.id, r.topic, r.game_id, r.created_at, r.score, r.completed_at,
           t.name AS teacher_name,
           (SELECT COUNT(*) FROM questions q WHERE q.recovery_id = r.id AND q.target_user_id = r.student_id) AS question_count
    FROM recoveries r
    LEFT JOIN users t ON t.id = r.teacher_id
    WHERE r.student_id = ? AND r.status = 'pending'
    ORDER BY r.created_at DESC
");
$stmt->execute([$userId]);
$pendingRecoveries = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT r.id, r.topic, r.created_at, r.score, r.completed_at,
           t.name AS teacher_name,
           (SELECT COUNT(*) FROM questions q WHERE q.recovery_id = r.id AND q.target_user_id = r.student_id) AS question_count
    FROM recoveries r
    LEFT JOIN users t ON t.id = r.teacher_id
    WHERE r.student_id = ? AND r.status = 'completed'
    ORDER BY r.completed_at DESC
");
$stmt->execute([$userId]);
$completedRecoveries = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<section class="container recovery-page">
  <header class="recovery-page-header">
    <span class="section-tag"><i class="fa-solid fa-clipboard-check"></i> Aprendizagem personalizada</span>
    <h1 class="section-title">Minhas Recuperações</h1>
    <p class="section-subtitle">Aqui estão as atividades enviadas para você pelo seu professor.</p>
  </header>

  <section aria-labelledby="pending-recoveries-title">
    <h2 class="section-heading" id="pending-recoveries-title">
      <i class="fa-solid fa-hourglass-half"></i> Pendentes
      <span class="alert-count-badge"><?= count($pendingRecoveries) ?></span>
    </h2>
    <?php if (!$pendingRecoveries): ?>
      <div class="card recovery-empty-state">
        <div class="stat-icon stat-icon-success"><i class="fa-solid fa-circle-check"></i></div>
        <h3>Você não tem nenhuma recuperação para fazer no momento 🎉</h3>
        <p>Quando seu professor enviar uma atividade, ela aparecerá aqui.</p>
      </div>
    <?php else: ?>
      <div class="recovery-cards">
        <?php foreach ($pendingRecoveries as $recovery): ?>
          <article class="card recovery-card">
            <div class="recovery-card-content">
              <span class="section-tag"><?= htmlspecialchars($recovery['topic']) ?></span>
              <h3><?= htmlspecialchars($recovery['topic']) ?></h3>
              <p>Professor: <?= htmlspecialchars($recovery['teacher_name'] ?? 'Equipe pedagógica') ?></p>
              <p>Enviada em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($recovery['created_at']))) ?> · <?= (int)$recovery['question_count'] ?> questões</p>
            </div>
            <?php if ((int)$recovery['question_count'] > 0): ?>
              <a class="btn btn-primary" href="<?= BASE_URL ?>/games/reinforcement.php?recovery_id=<?= (int)$recovery['id'] ?>">
                <i class="fa-solid fa-play"></i> Fazer recuperação
              </a>
            <?php else: ?>
              <span class="text-muted">Atividade indisponível. Peça ao professor para reenviá-la.</span>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="mt-4" aria-labelledby="completed-recoveries-title">
    <h2 class="section-heading" id="completed-recoveries-title"><i class="fa-solid fa-circle-check"></i> Concluídas</h2>
    <?php if (!$completedRecoveries): ?>
      <p class="text-muted">Suas recuperações concluídas aparecerão aqui.</p>
    <?php else: ?>
      <div class="recovery-cards">
        <?php foreach ($completedRecoveries as $recovery): ?>
          <article class="card recovery-card recovery-card-completed">
            <div class="recovery-card-content">
              <span class="section-tag"><?= htmlspecialchars($recovery['topic']) ?></span>
              <h3><?= htmlspecialchars($recovery['topic']) ?></h3>
              <p>Professor: <?= htmlspecialchars($recovery['teacher_name'] ?? 'Equipe pedagógica') ?></p>
              <p>Concluída em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($recovery['completed_at']))) ?> · <?= (int)$recovery['question_count'] ?> questões</p>
            </div>
            <strong class="recovery-score">Nota: <?= (int)$recovery['score'] ?>/100</strong>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
