<?php
/**
 * Retorna lista de campos disponíveis para lógica condicional
 * Retorna apenas campos anteriores ao campo atual (baseado em order_index)
 */
session_start();
require_once(__DIR__ . "/../../../core/db.php");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    die(json_encode(['error' => 'Não autenticado']));
}

$formId = $_GET['form_id'] ?? null;
$currentFieldId = $_GET['current_field_id'] ?? null;

if (!$formId) {
    http_response_code(400);
    die(json_encode(['error' => 'form_id é obrigatório']));
}

// Se estamos editando um campo, buscar campos anteriores
if ($currentFieldId) {
    // Buscar order_index do campo atual
    $currentStmt = $pdo->prepare("SELECT order_index FROM form_fields WHERE id = :id");
    $currentStmt->execute([':id' => $currentFieldId]);
    $currentField = $currentStmt->fetch(PDO::FETCH_ASSOC);

    if ($currentField) {
        // Buscar apenas campos anteriores
        $stmt = $pdo->prepare("
            SELECT id, label, type, order_index
            FROM form_fields
            WHERE form_id = :form_id
            AND order_index < :order_index
            AND type NOT IN ('welcome', 'message', 'terms')
            ORDER BY order_index ASC
        ");
        $stmt->execute([
            ':form_id' => $formId,
            ':order_index' => $currentField['order_index']
        ]);
    } else {
        // Se não encontrou o campo, retornar todos
        $stmt = $pdo->prepare("
            SELECT id, label, type, order_index
            FROM form_fields
            WHERE form_id = :form_id
            AND type NOT IN ('welcome', 'message', 'terms')
            ORDER BY order_index ASC
        ");
        $stmt->execute([':form_id' => $formId]);
    }
} else {
    // Se não há campo atual (novo campo), retornar todos
    $stmt = $pdo->prepare("
        SELECT id, label, type, order_index
        FROM form_fields
        WHERE form_id = :form_id
        AND type NOT IN ('welcome', 'message', 'terms')
        ORDER BY order_index ASC
    ");
    $stmt->execute([':form_id' => $formId]);
}

$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($fields);
