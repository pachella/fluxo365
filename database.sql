-- ============================================
-- Sistema Base - Fluxo365
-- Versão: 1.0.0
-- Estrutura SQL Completa
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================
-- Tabela: users
-- Descrição: Usuários do sistema
-- ============================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','client','affiliate') NOT NULL DEFAULT 'client',
  `plan` enum('FREE','PRO','FULL') DEFAULT 'FREE',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  KEY `status` (`status`),
  KEY `idx_email_status` (`email`, `status`),
  KEY `idx_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Dados Iniciais
-- ============================================

-- Usuário Administrador Padrão
-- Email: admin@fluxo365.com
-- Senha: admin123
-- IMPORTANTE: Altere a senha após o primeiro login!

INSERT INTO `users` (`name`, `email`, `password`, `role`, `plan`, `status`, `created_at`) VALUES
('Administrador', 'admin@fluxo365.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'FULL', 'active', NOW());

-- ============================================
-- Configurações Finais
-- ============================================

-- Commit das alterações
COMMIT;
