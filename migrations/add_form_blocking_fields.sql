-- ============================================
-- Migração: Adicionar campos de bloqueio de formulário
-- Data: 2025-01-XX
-- ============================================

-- Adicionar campos de bloqueio na tabela forms
ALTER TABLE forms
ADD COLUMN blocking_enabled TINYINT(1) DEFAULT 0 AFTER status,
ADD COLUMN blocking_type ENUM('date', 'responses') DEFAULT 'date' AFTER blocking_enabled,
ADD COLUMN blocking_date DATETIME NULL AFTER blocking_type,
ADD COLUMN blocking_response_limit INT NULL AFTER blocking_date,
ADD COLUMN blocking_message TEXT NULL AFTER blocking_response_limit;

-- Índices para melhor performance
CREATE INDEX idx_blocking_enabled ON forms(blocking_enabled);
CREATE INDEX idx_blocking_date ON forms(blocking_date);
