<?php
session_start();
require_once(__DIR__ . "/../../../core/db.php");
require_once __DIR__ . '/../../../core/PermissionManager.php';

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$formId = $_GET['id'] ?? null;

if (!$formId) {
    echo json_encode(['error' => 'ID do formulário não informado']);
    exit;
}

$permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

// Verificar se o formulário existe e se o usuário tem permissão
$sql = "SELECT * FROM forms WHERE id = :id";
if (!$permissionManager->canViewAllRecords()) {
    $sql .= " AND user_id = :user_id";
}

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $formId, PDO::PARAM_INT);
if (!$permissionManager->canViewAllRecords()) {
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
}

$stmt->execute();
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) {
    echo json_encode(['error' => 'Formulário não encontrado']);
    exit;
}

// Buscar personalizações (ou retornar defaults)
$customStmt = $pdo->prepare("SELECT * FROM form_customizations WHERE form_id = :form_id");
$customStmt->execute([':form_id' => $formId]);
$customization = $customStmt->fetch(PDO::FETCH_ASSOC);

// Se não existir, retornar valores padrão
if (!$customization) {
    $customization = [
        'form_id' => $formId,
        'background_color' => '#ffffff',
        'text_color' => '#000000',
        'primary_color' => '#4f46e5',
        'background_image' => '',
        'logo' => '',
        'button_radius' => 8,
        'font_family' => 'Inter'
    ];
}

echo json_encode($customization);