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

-- ============================================
-- 4. STATUS DO PARTICIPANTE (substitui pode_vincular_rituais)
-- Execute após a migration 3. Faça backup antes em produção.
-- ============================================

ALTER TABLE `participantes`
ADD COLUMN `status` ENUM('ativo','inativo','nao_pode_participar') NOT NULL DEFAULT 'ativo'
  COMMENT 'Status operacional do participante' AFTER `nascimento`;

-- Migrar dados legados (se migration 3 já foi aplicada)
UPDATE `participantes` SET `status` = 'nao_pode_participar' WHERE `pode_vincular_rituais` = 'Não';
UPDATE `participantes` SET `status` = 'ativo' WHERE `pode_vincular_rituais` = 'Sim' OR `pode_vincular_rituais` IS NULL;

ALTER TABLE `participantes`
CHANGE COLUMN `motivo_bloqueio_vinculacao` `motivo_status` TEXT NULL
  COMMENT 'Motivo/observação para inativo ou não pode participar';

UPDATE `participantes`
SET `motivo_status` = 'Não pode participar'
WHERE `status` = 'nao_pode_participar'
  AND (`motivo_status` IS NULL OR TRIM(`motivo_status`) = '');

ALTER TABLE `participantes` DROP COLUMN `pode_vincular_rituais`;

