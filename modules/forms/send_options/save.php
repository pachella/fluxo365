<?php
session_start();
require_once(__DIR__ . "/../../../core/db.php");
require_once __DIR__ . '/../../../core/PermissionManager.php';
header('Content-Type: text/plain; charset=utf-8');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo "unauthorized";
    exit();
}

$formId = $_POST['form_id'] ?? null;
$emailTo = trim($_POST['email_to'] ?? '');
$emailCc = trim($_POST['email_cc'] ?? '');

if (!$formId) {
    http_response_code(400);
    echo "ID do formulário não fornecido";
    exit();
}

if (empty($emailTo) || !filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo "E-mail principal inválido";
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
    
    $stmt = $pdo->prepare("SELECT id FROM form_integrations WHERE form_id = :form_id");
    $stmt->execute([':form_id' => $formId]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists) {
        $stmt = $pdo->prepare("
            UPDATE form_integrations 
            SET email_to = :email_to, 
                email_cc = :email_cc,
                updated_at = NOW()
            WHERE form_id = :form_id
        ");
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO form_integrations 
            (form_id, email_to, email_cc, created_at, updated_at) 
            VALUES 
            (:form_id, :email_to, :email_cc, NOW(), NOW())
        ");
    }
    
    $stmt->execute([
        ':form_id' => $formId,
        ':email_to' => $emailTo,
        ':email_cc' => $emailCc
    ]);
    
    echo "success";
    
} catch (PDOException $e) {
    error_log("Erro ao salvar opções de envio: " . $e->getMessage());
    http_response_code(500);
    echo "Erro ao salvar opções de envio";
}
