<?php
session_start();
require_once(__DIR__ . "/../../core/db.php");
require_once(__DIR__ . "/../../core/PlanService.php");

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
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $color = trim($_POST['color'] ?? '#4EA44B');
    $icon = trim($_POST['icon'] ?? 'folder');
    
    if (empty($id) || empty($name)) {
        http_response_code(400);
        echo "ID e nome da pasta são obrigatórios";
        exit;
    }
    
    // Forçar cor e ícone padrão no plano FREE
    if (PlanService::isFree()) {
        $color = '#4EA44B';
        $icon = 'folder';
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
    
    // Atualizar pasta
    $stmt = $pdo->prepare("
        UPDATE form_folders 
        SET name = :name, description = :description, color = :color, icon = :icon, updated_at = NOW()
        WHERE id = :id AND user_id = :user_id
    ");
    
    $stmt->execute([
        ':id' => $id,
        ':user_id' => $_SESSION['user_id'],
        ':name' => $name,
        ':description' => $description,
        ':color' => $color,
        ':icon' => $icon
    ]);
    
    echo "success";
    
} catch (PDOException $e) {
    error_log("Erro ao editar pasta: " . $e->getMessage());
    http_response_code(500);
    echo "Erro ao editar pasta";
}