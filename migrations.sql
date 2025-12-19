-- ============================================
-- MIGRAÇÕES PARA PRODUÇÃO
-- Data: 2025-01-XX
-- ============================================

-- ============================================
-- 1. CRIAÇÃO DA TABELA documentos
-- ============================================
CREATE TABLE IF NOT EXISTS `documentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `participante_id` int NOT NULL,
  `nome_arquivo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `caminho` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Tipo do arquivo (image/jpeg, application/pdf, etc)',
  `tamanho` int DEFAULT NULL COMMENT 'Tamanho em bytes',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `participante_id` (`participante_id`),
  CONSTRAINT `documentos_ibfk_1` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 2. ADIÇÃO DE CAMPOS DE ASSINATURA NA TABELA inscricoes
-- ============================================
-- Nota: Execute apenas se os campos ainda não existirem
-- Verifique antes de executar: DESCRIBE inscricoes;

ALTER TABLE `inscricoes`
ADD COLUMN `assinatura` TEXT NULL COMMENT 'Assinatura digital em base64' AFTER `obs_salvo_em`,
ADD COLUMN `assinatura_data` DATETIME NULL COMMENT 'Data e hora da assinatura' AFTER `assinatura`;

-- ============================================
-- 3. ADIÇÃO DE CAMPOS PARA CONTROLE DE VINCULAÇÃO DE RITUAIS
-- ============================================
ALTER TABLE `participantes`
ADD COLUMN `pode_vincular_rituais` ENUM('Sim','Não') DEFAULT 'Sim' COMMENT 'Permite vincular participante a novos rituais' AFTER `nascimento`,
ADD COLUMN `motivo_bloqueio_vinculacao` TEXT NULL COMMENT 'Motivo pelo qual não pode ser vinculado a novos rituais' AFTER `pode_vincular_rituais`;

