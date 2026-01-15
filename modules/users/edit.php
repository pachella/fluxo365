<?php
ob_clean();
session_start();
require_once(__DIR__ . "/../../core/db.php");
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$id = $_GET['id'] ?? '';

if (empty($id) || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, name, email, role, status
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuário não encontrado']);
        exit;
    }
    
    // Remover informações sensíveis
    unset($user['password']);
    
    echo json_encode($user);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro no banco de dados']);
}