<?php
$pageTitle = 'Inicio';
require_once __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<section class="hero" aria-label="Secao principal">
  <div class="hero-bg" aria-hidden="true"></div>
  <div class="hero-shapes" aria-hidden="true">
    <div class="hero-shape"></div>
    <div class="hero-shape"></div>
    <div class="hero-shape"></div>
  </div>
  <div class="hero-content container">
    <div class="hero-text" data-aos="fade-right">
      <span class="hero-badge"><i class="fa-solid fa-bolt"></i> Plataforma Gamificada</span>
      <h1 class="hero-title">Aprenda Matematica<br><span>jogando de verdade!</span></h1>
      <p class="hero-subtitle">A MathPlay transforma Matematica em aventura epica. Ganhe XP, desbloqueie medalhas e evolua seu personagem dominando conteudos do Ensino Fundamental II.</p>
      <div class="hero-actions">
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="/vortex/dashboard.php" class="btn btn-primary btn-lg"><i class="fa-solid fa-rocket"></i> Meu Dashboard</a>
          <a href="/vortex/games/index.php" class="btn btn-secondary btn-lg"><i class="fa-solid fa-gamepad"></i> Jogar Agora</a>
        <?php else: ?>
          <a href="/vortex/register.php" class="btn btn-primary btn-lg"><i class="fa-solid fa-rocket"></i> Comecar Gratis</a>
          <a href="/vortex/login.php" class="btn btn-secondary btn-lg"><i class="fa-solid fa-right-to-bracket"></i> Ja tenho conta</a>
        <?php endif; ?>
      </div>
      <div class="hero-stats">
        <div class="hero-stat">
          <div class="hero-stat-icon"><i class="fa-solid fa-gamepad"></i></div>
          <div class="hero-stat-num">2</div>
          <div class="hero-stat-label">Jogos Interativos</div>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-icon"><i class="fa-solid fa-trophy"></i></div>
          <div class="hero-stat-num">6+</div>
          <div class="hero-stat-label">Conquistas</div>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-icon"><i class="fa-solid fa-brain"></i></div>
          <div class="hero-stat-num">IA</div>
          <div class="hero-stat-label">Claude AI</div>
        </div>
      </div>
    </div>
    <div class="hero-img-wrap" data-aos="fade-left" aria-hidden="true">
      <img src="https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=1000&q=85&auto=format&fit=crop" alt="Estudantes aprendendo" class="hero-img" loading="eager">
      <div class="hero-float-card card-1">
        <div class="hero-float-icon-wrap hero-float-gold">
          <i class="fa-solid fa-trophy"></i>
        </div>
        <div>
          <div class="hero-float-text">Conquista Desbloqueada!</div>
          <div class="hero-float-sub">Mestre das Fracoes</div>
        </div>
      </div>
      <div class="hero-float-card card-2">
        <div class="hero-float-icon-wrap hero-float-purple">
          <i class="fa-solid fa-star"></i>
        </div>
        <div>
          <div class="hero-float-text">+150 XP ganhos</div>
          <div class="hero-float-sub">Nivel 3 alcancado!</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="section" aria-labelledby="how-title">
  <div class="container">
    <div class="section-header" data-aos="fade-up">
      <span class="section-tag">Como Funciona</span>
      <h2 class="section-title" id="how-title">Do zero a maestria em 3 passos</h2>
      <p class="section-subtitle">Uma jornada de aprendizado projetada para manter voce motivado e evoluindo sempre.</p>
    </div>
    <div class="steps-grid">
      <div class="step-card" data-aos="fade-up" data-aos-delay="0">
        <div class="step-img-wrap">
          <img src="https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?w=600&q=80&auto=format&fit=crop" alt="Cadastro" class="step-img" loading="lazy">
          <div class="step-number-badge">1</div>
        </div>
        <div class="step-body">
          <h3 class="step-title">Cadastre-se Gratis</h3>
          <p class="step-desc">Crie sua conta em segundos. Escolha ser Aluno ou Professor e personalize seu perfil de jogador.</p>
        </div>
      </div>
      <div class="step-card" data-aos="fade-up" data-aos-delay="100">
        <div class="step-img-wrap">
          <img src="https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=600&q=80&auto=format&fit=crop" alt="Escolha um jogo" class="step-img" loading="lazy">
          <div class="step-number-badge">2</div>
        </div>
        <div class="step-body">
          <h3 class="step-title">Escolha um Jogo</h3>
          <p class="step-desc">Selecione Chef das Fracoes ou Construtor de Cidades e mergulhe no aprendizado interativo.</p>
        </div>
      </div>
      <div class="step-card" data-aos="fade-up" data-aos-delay="200">
        <div class="step-img-wrap">
          <img src="https://images.unsplash.com/photo-1567427017947-545c5f8d16ad?w=600&q=80&auto=format&fit=crop" alt="Conquiste e evolua" class="step-img" loading="lazy">
          <div class="step-number-badge">3</div>
        </div>
        <div class="step-body">
          <h3 class="step-title">Evolua e Conquiste</h3>
          <p class="step-desc">Ganhe XP, suba de nivel, desbloqueie medalhas e receba questoes personalizadas pela IA Claude.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- GAMES SHOWCASE -->
<section class="section section-alt" aria-labelledby="games-title">
  <div class="container">
    <div class="section-header" data-aos="fade-up">
      <span class="section-tag">Nossos Jogos</span>
      <h2 class="section-title" id="games-title">Aprenda jogando com aventuras incriveis</h2>
      <p class="section-subtitle">Dois jogos criados para tornar Matematica divertida, desafiadora e educativa.</p>
    </div>
    <div class="game-grid">
      <article class="game-card game-card-fractions" tabindex="0" role="button" aria-label="Jogar Chef das Fracoes"
        onclick="window.location='/vortex/games/fractions.php'"
        onkeypress="if(event.key==='Enter')window.location='/vortex/games/fractions.php'"
        data-aos="fade-right">
        <img src="https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800&q=85&auto=format&fit=crop" alt="Cozinha com ingredientes" class="game-card-img" loading="lazy">
        <div class="game-card-gradient"></div>
        <div class="game-card-body">
          <div class="game-card-icon-wrap">
            <i class="fa-solid fa-utensils text-danger font-heavy"></i>
          </div>
          <h3 class="game-card-title">Chef das Fracoes</h3>
          <p class="game-card-desc">Monte receitas incriveis dividindo ingredientes na proporcao certa! Aprenda fracoes enquanto cozinha pratos deliciosos.</p>
          <a href="/vortex/games/fractions.php" class="btn game-btn-play">
            <i class="fa-solid fa-play"></i> Jogar Agora
          </a>
        </div>
        <div class="game-card-footer">
          <span class="game-difficulty">
            <i class="fa-solid fa-signal text-success"></i> Facil a Dificil
          </span>
          <span><i class="fa-solid fa-lightbulb"></i> Dicas Progressivas</span>
        </div>
      </article>

      <article class="game-card game-card-geometry" tabindex="0" role="button" aria-label="Jogar Construtor de Cidades"
        onclick="window.location='/vortex/games/geometry.php'"
        onkeypress="if(event.key==='Enter')window.location='/vortex/games/geometry.php'"
        data-aos="fade-left">
        <img src="https://images.unsplash.com/photo-1486325212027-8081e485255e?w=800&q=85&auto=format&fit=crop" alt="Cidade moderna" class="game-card-img" loading="lazy">
        <div class="game-card-gradient"></div>
        <div class="game-card-body">
          <div class="game-card-icon-wrap">
            <i class="fa-solid fa-city text-blue font-heavy"></i>
          </div>
          <h3 class="game-card-title">Construtor de Cidades</h3>
          <p class="game-card-desc">Construa sua propria metropole calculando areas e perimetros! Cada edificio exige calculos precisos para ser erguido.</p>
          <a href="/vortex/games/geometry.php" class="btn game-btn-play">
            <i class="fa-solid fa-play"></i> Jogar Agora
          </a>
        </div>
        <div class="game-card-footer">
          <span class="game-difficulty">
            <i class="fa-solid fa-signal text-success"></i> Facil a Dificil
          </span>
          <span><i class="fa-solid fa-building"></i> Construcao Visual</span>
        </div>
      </article>
    </div>
  </div>
</section>

<!-- FEATURES / GAMIFICATION -->
<section class="section" aria-labelledby="gamif-title">
  <div class="container">
    <div class="section-header" data-aos="fade-up">
      <span class="section-tag">Gamificacao</span>
      <h2 class="section-title" id="gamif-title">Cada acerto te leva mais longe</h2>
      <p class="section-subtitle">Mecanicas de jogo poderosas para manter a motivacao sempre alta.</p>
    </div>
    <div class="features-grid">
      <div class="feature-card" data-aos="fade-up" data-aos-delay="0">
        <div class="feature-img-wrap">
          <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=600&q=80&auto=format&fit=crop" alt="XP e Niveis" class="feature-img" loading="lazy">
          <div class="feature-icon-overlay"><i class="fa-solid fa-bolt"></i></div>
        </div>
        <div class="feature-body">
          <h3 class="feature-title">XP e Niveis</h3>
          <p class="feature-desc">Ganhe pontos de experiencia a cada acerto e suba de nivel, desbloqueando recompensas unicas.</p>
        </div>
      </div>
      <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
        <div class="feature-img-wrap">
          <img src="https://images.unsplash.com/photo-1567427017947-545c5f8d16ad?w=600&q=80&auto=format&fit=crop" alt="Medalhas e Trofeus" class="feature-img" loading="lazy">
          <div class="feature-icon-overlay"><i class="fa-solid fa-trophy"></i></div>
        </div>
        <div class="feature-body">
          <h3 class="feature-title">Medalhas</h3>
          <p class="feature-desc">Conquistas especiais por metas: primeiros jogos, sequencias de acertos e muito mais.</p>
        </div>
      </div>
      <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
        <div class="feature-img-wrap">
          <img src="https://images.unsplash.com/photo-1677442136019-21780ecad995?w=600&q=80&auto=format&fit=crop" alt="Inteligencia Artificial" class="feature-img" loading="lazy">
          <div class="feature-icon-overlay"><i class="fa-solid fa-brain"></i></div>
        </div>
        <div class="feature-body">
          <h3 class="feature-title">IA Adaptativa</h3>
          <p class="feature-desc">O Claude AI analisa seus erros e gera questoes personalizadas para reforcar seus pontos fracos.</p>
        </div>
      </div>
      <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
        <div class="feature-img-wrap">
          <img src="https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=600&q=80&auto=format&fit=crop" alt="Dicas Progressivas" class="feature-img" loading="lazy">
          <div class="feature-icon-overlay"><i class="fa-solid fa-lightbulb"></i></div>
        </div>
        <div class="feature-body">
          <h3 class="feature-title">Dicas Progressivas</h3>
          <p class="feature-desc">Ficou travado? Peca uma dica sem receber a resposta direta. Aprenda no seu ritmo.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ACCESSIBILITY -->
<section class="section section-alt" aria-labelledby="a11y-title">
  <div class="container">
    <div class="section-header" data-aos="fade-up">
      <span class="section-tag">Acessibilidade</span>
      <h2 class="section-title" id="a11y-title">Feito para todos os alunos</h2>
      <p class="section-subtitle">Recursos reais de acessibilidade para que nenhum aluno fique de fora.</p>
    </div>
    <div class="a11y-features">
      <div class="a11y-feature" data-aos="zoom-in" data-aos-delay="0">
        <div class="a11y-feature-icon-wrap"><i class="fa-solid fa-moon"></i></div>
        <div class="a11y-feature-name">Modo Escuro</div>
        <div class="a11y-feature-desc">Reduz fadiga visual</div>
      </div>
      <div class="a11y-feature" data-aos="zoom-in" data-aos-delay="80">
        <div class="a11y-feature-icon-wrap"><i class="fa-solid fa-circle-half-stroke"></i></div>
        <div class="a11y-feature-name">Alto Contraste</div>
        <div class="a11y-feature-desc">Conforme WCAG AA</div>
      </div>
      <div class="a11y-feature" data-aos="zoom-in" data-aos-delay="160">
        <div class="a11y-feature-icon-wrap font-heavy">Aa</div>
        <div class="a11y-feature-name">Fonte Dislexia</div>
        <div class="a11y-feature-desc">Tipografia Lexend</div>
      </div>
      <div class="a11y-feature" data-aos="zoom-in" data-aos-delay="240">
        <div class="a11y-feature-icon-wrap"><i class="fa-solid fa-volume-high"></i></div>
        <div class="a11y-feature-name">Leitura em Voz</div>
        <div class="a11y-feature-desc">Text-to-Speech nativo</div>
      </div>
      <div class="a11y-feature" data-aos="zoom-in" data-aos-delay="320">
        <div class="a11y-feature-icon-wrap"><i class="fa-solid fa-keyboard"></i></div>
        <div class="a11y-feature-name">Teclado Total</div>
        <div class="a11y-feature-desc">100% navegavel via teclado</div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section" aria-labelledby="cta-title">
  <div class="cta-bg-img" aria-hidden="true"></div>
  <div class="container cta-content" data-aos="zoom-in">
    <h2 class="cta-title" id="cta-title">Pronto para sua aventura matematica?</h2>
    <p class="cta-subtitle">Transforme a forma como voce aprende Matematica — gratuito, intuitivo e gamificado!</p>
    <?php if (!isset($_SESSION['user_id'])): ?>
      <a href="/vortex/register.php" class="btn btn-lg cta-btn">
        <i class="fa-solid fa-rocket"></i> Criar Conta Gratis
      </a>
    <?php else: ?>
      <a href="/vortex/games/index.php" class="btn btn-lg cta-btn">
        <i class="fa-solid fa-gamepad"></i> Jogar Agora
      </a>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
