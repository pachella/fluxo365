<?php
ob_clean();

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

    // Receber JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['form_id']) || !isset($data['items'])) {
        http_response_code(400);
        echo "Dados inválidos";
        exit();
    }

    $form_id = $data['form_id'];
    $items = $data['items']; // Array de objetos: [{type: 'field', id: 123}, {type: 'flow', id: 456}]

    if (!is_array($items)) {
        http_response_code(400);
        echo "Items deve ser um array";
        exit();
    }

    // Verificar se o usuário tem permissão para editar este formulário
    $formStmt = $pdo->prepare("SELECT user_id FROM forms WHERE id = :form_id");
    $formStmt->execute([':form_id' => $form_id]);
    $form = $formStmt->fetch(PDO::FETCH_ASSOC);

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

    $pdo->beginTransaction();

    // Atualizar order_index de campos e fluxos
    $fieldStmt = $pdo->prepare("UPDATE form_fields SET order_index = :order_index WHERE id = :id AND form_id = :form_id");
    $flowStmt = $pdo->prepare("UPDATE form_flows SET order_index = :order_index WHERE id = :id AND form_id = :form_id");

    foreach ($items as $index => $item) {
        if ($item['type'] === 'field') {
            $fieldStmt->execute([
                ':order_index' => $index,
                ':id' => $item['id'],
                ':form_id' => $form_id
            ]);
        } elseif ($item['type'] === 'flow') {
            $flowStmt->execute([
                ':order_index' => $index,
                ':id' => $item['id'],
                ':form_id' => $form_id
            ]);
        }
    }

    $pdo->commit();
    echo "success";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao salvar ordem: " . $e->getMessage());
    http_response_code(500);
    echo "Erro ao salvar ordem";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro interno: " . $e->getMessage());
    http_response_code(500);
    echo "Erro interno";
}

exit();
