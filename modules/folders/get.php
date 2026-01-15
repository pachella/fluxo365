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
    $id = intval($_GET['id'] ?? 0);
    
    if (empty($id)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID da pasta é obrigatório']);
        exit;
    }
    
    // Buscar pasta
    $stmt = $pdo->prepare("SELECT * FROM form_folders WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id' => $id,
        ':user_id' => $_SESSION['user_id']
    ]);
    
    $folder = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$folder) {
        http_response_code(404);
        echo json_encode(['error' => 'Pasta não encontrada']);
        exit;
    }
    
    echo json_encode($folder);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar pasta: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar pasta']);
}