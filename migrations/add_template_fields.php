<?php
/**
 * Migration: Adicionar campos de ícone e cor para templates
 * Data: 2025-11-11
 * Descrição: Adiciona campos icon e color na tabela forms para suportar templates visuais
 */

require_once(__DIR__ . "/../core/db.php");

try {
    // Adicionar campos icon e color
    $pdo->exec("
        ALTER TABLE forms
        ADD COLUMN icon VARCHAR(50) DEFAULT 'file-alt' AFTER folder_id,
        ADD COLUMN color VARCHAR(7) DEFAULT '#4EA44B' AFTER icon
    ");

    echo "✅ Migração executada com sucesso!\n";
    echo "Campos 'icon' e 'color' adicionados à tabela 'forms'.\n";

} catch (PDOException $e) {
    echo "❌ Erro na migração: " . $e->getMessage() . "\n";
    exit(1);
}
