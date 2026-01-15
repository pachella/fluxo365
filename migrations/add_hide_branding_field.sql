-- Adicionar campo para remover marca Formtalk (apenas PRO)
ALTER TABLE form_customizations
ADD COLUMN hide_formtalk_branding TINYINT(1) DEFAULT 0 AFTER success_bt_redirect;
