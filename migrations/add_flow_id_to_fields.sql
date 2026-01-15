-- Adicionar coluna flow_id à tabela form_fields
-- Esta coluna permite associar explicitamente um campo a um fluxo

ALTER TABLE form_fields
ADD COLUMN flow_id INT DEFAULT NULL AFTER form_id,
ADD CONSTRAINT fk_field_flow FOREIGN KEY (flow_id) REFERENCES form_flows(id) ON DELETE SET NULL;

-- Comentário:
-- - flow_id NULL = campo não pertence a nenhum fluxo
-- - flow_id = X = campo pertence ao fluxo X
-- - ON DELETE SET NULL = se o fluxo for deletado, o campo volta a ser independente
