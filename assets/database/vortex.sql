-- MathPlay Solutions / Vortex - Exporta��o de Banco de Dados
-- Gerado para InfinityFree (banco: if0_43234203_vortex)
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET NAMES utf8mb4;

-- --------------------------------------------------------
-- Estrutura da tabela `classes`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `classes`;
CREATE TABLE `classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_students` int NOT NULL DEFAULT '30',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `classes`
INSERT INTO `classes` (`id`, `name`, `max_students`, `created_at`) VALUES
('1', '7 A', '30', '2026-10-30 10:49:59'),
('2', '7 B', '30', '2026-10-30 10:49:59');

-- --------------------------------------------------------
-- Estrutura da tabela `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('student','teacher') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student',
  `profile_photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` int NOT NULL DEFAULT '1',
  `xp` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `avatar_color` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#4F46E5',
  `class_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_users_class` (`class_id`),
  CONSTRAINT `fk_users_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `users`
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `profile_photo`, `level`, `xp`, `created_at`, `avatar_color`, `class_id`) VALUES
('1', 'Professor Carlos Silva', 'professor@mathplay.com', '$2y$10$R7yXgWjvivBA1Zrk9YfTbOfBs9EWpijEoOdnHcx5xIs750C9a3TQK', 'teacher', NULL, '7', '696', '2026-09-02 16:33:25', '#4F46E5', NULL),
('9', 'jacinto', 'jacinto@gmail.com', '$2y$10$6mBMoabCV/LyttDsS3vkguoTrcEwmmw1X6xOrxcjjOPn6I4eEOkMy', 'student', NULL, '1', '0', '2026-10-21 13:58:56', '#DC2626', NULL);

-- --------------------------------------------------------
-- Estrutura da tabela `achievements`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `achievements`;
CREATE TABLE `achievements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'fa-trophy',
  `color` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#4F46E5',
  `condition_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `condition_value` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `achievements`
INSERT INTO `achievements` (`id`, `name`, `description`, `icon`, `color`, `condition_type`, `condition_value`) VALUES
('1', 'Primeiro Passo', 'Complete sua primeira partida na plataforma', 'fa-gamepad', '#4F46E5', 'games_played', '1'),
('2', 'Dedicado', 'Jogue 5 partidas no sistema', 'fa-calendar', '#4F46E5', 'games_played', '5'),
('3', 'Cem Pontos!', 'Alcance a marca de 100 XP acumulados', 'fa-bolt', '#F59E0B', 'xp_total', '100'),
('4', 'Mestre do XP', 'Alcance 500 XP acumulados no perfil', 'fa-award', '#F59E0B', 'xp_total', '500'),
('5', 'Estrela em Ascensao', 'Alcance o nivel 3 de maestria', 'fa-star', '#10B981', 'level', '3'),
('6', 'Lendario', 'Alcance o nivel 5 de maestria', 'fa-crown', '#10B981', 'level', '5');

-- --------------------------------------------------------
-- Estrutura da tabela `games`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `games`;
CREATE TABLE `games` (
  `id` int NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `topic` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `games`
INSERT INTO `games` (`id`, `slug`, `name`, `topic`, `description`) VALUES
('1', 'fractions', 'Chef das Fracoes', 'Fracoes e Razoes', 'Monte receitas incriveis dividindo ingredientes na proporcao certa! Domine fracoes enquanto cozinha pratos deliciosos.'),
('2', 'geometry', 'Construtor de Cidades', 'Geometria Plana', 'Construa sua propria metropole calculando areas e perimetros! Cada edificio precisa do espaco certo para ser erguido.'),
('3', 'mental-math', 'Calculadora Mental', 'Operacoes Basicas', 'Resolva calculos das quatro operacoes contra o relogio e aumente seu combo a cada acerto.');

-- --------------------------------------------------------
-- Estrutura da tabela `questions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `questions`;
CREATE TABLE `questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `game_id` int DEFAULT NULL,
  `topic` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_a` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_b` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_c` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_d` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `correct_answer` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `explanation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `difficulty` enum('easy','medium','hard') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'easy',
  `is_ai_generated` tinyint(1) DEFAULT '0',
  `target_user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `recovery_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_question_game` (`game_id`),
  KEY `fk_questions_target_user` (`target_user_id`),
  KEY `fk_questions_recovery` (`recovery_id`),
  CONSTRAINT `fk_question_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_questions_recovery` FOREIGN KEY (`recovery_id`) REFERENCES `recoveries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_questions_target_user` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=257 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `questions`
INSERT INTO `questions` (`id`, `game_id`, `topic`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `explanation`, `difficulty`, `is_ai_generated`, `target_user_id`, `created_at`, `recovery_id`) VALUES
('1', '1', 'Fracoes e Razoes', 'A receita pede 1/2 xicara de farinha. Para fazer o dobro da receita, quanto voce precisa?', '1/2 xicara', '1 xicara inteira', '2 xicaras', '1/4 de xicara', 'B', '1/2 multiplicado por 2 = 2/2 = 1 xicara inteira!', 'easy', '0', NULL, '2026-09-02 16:33:46', NULL),
('2', '1', 'Fracoes e Razoes', 'Uma pizza foi cortada em 8 pedacos iguais. Joao comeu 3 pedacos. Que fracao da pizza ele comeu?', '1/4 da pizza', '3/5 da pizza', '3/8 da pizza', '5/8 da pizza', 'C', 'Joao consumiu 3 de um total de 8 partes, o que representa 3/8 da pizza.', 'easy', '0', NULL, '2026-09-02 16:33:46', NULL),
('3', '1', 'Fracoes e Razoes', 'Qual e a metade exata da fracao 3/4?', '3/2', '1/4', '3/8', '6/4', 'C', 'Calcular a metade equivale a dividir por 2: (3/4) / 2 = 3/8.', 'easy', '0', NULL, '2026-09-02 16:33:46', NULL),
('4', '1', 'Fracoes e Razoes', 'Uma receita usa 2/3 de copo de leite. Qual fracao do copo restou?', '1/3 de copo', '2/3 de copo', '1/2 de copo', '3/3 de copo', 'A', 'O copo inteiro representa 3/3. Subtraindo a parte usada: 3/3 - 2/3 = 1/3.', 'easy', '0', NULL, '2026-09-02 16:33:46', NULL),
('5', '1', 'Fracoes e Razoes', 'Qual e o resultado da soma de 1/4 + 1/4?', '2/8', '1/2', '1/8', '3/4', 'B', '1/4 + 1/4 = 2/4. Simplificando a fracao por 2, obtemos 1/2.', 'easy', '0', NULL, '2026-09-02 16:33:46', NULL),
('6', '1', 'Fracoes e Razoes', 'Uma jarra contem 3/4 de suco. Voce bebe 1/4. Quanto resta na jarra?', '1/2 da jarra', '1/4 da jarra', '3/4 da jarra', '2/8 da jarra', 'A', '3/4 - 1/4 = 2/4 = 1/2 da jarra de suco.', 'medium', '0', NULL, '2026-09-02 16:33:46', NULL),
('7', '1', 'Fracoes e Razoes', 'Se 1/3 de uma receita demanda 6 ovos, quantos ovos serao necessarios para a receita completa?', '3 ovos', '9 ovos', '18 ovos', '12 ovos', 'C', 'Se 1/3 corresponde a 6 ovos, o total (3/3) e obtido por 6 x 3 = 18 ovos.', 'medium', '0', NULL, '2026-09-02 16:33:46', NULL),
('8', '1', 'Fracoes e Razoes', 'Qual das fracoes abaixo e equivalente a 2/3?', '4/9', '4/6', '3/4', '6/4', 'B', '2/3 = 4/6 multiplicando numerador e denominador pelo fator 2.', 'medium', '0', NULL, '2026-09-02 16:33:46', NULL),
('9', '2', 'Geometria Plana', 'Um terreno retangular tem 5m de largura e 8m de comprimento. Qual e a sua area total?', '26 m2', '40 m2', '13 m2', '20 m2', 'B', 'A area do retangulo e calculada por base x altura: 5 x 8 = 40 m2.', 'easy', '0', NULL, '2026-09-02 16:33:50', NULL),
('10', '2', 'Geometria Plana', 'Uma praca quadrada possui lado de 6m. Qual e o seu perimetro total?', '12 metros', '36 metros', '24 metros', '18 metros', 'C', 'O perimetro do quadrado e dado por 4 x lado: 4 x 6 = 24 metros.', 'easy', '0', NULL, '2026-09-02 16:33:50', NULL),
('11', '2', 'Geometria Plana', 'Para cercar um jardim retangular de 4m por 3m, quantos metros de cerca sao necessarios?', '7 metros', '12 metros', '14 metros', '24 metros', 'C', 'Perimetro = 2 x (4 + 3) = 2 x 7 = 14 metros de cerca.', 'easy', '0', NULL, '2026-09-02 16:33:50', NULL),
('12', '2', 'Geometria Plana', 'Um apartamento quadrado possui area de 25m2. Qual e a medida de cada lado?', '6 metros', '5 metros', '4 metros', '7 metros', 'B', 'A area do quadrado e lado ao quadrado (L^2). Raiz quadrada de 25 = 5 metros.', 'easy', '0', NULL, '2026-09-02 16:33:50', NULL),
('13', '2', 'Geometria Plana', 'Uma sala retangular mede 10m por 4m. Quantas placas de piso de 1m2 sao necessarias para cobrir o chao?', '28 placas', '14 placas', '40 placas', '20 placas', 'C', 'Area total da sala = 10 x 4 = 40 m2. Logo, sao necessarias 40 placas de 1m2.', 'medium', '0', NULL, '2026-09-02 16:33:50', NULL),
('14', '2', 'Geometria Plana', 'Um parque triangular possui base de 8m e altura de 5m. Qual e a sua area?', '40 m2', '13 m2', '20 m2', '26 m2', 'C', 'Area do triangulo = (base x altura) / 2 = (8 x 5) / 2 = 40 / 2 = 20 m2.', 'medium', '0', NULL, '2026-09-02 16:33:50', NULL),
('15', '2', 'Geometria Plana', 'Uma praca circular tem raio de 3m. Qual e a area aproximada? (Considere pi = 3)', '9 m2', '18 m2', '27 m2', '6 m2', 'C', 'Area do circulo = pi x r^2 = 3 x (3^2) = 3 x 9 = 27 m2.', 'medium', '0', NULL, '2026-09-02 16:33:50', NULL),
('16', '2', 'Geometria Plana', 'Um terreno em L e composto por um retangulo 10x6 do qual retirou-se um recorte 4x3. Qual e a area util restante?', '48 m2', '60 m2', '72 m2', '52 m2', 'A', 'Area total inicial: 10 x 6 = 60. Area recortada: 4 x 3 = 12. Area util restante: 60 - 12 = 48 m2.', 'hard', '0', NULL, '2026-09-02 16:33:50', NULL),
('17', '3', 'Operacoes Basicas', '10 ÷ 2', '', '', '', '', '5', 'Resolva a operacao da esquerda para a direita: 10 ÷ 2 = 5.', 'easy', '0', NULL, '2026-09-02 16:38:41', NULL),
('18', '3', 'Operacoes Basicas', '12 ÷ 2', '', '', '', '', '6', 'Resolva a operacao da esquerda para a direita: 12 ÷ 2 = 6.', 'easy', '0', NULL, '2026-09-02 16:38:41', NULL),
('19', '3', 'Operacoes Basicas', '8 × 8', '', '', '', '', '64', 'Resolva a operacao da esquerda para a direita: 8 × 8 = 64.', 'easy', '0', NULL, '2026-09-02 16:38:41', NULL),
('20', '3', 'Operacoes Basicas', '12 - 10', '', '', '', '', '2', 'Resolva a operacao da esquerda para a direita: 12 - 10 = 2.', 'easy', '0', NULL, '2026-09-02 16:38:41', NULL),
('21', '3', 'Operacoes Basicas', '19 + 6', '', '', '', '', '25', 'Resolva a operacao da esquerda para a direita: 19 + 6 = 25.', 'easy', '0', NULL, '2026-09-02 16:38:41', NULL),
('22', '3', 'Operacoes Basicas', '18 - 9', '', '', '', '', '9', 'Resolva a operacao da esquerda para a direita: 18 - 9 = 9.', 'easy', '0', NULL, '2026-09-02 16:38:41', NULL),
('23', '3', 'Operacoes Basicas', '18 ÷ 2', '', '', '', '', '9', 'Resolva a operacao da esquerda para a direita: 18 ÷ 2 = 9.', 'easy', '0', NULL, '2026-09-02 16:38:41', NULL),
('24', '3', 'Operacoes Basicas', '23 - 12', '', '', '', '', '11', 'Resolva a operacao da esquerda para a direita: 23 - 12 = 11.', 'easy', '0', NULL, '2026-09-02 16:38:41', NULL),
('25', '3', 'Operacoes Basicas', '28 + 3', '', '', '', '', '31', 'Resolva a operacao da esquerda para a direita: 28 + 3 = 31.', 'easy', '0', NULL, '2026-09-02 16:38:41', NULL),
('26', '3', 'Operacoes Basicas', '23 + 8', '', '', '', '', '31', 'Resolva a operacao da esquerda para a direita: 23 + 8 = 31.', 'easy', '0', NULL, '2026-09-02 16:38:41', NULL),
('27', '3', 'Operacoes Basicas', '22 + 10', '', '', '', '', '32', 'Resolva a operacao da esquerda para a direita: 22 + 10 = 32.', 'easy', '0', NULL, '2026-09-02 16:38:48', NULL),
('28', '3', 'Operacoes Basicas', '21 - 12', '', '', '', '', '9', 'Resolva a operacao da esquerda para a direita: 21 - 12 = 9.', 'easy', '0', NULL, '2026-09-02 16:38:50', NULL),
('29', '3', 'Operacoes Basicas', '23 + 5', '', '', '', '', '28', 'Resolva a operacao da esquerda para a direita: 23 + 5 = 28.', 'easy', '0', NULL, '2026-09-02 16:38:53', NULL),
('30', '3', 'Operacoes Basicas', '15 + 2', '', '', '', '', '17', 'Resolva a operacao da esquerda para a direita: 15 + 2 = 17.', 'easy', '0', NULL, '2026-09-02 16:38:55', NULL),
('31', '3', 'Operacoes Basicas', '14 × 6', '', '', '', '', '84', 'Resolva a operacao da esquerda para a direita: 14 × 6 = 84.', 'easy', '0', NULL, '2026-09-02 16:38:58', NULL),
('32', '3', 'Operacoes Basicas', '24 - 9', '', '', '', '', '15', 'Resolva a operacao da esquerda para a direita: 24 - 9 = 15.', 'easy', '0', NULL, '2026-09-02 16:39:01', NULL),
('33', '3', 'Operacoes Basicas', '13 - 12', '', '', '', '', '1', 'Resolva a operacao da esquerda para a direita: 13 - 12 = 1.', 'easy', '0', NULL, '2026-09-02 16:39:03', NULL),
('34', '3', 'Operacoes Basicas', '12 - 12', '', '', '', '', '0', 'Resolva a operacao da esquerda para a direita: 12 - 12 = 0.', 'easy', '0', NULL, '2026-09-02 16:39:08', NULL),
('35', '3', 'Operacoes Basicas', '24 ÷ 3', '', '', '', '', '8', 'Resolva a operacao da esquerda para a direita: 24 ÷ 3 = 8.', 'easy', '0', NULL, '2026-09-02 16:39:11', NULL),
('36', '3', 'Operacoes Basicas', '72 ÷ 9', '', '', '', '', '8', 'Resolva a operacao da esquerda para a direita: 72 ÷ 9 = 8.', 'easy', '0', NULL, '2026-09-04 08:30:16', NULL),
('37', '3', 'Operacoes Basicas', '21 ÷ 7', '', '', '', '', '3', 'Resolva a operacao da esquerda para a direita: 21 ÷ 7 = 3.', 'easy', '0', NULL, '2026-09-04 08:30:16', NULL),
('38', '3', 'Operacoes Basicas', '11 - 8', '', '', '', '', '3', 'Resolva a operacao da esquerda para a direita: 11 - 8 = 3.', 'easy', '0', NULL, '2026-09-04 08:30:16', NULL),
('39', '3', 'Operacoes Basicas', '22 - 12', '', '', '', '', '10', 'Resolva a operacao da esquerda para a direita: 22 - 12 = 10.', 'easy', '0', NULL, '2026-09-04 08:30:16', NULL),
('40', '3', 'Operacoes Basicas', '22 × 11', '', '', '', '', '242', 'Resolva a operacao da esquerda para a direita: 22 × 11 = 242.', 'easy', '0', NULL, '2026-09-04 08:30:16', NULL),
('41', '3', 'Operacoes Basicas', '12 - 5', '', '', '', '', '7', 'Resolva a operacao da esquerda para a direita: 12 - 5 = 7.', 'easy', '0', NULL, '2026-09-04 08:30:16', NULL),
('42', '3', 'Operacoes Basicas', '17 + 3', '', '', '', '', '20', 'Resolva a operacao da esquerda para a direita: 17 + 3 = 20.', 'easy', '0', NULL, '2026-09-04 08:30:16', NULL),
('43', '3', 'Operacoes Basicas', '60 ÷ 12', '', '', '', '', '5', 'Resolva a operacao da esquerda para a direita: 60 ÷ 12 = 5.', 'easy', '0', NULL, '2026-09-04 08:30:16', NULL),
('44', '3', 'Operacoes Basicas', '26 × 9', '', '', '', '', '234', 'Resolva a operacao da esquerda para a direita: 26 × 9 = 234.', 'easy', '0', NULL, '2026-09-04 08:30:16', NULL),
('45', '3', 'Operacoes Basicas', '25 × 6', '', '', '', '', '150', 'Resolva a operacao da esquerda para a direita: 25 × 6 = 150.', 'easy', '0', NULL, '2026-09-04 08:30:16', NULL),
('46', '3', 'Operacoes Basicas', '4 × 9', '', '', '', '', '36', 'Resolva a operacao da esquerda para a direita: 4 × 9 = 36.', 'easy', '0', NULL, '2026-09-04 08:31:34', NULL),
('47', '3', 'Operacoes Basicas', '2 + 10', '', '', '', '', '12', 'Resolva a operacao da esquerda para a direita: 2 + 10 = 12.', 'easy', '0', NULL, '2026-09-04 08:31:34', NULL),
('48', '3', 'Operacoes Basicas', '11 + 3', '', '', '', '', '14', 'Resolva a operacao da esquerda para a direita: 11 + 3 = 14.', 'easy', '0', NULL, '2026-09-04 08:31:34', NULL),
('49', '3', 'Operacoes Basicas', '19 × 4', '', '', '', '', '76', 'Resolva a operacao da esquerda para a direita: 19 × 4 = 76.', 'easy', '0', NULL, '2026-09-04 08:31:34', NULL),
('50', '3', 'Operacoes Basicas', '9 + 4', '', '', '', '', '13', 'Resolva a operacao da esquerda para a direita: 9 + 4 = 13.', 'easy', '0', NULL, '2026-09-04 08:31:34', NULL),
('51', '3', 'Operacoes Basicas', '21 - 2', '', '', '', '', '19', 'Resolva a operacao da esquerda para a direita: 21 - 2 = 19.', 'easy', '0', NULL, '2026-09-04 08:31:34', NULL),
('52', '3', 'Operacoes Basicas', '17 - 9', '', '', '', '', '8', 'Resolva a operacao da esquerda para a direita: 17 - 9 = 8.', 'easy', '0', NULL, '2026-09-04 08:31:34', NULL),
('53', '3', 'Operacoes Basicas', '21 + 4', '', '', '', '', '25', 'Resolva a operacao da esquerda para a direita: 21 + 4 = 25.', 'easy', '0', NULL, '2026-09-04 08:31:34', NULL),
('54', '3', 'Operacoes Basicas', '15 + 6', '', '', '', '', '21', 'Resolva a operacao da esquerda para a direita: 15 + 6 = 21.', 'easy', '0', NULL, '2026-09-04 08:31:34', NULL),
('55', '3', 'Operacoes Basicas', '63 ÷ 7', '', '', '', '', '9', 'Resolva a operacao da esquerda para a direita: 63 ÷ 7 = 9.', 'easy', '0', NULL, '2026-09-04 08:31:34', NULL),
('56', '3', 'Operacoes Basicas', '3 × 3', '', '', '', '', '9', 'Resolva a operacao da esquerda para a direita: 3 × 3 = 9.', 'easy', '0', NULL, '2026-09-04 08:31:58', NULL),
('57', '3', 'Operacoes Basicas', '19 + 2', '', '', '', '', '21', 'Resolva a operacao da esquerda para a direita: 19 + 2 = 21.', 'easy', '0', NULL, '2026-09-04 08:31:58', NULL),
('58', '3', 'Operacoes Basicas', '4 × 9', '', '', '', '', '36', 'Resolva a operacao da esquerda para a direita: 4 × 9 = 36.', 'easy', '0', NULL, '2026-09-04 08:31:58', NULL),
('59', '3', 'Operacoes Basicas', '33 ÷ 11', '', '', '', '', '3', 'Resolva a operacao da esquerda para a direita: 33 ÷ 11 = 3.', 'easy', '0', NULL, '2026-09-04 08:31:58', NULL),
('60', '3', 'Operacoes Basicas', '25 + 10', '', '', '', '', '35', 'Resolva a operacao da esquerda para a direita: 25 + 10 = 35.', 'easy', '0', NULL, '2026-09-04 08:31:58', NULL),
('61', '3', 'Operacoes Basicas', '18 + 11', '', '', '', '', '29', 'Resolva a operacao da esquerda para a direita: 18 + 11 = 29.', 'easy', '0', NULL, '2026-09-04 08:31:58', NULL),
('62', '3', 'Operacoes Basicas', '23 × 10', '', '', '', '', '230', 'Resolva a operacao da esquerda para a direita: 23 × 10 = 230.', 'easy', '0', NULL, '2026-09-04 08:31:58', NULL),
('63', '3', 'Operacoes Basicas', '10 - 10', '', '', '', '', '0', 'Resolva a operacao da esquerda para a direita: 10 - 10 = 0.', 'easy', '0', NULL, '2026-09-04 08:31:58', NULL),
('64', '3', 'Operacoes Basicas', '3 × 5', '', '', '', '', '15', 'Resolva a operacao da esquerda para a direita: 3 × 5 = 15.', 'easy', '0', NULL, '2026-09-04 08:31:58', NULL),
('65', '3', 'Operacoes Basicas', '66 ÷ 11', '', '', '', '', '6', 'Resolva a operacao da esquerda para a direita: 66 ÷ 11 = 6.', 'easy', '0', NULL, '2026-09-04 08:31:58', NULL),
('66', '3', 'Operacoes Basicas', '6 ÷ 3', '', '', '', '', '2', 'Nível Fácil: 6 ÷ 3 = 2.', 'easy', '0', NULL, '2026-09-04 08:39:16', NULL),
('67', '3', 'Operacoes Basicas', '24 ÷ 3', '', '', '', '', '8', 'Nível Fácil: 24 ÷ 3 = 8.', 'easy', '0', NULL, '2026-09-04 08:39:16', NULL),
('68', '3', 'Operacoes Basicas', '4 ÷ 2', '', '', '', '', '2', 'Nível Fácil: 4 ÷ 2 = 2.', 'easy', '0', NULL, '2026-09-04 08:39:16', NULL),
('69', '3', 'Operacoes Basicas', '12 + 4', '', '', '', '', '16', 'Nível Fácil: 12 + 4 = 16.', 'easy', '0', NULL, '2026-09-04 08:39:16', NULL),
('70', '3', 'Operacoes Basicas', '13 - 3', '', '', '', '', '10', 'Nível Fácil: 13 - 3 = 10.', 'easy', '0', NULL, '2026-09-04 08:39:16', NULL),
('71', '3', 'Operacoes Basicas', '14 - 9', '', '', '', '', '5', 'Nível Fácil: 14 - 9 = 5.', 'easy', '0', NULL, '2026-09-04 08:39:16', NULL),
('72', '3', 'Operacoes Basicas', '8 ÷ 2', '', '', '', '', '4', 'Nível Fácil: 8 ÷ 2 = 4.', 'easy', '0', NULL, '2026-09-04 08:39:16', NULL),
('73', '3', 'Operacoes Basicas', '11 + 9', '', '', '', '', '20', 'Nível Fácil: 11 + 9 = 20.', 'easy', '0', NULL, '2026-09-04 08:39:16', NULL),
('74', '3', 'Operacoes Basicas', '10 + 10', '', '', '', '', '20', 'Nível Fácil: 10 + 10 = 20.', 'easy', '0', NULL, '2026-09-04 08:39:16', NULL),
('75', '3', 'Operacoes Basicas', '19 - 8', '', '', '', '', '11', 'Nível Fácil: 19 - 8 = 11.', 'easy', '0', NULL, '2026-09-04 08:39:17', NULL),
('76', '3', 'Operacoes Basicas', '20 - 10', '', '', '', '', '10', 'Nível Fácil: 20 - 10 = 10.', 'easy', '0', NULL, '2026-09-04 08:39:17', NULL),
('77', '3', 'Operacoes Basicas', '11 + 3', '', '', '', '', '14', 'Nível Fácil: 11 + 3 = 14.', 'easy', '0', NULL, '2026-09-04 08:39:17', NULL),
('78', '3', 'Operacoes Basicas', '12 - 2', '', '', '', '', '10', 'Nível Fácil: 12 - 2 = 10.', 'easy', '0', NULL, '2026-09-04 08:39:56', NULL),
('79', '3', 'Operacoes Basicas', '12 + 2', '', '', '', '', '14', 'Nível Fácil: 12 + 2 = 14.', 'easy', '0', NULL, '2026-09-04 08:39:56', NULL),
('80', '3', 'Operacoes Basicas', '11 - 6', '', '', '', '', '5', 'Nível Fácil: 11 - 6 = 5.', 'easy', '0', NULL, '2026-09-04 08:39:56', NULL),
('81', '3', 'Operacoes Basicas', '72 ÷ 8', '', '', '', '', '9', 'Nível Médio: 72 ÷ 8 = 9.', 'medium', '0', NULL, '2026-09-04 08:39:56', NULL),
('82', '3', 'Operacoes Basicas', '81 ÷ 9', '', '', '', '', '9', 'Nível Médio: 81 ÷ 9 = 9.', 'medium', '0', NULL, '2026-09-04 08:39:56', NULL),
('83', '3', 'Operacoes Basicas', '54 ÷ 6', '', '', '', '', '9', 'Nível Médio: 54 ÷ 6 = 9.', 'medium', '0', NULL, '2026-09-04 08:39:56', NULL),
('84', '3', 'Operacoes Basicas', '13 × 3', '', '', '', '', '39', 'Nível Difícil: 13 × 3 = 39.', 'hard', '0', NULL, '2026-09-04 08:39:56', NULL),
('85', '3', 'Operacoes Basicas', '124 - 46', '', '', '', '', '78', 'Nível Difícil: 124 - 46 = 78.', 'hard', '0', NULL, '2026-09-04 08:39:56', NULL),
('86', '3', 'Operacoes Basicas', '154 - 75', '', '', '', '', '79', 'Nível Difícil: 154 - 75 = 79.', 'hard', '0', NULL, '2026-09-04 08:39:56', NULL),
('87', '3', 'Operacoes Basicas', '200 - 85', '', '', '', '', '115', 'Desafio Mestre: 200 - 85 = 115.', 'hard', '0', NULL, '2026-09-04 08:39:56', NULL),
('88', '3', 'Operacoes Basicas', '16 × 5', '', '', '', '', '80', 'Desafio Mestre: 16 × 5 = 80.', 'hard', '0', NULL, '2026-09-04 08:39:56', NULL),
('89', '3', 'Operacoes Basicas', '99 + 88', '', '', '', '', '187', 'Desafio Mestre: 99 + 88 = 187.', 'hard', '0', NULL, '2026-09-04 08:39:56', NULL),
('90', '3', 'Operacoes Basicas', '4 + 9', '', '', '', '', '13', 'Nível Fácil: 4 + 9 = 13.', 'easy', '0', NULL, '2026-09-04 08:40:33', NULL),
('91', '3', 'Operacoes Basicas', '7 + 6', '', '', '', '', '13', 'Nível Fácil: 7 + 6 = 13.', 'easy', '0', NULL, '2026-09-04 08:40:33', NULL),
('92', '3', 'Operacoes Basicas', '4 × 8', '', '', '', '', '32', 'Nível Fácil: 4 × 8 = 32.', 'easy', '0', NULL, '2026-09-04 08:40:33', NULL),
('93', '3', 'Operacoes Basicas', '73 - 39', '', '', '', '', '34', 'Nível Médio: 73 - 39 = 34.', 'medium', '0', NULL, '2026-09-04 08:40:33', NULL),
('94', '3', 'Operacoes Basicas', '61 - 40', '', '', '', '', '21', 'Nível Médio: 61 - 40 = 21.', 'medium', '0', NULL, '2026-09-04 08:40:33', NULL),
('95', '3', 'Operacoes Basicas', '37 - 20', '', '', '', '', '17', 'Nível Médio: 37 - 20 = 17.', 'medium', '0', NULL, '2026-09-04 08:40:33', NULL),
('96', '3', 'Operacoes Basicas', '80 ÷ 5', '', '', '', '', '16', 'Nível Difícil: 80 ÷ 5 = 16.', 'hard', '0', NULL, '2026-09-04 08:40:33', NULL),
('97', '3', 'Operacoes Basicas', '90 - 36', '', '', '', '', '54', 'Nível Difícil: 90 - 36 = 54.', 'hard', '0', NULL, '2026-09-04 08:40:33', NULL),
('98', '3', 'Operacoes Basicas', '93 - 42', '', '', '', '', '51', 'Nível Difícil: 93 - 42 = 51.', 'hard', '0', NULL, '2026-09-04 08:40:33', NULL),
('99', '3', 'Operacoes Basicas', '16 × 5', '', '', '', '', '80', 'Desafio Mestre: 16 × 5 = 80.', 'hard', '0', NULL, '2026-09-04 08:40:33', NULL),
('100', '3', 'Operacoes Basicas', '11 - 7', '', '', '', '', '4', 'Nível Fácil: 11 - 7 = 4.', 'easy', '0', NULL, '2026-09-04 08:40:36', NULL),
('101', '3', 'Operacoes Basicas', '3 × 6', '', '', '', '', '18', 'Nível Fácil: 3 × 6 = 18.', 'easy', '0', NULL, '2026-09-04 08:40:36', NULL),
('102', '3', 'Operacoes Basicas', '12 - 6', '', '', '', '', '6', 'Nível Fácil: 12 - 6 = 6.', 'easy', '0', NULL, '2026-09-04 08:40:36', NULL),
('103', '3', 'Operacoes Basicas', '6 × 7', '', '', '', '', '42', 'Nível Médio: 6 × 7 = 42.', 'medium', '0', NULL, '2026-09-04 08:40:36', NULL),
('104', '3', 'Operacoes Basicas', '9 × 7', '', '', '', '', '63', 'Nível Médio: 9 × 7 = 63.', 'medium', '0', NULL, '2026-09-04 08:40:36', NULL),
('105', '3', 'Operacoes Basicas', '25 + 15', '', '', '', '', '40', 'Nível Médio: 25 + 15 = 40.', 'medium', '0', NULL, '2026-09-04 08:40:36', NULL),
('106', '3', 'Operacoes Basicas', '108 - 45', '', '', '', '', '63', 'Nível Difícil: 108 - 45 = 63.', 'hard', '0', NULL, '2026-09-04 08:40:36', NULL),
('107', '3', 'Operacoes Basicas', '80 ÷ 5', '', '', '', '', '16', 'Nível Difícil: 80 ÷ 5 = 16.', 'hard', '0', NULL, '2026-09-04 08:40:36', NULL),
('108', '3', 'Operacoes Basicas', '123 - 60', '', '', '', '', '63', 'Nível Difícil: 123 - 60 = 63.', 'hard', '0', NULL, '2026-09-04 08:40:36', NULL),
('109', '3', 'Operacoes Basicas', '144 ÷ 12', '', '', '', '', '12', 'Desafio Mestre: 144 ÷ 12 = 12.', 'hard', '0', NULL, '2026-09-04 08:40:36', NULL),
('110', '3', 'Operacoes Basicas', '11 + 3', '', '', '', '', '14', 'Nível Fácil: 11 + 3 = 14.', 'easy', '0', NULL, '2026-09-04 08:40:46', NULL),
('111', '3', 'Operacoes Basicas', '18 ÷ 3', '', '', '', '', '6', 'Nível Fácil: 18 ÷ 3 = 6.', 'easy', '0', NULL, '2026-09-04 08:40:46', NULL),
('112', '3', 'Operacoes Basicas', '8 + 5', '', '', '', '', '13', 'Nível Fácil: 8 + 5 = 13.', 'easy', '0', NULL, '2026-09-04 08:40:46', NULL),
('113', '3', 'Operacoes Basicas', '33 + 30', '', '', '', '', '63', 'Nível Médio: 33 + 30 = 63.', 'medium', '0', NULL, '2026-09-04 08:40:46', NULL),
('114', '3', 'Operacoes Basicas', '7 × 4', '', '', '', '', '28', 'Nível Médio: 7 × 4 = 28.', 'medium', '0', NULL, '2026-09-04 08:40:46', NULL),
('115', '3', 'Operacoes Basicas', '28 + 13', '', '', '', '', '41', 'Nível Médio: 28 + 13 = 41.', 'medium', '0', NULL, '2026-09-04 08:40:46', NULL),
('116', '3', 'Operacoes Basicas', '116 - 71', '', '', '', '', '45', 'Nível Difícil: 116 - 71 = 45.', 'hard', '0', NULL, '2026-09-04 08:40:46', NULL),
('117', '3', 'Operacoes Basicas', '96 ÷ 3', '', '', '', '', '32', 'Nível Difícil: 96 ÷ 3 = 32.', 'hard', '0', NULL, '2026-09-04 08:40:46', NULL),
('118', '3', 'Operacoes Basicas', '84 ÷ 4', '', '', '', '', '21', 'Nível Difícil: 84 ÷ 4 = 21.', 'hard', '0', NULL, '2026-09-04 08:40:46', NULL),
('119', '3', 'Operacoes Basicas', '99 + 88', '', '', '', '', '187', 'Desafio Mestre: 99 + 88 = 187.', 'hard', '0', NULL, '2026-09-04 08:40:46', NULL),
('120', '3', 'Operacoes Basicas', '5 + 6', '', '', '', '', '11', 'Nível Fácil: 5 + 6 = 11.', 'easy', '0', NULL, '2026-09-04 08:40:50', NULL),
('121', '3', 'Operacoes Basicas', '14 - 4', '', '', '', '', '10', 'Nível Fácil: 14 - 4 = 10.', 'easy', '0', NULL, '2026-09-04 08:40:50', NULL),
('122', '3', 'Operacoes Basicas', '12 + 8', '', '', '', '', '20', 'Nível Fácil: 12 + 8 = 20.', 'easy', '0', NULL, '2026-09-04 08:40:50', NULL),
('123', '3', 'Operacoes Basicas', '20 + 32', '', '', '', '', '52', 'Nível Médio: 20 + 32 = 52.', 'medium', '0', NULL, '2026-09-04 08:40:50', NULL),
('124', '3', 'Operacoes Basicas', '64 - 34', '', '', '', '', '30', 'Nível Médio: 64 - 34 = 30.', 'medium', '0', NULL, '2026-09-04 08:40:50', NULL),
('125', '3', 'Operacoes Basicas', '6 × 7', '', '', '', '', '42', 'Nível Médio: 6 × 7 = 42.', 'medium', '0', NULL, '2026-09-04 08:40:50', NULL),
('126', '3', 'Operacoes Basicas', '69 + 47', '', '', '', '', '116', 'Nível Difícil: 69 + 47 = 116.', 'hard', '0', NULL, '2026-09-04 08:40:50', NULL),
('127', '3', 'Operacoes Basicas', '112 - 61', '', '', '', '', '51', 'Nível Difícil: 112 - 61 = 51.', 'hard', '0', NULL, '2026-09-04 08:40:50', NULL),
('128', '3', 'Operacoes Basicas', '78 + 71', '', '', '', '', '149', 'Nível Difícil: 78 + 71 = 149.', 'hard', '0', NULL, '2026-09-04 08:40:50', NULL),
('129', '3', 'Operacoes Basicas', '50 × 4', '', '', '', '', '200', 'Desafio Mestre: 50 × 4 = 200.', 'hard', '0', NULL, '2026-09-04 08:40:50', NULL),
('130', '3', 'Operacoes Basicas', '3 × 4', '', '', '', '', '12', 'Nível Fácil: 3 × 4 = 12.', 'easy', '0', NULL, '2026-09-04 08:41:17', NULL),
('131', '3', 'Operacoes Basicas', '12 - 2', '', '', '', '', '10', 'Nível Fácil: 12 - 2 = 10.', 'easy', '0', NULL, '2026-09-04 08:41:17', NULL),
('132', '3', 'Operacoes Basicas', '6 ÷ 3', '', '', '', '', '2', 'Nível Fácil: 6 ÷ 3 = 2.', 'easy', '0', NULL, '2026-09-04 08:41:17', NULL),
('133', '3', 'Operacoes Basicas', '27 + 14', '', '', '', '', '41', 'Nível Médio: 27 + 14 = 41.', 'medium', '0', NULL, '2026-09-04 08:41:17', NULL),
('134', '3', 'Operacoes Basicas', '32 ÷ 8', '', '', '', '', '4', 'Nível Médio: 32 ÷ 8 = 4.', 'medium', '0', NULL, '2026-09-04 08:41:17', NULL),
('135', '3', 'Operacoes Basicas', '45 + 15', '', '', '', '', '60', 'Nível Médio: 45 + 15 = 60.', 'medium', '0', NULL, '2026-09-04 08:41:17', NULL),
('136', '3', 'Operacoes Basicas', '96 ÷ 3', '', '', '', '', '32', 'Nível Difícil: 96 ÷ 3 = 32.', 'hard', '0', NULL, '2026-09-04 08:41:17', NULL),
('137', '3', 'Operacoes Basicas', '12 × 6', '', '', '', '', '72', 'Nível Difícil: 12 × 6 = 72.', 'hard', '0', NULL, '2026-09-04 08:41:17', NULL),
('138', '3', 'Operacoes Basicas', '73 + 37', '', '', '', '', '110', 'Nível Difícil: 73 + 37 = 110.', 'hard', '0', NULL, '2026-09-04 08:41:17', NULL),
('139', '3', 'Operacoes Basicas', '25 × 4', '', '', '', '', '100', 'Desafio Mestre: 25 × 4 = 100.', 'hard', '0', NULL, '2026-09-04 08:41:17', NULL),
('140', '3', 'Operacoes Basicas', '12 - 2', '', '', '', '', '10', 'Nível Fácil: 12 - 2 = 10.', 'easy', '0', NULL, '2026-09-04 08:48:09', NULL),
('141', '3', 'Operacoes Basicas', '13 - 2', '', '', '', '', '11', 'Nível Fácil: 13 - 2 = 11.', 'easy', '0', NULL, '2026-09-04 08:48:09', NULL),
('142', '3', 'Operacoes Basicas', '4 + 12', '', '', '', '', '16', 'Nível Fácil: 4 + 12 = 16.', 'easy', '0', NULL, '2026-09-04 08:48:09', NULL),
('143', '3', 'Operacoes Basicas', '63 ÷ 9', '', '', '', '', '7', 'Nível Médio: 63 ÷ 9 = 7.', 'medium', '0', NULL, '2026-09-04 08:48:09', NULL),
('144', '3', 'Operacoes Basicas', '22 + 22', '', '', '', '', '44', 'Nível Médio: 22 + 22 = 44.', 'medium', '0', NULL, '2026-09-04 08:48:09', NULL),
('145', '3', 'Operacoes Basicas', '8 × 9', '', '', '', '', '72', 'Nível Médio: 8 × 9 = 72.', 'medium', '0', NULL, '2026-09-04 08:48:09', NULL),
('146', '3', 'Operacoes Basicas', '113 - 47', '', '', '', '', '66', 'Nível Difícil: 113 - 47 = 66.', 'hard', '0', NULL, '2026-09-04 08:48:09', NULL),
('147', '3', 'Operacoes Basicas', '74 + 42', '', '', '', '', '116', 'Nível Difícil: 74 + 42 = 116.', 'hard', '0', NULL, '2026-09-04 08:48:09', NULL),
('148', '3', 'Operacoes Basicas', '148 - 75', '', '', '', '', '73', 'Nível Difícil: 148 - 75 = 73.', 'hard', '0', NULL, '2026-09-04 08:48:09', NULL),
('149', '3', 'Operacoes Basicas', '249 - 95', '', '', '', '', '154', 'Nível Especialista: 249 - 95 = 154.', 'hard', '0', NULL, '2026-09-04 08:48:09', NULL),
('150', '3', 'Operacoes Basicas', '266 - 124', '', '', '', '', '142', 'Nível Especialista: 266 - 124 = 142.', 'hard', '0', NULL, '2026-09-04 08:48:09', NULL),
('151', '3', 'Operacoes Basicas', '168 + 119', '', '', '', '', '287', 'Nível Especialista: 168 + 119 = 287.', 'hard', '0', NULL, '2026-09-04 08:48:09', NULL),
('152', '3', 'Operacoes Basicas', '14 + 8', '', '', '', '', '22', 'Nível Fácil: 14 + 8 = 22.', 'easy', '0', NULL, '2026-09-04 08:48:39', NULL),
('153', '3', 'Operacoes Basicas', '4 ÷ 2', '', '', '', '', '2', 'Nível Fácil: 4 ÷ 2 = 2.', 'easy', '0', NULL, '2026-09-04 08:48:39', NULL),
('154', '3', 'Operacoes Basicas', '69 - 35', '', '', '', '', '34', 'Nível Médio: 69 - 35 = 34.', 'medium', '0', NULL, '2026-09-04 08:48:39', NULL),
('155', '3', 'Operacoes Basicas', '37 + 35', '', '', '', '', '72', 'Nível Médio: 37 + 35 = 72.', 'medium', '0', NULL, '2026-09-04 08:48:39', NULL),
('156', '3', 'Operacoes Basicas', '12 × 4', '', '', '', '', '48', 'Nível Difícil: 12 × 4 = 48.', 'hard', '0', NULL, '2026-09-04 08:48:39', NULL),
('157', '3', 'Operacoes Basicas', '98 - 51', '', '', '', '', '47', 'Nível Difícil: 98 - 51 = 47.', 'hard', '0', NULL, '2026-09-04 08:48:39', NULL),
('158', '3', 'Operacoes Basicas', '211 - 133', '', '', '', '', '78', 'Nível Especialista: 211 - 133 = 78.', 'hard', '0', NULL, '2026-09-04 08:48:39', NULL),
('159', '3', 'Operacoes Basicas', '95 + 58', '', '', '', '', '153', 'Nível Especialista: 95 + 58 = 153.', 'hard', '0', NULL, '2026-09-04 08:48:39', NULL),
('160', '3', 'Operacoes Basicas', '11 × 15', '', '', '', '', '165', 'Nível Mestre: 11 × 15 = 165.', 'hard', '0', NULL, '2026-09-04 08:48:39', NULL),
('161', '3', 'Operacoes Basicas', '506 - 212', '', '', '', '', '294', 'Nível Mestre: 506 - 212 = 294.', 'hard', '0', NULL, '2026-09-04 08:48:39', NULL),
('162', '3', 'Operacoes Basicas', '15 × 15', '', '', '', '', '225', 'Nível Lenda: 15 × 15 = 225.', 'hard', '0', NULL, '2026-09-04 08:48:39', NULL),
('163', '3', 'Operacoes Basicas', '327 + 452', '', '', '', '', '779', 'Nível Lenda: 327 + 452 = 779.', 'hard', '0', NULL, '2026-09-04 08:48:39', NULL),
('164', '3', 'Operacoes Basicas', '50 × 8', '', '', '', '', '400', 'Nível Lenda: 50 × 8 = 400.', 'hard', '0', NULL, '2026-09-04 08:48:39', NULL),
('165', '3', 'Operacoes Basicas', '125 × 2', '', '', '', '', '250', 'Nível Lenda: 125 × 2 = 250.', 'hard', '0', NULL, '2026-09-04 08:48:39', NULL),
('166', '3', 'Operacoes Basicas', '80 × 5', '', '', '', '', '400', 'Nível Lenda: 80 × 5 = 400.', 'hard', '0', NULL, '2026-09-04 08:48:39', NULL),
('167', '3', 'Operacoes Basicas', '800 ÷ 20', '', '', '', '', '40', 'Nível Lenda: 800 ÷ 20 = 40.', 'hard', '0', NULL, '2026-09-04 08:48:39', NULL),
('168', '3', 'Operacoes Basicas', '22 - 10', '', '', '', '', '12', 'Nível Fácil: 22 - 10 = 12.', 'easy', '0', NULL, '2026-09-04 08:49:29', NULL),
('169', '3', 'Operacoes Basicas', '10 - 4', '', '', '', '', '6', 'Nível Fácil: 10 - 4 = 6.', 'easy', '0', NULL, '2026-09-04 08:49:29', NULL),
('170', '3', 'Operacoes Basicas', '4 × 6', '', '', '', '', '24', 'Nível Fácil: 4 × 6 = 24.', 'easy', '0', NULL, '2026-09-04 08:49:29', NULL),
('171', '3', 'Operacoes Basicas', '63 - 34', '', '', '', '', '29', 'Nível Médio: 63 - 34 = 29.', 'medium', '0', NULL, '2026-09-04 08:50:10', NULL),
('172', '3', 'Operacoes Basicas', '8 × 9', '', '', '', '', '72', 'Nível Médio: 8 × 9 = 72.', 'medium', '0', NULL, '2026-09-04 08:50:11', NULL),
('173', '3', 'Operacoes Basicas', '41 + 12', '', '', '', '', '53', 'Nível Médio: 41 + 12 = 53.', 'medium', '0', NULL, '2026-09-04 08:50:14', NULL),
('174', '3', 'Operacoes Basicas', '102 - 61', '', '', '', '', '41', 'Nível Difícil: 102 - 61 = 41.', 'hard', '0', NULL, '2026-09-04 08:50:20', NULL),
('175', '3', 'Operacoes Basicas', '12 × 7', '', '', '', '', '84', 'Nível Difícil: 12 × 7 = 84.', 'hard', '0', NULL, '2026-09-04 08:50:25', NULL),
('176', '3', 'Operacoes Basicas', '93 + 59', '', '', '', '', '152', 'Nível Difícil: 93 + 59 = 152.', 'hard', '0', NULL, '2026-09-04 08:50:39', NULL),
('177', '3', 'Operacoes Basicas', '53 + 71', '', '', '', '', '124', 'Nível Difícil: 53 + 71 = 124.', 'hard', '0', NULL, '2026-09-04 08:50:52', NULL),
('178', '3', 'Operacoes Basicas', '12 + 6', '', '', '', '', '18', 'Nível Fácil: 12 + 6 = 18.', 'easy', '0', NULL, '2026-09-04 08:51:07', NULL),
('179', '3', 'Operacoes Basicas', '3 × 3', '', '', '', '', '9', 'Nível Fácil: 3 × 3 = 9.', 'easy', '0', NULL, '2026-09-04 08:51:07', NULL),
('180', '3', 'Operacoes Basicas', '7 + 6', '', '', '', '', '13', 'Nível Fácil: 7 + 6 = 13.', 'easy', '0', NULL, '2026-09-04 08:51:08', NULL),
('181', '3', 'Operacoes Basicas', '8 ÷ 2', '', '', '', '', '4', 'Nível Fácil: 8 ÷ 2 = 4.', 'easy', '0', NULL, '2026-09-04 08:51:53', NULL),
('182', '3', 'Operacoes Basicas', '3 × 3', '', '', '', '', '9', 'Nível Fácil: 3 × 3 = 9.', 'easy', '0', NULL, '2026-09-04 08:51:53', NULL),
('183', '3', 'Operacoes Basicas', '6 + 7', '', '', '', '', '13', 'Nível Fácil: 6 + 7 = 13.', 'easy', '0', NULL, '2026-09-04 08:51:53', NULL),
('184', '3', 'Operacoes Basicas', '18 ÷ 3', '', '', '', '', '6', 'Nível Fácil: 18 ÷ 3 = 6.', 'easy', '0', NULL, '2026-09-04 08:51:58', NULL),
('185', '3', 'Operacoes Basicas', '15 ÷ 3', '', '', '', '', '5', 'Nível Fácil: 15 ÷ 3 = 5.', 'easy', '0', NULL, '2026-09-04 08:51:58', NULL),
('186', '3', 'Operacoes Basicas', '3 × 5', '', '', '', '', '15', 'Nível Fácil: 3 × 5 = 15.', 'easy', '0', NULL, '2026-09-04 08:51:58', NULL),
('187', '3', 'Operacoes Basicas', '13 - 10', '', '', '', '', '3', 'Nível Fácil: 13 - 10 = 3.', 'easy', '0', NULL, '2026-09-04 08:52:41', NULL),
('188', '3', 'Operacoes Basicas', '14 - 4', '', '', '', '', '10', 'Nível Fácil: 14 - 4 = 10.', 'easy', '0', NULL, '2026-09-04 08:52:41', NULL),
('189', '3', 'Operacoes Basicas', '19 - 9', '', '', '', '', '10', 'Nível Fácil: 19 - 9 = 10.', 'easy', '0', NULL, '2026-09-04 08:52:41', NULL),
('190', '3', 'Operacoes Basicas', '3 × 8', '', '', '', '', '24', 'Nível Fácil: 3 × 8 = 24.', 'easy', '0', NULL, '2026-09-04 08:52:43', NULL),
('191', '3', 'Operacoes Basicas', '6 + 8', '', '', '', '', '14', 'Nível Fácil: 6 + 8 = 14.', 'easy', '0', NULL, '2026-09-04 08:52:44', NULL),
('192', '3', 'Operacoes Basicas', '20 ÷ 4', '', '', '', '', '5', 'Nível Fácil: 20 ÷ 4 = 5.', 'easy', '0', NULL, '2026-09-04 08:52:44', NULL),
('193', '3', 'Operacoes Basicas', '2 × 5', '', '', '', '', '10', 'Nível Fácil: 2 × 5 = 10.', 'easy', '0', NULL, '2026-09-04 08:52:44', NULL),
('194', '3', 'Operacoes Basicas', '14 + 12', '', '', '', '', '26', 'Nível Fácil: 14 + 12 = 26.', 'easy', '0', NULL, '2026-09-04 08:52:45', NULL),
('195', '3', 'Operacoes Basicas', '8 + 4', '', '', '', '', '12', 'Nível Fácil: 8 + 4 = 12.', 'easy', '0', NULL, '2026-09-04 08:52:45', NULL),
('196', '3', 'Operacoes Basicas', '4 × 5', '', '', '', '', '20', 'Nível Fácil: 4 × 5 = 20.', 'easy', '0', NULL, '2026-09-04 08:52:45', NULL),
('197', '3', 'Operacoes Basicas', '12 - 3', '', '', '', '', '9', 'Nível Fácil: 12 - 3 = 9.', 'easy', '0', NULL, '2026-09-04 08:54:02', NULL),
('198', '3', 'Operacoes Basicas', '35 ÷ 5', '', '', '', '', '7', 'Nível Fácil: 35 ÷ 5 = 7.', 'easy', '0', NULL, '2026-09-04 08:54:02', NULL),
('199', '3', 'Operacoes Basicas', '5 × 4', '', '', '', '', '20', 'Nível Fácil: 5 × 4 = 20.', 'easy', '0', NULL, '2026-09-04 08:54:02', NULL),
('200', '3', 'Operacoes Basicas', '8 ÷ 4', '', '', '', '', '2', 'Nível Fácil: 8 ÷ 4 = 2.', 'easy', '0', NULL, '2026-09-04 08:54:55', NULL),
('201', '3', 'Operacoes Basicas', '14 - 10', '', '', '', '', '4', 'Nível Fácil: 14 - 10 = 4.', 'easy', '0', NULL, '2026-09-04 08:54:55', NULL),
('202', '3', 'Operacoes Basicas', '11 - 5', '', '', '', '', '6', 'Nível Fácil: 11 - 5 = 6.', 'easy', '0', NULL, '2026-09-04 08:54:55', NULL),
('203', '3', 'Operacoes Basicas', '41 - 26', '', '', '', '', '15', 'Nível Médio: 41 - 26 = 15.', 'medium', '0', NULL, '2026-09-04 08:55:01', NULL),
('204', '3', 'Operacoes Basicas', '50 - 35', '', '', '', '', '15', 'Nível Médio: 50 - 35 = 15.', 'medium', '0', NULL, '2026-09-04 08:55:04', NULL),
('205', '3', 'Operacoes Basicas', '72 ÷ 9', '', '', '', '', '8', 'Nível Médio: 72 ÷ 9 = 8.', 'medium', '0', NULL, '2026-09-04 08:55:09', NULL),
('206', '3', 'Operacoes Basicas', '12 ÷ 3', '', '', '', '', '4', 'Nível Fácil: 12 ÷ 3 = 4.', 'easy', '0', NULL, '2026-09-04 08:55:27', NULL),
('207', '3', 'Operacoes Basicas', '10 + 7', '', '', '', '', '17', 'Nível Fácil: 10 + 7 = 17.', 'easy', '0', NULL, '2026-09-04 08:55:27', NULL),
('208', '3', 'Operacoes Basicas', '11 + 6', '', '', '', '', '17', 'Nível Fácil: 11 + 6 = 17.', 'easy', '0', NULL, '2026-09-04 08:55:27', NULL),
('209', '3', 'Operacoes Basicas', '9 × 9', '', '', '', '', '81', 'Nível Médio: 9 × 9 = 81.', 'medium', '0', NULL, '2026-09-04 08:55:34', NULL),
('210', '3', 'Operacoes Basicas', '7 + 4', '', '', '', '', '11', 'Nível Fácil: 7 + 4 = 11.', 'easy', '0', NULL, '2026-09-04 08:55:39', NULL),
('211', '3', 'Operacoes Basicas', '7 + 3', '', '', '', '', '10', 'Nível Fácil: 7 + 3 = 10.', 'easy', '0', NULL, '2026-09-04 08:55:39', NULL),
('212', '3', 'Operacoes Basicas', '25 ÷ 5', '', '', '', '', '5', 'Nível Fácil: 25 ÷ 5 = 5.', 'easy', '0', NULL, '2026-09-04 08:55:39', NULL),
('213', '3', 'Operacoes Basicas', '63 - 37', '', '', '', '', '26', 'Nível Médio: 63 - 37 = 26.', 'medium', '0', NULL, '2026-09-04 08:55:41', NULL),
('214', '3', 'Operacoes Basicas', '64 ÷ 8', '', '', '', '', '8', 'Nível Médio: 64 ÷ 8 = 8.', 'medium', '0', NULL, '2026-09-04 08:55:43', NULL),
('215', '3', 'Operacoes Basicas', '54 - 37', '', '', '', '', '17', 'Nível Médio: 54 - 37 = 17.', 'medium', '0', NULL, '2026-09-04 08:55:45', NULL),
('216', '3', 'Operacoes Basicas', '64 + 38', '', '', '', '', '102', 'Nível Difícil: 64 + 38 = 102.', 'hard', '0', NULL, '2026-09-04 08:56:01', NULL),
('217', '3', 'Operacoes Basicas', '13 × 6', '', '', '', '', '78', 'Nível Difícil: 13 × 6 = 78.', 'hard', '0', NULL, '2026-09-04 08:56:07', NULL),
('218', '3', 'Operacoes Basicas', '13 × 3', '', '', '', '', '39', 'Nível Difícil: 13 × 3 = 39.', 'hard', '0', NULL, '2026-09-04 08:56:16', NULL),
('219', '3', 'Operacoes Basicas', '115 - 75', '', '', '', '', '40', 'Nível Difícil: 115 - 75 = 40.', 'hard', '0', NULL, '2026-09-04 08:56:29', NULL),
('220', '3', 'Operacoes Basicas', '6 ÷ 3', '', '', '', '', '2', 'Nível Fácil: 6 ÷ 3 = 2.', 'easy', '0', NULL, '2026-09-04 08:57:27', NULL),
('221', '3', 'Operacoes Basicas', '15 + 11', '', '', '', '', '26', 'Nível Fácil: 15 + 11 = 26.', 'easy', '0', NULL, '2026-09-04 08:57:27', NULL),
('222', '3', 'Operacoes Basicas', '40 ÷ 5', '', '', '', '', '8', 'Nível Fácil: 40 ÷ 5 = 8.', 'easy', '0', NULL, '2026-09-04 08:57:27', NULL),
('223', '3', 'Operacoes Basicas', '9 × 5', '', '', '', '', '45', 'Nível Médio: 9 × 5 = 45.', 'medium', '0', NULL, '2026-09-04 08:57:58', NULL),
('224', '3', 'Operacoes Basicas', '6 + 12', '', '', '', '', '18', 'Nível Fácil: 6 + 12 = 18.', 'easy', '0', NULL, '2026-09-04 08:58:06', NULL),
('225', '3', 'Operacoes Basicas', '9 - 4', '', '', '', '', '5', 'Nível Fácil: 9 - 4 = 5.', 'easy', '0', NULL, '2026-09-04 08:58:06', NULL),
('226', '3', 'Operacoes Basicas', '4 + 10', '', '', '', '', '14', 'Nível Fácil: 4 + 10 = 14.', 'easy', '0', NULL, '2026-09-04 08:58:06', NULL),
('227', '1', 'Fracoes e Razoes', '[Reforço] Se você dividir uma barra de chocolate em 10 pedaços e comer 4, qual fração representa a parte que sobrou?', '4/10', '6/10 (ou 3/5)', '1/2', '2/5', 'B', 'O total é 10/10. Subtraindo a parte consumida: 10/10 - 4/10 = 6/10, simplificando por 2 fica 3/5.', 'easy', '1', NULL, '2026-09-04 14:42:27', NULL),
('228', '1', 'Fracoes e Razoes', '[Reforço] Qual fração é equivalente a 3/4?', '6/8', '6/4', '3/8', '9/16', 'A', 'Multiplicando numerador e denominador por 2: 3/4 = (3×2)/(4×2) = 6/8.', 'easy', '1', NULL, '2026-09-04 14:42:27', NULL),
('229', '1', 'Fracoes e Razoes', '[Reforço] Qual é o resultado da soma 2/5 + 1/5?', '3/10', '3/5', '2/25', '1/5', 'B', 'Com denominadores iguais, mantemos o denominador 5 e somamos os numeradores: 2 + 1 = 3/5.', 'easy', '1', NULL, '2026-09-04 14:42:27', NULL),
('230', '3', 'Operacoes Basicas', '10 ÷ 2', '', '', '', '', '5', 'Nível Fácil: 10 ÷ 2 = 5.', 'easy', '0', NULL, '2026-09-09 07:52:14', NULL),
('231', '1', 'Fracoes e Razoes', '[Reforço] Se você dividir uma barra de chocolate em 10 pedaços e comer 4, qual fração representa a parte que sobrou?', '4/10', '6/10 (ou 3/5)', '1/2', '2/5', 'B', 'O total é 10/10. Subtraindo a parte consumida: 10/10 - 4/10 = 6/10, simplificando por 2 fica 3/5.', 'easy', '1', NULL, '2026-09-09 07:52:15', NULL),
('232', '1', 'Fracoes e Razoes', '[Reforço] Qual fração é equivalente a 3/4?', '6/8', '6/4', '3/8', '9/16', 'A', 'Multiplicando numerador e denominador por 2: 3/4 = (3×2)/(4×2) = 6/8.', 'easy', '1', NULL, '2026-09-09 07:52:15', NULL),
('233', '1', 'Fracoes e Razoes', '[Reforço] Qual é o resultado da soma 2/5 + 1/5?', '3/10', '3/5', '2/25', '1/5', 'B', 'Com denominadores iguais, mantemos o denominador 5 e somamos os numeradores: 2 + 1 = 3/5.', 'easy', '1', NULL, '2026-09-09 07:52:15', NULL),
('234', '3', 'Operacoes Basicas', '12 - 7', '', '', '', '', '5', '💡 Operação Inversa: quanto falta de 7 para 12? 7 + 5 = 12, portanto 12 − 7 = 5.', 'easy', '0', NULL, '2026-10-07 16:23:46', NULL),
('235', '3', 'Operacoes Basicas', '4 × 7', '', '', '', '', '28', '💡 Técnica do Dobro do Dobro: dobro de 7 é 14, e dobro de 14 é 28.', 'easy', '0', NULL, '2026-10-07 16:23:46', NULL),
('236', '3', 'Operacoes Basicas', '3 × 4', '', '', '', '', '12', '💡 Técnica do Dobro + 1x: calcule o dobro (4 × 2 = 8) e some mais 4 (8 + 4 = 12).', 'easy', '0', NULL, '2026-10-07 16:23:47', NULL),
('237', '3', 'Operacoes Basicas', '37 + 29', '', '', '', '', '66', '💡 Técnica da Compensação: 37 + 29 = 37 + 30 − 1 = 67 − 1 = 66.', 'medium', '0', NULL, '2026-10-07 16:24:13', NULL),
('238', '3', 'Operacoes Basicas', '28 + 22', '', '', '', '', '50', '💡 Técnica da Compensação: 28 + 22 = 22 + 30 − 2 = 52 − 2 = 50.', 'medium', '0', NULL, '2026-10-07 16:24:22', NULL),
('239', '3', 'Operacoes Basicas', '19 + 21', '', '', '', '', '40', '💡 Técnica da Compensação: 19 + 21 = 21 + 20 − 1 = 41 − 1 = 40.', 'medium', '0', NULL, '2026-10-07 16:24:28', NULL),
('240', '3', 'Operacoes Basicas', '96 ÷ 3', '', '', '', '', '32', '💡 Operação Inversa: pense qual número vezes 3 resulta em 96 (3 × 32 = 96). Portanto, 96 ÷ 3 = 32.', 'hard', '0', NULL, '2026-10-07 16:24:45', NULL),
('241', '3', 'Operacoes Basicas', '32 ÷ 4', '', '', '', '', '8', '💡 Metade da Metade: a metade de 32 é 16, e a metade de 16 é 8.', 'easy', '0', NULL, '2026-10-07 16:44:46', NULL),
('242', '3', 'Operacoes Basicas', '8 + 8', '', '', '', '', '16', '💡 Técnica da Compensação: 8 + 8 = 8 + 10 − 2 = 18 − 2 = 16.', 'easy', '0', NULL, '2026-10-07 16:44:46', NULL),
('243', '3', 'Operacoes Basicas', '11 - 7', '', '', '', '', '4', '💡 Operação Inversa: quanto falta de 7 para 11? 7 + 4 = 11, portanto 11 − 7 = 4.', 'easy', '0', NULL, '2026-10-07 16:44:46', NULL),
('244', '3', 'Operacoes Basicas', '8 × 9', '', '', '', '', '72', '💡 Regra do 9: multiplique por 10 e subtraia 1x (8 × 10 − 8 = 80 − 8 = 72).', 'medium', '0', NULL, '2026-10-07 16:44:49', NULL),
('245', '3', 'Operacoes Basicas', '36 + 19', '', '', '', '', '55', '💡 Técnica da Compensação: 36 + 19 = 36 + 20 − 1 = 56 − 1 = 55.', 'medium', '0', NULL, '2026-10-07 16:44:51', NULL),
('246', '3', 'Operacoes Basicas', '8 × 4', '', '', '', '', '32', '💡 Técnica do Dobro do Dobro: dobro de 8 é 16, e dobro de 16 é 32.', 'medium', '0', NULL, '2026-10-07 16:44:55', NULL),
('247', '3', 'Operacoes Basicas', '92 + 42', '', '', '', '', '134', '💡 Decomposição: some dezenas (90 + 40 = 130) e unidades (2 + 2 = 4) → 130 + 4 = 134.', 'hard', '0', NULL, '2026-10-07 16:45:02', NULL),
('248', '3', 'Operacoes Basicas', '15 × 3', '', '', '', '', '45', '💡 Técnica do Dobro + 1x: calcule o dobro (15 × 2 = 30) e some mais 15 (30 + 15 = 45).', 'hard', '0', NULL, '2026-10-07 16:45:16', NULL),
('249', '3', 'Operacoes Basicas', '79 + 71', '', '', '', '', '150', '💡 Técnica da Compensação: 79 + 71 = 71 + 80 − 1 = 151 − 1 = 150.', 'hard', '0', NULL, '2026-10-07 16:45:19', NULL),
('250', '3', 'Operacoes Basicas', '125 - 50', '', '', '', '', '75', '💡 Subtração em 2 Etapas: subtraia a dezena (125 − 50 = 75) e depois a unidade (75 − 0 = 75).', 'hard', '0', NULL, '2026-10-07 16:45:32', NULL),
('251', '3', 'Operacoes Basicas', '107 + 67', '', '', '', '', '174', '💡 Técnica da Compensação: 107 + 67 = 107 + 70 − 3 = 177 − 3 = 174.', 'hard', '0', NULL, '2026-10-07 16:45:37', NULL),
('252', '3', 'Operacoes Basicas', '238 - 124', '', '', '', '', '114', '💡 Subtração em 2 Etapas: subtraia a dezena (238 − 120 = 118) e depois a unidade (118 − 4 = 114).', 'hard', '0', NULL, '2026-10-07 16:45:53', NULL),
('253', '3', 'Operacoes Basicas', '14 + 3', '', '', '', '', '17', '💡 Decomposição: some dezenas (10 + 0 = 10) e unidades (4 + 3 = 7) → 10 + 7 = 17.', 'easy', '0', NULL, '2026-10-21 11:49:44', NULL),
('254', '3', 'Operacoes Basicas', '5 × 6', '', '', '', '', '30', '💡 Regra do 5 (Metade de 10): multiplique por 10 (6 × 10 = 60) e divida pela metade (60 ÷ 2 = 30).', 'easy', '0', NULL, '2026-10-21 11:49:44', NULL),
('255', '3', 'Operacoes Basicas', '18 - 8', '', '', '', '', '10', '💡 Operação Inversa: quanto falta de 8 para 18? 8 + 10 = 18, portanto 18 − 8 = 10.', 'easy', '0', NULL, '2026-10-21 11:49:44', NULL),
('256', '3', 'Operacoes Basicas', '2 + 2', '', '', '', '', '4', '💡 Contagem Ágil: comece no 2 e some 2 = 4.', 'easy', '0', NULL, '2026-10-21 12:05:11', NULL);

-- --------------------------------------------------------
-- Estrutura da tabela `recoveries`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `recoveries`;
CREATE TABLE `recoveries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `teacher_id` int DEFAULT NULL,
  `topic` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `game_id` int DEFAULT NULL,
  `status` enum('pending','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `score` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_recoveries_student` (`student_id`),
  KEY `fk_recoveries_teacher` (`teacher_id`),
  CONSTRAINT `fk_recoveries_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_recoveries_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `game_sessions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `game_sessions`;
CREATE TABLE `game_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `game_id` int NOT NULL,
  `score` int NOT NULL DEFAULT '0',
  `correct_answers` int NOT NULL DEFAULT '0',
  `wrong_answers` int NOT NULL DEFAULT '0',
  `time_spent` int NOT NULL DEFAULT '0',
  `difficulty` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'easy',
  `played_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_session_user` (`user_id`),
  KEY `fk_session_game` (`game_id`),
  CONSTRAINT `fk_session_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `game_sessions`
INSERT INTO `game_sessions` (`id`, `user_id`, `game_id`, `score`, `correct_answers`, `wrong_answers`, `time_spent`, `difficulty`, `played_at`) VALUES
('7', '1', '2', '50', '1', '7', '32', 'easy', '2026-10-21 11:50:22'),
('8', '1', '1', '50', '1', '7', '19', 'easy', '2026-10-21 11:50:44');

-- --------------------------------------------------------
-- Estrutura da tabela `user_achievements`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `user_achievements`;
CREATE TABLE `user_achievements` (
  `user_id` int NOT NULL,
  `achievement_id` int NOT NULL,
  `unlocked_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`achievement_id`),
  KEY `fk_uach_ach` (`achievement_id`),
  CONSTRAINT `fk_uach_ach` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uach_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `user_achievements`
INSERT INTO `user_achievements` (`user_id`, `achievement_id`, `unlocked_at`) VALUES
('1', '1', '2026-10-21 11:50:22'),
('1', '3', '2026-10-21 11:50:22'),
('1', '4', '2026-10-21 11:50:22'),
('1', '5', '2026-10-21 11:50:22'),
('1', '6', '2026-10-21 11:50:22');

-- --------------------------------------------------------
-- Estrutura da tabela `learning_trail`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `learning_trail`;
CREATE TABLE `learning_trail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `topic` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `progress_pct` int NOT NULL DEFAULT '0',
  `current_level` enum('easy','medium','hard') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'easy',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_topic` (`user_id`,`topic`),
  CONSTRAINT `fk_trail_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `learning_trail`
INSERT INTO `learning_trail` (`id`, `user_id`, `topic`, `progress_pct`, `current_level`) VALUES
('7', '1', 'Geometria Plana', '13', 'easy'),
('8', '1', 'Fracoes e Razoes', '13', 'easy');

-- --------------------------------------------------------
-- Estrutura da tabela `monthly_recovery_audits`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `monthly_recovery_audits`;
CREATE TABLE `monthly_recovery_audits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `audit_month` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `executed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `students_audited` int NOT NULL DEFAULT '0',
  `students_in_recovery` int NOT NULL DEFAULT '0',
  `details_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_audit_month` (`audit_month`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `monthly_recovery_audits`
INSERT INTO `monthly_recovery_audits` (`id`, `audit_month`, `executed_at`, `students_audited`, `students_in_recovery`, `details_json`) VALUES
('1', '2026-09', '2026-09-11 13:54:04', '5', '0', '[]'),
('4', '2026-10', '2026-10-09 10:47:55', '3', '0', '[]');

-- --------------------------------------------------------
-- Estrutura da tabela `notifications`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'info',
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_notif_user` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados da tabela `notifications`
INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `is_read`, `created_at`) VALUES
('3', '1', 'alert', 'Alerta Pedagogico', 'Turma do 7° ano com bom desempenho inicial em Fracoes e Razoes.', '1', '2026-09-02 16:34:01'),
('5', '1', 'ai', 'Reforco Personalizado Disponivel', 'Geradas 3 questoes de reforco sobre Fracoes e Razoes com foco pedagogico!', '1', '2026-09-04 14:42:27'),
('9', '1', 'alert', '📅 Auditoria Mensal (10/2026): Desempenho OK', 'A auditoria mensal analisou 0 aluno(s) e nenhum apresentou rendimento crítico abaixo de 60%. Toda a turma está dentro da meta esperada!', '1', '2026-10-07 15:39:14'),
('10', '1', 'alert', '📅 Auditoria Mensal (10/2026): Desempenho OK', 'A auditoria mensal analisou 0 aluno(s) e nenhum apresentou rendimento crítico abaixo de 60%. Toda a turma está dentro da meta esperada!', '1', '2026-10-09 10:27:42'),
('11', '1', 'alert', '📅 Auditoria Mensal (10/2026): Desempenho OK', 'A auditoria mensal analisou 3 aluno(s) e nenhum apresentou rendimento crítico abaixo de 60%. Toda a turma está dentro da meta esperada!', '1', '2026-10-09 10:37:46'),
('12', '1', 'alert', '📅 Auditoria Mensal (10/2026): Desempenho OK', 'A auditoria mensal analisou 3 aluno(s) e nenhum apresentou rendimento crítico abaixo de 60%. Toda a turma está dentro da meta esperada!', '1', '2026-10-09 10:47:55'),
('13', '1', 'achievement', 'Conquista Desbloqueada!', 'Você desbloqueou: Primeiro Passo — Complete sua primeira partida na plataforma', '0', '2026-10-21 11:50:22'),
('14', '1', 'achievement', 'Conquista Desbloqueada!', 'Você desbloqueou: Cem Pontos! — Alcance a marca de 100 XP acumulados', '0', '2026-10-21 11:50:22'),
('15', '1', 'achievement', 'Conquista Desbloqueada!', 'Você desbloqueou: Mestre do XP — Alcance 500 XP acumulados no perfil', '0', '2026-10-21 11:50:22'),
('16', '1', 'achievement', 'Conquista Desbloqueada!', 'Você desbloqueou: Estrela em Ascensao — Alcance o nivel 3 de maestria', '0', '2026-10-21 11:50:22'),
('17', '1', 'achievement', 'Conquista Desbloqueada!', 'Você desbloqueou: Lendario — Alcance o nivel 5 de maestria', '0', '2026-10-21 11:50:22'),
('18', '1', 'alert', 'Alerta de Desempenho', 'O aluno Professor Carlos Silva teve mais de 60% de erros no conteúdo: Geometria Plana. Recomenda-se aplicar reforço pedagógico.', '0', '2026-10-21 11:50:22'),
('19', '1', 'alert', 'Alerta de Desempenho', 'O aluno Professor Carlos Silva teve mais de 60% de erros no conteúdo: Fracoes e Razoes. Recomenda-se aplicar reforço pedagógico.', '0', '2026-10-21 11:50:44');

SET FOREIGN_KEY_CHECKS = 1;
