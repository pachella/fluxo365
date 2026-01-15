<?php
/**
 * Aplicar migração de campos de bloqueio
 */

require_once(__DIR__ . '/../core/db.php');

try {
    echo "Iniciando migração de campos de bloqueio...\n";

    // Verificar se os campos já existem
    $checkStmt = $pdo->query("SHOW COLUMNS FROM forms LIKE 'blocking_enabled'");
    if ($checkStmt->rowCount() > 0) {
        echo "⚠️  Campos de bloqueio já existem. Migração não necessária.\n";
        exit(0);
    }

    // Ler o arquivo SQL
    $sql = file_get_contents(__DIR__ . '/add_form_blocking_fields.sql');

    // Remover comentários e dividir por ponto e vírgula
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !str_starts_with($stmt, '--');
        }
    );

    // Executar cada statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executando: " . substr($statement, 0, 50) . "...\n";
            $pdo->exec($statement);
        }
    }

    echo "✓ Migração concluída com sucesso!\n";
    echo "✓ Campos adicionados: blocking_enabled, blocking_type, blocking_date, blocking_response_limit, blocking_message\n";

} catch (PDOException $e) {
    echo "✗ Erro na migração: " . $e->getMessage() . "\n";
    exit(1);
}
?>
