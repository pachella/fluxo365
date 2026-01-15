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

    $flow_id = trim($_POST['flow_id'] ?? '');

    if (empty($flow_id)) {
        throw new Exception('ID do fluxo não fornecido');
    }

    // Buscar fluxo original
    $stmt = $pdo->prepare("SELECT * FROM form_flows WHERE id = :flow_id");
    $stmt->execute([':flow_id' => $flow_id]);
    $flow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$flow) {
        throw new Exception('Fluxo não encontrado');
    }

    // Verificar permissão
    if (!$permissionManager->canViewAllRecords()) {
        $checkStmt = $pdo->prepare("SELECT id FROM forms WHERE id = :form_id AND user_id = :user_id");
        $checkStmt->execute([
            ':form_id' => $flow['form_id'],
            ':user_id' => $_SESSION['user_id']
        ]);
        if (!$checkStmt->fetch()) {
            throw new Exception('Acesso negado');
        }
    }

    // Obter próximo order_index
    $orderStmt = $pdo->prepare("SELECT MAX(order_index) as max_order FROM form_flows WHERE form_id = :form_id");
    $orderStmt->execute([':form_id' => $flow['form_id']]);
    $maxOrder = $orderStmt->fetch(PDO::FETCH_ASSOC);
    $nextOrder = ($maxOrder['max_order'] ?? 0) + 1;

    // Duplicar fluxo
    $insertStmt = $pdo->prepare("
        INSERT INTO form_flows (
            form_id, label, conditions, conditions_type, order_index
        ) VALUES (
            :form_id, :label, :conditions, :conditions_type, :order_index
        )
    ");

    $insertStmt->execute([
        ':form_id' => $flow['form_id'],
        ':label' => $flow['label'] . ' (cópia)',
        ':conditions' => $flow['conditions'],
        ':conditions_type' => $flow['conditions_type'],
        ':order_index' => $nextOrder
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Fluxo duplicado com sucesso'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
