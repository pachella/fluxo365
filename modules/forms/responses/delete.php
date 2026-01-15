<?php
ob_clean();

session_start();
require_once(__DIR__ . "/../../../core/db.php");
require_once __DIR__ . '/../../../core/PermissionManager.php';

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
    
    // Verificar se a resposta existe e se o usuário tem permissão
    $stmt = $pdo->prepare("
        SELECT fr.id, f.user_id 
        FROM form_responses fr
        INNER JOIN forms f ON fr.form_id = f.id
        WHERE fr.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $response = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$response) {
        http_response_code(404);
        echo "Resposta não encontrada";
        exit();
    }
    
    if (!$permissionManager->canDeleteRecord($response['user_id'])) {
        http_response_code(403);
        echo "Você não tem permissão para excluir esta resposta";
        exit();
    }
    
    // Deletar a resposta (CASCADE vai deletar os response_answers)
    $stmt = $pdo->prepare("DELETE FROM form_responses WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    echo "success";
    
} catch (PDOException $e) {
    error_log("Erro ao excluir resposta: " . $e->getMessage());
    http_response_code(500);
    echo "Erro ao excluir resposta";
}

exit();