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
      border-radius: 4px !important;
    }
    .input {
      border-radius: 4px !important;
    }
    .card {
      border-radius: 4px !important;
    }
    .alert {
      border-radius: 4px !important;
    }

    /* Logo branco no tema escuro */
    .dark .logo-fluxo {
      filter: brightness(0) invert(1);
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
          <div class="form-control">
            <label class="label">
              <span class="label-text">E-mail</span>
            </label>
            <input
              type="email"
              name="email"
              required
              placeholder="seu@email.com"
              class="input input-bordered w-full"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>

          <!-- Senha -->
          <div class="form-control">
            <label class="label">
              <span class="label-text">Senha</span>
              <a href="forgot.php" class="label-text-alt link link-primary">Esqueceu a senha?</a>
            </label>
            <input
              type="password"
              name="password"
              required
              placeholder="••••••••"
              class="input input-bordered w-full">
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

    <!-- Theme Toggle -->
    <div class="text-center mt-6">
      <label class="swap swap-rotate btn btn-ghost btn-circle">
        <input type="checkbox" id="theme-toggle-auth" class="theme-controller" />
        <i data-feather="sun" class="swap-off w-5 h-5"></i>
        <i data-feather="moon" class="swap-on w-5 h-5"></i>
      </label>
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

        themeToggle.checked = (currentTheme === 'dark');

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
          feather.replace();
        });
      }
    });
  </script>
</body>
</html>