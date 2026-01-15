<?php
/**
 * Script para aplicar migração de redirecionamento
 * Execute este arquivo uma vez para adicionar as colunas necessárias
 */

require_once(__DIR__ . '/../core/db.php');

echo "<h2>Aplicando Migração: Adicionar campos de redirecionamento</h2>\n";

try {
    // Verificar se as colunas já existem
    $checkStmt = $pdo->query("SHOW COLUMNS FROM form_customizations LIKE 'success_redirect_enabled'");
    $columnExists = $checkStmt->fetch();

    if ($columnExists) {
        echo "<p style='color: green;'>✓ As colunas já existem! Migração já foi aplicada anteriormente.</p>\n";
        exit;
    }

    // Aplicar migração
    echo "<p>Adicionando colunas ao banco de dados...</p>\n";

    $pdo->exec("
        ALTER TABLE form_customizations
        ADD COLUMN success_redirect_enabled TINYINT(1) DEFAULT 0 COMMENT 'Ativa/desativa redirecionamento após sucesso',
        ADD COLUMN success_redirect_url VARCHAR(500) DEFAULT NULL COMMENT 'URL de destino do redirecionamento',
        ADD COLUMN success_redirect_type VARCHAR(20) DEFAULT 'automatic' COMMENT 'Tipo: automatic (automático) ou button (via botão)',
        ADD COLUMN success_bt_redirect VARCHAR(255) DEFAULT 'Continuar' COMMENT 'Texto do botão de redirecionamento'
    ");

    echo "<p style='color: green;'>✓ Migração aplicada com sucesso!</p>\n";
    echo "<p>As seguintes colunas foram adicionadas à tabela <code>form_customizations</code>:</p>\n";
    echo "<ul>\n";
    echo "  <li><code>success_redirect_enabled</code> - Ativa/desativa redirecionamento</li>\n";
    echo "  <li><code>success_redirect_url</code> - URL de destino</li>\n";
    echo "  <li><code>success_redirect_type</code> - Tipo (automatic/button)</li>\n";
    echo "  <li><code>success_bt_redirect</code> - Texto do botão</li>\n";
    echo "</ul>\n";
    echo "<p><strong>Pronto!</strong> Agora você pode usar a funcionalidade de redirecionamento.</p>\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "<p style='color: orange;'>⚠ As colunas já existem. Nada foi alterado.</p>\n";
    } else {
        echo "<p style='color: red;'>✗ Erro ao aplicar migração:</p>\n";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>\n";
    }
}
?>
