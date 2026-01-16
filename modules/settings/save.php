<?php
ob_clean();
session_start();
require_once(__DIR__ . "/../../core/db.php");
require_once(__DIR__ . "/../../core/PermissionManager.php");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo "Não autorizado";
    exit;
}

$permissionManager = new PermissionManager(
    $_SESSION['user_role'],
    $_SESSION['user_id'] ?? null
);

// Apenas admin pode salvar
if (!$permissionManager->isAdmin()) {
    http_response_code(403);
    echo "Apenas administradores podem alterar configurações";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo "Método não permitido";
    exit;
}

try {
    $settings = [
        'company_name' => trim($_POST['company_name'] ?? ''),
        'logo_url' => trim($_POST['logo_url'] ?? ''),
        'contact_email' => trim($_POST['contact_email'] ?? ''),
        'contact_phone' => trim($_POST['contact_phone'] ?? ''),
        'primary_color' => trim($_POST['primary_color'] ?? '#6366f1'),
        'secondary_color' => trim($_POST['secondary_color'] ?? '#8b5cf6'),
        'button_text_color' => trim($_POST['button_text_color'] ?? '#ffffff'),
        'use_gradient' => $_POST['use_gradient'] ?? '0'
    ];

    // Validar cores (formato hexadecimal)
    foreach (['primary_color', 'secondary_color', 'button_text_color'] as $colorField) {
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $settings[$colorField])) {
            http_response_code(400);
            echo "Cor inválida: {$colorField}";
            exit;
        }
    }

    // Inserir ou atualizar cada configuração
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");

    foreach ($settings as $key => $value) {
        $stmt->execute([$key, $value]);
    }

    echo "success";

} catch (PDOException $e) {
    http_response_code(500);
    error_log('Erro ao salvar configurações: ' . $e->getMessage());
    echo "Erro ao salvar configurações: " . $e->getMessage();
}
