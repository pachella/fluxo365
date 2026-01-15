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
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $color = trim($_POST['color'] ?? '#4EA44B');
    $icon = trim($_POST['icon'] ?? 'folder');
    
    if (empty($name)) {
        http_response_code(400);
        echo "Nome da pasta é obrigatório";
        exit;
    }
    
    // Verificar limite de pastas por plano
    if (!PlanService::canCreate('folders')) {
        http_response_code(403);
        echo PlanService::getLimitMessage('folders');
        exit;
    }

    // Forçar cor e ícone padrão no plano FREE
    if (PlanService::isFree()) {
        $color = '#4EA44B';
        $icon = 'folder';
    }
    
    // Criar pasta
    $stmt = $pdo->prepare("
        INSERT INTO form_folders (user_id, name, description, color, icon, created_at) 
        VALUES (:user_id, :name, :description, :color, :icon, NOW())
    ");
    
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':name' => $name,
        ':description' => $description,
        ':color' => $color,
        ':icon' => $icon
    ]);
    
    echo "success";
    
} catch (PDOException $e) {
    error_log("Erro ao criar pasta: " . $e->getMessage());
    http_response_code(500);
    echo "Erro ao criar pasta";
}