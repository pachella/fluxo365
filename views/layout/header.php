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
  <!-- DaisyUI via CDN -->
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" type="text/css" />
  <script>
    tailwind.config = {
      darkMode: 'class',
      daisyui: {
        themes: ["light", "dark"],
      }
    }
  </script>

  <!-- Customizações DaisyUI -->
  <style>
    /* Badges com mais padding e menos arredondamento */
    .badge {
      border-radius: 0.375rem !important; /* rounded-md */
      padding: 0.375rem 0.75rem !important; /* px-3 py-1.5 */
    }
    .badge-sm {
      padding: 0.25rem 0.5rem !important; /* px-2 py-1 */
    }
    .badge-xs {
      padding: 0.125rem 0.375rem !important; /* px-1.5 py-0.5 */
    }

    /* Botões menos arredondados */
    .btn {
      border-radius: 0.5rem !important; /* rounded-lg */
    }
    .btn-sm {
      border-radius: 0.375rem !important; /* rounded-md */
    }
    .btn-xs {
      border-radius: 0.25rem !important; /* rounded */
    }

    /* Cards menos arredondados */
    .card {
      border-radius: 0.5rem !important; /* rounded-lg */
    }
  </style>
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
      document.documentElement.setAttribute('data-theme', theme);
      if (theme === 'dark') {
        document.documentElement.classList.add('dark');
      }
    })();
  </script>
</head>
<body class="bg-base-100 flex h-screen overflow-hidden">

  <!-- Script do Toggle Dark Mode -->
  <script>
    // Sincronizar checkbox com tema atual
    document.addEventListener('DOMContentLoaded', () => {
      const themeToggle = document.getElementById('theme-toggle');
      if (themeToggle) {
        const html = document.documentElement;
        const currentTheme = localStorage.getItem('theme') || 'light';

        // Sincronizar estado inicial do checkbox
        themeToggle.checked = (currentTheme === 'dark');

        // Handler do toggle
        themeToggle.addEventListener('change', () => {
          if (themeToggle.checked) {
            html.classList.add('dark');
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
          } else {
            html.classList.remove('dark');
            html.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
          }

          // Re-renderizar ícones do Feather
          if (typeof feather !== 'undefined') {
            feather.replace();
          }
        });
      }
    });

    // Variável global do role do usuário
    window.userRole = '<?= $_SESSION["user_role"] ?? "user" ?>';
  </script>