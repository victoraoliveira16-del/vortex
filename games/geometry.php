<?php
$pageTitle = 'Construtor de Cidades';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$stmt = $pdo->prepare('SELECT * FROM games WHERE slug=?');
$stmt->execute(['geometry']);
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
        <i class="fa-solid fa-city text-blue"></i> Construtor de Cidades
      </h1>
      <p class="game-header-subtitle">Calcule areas e perimetros para expandir sua metropole moderna!</p>
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
      <span class="xp-current">Progresso da Construcao</span>
      <span class="xp-next" id="progressLabel">0%</span>
    </div>
    <div class="xp-bar-wrap">
      <div class="xp-bar-fill" id="progressBar" style="width:0%;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
  </div>

  <!-- Animated City Scenery -->
  <div class="city-scene" aria-label="Cenario da cidade em construcao" aria-live="polite">
    <div class="city-sky"></div>
    <div class="sun" aria-hidden="true"></div>
    <div class="city-buildings" id="cityBuildings"></div>
    <div class="city-ground"></div>
    <div class="city-stats">
      <div class="city-stat"><i class="fa-solid fa-building"></i> Edificios: <span id="buildingCount">0</span></div>
      <div class="city-stat"><i class="fa-solid fa-star"></i> Nivel: <span id="cityLevel">Vilarejo</span></div>
    </div>
  </div>

  <!-- Question Area -->
  <div id="questionArea">
    <div class="question-card" id="questionCard">
      <div class="question-number" id="qLabel">Questao 1 de <?= count($questions) ?></div>
      <div class="question-text" id="questionText">Carregando pergunta geometrica...</div>

      <button class="tts-btn" onclick="speakQuestion()" aria-label="Ouvir questao em voz alta">
        <i class="fa-solid fa-volume-high"></i> Ouvir questao
      </button>

      <div class="options-grid" id="optionsGrid"></div>

      <div class="hint-box" id="hintBox">
        <div class="hint-title"><i class="fa-solid fa-compass-drafting"></i> Dica do Arquiteto</div>
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
        <i class="fa-solid fa-city" id="resultIcon"></i>
      </div>
      <h2 class="result-title" id="resultTitle">Metropole Construida!</h2>
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
        <a href="/vortex/games/fractions.php" class="btn btn-success">
          <i class="fa-solid fa-utensils"></i> Chef das Fracoes
        </a>
      </div>
    </div>
  </div>
</div>

<script>
// Gabarito removido: apenas campos de exibição são enviados ao cliente
var questions = <?= json_encode(array_values(array_map(function($q){
    return [
        'id'            => (int)$q['id'],
        'question_text' => $q['question_text'],
        'option_a'      => $q['option_a'],
        'option_b'      => $q['option_b'],
        'option_c'      => $q['option_c'],
        'option_d'      => $q['option_d'],
        'difficulty'    => $q['difficulty'],
    ];
}, $questions))) ?>;
var gameId = <?= (int)$game['id'] ?>;
var current = 0, score = 0, correct = 0, wrong = 0, answered = false, hintUsed = false;
var buildingCount = 0;
var startTime = Date.now();
var buildingColors = ['#EF4444', '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#06B6D4', '#EC4899', '#6366F1'];
var cityLevels = ['Vilarejo', 'Distrito', 'Cidade Polo', 'Metropole', 'Megalopole', 'Capital Global'];

function addBuilding(isCorrect){
  var container = document.getElementById('cityBuildings');
  var building = document.createElement('div');
  var height = isCorrect ? (45 + buildingCount * 14) : (22 + Math.random() * 15);
  var width = isCorrect ? 42 : 32;
  var color = isCorrect ? buildingColors[buildingCount % buildingColors.length] : '#64748B';

  building.className = 'building';
  building.style.width = width + 'px';
  building.style.height = '0px';
  building.style.background = color;
  building.innerHTML = '<div class="building-win"></div><div class="building-label">' + (isCorrect ? '<i class="fa-solid fa-check"></i>' : '') + '</div>';
  
  container.appendChild(building);
  requestAnimationFrame(function(){
    setTimeout(function(){ building.style.height = height + 'px'; }, 40);
  });

  if(isCorrect){
    buildingCount++;
    document.getElementById('buildingCount').textContent = buildingCount;
    document.getElementById('cityLevel').textContent = cityLevels[Math.min(buildingCount, 5)];
  }
}

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

  var opts = ['A','B','C','D'];
  var keys = ['option_a','option_b','option_c','option_d'];
  var grid = document.getElementById('optionsGrid');
  grid.innerHTML = '';

  keys.forEach(function(k, i){
    var btn = document.createElement('button');
    btn.className = 'option-btn';
    btn.innerHTML = '<span class="option-letter">' + opts[i] + '</span><span>' + (q[k] || '') + '</span>';
    btn.onclick = function(){
      if(answered) return;
      selectAnswer(opts[i], btn, q);
    };
    btn.setAttribute('aria-label', 'Opcao ' + opts[i] + ': ' + (q[k] || ''));
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

async function selectAnswer(chosen, btnEl, q){
  if(answered) return;
  answered = true;
  stopTimer();

  var allBtns = document.querySelectorAll('.option-btn');
  allBtns.forEach(function(b){ b.disabled = true; });

  if(btnEl) btnEl.classList.add('selected');

  // Valida no servidor — gabarito nunca fica no client
  var result = await checkAnswer(gameId, q.id, chosen, hintUsed);
  if(btnEl) btnEl.classList.remove('selected');

  if(!result || !result.success){
    answered = false;
    allBtns.forEach(function(b){ b.disabled = false; });
    showToast('Nao foi possivel confirmar a resposta. Tente novamente.', 'error');
    return;
  }

  var isCorrect     = !!(result && result.correct);
  var pts           = (result && result.points) || 0;
  var correctLetter = (result && result.correct_letter) || '';
  var explanation   = (result && result.explanation) || '';

  if(isCorrect){
    score += pts;
    correct++;
    if(btnEl) btnEl.classList.add('correct');
    addBuilding(true);
    flashCorrect();
    showFeedback(true, 'Edificio Ergido!', explanation || 'Excelente calculo geometrico, Arquiteto!');
    showToast('Acertou! +' + pts + ' pontos — Novo predio construido!', 'success');
  } else {
    wrong++;
    if(btnEl) btnEl.classList.add('wrong');
    var opts = ['A','B','C','D'];
    allBtns.forEach(function(b, i){
      if(opts[i] === correctLetter) b.classList.add('correct');
    });
    addBuilding(false);
    flashWrong();
    showFeedback(false, 'Falha Estrutural!', explanation || (correctLetter ? 'A resposta correta era a opcao ' + correctLetter : 'Resposta incorreta.'));
    showToast('Errou! Revise o calculo da area/perimetro', 'error');
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
    'Para calcular a area de um retangulo: multiplique a largura pelo comprimento (b x h).',
    'O perimetro e a soma de todas as medidas dos lados externos da figura.',
    'A area do quadrado e calculada elevando o lado ao quadrado (L x L).',
    'A area do triangulo e dada por (base x altura) dividida por 2.',
    'A area aproximada do circulo e dada por pi x raio ao quadrado (use pi = 3).',
    'Para figuras em formato de L, divida a regiao em dois retangulos e some as areas.'
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
  var icon = pct >= 0.8 ? 'fa-trophy' : (pct >= 0.5 ? 'fa-city' : 'fa-building');
  var title = pct >= 0.8 ? 'Metropole Incrivel! Obra-Prima!' : (pct >= 0.5 ? 'Bela cidade, Arquiteto!' : 'Continue construindo e praticando!');

  document.getElementById('resultIcon').className = 'fa-solid ' + icon;
  document.getElementById('resultTitle').textContent = title;
  document.getElementById('resultScore').textContent = 'Voce construiu ' + buildingCount + ' edificios e totalizou ' + score + ' pontos!';
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

  saveScore(gameId, elapsed, 'easy', score, correct, wrong);
}

function restartGame(){
  current = 0; score = 0; correct = 0; wrong = 0; answered = false; buildingCount = 0; startTime = Date.now();
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
