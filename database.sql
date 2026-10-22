-- ==========================================================
-- Banco de Dados: chaveamento_db
-- Sistema de Chaveamento Esportivo Automático
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `chaveamento_db` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `chaveamento_db`;

-- 1. Tabela de Usuários (com senhas em hash bcrypt)
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `tipo` ENUM('admin', 'organizador') DEFAULT 'admin',
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabela de Torneios
CREATE TABLE IF NOT EXISTS `torneios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(150) NOT NULL,
  `modalidade` VARCHAR(80) NOT NULL,
  `formato` VARCHAR(50) DEFAULT 'mata_mata',
  `descricao` TEXT NULL,
  `status` ENUM('planejado', 'em_andamento', 'finalizado') DEFAULT 'planejado',
  `criado_por` INT NOT NULL,
  `campeao_id` INT NULL,
  `vice_id` INT NULL,
  `terceiro_id` INT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `finalizado_em` DATETIME NULL,
  CONSTRAINT `fk_torneio_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabela de Participantes / Equipes
CREATE TABLE IF NOT EXISTS `participantes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `torneio_id` INT NOT NULL,
  `nome` VARCHAR(100) NOT NULL,
  `numero_semente` INT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_participante_torneio` FOREIGN KEY (`torneio_id`) REFERENCES `torneios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabela de Partidas (Chaveamento em Árvore)
CREATE TABLE IF NOT EXISTS `partidas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `torneio_id` INT NOT NULL,
  `rodada` INT NOT NULL,
  `nome_rodada` VARCHAR(50) NOT NULL,
  `ordem_partida` INT NOT NULL,
  `participante1_id` INT NULL,
  `participante2_id` INT NULL,
  `placar1` INT NULL,
  `placar2` INT NULL,
  `penaltis1` INT NULL,
  `penaltis2` INT NULL,
  `vencedor_id` INT NULL,
  `status` ENUM('pendente', 'em_andamento', 'finalizada', 'bye') DEFAULT 'pendente',
  `proxima_partida_id` INT NULL,
  `proximo_slot` VARCHAR(20) NULL,
  `atualizado_por` INT NULL,
  `atualizado_em` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_partida_torneio` FOREIGN KEY (`torneio_id`) REFERENCES `torneios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_partida_p1` FOREIGN KEY (`participante1_id`) REFERENCES `participantes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_partida_p2` FOREIGN KEY (`participante2_id`) REFERENCES `participantes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_partida_vencedor` FOREIGN KEY (`vencedor_id`) REFERENCES `participantes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabela de Logs e Auditoria Completa
CREATE TABLE IF NOT EXISTS `logs_auditoria` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NULL,
  `usuario_nome` VARCHAR(100) NULL,
  `torneio_id` INT NULL,
  `acao` VARCHAR(60) NOT NULL,
  `descricao` TEXT NOT NULL,
  `dados_anteriores` TEXT NULL,
  `dados_novos` TEXT NULL,
  `ip` VARCHAR(45) NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_logs_torneio` FOREIGN KEY (`torneio_id`) REFERENCES `torneios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuário Administrador Padrão (Senha: admin123 criptografada com Bcrypt)
INSERT INTO `usuarios` (`nome`, `email`, `senha`, `tipo`) 
VALUES ('Administrador Geral', 'admin@etec.sp.gov.br', '$2y$10$w8W7hT4LqJ2Yf3g1k7h1e.j9Yt7qCgYdJvK9vX0uF6l5d3b6a9eOi', 'admin')
ON DUPLICATE KEY UPDATE `id`=`id`;
