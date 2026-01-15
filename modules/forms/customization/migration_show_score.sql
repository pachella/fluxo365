-- Migration para adicionar campo show_score na tabela form_customizations
-- Execute este script no banco de dados para habilitar a exibição de pontuação na mensagem de sucesso

ALTER TABLE form_customizations
ADD COLUMN IF NOT EXISTS show_score TINYINT(1) DEFAULT 0 AFTER success_bt_redirect;

-- Verificar estrutura da tabela
DESCRIBE form_customizations;
