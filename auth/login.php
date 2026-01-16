<?php
session_start();
require_once("../core/db.php");
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Buscar usuário no banco
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->bindParam(":email", $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar senha
    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user_id"]   = $user["id"];
        $_SESSION["user_name"] = $user["name"];
        $_SESSION["user_role"] = $user["role"];
        $_SESSION["user_email"] = $user["email"];

        // ✅ SETAR CLIENT_ID PARA CLIENTES E AFILIADOS
        if (($user["role"] === "client" || $user["role"] === "affiliate") && !empty($user["client_id"])) {
            $_SESSION["client_id"] = $user["client_id"];
        }

        // ✅ REDIRECIONAR TODOS PARA /dashboard - O dashboard.php faz o roteamento correto
        header("Location: ../dashboard");
        exit;
    } else {
        $error = "E-mail ou senha inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Fluxo365</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" type="text/css" />
  <script src="https://unpkg.com/feather-icons"></script>
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
    .btn {
      border-radius: 8px !important;
    }
    .input {
      border-radius: 8px !important;
    }
    .card {
      border-radius: 8px !important;
    }
    .alert {
      border-radius: 8px !important;
    }

    /* Logo branco no tema escuro */
    .dark .logo-fluxo {
      filter: brightness(0) invert(1);
    }

    /* Toggle de tema fixo no canto superior direito */
    #theme-toggle-fixed {
      position: fixed;
      top: 15px;
      right: 15px;
      z-index: 9999;
    }
  </style>

  <!-- Dark Mode Script -->
  <script>
    (function() {
      const theme = localStorage.getItem('theme') || 'light';
      document.documentElement.setAttribute('data-theme', theme);
      if (theme === 'dark') {
        document.documentElement.classList.add('dark');
      }
    })();
  </script>
</head>
<body class="bg-base-200 flex items-center justify-center min-h-screen py-8 px-4">

  <!-- Theme Toggle - Fixo no canto superior direito -->
  <div id="theme-toggle-fixed">
    <label class="swap swap-rotate btn btn-ghost btn-circle btn-lg">
      <input type="checkbox" id="theme-toggle-auth" class="theme-controller" />
      <svg class="swap-off fill-current w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5.64,17l-.71.71a1,1,0,0,0,0,1.41,1,1,0,0,0,1.41,0l.71-.71A1,1,0,0,0,5.64,17ZM5,12a1,1,0,0,0-1-1H3a1,1,0,0,0,0,2H4A1,1,0,0,0,5,12Zm7-7a1,1,0,0,0,1-1V3a1,1,0,0,0-2,0V4A1,1,0,0,0,12,5ZM5.64,7.05a1,1,0,0,0,.7.29,1,1,0,0,0,.71-.29,1,1,0,0,0,0-1.41l-.71-.71A1,1,0,0,0,4.93,6.34Zm12,.29a1,1,0,0,0,.7-.29l.71-.71a1,1,0,1,0-1.41-1.41L17,5.64a1,1,0,0,0,0,1.41A1,1,0,0,0,17.66,7.34ZM21,11H20a1,1,0,0,0,0,2h1a1,1,0,0,0,0-2Zm-9,8a1,1,0,0,0-1,1v1a1,1,0,0,0,2,0V20A1,1,0,0,0,12,19ZM18.36,17A1,1,0,0,0,17,18.36l.71.71a1,1,0,0,0,1.41,0,1,1,0,0,0,0-1.41ZM12,6.5A5.5,5.5,0,1,0,17.5,12,5.51,5.51,0,0,0,12,6.5Zm0,9A3.5,3.5,0,1,1,15.5,12,3.5,3.5,0,0,1,12,15.5Z"/></svg>
      <svg class="swap-on fill-current w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21.64,13a1,1,0,0,0-1.05-.14,8.05,8.05,0,0,1-3.37.73A8.15,8.15,0,0,1,9.08,5.49a8.59,8.59,0,0,1,.25-2A1,1,0,0,0,8,2.36,10.14,10.14,0,1,0,22,14.05,1,1,0,0,0,21.64,13Zm-9.5,6.69A8.14,8.14,0,0,1,7.08,5.22v.27A10.15,10.15,0,0,0,17.22,15.63a9.79,9.79,0,0,0,2.1-.22A8.11,8.11,0,0,1,12.14,19.73Z"/></svg>
    </label>
  </div>

  <div class="w-full max-w-md">
    <!-- Logo -->
    <div class="text-center mb-8">
      <img src="https://fluxo365.com/wp-content/uploads/2026/01/logo_fluxo.svg" alt="Fluxo365" class="h-10 mx-auto logo-fluxo">
    </div>

    <div class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <h1 class="card-title text-2xl mb-1">Bem-vindo de volta</h1>
        <p class="text-sm opacity-60 mb-6">Faça login para acessar sua conta</p>

        <?php if (!empty($error)): ?>
          <div class="alert alert-error mb-6">
            <i data-feather="alert-circle" class="w-4 h-4"></i>
            <span><?= htmlspecialchars($error) ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
          <!-- E-mail -->
          <label class="input input-bordered flex items-center gap-2">
            <i data-feather="mail" class="w-5 h-5 opacity-60"></i>
            <input
              type="email"
              name="email"
              required
              placeholder="Digite seu e-mail"
              class="grow"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </label>

          <!-- Senha -->
          <div class="space-y-2">
            <label class="input input-bordered flex items-center gap-2">
              <i data-feather="lock" class="w-5 h-5 opacity-60"></i>
              <input
                type="password"
                name="password"
                required
                placeholder="Digite sua senha"
                class="grow">
            </label>
            <div class="text-right">
              <a href="forgot.php" class="text-sm link link-primary">Esqueceu a senha?</a>
            </div>
          </div>

          <!-- Botão Submit -->
          <button type="submit" class="btn btn-primary w-full">Entrar</button>
        </form>

        <!-- Link para Registro -->
        <div class="divider">OU</div>
        <div class="text-center text-sm">
          Não tem uma conta?
          <a href="register.php" class="link link-primary font-medium">
            Criar conta
          </a>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <p class="text-center text-xs opacity-50 mt-8">
      © 2025 Fluxo365. Todos os direitos reservados.
    </p>
  </div>

  <script>
    // Renderizar ícones do Feather
    feather.replace();

    // Theme toggle
    document.addEventListener('DOMContentLoaded', () => {
      const themeToggle = document.getElementById('theme-toggle-auth');
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
  </script>
</body>
</html>