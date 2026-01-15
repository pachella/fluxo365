<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: /auth/login.php");
    exit;
}

// Carregar DB primeiro (se ainda não foi carregado)
if (!isset($pdo)) {
    require_once(__DIR__ . "/../../core/db.php");
}

// Carregar cache helper
require_once(__DIR__ . "/../../core/cache_helper.php");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | Supersites</title>
  <!-- Favicon -->
  <link rel="icon" type="image/webp" href="https://formtalk.app/wp-content/uploads/2025/11/cropped-favicon-20251107044740-32x32.webp">
  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
    }
  </script>
  <!-- Feather icons -->
  <script src="https://unpkg.com/feather-icons"></script>
  <!-- CSS do SweetAlert2 -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
  <!-- JS do SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- CSS Global Supersites -->
  <link rel="stylesheet" href="<?= assetUrl('/scripts/css/global.css') ?>">

  <!-- Scripts globais (ORDEM CORRETA) -->
  <script src="<?= assetUrl('/scripts/js/global/theme.js') ?>"></script>
  <script src="<?= assetUrl('/scripts/js/global/ui.js') ?>"></script>
  <script src="<?= assetUrl('/scripts/js/global/modals.js') ?>"></script>
  <script src="<?= assetUrl('/scripts/js/global/helpers.js') ?>"></script>

  <!-- Variáveis globais do usuário -->
  <script>
    window.userName = "<?= htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES) ?>";
    window.userEmail = "<?= htmlspecialchars($_SESSION['user_email'] ?? '', ENT_QUOTES) ?>";
  </script>
  
  <style>
    /* Remover TODAS as transições do tema dark (instantâneo) */
    html, html *, 
    body, body *, 
    nav, nav *,
    .dark, .dark * {
      transition: none !important;
    }
    
    /* Permitir transições APENAS em hovers específicos */
    button:not(.swal2-close):not(.swal2-confirm):not(.swal2-cancel):hover, 
    a:hover {
      transition: background-color 0.15s ease !important;
    }
    
    /* SweetAlert com animação bounce rápida ao ABRIR */
    .swal2-popup.swal2-show {
      animation: swal2-show 0.25s;
    }
    
    @keyframes swal2-show {
      0% {
        transform: scale(0.7);
      }
      45% {
        transform: scale(1.05);
      }
      80% {
        transform: scale(0.95);
      }
      100% {
        transform: scale(1);
      }
    }
    
    /* SweetAlert com animação bounce rápida ao FECHAR */
    .swal2-popup.swal2-hide {
      animation: swal2-hide 0.2s;
    }
    
    @keyframes swal2-hide {
      0% {
        transform: scale(1);
      }
      100% {
        transform: scale(0.7);
        opacity: 0;
      }
    }
    
    /* Backdrop rápido ao ABRIR */
    .swal2-container.swal2-backdrop-show {
      animation: swal2-backdrop-show 0.15s;
    }
    
    @keyframes swal2-backdrop-show {
      0% {
        opacity: 0;
      }
      100% {
        opacity: 1;
      }
    }
    
    /* Backdrop rápido ao FECHAR */
    .swal2-container.swal2-backdrop-hide {
      animation: swal2-backdrop-hide 0.15s;
    }
    
    @keyframes swal2-backdrop-hide {
      0% {
        opacity: 1;
      }
      100% {
        opacity: 0;
      }
    }
  </style>
  
  <!-- Dark Mode Script -->
  <script>
    // Aplicar tema antes da página carregar (evita flash)
    (function() {
      const theme = localStorage.getItem('theme') || 'light';
      if (theme === 'dark') {
        document.documentElement.classList.add('dark');
      }
    })();
  </script>
</head>
<body class="bg-gray-100 dark:bg-zinc-900 text-gray-800 dark:text-gray-200">
  <!-- Topbar -->
  <nav class="bg-white dark:bg-zinc-800 text-gray-800 dark:text-gray-200 shadow-sm border-b border-gray-200 dark:border-zinc-700">
    <div class="max-w-9xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <!-- Esquerda: Hambúrguer + Logo -->
        <div class="flex items-center space-x-3">
          <!-- Botão Hambúrguer (apenas mobile) -->
          <button onclick="openSidebar()" 
                  class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700"
                  aria-label="Abrir menu">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
          
          <!-- Logo -->
          <img src="/uploads/system/logo.png" alt="Supersites" class="h-7 dark:brightness-0 dark:invert">
        </div>
        
        <!-- Direita: Toggle + Usuário + Sair -->
        <div class="flex items-center space-x-2 sm:space-x-4">
          <!-- Toggle Dark Mode -->
          <button id="theme-toggle" 
                  class="p-2 rounded-lg bg-gray-200 dark:bg-zinc-700 hover:bg-gray-300 dark:hover:bg-zinc-600"
                  title="Alternar tema">
            <!-- Ícone Sol (visível no dark mode) -->
            <svg id="theme-toggle-sun" class="w-5 h-5 hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path>
            </svg>
            <!-- Ícone Lua (visível no light mode) -->
            <svg id="theme-toggle-moon" class="w-5 h-5 block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
              <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
            </svg>
          </button>
          
          <!-- Usuário (oculta texto no mobile) -->
          <span class="hidden sm:inline text-gray-700 dark:text-gray-300">
            <?= htmlspecialchars($_SESSION["user_name"]) ?>
            <span class="text-sm text-gray-500 dark:text-gray-400">(<?= htmlspecialchars($_SESSION["user_role"]) ?>)</span>
          </span>
          
          <!-- Botão Sair -->
          <a href="/auth/logout.php" 
             class="bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white px-3 py-1 rounded-md text-sm font-medium">
            Sair
          </a>
        </div>
      </div>
    </div>
  </nav>
  <div class="flex">

  <!-- Script do Toggle Dark Mode -->
  <script>
    const themeToggle = document.getElementById('theme-toggle');
    const html = document.documentElement;

    themeToggle.addEventListener('click', () => {
      if (html.classList.contains('dark')) {
        html.classList.remove('dark');
        localStorage.setItem('theme', 'light');
      } else {
        html.classList.add('dark');
        localStorage.setItem('theme', 'dark');
      }
    });

    // Variável global do role do usuário
    window.userRole = '<?= $_SESSION["user_role"] ?? "user" ?>';
  </script>