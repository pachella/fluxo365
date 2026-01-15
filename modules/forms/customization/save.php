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
    // Verificar se já existe personalização
    $checkStmt = $pdo->prepare("SELECT id FROM form_customizations WHERE form_id = :form_id");
    $checkStmt->execute([':form_id' => $formId]);
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists) {
        // UPDATE
        $sql = "UPDATE form_customizations SET
                background_color = :background_color,
                text_color = :text_color,
                primary_color = :primary_color,
                button_text_color = :button_text_color,
                background_image = :background_image,
                logo = :logo,
                button_radius = :button_radius,
                font_family = :font_family,
                content_alignment = :content_alignment
                WHERE form_id = :form_id";
    } else {
        // INSERT
        $sql = "INSERT INTO form_customizations
                (form_id, background_color, text_color, primary_color, button_text_color, background_image, logo, button_radius, font_family, content_alignment)
                VALUES
                (:form_id, :background_color, :text_color, :primary_color, :button_text_color, :background_image, :logo, :button_radius, :font_family, :content_alignment)";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':form_id', $formId, PDO::PARAM_INT);
    $stmt->bindValue(':background_color', $_POST['background_color'] ?? '#ffffff');
    $stmt->bindValue(':text_color', $_POST['text_color'] ?? '#000000');
    $stmt->bindValue(':primary_color', $_POST['primary_color'] ?? '#4f46e5');
    $stmt->bindValue(':button_text_color', $_POST['button_text_color'] ?? '#ffffff');
    $stmt->bindValue(':background_image', $_POST['background_image'] ?? '');
    $stmt->bindValue(':logo', $_POST['logo'] ?? '');
    $stmt->bindValue(':button_radius', $_POST['button_radius'] ?? 8, PDO::PARAM_INT);
    $stmt->bindValue(':font_family', $_POST['font_family'] ?? 'Inter');
    $stmt->bindValue(':content_alignment', $_POST['content_alignment'] ?? 'center');
    
    $stmt->execute();
    
    echo "success";
    
} catch (PDOException $e) {
    echo "Erro ao salvar: " . $e->getMessage();
    http_response_code(500);
}