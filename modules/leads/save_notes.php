<?php
session_start();
require_once(__DIR__ . "/../../core/db.php");
require_once(__DIR__ . "/../../core/PermissionManager.php");

header('Content-Type: application/json');

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$leadId = $_POST['lead_id'] ?? null;
$notes = $_POST['notes'] ?? '';

if (!$leadId) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do lead não fornecido']);
    exit;
}

$permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

try {
    // Verificar se o lead existe e se o usuário tem permissão
    $checkSql = "SELECT fr.*, f.user_id as form_owner
                 FROM form_responses fr
                 INNER JOIN forms f ON fr.form_id = f.id
                 WHERE fr.id = :id";

    $stmt = $pdo->prepare($checkSql);
    $stmt->execute([':id' => $leadId]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lead) {
        http_response_code(404);
        echo json_encode(['error' => 'Lead não encontrado']);
        exit;
    }

    // Verificar permissão (usar canEditRecord em vez de canDeleteRecord)
    if (!$permissionManager->canEditRecord($lead['form_owner'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Sem permissão para editar este lead']);
        exit;
    }

    // Atualizar observações
    $updateSql = "UPDATE form_responses
                  SET notes = :notes,
                      notes_updated_at = NOW(),
                      notes_updated_by = :user_id
                  WHERE id = :id";

    $stmt = $pdo->prepare($updateSql);
    $stmt->execute([
        ':notes' => $notes,
        ':user_id' => $_SESSION['user_id'],
        ':id' => $leadId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Observações salvas com sucesso'
    ]);

} catch (PDOException $e) {
    error_log("Erro ao salvar observações: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao salvar observações']);
}
