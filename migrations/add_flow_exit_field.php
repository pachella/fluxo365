<?php
/**
 * Migration: Adicionar campo de saída dos fluxos
 *
 * Adiciona coluna exit_to_field_id na tabela form_flows para permitir
 * especificar para qual campo ir após completar um fluxo.
 */

require_once(__DIR__ . '/../core/db.php');

try {
    // Verificar se a coluna já existe
    $checkStmt = $pdo->query("SHOW COLUMNS FROM form_flows LIKE 'exit_to_field_id'");
    $exists = $checkStmt->fetch();

    if ($exists) {
        echo "✓ Coluna 'exit_to_field_id' já existe. Nada a fazer.\n";
        exit(0);
    }

    // Adicionar a coluna
    $pdo->exec("ALTER TABLE form_flows ADD COLUMN exit_to_field_id INT DEFAULT NULL AFTER order_index");

    echo "✓ Coluna 'exit_to_field_id' adicionada com sucesso!\n";
    echo "✓ Agora é possível especificar para onde ir após completar um fluxo.\n";

} catch (PDOException $e) {
    echo "✗ Erro ao executar migration: " . $e->getMessage() . "\n";
    exit(1);
}
