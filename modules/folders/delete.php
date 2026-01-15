<?php
session_start();
require_once(__DIR__ . "/../../core/db.php");

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo "Não autorizado";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Método não permitido";
    exit;
}

try {
    $id = intval($_GET['id'] ?? 0);
    
    if (empty($id)) {
        http_response_code(400);
        echo "ID da pasta é obrigatório";
        exit;
    }
    
    // Verificar se a pasta pertence ao usuário
    $stmt = $pdo->prepare("SELECT id FROM form_folders WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id' => $id,
        ':user_id' => $_SESSION['user_id']
    ]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo "Pasta não encontrada";
        exit;
    }
    
    // Excluir pasta (os formulários ficarão com folder_id = NULL graças ao ON DELETE SET NULL)
    $stmt = $pdo->prepare("DELETE FROM form_folders WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id' => $id,
        ':user_id' => $_SESSION['user_id']
    ]);
    
    echo "success";
    
} catch (PDOException $e) {
    error_log("Erro ao excluir pasta: " . $e->getMessage());
    http_response_code(500);
    echo "Erro ao excluir pasta";
}