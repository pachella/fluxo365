<?php
ob_clean();

session_start();
require_once(__DIR__ . "/../../../core/db.php");
require_once __DIR__ . '/../../../core/PermissionManager.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit();
}

try {
    $permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

    $field_id = trim($_POST['field_id'] ?? '');

    if (empty($field_id)) {
        throw new Exception('ID do campo não fornecido');
    }

    // Buscar campo original
    $stmt = $pdo->prepare("SELECT * FROM form_fields WHERE id = :field_id");
    $stmt->execute([':field_id' => $field_id]);
    $field = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$field) {
        throw new Exception('Campo não encontrado');
    }

    // Verificar permissão
    if (!$permissionManager->canViewAllRecords()) {
        $checkStmt = $pdo->prepare("SELECT id FROM forms WHERE id = :form_id AND user_id = :user_id");
        $checkStmt->execute([
            ':form_id' => $field['form_id'],
            ':user_id' => $_SESSION['user_id']
        ]);
        if (!$checkStmt->fetch()) {
            throw new Exception('Acesso negado');
        }
    }

    // Obter próximo order_index
    $orderStmt = $pdo->prepare("SELECT MAX(order_index) as max_order FROM form_fields WHERE form_id = :form_id");
    $orderStmt->execute([':form_id' => $field['form_id']]);
    $maxOrder = $orderStmt->fetch(PDO::FETCH_ASSOC);
    $nextOrder = ($maxOrder['max_order'] ?? 0) + 1;

    // Duplicar campo
    $insertStmt = $pdo->prepare("
        INSERT INTO form_fields (
            form_id, type, label, description, placeholder, required,
            allow_multiple, options, config, media, media_style,
            media_position, media_size, conditional_logic, order_index
        ) VALUES (
            :form_id, :type, :label, :description, :placeholder, :required,
            :allow_multiple, :options, :config, :media, :media_style,
            :media_position, :media_size, :conditional_logic, :order_index
        )
    ");

    $insertStmt->execute([
        ':form_id' => $field['form_id'],
        ':type' => $field['type'],
        ':label' => $field['label'] . ' (cópia)',
        ':description' => $field['description'],
        ':placeholder' => $field['placeholder'],
        ':required' => $field['required'],
        ':allow_multiple' => $field['allow_multiple'],
        ':options' => $field['options'],
        ':config' => $field['config'],
        ':media' => $field['media'],
        ':media_style' => $field['media_style'],
        ':media_position' => $field['media_position'],
        ':media_size' => $field['media_size'],
        ':conditional_logic' => $field['conditional_logic'],
        ':order_index' => $nextOrder
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Campo duplicado com sucesso'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
