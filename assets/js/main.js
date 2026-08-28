// MathPlay Solutions — main.js

// Preferences initialization (IIFE)
(function applyPrefs(){
  if(localStorage.getItem('darkMode') === '1') document.documentElement.classList.add('dark-mode');
  if(localStorage.getItem('contrast') === '1') document.documentElement.classList.add('high-contrast');
  if(localStorage.getItem('dyslexia') === '1') document.documentElement.classList.add('dyslexia-font');
  var fs = localStorage.getItem('fontSize');
  if(fs) document.documentElement.style.fontSize = fs + 'px';
})();

function toggleDark(){
  document.body.classList.remove('high-contrast');
  localStorage.setItem('contrast', '0');
  var on = document.body.classList.toggle('dark-mode');
  localStorage.setItem('darkMode', on ? '1' : '0');
  var btn = document.getElementById('darkBtn');
  if(btn) btn.classList.toggle('active', on);
}

function toggleContrast(){
  document.body.classList.remove('dark-mode');
  localStorage.setItem('darkMode', '0');
  var on = document.body.classList.toggle('high-contrast');
  localStorage.setItem('contrast', on ? '1' : '0');
  var btn = document.getElementById('contrastBtn');
  if(btn) btn.classList.toggle('active', on);
}

function toggleDyslexia(){
  var on = document.body.classList.toggle('dyslexia-font');
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
  if(localStorage.getItem('darkMode') === '1'){
    var b = document.getElementById('darkBtn');
    if(b) b.classList.add('active');
  }
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

// Async Score Saving API
async function saveScore(gameId, score, correct, wrong, timeSpent, difficulty){
  try {
    var res = await fetch('/vortex/api/save_score.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        game_id: gameId,
        score: score,
        correct: correct,
        wrong: wrong,
        time_spent: timeSpent,
        difficulty: difficulty
      })
    });
    var data = await res.json();
    if(data.success){
      animateXP(data.xp_gained);
      showToast('Pontuacao e XP computados com sucesso!', 'success');
    }
    return data;
  } catch(e) {
    showToast('Erro ao registrar pontuacao no servidor.', 'error');
    return null;
  }
}