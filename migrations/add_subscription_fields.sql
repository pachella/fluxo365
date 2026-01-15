-- Adicionar apenas campo de expiração PRO na tabela users
-- Execute este SQL apenas uma vez

ALTER TABLE users
ADD COLUMN IF NOT EXISTS pro_expires_at DATETIME DEFAULT NULL AFTER plan;

-- Criar índice para melhorar performance em consultas de expiração
CREATE INDEX IF NOT EXISTS idx_pro_expires ON users(pro_expires_at);
