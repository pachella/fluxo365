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
    
    $sql = "SELECT fi.*, f.user_id as form_user_id 
            FROM form_integrations fi
            INNER JOIN forms f ON fi.form_id = f.id
            WHERE fi.form_id = :id";
    
    if (!$permissionManager->canViewAllRecords()) {
        $sql .= " AND f.user_id = :user_id";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
    if (!$permissionManager->canViewAllRecords()) {
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $sendOptions = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sendOptions) {
        echo json_encode([
            'email_to' => '',
            'email_cc' => ''
        ]);
        exit();
    }
    
    unset($sendOptions['form_user_id']);
    unset($sendOptions['id']);
    unset($sendOptions['form_id']);
    unset($sendOptions['created_at']);
    unset($sendOptions['updated_at']);
    unset($sendOptions['sheets_url']);
    unset($sendOptions['sheets_enabled']);
    
    echo json_encode($sendOptions);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar opções de envio: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar dados']);
}
