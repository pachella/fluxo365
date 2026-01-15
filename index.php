<?php
/**
 * Página Inicial - Fluxo365
 */

// ==========================================
// VERIFICAR SE O SISTEMA PRECISA DE INSTALAÇÃO
// ==========================================
$configFile = __DIR__ . '/core/db.php';
$needsInstallation = false;

// Verificar se o arquivo de configuração existe e está configurado
if (!file_exists($configFile)) {
    $needsInstallation = true;
} else {
    $configContent = file_get_contents($configFile);
    // Verificar se ainda tem placeholders ou se está vazio
    if (strpos($configContent, '___DB_HOST___') !== false ||
        strpos($configContent, 'webformtalk_forms') !== false ||
        trim($configContent) === '') {
        $needsInstallation = true;
    }
}

// Redirecionar para instalação se necessário
if ($needsInstallation) {
    header("Location: /install.php");
    exit;
}

// ==========================================
// SISTEMA INSTALADO - CONTINUAR NORMALMENTE
// ==========================================
session_start();

// Verificar se o usuário está logado
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Usuário logado - redirecionar para dashboard
    header("Location: /dashboard/");
    exit;
} else {
    // Usuário não logado - redirecionar para login
    header("Location: /auth/login.php");
    exit;
}
?>