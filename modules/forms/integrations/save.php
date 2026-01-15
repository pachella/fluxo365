<?php
session_start();
require_once(__DIR__ . "/../../../core/db.php");
require_once __DIR__ . '/../../../core/PermissionManager.php';
require_once __DIR__ . '/../../../core/PlanService.php';
header('Content-Type: text/plain; charset=utf-8');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo "unauthorized";
    exit();
}

$formId = $_POST['form_id'] ?? null;

// Integração FREE: Google Sheets
$sheetsUrl = trim($_POST['sheets_url'] ?? '');
$sheetsEnabled = isset($_POST['sheets_enabled']) ? 1 : 0;

// Rastreamento FREE: UTM
$utmEnabled = isset($_POST['utm_enabled']) ? 1 : 0;

// Integrações e Rastreamento PRO - inicializar como desabilitados
$webhookUrl = '';
$webhookMethod = 'POST';
$webhookHeaders = '';
$webhookEnabled = 0;
$calendlyUrl = '';
$calendlyEnabled = 0;
$fbPixelId = '';
$fbPixelEnabled = 0;
$gtmId = '';
$gtmEnabled = 0;
$gaId = '';
$gaEnabled = 0;

// Se for PRO ou FULL, permite configurar recursos PRO
if (PlanService::hasProAccess()) {
    $webhookUrl = trim($_POST['webhook_url'] ?? '');
    $webhookMethod = $_POST['webhook_method'] ?? 'POST';
    $webhookHeaders = trim($_POST['webhook_headers'] ?? '');
    $webhookEnabled = isset($_POST['webhook_enabled']) ? 1 : 0;
    $calendlyUrl = trim($_POST['calendly_url'] ?? '');
    $calendlyEnabled = isset($_POST['calendly_enabled']) ? 1 : 0;
    $fbPixelId = trim($_POST['fb_pixel_id'] ?? '');
    $fbPixelEnabled = isset($_POST['fb_pixel_enabled']) ? 1 : 0;
    $gtmId = trim($_POST['gtm_id'] ?? '');
    $gtmEnabled = isset($_POST['gtm_enabled']) ? 1 : 0;
    $gaId = trim($_POST['ga_id'] ?? '');
    $gaEnabled = isset($_POST['ga_enabled']) ? 1 : 0;
}

if (!$formId) {
    http_response_code(400);
    echo "ID do formulário não fornecido";
    exit();
}

// Validações
if ($webhookEnabled && empty($webhookUrl)) {
    http_response_code(400);
    echo "URL do webhook é obrigatória quando habilitado";
    exit();
}

if ($webhookEnabled && !filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo "URL do webhook inválida";
    exit();
}

if ($sheetsEnabled && empty($sheetsUrl)) {
    http_response_code(400);
    echo "URL do Google Sheets é obrigatória quando habilitado";
    exit();
}

if ($calendlyEnabled && empty($calendlyUrl)) {
    http_response_code(400);
    echo "URL do Calendly é obrigatória quando habilitado";
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
        echo "Formulário não encontrado";
        exit();
    }

    // Verificar se já existe registro
    $stmt = $pdo->prepare("SELECT id FROM form_integrations WHERE form_id = :form_id");
    $stmt->execute([':form_id' => $formId]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $stmt = $pdo->prepare("
            UPDATE form_integrations
            SET webhook_url = :webhook_url,
                webhook_method = :webhook_method,
                webhook_headers = :webhook_headers,
                webhook_enabled = :webhook_enabled,
                sheets_url = :sheets_url,
                sheets_enabled = :sheets_enabled,
                calendly_url = :calendly_url,
                calendly_enabled = :calendly_enabled,
                utm_enabled = :utm_enabled,
                fb_pixel_id = :fb_pixel_id,
                fb_pixel_enabled = :fb_pixel_enabled,
                gtm_id = :gtm_id,
                gtm_enabled = :gtm_enabled,
                ga_id = :ga_id,
                ga_enabled = :ga_enabled,
                updated_at = NOW()
            WHERE form_id = :form_id
        ");
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO form_integrations
            (form_id, webhook_url, webhook_method, webhook_headers, webhook_enabled,
             sheets_url, sheets_enabled, calendly_url, calendly_enabled,
             utm_enabled, fb_pixel_id, fb_pixel_enabled, gtm_id, gtm_enabled, ga_id, ga_enabled,
             created_at, updated_at)
            VALUES
            (:form_id, :webhook_url, :webhook_method, :webhook_headers, :webhook_enabled,
             :sheets_url, :sheets_enabled, :calendly_url, :calendly_enabled,
             :utm_enabled, :fb_pixel_id, :fb_pixel_enabled, :gtm_id, :gtm_enabled, :ga_id, :ga_enabled,
             NOW(), NOW())
        ");
    }

    $stmt->execute([
        ':form_id' => $formId,
        ':webhook_url' => $webhookUrl,
        ':webhook_method' => $webhookMethod,
        ':webhook_headers' => $webhookHeaders,
        ':webhook_enabled' => $webhookEnabled,
        ':sheets_url' => $sheetsUrl,
        ':sheets_enabled' => $sheetsEnabled,
        ':calendly_url' => $calendlyUrl,
        ':calendly_enabled' => $calendlyEnabled,
        ':utm_enabled' => $utmEnabled,
        ':fb_pixel_id' => $fbPixelId,
        ':fb_pixel_enabled' => $fbPixelEnabled,
        ':gtm_id' => $gtmId,
        ':gtm_enabled' => $gtmEnabled,
        ':ga_id' => $gaId,
        ':ga_enabled' => $gaEnabled
    ]);

    echo "success";

} catch (PDOException $e) {
    error_log("Erro ao salvar integrações: " . $e->getMessage());
    http_response_code(500);
    echo "Erro ao salvar integrações: " . $e->getMessage();
}
