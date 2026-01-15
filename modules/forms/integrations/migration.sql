-- Migration para adicionar colunas de integração e rastreamento na tabela form_integrations
-- Execute este script no banco de dados para adicionar suporte completo

-- Adicionar colunas para Webhook
ALTER TABLE form_integrations
ADD COLUMN IF NOT EXISTS webhook_url VARCHAR(500) DEFAULT NULL AFTER email_cc,
ADD COLUMN IF NOT EXISTS webhook_method VARCHAR(10) DEFAULT 'POST' AFTER webhook_url,
ADD COLUMN IF NOT EXISTS webhook_headers TEXT DEFAULT NULL AFTER webhook_method,
ADD COLUMN IF NOT EXISTS webhook_enabled TINYINT(1) DEFAULT 0 AFTER webhook_headers;

-- Adicionar/restaurar colunas para Google Sheets
ALTER TABLE form_integrations
ADD COLUMN IF NOT EXISTS sheets_url VARCHAR(500) DEFAULT NULL AFTER webhook_enabled,
ADD COLUMN IF NOT EXISTS sheets_enabled TINYINT(1) DEFAULT 0 AFTER sheets_url;

-- Adicionar colunas para Calendly
ALTER TABLE form_integrations
ADD COLUMN IF NOT EXISTS calendly_url VARCHAR(500) DEFAULT NULL AFTER sheets_enabled,
ADD COLUMN IF NOT EXISTS calendly_enabled TINYINT(1) DEFAULT 0 AFTER calendly_url;

-- Adicionar colunas para UTM Tracking
ALTER TABLE form_integrations
ADD COLUMN IF NOT EXISTS utm_enabled TINYINT(1) DEFAULT 0 AFTER calendly_enabled;

-- Adicionar colunas para Facebook Pixel
ALTER TABLE form_integrations
ADD COLUMN IF NOT EXISTS fb_pixel_id VARCHAR(50) DEFAULT NULL AFTER utm_enabled,
ADD COLUMN IF NOT EXISTS fb_pixel_enabled TINYINT(1) DEFAULT 0 AFTER fb_pixel_id;

-- Adicionar colunas para Google Tag Manager
ALTER TABLE form_integrations
ADD COLUMN IF NOT EXISTS gtm_id VARCHAR(50) DEFAULT NULL AFTER fb_pixel_enabled,
ADD COLUMN IF NOT EXISTS gtm_enabled TINYINT(1) DEFAULT 0 AFTER gtm_id;

-- Adicionar colunas para Google Analytics
ALTER TABLE form_integrations
ADD COLUMN IF NOT EXISTS ga_id VARCHAR(50) DEFAULT NULL AFTER gtm_enabled,
ADD COLUMN IF NOT EXISTS ga_enabled TINYINT(1) DEFAULT 0 AFTER ga_id;

-- Verificar estrutura da tabela
DESCRIBE form_integrations;
