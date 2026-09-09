<?php
$pageTitle = 'Missão de Reforço com IA';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$selectedTopic = isset($_GET['topic']) ? trim($_GET['topic']) : '';

// Buscar tópicos disponíveis de reforço com IA
$stmtTopics = $pdo->query('SELECT topic, COUNT(*) as qty FROM questions WHERE is_ai_generated = 1 GROUP BY topic ORDER BY topic ASC');
$availableTopics = $stmtTopics->fetchAll();

// Se nenhum tópico foi especificado e há tópicos disponíveis, pode filtrar ou trazer todos
if ($selectedTopic !== '') {
    $stmtQ = $pdo->prepare('SELECT * FROM questions WHERE is_ai_generated = 1 AND topic = ? ORDER BY id DESC LIMIT 10');
    $stmtQ->execute([$selectedTopic]);
} else {
    $stmtQ = $pdo->query('SELECT * FROM questions WHERE is_ai_generated = 1 ORDER BY id DESC LIMIT 10');
}
$questions = $stmtQ->fetchAll();

// Se não houver questões de reforço ainda, buscar questões gerais para não deixar o aluno sem treino
$isFallbackRegular = false;
if (empty($questions)) {
    $isFallbackRegular = true;
    if ($selectedTopic !== '') {
        $stmtQ = $pdo->prepare('SELECT * FROM questions WHERE topic = ? ORDER BY RAND() LIMIT 8');
        $stmtQ->execute([$selectedTopic]);
    } else {
        $stmtQ = $pdo->query('SELECT * FROM questions ORDER BY RAND() LIMIT 8');
    }
    $questions = $stmtQ->fetchAll();
}

// Determinar game_id para salvar sessão (padrão 1 se misto)
$activeGameId = 1;
if (!empty($questions[0]['game_id'])) {
    $activeGameId = (int)$questions[0]['game_id'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="game-wrapper reinforcement-wrapper">
  <!-- Game Header -->
  <div class="game-header reinforcement-header">
    <div>
      <div class="reinforce-badge">
        <i class="fa-solid fa-brain text-cyan"></i>
        <span>IA Pedagógica</span>
      </div>
      <h1 class="game-header-title">
        <i class="fa-solid fa-crosshairs text-primary"></i> Missão de Reforço
      </h1>
      <p class="game-header-subtitle">Pratique questões formuladas com inteligência artificial para superar suas dúvidas e acelerar seu aprendizado.</p>
    </div>
    <div class="game-score-display">
      <div class="game-score-item">
        <div class="game-score-value" id="score">0</div>
        <div class="game-score-label">Pontos</div>
      </div>
      <div class="game-score-item">
        <div class="game-score-value" id="qnum">1/<?= count($questions) ?></div>
        <div class="game-score-label">Questão</div>
      </div>
      <div class="game-score-item">
        <div class="game-score-value text-success" id="hitsCount">0</div>
        <div class="game-score-label">Acertos</div>
      </div>
    </div>
  </div>

  <!-- Barra de Tópicos / Filtro de Reforço -->
  <div class="reinforce-filter-bar">
    <span class="filter-label"><i class="fa-solid fa-layer-group"></i> Tópico:</span>
    <a href="/vortex/games/reinforcement.php" class="topic-pill <?= empty($selectedTopic) ? 'active' : '' ?>">
      Todos (<?= array_sum(array_column($availableTopics, 'qty')) ?>)
    </a>
    <?php foreach ($availableTopics as $t): ?>
      <a href="/vortex/games/reinforcement.php?topic=<?= urlencode($t['topic']) ?>" 
         class="topic-pill <?= ($selectedTopic === $t['topic']) ? 'active' : '' ?>">
        <?= htmlspecialchars($t['topic']) ?> (<?= $t['qty'] ?>)
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Progress Bar -->
  <div class="game-progress-wrap">
    <div class="xp-label">
      <span class="xp-current" id="progressTitle">Progresso do Treino</span>
      <span class="xp-next" id="progressLabel">0%</span>
    </div>
    <div class="xp-bar-wrap">
      <div class="xp-bar-fill" id="progressBar" style="width:0%;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
  </div>

  <?php if (empty($questions)): ?>
    <div class="card empty-state-card mt-4">
      <div class="empty-state-icon">
        <i class="fa-solid fa-lightbulb text-warning"></i>
      </div>
      <h3 class="empty-state-title">Nenhuma questão de reforço encontrada</h3>
      <p class="empty-state-text">Ainda não há questões de reforço geradas para este tópico. Você pode pedir ao seu professor para gerar questões ou praticar nos jogos regulares.</p>
      <div class="flex-row justify-center gap-md mt-3">
        <a href="/vortex/games/index.php" class="btn btn-primary">
          <i class="fa-solid fa-gamepad"></i> Explorar Jogos
        </a>
        <a href="/vortex/dashboard.php" class="btn btn-secondary">
          <i class="fa-solid fa-house"></i> Voltar ao Início
        </a>
      </div>
    </div>
  <?php else: ?>

  <!-- Question Area -->
  <div id="questionArea">
    <div class="question-card reinforcement-card" id="questionCard">
      <div class="reinforcement-card-top">
        <div class="question-number" id="qLabel">Questão 1 de <?= count($questions) ?></div>
        <div class="question-topic-badge" id="qTopic">Reforço Personalizado</div>
      </div>

      <div class="question-text" id="questionText">Carregando pergunta...</div>
      
      <button class="tts-btn" onclick="speakQuestion()" aria-label="Ouvir questão em voz alta">
        <i class="fa-solid fa-volume-high"></i> Ouvir questão
      </button>

      <div class="options-grid" id="optionsGrid"></div>

      <!-- Feedback Pedagógico Expandido -->
      <div class="feedback-box reinforcement-feedback" id="feedbackBox">
        <div class="feedback-header">
          <div class="feedback-title" id="feedbackTitle"></div>
          <span class="feedback-xp-pill" id="feedbackXp"></span>
        </div>
        <div class="feedback-pedagogical-card" id="feedbackPedagogical">
          <div class="pedagogical-header">
            <i class="fa-solid fa-graduation-cap"></i>
            <span>Explicação Estratégica</span>
          </div>
          <div class="feedback-explanation" id="feedbackExplanation"></div>
        </div>
      </div>

      <div class="game-actions-bar">
        <button class="btn btn-primary d-none" onclick="nextQuestion()" id="nextBtn">
          Próxima Questão <i class="fa-solid fa-arrow-right"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Victory & Results Area -->
  <div id="resultArea" class="d-none" aria-live="polite">
    <div class="question-card game-result reinforcement-result">
      <div class="result-badge-wrap" id="resultBadge">
        <i class="fa-solid fa-award" id="resultIcon"></i>
      </div>
      <h2 class="result-title" id="resultTitle">Treino de Reforço Concluído!</h2>
      <p class="result-score" id="resultScore">Você dedicou tempo para revisar e fixar conceitos essenciais.</p>
      
      <div class="result-stars" aria-label="Estrelas conquistadas">
        <i class="fa-solid fa-star star-icon" id="s1"></i>
        <i class="fa-solid fa-star star-icon" id="s2"></i>
        <i class="fa-solid fa-star star-icon" id="s3"></i>
      </div>

      <div class="result-stats">
        <div class="result-stat-card">
          <div class="result-stat-value" id="rScore">0</div>
          <div class="result-stat-label">Pontos</div>
        </div>
        <div class="result-stat-card">
          <div class="result-stat-value text-success" id="rCorrect">0</div>
          <div class="result-stat-label">Acertos</div>
        </div>
        <div class="result-stat-card">
          <div class="result-stat-value text-danger" id="rWrong">0</div>
          <div class="result-stat-label">Erros</div>
        </div>
      </div>

      <div class="result-actions">
        <button class="btn btn-primary" onclick="location.reload()">
          <i class="fa-solid fa-rotate-right"></i> Refazer Treino
        </button>
        <a href="/vortex/dashboard.php" class="btn btn-secondary">
          <i class="fa-solid fa-chart-line"></i> Ir ao Dashboard
        </a>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
var questions = <?= json_encode(array_values(array_map(function($q){
    return [
        'id'            => (int)$q['id'],
        'game_id'       => (int)($q['game_id'] ?? 1),
        'topic'         => $q['topic'] ?? 'Reforço',
        'question_text' => $q['question_text'],
        'option_a'      => $q['option_a'],
        'option_b'      => $q['option_b'],
        'option_c'      => $q['option_c'],
        'option_d'      => $q['option_d'],
        'difficulty'    => $q['difficulty'] ?? 'easy',
    ];
}, $questions))) ?>;

var activeGameId = <?= (int)$activeGameId ?>;
var current      = 0;
var score        = 0;
var correct      = 0;
var wrong        = 0;
var answered     = false;
var startTime    = Date.now();

function loadQuestion(){
  if(current >= questions.length){ showResult(); return; }
  var q = questions[current];
  answered = false;

  document.getElementById('questionText').textContent = q.question_text;
  document.getElementById('qLabel').textContent = 'Questão ' + (current + 1) + ' de ' + questions.length;
  document.getElementById('qTopic').textContent = q.topic;
  document.getElementById('qnum').textContent = (current + 1) + '/' + questions.length;

  var pct = Math.round((current / questions.length) * 100);
  document.getElementById('progressBar').style.width = pct + '%';
  document.getElementById('progressLabel').textContent = pct + '%';

  var feedbackBox = document.getElementById('feedbackBox');
  feedbackBox.className = 'feedback-box reinforcement-feedback';
  feedbackBox.style.display = 'none';
  document.getElementById('nextBtn').classList.add('d-none');

  var grid = document.getElementById('optionsGrid');
  grid.innerHTML = '';
  var opts = [
    {k: 'A', text: q.option_a},
    {k: 'B', text: q.option_b},
    {k: 'C', text: q.option_c},
    {k: 'D', text: q.option_d}
  ];

  opts.forEach(function(o){
    var btn = document.createElement('button');
    btn.className = 'option-btn';
    btn.setAttribute('data-letter', o.k);
    btn.innerHTML = '<span class="option-letter">' + o.k + '</span><span class="option-text">' + escapeHtml(o.text) + '</span>';
    btn.onclick = function(){ checkAns(o.k, btn); };
    grid.appendChild(btn);
  });
}

async function checkAns(chosenLetter, clickedBtn){
  if(answered) return;
  answered = true;
  var q = questions[current];

  document.querySelectorAll('.option-btn').forEach(function(b){
    b.disabled = true;
  });

  try {
    var token = getCsrfToken();
    var res = await fetch('/vortex/api/check_answer.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token
      },
      body: JSON.stringify({
        question_id: q.id,
        chosen: chosenLetter,
        game_id: q.game_id || activeGameId,
        hint_used: false,
        csrf_token: token
      })
    });
    var data = await res.json();

    if(data.success){
      var feedbackBox = document.getElementById('feedbackBox');
      var feedbackTitle = document.getElementById('feedbackTitle');
      var feedbackExp = document.getElementById('feedbackExplanation');
      var feedbackXp = document.getElementById('feedbackXp');

      if(data.correct){
        clickedBtn.classList.add('correct');
        score += data.points || 50;
        correct++;
        document.getElementById('score').textContent = score;
        document.getElementById('hitsCount').textContent = correct;

        feedbackBox.classList.add('correct');
        feedbackTitle.innerHTML = '<i class="fa-solid fa-circle-check text-success"></i> Muito bem! Resposta exata.';
        feedbackXp.textContent = '+' + (data.points || 50) + ' XP';
        feedbackXp.className = 'feedback-xp-pill xp-gain';
      } else {
        clickedBtn.classList.add('wrong');
        wrong++;

        document.querySelectorAll('.option-btn').forEach(function(b){
          if(b.getAttribute('data-letter') === data.correct_letter){
            b.classList.add('correct');
          }
        });

        feedbackBox.classList.add('wrong');
        feedbackTitle.innerHTML = '<i class="fa-solid fa-circle-xmark text-danger"></i> Quase lá! Analise a resolução abaixo:';
        feedbackXp.textContent = '+0 XP';
        feedbackXp.className = 'feedback-xp-pill xp-zero';
      }

      feedbackExp.textContent = data.explanation || 'Compreender o passo a passo ajuda a não errar nas próximas!';
      feedbackBox.style.display = 'block';
      document.getElementById('nextBtn').classList.remove('d-none');
    }
  } catch(e){
    showToast('Erro ao validar resposta.', 'error');
    document.getElementById('nextBtn').classList.remove('d-none');
  }
}

function nextQuestion(){
  current++;
  loadQuestion();
}

async function showResult(){
  document.getElementById('questionArea').classList.add('d-none');
  var resArea = document.getElementById('resultArea');
  resArea.classList.remove('d-none');

  document.getElementById('rScore').textContent = score;
  document.getElementById('rCorrect').textContent = correct;
  document.getElementById('rWrong').textContent = wrong;

  var pct = questions.length > 0 ? Math.round((correct / questions.length) * 100) : 0;
  var stars = pct >= 80 ? 3 : (pct >= 50 ? 2 : 1);

  for(var i = 1; i <= 3; i++){
    var s = document.getElementById('s' + i);
    if(i <= stars){
      s.classList.add('active');
    }
  }

  // Salvar no servidor
  var elapsed = Math.round((Date.now() - startTime) / 1000);
  try {
    var token = getCsrfToken();
    var res = await fetch('/vortex/api/save_score.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token
      },
      body: JSON.stringify({
        game_id: activeGameId,
        score: score,
        correct: correct,
        wrong: wrong,
        time_spent: elapsed,
        difficulty: 'easy',
        csrf_token: token
      })
    });
    var saved = await res.json();
    if(saved.success && saved.xp_gained){
      showToast('Excelente! Você ganhou ' + saved.xp_gained + ' XP!', 'success');
    }
  } catch(e){}
}

function speakQuestion(){
  if(!('speechSynthesis' in window)){
    showToast('Seu navegador não suporta leitura em voz.', 'info');
    return;
  }
  var q = questions[current];
  if(!q) return;
  var text = q.question_text;
  var u = new SpeechSynthesisUtterance(text);
  u.lang = 'pt-BR';
  window.speechSynthesis.cancel();
  window.speechSynthesis.speak(u);
}

function escapeHtml(str){
  if(!str) return '';
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('DOMContentLoaded', function(){
  if(questions && questions.length > 0){
    loadQuestion();
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
