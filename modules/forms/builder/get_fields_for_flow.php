<?php
session_start();
require_once(__DIR__ . "/../../../core/db.php");
require_once __DIR__ . '/../../../core/PermissionManager.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit();
}

try {
    $permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

    $form_id = $_GET['form_id'] ?? '';

    if (empty($form_id)) {
        throw new Exception('ID do formulário não fornecido');
    }

    // Verificar se o formulário pertence ao usuário
    if (!$permissionManager->canViewAllRecords()) {
        $checkStmt = $pdo->prepare("SELECT id FROM forms WHERE id = :form_id AND user_id = :user_id");
        $checkStmt->execute([
            ':form_id' => $form_id,
            ':user_id' => $_SESSION['user_id']
        ]);
        if (!$checkStmt->fetch()) {
            throw new Exception('Formulário não encontrado');
        }
    }

    // Buscar TODOS os campos do formulário que podem ter valores
    // Excluir apenas campos que não têm valores (welcome, message)
    $stmt = $pdo->prepare("
        SELECT id, label, type
        FROM form_fields
        WHERE form_id = :form_id
        AND type NOT IN ('welcome', 'message')
        ORDER BY order_index ASC
    ");
    $stmt->execute([':form_id' => $form_id]);
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'fields' => $fields
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
