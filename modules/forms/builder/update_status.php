<?php
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

try {
    $permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

    $formId = trim($_POST['form_id'] ?? '');
    $status = trim($_POST['status'] ?? '');

    if (empty($formId)) {
        http_response_code(400);
        echo "ID do formulário é obrigatório";
        exit();
    }

    // Validar status
    if (!in_array($status, ['ativo', 'rascunho'])) {
        http_response_code(400);
        echo "Status inválido";
        exit();
    }

    // Verificar se o formulário existe e se o usuário tem permissão para editá-lo
    $checkStmt = $pdo->prepare("SELECT user_id FROM forms WHERE id = :id");
    $checkStmt->execute([':id' => $formId]);
    $form = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$form) {
        http_response_code(404);
        echo "Formulário não encontrado";
        exit();
    }

    if (!$permissionManager->canEditRecord($form['user_id'])) {
        http_response_code(403);
        echo "Você não tem permissão para editar este formulário";
        exit();
    }

    // Atualizar status
    $stmt = $pdo->prepare("UPDATE forms SET status = :status WHERE id = :id");
    $stmt->execute([
        ':status' => $status,
        ':id' => $formId
    ]);

    echo "success";

} catch (PDOException $e) {
    error_log("Erro no banco de dados: " . $e->getMessage());
    http_response_code(500);
    echo "Erro no banco de dados";
} catch (Exception $e) {
    error_log("Erro interno: " . $e->getMessage());
    http_response_code(500);
    echo "Erro interno";
}

exit();
