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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit();
}

try {
    $permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

    $field_id = $_POST['field_id'] ?? '';
    $flow_id = $_POST['flow_id'] ?? null;

    if (empty($field_id)) {
        throw new Exception('ID do campo não fornecido');
    }

    // flow_id pode ser null (remover do fluxo) ou um número (adicionar ao fluxo)
    $flow_id = ($flow_id === '' || $flow_id === 'null') ? null : intval($flow_id);

    // Verificar se o campo pertence ao usuário
    if (!$permissionManager->canViewAllRecords()) {
        $checkStmt = $pdo->prepare("
            SELECT ff.id
            FROM form_fields ff
            JOIN forms f ON ff.form_id = f.id
            WHERE ff.id = :field_id AND f.user_id = :user_id
        ");
        $checkStmt->execute([
            ':field_id' => $field_id,
            ':user_id' => $_SESSION['user_id']
        ]);
        if (!$checkStmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Acesso negado']);
            exit();
        }
    }

    // Atualizar flow_id do campo
    try {
        $stmt = $pdo->prepare("UPDATE form_fields SET flow_id = :flow_id WHERE id = :field_id");
        $stmt->execute([
            ':flow_id' => $flow_id,
            ':field_id' => $field_id
        ]);

        error_log('update_field_flow.php - Campo #' . $field_id . ' ' . ($flow_id ? 'adicionado ao fluxo #' . $flow_id : 'removido do fluxo'));

        echo json_encode([
            'success' => true,
            'message' => $flow_id ? 'Campo adicionado ao fluxo' : 'Campo removido do fluxo'
        ]);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'flow_id') !== false) {
            throw new Exception('Erro: A coluna flow_id não existe. Execute o SQL em migrations/add_flow_id_to_fields.sql');
        }
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
