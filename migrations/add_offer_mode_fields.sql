-- Migration: Adicionar campos do Modo Oferta
-- Data: 2026-01-08
-- Descrição: Adiciona campos para configurar modo oferta na mensagem de sucesso

ALTER TABLE form_customizations
ADD COLUMN IF NOT EXISTS offer_mode_enabled TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS offer_loading_text_1 VARCHAR(255) DEFAULT 'Analisando seu perfil...',
ADD COLUMN IF NOT EXISTS offer_loading_text_2 VARCHAR(255) DEFAULT 'Procurando a melhor oferta...',
ADD COLUMN IF NOT EXISTS offer_title VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS offer_description TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS offer_anchor_price DECIMAL(10, 2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS offer_promo_price DECIMAL(10, 2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS offer_scarcity_text VARCHAR(255) DEFAULT NULL;
