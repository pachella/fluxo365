<?php
// Incluir sistema de permissões
require_once __DIR__ . '/../../core/PermissionManager.php';

// Incluir versionamento
require_once __DIR__ . '/../../config/version.php';

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
    return strpos($currentPage, $page) === 0 ? 'active' : '';
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
<aside id="sidebar" class="fixed lg:static inset-y-0 left-0 transform -translate-x-full lg:translate-x-0 w-64 bg-base-200 shadow-lg h-screen transition-transform duration-300 ease-in-out z-50 flex flex-col">
  <!-- Header da sidebar (mobile) -->
  <div class="lg:hidden flex items-center justify-between p-4 border-b border-base-300">
    <h2 class="text-lg font-bold">Menu</h2>
    <button onclick="closeSidebar()" class="btn btn-ghost btn-sm btn-square">
      <i data-feather="x" class="w-5 h-5"></i>
    </button>
  </div>

  <nav class="flex-1 p-4 overflow-y-auto">
    <ul class="menu menu-vertical w-full">
      <?php foreach ($moduleStructure as $config): ?>
        <li>
          <a href="<?= $config['url'] ?>" class="<?= isActive($config['name'], $currentPage) ?>">
            <i data-feather="<?= $config['icon'] ?>" class="w-5 h-5"></i>
            <?= $config['label'] ?>
            <?php if (isset($config['badge'])): ?>
              <span class="badge badge-primary badge-sm"><?= $config['badge'] ?></span>
            <?php endif; ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <!-- Perfil do usuário -->
  <div class="p-4 border-t border-base-300 bg-base-200">
    <div class="flex items-center gap-3 mb-2">
      <div class="avatar placeholder">
        <div class="bg-primary text-primary-content rounded-full w-10">
          <span class="text-xl font-bold">
            <?= strtoupper(substr($_SESSION["user_name"] ?? 'U', 0, 1)) ?>
          </span>
        </div>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium truncate"><?= htmlspecialchars($_SESSION["user_name"] ?? 'Usuário') ?></p>
        <p class="text-xs opacity-60"><?= $roleLabel ?></p>
      </div>
    </div>
    <div class="flex items-center justify-between">
      <a href="/auth/logout.php" class="btn btn-error btn-xs">
        <i data-feather="log-out" class="w-3 h-3"></i>
        Sair
      </a>
      <span class="text-xs opacity-40"><?= getAppVersion() ?></span>
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
<main class="flex-1 p-4 lg:p-6 bg-base-100 overflow-y-auto h-screen">