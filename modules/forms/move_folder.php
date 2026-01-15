<?php
session_start();
require_once(__DIR__ . "/../../core/db.php");
require_once(__DIR__ . "/../../core/PermissionManager.php");

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
    $formId = intval($_POST['form_id'] ?? 0);
    $folderId = $_POST['folder_id'] ?? null; // Pode ser NULL para remover da pasta
    
    if (empty($formId)) {
        http_response_code(400);
        echo "ID do formulário é obrigatório";
        exit;
    }
    
    $permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id']);
    
    // Verificar se o formulário existe e pertence ao usuário
    $stmt = $pdo->prepare("SELECT user_id FROM forms WHERE id = :id");
    $stmt->execute([':id' => $formId]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$form) {
        http_response_code(404);
        echo "Formulário não encontrado";
        exit;
    }
    
    // Verificar permissão
    if (!$permissionManager->canEditRecord($form['user_id'])) {
        http_response_code(403);
        echo "Sem permissão para editar este formulário";
        exit;
    }
    
    // Se folderId não é null, verificar se a pasta existe e pertence ao usuário
    if ($folderId !== null && $folderId !== '') {
        $folderId = intval($folderId);
        
        $folderStmt = $pdo->prepare("SELECT id FROM form_folders WHERE id = :id AND user_id = :user_id");
        $folderStmt->execute([
            ':id' => $folderId,
            ':user_id' => $_SESSION['user_id']
        ]);
        
        if (!$folderStmt->fetch()) {
            http_response_code(404);
            echo "Pasta não encontrada";
            exit;
        }
    } else {
        $folderId = null; // Remover da pasta
    }
    
    // Atualizar folder_id do formulário
    $updateStmt = $pdo->prepare("UPDATE forms SET folder_id = :folder_id WHERE id = :id");
    $updateStmt->execute([
        ':folder_id' => $folderId,
        ':id' => $formId
    ]);
    
    echo "success";
    
} catch (PDOException $e) {
    error_log("Erro ao mover formulário: " . $e->getMessage());
    http_response_code(500);
    echo "Erro ao mover formulário";
}