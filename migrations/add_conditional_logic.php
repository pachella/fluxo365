<?php
/**
 * Migration: Adicionar Lógica Condicional aos Campos
 *
 * Esta migration adiciona a coluna conditional_logic à tabela form_fields
 * para permitir que campos sejam mostrados/escondidos baseado em condições.
 *
 * Estrutura do JSON armazenado:
 * {
 *   "enabled": true,
 *   "logic_type": "all",  // "all" (AND) ou "any" (OR)
 *   "conditions": [
 *     {
 *       "field_id": 123,
 *       "operator": "equals",
 *       "value": "Sim"
 *     }
 *   ]
 * }
 */

require_once(__DIR__ . '/../core/db.php');

try {
    // Verificar se a coluna já existe
    $checkStmt = $pdo->query("SHOW COLUMNS FROM form_fields LIKE 'conditional_logic'");
    $exists = $checkStmt->fetch();

    if ($exists) {
        echo "✓ Coluna 'conditional_logic' já existe. Nada a fazer.\n";
        exit(0);
    }

    // Adicionar a coluna
    $pdo->exec("ALTER TABLE form_fields ADD COLUMN conditional_logic TEXT DEFAULT NULL");

    echo "✓ Coluna 'conditional_logic' adicionada com sucesso!\n";
    echo "✓ Agora os campos podem ter lógica condicional.\n";

} catch (PDOException $e) {
    echo "✗ Erro ao executar migration: " . $e->getMessage() . "\n";
    exit(1);
}
