<?php
session_start();
require_once(__DIR__ . "/../../../core/db.php");
require_once __DIR__ . '/../../../core/PermissionManager.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit();
}

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID não fornecido']);
    exit();
}

try {
    $permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);
    
    // Buscar o campo e verificar permissão através do formulário
    $sql = "SELECT ff.*, f.user_id as form_user_id 
            FROM form_fields ff
            INNER JOIN forms f ON ff.form_id = f.id
            WHERE ff.id = :id";
    
    if (!$permissionManager->canViewAllRecords()) {
        $sql .= " AND f.user_id = :user_id";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
    if (!$permissionManager->canViewAllRecords()) {
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $field = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$field) {
        http_response_code(404);
        echo json_encode(['error' => 'Campo não encontrado']);
        exit();
    }
    
    // Remover dados desnecessários
    unset($field['form_user_id']);
    
    echo json_encode($field);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar campo: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar dados']);
}