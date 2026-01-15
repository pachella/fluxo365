-- Migração: Adicionar campos de redirecionamento à mensagem de sucesso
-- Data: 2025-11-11
-- Descrição: Adiciona campos para configurar redirecionamento automático ou via botão após envio do formulário

ALTER TABLE form_customizations
ADD COLUMN success_redirect_enabled TINYINT(1) DEFAULT 0 COMMENT 'Ativa/desativa redirecionamento após sucesso',
ADD COLUMN success_redirect_url VARCHAR(500) DEFAULT NULL COMMENT 'URL de destino do redirecionamento',
ADD COLUMN success_redirect_type VARCHAR(20) DEFAULT 'automatic' COMMENT 'Tipo: automatic (automático) ou button (via botão)',
ADD COLUMN success_bt_redirect VARCHAR(255) DEFAULT 'Continuar' COMMENT 'Texto do botão de redirecionamento';
