<?php
ob_clean();

session_start();
require_once(__DIR__ . "/../../core/db.php");
require_once __DIR__ . '/../../core/PermissionManager.php';

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo "Não autorizado";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Método não permitido";
    exit();
}

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo "ID não fornecido";
    exit();
}

try {
    $permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);
    
    // Verificar se o formulário existe e se o usuário tem permissão
    $stmt = $pdo->prepare("SELECT user_id FROM forms WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$form) {
        http_response_code(404);
        echo "Formulário não encontrado";
        exit();
    }
    
    if (!$permissionManager->canDeleteRecord($form['user_id'])) {
        http_response_code(403);
        echo "Você não tem permissão para excluir este formulário";
        exit();
    }
    
    // Deletar o formulário (CASCADE vai deletar fields, responses e answers)
    $stmt = $pdo->prepare("DELETE FROM forms WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    echo "success";
    
} catch (PDOException $e) {
    error_log("Erro ao excluir formulário: " . $e->getMessage());
    http_response_code(500);
    echo "Erro ao excluir formulário";
}

exit();