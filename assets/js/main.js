// MathPlay Solutions — main.js
var BASE_URL = (typeof window.BASE_URL !== 'undefined') ? window.BASE_URL : (function(){
  var p = window.location.pathname;
  return p.indexOf('/vortex') === 0 ? '/vortex' : '';
})();

// Preferences initialization (IIFE)
(function applyPrefs(){
  if(localStorage.getItem('darkMode') === '1') document.documentElement.classList.add('dark-mode');
  if(localStorage.getItem('contrast') === '1') document.documentElement.classList.add('high-contrast');
  if(localStorage.getItem('dyslexia') === '1') document.documentElement.classList.add('dyslexia-font');
  var fs = localStorage.getItem('fontSize');
  if(fs) document.documentElement.style.fontSize = fs + 'px';
})();

function toggleDark(){
  document.documentElement.classList.remove('high-contrast');
  localStorage.setItem('contrast', '0');
  var contrastBtn = document.getElementById('contrastBtn');
  if(contrastBtn) contrastBtn.classList.remove('active');
  var on = document.documentElement.classList.toggle('dark-mode');
  localStorage.setItem('darkMode', on ? '1' : '0');
  updateThemeButton(on);
}

function toggleContrast(){
  document.documentElement.classList.remove('dark-mode');
  localStorage.setItem('darkMode', '0');
  updateThemeButton(false);
  var on = document.documentElement.classList.toggle('high-contrast');
  localStorage.setItem('contrast', on ? '1' : '0');
  var btn = document.getElementById('contrastBtn');
  if(btn) btn.classList.toggle('active', on);
}

function updateThemeButton(isDark){
  var btn = document.getElementById('darkBtn');
  if(!btn) return;
  btn.classList.toggle('active', isDark);
  btn.title = isDark ? 'Mudar para tema claro' : 'Mudar para tema escuro';
  btn.setAttribute('aria-label', btn.title);
  btn.innerHTML = '<i class="fa-solid ' + (isDark ? 'fa-sun' : 'fa-moon') + '" aria-hidden="true"></i>';
}

function toggleDyslexia(){
  var on = document.documentElement.classList.toggle('dyslexia-font');
  localStorage.setItem('dyslexia', on ? '1' : '0');
  var btn = document.getElementById('dyslexiaBtn');
  if(btn) btn.classList.toggle('active', on);
}

function adjustFont(delta){
  var cur = parseInt(localStorage.getItem('fontSize') || '16', 10);
  var next = Math.min(24, Math.max(12, cur + delta));
  document.documentElement.style.fontSize = next + 'px';
  localStorage.setItem('fontSize', next);
}

document.addEventListener('DOMContentLoaded', function(){
  updateThemeButton(localStorage.getItem('darkMode') === '1');
  if(localStorage.getItem('contrast') === '1'){
    var b = document.getElementById('contrastBtn');
    if(b) b.classList.add('active');
  }
  if(localStorage.getItem('dyslexia') === '1'){
    var b = document.getElementById('dyslexiaBtn');
    if(b) b.classList.add('active');
  }

  // Hamburger Menu
  var ham = document.getElementById('hamburger');
  var nav = document.getElementById('navLinks');
  if(ham && nav){
    ham.addEventListener('click', function(){
      var open = nav.classList.toggle('open');
      ham.setAttribute('aria-expanded', open);
    });
    document.addEventListener('click', function(e){
      if(!ham.contains(e.target) && !nav.contains(e.target)){
        nav.classList.remove('open');
        ham.setAttribute('aria-expanded', 'false');
      }
    });
  }
});

// Toast System with Font Awesome Icons (No emojis)
function showToast(message, type, duration){
  type = type || 'info';
  duration = duration || 4000;
  var container = document.getElementById('toast-container');
  if(!container) return;

  var iconClasses = {
    success: 'fa-solid fa-circle-check',
    error: 'fa-solid fa-circle-xmark',
    warning: 'fa-solid fa-triangle-exclamation',
    info: 'fa-solid fa-circle-info',
    achievement: 'fa-solid fa-trophy',
    ai: 'fa-solid fa-brain'
  };

  var toast = document.createElement('div');
  toast.className = 'toast ' + type;
  toast.setAttribute('role', 'alert');
  toast.innerHTML = 
    '<div class="toast-icon"><i class="' + (iconClasses[type] || iconClasses.info) + '"></i></div>' +
    '<div class="toast-msg">' + message + '</div>' +
    '<button class="toast-close" onclick="this.parentElement.remove()" aria-label="Fechar">&times;</button>';

  container.appendChild(toast);
  setTimeout(function(){
    toast.style.animation = 'slideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1) reverse';
    setTimeout(function(){ toast.remove(); }, 300);
  }, duration);
}

// Text-to-Speech (TTS)
var ttsUtterance = null;
function speak(text){
  if(!('speechSynthesis' in window)){
    showToast('Navegador sem suporte a leitura por voz.', 'warning');
    return;
  }
  if(ttsUtterance){
    window.speechSynthesis.cancel();
    ttsUtterance = null;
    return;
  }
  ttsUtterance = new SpeechSynthesisUtterance(text);
  ttsUtterance.lang = 'pt-BR';
  ttsUtterance.rate = 0.95;
  ttsUtterance.onend = function(){ ttsUtterance = null; };
  window.speechSynthesis.speak(ttsUtterance);
}

// Countdown Timer Engine
var _timerInterval = null;
function startTimer(seconds, onEnd, displayEl){
  clearInterval(_timerInterval);
  var remaining = seconds;
  if(displayEl) displayEl.textContent = remaining;

  _timerInterval = setInterval(function(){
    remaining--;
    if(displayEl) displayEl.textContent = remaining;
    if(remaining <= 10 && displayEl){
      var tw = displayEl.closest('.game-timer');
      if(tw) tw.classList.add('urgent');
    }
    if(remaining <= 0){
      clearInterval(_timerInterval);
      if(onEnd) onEnd();
    }
  }, 1000);
  return _timerInterval;
}

function stopTimer(){
  clearInterval(_timerInterval);
}

// Visual XP Gains Popup
function animateXP(gained){
  var popup = document.createElement('div');
  popup.className = 'xp-popup';
  popup.innerHTML = '<i class="fa-solid fa-bolt"></i> +' + gained + ' XP!';
  document.body.appendChild(popup);
  setTimeout(function(){ popup.remove(); }, 2000);
}

// Screen Flash on Answers
function flashCorrect(){ flashScreen('#10B981'); }
function flashWrong(){ flashScreen('#EF4444'); }

function flashScreen(color){
  var overlay = document.createElement('div');
  overlay.style.cssText = 'position:fixed;inset:0;background:' + color + ';opacity:0.18;pointer-events:none;z-index:9999;animation:pulse 0.4s ease forwards;';
  document.body.appendChild(overlay);
  setTimeout(function(){ overlay.remove(); }, 400);
}

// Lê o CSRF token do meta tag injetado pelo header.php
function getCsrfToken(){
  var m = document.querySelector('meta[name="csrf-token"]');
  return m ? m.getAttribute('content') : '';
}

// Validação server-side de resposta (gabarito nunca fica no client)
async function checkAnswer(gameId, questionId, chosen, hintUsed){
  var controller = new AbortController();
  var timeout = setTimeout(function(){ controller.abort(); }, 10000);
  try {
    var token = getCsrfToken();
    var res = await fetch(BASE_URL + '/api/check_answer.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token
      },
      signal: controller.signal,
      body: JSON.stringify({
        csrf_token: token,
        game_id: gameId,
        question_id: questionId,
        chosen: chosen,
        hint_used: !!hintUsed
      })
    });
    if(!res.ok) throw new Error('HTTP ' + res.status);
    var data = await res.json();
    return data;
  } catch(e) {
    console.error('Erro na chamada checkAnswer:', e);
    showToast('Erro de conexao ao verificar resposta.', 'error');
    return { success: false, correct: false, points: 0, explanation: '' };
  } finally {
    clearTimeout(timeout);
  }
}

// Async Score Saving API — score vem do servidor, não do cliente
async function saveScore(gameId, timeSpent, difficulty, fallbackScore, fallbackCorrect, fallbackWrong){
  try {
    var token = getCsrfToken();
    var res = await fetch(BASE_URL + '/api/save_score.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token
      },
      body: JSON.stringify({
        csrf_token: token,
        game_id: gameId,
        time_spent: timeSpent,
        difficulty: difficulty,
        score: fallbackScore || 0,
        correct: fallbackCorrect || 0,
        wrong: fallbackWrong || 0
      })
    });
    var data = await res.json();
    if(data && data.success){
      animateXP(data.xp_gained);
      displayPhaseRewards(data);
      showToast('Fase concluída! +' + data.xp_gained + ' XP ganhos!', 'success');
    }
    return data;
  } catch(e) {
    showToast('Erro ao registrar pontuacao no servidor.', 'error');
    return null;
  }
}

// Exibe cartão de recompensas da fase concluída e celebrações
function displayPhaseRewards(data){
  if(!data || !data.success) return;

  var resultArea = document.getElementById('resultArea');
  if(!resultArea) return;

  // Remove qualquer card de recompensa anterior
  var oldCard = resultArea.querySelector('.phase-reward-card');
  if(oldCard) oldCard.remove();

  var card = document.createElement('div');
  card.className = 'phase-reward-card';

  var bonusScoreHtml = '';
  if(data.bonus_score && data.bonus_score > 0){
    bonusScoreHtml = '<div class="phase-reward-item">' +
      '<span class="reward-tag"><i class="fa-solid fa-trophy text-warning"></i> Bônus de Pontuação:</span>' +
      '<span class="reward-val text-warning">+' + data.bonus_score + ' pts</span>' +
    '</div>';
  }

  var starsLabel = (data.stars === 3) ? '3 Estrelas ⭐⭐⭐' : ((data.stars === 2) ? '2 Estrelas ⭐⭐' : '1 Estrela ⭐');

  card.innerHTML = 
    '<div class="phase-reward-header">' +
      '<i class="fa-solid fa-gift"></i>' +
      '<span>Recompensas da Fase Concluída</span>' +
    '</div>' +
    '<div class="phase-reward-body">' +
      '<div class="phase-reward-item">' +
        '<span class="reward-tag"><i class="fa-solid fa-flag-checkered text-info"></i> Conclusão da Fase:</span>' +
        '<span class="reward-val text-info">+' + (data.base_xp || 50) + ' XP</span>' +
      '</div>' +
      '<div class="phase-reward-item">' +
        '<span class="reward-tag"><i class="fa-solid fa-star text-warning"></i> Bônus Desempenho (' + starsLabel + '):</span>' +
        '<span class="reward-val text-warning">+' + (data.bonus_xp || 0) + ' XP</span>' +
      '</div>' +
      bonusScoreHtml +
      '<div class="phase-reward-item phase-reward-total">' +
        '<span>Total de XP Ganho:</span>' +
        '<span class="reward-total-badge">+' + (data.xp_gained || 0) + ' XP</span>' +
      '</div>' +
    '</div>';

  // Insere logo antes das ações finais ou no final das estatísticas
  var actions = resultArea.querySelector('.result-actions');
  if(actions && actions.parentNode){
    actions.parentNode.insertBefore(card, actions);
  } else {
    resultArea.appendChild(card);
  }

  // Se subiu de nível, dispara o modal comemorativo de Level Up
  if(data.level_up){
    setTimeout(function(){
      showLevelUpModal(data.level);
    }, 600);
  }

  // Notifica conquistas desbloqueadas
  if(data.achievements && data.achievements.length > 0){
    data.achievements.forEach(function(ach, i){
      setTimeout(function(){
        showToast('🏆 Conquista Desbloqueada: ' + ach.name + '!', 'success', 6000);
      }, 1200 + (i * 800));
    });
  }
}

// Modal comemorativo de Level Up
function showLevelUpModal(newLevel){
  var existing = document.querySelector('.level-up-overlay');
  if(existing) existing.remove();

  var overlay = document.createElement('div');
  overlay.className = 'level-up-overlay';

  // Gera confetes decorativos coloridos
  var colors = ['#F59E0B', '#10B981', '#3B82F6', '#EF4444', '#EC4899', '#06B6D4'];
  var confettiHtml = '';
  for(var i = 0; i < 24; i++){
    var left = Math.random() * 95;
    var delay = (Math.random() * 1.5).toFixed(2);
    var color = colors[i % colors.length];
    confettiHtml += '<div class="confetti-particle" style="left:' + left + '%; background:' + color + '; animation-delay:' + delay + 's;"></div>';
  }

  overlay.innerHTML = 
    '<div class="level-up-modal">' +
      confettiHtml +
      '<div class="level-up-crown-icon"><i class="fa-solid fa-crown"></i></div>' +
      '<div class="level-up-title">SUBIU DE NÍVEL!</div>' +
      '<div class="level-up-badge">NÍVEL ' + newLevel + '</div>' +
      '<p class="level-up-text">Parabéns pelo seu esforço! Suas habilidades matemáticas subiram de patamar!</p>' +
      '<button class="level-up-btn" onclick="this.closest(\'.level-up-overlay\').remove()">' +
        '<i class="fa-solid fa-bolt"></i> Continuar Explorando' +
      '</button>' +
    '</div>';

  document.body.appendChild(overlay);
}