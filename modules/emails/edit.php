<?php
session_start();
require_once(__DIR__ . "/../../core/db.php");

// Verificar autenticação
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Verificar se foi fornecido um ID
$id = $_GET['id'] ?? '';
if (empty($id) || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        http_response_code(404);
        echo json_encode(['error' => 'Template não encontrado']);
        exit;
    }
    
    // Definir content-type
    header('Content-Type: application/json');
    
    // Retornar dados do template
    echo json_encode($template);
    
} catch (PDOException $e) {
    error_log("Erro no banco de dados: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>