<?php
session_start();
require_once("../core/db.php");

// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Test CRM</title></head><body>";
echo "<h1>Teste de Conexão - CRM</h1>";

// Verificar se está logado
if (!isset($_SESSION["user_id"])) {
    echo "<p style='color:red'>❌ Usuário não está logado</p>";
    echo "<p>Faça login primeiro: <a href='/auth/login.php'>Login</a></p>";
    exit;
}

echo "<p style='color:green'>✅ Usuário logado: ID = " . $_SESSION['user_id'] . "</p>";

// Verificar conexão com banco
try {
    $stmt = $pdo->query("SELECT 1");
    echo "<p style='color:green'>✅ Conexão com banco de dados OK</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Erro na conexão: " . $e->getMessage() . "</p>";
    exit;
}

// Verificar se as tabelas existem
$tables = ['crm_boards', 'crm_columns', 'crm_cards', 'crm_tags', 'crm_card_tags'];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color:green'>✅ Tabela '$table' existe</p>";

            // Mostrar estrutura
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre style='margin-left:20px; font-size:12px;'>";
            foreach ($columns as $col) {
                echo $col['Field'] . " (" . $col['Type'] . ")" . ($col['Null'] == 'NO' ? ' NOT NULL' : '') . "\n";
            }
            echo "</pre>";
        } else {
            echo "<p style='color:red'>❌ Tabela '$table' NÃO existe - Execute o SQL primeiro!</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Erro ao verificar tabela '$table': " . $e->getMessage() . "</p>";
    }
}

// Tentar inserir um quadro de teste
echo "<hr><h2>Teste de Inserção</h2>";
try {
    $testName = "Teste " . date('H:i:s');
    $stmt = $pdo->prepare("INSERT INTO crm_boards (name, description, color, user_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$testName, 'Descrição teste', '#6366f1', $_SESSION['user_id']]);
    $boardId = $pdo->lastInsertId();
    echo "<p style='color:green'>✅ Quadro de teste criado com sucesso! ID: $boardId</p>";

    // Deletar o teste
    $stmt = $pdo->prepare("DELETE FROM crm_boards WHERE id = ?");
    $stmt->execute([$boardId]);
    echo "<p style='color:green'>✅ Quadro de teste removido</p>";

} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Erro ao inserir: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
