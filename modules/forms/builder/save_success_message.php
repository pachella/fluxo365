<?php
session_start();
require_once(__DIR__ . "/../../../core/db.php");
require_once __DIR__ . '/../../../core/PermissionManager.php';

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_SESSION["user_id"])) {
    echo "Erro: Não autenticado";
    exit;
}

$formId = $_POST['form_id'] ?? null;
$successMessageTitle = $_POST['success_message_title'] ?? '';
$successMessageDescription = $_POST['success_message_description'] ?? '';

// Novos campos de redirecionamento
$redirectEnabled = isset($_POST['success_redirect_enabled']) ? (int)$_POST['success_redirect_enabled'] : 0;
$redirectUrl = $_POST['success_redirect_url'] ?? null;
$redirectType = $_POST['success_redirect_type'] ?? 'automatic';
$redirectButtonText = $_POST['success_bt_redirect'] ?? 'Continuar';

// Campo de remoção de marca
$hideBranding = isset($_POST['hide_formtalk_branding']) ? (int)$_POST['hide_formtalk_branding'] : 0;

if (!$formId) {
    echo "Erro: ID do formulário não fornecido";
    exit;
}

// Verificar permissão
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
    echo "Erro: Formulário não encontrado ou sem permissão";
    exit;
}

try {
    // Verificar se já existe customização para este formulário
    $checkStmt = $pdo->prepare("SELECT id FROM form_customizations WHERE form_id = :form_id");
    $checkStmt->execute([':form_id' => $formId]);
    $existingCustomization = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingCustomization) {
        // Atualizar customização existente
        $updateStmt = $pdo->prepare("
            UPDATE form_customizations
            SET success_message_title = :title,
                success_message_description = :description,
                success_redirect_enabled = :redirect_enabled,
                success_redirect_url = :redirect_url,
                success_redirect_type = :redirect_type,
                success_bt_redirect = :redirect_button_text,
                hide_formtalk_branding = :hide_branding
            WHERE form_id = :form_id
        ");

        $updateStmt->execute([
            ':title' => $successMessageTitle,
            ':description' => $successMessageDescription,
            ':redirect_enabled' => $redirectEnabled,
            ':redirect_url' => $redirectUrl,
            ':redirect_type' => $redirectType,
            ':redirect_button_text' => $redirectButtonText,
            ':hide_branding' => $hideBranding,
            ':form_id' => $formId
        ]);
    } else {
        // Criar nova customização
        $insertStmt = $pdo->prepare("
            INSERT INTO form_customizations (
                form_id,
                success_message_title,
                success_message_description,
                success_redirect_enabled,
                success_redirect_url,
                success_redirect_type,
                success_bt_redirect,
                hide_formtalk_branding
            ) VALUES (
                :form_id,
                :title,
                :description,
                :redirect_enabled,
                :redirect_url,
                :redirect_type,
                :redirect_button_text,
                :hide_branding
            )
        ");

        $insertStmt->execute([
            ':form_id' => $formId,
            ':title' => $successMessageTitle,
            ':description' => $successMessageDescription,
            ':redirect_enabled' => $redirectEnabled,
            ':redirect_url' => $redirectUrl,
            ':redirect_type' => $redirectType,
            ':redirect_button_text' => $redirectButtonText,
            ':hide_branding' => $hideBranding
        ]);
    }

    echo "success";

} catch (PDOException $e) {
    error_log("Erro ao salvar mensagem de sucesso: " . $e->getMessage());
    echo "Erro: " . $e->getMessage();
}
?>