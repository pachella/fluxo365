<?php
session_start();
// ✅ INCLUIR SISTEMA DE PERMISSÕES
require_once __DIR__ . '/../core/PermissionManager.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

// ✅ CRIAR INSTÂNCIA DO PERMISSION MANAGER
$permissionManager = new PermissionManager(
    $_SESSION['user_role'] ?? null,
    $_SESSION['client_id'] ?? null
);

// Caminhos absolutos baseados na raiz de "modules"
$basePath = __DIR__;
$viewsPath = dirname(__DIR__) . "/views/layout/";

// Inclui layout
require_once($viewsPath . "header.php");
require_once($viewsPath . "sidebar.php");

// Rota padrão
$page = $_GET['page'] ?? 'dashboard/home';

// Sanitização básica
if (!preg_match('#^[a-zA-Z0-9/_-]+$#', $page)) {
    $page = 'dashboard/home';
}

// ✅ EXTRAIR MÓDULO DA PÁGINA PARA VERIFICAR PERMISSÕES
$pageParts = explode('/', $page);
$module = $pageParts[0]; // Ex: 'clients' em 'clients/list'

// ✅ VERIFICAR PERMISSÕES ANTES DE CARREGAR MÓDULO
if (!$permissionManager->canAccessModule($module)) {
    echo '<div class="p-6">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <h1 class="text-xl font-bold mb-2">Acesso Negado</h1>
                <p>Você não tem permissão para acessar este módulo.</p>
            </div>
          </div>';
} else {
    // ✅ ROTEAMENTO DE DASHBOARDS POR TIPO DE USUÁRIO
    if ($page === 'dashboard/home') {
        $userRole = $_SESSION['user_role'] ?? null;
        
        // Admin → dashboard administrativa
        if ($permissionManager->isAdmin()) {
            $file = $basePath . '/dashboard/home.php';
        }
        // Cliente → dashboard de cliente
        elseif ($userRole === 'client') {
            $file = $basePath . '/dashboard/client.php';
        }
        // Afiliado → dashboard de afiliado
        elseif ($userRole === 'affiliate') {
            $file = $basePath . '/dashboard/affiliate.php';
        }
        // Outros → acesso negado
        else {
            echo '<div class="p-6">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <h1 class="text-xl font-bold mb-2">Acesso Negado</h1>
                        <p>Você não tem permissão para acessar a dashboard administrativa.</p>
                    </div>
                  </div>';
            $file = null;
        }
    } else {
        // ✅ OUTRAS PÁGINAS - CAMINHO NORMAL
        $file = $basePath . '/' . $page . '.php';
    }
    
    // ✅ CARREGAR ARQUIVO SE EXISTIR
    if ($file && file_exists($file)) {
        // ✅ DISPONIBILIZAR PERMISSION MANAGER PARA O MÓDULO
        $GLOBALS['permissionManager'] = $permissionManager;
        
        // Funções helper para usar nos módulos
        function getPermissionManager() {
            return $GLOBALS['permissionManager'];
        }
        
        function getSQLFilter($table = '') {
            return $GLOBALS['permissionManager']->getSQLFilter($table);
        }
        
        function canEdit($recordClientId) {
            return $GLOBALS['permissionManager']->canEditRecord($recordClientId);
        }
        
        require $file;
    } elseif ($file) {
        echo '<div class="p-6">
                <h1 class="text-xl font-bold mb-2">Página não encontrada</h1>
                <p class="text-gray-600">Verifique o link do menu.</p>
              </div>';
    }
}

// Inclui footer
require_once($viewsPath . "footer.php");
?>