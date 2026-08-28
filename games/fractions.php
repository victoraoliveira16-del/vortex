<?php
$pageTitle = 'Chef das Fracoes';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$stmt = $pdo->prepare('SELECT * FROM games WHERE slug=?');
$stmt->execute(['fractions']);
$game = $stmt->fetch();

$stmt = $pdo->prepare('SELECT * FROM questions WHERE game_id=? ORDER BY RAND() LIMIT 8');
$stmt->execute([$game['id']]);
$questions = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="game-wrapper">
  <!-- Game Header -->
  <div class="game-header">
    <div>
      <h1 class="game-header-title">
        <i class="fa-solid fa-utensils text-danger"></i> Chef das Fracoes
      </h1>
      <p class="game-header-subtitle">Monte as receitas certas dominando fracoes e proporcoes!</p>
    </div>
    <div class="game-score-display">
      <div class="game-score-item">
        <div class="game-score-value" id="score">0</div>
        <div class="game-score-label">Pontos</div>
      </div>
      <div class="game-score-item">
        <div class="game-score-value" id="qnum">1/<?= count($questions) ?></div>
        <div class="game-score-label">Questao</div>
      </div>
      <div class="game-timer" id="timerBox" aria-live="polite">
        <i class="fa-solid fa-stopwatch"></i> <span id="timer">30</span>s
      </div>
    </div>
  </div>

  <!-- Progress Bar -->
  <div class="game-progress-wrap">
    <div class="xp-label">
      <span class="xp-current" id="progressTitle">Progresso da Partida</span>
      <span class="xp-next" id="progressLabel">0%</span>
    </div>
    <div class="xp-bar-wrap">
      <div class="xp-bar-fill" id="progressBar" style="width:0%;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
  </div>

  <!-- Culinary Scenario Card -->
  <div class="kitchen-station" id="scenarioBox" aria-live="polite">
    <div class="kitchen-icon-wrap" id="scenarioIconWrap">
      <i class="fa-solid fa-pizza-slice" id="scenarioIcon"></i>
    </div>
    <div>
      <div class="kitchen-tag">Desafio da Cozinha</div>
      <div class="kitchen-text" id="scenarioText">A receita esta pronta para ser preparada, Chef!</div>
    </div>
  </div>

  <!-- Question Area -->
  <div id="questionArea">
    <div class="question-card" id="questionCard">
      <div class="question-number" id="qLabel">Questao 1 de <?= count($questions) ?></div>
      <div class="question-text" id="questionText">Carregando pergunta...</div>
      
      <button class="tts-btn" onclick="speakQuestion()" aria-label="Ouvir questao em voz alta">
        <i class="fa-solid fa-volume-high"></i> Ouvir questao
      </button>

      <div class="options-grid" id="optionsGrid"></div>

      <div class="hint-box" id="hintBox">
        <div class="hint-title"><i class="fa-solid fa-lightbulb"></i> Dica do Chef</div>
        <div id="hintText"></div>
      </div>

      <div class="feedback-box" id="feedbackBox">
        <div class="feedback-title" id="feedbackTitle"></div>
        <div class="feedback-explanation" id="feedbackExplanation"></div>
      </div>

      <div class="game-actions-bar">
        <button class="btn btn-secondary btn-sm" onclick="showHint()" id="hintBtn">
          <i class="fa-solid fa-lightbulb"></i> Dica (-5 pts)
        </button>
        <button class="btn btn-primary d-none" onclick="nextQuestion()" id="nextBtn">
          Proxima Questao <i class="fa-solid fa-arrow-right"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Victory & Results Area -->
  <div id="resultArea" class="d-none" aria-live="polite">
    <div class="question-card game-result">
      <div class="result-badge-wrap" id="resultBadge">
        <i class="fa-solid fa-trophy" id="resultIcon"></i>
      </div>
      <h2 class="result-title" id="resultTitle">Excelente trabalho, Chef!</h2>
      <p class="result-score" id="resultScore"></p>
      
      <div class="result-stars" aria-label="Estrelas conquistadas">
        <i class="fa-solid fa-star star-icon" id="s1"></i>
        <i class="fa-solid fa-star star-icon" id="s2"></i>
        <i class="fa-solid fa-star star-icon" id="s3"></i>
      </div>

      <div class="result-stats">
        <div class="result-stat-card">
          <div class="result-stat-value" id="rScore">0</div>
          <div class="result-stat-label">Pontuacao</div>
        </div>
        <div class="result-stat-card">
          <div class="result-stat-value" id="rCorrect">0</div>
          <div class="result-stat-label">Acertos</div>
        </div>
        <div class="result-stat-card">
          <div class="result-stat-value" id="rWrong">0</div>
          <div class="result-stat-label">Erros</div>
        </div>
      </div>

      <div class="result-actions">
        <button class="btn btn-primary" onclick="restartGame()">
          <i class="fa-solid fa-rotate-right"></i> Jogar Novamente
        </button>
        <a href="/vortex/dashboard.php" class="btn btn-secondary">
          <i class="fa-solid fa-chart-line"></i> Dashboard
        </a>
        <a href="/vortex/games/geometry.php" class="btn btn-success">
          <i class="fa-solid fa-city"></i> Construtor de Cidades
        </a>
      </div>
    </div>
  </div>
</div>

<script>
var questions = <?= json_encode(array_values($questions)) ?>;
var gameId    = <?= (int)$game['id'] ?>;
var current   = 0;
var score     = 0;
var correct   = 0;
var wrong     = 0;
var answered  = false;
var startTime = Date.now();
var hintUsed  = false;

var scenarios = [
  {icon:'fa-pizza-slice', text:'A pizzaria precisa da proporcao exata de ingredientes!'},
  {icon:'fa-cake-candles', text:'O bolo de aniversario exige medidas fracionarias precisas!'},
  {icon:'fa-bread-slice', text:'A massa artesanal precisa da quantidade exata de farinha!'},
  {icon:'fa-bowl-rice', text:'A receita gourmet exige proporcoes perfeitas de tempero!'},
  {icon:'fa-cheese', text:'Divida as porcoes de queijo na proporcao solicitada!'},
  {icon:'fa-cookie', text:'Os cookies precisam de fracoes exatas de acucar e leite!'},
  {icon:'fa-mug-hot', text:'A bebida especial requer a mistura na razao adequada!'},
  {icon:'fa-apple-whole', text:'Divida os ingredientes frescos para a sobremesa!'}
];

function loadQuestion(){
  if(current >= questions.length){ showResult(); return; }
  var q = questions[current];
  answered = false; hintUsed = false;

  document.getElementById('questionText').textContent = q.question_text;
  document.getElementById('qLabel').textContent = 'Questao ' + (current + 1) + ' de ' + questions.length;
  document.getElementById('qnum').textContent = (current + 1) + '/' + questions.length;
  
  var pct = Math.round(current / questions.length * 100);
  document.getElementById('progressBar').style.width = pct + '%';
  document.getElementById('progressLabel').textContent = pct + '%';

  var sc = scenarios[current % scenarios.length];
  var scIcon = document.getElementById('scenarioIcon');
  scIcon.className = 'fa-solid ' + sc.icon;
  document.getElementById('scenarioText').textContent = sc.text;

  var opts = ['A','B','C','D'];
  var keys = ['option_a','option_b','option_c','option_d'];
  var grid = document.getElementById('optionsGrid');
  grid.innerHTML = '';

  keys.forEach(function(k, i){
    var btn = document.createElement('button');
    btn.className = 'option-btn';
    btn.innerHTML = '<span class="option-letter">' + opts[i] + '</span><span>' + q[k] + '</span>';
    btn.onclick = function(){ if(!answered) selectAnswer(opts[i], btn, q); };
    btn.setAttribute('aria-label', 'Opcao ' + opts[i] + ': ' + q[k]);
    grid.appendChild(btn);
  });

  document.getElementById('hintBox').classList.remove('show');
  document.getElementById('feedbackBox').className = 'feedback-box';
  document.getElementById('hintBtn').classList.remove('d-none');
  document.getElementById('nextBtn').classList.add('d-none');

  startTimer(30, function(){
    if(!answered){ selectAnswer('TIMEOUT', null, q); }
  }, document.getElementById('timer'));
}

function selectAnswer(chosen, btnEl, q){
  if(answered) return;
  answered = true;
  stopTimer();
  var pts = 0;
  var allBtns = document.querySelectorAll('.option-btn');
  allBtns.forEach(function(b){ b.disabled = true; });

  if(chosen === q.correct_answer){
    pts = hintUsed ? 45 : 50;
    score += pts;
    correct++;
    if(btnEl) btnEl.classList.add('correct');
    flashCorrect();
    showFeedback(true, 'Perfeito, Chef!', q.explanation || 'Excelente resposta!');
    showToast('Acertou! +' + pts + ' pontos', 'success');
  } else {
    wrong++;
    if(btnEl) btnEl.classList.add('wrong');
    var opts = ['A','B','C','D'];
    allBtns.forEach(function(b, i){
      if(opts[i] === q.correct_answer) b.classList.add('correct');
    });
    flashWrong();
    showFeedback(false, 'Quase la!', q.explanation || ('A resposta correta era a opcao ' + q.correct_answer));
    showToast('Errou! Veja a explicacao detalhada', 'error');
  }
  document.getElementById('score').textContent = score;
  document.getElementById('nextBtn').classList.remove('d-none');
  document.getElementById('hintBtn').classList.add('d-none');
}

function showFeedback(isCorrect, title, explanation){
  var box = document.getElementById('feedbackBox');
  box.className = 'feedback-box ' + (isCorrect ? 'feedback-correct' : 'feedback-wrong') + ' show';
  document.getElementById('feedbackTitle').innerHTML = '<i class="fa-solid ' + (isCorrect ? 'fa-circle-check' : 'fa-circle-xmark') + '"></i> ' + title;
  document.getElementById('feedbackExplanation').textContent = explanation;
}

function showHint(){
  if(hintUsed || answered) return;
  hintUsed = true;
  var hints = [
    'Pense em quantas partes iguais o todo esta dividido.',
    'Lembre: o numerador indica quantas partes voce tem e o denominador o total de partes.',
    'Fracoes equivalentes representam o mesmo valor matematico.',
    'Para somar fracoes com denominadores iguais, basta somar os numeradores.',
    'Para comparar fracoes diferentes, converta-as para um denominador comum.'
  ];
  document.getElementById('hintText').textContent = hints[current % hints.length];
  document.getElementById('hintBox').classList.add('show');
  document.getElementById('hintBtn').classList.add('d-none');
}

function nextQuestion(){
  current++;
  loadQuestion();
}

function speakQuestion(){
  var q = questions[current];
  if(q) speak(q.question_text);
}

function showResult(){
  stopTimer();
  var elapsed = Math.round((Date.now() - startTime) / 1000);
  document.getElementById('questionArea').classList.add('d-none');
  document.getElementById('resultArea').classList.remove('d-none');

  var pct = correct / questions.length;
  var icon = pct >= 0.8 ? 'fa-trophy' : (pct >= 0.5 ? 'fa-award' : 'fa-bolt');
  var title = pct >= 0.8 ? 'Chef Estrela! Desempenho Impecavel!' : (pct >= 0.5 ? 'Bom trabalho, Chef!' : 'Continue praticando, Chef!');

  document.getElementById('resultIcon').className = 'fa-solid ' + icon;
  document.getElementById('resultTitle').textContent = title;
  document.getElementById('resultScore').textContent = 'Voce fez ' + score + ' pontos com ' + correct + ' acertos em ' + questions.length + ' questoes!';
  document.getElementById('rScore').textContent = score;
  document.getElementById('rCorrect').textContent = correct;
  document.getElementById('rWrong').textContent = wrong;

  var stars = Math.ceil(pct * 3);
  for(var i = 1; i <= stars; i++){
    setTimeout(function(si){
      return function(){
        var el = document.getElementById('s' + si);
        if(el) el.classList.add('lit');
      };
    }(i), i * 250);
  }

  saveScore(gameId, score, correct, wrong, elapsed, 'easy');
}

function restartGame(){
  current = 0; score = 0; correct = 0; wrong = 0; answered = false; startTime = Date.now();
  document.getElementById('score').textContent = '0';
  document.getElementById('questionArea').classList.remove('d-none');
  document.getElementById('resultArea').classList.add('d-none');
  ['s1','s2','s3'].forEach(function(id){
    var el = document.getElementById(id);
    if(el) el.classList.remove('lit');
  });
  questions.sort(function(){ return Math.random() - 0.5; });
  loadQuestion();
}

document.addEventListener('DOMContentLoaded', function(){ loadQuestion(); });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
