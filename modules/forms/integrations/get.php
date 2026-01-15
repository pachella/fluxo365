<?php
session_start();
require_once(__DIR__ . "/../../../core/db.php");
require_once __DIR__ . '/../../../core/PermissionManager.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit();
}

$formId = $_GET['id'] ?? null;

if (!$formId) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do formulário não fornecido']);
    exit();
}

try {
    $permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

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
        http_response_code(404);
        echo json_encode(['error' => 'Formulário não encontrado']);
        exit();
    }

    // Buscar integrações
    $stmt = $pdo->prepare("SELECT * FROM form_integrations WHERE form_id = :form_id");
    $stmt->execute([':form_id' => $formId]);
    $integrations = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$integrations) {
        echo json_encode([
            'webhook_url' => '',
            'webhook_method' => 'POST',
            'webhook_headers' => '',
            'webhook_enabled' => false,
            'sheets_url' => '',
            'sheets_enabled' => false,
            'calendly_url' => '',
            'calendly_enabled' => false,
            'utm_enabled' => false,
            'fb_pixel_id' => '',
            'fb_pixel_enabled' => false,
            'gtm_id' => '',
            'gtm_enabled' => false,
            'ga_id' => '',
            'ga_enabled' => false
        ]);
        exit();
    }

    // Retornar dados
    echo json_encode([
        'webhook_url' => $integrations['webhook_url'] ?? '',
        'webhook_method' => $integrations['webhook_method'] ?? 'POST',
        'webhook_headers' => $integrations['webhook_headers'] ?? '',
        'webhook_enabled' => (bool)($integrations['webhook_enabled'] ?? false),
        'sheets_url' => $integrations['sheets_url'] ?? '',
        'sheets_enabled' => (bool)($integrations['sheets_enabled'] ?? false),
        'calendly_url' => $integrations['calendly_url'] ?? '',
        'calendly_enabled' => (bool)($integrations['calendly_enabled'] ?? false),
        'utm_enabled' => (bool)($integrations['utm_enabled'] ?? false),
        'fb_pixel_id' => $integrations['fb_pixel_id'] ?? '',
        'fb_pixel_enabled' => (bool)($integrations['fb_pixel_enabled'] ?? false),
        'gtm_id' => $integrations['gtm_id'] ?? '',
        'gtm_enabled' => (bool)($integrations['gtm_enabled'] ?? false),
        'ga_id' => $integrations['ga_id'] ?? '',
        'ga_enabled' => (bool)($integrations['ga_enabled'] ?? false)
    ]);

} catch (PDOException $e) {
    error_log("Erro ao carregar integrações: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao carregar integrações']);
}
