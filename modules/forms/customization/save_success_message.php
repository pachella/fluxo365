<?php
session_start();
require_once(__DIR__ . "/../../../core/db.php");
require_once __DIR__ . '/../../../core/PermissionManager.php';

if (!isset($_SESSION["user_id"])) {
    echo "Não autorizado";
    http_response_code(401);
    exit;
}

$formId = $_POST['form_id'] ?? null;
$successTitle = $_POST['success_message_title'] ?? 'Tudo certo!';
$successDescription = $_POST['success_message_description'] ?? 'Obrigado por responder nosso formulário.';

// Novos campos de redirecionamento
$redirectEnabled = isset($_POST['success_redirect_enabled']) ? (int)$_POST['success_redirect_enabled'] : 0;
$redirectUrl = $_POST['success_redirect_url'] ?? null;
$redirectType = $_POST['success_redirect_type'] ?? 'automatic';
$redirectButtonText = $_POST['success_bt_redirect'] ?? 'Continuar';

// Campo de exibir pontuação
$showScore = isset($_POST['show_score']) ? (int)$_POST['show_score'] : 0;

// Campo de remover marca Formtalk
$hideBranding = isset($_POST['hide_formtalk_branding']) ? (int)$_POST['hide_formtalk_branding'] : 0;

// Mídia da mensagem de sucesso
$successMessageMedia = $_POST['success_message_media'] ?? null;

if (!$formId) {
    echo "ID do formulário não informado";
    http_response_code(400);
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
    echo "Formulário não encontrado";
    http_response_code(404);
    exit;
}

try {
    // Auto-migration: Adicionar campo de mídia da mensagem de sucesso se não existir
    $columns = $pdo->query("SHOW COLUMNS FROM form_customizations LIKE 'success_message_media'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE form_customizations ADD COLUMN success_message_media TEXT DEFAULT NULL");
    }

    // Verificar se já existe personalização
    $checkStmt = $pdo->prepare("SELECT id FROM form_customizations WHERE form_id = :form_id");
    $checkStmt->execute([':form_id' => $formId]);
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        // UPDATE
        $sql = "UPDATE form_customizations SET
                success_message_title = :success_message_title,
                success_message_description = :success_message_description,
                success_message_media = :success_message_media,
                success_redirect_enabled = :success_redirect_enabled,
                success_redirect_url = :success_redirect_url,
                success_redirect_type = :success_redirect_type,
                success_bt_redirect = :success_bt_redirect,
                show_score = :show_score,
                hide_formtalk_branding = :hide_formtalk_branding
                WHERE form_id = :form_id";
    } else {
        // INSERT - criar personalização com valores padrão e as novas mensagens
        $sql = "INSERT INTO form_customizations
                (form_id, background_color, text_color, primary_color, button_text_color, background_image, logo, button_radius, font_family, success_message_title, success_message_description, success_message_media, success_redirect_enabled, success_redirect_url, success_redirect_type, success_bt_redirect, show_score, hide_formtalk_branding)
                VALUES
                (:form_id, :background_color, :text_color, :primary_color, :button_text_color, :background_image, :logo, :button_radius, :font_family, :success_message_title, :success_message_description, :success_message_media, :success_redirect_enabled, :success_redirect_url, :success_redirect_type, :success_bt_redirect, :show_score, :hide_formtalk_branding)";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':form_id', $formId, PDO::PARAM_INT);
    $stmt->bindValue(':success_message_title', $successTitle);
    $stmt->bindValue(':success_message_description', $successDescription);
    $stmt->bindValue(':success_message_media', $successMessageMedia);
    $stmt->bindValue(':success_redirect_enabled', $redirectEnabled, PDO::PARAM_INT);
    $stmt->bindValue(':success_redirect_url', $redirectUrl);
    $stmt->bindValue(':success_redirect_type', $redirectType);
    $stmt->bindValue(':success_bt_redirect', $redirectButtonText);
    $stmt->bindValue(':show_score', $showScore, PDO::PARAM_INT);
    $stmt->bindValue(':hide_formtalk_branding', $hideBranding, PDO::PARAM_INT);

    if (!$exists) {
        // Inserir campos com valores padrão para nova customização
        $stmt->bindValue(':background_color', '#ffffff');
        $stmt->bindValue(':text_color', '#000000');
        $stmt->bindValue(':primary_color', '#4f46e5');
        $stmt->bindValue(':button_text_color', '#ffffff');
        $stmt->bindValue(':background_image', '');
        $stmt->bindValue(':logo', '');
        $stmt->bindValue(':button_radius', 8, PDO::PARAM_INT);
        $stmt->bindValue(':font_family', 'Inter');
    }

    $stmt->execute();

    echo "success";

} catch (PDOException $e) {
    echo "Erro ao salvar: " . $e->getMessage();
    http_response_code(500);
}