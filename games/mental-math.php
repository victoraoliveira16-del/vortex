<?php
$pageTitle = 'Calculadora Mental - Modo Infinito';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$stmt = $pdo->prepare('SELECT * FROM games WHERE slug=?');
$stmt->execute(['mental-math']);
$game = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="calc-arcade-wrapper">
  <!-- Unified Arcade Cabinet -->
  <div class="arcade-cabinet" id="arcadeCabinet">
    
    <!-- Cabinet Top Marquee Bar -->
    <div class="arcade-marquee">
      <a href="/vortex/games/index.php" class="arcade-nav-back" title="Voltar para Todos os Jogos">
        <i class="fa-solid fa-chevron-left"></i> <span>Jogos</span>
      </a>
      <div class="arcade-title-group">
        <span class="arcade-live-badge"><i class="fa-solid fa-circle"></i> MODO INFINITO</span>
        <h1 class="arcade-title"><i class="fa-solid fa-bolt text-warning"></i> Calculadora Mental</h1>
      </div>
      <div class="arcade-top-actions">
        <button type="button" class="arcade-pill-btn tts-btn" onclick="speakQuestion()" aria-label="Ouvir conta" title="Ouvir conta em voz alta">
          <i class="fa-solid fa-volume-high"></i>
        </button>
        <button type="button" class="arcade-btn-exit" onclick="confirmFinishGame()" title="Salvar pontuação e encerrar partida">
          <i class="fa-solid fa-flag-checkered"></i> Encerrar
        </button>
      </div>
    </div>

    <!-- Integrated HUD Panel -->
    <div class="arcade-hud">
      <!-- Row 1: Lives, Level, Score -->
      <div class="arcade-hud-row">
        <!-- Vidas (Esquerda) -->
        <div class="arcade-metric arcade-lives-metric">
          <div class="arcade-metric-label"><i class="fa-solid fa-heart-pulse text-danger"></i> Vidas</div>
          <div class="arcade-lives-box" id="livesBox" aria-label="3 vidas restantes">
            <span class="life-heart lit"><i class="fa-solid fa-heart"></i></span>
            <span class="life-heart lit"><i class="fa-solid fa-heart"></i></span>
            <span class="life-heart lit"><i class="fa-solid fa-heart"></i></span>
          </div>
        </div>

        <!-- Nível & Questão (Centro) -->
        <div class="arcade-metric arcade-center-metric">
          <div class="calc-level-badge level-easy" id="levelBadge">
            <i class="fa-solid fa-seedling"></i> Nível 1 · Fácil
          </div>
          <div class="arcade-qnum" id="qnum">Questão #1</div>
        </div>

        <!-- Pontuação (Direita) -->
        <div class="arcade-metric arcade-score-metric">
          <div class="arcade-metric-label"><i class="fa-solid fa-trophy text-warning"></i> Pontos</div>
          <div class="arcade-score-value" id="score">0</div>
        </div>
      </div>

      <!-- Row 2: Combo Meter + Cronômetro Digital com Barra Fluida -->
      <div class="arcade-hud-sub">
        <div class="calc-streak-badge" id="streakBadge">
          <i class="fa-solid fa-bolt"></i> <span id="streakText">Combo x1</span>
        </div>
        
        <div class="arcade-timer-widget">
          <div class="arcade-timer-bar-track" aria-hidden="true">
            <div class="arcade-timer-bar-fill" id="timerFill" style="width: 100%;"></div>
          </div>
          <div class="arcade-timer-digital" id="timerBox">
            <i class="fa-solid fa-stopwatch"></i>
            <span id="timerText">15.0</span>s
          </div>
        </div>
      </div>

      <!-- Barra de Progresso dentro da faixa de nível atual -->
      <div class="arcade-tier-track" title="Progresso de nível">
        <div class="arcade-tier-fill" id="progressBar" style="width: 33%;" role="progressbar" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100"></div>
      </div>
    </div>

    <!-- Question & CRT Display Area -->
    <div id="questionArea">
      <!-- Arcade CRT Expression Screen -->
      <div class="arcade-screen-bezel">
        <div class="arcade-screen" id="arcadeScreen">
          <!-- Pop de Pontuação Flutuante -->
          <div class="arcade-combo-pop" id="comboPop">+100</div>

          <div class="arcade-prompt-label">Resolva o Cálculo:</div>
          <div class="calc-expression" id="calcExpr">4 × 5</div>

          <!-- Display de Entrada Numérica -->
          <div class="arcade-display" id="calcDisplay">
            <span class="arcade-display-text" id="calcDisplayText"></span>
            <span class="arcade-cursor"></span>
          </div>
        </div>
      </div>

      <!-- Tactile Arcade Controller Keypad (3D) -->
      <div class="arcade-controller">
        <div class="calc-keypad" id="calcKeypad">
          <button type="button" class="arcade-key" onclick="pressKey('1')">1</button>
          <button type="button" class="arcade-key" onclick="pressKey('2')">2</button>
          <button type="button" class="arcade-key" onclick="pressKey('3')">3</button>

          <button type="button" class="arcade-key" onclick="pressKey('4')">4</button>
          <button type="button" class="arcade-key" onclick="pressKey('5')">5</button>
          <button type="button" class="arcade-key" onclick="pressKey('6')">6</button>

          <button type="button" class="arcade-key" onclick="pressKey('7')">7</button>
          <button type="button" class="arcade-key" onclick="pressKey('8')">8</button>
          <button type="button" class="arcade-key" onclick="pressKey('9')">9</button>

          <button type="button" class="arcade-key arcade-key-comma" onclick="pressKey('-')" title="Sinal Negativo">-</button>
          <button type="button" class="arcade-key" onclick="pressKey('0')">0</button>
          <button type="button" class="arcade-key arcade-key-backspace" onclick="backspaceKey()" aria-label="Apagar dígito" title="Apagar (Backspace)">
            <svg width="24" height="20" viewBox="0 0 24 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M7.5 1.5L1.5 10L7.5 18.5H22.5V1.5H7.5Z" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 6.5L18 13.5M18 6.5L12 13.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>

        <!-- Botão Principal de Disparo / Envio -->
        <button type="button" class="arcade-go-btn" id="goBtn" onclick="submitAnswer()">
          <span>ENVIAR RESPOSTA</span>
          <span class="arcade-go-kbd"><kbd>ENTER</kbd></span>
        </button>

        <!-- Feedback & Explicação -->
        <div class="feedback-box mt-3" id="feedbackBox">
          <div class="feedback-title" id="feedbackTitle"></div>
          <div class="feedback-explanation" id="feedbackExplanation"></div>
        </div>

        <!-- Barra de Ação (Próxima questão após erro) -->
        <div class="game-actions-bar mt-3 d-none" id="actionsBar">
          <button class="arcade-next-btn" onclick="nextQuestion()" id="nextBtn">
            Próxima Questão <i class="fa-solid fa-arrow-right"></i>
          </button>
        </div>

        <!-- Dica de Teclado -->
        <div class="arcade-keyboard-hint">
          <i class="fa-solid fa-keyboard"></i> Dica: Você pode digitar os números e apertar Enter no teclado!
        </div>
      </div>
    </div>

    <!-- Victory & Results Area -->
    <div id="resultArea" class="d-none" aria-live="polite">
      <div class="question-card game-result" style="background: transparent; border: none; padding: 0; box-shadow: none;">
        <div class="result-badge-wrap" id="resultBadge">
          <i class="fa-solid fa-trophy" id="resultIcon"></i>
        </div>
        <h2 class="result-title text-white" id="resultTitle">Excelente Agilidade Mental!</h2>
        <p class="result-score text-slate-300" id="resultScore"></p>
        
        <div class="result-stars" aria-label="Estrelas conquistadas">
          <i class="fa-solid fa-star star-icon" id="s1"></i>
          <i class="fa-solid fa-star star-icon" id="s2"></i>
          <i class="fa-solid fa-star star-icon" id="s3"></i>
        </div>

        <div class="result-stats">
          <div class="result-stat-card" style="background: #0B1C2D; border-color: #1B4568;">
            <div class="result-stat-value text-warning" id="rScore">0</div>
            <div class="result-stat-label text-slate-400">Pontuação</div>
          </div>
          <div class="result-stat-card" style="background: #0B1C2D; border-color: #1B4568;">
            <div class="result-stat-value text-success" id="rCorrect">0</div>
            <div class="result-stat-label text-slate-400">Acertos</div>
          </div>
          <div class="result-stat-card" style="background: #0B1C2D; border-color: #1B4568;">
            <div class="result-stat-value text-danger" id="rWrong">0</div>
            <div class="result-stat-label text-slate-400">Erros</div>
          </div>
          <div class="result-stat-card" style="background: #0B1C2D; border-color: #1B4568;">
            <div class="result-stat-value text-info" id="rMaxStreak">0</div>
            <div class="result-stat-label text-slate-400">Maior Combo</div>
          </div>
        </div>

        <div class="result-actions mt-4">
          <button onclick="restartGame()" class="arcade-go-btn" style="max-width: 260px; margin: 0 auto;">
            <i class="fa-solid fa-rotate-left"></i> Jogar Novamente
          </button>
          <div class="d-flex gap-2 justify-content-center mt-3">
            <a href="/vortex/dashboard.php" class="btn btn-secondary">
              <i class="fa-solid fa-chart-line"></i> Dashboard
            </a>
            <a href="/vortex/games/index.php" class="btn btn-success">
              <i class="fa-solid fa-gamepad"></i> Outros Jogos
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
var questions = [];

var gameId    = <?= (int)($game['id'] ?? 3) ?>;
var current   = 0;
var score     = 0;
var correct   = 0;
var wrong     = 0;
var streak    = 0;
var maxStreak = 0;
var MAX_LIVES = 3;
var lives     = MAX_LIVES;
var answered  = false;
var currentVal = '';
var startTime = Date.now();
var isFetchingNext = false;

// Motor de Tempo: 15 Segundos Exatos
var QUESTION_TIME = 15.0;
var timeLeft = QUESTION_TIME;
var timerInterval = null;

function getLevelForQuestion(qNum){
  if(qNum <= 3) return 1;
  if(qNum <= 6) return 2;
  if(qNum <= 10) return 3;
  if(qNum <= 15) return 4;
  if(qNum <= 20) return 5;
  return Math.floor((qNum - 21) / 5) + 6;
}

function getLevelInfo(lvl){
  if(lvl === 1) return { name: 'Nível 1 · Fácil', cls: 'level-easy', icon: 'fa-seedling' };
  if(lvl === 2) return { name: 'Nível 2 · Médio', cls: 'level-medium', icon: 'fa-bolt' };
  if(lvl === 3) return { name: 'Nível 3 · Difícil', cls: 'level-hard', icon: 'fa-fire' };
  if(lvl === 4) return { name: 'Nível 4 · Especialista', cls: 'level-hard', icon: 'fa-brain' };
  if(lvl === 5) return { name: 'Nível 5 · Desafio Mestre', cls: 'level-master', icon: 'fa-crown' };
  return { name: 'Nível ' + lvl + ' · Lenda dos Números', cls: 'level-master', icon: 'fa-wand-magic-sparkles' };
}

function updateLivesUI(){
  var box = document.getElementById('livesBox');
  if(!box) return;
  var html = '';
  for(var i = 0; i < MAX_LIVES; i++){
    if(i < lives){
      html += '<span class="life-heart lit"><i class="fa-solid fa-heart"></i></span>';
    } else {
      html += '<span class="life-heart lost"><i class="fa-solid fa-heart"></i></span>';
    }
  }
  box.innerHTML = html;
  box.setAttribute('aria-label', lives + ' de ' + MAX_LIVES + ' vidas restantes');
}

function startQuestionTimer(){
  clearInterval(timerInterval);
  timeLeft = QUESTION_TIME;
  updateTimerUI();

  timerInterval = setInterval(function(){
    if(answered) { clearInterval(timerInterval); return; }
    timeLeft -= 0.1;
    if(timeLeft <= 0){
      timeLeft = 0;
      updateTimerUI();
      clearInterval(timerInterval);
      if(!answered){
        timeExpired();
      }
    } else {
      updateTimerUI();
    }
  }, 100);
}

function updateTimerUI(){
  var formatted = Math.max(0, timeLeft).toFixed(1);
  var textEl = document.getElementById('timerText');
  var fillEl = document.getElementById('timerFill');
  if(textEl) textEl.textContent = formatted;

  if(fillEl){
    var pct = (timeLeft / QUESTION_TIME) * 100;
    fillEl.style.width = Math.max(0, pct) + '%';
    if(timeLeft <= 3.0){
      fillEl.classList.add('urgent');
    } else {
      fillEl.classList.remove('urgent');
    }
  }
}

async function loadQuestion(){
  if(lives <= 0){
    finishGame(true);
    return;
  }

  // Se a questão ainda não estiver no buffer, carrega com spinner
  if(current >= questions.length){
    document.getElementById('calcExpr').innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="font-size:2.5rem;"></i>';
    var qNumTarget = current + 1;
    var lvlTarget = getLevelForQuestion(qNumTarget);
    var newQ = await createNextQuestion(lvlTarget);
    if(newQ){
      questions.push(newQ);
    } else {
      showToast('Erro ao carregar questão. Tentando novamente...', 'error');
      setTimeout(loadQuestion, 1000);
      return;
    }
  }

  answered = false;
  currentVal = '';
  updateDisplay();

  var q = questions[current];
  var qNum = current + 1;
  var currentLvl = q.level || getLevelForQuestion(qNum);

  document.getElementById('calcExpr').textContent = q.question_text;
  document.getElementById('qnum').textContent = '#' + qNum;

  // Atualiza Badge de Nível
  var lInfo = getLevelInfo(currentLvl);
  var levelBadge = document.getElementById('levelBadge');
  if(levelBadge){
    levelBadge.className = 'calc-level-badge ' + lInfo.cls;
    levelBadge.innerHTML = '<i class="fa-solid ' + lInfo.icon + '"></i> ' + lInfo.name;
  }

  // Notificações visuais de evolução de nível
  if(qNum === 4){
    showToast('🎉 Nível 2 Desbloqueado! Contas com números maiores!', 'info');
  } else if(qNum === 7){
    showToast('🔥 Nível 3 Ativado! Multiplicações e divisões avançadas!', 'warning');
  } else if(qNum === 11){
    showToast('🧠 Nível 4 Especialista! Foco total!', 'achievement');
  } else if(qNum === 16){
    showToast('👑 Nível 5 Desafio Mestre! Agilidade extrema!', 'achievement');
  } else if(qNum >= 21 && (qNum - 21) % 5 === 0){
    showToast('✨ Nível ' + currentLvl + ' · Lenda da Matemática! Ritmo insano!', 'achievement');
  }

  // Atualiza Barra de Progresso dentro do tier do nível
  var tierProgress = 0;
  if(qNum <= 3) { tierProgress = Math.round((qNum / 3) * 100); }
  else if(qNum <= 6) { tierProgress = Math.round(((qNum - 3) / 3) * 100); }
  else if(qNum <= 10) { tierProgress = Math.round(((qNum - 6) / 4) * 100); }
  else if(qNum <= 15) { tierProgress = Math.round(((qNum - 10) / 5) * 100); }
  else if(qNum <= 20) { tierProgress = Math.round(((qNum - 15) / 5) * 100); }
  else { tierProgress = Math.round((((qNum - 21) % 5 + 1) / 5) * 100); }
  
  var progressBar = document.getElementById('progressBar');
  if(progressBar){
    progressBar.style.width = tierProgress + '%';
    progressBar.setAttribute('aria-valuenow', tierProgress);
  }

  document.getElementById('feedbackBox').className = 'feedback-box';
  document.getElementById('actionsBar').classList.add('d-none');
  document.getElementById('goBtn').classList.remove('d-none');

  // Reabilita o teclado arcade
  document.querySelectorAll('.arcade-key, .calc-key').forEach(function(b){ b.disabled = false; });

  startQuestionTimer();

  // Prefetch contínuo de questões em segundo plano
  ensurePrefetch();
}

function pressKey(key){
  if(answered) return;
  if(currentVal.length >= 7) return;
  if(key === '-' && currentVal.length > 0) return;
  currentVal += key;
  updateDisplay();
}

function backspaceKey(){
  if(answered) return;
  if(currentVal.length > 0){
    currentVal = currentVal.slice(0, -1);
    updateDisplay();
  }
}

function updateDisplay(){
  var textEl = document.getElementById('calcDisplayText');
  if(textEl) textEl.textContent = currentVal;
}

function timeExpired(){
  submitAnswer('TIMEOUT');
}

async function submitAnswer(overrideVal){
  if(answered || lives <= 0) return;
  answered = true;
  clearInterval(timerInterval);

  var chosen = (overrideVal !== undefined) ? overrideVal : currentVal.trim();
  if(!chosen && overrideVal === undefined){
    answered = false;
    startQuestionTimer();
    showToast('Digite sua resposta antes de enviar!', 'warning');
    return;
  }

  // Desabilita botões durante validação
  document.querySelectorAll('.arcade-key, .calc-key').forEach(function(b){ b.disabled = true; });
  document.getElementById('goBtn').classList.add('d-none');

  var q = questions[current];
  var result = await checkAnswer(gameId, q.id, chosen, false);

  var isCorrect = !!(result && result.correct);
  var pts = (result && result.points) || 0;
  var correctLetter = (result && result.correct_letter) || '';
  var explanation = (result && result.explanation) || '';

  if(isCorrect){
    streak++;
    if(streak > maxStreak) maxStreak = streak;

    // Multiplicador por Combo
    var multiplier = streak >= 5 ? 3 : (streak >= 3 ? 2 : 1);
    var earned = pts * multiplier;
    score += earned;
    correct++;

    flashCorrect();
    updateStreakUI();

    // Efeito arcade na tela (brilho verde e combo pop flutuante)
    var screenEl = document.getElementById('arcadeScreen');
    if(screenEl){
      screenEl.classList.add('screen-correct');
      setTimeout(function(){ screenEl.classList.remove('screen-correct'); }, 550);
    }
    var popEl = document.getElementById('comboPop');
    if(popEl){
      popEl.textContent = '+' + earned + (multiplier > 1 ? ' (' + multiplier + 'x Combo!)' : '');
      popEl.classList.add('show');
      setTimeout(function(){ popEl.classList.remove('show'); }, 600);
    }

    showFeedback(true, 'Correto! +' + earned + ' pts' + (multiplier > 1 ? ' (' + multiplier + 'x Combo!)' : ''), explanation || 'Excelente velocidade mental!');
    showToast('Acertou! +' + earned + ' pontos', 'success');

    setTimeout(nextQuestion, 550);
  } else {
    streak = 0;
    wrong++;
    lives--;
    updateLivesUI();
    updateStreakUI();
    flashWrong();

    // Tremor tátil no gabinete arcade
    var cabinetEl = document.getElementById('arcadeCabinet');
    if(cabinetEl){
      cabinetEl.classList.add('screen-shake');
      setTimeout(function(){ cabinetEl.classList.remove('screen-shake'); }, 420);
    }

    var msg = (chosen === 'TIMEOUT') ? 'Tempo Esgotado! (15s)' : 'Resposta Incorreta!';

    if(lives <= 0){
      showFeedback(false, msg, (explanation || ('O resultado correto era: ' + correctLetter)) + ' 💔 Fim das 3 vidas!');
      showToast('Fim de jogo! Suas 3 vidas acabaram.', 'error');
      setTimeout(function(){
        finishGame(true);
      }, 1500);
      return;
    } else {
      var lifeText = lives === 1 ? 'Resta apenas 1 vida!' : 'Restam ' + lives + ' vidas!';
      showFeedback(false, msg, (explanation || ('O resultado correto era: ' + correctLetter)) + ' — ' + lifeText);
      showToast('Errou! ' + lifeText, 'error');
      document.getElementById('actionsBar').classList.remove('d-none');
    }
  }

  document.getElementById('score').textContent = score;
}

function updateStreakUI(){
  var badge = document.getElementById('streakBadge');
  var text = document.getElementById('streakText');
  if(!badge || !text) return;

  if(streak >= 5){
    badge.className = 'calc-streak-badge fire';
    text.textContent = '🔥 Ultra x' + streak + '!';
  } else if(streak >= 2){
    badge.className = 'calc-streak-badge fire';
    text.textContent = '⚡ Combo x' + streak;
  } else {
    badge.className = 'calc-streak-badge';
    text.textContent = 'Combo x1';
  }
}

function showFeedback(isCorrect, title, explanation){
  var box = document.getElementById('feedbackBox');
  box.className = 'feedback-box ' + (isCorrect ? 'feedback-correct' : 'feedback-wrong') + ' show';
  document.getElementById('feedbackTitle').innerHTML = '<i class="fa-solid ' + (isCorrect ? 'fa-circle-check' : 'fa-circle-xmark') + '"></i> ' + title;
  document.getElementById('feedbackExplanation').textContent = explanation;
}

async function createNextQuestion(lvl){
  try {
    var token = getCsrfToken();
    var response = await fetch('/vortex/api/generate_mental_question.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token
      },
      body: JSON.stringify({ game_id: gameId, level: lvl || 1, csrf_token: token })
    });
    var data = await response.json();
    if(data.success && data.question){
      data.question.level = lvl || 1;
      return data.question;
    }
    return null;
  } catch (error) {
    console.error('Erro ao gerar questão mental:', error);
    return null;
  }
}

async function ensurePrefetch(){
  var targetCount = current + 3;
  if(questions.length < targetCount && !isFetchingNext){
    isFetchingNext = true;
    var nextQNum = questions.length + 1;
    var nextLvl = getLevelForQuestion(nextQNum);
    var q = await createNextQuestion(nextLvl);
    if(q){
      questions.push(q);
    }
    isFetchingNext = false;
  }
}

async function seedQuestions(){
  questions = [];
  current = 0;
  for(var i = 1; i <= 3; i++){
    var next = await createNextQuestion(1);
    if(next){
      questions.push(next);
    }
  }

  if(questions.length === 0){
    showToast('Não foi possível inicializar as questões.', 'error');
    return;
  }

  loadQuestion();
}

function nextQuestion(){
  current++;
  loadQuestion();
}

function speakQuestion(){
  var q = questions[current];
  if(q){
    var text = q.question_text
      .replace('×', 'vezes')
      .replace('÷', 'dividido por')
      .replace('+', 'mais')
      .replace('-', 'menos');
    speak('Quanto é ' + text + '?');
  }
}

function confirmFinishGame(){
  if(confirm('Deseja encerrar a partida agora e registrar sua pontuação de ' + score + ' pontos?')){
    finishGame(false);
  }
}

function finishGame(isGameOver){
  clearInterval(timerInterval);
  var elapsed = Math.round((Date.now() - startTime) / 1000);
  document.getElementById('questionArea').classList.add('d-none');
  document.getElementById('resultArea').classList.remove('d-none');

  var totalAnswered = correct + wrong;
  var finalLvl = getLevelForQuestion(totalAnswered || 1);
  var accPct = totalAnswered > 0 ? (correct / totalAnswered) : 0;

  var icon = isGameOver ? 'fa-heart-crack' : 'fa-trophy';
  var title = isGameOver ? 'Fim de Jogo! Ótima Resistência Mental!' : 'Partida Encerrada com Sucesso!';
  if(correct >= 20) {
    title = '🔥 Nível Lendário! Reflexos Extraordinários!';
    icon = 'fa-crown';
  } else if(correct >= 10){
    title = '⚡ Excelente Agilidade Mental!';
    icon = 'fa-bolt';
  }

  document.getElementById('resultIcon').className = 'fa-solid ' + icon;
  document.getElementById('resultTitle').textContent = title;
  document.getElementById('resultScore').textContent = 'Você conquistou ' + score + ' pontos e chegou até o Nível ' + finalLvl + ' (' + correct + ' acertos em ' + totalAnswered + ' contas resolvidas)!';
  document.getElementById('rScore').textContent = score;
  document.getElementById('rCorrect').textContent = correct;
  document.getElementById('rWrong').textContent = wrong;
  document.getElementById('rMaxStreak').textContent = 'x' + maxStreak;

  // Sistema de Estrelas:
  // 3 Estrelas: 12+ acertos e >= 70% de precisão
  // 2 Estrelas: 6+ acertos e >= 50% de precisão
  // 1 Estrela: 1+ acertos
  var stars = 1;
  if(correct >= 12 && accPct >= 0.70){
    stars = 3;
  } else if(correct >= 6 && accPct >= 0.50){
    stars = 2;
  } else if(correct < 1){
    stars = 0;
  }

  for(var i = 1; i <= stars; i++){
    setTimeout(function(si){
      return function(){
        var el = document.getElementById('s' + si);
        if(el) el.classList.add('lit');
      };
    }(i), i * 250);
  }

  var finalDiff = finalLvl >= 4 ? 'hard' : (finalLvl >= 2 ? 'medium' : 'easy');
  saveScore(gameId, elapsed, finalDiff, score, correct, wrong);
}

function restartGame(){
  current = 0; score = 0; correct = 0; wrong = 0; streak = 0; maxStreak = 0;
  lives = MAX_LIVES; answered = false; currentVal = ''; startTime = Date.now();
  
  document.getElementById('score').textContent = '0';
  document.getElementById('qnum').textContent = '#1';
  document.getElementById('questionArea').classList.remove('d-none');
  document.getElementById('resultArea').classList.add('d-none');
  
  ['s1','s2','s3'].forEach(function(id){
    var el = document.getElementById(id);
    if(el) el.classList.remove('lit');
  });

  updateLivesUI();
  updateStreakUI();
  seedQuestions();
}

// Suporte total ao teclado físico (0-9, Backspace, Enter, -, vírgula)
document.addEventListener('keydown', function(e){
  if(document.getElementById('questionArea').classList.contains('d-none')) return;

  if(e.key >= '0' && e.key <= '9'){
    pressKey(e.key);
    highlightKey(e.key);
  } else if(e.key === '-' || e.key === 'Subtract'){
    pressKey('-');
    highlightKey('-');
  } else if(e.key === 'Backspace'){
    backspaceKey();
    var bKey = document.querySelector('.arcade-key-backspace, .calc-key-backspace');
    if(bKey){ bKey.classList.add('pressed'); setTimeout(function(){ bKey.classList.remove('pressed'); }, 100); }
  } else if(e.key === 'Enter'){
    if(answered){
      var actionsVisible = !document.getElementById('actionsBar').classList.contains('d-none');
      if(actionsVisible) nextQuestion();
    } else {
      var goBtn = document.getElementById('goBtn');
      if(goBtn){ goBtn.classList.add('pressed'); setTimeout(function(){ goBtn.classList.remove('pressed'); }, 100); }
      submitAnswer();
    }
  }
});

function highlightKey(k){
  document.querySelectorAll('.arcade-key, .calc-key').forEach(function(btn){
    if(btn.textContent.trim() === k){
      btn.classList.add('pressed');
      setTimeout(function(){ btn.classList.remove('pressed'); }, 100);
    }
  });
}

document.addEventListener('DOMContentLoaded', function(){ 
  updateLivesUI();
  seedQuestions(); 
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
