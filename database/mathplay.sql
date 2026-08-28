-- MathPlay Solutions — Banco de Dados Completo
-- Importe este script no phpMyAdmin: http://localhost/phpmyadmin

CREATE DATABASE IF NOT EXISTS mathplay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mathplay;

-- =============================================================================
-- TABELA: users
-- =============================================================================
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('student','teacher') DEFAULT 'student',
  avatar_color  VARCHAR(7) DEFAULT '#4F46E5',
  level         INT DEFAULT 1,
  xp            INT DEFAULT 0,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================================================
-- TABELA: games
-- =============================================================================
CREATE TABLE IF NOT EXISTS games (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  slug        VARCHAR(50) UNIQUE NOT NULL,
  name        VARCHAR(100) NOT NULL,
  topic       VARCHAR(100),
  description TEXT,
  color_start VARCHAR(7) DEFAULT '#4F46E5',
  color_end   VARCHAR(7) DEFAULT '#7C3AED'
);

-- =============================================================================
-- TABELA: game_sessions
-- =============================================================================
CREATE TABLE IF NOT EXISTS game_sessions (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NOT NULL,
  game_id         INT NOT NULL,
  score           INT DEFAULT 0,
  correct_answers INT DEFAULT 0,
  wrong_answers   INT DEFAULT 0,
  time_spent      INT DEFAULT 0,
  difficulty      VARCHAR(20) DEFAULT 'easy',
  played_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);

-- =============================================================================
-- TABELA: achievements
-- =============================================================================
CREATE TABLE IF NOT EXISTS achievements (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(100) NOT NULL,
  description     VARCHAR(255),
  icon            VARCHAR(50) DEFAULT 'fa-trophy',
  condition_type  VARCHAR(50),
  condition_value INT DEFAULT 0,
  color           VARCHAR(7) DEFAULT '#F59E0B'
);

-- =============================================================================
-- TABELA: user_achievements
-- =============================================================================
CREATE TABLE IF NOT EXISTS user_achievements (
  user_id        INT NOT NULL,
  achievement_id INT NOT NULL,
  unlocked_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, achievement_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
);

-- =============================================================================
-- TABELA: questions
-- =============================================================================
CREATE TABLE IF NOT EXISTS questions (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  game_id         INT,
  topic           VARCHAR(100),
  question_text   TEXT NOT NULL,
  option_a        VARCHAR(255),
  option_b        VARCHAR(255),
  option_c        VARCHAR(255),
  option_d        VARCHAR(255),
  correct_answer  CHAR(1),
  explanation     TEXT,
  difficulty      ENUM('easy','medium','hard') DEFAULT 'easy',
  is_ai_generated TINYINT(1) DEFAULT 0,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE SET NULL
);

-- =============================================================================
-- TABELA: learning_trail
-- =============================================================================
CREATE TABLE IF NOT EXISTS learning_trail (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  topic         VARCHAR(100) NOT NULL,
  progress_pct  INT DEFAULT 0,
  current_level ENUM('easy','medium','hard') DEFAULT 'easy',
  UNIQUE KEY uniq_user_topic (user_id, topic),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================================================
-- TABELA: notifications
-- =============================================================================
CREATE TABLE IF NOT EXISTS notifications (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  type       VARCHAR(50) DEFAULT 'info',
  title      VARCHAR(150),
  message    TEXT,
  is_read    TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================================================
-- SEED DATA: JOGOS
-- =============================================================================
INSERT IGNORE INTO games (slug, name, topic, description, color_start, color_end) VALUES
('fractions', 'Chef das Fracoes', 'Fracoes e Razoes', 'Monte receitas incriveis dividindo ingredientes na proporcao certa! Domine fracoes e razoes.', '#F97316', '#EF4444'),
('geometry',  'Construtor de Cidades', 'Geometria Plana', 'Construa sua propria metropole calculando areas e perimetros geometricos.', '#10B981', '#3B82F6');

-- =============================================================================
-- SEED DATA: CONQUISTAS
-- =============================================================================
INSERT IGNORE INTO achievements (name, description, icon, condition_type, condition_value, color) VALUES
('Primeiro Passo',      'Complete sua primeira partida na plataforma', 'fa-gamepad',  'games_played', 1,   '#F59E0B'),
('Dedicado',            'Jogue 5 partidas no sistema',                'fa-calendar', 'games_played', 5,   '#3B82F6'),
('Cem Pontos!',         'Alcance a marca de 100 XP acumulados',       'fa-bolt',     'xp_total',     100, '#EF4444'),
('Mestre do XP',        'Alcance 500 XP acumulados no perfil',        'fa-award',    'xp_total',     500, '#F97316'),
('Estrela em Ascensao', 'Alcance o nivel 3 de maestria',              'fa-star',     'level',        3,   '#8B5CF6'),
('Lendario',            'Alcance o nivel 5 de maestria',              'fa-crown',    'level',        5,   '#F59E0B');

-- =============================================================================
-- SEED DATA: QUESTOES CHEF DAS FRACOES
-- =============================================================================
INSERT IGNORE INTO questions (game_id, topic, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, difficulty) VALUES
(1,'Fracoes e Razoes','A receita pede 1/2 xicara de farinha. Para fazer o dobro da receita, quanto voce precisa?','1/2 xicara','1 xicara inteira','2 xicaras','1/4 de xicara','B','1/2 vezes 2 = 2/2 = 1 xicara inteira!','easy'),
(1,'Fracoes e Razoes','Uma pizza foi cortada em 8 pedacos iguais. Joao comeu 3 pedacos. Que fracao ele comeu?','1/4 da pizza','3/5 da pizza','3/8 da pizza','5/8 da pizza','C','Joao consumiu 3 de 8 partes = 3/8 da pizza.','easy'),
(1,'Fracoes e Razoes','Qual e a metade exata de 3/4?','3/2','1/4','3/8','6/4','C','Calcular a metade equivale a dividir por 2: (3/4) / 2 = 3/8.','easy'),
(1,'Fracoes e Razoes','Uma receita usa 2/3 de copo de leite. Qual fracao do copo restou?','1/3 de copo','2/3 de copo','1/2 de copo','3/3 de copo','A','O copo inteiro e 3/3. Logo: 3/3 - 2/3 = 1/3.','easy'),
(1,'Fracoes e Razoes','Qual e o resultado da soma de 1/4 + 1/4?','2/8','1/2','1/8','3/4','B','1/4 + 1/4 = 2/4. Simplificando por 2, obtemos 1/2.','easy'),
(1,'Fracoes e Razoes','Uma jarra contem 3/4 de suco. Voce bebe 1/4. Quanto resta na jarra?','1/2 da jarra','1/4 da jarra','3/4 da jarra','2/8 da jarra','A','3/4 - 1/4 = 2/4 = 1/2 da jarra.','medium'),
(1,'Fracoes e Razoes','Se 1/3 de uma receita demanda 6 ovos, quantos ovos serao necessarios para a receita completa?','3 ovos','9 ovos','18 ovos','12 ovos','C','Se 1/3 corresponde a 6 ovos, o total (3/3) e 6 x 3 = 18 ovos.','medium'),
(1,'Fracoes e Razoes','Qual das fracoes abaixo e equivalente a 2/3?','4/9','4/6','3/4','6/4','B','2/3 = 4/6 multiplicando numerador e denominador por 2.','medium');

-- =============================================================================
-- SEED DATA: QUESTOES CONSTRUTOR DE CIDADES
-- =============================================================================
INSERT IGNORE INTO questions (game_id, topic, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, difficulty) VALUES
(2,'Geometria Plana','Um terreno retangular tem 5m de largura e 8m de comprimento. Qual e a area total?','26 m2','40 m2','13 m2','20 m2','B','Area do retangulo = base x altura = 5 x 8 = 40 m2.','easy'),
(2,'Geometria Plana','Uma praca quadrada possui lado de 6m. Qual e o perimetro total?','12 metros','36 metros','24 metros','18 metros','C','Perimetro do quadrado = 4 x lado = 4 x 6 = 24 metros.','easy'),
(2,'Geometria Plana','Para cercar um jardim retangular de 4m por 3m, quantos metros de cerca sao necessarios?','7 metros','12 metros','14 metros','24 metros','C','Perimetro = 2 x (4 + 3) = 2 x 7 = 14 metros.','easy'),
(2,'Geometria Plana','Um apartamento quadrado possui area de 25m2. Qual e a medida de cada lado?','6 metros','5 metros','4 metros','7 metros','B','Area do quadrado = lado ao quadrado. Raiz quadrada de 25 = 5 metros.','easy'),
(2,'Geometria Plana','Uma sala retangular mede 10m por 4m. Quantas placas de piso de 1m2 sao necessarias?','28 placas','14 placas','40 placas','20 placas','C','Area total = 10 x 4 = 40 m2. Logo sao necessarias 40 placas de 1m2.','medium'),
(2,'Geometria Plana','Um parque triangular possui base de 8m e altura de 5m. Qual e a sua area?','40 m2','13 m2','20 m2','26 m2','C','Area do triangulo = (base x altura) / 2 = (8 x 5) / 2 = 20 m2.','medium'),
(2,'Geometria Plana','Uma praca circular tem raio de 3m. Qual e a area aproximada? (Considere pi = 3)','9 m2','18 m2','27 m2','6 m2','C','Area do circulo = pi x raio ao quadrado = 3 x (3^2) = 3 x 9 = 27 m2.','medium'),
(2,'Geometria Plana','Um terreno em L e composto por um retangulo 10x6 do qual retirou-se um recorte 4x3. Qual e a area util?','48 m2','60 m2','72 m2','52 m2','A','Area total inicial: 10 x 6 = 60. Area recortada: 4 x 3 = 12. Area util: 60 - 12 = 48 m2.','hard');