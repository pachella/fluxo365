<?php
/**
 * Migration: Adicionar coluna score para salvar pontuação
 *
 * Adiciona coluna score na tabela response_answers para armazenar
 * a pontuação quando um campo de múltipla escolha tem scoring ativado.
 */

require_once(__DIR__ . '/../core/db.php');

try {
    // Verificar se a coluna já existe
    $checkStmt = $pdo->query("SHOW COLUMNS FROM response_answers LIKE 'score'");
    $exists = $checkStmt->fetch();

    if ($exists) {
        echo "✓ Coluna 'score' já existe. Nada a fazer.\n";
        exit(0);
    }

    // Adicionar a coluna
    $pdo->exec("ALTER TABLE response_answers ADD COLUMN score INT DEFAULT NULL AFTER answer");

    echo "✓ Coluna 'score' adicionada com sucesso!\n";
    echo "✓ Agora as respostas podem armazenar pontuação.\n";

} catch (PDOException $e) {
    echo "✗ Erro ao executar migration: " . $e->getMessage() . "\n";
    exit(1);
}
