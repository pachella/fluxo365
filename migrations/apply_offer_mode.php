<?php
/**
 * Migration: Adicionar campos do Modo Oferta
 * Data: 2026-01-08
 */

require_once __DIR__ . '/../core/db.php';

try {
    echo "Aplicando migration: Campos do Modo Oferta...\n";

    // Ler o arquivo SQL
    $sql = file_get_contents(__DIR__ . '/add_offer_mode_fields.sql');

    // Executar cada statement
    $pdo->exec($sql);

    echo "✅ Migration aplicada com sucesso!\n";
    echo "Campos adicionados:\n";
    echo "  - offer_mode_enabled\n";
    echo "  - offer_loading_text_1\n";
    echo "  - offer_loading_text_2\n";
    echo "  - offer_title\n";
    echo "  - offer_description\n";
    echo "  - offer_anchor_price\n";
    echo "  - offer_promo_price\n";
    echo "  - offer_scarcity_text\n";

} catch (PDOException $e) {
    echo "❌ Erro ao aplicar migration: " . $e->getMessage() . "\n";
    exit(1);
}
