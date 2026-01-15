<?php
ob_clean();

session_start();
require_once(__DIR__ . "/../../core/db.php");
require_once __DIR__ . '/../../core/PermissionManager.php';

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

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo "ID não fornecido";
    exit();
}

try {
    $permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

    // Verificar se o formulário existe e se o usuário tem permissão
    $stmt = $pdo->prepare("SELECT * FROM forms WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $originalForm = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$originalForm) {
        http_response_code(404);
        echo "Formulário não encontrado";
        exit();
    }

    // Verificar permissão (usar canEditRecord pois duplicar é similar a editar/criar)
    if (!$permissionManager->canEditRecord($originalForm['user_id'])) {
        http_response_code(403);
        echo "Você não tem permissão para duplicar este formulário";
        exit();
    }

    // Iniciar transação
    $pdo->beginTransaction();

    // 1. DUPLICAR O FORMULÁRIO
    $newTitle = $originalForm['title'] . " (cópia)";

    $sql = "INSERT INTO forms (user_id, title, description, display_mode, status, folder_id)
            VALUES (:user_id, :title, :description, :display_mode, :status, :folder_id)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'], // Atribuir ao usuário que está duplicando
        ':title' => $newTitle,
        ':description' => $originalForm['description'],
        ':display_mode' => $originalForm['display_mode'],
        ':status' => 'rascunho', // Sempre criar como rascunho
        ':folder_id' => $originalForm['folder_id']
    ]);

    $newFormId = $pdo->lastInsertId();

    // 2. DUPLICAR OS CAMPOS (form_fields)
    $fieldsStmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = :form_id ORDER BY order_index");
    $fieldsStmt->execute([':form_id' => $id]);
    $fields = $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($fields as $field) {
        $insertFieldSql = "INSERT INTO form_fields
            (form_id, type, label, description, placeholder, options, required, allow_multiple,
             config, media, media_style, media_position, media_size, order_index)
            VALUES
            (:form_id, :type, :label, :description, :placeholder, :options, :required, :allow_multiple,
             :config, :media, :media_style, :media_position, :media_size, :order_index)";

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
            ':order_index' => $field['order_index']
        ]);
    }

    // 3. DUPLICAR CUSTOMIZAÇÕES (form_customizations) - se existir
    $customStmt = $pdo->prepare("SELECT * FROM form_customizations WHERE form_id = :form_id");
    $customStmt->execute([':form_id' => $id]);
    $customization = $customStmt->fetch(PDO::FETCH_ASSOC);

    if ($customization) {
        $insertCustomSql = "INSERT INTO form_customizations
            (form_id, background_color, text_color, primary_color, button_text_color,
             background_image, logo, button_radius, font_family,
             success_message_title, success_message_description)
            VALUES
            (:form_id, :background_color, :text_color, :primary_color, :button_text_color,
             :background_image, :logo, :button_radius, :font_family,
             :success_message_title, :success_message_description)";

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
            ':success_message_description' => $customization['success_message_description']
        ]);
    }

    // 4. DUPLICAR INTEGRAÇÕES (form_integrations) - se existir
    $integrationStmt = $pdo->prepare("SELECT * FROM form_integrations WHERE form_id = :form_id");
    $integrationStmt->execute([':form_id' => $id]);
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

    echo "success:" . $newFormId;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao duplicar formulário: " . $e->getMessage());
    http_response_code(500);
    echo "Erro ao duplicar formulário: " . $e->getMessage();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro interno: " . $e->getMessage());
    http_response_code(500);
    echo "Erro interno";
}

exit();
