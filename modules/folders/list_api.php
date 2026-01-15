<?php
session_start();
require_once(__DIR__ . "/../../core/db.php");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

try {
    // Buscar pastas do usuário
    $stmt = $pdo->prepare("
        SELECT id, name, color, icon 
        FROM form_folders 
        WHERE user_id = :user_id 
        ORDER BY name ASC
    ");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($folders);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar pastas: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar pastas']);
}