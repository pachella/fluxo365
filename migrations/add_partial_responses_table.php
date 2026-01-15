<?php
/**
 * Migration: Criar tabela de respostas parciais
 *
 * Cria a tabela partial_responses para armazenar respostas parciais/abandonadas
 * de formulários que ainda não foram completamente enviados.
 */

require_once(__DIR__ . '/../core/db.php');

try {
    // Verificar se a tabela já existe
    $checkStmt = $pdo->query("SHOW TABLES LIKE 'partial_responses'");
    $exists = $checkStmt->fetch();

    if ($exists) {
        echo "✓ Tabela 'partial_responses' já existe. Nada a fazer.\n";
        exit(0);
    }

    // Criar a tabela
    $pdo->exec("
        CREATE TABLE partial_responses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            form_id INT NOT NULL,
            session_id VARCHAR(255) NOT NULL,
            answers_data TEXT NOT NULL,
            progress INT DEFAULT 0,
            last_field_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            completed TINYINT(1) DEFAULT 0,
            FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
            UNIQUE KEY unique_session (form_id, session_id),
            INDEX idx_form_id (form_id),
            INDEX idx_created_at (created_at),
            INDEX idx_completed (completed)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    echo "✓ Tabela 'partial_responses' criada com sucesso!\n";
    echo "✓ Agora o sistema pode armazenar respostas parciais de formulários.\n";

} catch (PDOException $e) {
    echo "✗ Erro ao executar migration: " . $e->getMessage() . "\n";
    exit(1);
}
