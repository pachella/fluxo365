<?php
/**
 * Endpoint: Usar template
 *
 * Duplica um formulário template e atribui ao usuário
 * Diferente do duplicate.php, não adiciona "(cópia)" ao nome
 */

ob_clean();
session_start();
require_once(__DIR__ . "/../../core/db.php");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit();
}

$templateId = $_POST['template_id'] ?? null;

if (!$templateId) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do template não fornecido']);
    exit();
}

try {
    // Verificar se o template existe e está na pasta Templates
    $stmt = $pdo->prepare("SELECT * FROM forms WHERE id = :id AND folder_id = 8");
    $stmt->execute([':id' => $templateId]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        http_response_code(404);
        echo json_encode(['error' => 'Template não encontrado']);
        exit();
    }

    // Iniciar transação
    $pdo->beginTransaction();

    // 1. DUPLICAR O FORMULÁRIO (sem adicionar "cópia" ao nome)
    $sql = "INSERT INTO forms (user_id, title, description, display_mode, status, folder_id, icon, color)
            VALUES (:user_id, :title, :description, :display_mode, :status, NULL, :icon, :color)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'], // Atribuir ao usuário atual
        ':title' => $template['title'], // Manter o título original
        ':description' => $template['description'],
        ':display_mode' => $template['display_mode'],
        ':status' => 'rascunho', // Criar como rascunho
        ':icon' => $template['icon'],
        ':color' => $template['color']
    ]);

    $newFormId = $pdo->lastInsertId();

    // 2. DUPLICAR OS CAMPOS (form_fields)
    $fieldsStmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = :form_id ORDER BY order_index");
    $fieldsStmt->execute([':form_id' => $templateId]);
    $fields = $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($fields as $field) {
        $insertFieldSql = "INSERT INTO form_fields
            (form_id, type, label, description, placeholder, options, required, allow_multiple,
             config, media, media_style, media_position, media_size, order_index, conditional_logic)
            VALUES
            (:form_id, :type, :label, :description, :placeholder, :options, :required, :allow_multiple,
             :config, :media, :media_style, :media_position, :media_size, :order_index, :conditional_logic)";

        $insertFieldStmt = $pdo->prepare($insertFieldSql);
        $insertFieldStmt->execute([
            ':form_id' => $newFormId,
            ':type' => $field['type'],
            ':label' => $field['label'],
            ':description' => $field['description'],
            ':placeholder' => $field['placeholder'],
            ':options' => $field['options'],
            ':required' => $field['required'],
            ':allow_multiple' => $field['allow_multiple'],
            ':config' => $field['config'],
            ':media' => $field['media'],
            ':media_style' => $field['media_style'],
            ':media_position' => $field['media_position'],
            ':media_size' => $field['media_size'],
            ':order_index' => $field['order_index'],
            ':conditional_logic' => $field['conditional_logic']
        ]);
    }

    // 3. DUPLICAR CUSTOMIZAÇÕES (form_customizations) - se existir
    $customStmt = $pdo->prepare("SELECT * FROM form_customizations WHERE form_id = :form_id");
    $customStmt->execute([':form_id' => $templateId]);
    $customization = $customStmt->fetch(PDO::FETCH_ASSOC);

    if ($customization) {
        $insertCustomSql = "INSERT INTO form_customizations
            (form_id, background_color, text_color, primary_color, button_text_color,
             background_image, logo, button_radius, font_family,
             success_message_title, success_message_description,
             success_redirect_enabled, success_redirect_url, success_redirect_type, success_bt_redirect,
             hide_branding)
            VALUES
            (:form_id, :background_color, :text_color, :primary_color, :button_text_color,
             :background_image, :logo, :button_radius, :font_family,
             :success_message_title, :success_message_description,
             :success_redirect_enabled, :success_redirect_url, :success_redirect_type, :success_bt_redirect,
             :hide_branding)";

        $insertCustomStmt = $pdo->prepare($insertCustomSql);
        $insertCustomStmt->execute([
            ':form_id' => $newFormId,
            ':background_color' => $customization['background_color'],
            ':text_color' => $customization['text_color'],
            ':primary_color' => $customization['primary_color'],
            ':button_text_color' => $customization['button_text_color'],
            ':background_image' => $customization['background_image'],
            ':logo' => $customization['logo'],
            ':button_radius' => $customization['button_radius'],
            ':font_family' => $customization['font_family'],
            ':success_message_title' => $customization['success_message_title'],
            ':success_message_description' => $customization['success_message_description'],
            ':success_redirect_enabled' => $customization['success_redirect_enabled'],
            ':success_redirect_url' => $customization['success_redirect_url'],
            ':success_redirect_type' => $customization['success_redirect_type'],
            ':success_bt_redirect' => $customization['success_bt_redirect'],
            ':hide_branding' => $customization['hide_branding']
        ]);
    }

    // 4. DUPLICAR INTEGRAÇÕES (form_integrations) - se existir
    $integrationStmt = $pdo->prepare("SELECT * FROM form_integrations WHERE form_id = :form_id");
    $integrationStmt->execute([':form_id' => $templateId]);
    $integration = $integrationStmt->fetch(PDO::FETCH_ASSOC);

    if ($integration) {
        $insertIntegrationSql = "INSERT INTO form_integrations
            (form_id, email_to, email_cc, sheets_url, sheets_enabled)
            VALUES
            (:form_id, :email_to, :email_cc, :sheets_url, :sheets_enabled)";

        $insertIntegrationStmt = $pdo->prepare($insertIntegrationSql);
        $insertIntegrationStmt->execute([
            ':form_id' => $newFormId,
            ':email_to' => $integration['email_to'],
            ':email_cc' => $integration['email_cc'],
            ':sheets_url' => $integration['sheets_url'],
            ':sheets_enabled' => $integration['sheets_enabled']
        ]);
    }

    // Commit da transação
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'form_id' => $newFormId
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao usar template: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao usar template: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro interno: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno']);
}

exit();
