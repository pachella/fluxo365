<?php
// Arquivo de proteção - incluir no topo de cada módulo
session_start();

// Carregar o PermissionManager
require_once __DIR__ . '/PermissionManager.php';

// Verificar se usuário está logado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    // Se é requisição AJAX, retorna JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Sessão expirada', 'redirect' => '/auth/login.php']);
        exit;
    }
    
    // Redireciona para login
    header('Location: /auth/login.php');
    exit;
}

// Detectar módulo atual baseado no caminho do arquivo
$currentPath = $_SERVER['PHP_SELF'];
$pathParts = explode('/', $currentPath);
$currentModule = null;

// Encontrar o módulo na URL (ex: /modules/clients/list.php -> clients)
$moduleIndex = array_search('modules', $pathParts);
if ($moduleIndex !== false && isset($pathParts[$moduleIndex + 1])) {
    $currentModule = $pathParts[$moduleIndex + 1];
}

// Se não conseguiu detectar o módulo, permitir acesso (para arquivos fora de modules/)
if (!$currentModule) {
    return;
}

// Criar instância do PermissionManager
$permissionManager = new PermissionManager(
    $_SESSION['user_role'],
    $_SESSION['client_id'] ?? null
);

// Verificar se tem permissão para acessar este módulo
if (!$permissionManager->canAccessModule($currentModule)) {
    // Log da tentativa de acesso não autorizado
    error_log("Acesso negado - Usuário: {$_SESSION['user_id']}, Role: {$_SESSION['user_role']}, Módulo: {$currentModule}");
    
    // Negar acesso
    $permissionManager->denyAccess("Você não tem permissão para acessar este módulo.");
}

// Se chegou até aqui, usuário tem permissão
// Disponibilizar o PermissionManager para uso no módulo
$GLOBALS['permissionManager'] = $permissionManager;

// Função helper para usar nos módulos
function getPermissionManager() {
    return $GLOBALS['permissionManager'];
}

// Função helper para filtros SQL
function getSQLFilter($table = '') {
    return $GLOBALS['permissionManager']->getSQLFilter($table);
}

// Função helper para verificar se pode editar
function canEdit($recordClientId) {
    return $GLOBALS['permissionManager']->canEditRecord($recordClientId);
}
?>