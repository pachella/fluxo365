<?php
session_start();
require_once(__DIR__ . "/../../../core/db.php");
require_once __DIR__ . '/../../../core/PermissionManager.php';
require_once __DIR__ . '/../../../core/PlanService.php';

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Verificar se tem acesso PRO
if (!PlanService::hasProAccess()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Recurso PRO']);
    exit;
}

$partialId = $_GET['id'] ?? null;

if (!$partialId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID não fornecido']);
    exit;
}

$permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

// Buscar resposta parcial
$sql = "SELECT pr.*, f.user_id as form_user_id
        FROM partial_responses pr
        INNER JOIN forms f ON pr.form_id = f.id
        WHERE pr.id = :id";

if (!$permissionManager->canViewAllRecords()) {
    $sql .= " AND f.user_id = :user_id";
}

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $partialId, PDO::PARAM_INT);
if (!$permissionManager->canViewAllRecords()) {
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
}

$stmt->execute();
$partial = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partial) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Resposta parcial não encontrada']);
    exit;
}

http_response_code(200);
echo json_encode([
    'success' => true,
    'partial' => $partial
]);
