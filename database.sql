-- ============================================
-- Sistema de Participantes - Instituto Céu Interior
-- Estrutura do Banco de Dados (Versão Limpa)
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================
-- ESTRUTURA DAS TABELAS
-- ============================================

--
-- Estrutura para tabela `perfis`
--
CREATE TABLE `perfis` (
  `id` int NOT NULL,
  `nome` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dados essenciais para tabela `perfis`
--
INSERT INTO `perfis` (`id`, `nome`, `descricao`) VALUES
(1, 'Administrador', 'Acesso completo ao sistema'),
(2, 'Usuário', 'Acesso limitado ao sistema');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--
CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `senha` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `perfil_id` int DEFAULT '2'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Usuário administrador padrão (senha: @Dmin123)
--
INSERT INTO `usuarios` (`id`, `nome`, `usuario`, `email`, `senha`, `perfil_id`) VALUES
(1, 'Administrador', 'admin', 'admin@testmail.com', '65fd55a424e86141506cb54c8ad2f55ac3fd10ebfd3f7847a958512824439cea', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `participantes`
--
CREATE TABLE `participantes` (
  `id` int NOT NULL,
  `foto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nome_completo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nascimento` date NOT NULL,
  `sexo` enum('M','F') COLLATE utf8mb4_general_ci NOT NULL,
  `cpf` varchar(14) COLLATE utf8mb4_general_ci NOT NULL,
  `rg` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `passaporte` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `celular` varchar(15) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `como_soube` text COLLATE utf8mb4_general_ci,
  `cep` varchar(9) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `endereco_rua` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `endereco_numero` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `endereco_complemento` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cidade` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` varchar(2) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bairro` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sobre_participante` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `rituais`
--
CREATE TABLE `rituais` (
  `id` int NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `data_ritual` date NOT NULL,
  `foto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `padrinho_madrinha` enum('Dirceu','Gabriela','Dirceu e Gabriela') COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `inscricoes`
--
CREATE TABLE `inscricoes` (
  `id` int NOT NULL,
  `ritual_id` int NOT NULL,
  `participante_id` int NOT NULL,
  `primeira_vez_instituto` enum('Sim','Não') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `primeira_vez_ayahuasca` enum('Sim','Não') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `doenca_psiquiatrica` enum('Sim','Não') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nome_doenca` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `uso_medicao` enum('Sim','Não') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nome_medicao` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mensagem` text COLLATE utf8mb4_general_ci,
  `observacao` text COLLATE utf8mb4_general_ci,
  `presente` enum('Sim','Não') COLLATE utf8mb4_general_ci DEFAULT 'Não',
  `salvo_em` datetime DEFAULT NULL,
  `obs_salvo_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `password_recovery_tokens`
--
CREATE TABLE `password_recovery_tokens` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `remember_tokens`
--
CREATE TABLE `remember_tokens` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- ÍNDICES E CHAVES PRIMÁRIAS
-- ============================================

--
-- Índices de tabela `perfis`
--
ALTER TABLE `perfis`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_usuario_perfil` (`perfil_id`);

--
-- Índices de tabela `participantes`
--
ALTER TABLE `participantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- Índices de tabela `rituais`
--
ALTER TABLE `rituais`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `inscricoes`
--
ALTER TABLE `inscricoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ritual_id` (`ritual_id`),
  ADD KEY `participante_id` (`participante_id`);

--
-- Índices de tabela `password_recovery_tokens`
--
ALTER TABLE `password_recovery_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `expires_at` (`expires_at`),
  ADD KEY `idx_cleanup` (`expires_at`,`created_at`);

--
-- Índices de tabela `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires_at` (`expires_at`);

-- ============================================
-- AUTO_INCREMENT
-- ============================================

ALTER TABLE `perfis`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `participantes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `rituais`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `inscricoes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `password_recovery_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `remember_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

-- ============================================
-- CHAVES ESTRANGEIRAS
-- ============================================

--
-- Restrições para tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuario_perfil` FOREIGN KEY (`perfil_id`) REFERENCES `perfis` (`id`);

--
-- Restrições para tabela `inscricoes`
--
ALTER TABLE `inscricoes`
  ADD CONSTRAINT `inscricoes_ibfk_1` FOREIGN KEY (`ritual_id`) REFERENCES `rituais` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inscricoes_ibfk_2` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabela `password_recovery_tokens`
--
ALTER TABLE `password_recovery_tokens`
  ADD CONSTRAINT `password_recovery_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabela `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

-- ============================================
-- EVENTOS DE LIMPEZA AUTOMÁTICA
-- ============================================

DELIMITER $$

--
-- Evento para limpar tokens de "lembrar" expirados
--
CREATE EVENT IF NOT EXISTS `cleanup_expired_tokens`
ON SCHEDULE EVERY 1 WEEK
ON COMPLETION NOT PRESERVE
ENABLE
DO BEGIN
    DELETE FROM remember_tokens WHERE expires_at < NOW();
END$$

--
-- Evento para limpar tokens de recuperação expirados
--
CREATE EVENT IF NOT EXISTS `cleanup_recovery_tokens`
ON SCHEDULE EVERY 1 WEEK
ON COMPLETION NOT PRESERVE
ENABLE
DO BEGIN
    DELETE FROM password_recovery_tokens WHERE expires_at < NOW();
END$$

DELIMITER ;

-- ============================================
-- FINALIZAÇÃO
-- ============================================

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ============================================
-- CONFIGURAÇÃO INICIAL
-- ============================================
--
-- APÓS IMPORTAR ESTE SQL:
-- 1. Altere a senha do usuário admin no sistema
-- 2. Configure seu arquivo .env com as credenciais corretas
-- 3. Ajuste as permissões da pasta storage/uploads/
-- 4. Configure o reCAPTCHA no .env se necessário
--
-- Login padrão:
-- Usuário: admin
-- Senha: @Dmin123
--
-- IMPORTANTE: Altere a senha após o primeiro login!
-- ============================================