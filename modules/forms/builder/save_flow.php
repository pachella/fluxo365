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

    $form_id = trim($_POST['form_id'] ?? '');
    $flow_id = trim($_POST['flow_id'] ?? '');
    $label = trim($_POST['label'] ?? 'Novo Fluxo');
    $conditions = trim($_POST['conditions'] ?? '[]');
    $conditions_type = trim($_POST['conditions_type'] ?? 'all');

    // Validar JSON de condições
    $conditionsArray = json_decode($conditions, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Formato inválido de condições');
    }

    // Verificar se o formulário pertence ao usuário
    if (!$permissionManager->canViewAllRecords()) {
        $checkStmt = $pdo->prepare("SELECT id FROM forms WHERE id = :form_id AND user_id = :user_id");
        $checkStmt->execute([
            ':form_id' => $form_id,
            ':user_id' => $_SESSION['user_id']
        ]);
        if (!$checkStmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Acesso negado']);
            exit();
        }
    }

    if (!empty($flow_id)) {
        // Atualizar fluxo existente
        $stmt = $pdo->prepare("
            UPDATE form_flows
            SET label = :label,
                conditions = :conditions,
                conditions_type = :conditions_type
            WHERE id = :flow_id AND form_id = :form_id
        ");
        $stmt->execute([
            ':label' => $label,
            ':conditions' => $conditions,
            ':conditions_type' => $conditions_type,
            ':flow_id' => $flow_id,
            ':form_id' => $form_id
        ]);

        echo json_encode([
            'success' => true,
            'flow_id' => $flow_id,
            'message' => 'Fluxo atualizado com sucesso'
        ]);
    } else {
        // Criar novo fluxo
        // Obter o próximo order_index
        $orderStmt = $pdo->prepare("SELECT MAX(order_index) as max_order FROM form_fields WHERE form_id = :form_id");
        $orderStmt->execute([':form_id' => $form_id]);
        $maxOrder = $orderStmt->fetch(PDO::FETCH_ASSOC);
        $nextOrder = ($maxOrder['max_order'] ?? 0) + 1;

        $stmt = $pdo->prepare("
            INSERT INTO form_flows (form_id, label, conditions, conditions_type, order_index)
            VALUES (:form_id, :label, :conditions, :conditions_type, :order_index)
        ");
        $stmt->execute([
            ':form_id' => $form_id,
            ':label' => $label,
            ':conditions' => $conditions,
            ':conditions_type' => $conditions_type,
            ':order_index' => $nextOrder
        ]);

        $newFlowId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'flow_id' => $newFlowId,
            'message' => 'Fluxo criado com sucesso'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
