-- Migration: Adicionar coluna score e notes em form_responses
-- Data: 2026-01-09

-- Adicionar coluna score para pontuação total
ALTER TABLE form_responses
ADD COLUMN score INT NULL DEFAULT 0;

-- Adicionar colunas para observações sobre leads
ALTER TABLE form_responses
ADD COLUMN notes TEXT NULL,
ADD COLUMN notes_updated_at DATETIME NULL,
ADD COLUMN notes_updated_by INT NULL;

-- Índices para busca rápida
ALTER TABLE form_responses
ADD INDEX idx_score (score),
ADD INDEX idx_notes_updated (notes_updated_at);
