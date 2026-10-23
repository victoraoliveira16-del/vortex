/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;

/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;

/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;

/*!50503 SET NAMES utf8mb4 */;

/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;

/*!40103 SET TIME_ZONE='+00:00' */;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;

/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

DROP TABLE IF EXISTS `achievements`;

/*!40101 SET @saved_cs_client     = @@character_set_client */;

/*!50503 SET character_set_client = utf8mb4 */;

CREATE TABLE `achievements` (

  `id` int NOT NULL AUTO_INCREMENT,

  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,

  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,

  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'fa-trophy',

  `condition_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,

  `condition_value` int NOT NULL DEFAULT '0',

  PRIMARY KEY (`id`)

) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `achievements` DISABLE KEYS */;

INSERT INTO `achievements` VALUES (1,'Primeiro Passo','Complete sua primeira partida na plataforma','fa-gamepad','games_played',1),(2,'Dedicado','Jogue 5 partidas no sistema','fa-calendar','games_played',5),(3,'Cem Pontos!','Alcance a marca de 100 XP acumulados','fa-bolt','xp_total',100),(4,'Mestre do XP','Alcance 500 XP acumulados no perfil','fa-award','xp_total',500),(5,'Estrela em Ascensao','Alcance o nivel 3 de maestria','fa-star','level',3),(6,'Lendario','Alcance o nivel 5 de maestria','fa-crown','level',5);

/*!40000 ALTER TABLE `achievements` ENABLE KEYS */;

DROP TABLE IF EXISTS `game_sessions`;

/*!40101 SET @saved_cs_client     = @@character_set_client */;

/*!50503 SET character_set_client = utf8mb4 */;

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

/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `game_sessions` DISABLE KEYS */;

INSERT INTO `game_sessions` VALUES (7,1,2,50,1,7,32,'easy','2026-10-21 14:50:22'),(8,1,1,50,1,7,19,'easy','2026-10-21 14:50:44');

/*!40000 ALTER TABLE `game_sessions` ENABLE KEYS */;

DROP TABLE IF EXISTS `games`;

/*!40101 SET @saved_cs_client     = @@character_set_client */;

/*!50503 SET character_set_client = utf8mb4 */;

CREATE TABLE `games` (

  `id` int NOT NULL AUTO_INCREMENT,

  `slug` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,

  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,

  `topic` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,

  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,

  PRIMARY KEY (`id`),

  UNIQUE KEY `slug` (`slug`)

) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `games` DISABLE KEYS */;

INSERT INTO `games` VALUES (1,'fractions','Chef das Fracoes','Fracoes e Razoes','Monte receitas incriveis dividindo ingredientes na proporcao certa! Domine fracoes enquanto cozinha pratos deliciosos.'),(2,'geometry','Construtor de Cidades','Geometria Plana','Construa sua propria metropole calculando areas e perimetros! Cada edificio precisa do espaco certo para ser erguido.'),(3,'mental-math','Calculadora Mental','Operacoes Basicas','Resolva calculos das quatro operacoes contra o relogio e aumente seu combo a cada acerto.');

/*!40000 ALTER TABLE `games` ENABLE KEYS */;

DROP TABLE IF EXISTS `learning_trail`;

/*!40101 SET @saved_cs_client     = @@character_set_client */;

/*!50503 SET character_set_client = utf8mb4 */;

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

/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `learning_trail` DISABLE KEYS */;

INSERT INTO `learning_trail` VALUES (7,1,'Geometria Plana',13,'easy'),(8,1,'Fracoes e Razoes',13,'easy');

/*!40000 ALTER TABLE `learning_trail` ENABLE KEYS */;

DROP TABLE IF EXISTS `monthly_recovery_audits`;

/*!40101 SET @saved_cs_client     = @@character_set_client */;

/*!50503 SET character_set_client = utf8mb4 */;

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

/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `monthly_recovery_audits` DISABLE KEYS */;

INSERT INTO `monthly_recovery_audits` VALUES (1,'2026-09','2026-09-11 16:54:04',5,0,'[]'),(4,'2026-10','2026-10-09 13:47:55',3,0,'[]');

/*!40000 ALTER TABLE `monthly_recovery_audits` ENABLE KEYS */;

DROP TABLE IF EXISTS `notifications`;

/*!40101 SET @saved_cs_client     = @@character_set_client */;

/*!50503 SET character_set_client = utf8mb4 */;

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

/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;

INSERT INTO `notifications` VALUES (3,1,'alert','Alerta Pedagogico','Turma do 7┬░ ano com bom desempenho inicial em Fracoes e Razoes.',1,'2026-09-02 19:34:01'),(5,1,'ai','Reforco Personalizado Disponivel','Geradas 3 questoes de reforco sobre Fracoes e Razoes com foco pedagogico!',1,'2026-09-04 17:42:27'),(9,1,'alert','­ƒôà Auditoria Mensal (10/2026): Desempenho OK','A auditoria mensal analisou 0 aluno(s) e nenhum apresentou rendimento cr├¡tico abaixo de 60%. Toda a turma est├í dentro da meta esperada!',1,'2026-10-07 18:39:14'),(10,1,'alert','­ƒôà Auditoria Mensal (10/2026): Desempenho OK','A auditoria mensal analisou 0 aluno(s) e nenhum apresentou rendimento cr├¡tico abaixo de 60%. Toda a turma est├í dentro da meta esperada!',1,'2026-10-09 13:27:42'),(11,1,'alert','­ƒôà Auditoria Mensal (10/2026): Desempenho OK','A auditoria mensal analisou 3 aluno(s) e nenhum apresentou rendimento cr├¡tico abaixo de 60%. Toda a turma est├í dentro da meta esperada!',1,'2026-10-09 13:37:46'),(12,1,'alert','­ƒôà Auditoria Mensal (10/2026): Desempenho OK','A auditoria mensal analisou 3 aluno(s) e nenhum apresentou rendimento cr├¡tico abaixo de 60%. Toda a turma est├í dentro da meta esperada!',1,'2026-10-09 13:47:55'),(13,1,'achievement','Conquista Desbloqueada!','Voc├¬ desbloqueou: Primeiro Passo ÔÇö Complete sua primeira partida na plataforma',0,'2026-10-21 14:50:22'),(14,1,'achievement','Conquista Desbloqueada!','Voc├¬ desbloqueou: Cem Pontos! ÔÇö Alcance a marca de 100 XP acumulados',0,'2026-10-21 14:50:22'),(15,1,'achievement','Conquista Desbloqueada!','Voc├¬ desbloqueou: Mestre do XP ÔÇö Alcance 500 XP acumulados no perfil',0,'2026-10-21 14:50:22'),(16,1,'achievement','Conquista Desbloqueada!','Voc├¬ desbloqueou: Estrela em Ascensao ÔÇö Alcance o nivel 3 de maestria',0,'2026-10-21 14:50:22'),(17,1,'achievement','Conquista Desbloqueada!','Voc├¬ desbloqueou: Lendario ÔÇö Alcance o nivel 5 de maestria',0,'2026-10-21 14:50:22'),(18,1,'alert','Alerta de Desempenho','O aluno Professor Carlos Silva teve mais de 60% de erros no conte├║do: Geometria Plana. Recomenda-se aplicar refor├ºo pedag├│gico.',0,'2026-10-21 14:50:22'),(19,1,'alert','Alerta de Desempenho','O aluno Professor Carlos Silva teve mais de 60% de erros no conte├║do: Fracoes e Razoes. Recomenda-se aplicar refor├ºo pedag├│gico.',0,'2026-10-21 14:50:44');

/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;

DROP TABLE IF EXISTS `questions`;

/*!40101 SET @saved_cs_client     = @@character_set_client */;

/*!50503 SET character_set_client = utf8mb4 */;

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

  PRIMARY KEY (`id`),

  KEY `fk_question_game` (`game_id`),

  KEY `fk_questions_target_user` (`target_user_id`),

  CONSTRAINT `fk_question_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE SET NULL,

  CONSTRAINT `fk_questions_target_user` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL

) ENGINE=InnoDB AUTO_INCREMENT=257 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `questions` DISABLE KEYS */;

INSERT INTO `questions` VALUES (1,1,'Fracoes e Razoes','A receita pede 1/2 xicara de farinha. Para fazer o dobro da receita, quanto voce precisa?','1/2 xicara','1 xicara inteira','2 xicaras','1/4 de xicara','B','1/2 multiplicado por 2 = 2/2 = 1 xicara inteira!','easy',0,NULL,'2026-09-02 19:33:46'),(2,1,'Fracoes e Razoes','Uma pizza foi cortada em 8 pedacos iguais. Joao comeu 3 pedacos. Que fracao da pizza ele comeu?','1/4 da pizza','3/5 da pizza','3/8 da pizza','5/8 da pizza','C','Joao consumiu 3 de um total de 8 partes, o que representa 3/8 da pizza.','easy',0,NULL,'2026-09-02 19:33:46'),(3,1,'Fracoes e Razoes','Qual e a metade exata da fracao 3/4?','3/2','1/4','3/8','6/4','C','Calcular a metade equivale a dividir por 2: (3/4) / 2 = 3/8.','easy',0,NULL,'2026-09-02 19:33:46'),(4,1,'Fracoes e Razoes','Uma receita usa 2/3 de copo de leite. Qual fracao do copo restou?','1/3 de copo','2/3 de copo','1/2 de copo','3/3 de copo','A','O copo inteiro representa 3/3. Subtraindo a parte usada: 3/3 - 2/3 = 1/3.','easy',0,NULL,'2026-09-02 19:33:46'),(5,1,'Fracoes e Razoes','Qual e o resultado da soma de 1/4 + 1/4?','2/8','1/2','1/8','3/4','B','1/4 + 1/4 = 2/4. Simplificando a fracao por 2, obtemos 1/2.','easy',0,NULL,'2026-09-02 19:33:46'),(6,1,'Fracoes e Razoes','Uma jarra contem 3/4 de suco. Voce bebe 1/4. Quanto resta na jarra?','1/2 da jarra','1/4 da jarra','3/4 da jarra','2/8 da jarra','A','3/4 - 1/4 = 2/4 = 1/2 da jarra de suco.','medium',0,NULL,'2026-09-02 19:33:46'),(7,1,'Fracoes e Razoes','Se 1/3 de uma receita demanda 6 ovos, quantos ovos serao necessarios para a receita completa?','3 ovos','9 ovos','18 ovos','12 ovos','C','Se 1/3 corresponde a 6 ovos, o total (3/3) e obtido por 6 x 3 = 18 ovos.','medium',0,NULL,'2026-09-02 19:33:46'),(8,1,'Fracoes e Razoes','Qual das fracoes abaixo e equivalente a 2/3?','4/9','4/6','3/4','6/4','B','2/3 = 4/6 multiplicando numerador e denominador pelo fator 2.','medium',0,NULL,'2026-09-02 19:33:46'),(9,2,'Geometria Plana','Um terreno retangular tem 5m de largura e 8m de comprimento. Qual e a sua area total?','26 m2','40 m2','13 m2','20 m2','B','A area do retangulo e calculada por base x altura: 5 x 8 = 40 m2.','easy',0,NULL,'2026-09-02 19:33:50'),(10,2,'Geometria Plana','Uma praca quadrada possui lado de 6m. Qual e o seu perimetro total?','12 metros','36 metros','24 metros','18 metros','C','O perimetro do quadrado e dado por 4 x lado: 4 x 6 = 24 metros.','easy',0,NULL,'2026-09-02 19:33:50'),(11,2,'Geometria Plana','Para cercar um jardim retangular de 4m por 3m, quantos metros de cerca sao necessarios?','7 metros','12 metros','14 metros','24 metros','C','Perimetro = 2 x (4 + 3) = 2 x 7 = 14 metros de cerca.','easy',0,NULL,'2026-09-02 19:33:50'),(12,2,'Geometria Plana','Um apartamento quadrado possui area de 25m2. Qual e a medida de cada lado?','6 metros','5 metros','4 metros','7 metros','B','A area do quadrado e lado ao quadrado (L^2). Raiz quadrada de 25 = 5 metros.','easy',0,NULL,'2026-09-02 19:33:50'),(13,2,'Geometria Plana','Uma sala retangular mede 10m por 4m. Quantas placas de piso de 1m2 sao necessarias para cobrir o chao?','28 placas','14 placas','40 placas','20 placas','C','Area total da sala = 10 x 4 = 40 m2. Logo, sao necessarias 40 placas de 1m2.','medium',0,NULL,'2026-09-02 19:33:50'),(14,2,'Geometria Plana','Um parque triangular possui base de 8m e altura de 5m. Qual e a sua area?','40 m2','13 m2','20 m2','26 m2','C','Area do triangulo = (base x altura) / 2 = (8 x 5) / 2 = 40 / 2 = 20 m2.','medium',0,NULL,'2026-09-02 19:33:50'),(15,2,'Geometria Plana','Uma praca circular tem raio de 3m. Qual e a area aproximada? (Considere pi = 3)','9 m2','18 m2','27 m2','6 m2','C','Area do circulo = pi x r^2 = 3 x (3^2) = 3 x 9 = 27 m2.','medium',0,NULL,'2026-09-02 19:33:50'),(16,2,'Geometria Plana','Um terreno em L e composto por um retangulo 10x6 do qual retirou-se um recorte 4x3. Qual e a area util restante?','48 m2','60 m2','72 m2','52 m2','A','Area total inicial: 10 x 6 = 60. Area recortada: 4 x 3 = 12. Area util restante: 60 - 12 = 48 m2.','hard',0,NULL,'2026-09-02 19:33:50'),(17,3,'Operacoes Basicas','10 ├À 2','','','','','5','Resolva a operacao da esquerda para a direita: 10 ├À 2 = 5.','easy',0,NULL,'2026-09-02 19:38:41'),(18,3,'Operacoes Basicas','12 ├À 2','','','','','6','Resolva a operacao da esquerda para a direita: 12 ├À 2 = 6.','easy',0,NULL,'2026-09-02 19:38:41'),(19,3,'Operacoes Basicas','8 ├ù 8','','','','','64','Resolva a operacao da esquerda para a direita: 8 ├ù 8 = 64.','easy',0,NULL,'2026-09-02 19:38:41'),(20,3,'Operacoes Basicas','12 - 10','','','','','2','Resolva a operacao da esquerda para a direita: 12 - 10 = 2.','easy',0,NULL,'2026-09-02 19:38:41'),(21,3,'Operacoes Basicas','19 + 6','','','','','25','Resolva a operacao da esquerda para a direita: 19 + 6 = 25.','easy',0,NULL,'2026-09-02 19:38:41'),(22,3,'Operacoes Basicas','18 - 9','','','','','9','Resolva a operacao da esquerda para a direita: 18 - 9 = 9.','easy',0,NULL,'2026-09-02 19:38:41'),(23,3,'Operacoes Basicas','18 ├À 2','','','','','9','Resolva a operacao da esquerda para a direita: 18 ├À 2 = 9.','easy',0,NULL,'2026-09-02 19:38:41'),(24,3,'Operacoes Basicas','23 - 12','','','','','11','Resolva a operacao da esquerda para a direita: 23 - 12 = 11.','easy',0,NULL,'2026-09-02 19:38:41'),(25,3,'Operacoes Basicas','28 + 3','','','','','31','Resolva a operacao da esquerda para a direita: 28 + 3 = 31.','easy',0,NULL,'2026-09-02 19:38:41'),(26,3,'Operacoes Basicas','23 + 8','','','','','31','Resolva a operacao da esquerda para a direita: 23 + 8 = 31.','easy',0,NULL,'2026-09-02 19:38:41'),(27,3,'Operacoes Basicas','22 + 10','','','','','32','Resolva a operacao da esquerda para a direita: 22 + 10 = 32.','easy',0,NULL,'2026-09-02 19:38:48'),(28,3,'Operacoes Basicas','21 - 12','','','','','9','Resolva a operacao da esquerda para a direita: 21 - 12 = 9.','easy',0,NULL,'2026-09-02 19:38:50'),(29,3,'Operacoes Basicas','23 + 5','','','','','28','Resolva a operacao da esquerda para a direita: 23 + 5 = 28.','easy',0,NULL,'2026-09-02 19:38:53'),(30,3,'Operacoes Basicas','15 + 2','','','','','17','Resolva a operacao da esquerda para a direita: 15 + 2 = 17.','easy',0,NULL,'2026-09-02 19:38:55'),(31,3,'Operacoes Basicas','14 ├ù 6','','','','','84','Resolva a operacao da esquerda para a direita: 14 ├ù 6 = 84.','easy',0,NULL,'2026-09-02 19:38:58'),(32,3,'Operacoes Basicas','24 - 9','','','','','15','Resolva a operacao da esquerda para a direita: 24 - 9 = 15.','easy',0,NULL,'2026-09-02 19:39:01'),(33,3,'Operacoes Basicas','13 - 12','','','','','1','Resolva a operacao da esquerda para a direita: 13 - 12 = 1.','easy',0,NULL,'2026-09-02 19:39:03'),(34,3,'Operacoes Basicas','12 - 12','','','','','0','Resolva a operacao da esquerda para a direita: 12 - 12 = 0.','easy',0,NULL,'2026-09-02 19:39:08'),(35,3,'Operacoes Basicas','24 ├À 3','','','','','8','Resolva a operacao da esquerda para a direita: 24 ├À 3 = 8.','easy',0,NULL,'2026-09-02 19:39:11'),(36,3,'Operacoes Basicas','72 ├À 9','','','','','8','Resolva a operacao da esquerda para a direita: 72 ├À 9 = 8.','easy',0,NULL,'2026-09-04 11:30:16'),(37,3,'Operacoes Basicas','21 ├À 7','','','','','3','Resolva a operacao da esquerda para a direita: 21 ├À 7 = 3.','easy',0,NULL,'2026-09-04 11:30:16'),(38,3,'Operacoes Basicas','11 - 8','','','','','3','Resolva a operacao da esquerda para a direita: 11 - 8 = 3.','easy',0,NULL,'2026-09-04 11:30:16'),(39,3,'Operacoes Basicas','22 - 12','','','','','10','Resolva a operacao da esquerda para a direita: 22 - 12 = 10.','easy',0,NULL,'2026-09-04 11:30:16'),(40,3,'Operacoes Basicas','22 ├ù 11','','','','','242','Resolva a operacao da esquerda para a direita: 22 ├ù 11 = 242.','easy',0,NULL,'2026-09-04 11:30:16'),(41,3,'Operacoes Basicas','12 - 5','','','','','7','Resolva a operacao da esquerda para a direita: 12 - 5 = 7.','easy',0,NULL,'2026-09-04 11:30:16'),(42,3,'Operacoes Basicas','17 + 3','','','','','20','Resolva a operacao da esquerda para a direita: 17 + 3 = 20.','easy',0,NULL,'2026-09-04 11:30:16'),(43,3,'Operacoes Basicas','60 ├À 12','','','','','5','Resolva a operacao da esquerda para a direita: 60 ├À 12 = 5.','easy',0,NULL,'2026-09-04 11:30:16'),(44,3,'Operacoes Basicas','26 ├ù 9','','','','','234','Resolva a operacao da esquerda para a direita: 26 ├ù 9 = 234.','easy',0,NULL,'2026-09-04 11:30:16'),(45,3,'Operacoes Basicas','25 ├ù 6','','','','','150','Resolva a operacao da esquerda para a direita: 25 ├ù 6 = 150.','easy',0,NULL,'2026-09-04 11:30:16'),(46,3,'Operacoes Basicas','4 ├ù 9','','','','','36','Resolva a operacao da esquerda para a direita: 4 ├ù 9 = 36.','easy',0,NULL,'2026-09-04 11:31:34'),(47,3,'Operacoes Basicas','2 + 10','','','','','12','Resolva a operacao da esquerda para a direita: 2 + 10 = 12.','easy',0,NULL,'2026-09-04 11:31:34'),(48,3,'Operacoes Basicas','11 + 3','','','','','14','Resolva a operacao da esquerda para a direita: 11 + 3 = 14.','easy',0,NULL,'2026-09-04 11:31:34'),(49,3,'Operacoes Basicas','19 ├ù 4','','','','','76','Resolva a operacao da esquerda para a direita: 19 ├ù 4 = 76.','easy',0,NULL,'2026-09-04 11:31:34'),(50,3,'Operacoes Basicas','9 + 4','','','','','13','Resolva a operacao da esquerda para a direita: 9 + 4 = 13.','easy',0,NULL,'2026-09-04 11:31:34'),(51,3,'Operacoes Basicas','21 - 2','','','','','19','Resolva a operacao da esquerda para a direita: 21 - 2 = 19.','easy',0,NULL,'2026-09-04 11:31:34'),(52,3,'Operacoes Basicas','17 - 9','','','','','8','Resolva a operacao da esquerda para a direita: 17 - 9 = 8.','easy',0,NULL,'2026-09-04 11:31:34'),(53,3,'Operacoes Basicas','21 + 4','','','','','25','Resolva a operacao da esquerda para a direita: 21 + 4 = 25.','easy',0,NULL,'2026-09-04 11:31:34'),(54,3,'Operacoes Basicas','15 + 6','','','','','21','Resolva a operacao da esquerda para a direita: 15 + 6 = 21.','easy',0,NULL,'2026-09-04 11:31:34'),(55,3,'Operacoes Basicas','63 ├À 7','','','','','9','Resolva a operacao da esquerda para a direita: 63 ├À 7 = 9.','easy',0,NULL,'2026-09-04 11:31:34'),(56,3,'Operacoes Basicas','3 ├ù 3','','','','','9','Resolva a operacao da esquerda para a direita: 3 ├ù 3 = 9.','easy',0,NULL,'2026-09-04 11:31:58'),(57,3,'Operacoes Basicas','19 + 2','','','','','21','Resolva a operacao da esquerda para a direita: 19 + 2 = 21.','easy',0,NULL,'2026-09-04 11:31:58'),(58,3,'Operacoes Basicas','4 ├ù 9','','','','','36','Resolva a operacao da esquerda para a direita: 4 ├ù 9 = 36.','easy',0,NULL,'2026-09-04 11:31:58'),(59,3,'Operacoes Basicas','33 ├À 11','','','','','3','Resolva a operacao da esquerda para a direita: 33 ├À 11 = 3.','easy',0,NULL,'2026-09-04 11:31:58'),(60,3,'Operacoes Basicas','25 + 10','','','','','35','Resolva a operacao da esquerda para a direita: 25 + 10 = 35.','easy',0,NULL,'2026-09-04 11:31:58'),(61,3,'Operacoes Basicas','18 + 11','','','','','29','Resolva a operacao da esquerda para a direita: 18 + 11 = 29.','easy',0,NULL,'2026-09-04 11:31:58'),(62,3,'Operacoes Basicas','23 ├ù 10','','','','','230','Resolva a operacao da esquerda para a direita: 23 ├ù 10 = 230.','easy',0,NULL,'2026-09-04 11:31:58'),(63,3,'Operacoes Basicas','10 - 10','','','','','0','Resolva a operacao da esquerda para a direita: 10 - 10 = 0.','easy',0,NULL,'2026-09-04 11:31:58'),(64,3,'Operacoes Basicas','3 ├ù 5','','','','','15','Resolva a operacao da esquerda para a direita: 3 ├ù 5 = 15.','easy',0,NULL,'2026-09-04 11:31:58'),(65,3,'Operacoes Basicas','66 ├À 11','','','','','6','Resolva a operacao da esquerda para a direita: 66 ├À 11 = 6.','easy',0,NULL,'2026-09-04 11:31:58'),(66,3,'Operacoes Basicas','6 ├À 3','','','','','2','N├¡vel F├ícil: 6 ├À 3 = 2.','easy',0,NULL,'2026-09-04 11:39:16'),(67,3,'Operacoes Basicas','24 ├À 3','','','','','8','N├¡vel F├ícil: 24 ├À 3 = 8.','easy',0,NULL,'2026-09-04 11:39:16'),(68,3,'Operacoes Basicas','4 ├À 2','','','','','2','N├¡vel F├ícil: 4 ├À 2 = 2.','easy',0,NULL,'2026-09-04 11:39:16'),(69,3,'Operacoes Basicas','12 + 4','','','','','16','N├¡vel F├ícil: 12 + 4 = 16.','easy',0,NULL,'2026-09-04 11:39:16'),(70,3,'Operacoes Basicas','13 - 3','','','','','10','N├¡vel F├ícil: 13 - 3 = 10.','easy',0,NULL,'2026-09-04 11:39:16'),(71,3,'Operacoes Basicas','14 - 9','','','','','5','N├¡vel F├ícil: 14 - 9 = 5.','easy',0,NULL,'2026-09-04 11:39:16'),(72,3,'Operacoes Basicas','8 ├À 2','','','','','4','N├¡vel F├ícil: 8 ├À 2 = 4.','easy',0,NULL,'2026-09-04 11:39:16'),(73,3,'Operacoes Basicas','11 + 9','','','','','20','N├¡vel F├ícil: 11 + 9 = 20.','easy',0,NULL,'2026-09-04 11:39:16'),(74,3,'Operacoes Basicas','10 + 10','','','','','20','N├¡vel F├ícil: 10 + 10 = 20.','easy',0,NULL,'2026-09-04 11:39:16'),(75,3,'Operacoes Basicas','19 - 8','','','','','11','N├¡vel F├ícil: 19 - 8 = 11.','easy',0,NULL,'2026-09-04 11:39:17'),(76,3,'Operacoes Basicas','20 - 10','','','','','10','N├¡vel F├ícil: 20 - 10 = 10.','easy',0,NULL,'2026-09-04 11:39:17'),(77,3,'Operacoes Basicas','11 + 3','','','','','14','N├¡vel F├ícil: 11 + 3 = 14.','easy',0,NULL,'2026-09-04 11:39:17'),(78,3,'Operacoes Basicas','12 - 2','','','','','10','N├¡vel F├ícil: 12 - 2 = 10.','easy',0,NULL,'2026-09-04 11:39:56'),(79,3,'Operacoes Basicas','12 + 2','','','','','14','N├¡vel F├ícil: 12 + 2 = 14.','easy',0,NULL,'2026-09-04 11:39:56'),(80,3,'Operacoes Basicas','11 - 6','','','','','5','N├¡vel F├ícil: 11 - 6 = 5.','easy',0,NULL,'2026-09-04 11:39:56'),(81,3,'Operacoes Basicas','72 ├À 8','','','','','9','N├¡vel M├®dio: 72 ├À 8 = 9.','medium',0,NULL,'2026-09-04 11:39:56'),(82,3,'Operacoes Basicas','81 ├À 9','','','','','9','N├¡vel M├®dio: 81 ├À 9 = 9.','medium',0,NULL,'2026-09-04 11:39:56'),(83,3,'Operacoes Basicas','54 ├À 6','','','','','9','N├¡vel M├®dio: 54 ├À 6 = 9.','medium',0,NULL,'2026-09-04 11:39:56'),(84,3,'Operacoes Basicas','13 ├ù 3','','','','','39','N├¡vel Dif├¡cil: 13 ├ù 3 = 39.','hard',0,NULL,'2026-09-04 11:39:56'),(85,3,'Operacoes Basicas','124 - 46','','','','','78','N├¡vel Dif├¡cil: 124 - 46 = 78.','hard',0,NULL,'2026-09-04 11:39:56'),(86,3,'Operacoes Basicas','154 - 75','','','','','79','N├¡vel Dif├¡cil: 154 - 75 = 79.','hard',0,NULL,'2026-09-04 11:39:56'),(87,3,'Operacoes Basicas','200 - 85','','','','','115','Desafio Mestre: 200 - 85 = 115.','hard',0,NULL,'2026-09-04 11:39:56'),(88,3,'Operacoes Basicas','16 ├ù 5','','','','','80','Desafio Mestre: 16 ├ù 5 = 80.','hard',0,NULL,'2026-09-04 11:39:56'),(89,3,'Operacoes Basicas','99 + 88','','','','','187','Desafio Mestre: 99 + 88 = 187.','hard',0,NULL,'2026-09-04 11:39:56'),(90,3,'Operacoes Basicas','4 + 9','','','','','13','N├¡vel F├ícil: 4 + 9 = 13.','easy',0,NULL,'2026-09-04 11:40:33'),(91,3,'Operacoes Basicas','7 + 6','','','','','13','N├¡vel F├ícil: 7 + 6 = 13.','easy',0,NULL,'2026-09-04 11:40:33'),(92,3,'Operacoes Basicas','4 ├ù 8','','','','','32','N├¡vel F├ícil: 4 ├ù 8 = 32.','easy',0,NULL,'2026-09-04 11:40:33'),(93,3,'Operacoes Basicas','73 - 39','','','','','34','N├¡vel M├®dio: 73 - 39 = 34.','medium',0,NULL,'2026-09-04 11:40:33'),(94,3,'Operacoes Basicas','61 - 40','','','','','21','N├¡vel M├®dio: 61 - 40 = 21.','medium',0,NULL,'2026-09-04 11:40:33'),(95,3,'Operacoes Basicas','37 - 20','','','','','17','N├¡vel M├®dio: 37 - 20 = 17.','medium',0,NULL,'2026-09-04 11:40:33'),(96,3,'Operacoes Basicas','80 ├À 5','','','','','16','N├¡vel Dif├¡cil: 80 ├À 5 = 16.','hard',0,NULL,'2026-09-04 11:40:33'),(97,3,'Operacoes Basicas','90 - 36','','','','','54','N├¡vel Dif├¡cil: 90 - 36 = 54.','hard',0,NULL,'2026-09-04 11:40:33'),(98,3,'Operacoes Basicas','93 - 42','','','','','51','N├¡vel Dif├¡cil: 93 - 42 = 51.','hard',0,NULL,'2026-09-04 11:40:33'),(99,3,'Operacoes Basicas','16 ├ù 5','','','','','80','Desafio Mestre: 16 ├ù 5 = 80.','hard',0,NULL,'2026-09-04 11:40:33'),(100,3,'Operacoes Basicas','11 - 7','','','','','4','N├¡vel F├ícil: 11 - 7 = 4.','easy',0,NULL,'2026-09-04 11:40:36'),(101,3,'Operacoes Basicas','3 ├ù 6','','','','','18','N├¡vel F├ícil: 3 ├ù 6 = 18.','easy',0,NULL,'2026-09-04 11:40:36'),(102,3,'Operacoes Basicas','12 - 6','','','','','6','N├¡vel F├ícil: 12 - 6 = 6.','easy',0,NULL,'2026-09-04 11:40:36'),(103,3,'Operacoes Basicas','6 ├ù 7','','','','','42','N├¡vel M├®dio: 6 ├ù 7 = 42.','medium',0,NULL,'2026-09-04 11:40:36'),(104,3,'Operacoes Basicas','9 ├ù 7','','','','','63','N├¡vel M├®dio: 9 ├ù 7 = 63.','medium',0,NULL,'2026-09-04 11:40:36'),(105,3,'Operacoes Basicas','25 + 15','','','','','40','N├¡vel M├®dio: 25 + 15 = 40.','medium',0,NULL,'2026-09-04 11:40:36'),(106,3,'Operacoes Basicas','108 - 45','','','','','63','N├¡vel Dif├¡cil: 108 - 45 = 63.','hard',0,NULL,'2026-09-04 11:40:36'),(107,3,'Operacoes Basicas','80 ├À 5','','','','','16','N├¡vel Dif├¡cil: 80 ├À 5 = 16.','hard',0,NULL,'2026-09-04 11:40:36'),(108,3,'Operacoes Basicas','123 - 60','','','','','63','N├¡vel Dif├¡cil: 123 - 60 = 63.','hard',0,NULL,'2026-09-04 11:40:36'),(109,3,'Operacoes Basicas','144 ├À 12','','','','','12','Desafio Mestre: 144 ├À 12 = 12.','hard',0,NULL,'2026-09-04 11:40:36'),(110,3,'Operacoes Basicas','11 + 3','','','','','14','N├¡vel F├ícil: 11 + 3 = 14.','easy',0,NULL,'2026-09-04 11:40:46'),(111,3,'Operacoes Basicas','18 ├À 3','','','','','6','N├¡vel F├ícil: 18 ├À 3 = 6.','easy',0,NULL,'2026-09-04 11:40:46'),(112,3,'Operacoes Basicas','8 + 5','','','','','13','N├¡vel F├ícil: 8 + 5 = 13.','easy',0,NULL,'2026-09-04 11:40:46'),(113,3,'Operacoes Basicas','33 + 30','','','','','63','N├¡vel M├®dio: 33 + 30 = 63.','medium',0,NULL,'2026-09-04 11:40:46'),(114,3,'Operacoes Basicas','7 ├ù 4','','','','','28','N├¡vel M├®dio: 7 ├ù 4 = 28.','medium',0,NULL,'2026-09-04 11:40:46'),(115,3,'Operacoes Basicas','28 + 13','','','','','41','N├¡vel M├®dio: 28 + 13 = 41.','medium',0,NULL,'2026-09-04 11:40:46'),(116,3,'Operacoes Basicas','116 - 71','','','','','45','N├¡vel Dif├¡cil: 116 - 71 = 45.','hard',0,NULL,'2026-09-04 11:40:46'),(117,3,'Operacoes Basicas','96 ├À 3','','','','','32','N├¡vel Dif├¡cil: 96 ├À 3 = 32.','hard',0,NULL,'2026-09-04 11:40:46'),(118,3,'Operacoes Basicas','84 ├À 4','','','','','21','N├¡vel Dif├¡cil: 84 ├À 4 = 21.','hard',0,NULL,'2026-09-04 11:40:46'),(119,3,'Operacoes Basicas','99 + 88','','','','','187','Desafio Mestre: 99 + 88 = 187.','hard',0,NULL,'2026-09-04 11:40:46'),(120,3,'Operacoes Basicas','5 + 6','','','','','11','N├¡vel F├ícil: 5 + 6 = 11.','easy',0,NULL,'2026-09-04 11:40:50'),(121,3,'Operacoes Basicas','14 - 4','','','','','10','N├¡vel F├ícil: 14 - 4 = 10.','easy',0,NULL,'2026-09-04 11:40:50'),(122,3,'Operacoes Basicas','12 + 8','','','','','20','N├¡vel F├ícil: 12 + 8 = 20.','easy',0,NULL,'2026-09-04 11:40:50'),(123,3,'Operacoes Basicas','20 + 32','','','','','52','N├¡vel M├®dio: 20 + 32 = 52.','medium',0,NULL,'2026-09-04 11:40:50'),(124,3,'Operacoes Basicas','64 - 34','','','','','30','N├¡vel M├®dio: 64 - 34 = 30.','medium',0,NULL,'2026-09-04 11:40:50'),(125,3,'Operacoes Basicas','6 ├ù 7','','','','','42','N├¡vel M├®dio: 6 ├ù 7 = 42.','medium',0,NULL,'2026-09-04 11:40:50'),(126,3,'Operacoes Basicas','69 + 47','','','','','116','N├¡vel Dif├¡cil: 69 + 47 = 116.','hard',0,NULL,'2026-09-04 11:40:50'),(127,3,'Operacoes Basicas','112 - 61','','','','','51','N├¡vel Dif├¡cil: 112 - 61 = 51.','hard',0,NULL,'2026-09-04 11:40:50'),(128,3,'Operacoes Basicas','78 + 71','','','','','149','N├¡vel Dif├¡cil: 78 + 71 = 149.','hard',0,NULL,'2026-09-04 11:40:50'),(129,3,'Operacoes Basicas','50 ├ù 4','','','','','200','Desafio Mestre: 50 ├ù 4 = 200.','hard',0,NULL,'2026-09-04 11:40:50'),(130,3,'Operacoes Basicas','3 ├ù 4','','','','','12','N├¡vel F├ícil: 3 ├ù 4 = 12.','easy',0,NULL,'2026-09-04 11:41:17'),(131,3,'Operacoes Basicas','12 - 2','','','','','10','N├¡vel F├ícil: 12 - 2 = 10.','easy',0,NULL,'2026-09-04 11:41:17'),(132,3,'Operacoes Basicas','6 ├À 3','','','','','2','N├¡vel F├ícil: 6 ├À 3 = 2.','easy',0,NULL,'2026-09-04 11:41:17'),(133,3,'Operacoes Basicas','27 + 14','','','','','41','N├¡vel M├®dio: 27 + 14 = 41.','medium',0,NULL,'2026-09-04 11:41:17'),(134,3,'Operacoes Basicas','32 ├À 8','','','','','4','N├¡vel M├®dio: 32 ├À 8 = 4.','medium',0,NULL,'2026-09-04 11:41:17'),(135,3,'Operacoes Basicas','45 + 15','','','','','60','N├¡vel M├®dio: 45 + 15 = 60.','medium',0,NULL,'2026-09-04 11:41:17'),(136,3,'Operacoes Basicas','96 ├À 3','','','','','32','N├¡vel Dif├¡cil: 96 ├À 3 = 32.','hard',0,NULL,'2026-09-04 11:41:17'),(137,3,'Operacoes Basicas','12 ├ù 6','','','','','72','N├¡vel Dif├¡cil: 12 ├ù 6 = 72.','hard',0,NULL,'2026-09-04 11:41:17'),(138,3,'Operacoes Basicas','73 + 37','','','','','110','N├¡vel Dif├¡cil: 73 + 37 = 110.','hard',0,NULL,'2026-09-04 11:41:17'),(139,3,'Operacoes Basicas','25 ├ù 4','','','','','100','Desafio Mestre: 25 ├ù 4 = 100.','hard',0,NULL,'2026-09-04 11:41:17'),(140,3,'Operacoes Basicas','12 - 2','','','','','10','N├¡vel F├ícil: 12 - 2 = 10.','easy',0,NULL,'2026-09-04 11:48:09'),(141,3,'Operacoes Basicas','13 - 2','','','','','11','N├¡vel F├ícil: 13 - 2 = 11.','easy',0,NULL,'2026-09-04 11:48:09'),(142,3,'Operacoes Basicas','4 + 12','','','','','16','N├¡vel F├ícil: 4 + 12 = 16.','easy',0,NULL,'2026-09-04 11:48:09'),(143,3,'Operacoes Basicas','63 ├À 9','','','','','7','N├¡vel M├®dio: 63 ├À 9 = 7.','medium',0,NULL,'2026-09-04 11:48:09'),(144,3,'Operacoes Basicas','22 + 22','','','','','44','N├¡vel M├®dio: 22 + 22 = 44.','medium',0,NULL,'2026-09-04 11:48:09'),(145,3,'Operacoes Basicas','8 ├ù 9','','','','','72','N├¡vel M├®dio: 8 ├ù 9 = 72.','medium',0,NULL,'2026-09-04 11:48:09'),(146,3,'Operacoes Basicas','113 - 47','','','','','66','N├¡vel Dif├¡cil: 113 - 47 = 66.','hard',0,NULL,'2026-09-04 11:48:09'),(147,3,'Operacoes Basicas','74 + 42','','','','','116','N├¡vel Dif├¡cil: 74 + 42 = 116.','hard',0,NULL,'2026-09-04 11:48:09'),(148,3,'Operacoes Basicas','148 - 75','','','','','73','N├¡vel Dif├¡cil: 148 - 75 = 73.','hard',0,NULL,'2026-09-04 11:48:09'),(149,3,'Operacoes Basicas','249 - 95','','','','','154','N├¡vel Especialista: 249 - 95 = 154.','hard',0,NULL,'2026-09-04 11:48:09'),(150,3,'Operacoes Basicas','266 - 124','','','','','142','N├¡vel Especialista: 266 - 124 = 142.','hard',0,NULL,'2026-09-04 11:48:09'),(151,3,'Operacoes Basicas','168 + 119','','','','','287','N├¡vel Especialista: 168 + 119 = 287.','hard',0,NULL,'2026-09-04 11:48:09'),(152,3,'Operacoes Basicas','14 + 8','','','','','22','N├¡vel F├ícil: 14 + 8 = 22.','easy',0,NULL,'2026-09-04 11:48:39'),(153,3,'Operacoes Basicas','4 ├À 2','','','','','2','N├¡vel F├ícil: 4 ├À 2 = 2.','easy',0,NULL,'2026-09-04 11:48:39'),(154,3,'Operacoes Basicas','69 - 35','','','','','34','N├¡vel M├®dio: 69 - 35 = 34.','medium',0,NULL,'2026-09-04 11:48:39'),(155,3,'Operacoes Basicas','37 + 35','','','','','72','N├¡vel M├®dio: 37 + 35 = 72.','medium',0,NULL,'2026-09-04 11:48:39'),(156,3,'Operacoes Basicas','12 ├ù 4','','','','','48','N├¡vel Dif├¡cil: 12 ├ù 4 = 48.','hard',0,NULL,'2026-09-04 11:48:39'),(157,3,'Operacoes Basicas','98 - 51','','','','','47','N├¡vel Dif├¡cil: 98 - 51 = 47.','hard',0,NULL,'2026-09-04 11:48:39'),(158,3,'Operacoes Basicas','211 - 133','','','','','78','N├¡vel Especialista: 211 - 133 = 78.','hard',0,NULL,'2026-09-04 11:48:39'),(159,3,'Operacoes Basicas','95 + 58','','','','','153','N├¡vel Especialista: 95 + 58 = 153.','hard',0,NULL,'2026-09-04 11:48:39'),(160,3,'Operacoes Basicas','11 ├ù 15','','','','','165','N├¡vel Mestre: 11 ├ù 15 = 165.','hard',0,NULL,'2026-09-04 11:48:39'),(161,3,'Operacoes Basicas','506 - 212','','','','','294','N├¡vel Mestre: 506 - 212 = 294.','hard',0,NULL,'2026-09-04 11:48:39'),(162,3,'Operacoes Basicas','15 ├ù 15','','','','','225','N├¡vel Lenda: 15 ├ù 15 = 225.','hard',0,NULL,'2026-09-04 11:48:39'),(163,3,'Operacoes Basicas','327 + 452','','','','','779','N├¡vel Lenda: 327 + 452 = 779.','hard',0,NULL,'2026-09-04 11:48:39'),(164,3,'Operacoes Basicas','50 ├ù 8','','','','','400','N├¡vel Lenda: 50 ├ù 8 = 400.','hard',0,NULL,'2026-09-04 11:48:39'),(165,3,'Operacoes Basicas','125 ├ù 2','','','','','250','N├¡vel Lenda: 125 ├ù 2 = 250.','hard',0,NULL,'2026-09-04 11:48:39'),(166,3,'Operacoes Basicas','80 ├ù 5','','','','','400','N├¡vel Lenda: 80 ├ù 5 = 400.','hard',0,NULL,'2026-09-04 11:48:39'),(167,3,'Operacoes Basicas','800 ├À 20','','','','','40','N├¡vel Lenda: 800 ├À 20 = 40.','hard',0,NULL,'2026-09-04 11:48:39'),(168,3,'Operacoes Basicas','22 - 10','','','','','12','N├¡vel F├ícil: 22 - 10 = 12.','easy',0,NULL,'2026-09-04 11:49:29'),(169,3,'Operacoes Basicas','10 - 4','','','','','6','N├¡vel F├ícil: 10 - 4 = 6.','easy',0,NULL,'2026-09-04 11:49:29'),(170,3,'Operacoes Basicas','4 ├ù 6','','','','','24','N├¡vel F├ícil: 4 ├ù 6 = 24.','easy',0,NULL,'2026-09-04 11:49:29'),(171,3,'Operacoes Basicas','63 - 34','','','','','29','N├¡vel M├®dio: 63 - 34 = 29.','medium',0,NULL,'2026-09-04 11:50:10'),(172,3,'Operacoes Basicas','8 ├ù 9','','','','','72','N├¡vel M├®dio: 8 ├ù 9 = 72.','medium',0,NULL,'2026-09-04 11:50:11'),(173,3,'Operacoes Basicas','41 + 12','','','','','53','N├¡vel M├®dio: 41 + 12 = 53.','medium',0,NULL,'2026-09-04 11:50:14'),(174,3,'Operacoes Basicas','102 - 61','','','','','41','N├¡vel Dif├¡cil: 102 - 61 = 41.','hard',0,NULL,'2026-09-04 11:50:20'),(175,3,'Operacoes Basicas','12 ├ù 7','','','','','84','N├¡vel Dif├¡cil: 12 ├ù 7 = 84.','hard',0,NULL,'2026-09-04 11:50:25'),(176,3,'Operacoes Basicas','93 + 59','','','','','152','N├¡vel Dif├¡cil: 93 + 59 = 152.','hard',0,NULL,'2026-09-04 11:50:39'),(177,3,'Operacoes Basicas','53 + 71','','','','','124','N├¡vel Dif├¡cil: 53 + 71 = 124.','hard',0,NULL,'2026-09-04 11:50:52'),(178,3,'Operacoes Basicas','12 + 6','','','','','18','N├¡vel F├ícil: 12 + 6 = 18.','easy',0,NULL,'2026-09-04 11:51:07'),(179,3,'Operacoes Basicas','3 ├ù 3','','','','','9','N├¡vel F├ícil: 3 ├ù 3 = 9.','easy',0,NULL,'2026-09-04 11:51:07'),(180,3,'Operacoes Basicas','7 + 6','','','','','13','N├¡vel F├ícil: 7 + 6 = 13.','easy',0,NULL,'2026-09-04 11:51:08'),(181,3,'Operacoes Basicas','8 ├À 2','','','','','4','N├¡vel F├ícil: 8 ├À 2 = 4.','easy',0,NULL,'2026-09-04 11:51:53'),(182,3,'Operacoes Basicas','3 ├ù 3','','','','','9','N├¡vel F├ícil: 3 ├ù 3 = 9.','easy',0,NULL,'2026-09-04 11:51:53'),(183,3,'Operacoes Basicas','6 + 7','','','','','13','N├¡vel F├ícil: 6 + 7 = 13.','easy',0,NULL,'2026-09-04 11:51:53'),(184,3,'Operacoes Basicas','18 ├À 3','','','','','6','N├¡vel F├ícil: 18 ├À 3 = 6.','easy',0,NULL,'2026-09-04 11:51:58'),(185,3,'Operacoes Basicas','15 ├À 3','','','','','5','N├¡vel F├ícil: 15 ├À 3 = 5.','easy',0,NULL,'2026-09-04 11:51:58'),(186,3,'Operacoes Basicas','3 ├ù 5','','','','','15','N├¡vel F├ícil: 3 ├ù 5 = 15.','easy',0,NULL,'2026-09-04 11:51:58'),(187,3,'Operacoes Basicas','13 - 10','','','','','3','N├¡vel F├ícil: 13 - 10 = 3.','easy',0,NULL,'2026-09-04 11:52:41'),(188,3,'Operacoes Basicas','14 - 4','','','','','10','N├¡vel F├ícil: 14 - 4 = 10.','easy',0,NULL,'2026-09-04 11:52:41'),(189,3,'Operacoes Basicas','19 - 9','','','','','10','N├¡vel F├ícil: 19 - 9 = 10.','easy',0,NULL,'2026-09-04 11:52:41'),(190,3,'Operacoes Basicas','3 ├ù 8','','','','','24','N├¡vel F├ícil: 3 ├ù 8 = 24.','easy',0,NULL,'2026-09-04 11:52:43'),(191,3,'Operacoes Basicas','6 + 8','','','','','14','N├¡vel F├ícil: 6 + 8 = 14.','easy',0,NULL,'2026-09-04 11:52:44'),(192,3,'Operacoes Basicas','20 ├À 4','','','','','5','N├¡vel F├ícil: 20 ├À 4 = 5.','easy',0,NULL,'2026-09-04 11:52:44'),(193,3,'Operacoes Basicas','2 ├ù 5','','','','','10','N├¡vel F├ícil: 2 ├ù 5 = 10.','easy',0,NULL,'2026-09-04 11:52:44'),(194,3,'Operacoes Basicas','14 + 12','','','','','26','N├¡vel F├ícil: 14 + 12 = 26.','easy',0,NULL,'2026-09-04 11:52:45'),(195,3,'Operacoes Basicas','8 + 4','','','','','12','N├¡vel F├ícil: 8 + 4 = 12.','easy',0,NULL,'2026-09-04 11:52:45'),(196,3,'Operacoes Basicas','4 ├ù 5','','','','','20','N├¡vel F├ícil: 4 ├ù 5 = 20.','easy',0,NULL,'2026-09-04 11:52:45'),(197,3,'Operacoes Basicas','12 - 3','','','','','9','N├¡vel F├ícil: 12 - 3 = 9.','easy',0,NULL,'2026-09-04 11:54:02'),(198,3,'Operacoes Basicas','35 ├À 5','','','','','7','N├¡vel F├ícil: 35 ├À 5 = 7.','easy',0,NULL,'2026-09-04 11:54:02'),(199,3,'Operacoes Basicas','5 ├ù 4','','','','','20','N├¡vel F├ícil: 5 ├ù 4 = 20.','easy',0,NULL,'2026-09-04 11:54:02'),(200,3,'Operacoes Basicas','8 ├À 4','','','','','2','N├¡vel F├ícil: 8 ├À 4 = 2.','easy',0,NULL,'2026-09-04 11:54:55'),(201,3,'Operacoes Basicas','14 - 10','','','','','4','N├¡vel F├ícil: 14 - 10 = 4.','easy',0,NULL,'2026-09-04 11:54:55'),(202,3,'Operacoes Basicas','11 - 5','','','','','6','N├¡vel F├ícil: 11 - 5 = 6.','easy',0,NULL,'2026-09-04 11:54:55'),(203,3,'Operacoes Basicas','41 - 26','','','','','15','N├¡vel M├®dio: 41 - 26 = 15.','medium',0,NULL,'2026-09-04 11:55:01'),(204,3,'Operacoes Basicas','50 - 35','','','','','15','N├¡vel M├®dio: 50 - 35 = 15.','medium',0,NULL,'2026-09-04 11:55:04'),(205,3,'Operacoes Basicas','72 ├À 9','','','','','8','N├¡vel M├®dio: 72 ├À 9 = 8.','medium',0,NULL,'2026-09-04 11:55:09'),(206,3,'Operacoes Basicas','12 ├À 3','','','','','4','N├¡vel F├ícil: 12 ├À 3 = 4.','easy',0,NULL,'2026-09-04 11:55:27'),(207,3,'Operacoes Basicas','10 + 7','','','','','17','N├¡vel F├ícil: 10 + 7 = 17.','easy',0,NULL,'2026-09-04 11:55:27'),(208,3,'Operacoes Basicas','11 + 6','','','','','17','N├¡vel F├ícil: 11 + 6 = 17.','easy',0,NULL,'2026-09-04 11:55:27'),(209,3,'Operacoes Basicas','9 ├ù 9','','','','','81','N├¡vel M├®dio: 9 ├ù 9 = 81.','medium',0,NULL,'2026-09-04 11:55:34'),(210,3,'Operacoes Basicas','7 + 4','','','','','11','N├¡vel F├ícil: 7 + 4 = 11.','easy',0,NULL,'2026-09-04 11:55:39'),(211,3,'Operacoes Basicas','7 + 3','','','','','10','N├¡vel F├ícil: 7 + 3 = 10.','easy',0,NULL,'2026-09-04 11:55:39'),(212,3,'Operacoes Basicas','25 ├À 5','','','','','5','N├¡vel F├ícil: 25 ├À 5 = 5.','easy',0,NULL,'2026-09-04 11:55:39'),(213,3,'Operacoes Basicas','63 - 37','','','','','26','N├¡vel M├®dio: 63 - 37 = 26.','medium',0,NULL,'2026-09-04 11:55:41'),(214,3,'Operacoes Basicas','64 ├À 8','','','','','8','N├¡vel M├®dio: 64 ├À 8 = 8.','medium',0,NULL,'2026-09-04 11:55:43'),(215,3,'Operacoes Basicas','54 - 37','','','','','17','N├¡vel M├®dio: 54 - 37 = 17.','medium',0,NULL,'2026-09-04 11:55:45'),(216,3,'Operacoes Basicas','64 + 38','','','','','102','N├¡vel Dif├¡cil: 64 + 38 = 102.','hard',0,NULL,'2026-09-04 11:56:01'),(217,3,'Operacoes Basicas','13 ├ù 6','','','','','78','N├¡vel Dif├¡cil: 13 ├ù 6 = 78.','hard',0,NULL,'2026-09-04 11:56:07'),(218,3,'Operacoes Basicas','13 ├ù 3','','','','','39','N├¡vel Dif├¡cil: 13 ├ù 3 = 39.','hard',0,NULL,'2026-09-04 11:56:16'),(219,3,'Operacoes Basicas','115 - 75','','','','','40','N├¡vel Dif├¡cil: 115 - 75 = 40.','hard',0,NULL,'2026-09-04 11:56:29'),(220,3,'Operacoes Basicas','6 ├À 3','','','','','2','N├¡vel F├ícil: 6 ├À 3 = 2.','easy',0,NULL,'2026-09-04 11:57:27'),(221,3,'Operacoes Basicas','15 + 11','','','','','26','N├¡vel F├ícil: 15 + 11 = 26.','easy',0,NULL,'2026-09-04 11:57:27'),(222,3,'Operacoes Basicas','40 ├À 5','','','','','8','N├¡vel F├ícil: 40 ├À 5 = 8.','easy',0,NULL,'2026-09-04 11:57:27'),(223,3,'Operacoes Basicas','9 ├ù 5','','','','','45','N├¡vel M├®dio: 9 ├ù 5 = 45.','medium',0,NULL,'2026-09-04 11:57:58'),(224,3,'Operacoes Basicas','6 + 12','','','','','18','N├¡vel F├ícil: 6 + 12 = 18.','easy',0,NULL,'2026-09-04 11:58:06'),(225,3,'Operacoes Basicas','9 - 4','','','','','5','N├¡vel F├ícil: 9 - 4 = 5.','easy',0,NULL,'2026-09-04 11:58:06'),(226,3,'Operacoes Basicas','4 + 10','','','','','14','N├¡vel F├ícil: 4 + 10 = 14.','easy',0,NULL,'2026-09-04 11:58:06'),(227,1,'Fracoes e Razoes','[Refor├ºo] Se voc├¬ dividir uma barra de chocolate em 10 peda├ºos e comer 4, qual fra├º├úo representa a parte que sobrou?','4/10','6/10 (ou 3/5)','1/2','2/5','B','O total ├® 10/10. Subtraindo a parte consumida: 10/10 - 4/10 = 6/10, simplificando por 2 fica 3/5.','easy',1,NULL,'2026-09-04 17:42:27'),(228,1,'Fracoes e Razoes','[Refor├ºo] Qual fra├º├úo ├® equivalente a 3/4?','6/8','6/4','3/8','9/16','A','Multiplicando numerador e denominador por 2: 3/4 = (3├ù2)/(4├ù2) = 6/8.','easy',1,NULL,'2026-09-04 17:42:27'),(229,1,'Fracoes e Razoes','[Refor├ºo] Qual ├® o resultado da soma 2/5 + 1/5?','3/10','3/5','2/25','1/5','B','Com denominadores iguais, mantemos o denominador 5 e somamos os numeradores: 2 + 1 = 3/5.','easy',1,NULL,'2026-09-04 17:42:27'),(230,3,'Operacoes Basicas','10 ├À 2','','','','','5','N├¡vel F├ícil: 10 ├À 2 = 5.','easy',0,NULL,'2026-09-09 10:52:14'),(231,1,'Fracoes e Razoes','[Refor├ºo] Se voc├¬ dividir uma barra de chocolate em 10 peda├ºos e comer 4, qual fra├º├úo representa a parte que sobrou?','4/10','6/10 (ou 3/5)','1/2','2/5','B','O total ├® 10/10. Subtraindo a parte consumida: 10/10 - 4/10 = 6/10, simplificando por 2 fica 3/5.','easy',1,NULL,'2026-09-09 10:52:15'),(232,1,'Fracoes e Razoes','[Refor├ºo] Qual fra├º├úo ├® equivalente a 3/4?','6/8','6/4','3/8','9/16','A','Multiplicando numerador e denominador por 2: 3/4 = (3├ù2)/(4├ù2) = 6/8.','easy',1,NULL,'2026-09-09 10:52:15'),(233,1,'Fracoes e Razoes','[Refor├ºo] Qual ├® o resultado da soma 2/5 + 1/5?','3/10','3/5','2/25','1/5','B','Com denominadores iguais, mantemos o denominador 5 e somamos os numeradores: 2 + 1 = 3/5.','easy',1,NULL,'2026-09-09 10:52:15'),(234,3,'Operacoes Basicas','12 - 7','','','','','5','­ƒÆí Opera├º├úo Inversa: quanto falta de 7 para 12? 7 + 5 = 12, portanto 12 ÔêÆ 7 = 5.','easy',0,NULL,'2026-10-07 19:23:46'),(235,3,'Operacoes Basicas','4 ├ù 7','','','','','28','­ƒÆí T├®cnica do Dobro do Dobro: dobro de 7 ├® 14, e dobro de 14 ├® 28.','easy',0,NULL,'2026-10-07 19:23:46'),(236,3,'Operacoes Basicas','3 ├ù 4','','','','','12','­ƒÆí T├®cnica do Dobro + 1x: calcule o dobro (4 ├ù 2 = 8) e some mais 4 (8 + 4 = 12).','easy',0,NULL,'2026-10-07 19:23:47'),(237,3,'Operacoes Basicas','37 + 29','','','','','66','­ƒÆí T├®cnica da Compensa├º├úo: 37 + 29 = 37 + 30 ÔêÆ 1 = 67 ÔêÆ 1 = 66.','medium',0,NULL,'2026-10-07 19:24:13'),(238,3,'Operacoes Basicas','28 + 22','','','','','50','­ƒÆí T├®cnica da Compensa├º├úo: 28 + 22 = 22 + 30 ÔêÆ 2 = 52 ÔêÆ 2 = 50.','medium',0,NULL,'2026-10-07 19:24:22'),(239,3,'Operacoes Basicas','19 + 21','','','','','40','­ƒÆí T├®cnica da Compensa├º├úo: 19 + 21 = 21 + 20 ÔêÆ 1 = 41 ÔêÆ 1 = 40.','medium',0,NULL,'2026-10-07 19:24:28'),(240,3,'Operacoes Basicas','96 ├À 3','','','','','32','­ƒÆí Opera├º├úo Inversa: pense qual n├║mero vezes 3 resulta em 96 (3 ├ù 32 = 96). Portanto, 96 ├À 3 = 32.','hard',0,NULL,'2026-10-07 19:24:45'),(241,3,'Operacoes Basicas','32 ├À 4','','','','','8','­ƒÆí Metade da Metade: a metade de 32 ├® 16, e a metade de 16 ├® 8.','easy',0,NULL,'2026-10-07 19:44:46'),(242,3,'Operacoes Basicas','8 + 8','','','','','16','­ƒÆí T├®cnica da Compensa├º├úo: 8 + 8 = 8 + 10 ÔêÆ 2 = 18 ÔêÆ 2 = 16.','easy',0,NULL,'2026-10-07 19:44:46'),(243,3,'Operacoes Basicas','11 - 7','','','','','4','­ƒÆí Opera├º├úo Inversa: quanto falta de 7 para 11? 7 + 4 = 11, portanto 11 ÔêÆ 7 = 4.','easy',0,NULL,'2026-10-07 19:44:46'),(244,3,'Operacoes Basicas','8 ├ù 9','','','','','72','­ƒÆí Regra do 9: multiplique por 10 e subtraia 1x (8 ├ù 10 ÔêÆ 8 = 80 ÔêÆ 8 = 72).','medium',0,NULL,'2026-10-07 19:44:49'),(245,3,'Operacoes Basicas','36 + 19','','','','','55','­ƒÆí T├®cnica da Compensa├º├úo: 36 + 19 = 36 + 20 ÔêÆ 1 = 56 ÔêÆ 1 = 55.','medium',0,NULL,'2026-10-07 19:44:51'),(246,3,'Operacoes Basicas','8 ├ù 4','','','','','32','­ƒÆí T├®cnica do Dobro do Dobro: dobro de 8 ├® 16, e dobro de 16 ├® 32.','medium',0,NULL,'2026-10-07 19:44:55'),(247,3,'Operacoes Basicas','92 + 42','','','','','134','­ƒÆí Decomposi├º├úo: some dezenas (90 + 40 = 130) e unidades (2 + 2 = 4) ÔåÆ 130 + 4 = 134.','hard',0,NULL,'2026-10-07 19:45:02'),(248,3,'Operacoes Basicas','15 ├ù 3','','','','','45','­ƒÆí T├®cnica do Dobro + 1x: calcule o dobro (15 ├ù 2 = 30) e some mais 15 (30 + 15 = 45).','hard',0,NULL,'2026-10-07 19:45:16'),(249,3,'Operacoes Basicas','79 + 71','','','','','150','­ƒÆí T├®cnica da Compensa├º├úo: 79 + 71 = 71 + 80 ÔêÆ 1 = 151 ÔêÆ 1 = 150.','hard',0,NULL,'2026-10-07 19:45:19'),(250,3,'Operacoes Basicas','125 - 50','','','','','75','­ƒÆí Subtra├º├úo em 2 Etapas: subtraia a dezena (125 ÔêÆ 50 = 75) e depois a unidade (75 ÔêÆ 0 = 75).','hard',0,NULL,'2026-10-07 19:45:32'),(251,3,'Operacoes Basicas','107 + 67','','','','','174','­ƒÆí T├®cnica da Compensa├º├úo: 107 + 67 = 107 + 70 ÔêÆ 3 = 177 ÔêÆ 3 = 174.','hard',0,NULL,'2026-10-07 19:45:37'),(252,3,'Operacoes Basicas','238 - 124','','','','','114','­ƒÆí Subtra├º├úo em 2 Etapas: subtraia a dezena (238 ÔêÆ 120 = 118) e depois a unidade (118 ÔêÆ 4 = 114).','hard',0,NULL,'2026-10-07 19:45:53'),(253,3,'Operacoes Basicas','14 + 3','','','','','17','­ƒÆí Decomposi├º├úo: some dezenas (10 + 0 = 10) e unidades (4 + 3 = 7) ÔåÆ 10 + 7 = 17.','easy',0,NULL,'2026-10-21 14:49:44'),(254,3,'Operacoes Basicas','5 ├ù 6','','','','','30','­ƒÆí Regra do 5 (Metade de 10): multiplique por 10 (6 ├ù 10 = 60) e divida pela metade (60 ├À 2 = 30).','easy',0,NULL,'2026-10-21 14:49:44'),(255,3,'Operacoes Basicas','18 - 8','','','','','10','­ƒÆí Opera├º├úo Inversa: quanto falta de 8 para 18? 8 + 10 = 18, portanto 18 ÔêÆ 8 = 10.','easy',0,NULL,'2026-10-21 14:49:44'),(256,3,'Operacoes Basicas','2 + 2','','','','','4','­ƒÆí Contagem ├ügil: comece no 2 e some 2 = 4.','easy',0,NULL,'2026-10-21 15:05:11');

/*!40000 ALTER TABLE `questions` ENABLE KEYS */;

DROP TABLE IF EXISTS `user_achievements`;

/*!40101 SET @saved_cs_client     = @@character_set_client */;

/*!50503 SET character_set_client = utf8mb4 */;

CREATE TABLE `user_achievements` (

  `user_id` int NOT NULL,

  `achievement_id` int NOT NULL,

  `unlocked_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`user_id`,`achievement_id`),

  KEY `fk_uach_ach` (`achievement_id`),

  CONSTRAINT `fk_uach_ach` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE,

  CONSTRAINT `fk_uach_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `user_achievements` DISABLE KEYS */;

INSERT INTO `user_achievements` VALUES (1,1,'2026-10-21 14:50:22'),(1,3,'2026-10-21 14:50:22'),(1,4,'2026-10-21 14:50:22'),(1,5,'2026-10-21 14:50:22'),(1,6,'2026-10-21 14:50:22');

/*!40000 ALTER TABLE `user_achievements` ENABLE KEYS */;

DROP TABLE IF EXISTS `users`;

/*!40101 SET @saved_cs_client     = @@character_set_client */;

/*!50503 SET character_set_client = utf8mb4 */;

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

  PRIMARY KEY (`id`),

  UNIQUE KEY `email` (`email`)

) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `users` DISABLE KEYS */;

INSERT INTO `users` VALUES (1,'Professor Carlos Silva','professor@mathplay.com','$2y$10$R7yXgWjvivBA1Zrk9YfTbOfBs9EWpijEoOdnHcx5xIs750C9a3TQK','teacher',NULL,7,696,'2026-09-02 19:33:25','#4F46E5');

/*!40000 ALTER TABLE `users` ENABLE KEYS */;

CREATE TABLE `classes` (

  `id` int NOT NULL AUTO_INCREMENT,

  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,

  `max_students` int NOT NULL DEFAULT '30',

  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  UNIQUE KEY `name` (`name`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `classes` (`name`) VALUES ('7 A'),('7 B');

ALTER TABLE `users` ADD COLUMN `class_id` int DEFAULT NULL;

ALTER TABLE `users`
  ADD KEY `fk_users_class` (`class_id`),
  ADD CONSTRAINT `fk_users_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL;

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

ALTER TABLE `questions` ADD COLUMN `recovery_id` int DEFAULT NULL;

ALTER TABLE `questions`
  ADD KEY `fk_questions_recovery` (`recovery_id`),
  ADD CONSTRAINT `fk_questions_recovery` FOREIGN KEY (`recovery_id`) REFERENCES `recoveries` (`id`) ON DELETE CASCADE;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;

/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;

/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;

/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;

/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


