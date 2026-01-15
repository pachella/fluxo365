<?php
// Script para executar migration
// Acesse via navegador: /migrations/run_migration.php?file=nome_do_arquivo

session_start();
require_once(__DIR__ . '/../core/db.php');

// Apenas admin pode executar migrations
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die('Acesso negado. Apenas administradores podem executar migrations.');
}

// Permitir especificar arquivo via URL ou usar o padrão
$fileName = $_GET['file'] ?? 'add_content_alignment_field.sql';
$migrationFile = __DIR__ . '/' . basename($fileName);

if (!file_exists($migrationFile)) {
    die('Arquivo de migration não encontrado: ' . htmlspecialchars($fileName));
}

$sql = file_get_contents($migrationFile);

try {
    $pdo->exec($sql);
    echo '<h1>✓ Migration executada com sucesso!</h1>';
    echo '<p>Arquivo: ' . htmlspecialchars($fileName) . '</p>';
    echo '<p><a href="/dashboard">← Voltar ao Dashboard</a></p>';
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        echo '<h1>Migration já foi executada anteriormente</h1>';
        echo '<p>As alterações já existem no banco de dados.</p>';
        echo '<p><a href="/dashboard">← Voltar ao Dashboard</a></p>';
    } else {
        echo '<h1>Erro ao executar migration</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><a href="/dashboard">← Voltar ao Dashboard</a></p>';
    }
}
