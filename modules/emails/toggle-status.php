<?php
session_start();
require_once(__DIR__ . "/../../core/db.php");

if (!isset($_SESSION["user_id"])) {
    echo "Não autorizado";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Método não permitido";
    exit;
}

try {
    $id = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? '';
    
    // Validações
    if (empty($id) || !is_numeric($id)) {
        echo "ID inválido";
        exit;
    }
    
    if ($status !== '0' && $status !== '1') {
        echo "Status inválido";
        exit;
    }
    
    $stmt = $pdo->prepare("
        UPDATE email_templates 
        SET active = :status, updated_at = NOW()
        WHERE id = :id
    ");
    
    $result = $stmt->execute([
        ':id' => $id,
        ':status' => $status
    ]);
    
    if ($result) {
        echo "success";
    } else {
        echo "Erro ao alterar status";
    }
    
} catch (PDOException $e) {
    error_log("Erro no banco: " . $e->getMessage());
    echo "Erro no banco de dados";
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    echo "Erro interno";
}
?>