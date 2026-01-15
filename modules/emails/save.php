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
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Validações
    if (empty($subject)) {
        echo "Assunto é obrigatório";
        exit;
    }
    
    if (!empty($id) && is_numeric($id)) {
        // Editar template existente
        $stmt = $pdo->prepare("
            UPDATE email_templates 
            SET subject = :subject, body = :body, active = :active, updated_at = NOW()
            WHERE id = :id
        ");
        
        $result = $stmt->execute([
            ':id' => $id,
            ':subject' => $subject,
            ':body' => $body,
            ':active' => $active
        ]);
        
        if ($result) {
            echo "success";
        } else {
            echo "Erro ao atualizar template";
        }
    } else {
        echo "ID inválido";
    }
    
} catch (PDOException $e) {
    error_log("Erro no banco: " . $e->getMessage());
    echo "Erro no banco de dados";
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    echo "Erro interno";
}
?>