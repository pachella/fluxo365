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

    // Verificar se o fluxo pertence ao usuário (através do formulário)
    if (!$permissionManager->canViewAllRecords()) {
        $checkStmt = $pdo->prepare("
            SELECT f.id
            FROM form_flows ff
            JOIN forms f ON ff.form_id = f.id
            WHERE ff.id = :flow_id AND f.user_id = :user_id
        ");
        $checkStmt->execute([
            ':flow_id' => $flow_id,
            ':user_id' => $_SESSION['user_id']
        ]);
        if (!$checkStmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Acesso negado']);
            exit();
        }
    }

    // Primeiro, deletar todos os campos que estão dentro deste fluxo
    $deleteFieldsStmt = $pdo->prepare("DELETE FROM form_fields WHERE flow_id = :flow_id");
    $deleteFieldsStmt->execute([':flow_id' => $flow_id]);

    // Depois, deletar o fluxo
    $stmt = $pdo->prepare("DELETE FROM form_flows WHERE id = :flow_id");
    $stmt->execute([':flow_id' => $flow_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Fluxo e seus campos removidos com sucesso'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
