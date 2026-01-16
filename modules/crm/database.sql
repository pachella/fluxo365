-- ========================================
-- MÓDULO CRM - KANBAN SYSTEM
-- ========================================

-- Tabela de Quadros (Boards)
CREATE TABLE IF NOT EXISTS `crm_boards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#6366f1',
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `crm_boards_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Colunas (Status/Stages)
CREATE TABLE IF NOT EXISTS `crm_columns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `board_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `color` varchar(7) DEFAULT '#64748b',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `board_id` (`board_id`),
  CONSTRAINT `crm_columns_board_fk` FOREIGN KEY (`board_id`) REFERENCES `crm_boards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Cards (Tarefas/Leads)
CREATE TABLE IF NOT EXISTS `crm_cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `column_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `assigned_to` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `column_id` (`column_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `crm_cards_column_fk` FOREIGN KEY (`column_id`) REFERENCES `crm_columns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_cards_assigned_fk` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_cards_creator_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Tags
CREATE TABLE IF NOT EXISTS `crm_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `board_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#6366f1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `board_id` (`board_id`),
  CONSTRAINT `crm_tags_board_fk` FOREIGN KEY (`board_id`) REFERENCES `crm_boards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de relacionamento Card-Tag (N:N)
CREATE TABLE IF NOT EXISTS `crm_card_tags` (
  `card_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`card_id`, `tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `crm_card_tags_card_fk` FOREIGN KEY (`card_id`) REFERENCES `crm_cards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_card_tags_tag_fk` FOREIGN KEY (`tag_id`) REFERENCES `crm_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados iniciais de exemplo (opcional)
-- Você pode descomentar abaixo para criar um quadro de exemplo

/*
-- Inserir quadro de exemplo
INSERT INTO `crm_boards` (`name`, `description`, `color`, `user_id`)
VALUES ('Pipeline de Vendas', 'Acompanhamento de leads e oportunidades', '#6366f1', 1);

-- Inserir colunas padrão (substitua @board_id pelo ID do quadro criado)
SET @board_id = LAST_INSERT_ID();

INSERT INTO `crm_columns` (`board_id`, `name`, `position`, `color`) VALUES
(@board_id, 'Lead', 0, '#64748b'),
(@board_id, 'Qualificado', 1, '#3b82f6'),
(@board_id, 'Proposta', 2, '#8b5cf6'),
(@board_id, 'Negociação', 3, '#f59e0b'),
(@board_id, 'Ganho', 4, '#10b981'),
(@board_id, 'Perdido', 5, '#ef4444');

-- Inserir tags de exemplo
INSERT INTO `crm_tags` (`board_id`, `name`, `color`) VALUES
(@board_id, 'Urgente', '#ef4444'),
(@board_id, 'Alto Valor', '#10b981'),
(@board_id, 'Follow-up', '#f59e0b'),
(@board_id, 'Frio', '#64748b');
*/
