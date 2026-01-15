<?php
// Incluir sistema de permissões
require_once __DIR__ . '/../../core/PermissionManager.php';

// Verificar se usuário está logado
if (!isset($_SESSION['user_role'])) {
    return;
}

// Criar instância do PermissionManager
$permissionManager = new PermissionManager(
    $_SESSION['user_role'],
    $_SESSION['user_id'] ?? null
);

$currentPage = $_GET['page'] ?? 'dashboard/home';

function isActive($page, $currentPage) {
    return strpos($currentPage, $page) === 0
        ? 'bg-gray-100 dark:bg-zinc-700 text-gray-900 dark:text-zinc-100'
        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 hover:text-gray-900 dark:hover:text-zinc-100';
}

// Escanear módulos automaticamente
$modulesPath = __DIR__ . '/../../modules';
$moduleStructure = [];



if (is_dir($modulesPath)) {
    $modules = array_diff(scandir($modulesPath), ['.', '..']);
    
    foreach ($modules as $module) {
        $configFile = "$modulesPath/$module/config.php";
        
        if (file_exists($configFile)) {
            $config = require $configFile;
            
            // Verificar se o usuário tem permissão para acessar este módulo
            if (isset($config['roles']) && in_array($_SESSION['user_role'], $config['roles'])) {
                $moduleStructure[$config['order']] = $config;
            }
        }
    }
    
    // Ordenar por ordem
    ksort($moduleStructure);
}

// Definir label do perfil baseado no role
$roleLabel = 'Usuário';
if ($permissionManager->isAdmin()) {
    $roleLabel = 'Administrador';
} elseif ($permissionManager->isClient()) {
    $roleLabel = 'Cliente';
}
?>

<!-- Overlay (mobile) -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed lg:static inset-y-0 left-0 transform -translate-x-full lg:translate-x-0 w-64 bg-white dark:bg-zinc-800 shadow-lg min-h-screen transition-transform duration-300 ease-in-out z-50 flex flex-col">
  <!-- Header da sidebar (mobile) -->
  <div class="lg:hidden flex items-center justify-between p-4 border-b border-gray-200 dark:border-zinc-700">
    <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200">Menu</h2>
    <button onclick="closeSidebar()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700 transition-colors">
      <i data-feather="x" class="w-5 h-5 text-gray-600 dark:text-gray-400"></i>
    </button>
  </div>

  <nav class="flex-1 p-4 overflow-y-auto mt-4">
    <ul class="space-y-2">
      
      <?php foreach ($moduleStructure as $config): ?>
        <li>
          <a href="<?= $config['url'] ?>"
             class="flex items-center justify-between px-3 py-2 rounded-lg transition-colors <?= isActive($config['name'], $currentPage) ?>">
            <span class="flex items-center">
              <i data-feather="<?= $config['icon'] ?>" class="w-5 h-5 mr-2"></i> <?= $config['label'] ?>
            </span>
            <?php if (isset($config['badge'])): ?>
              <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300">
                <?= $config['badge'] ?>
              </span>
            <?php endif; ?>
          </a>
        </li>
      <?php endforeach; ?>
      
    </ul>
  </nav>
  
  <!-- Perfil do usuário -->
  <div class="p-4 border-t border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 transition-colors duration-200">
    <div class="flex items-center space-x-3">
      <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold" style="background-color: #4EA44B;">
        <?= strtoupper(substr($_SESSION["user_name"] ?? 'U', 0, 1)) ?>
      </div>
      <div>
        <p class="text-sm font-medium text-gray-800 dark:text-gray-200"><?= htmlspecialchars($_SESSION["user_name"] ?? 'Usuário') ?></p>
        <p class="text-xs text-gray-500 dark:text-gray-400"><?= $roleLabel ?></p>
        <a href="/auth/logout.php" class="text-xs text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">Sair</a>
      </div>
    </div>
  </div>
</aside>

<script>
// Abrir sidebar (mobile)
function openSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    sidebar.classList.remove('-translate-x-full');
    overlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

// Fechar sidebar (mobile)
function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
    document.body.style.overflow = '';
}

// Fechar sidebar ao clicar em links (mobile)
document.addEventListener('DOMContentLoaded', function() {
    const sidebarLinks = document.querySelectorAll('#sidebar a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 1024) {
                closeSidebar();
            }
        });
    });
});
</script>

<!-- Área principal -->
<main class="flex-1 p-4 lg:p-6 bg-gray-100 dark:bg-zinc-900 transition-colors duration-200">