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

    $flow_id = $_GET['flow_id'] ?? '';

    if (empty($flow_id)) {
        throw new Exception('ID do fluxo não fornecido');
    }

    // Buscar fluxo
    $sql = "SELECT ff.* FROM form_flows ff";

    if (!$permissionManager->canViewAllRecords()) {
        $sql .= " JOIN forms f ON ff.form_id = f.id WHERE ff.id = :flow_id AND f.user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':flow_id' => $flow_id,
            ':user_id' => $_SESSION['user_id']
        ]);
    } else {
        $sql .= " WHERE ff.id = :flow_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':flow_id' => $flow_id]);
    }

    $flow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$flow) {
        throw new Exception('Fluxo não encontrado');
    }

    echo json_encode([
        'success' => true,
        'flow' => $flow
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
