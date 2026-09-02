-- =============================================================================
-- PROJETO: MathPlay Solutions - Plataforma Educacional Gamificada
-- ARQUIVO: database/mathplay.sql
-- BANCO DE DADOS: MySQL / MariaDB (XAMPP / phpMyAdmin)
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `mathplay` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `mathplay`;

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. TABELA: users (Alunos e Professores)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'teacher') NOT NULL DEFAULT 'student',
  `avatar_color` VARCHAR(7) DEFAULT '#4F46E5',
  `level` INT NOT NULL DEFAULT 1,
  `xp` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. TABELA: games (Catalogo de Jogos)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `games` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `topic` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `color_start` VARCHAR(7) DEFAULT '#4F46E5',
  `color_end` VARCHAR(7) DEFAULT '#7C3AED'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. TABELA: game_sessions (Historico de Partidas e Desempenho)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `game_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `game_id` INT NOT NULL,
  `score` INT NOT NULL DEFAULT 0,
  `correct_answers` INT NOT NULL DEFAULT 0,
  `wrong_answers` INT NOT NULL DEFAULT 0,
  `time_spent` INT NOT NULL DEFAULT 0,
  `difficulty` VARCHAR(20) DEFAULT 'easy',
  `played_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_session_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. TABELA: achievements (Conquistas / Medalhas do Sistema)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `achievements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'fa-trophy',
  `condition_type` VARCHAR(50) NOT NULL,
  `condition_value` INT NOT NULL DEFAULT 0,
  `color` VARCHAR(7) DEFAULT '#F59E0B'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. TABELA: user_achievements (Conquistas Desbloqueadas pelos Usuarios)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_achievements` (
  `user_id` INT NOT NULL,
  `achievement_id` INT NOT NULL,
  `unlocked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `achievement_id`),
  CONSTRAINT `fk_uach_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uach_ach` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. TABELA: questions (Banco de Questoes Matematicas)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `game_id` INT,
  `topic` VARCHAR(100) NOT NULL,
  `question_text` TEXT NOT NULL,
  `option_a` VARCHAR(255) NOT NULL,
  `option_b` VARCHAR(255) NOT NULL,
  `option_c` VARCHAR(255) NOT NULL,
  `option_d` VARCHAR(255) NOT NULL,
  `correct_answer` CHAR(1) NOT NULL,
  `explanation` TEXT,
  `difficulty` ENUM('easy', 'medium', 'hard') DEFAULT 'easy',
  `is_ai_generated` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_question_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. TABELA: learning_trail (Trilha e Progresso por Topico)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `learning_trail` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `topic` VARCHAR(100) NOT NULL,
  `progress_pct` INT NOT NULL DEFAULT 0,
  `current_level` ENUM('easy', 'medium', 'hard') DEFAULT 'easy',
  UNIQUE KEY `uniq_user_topic` (`user_id`, `topic`),
  CONSTRAINT `fk_trail_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. TABELA: notifications (Alertas Pedagogicos e Notificacoes)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `type` VARCHAR(50) DEFAULT 'info',
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- DADOS INICIAIS (SEEDS)
-- =============================================================================

-- Usuarios Padrao para Testes Imediatos (Senha para ambos: senha123)
INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `avatar_color`, `level`, `xp`) VALUES
(1, 'Professor Carlos Silva', 'professor@mathplay.com', '$2y$10$Cx.x1woy4ECbzqwYBJVCU.o3HujHRE5m0SzGpLmG7Feytzo6ZIWhq', 'teacher', '#059669', 5, 520),
(2, 'Lucas Santos', 'aluno@mathplay.com', '$2y$10$Cx.x1woy4ECbzqwYBJVCU.o3HujHRE5m0SzGpLmG7Feytzo6ZIWhq', 'student', '#4F46E5', 2, 180),
(3, 'Mariana Oliveira', 'mariana@mathplay.com', '$2y$10$Cx.x1woy4ECbzqwYBJVCU.o3HujHRE5m0SzGpLmG7Feytzo6ZIWhq', 'student', '#DB2777', 3, 290);

-- Jogos Disponiveis
INSERT IGNORE INTO `games` (`id`, `slug`, `name`, `topic`, `description`, `color_start`, `color_end`) VALUES
(1, 'fractions', 'Chef das Fracoes', 'Fracoes e Razoes', 'Monte receitas incriveis dividindo ingredientes na proporcao certa! Domine fracoes enquanto cozinha pratos deliciosos.', '#F97316', '#EF4444'),
(2, 'geometry', 'Construtor de Cidades', 'Geometria Plana', 'Construa sua propria metropole calculando areas e perimetros! Cada edificio precisa do espaco certo para ser erguido.', '#10B981', '#3B82F6');

-- Conquistas do Sistema
INSERT IGNORE INTO `achievements` (`id`, `name`, `description`, `icon`, `condition_type`, `condition_value`, `color`) VALUES
(1, 'Primeiro Passo', 'Complete sua primeira partida na plataforma', 'fa-gamepad', 'games_played', 1, '#F59E0B'),
(2, 'Dedicado', 'Jogue 5 partidas no sistema', 'fa-calendar', 'games_played', 5, '#3B82F6'),
(3, 'Cem Pontos!', 'Alcance a marca de 100 XP acumulados', 'fa-bolt', 'xp_total', 100, '#EF4444'),
(4, 'Mestre do XP', 'Alcance 500 XP acumulados no perfil', 'fa-award', 'xp_total', 500, '#F97316'),
(5, 'Estrela em Ascensao', 'Alcance o nivel 3 de maestria', 'fa-star', 'level', 3, '#8B5CF6'),
(6, 'Lendario', 'Alcance o nivel 5 de maestria', 'fa-crown', 'level', 5, '#F59E0B');

-- Conquistas Desbloqueadas para os Alunos de Demonstracao
INSERT IGNORE INTO `user_achievements` (`user_id`, `achievement_id`) VALUES
(2, 1),
(2, 3),
(3, 1),
(3, 3),
(3, 5);

-- Questoes: Chef das Fracoes (Jogo 1)
INSERT IGNORE INTO `questions` (`id`, `game_id`, `topic`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `explanation`, `difficulty`) VALUES
(1, 1, 'Fracoes e Razoes', 'A receita pede 1/2 xicara de farinha. Para fazer o dobro da receita, quanto voce precisa?', '1/2 xicara', '1 xicara inteira', '2 xicaras', '1/4 de xicara', 'B', '1/2 multiplicado por 2 = 2/2 = 1 xicara inteira!', 'easy'),
(2, 1, 'Fracoes e Razoes', 'Uma pizza foi cortada em 8 pedacos iguais. Joao comeu 3 pedacos. Que fracao da pizza ele comeu?', '1/4 da pizza', '3/5 da pizza', '3/8 da pizza', '5/8 da pizza', 'C', 'Joao consumiu 3 de um total de 8 partes, o que representa 3/8 da pizza.', 'easy'),
(3, 1, 'Fracoes e Razoes', 'Qual e a metade exata da fracao 3/4?', '3/2', '1/4', '3/8', '6/4', 'C', 'Calcular a metade equivale a dividir por 2: (3/4) / 2 = 3/8.', 'easy'),
(4, 1, 'Fracoes e Razoes', 'Uma receita usa 2/3 de copo de leite. Qual fracao do copo restou?', '1/3 de copo', '2/3 de copo', '1/2 de copo', '3/3 de copo', 'A', 'O copo inteiro representa 3/3. Subtraindo a parte usada: 3/3 - 2/3 = 1/3.', 'easy'),
(5, 1, 'Fracoes e Razoes', 'Qual e o resultado da soma de 1/4 + 1/4?', '2/8', '1/2', '1/8', '3/4', 'B', '1/4 + 1/4 = 2/4. Simplificando a fracao por 2, obtemos 1/2.', 'easy'),
(6, 1, 'Fracoes e Razoes', 'Uma jarra contem 3/4 de suco. Voce bebe 1/4. Quanto resta na jarra?', '1/2 da jarra', '1/4 da jarra', '3/4 da jarra', '2/8 da jarra', 'A', '3/4 - 1/4 = 2/4 = 1/2 da jarra de suco.', 'medium'),
(7, 1, 'Fracoes e Razoes', 'Se 1/3 de uma receita demanda 6 ovos, quantos ovos serao necessarios para a receita completa?', '3 ovos', '9 ovos', '18 ovos', '12 ovos', 'C', 'Se 1/3 corresponde a 6 ovos, o total (3/3) e obtido por 6 x 3 = 18 ovos.', 'medium'),
(8, 1, 'Fracoes e Razoes', 'Qual das fracoes abaixo e equivalente a 2/3?', '4/9', '4/6', '3/4', '6/4', 'B', '2/3 = 4/6 multiplicando numerador e denominador pelo fator 2.', 'medium');

-- Questoes: Construtor de Cidades (Jogo 2)
INSERT IGNORE INTO `questions` (`id`, `game_id`, `topic`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `explanation`, `difficulty`) VALUES
(9,  2, 'Geometria Plana', 'Um terreno retangular tem 5m de largura e 8m de comprimento. Qual e a sua area total?', '26 m2', '40 m2', '13 m2', '20 m2', 'B', 'A area do retangulo e calculada por base x altura: 5 x 8 = 40 m2.', 'easy'),
(10, 2, 'Geometria Plana', 'Uma praca quadrada possui lado de 6m. Qual e o seu perimetro total?', '12 metros', '36 metros', '24 metros', '18 metros', 'C', 'O perimetro do quadrado e dado por 4 x lado: 4 x 6 = 24 metros.', 'easy'),
(11, 2, 'Geometria Plana', 'Para cercar um jardim retangular de 4m por 3m, quantos metros de cerca sao necessarios?', '7 metros', '12 metros', '14 metros', '24 metros', 'C', 'Perimetro = 2 x (4 + 3) = 2 x 7 = 14 metros de cerca.', 'easy'),
(12, 2, 'Geometria Plana', 'Um apartamento quadrado possui area de 25m2. Qual e a medida de cada lado?', '6 metros', '5 metros', '4 metros', '7 metros', 'B', 'A area do quadrado e lado ao quadrado (L^2). Raiz quadrada de 25 = 5 metros.', 'easy'),
(13, 2, 'Geometria Plana', 'Uma sala retangular mede 10m por 4m. Quantas placas de piso de 1m2 sao necessarias para cobrir o chao?', '28 placas', '14 placas', '40 placas', '20 placas', 'C', 'Area total da sala = 10 x 4 = 40 m2. Logo, sao necessarias 40 placas de 1m2.', 'medium'),
(14, 2, 'Geometria Plana', 'Um parque triangular possui base de 8m e altura de 5m. Qual e a sua area?', '40 m2', '13 m2', '20 m2', '26 m2', 'C', 'Area do triangulo = (base x altura) / 2 = (8 x 5) / 2 = 40 / 2 = 20 m2.', 'medium'),
(15, 2, 'Geometria Plana', 'Uma praca circular tem raio de 3m. Qual e a area aproximada? (Considere pi = 3)', '9 m2', '18 m2', '27 m2', '6 m2', 'C', 'Area do circulo = pi x r^2 = 3 x (3^2) = 3 x 9 = 27 m2.', 'medium'),
(16, 2, 'Geometria Plana', 'Um terreno em L e composto por um retangulo 10x6 do qual retirou-se um recorte 4x3. Qual e a area util restante?', '48 m2', '60 m2', '72 m2', '52 m2', 'A', 'Area total inicial: 10 x 6 = 60. Area recortada: 4 x 3 = 12. Area util restante: 60 - 12 = 48 m2.', 'hard');

-- Sessoes de Demonstracao
INSERT IGNORE INTO `game_sessions` (`user_id`, `game_id`, `score`, `correct_answers`, `wrong_answers`, `time_spent`, `difficulty`) VALUES
(2, 1, 350, 7, 1, 140, 'easy'),
(2, 2, 280, 6, 2, 180, 'easy'),
(3, 1, 400, 8, 0, 110, 'easy'),
(3, 2, 380, 8, 0, 125, 'easy');

-- Trilhas de Aprendizado Iniciais
INSERT IGNORE INTO `learning_trail` (`user_id`, `topic`, `progress_pct`, `current_level`) VALUES
(2, 'Fracoes e Razoes', 88, 'easy'),
(2, 'Geometria Plana', 75, 'easy'),
(3, 'Fracoes e Razoes', 100, 'medium'),
(3, 'Geometria Plana', 100, 'medium');

-- Notificacoes de Demonstracao
INSERT IGNORE INTO `notifications` (`user_id`, `type`, `title`, `message`, `is_read`) VALUES
(2, 'achievement', 'Conquista Desbloqueada!', 'Voce desbloqueou: Primeiro Passo - Complete sua primeira partida na plataforma', 1),
(2, 'achievement', 'Conquista Desbloqueada!', 'Voce desbloqueou: Cem Pontos! - Alcance a marca de 100 XP acumulados', 1),
(1, 'alert', 'Alerta Pedagogico', 'Turma do 7o ano com bom desempenho inicial em Fracoes e Razoes.', 0);
