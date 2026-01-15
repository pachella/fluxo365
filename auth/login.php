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
  <title>Login | Formtalk</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .btn-supersites {
      background-color: #4EA44B;
    }
    .btn-supersites:hover {
      background-color: #00AE5C;
    }
    
    .input-with-icon {
      position: relative;
      margin-bottom: 1rem;
    }
    
    .input-with-icon i {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: #9ca3af;
      font-size: 1rem;
      pointer-events: none;
    }
    
    .input-with-icon .toggle-password {
      left: auto;
      right: 1rem;
      pointer-events: auto;
      cursor: pointer;
    }
    
    .input-with-icon input {
      width: 100%;
      padding: 0.875rem 0.75rem 0.875rem 2.75rem;
      border: 1px solid #d1d5db;
      border-radius: 0.5rem;
      font-size: 0.9375rem;
      outline: none;
      transition: all 0.2s;
    }
    
    .input-with-icon.password-field input {
      padding-right: 2.75rem;
    }
    
    .toggle-password:hover {
      color: #6b7280;
    }
    
    .input-with-icon input:focus {
      border-color: #00AE5C;
      box-shadow: 0 0 0 3px rgba(100, 207, 114, 0.1);
    }
    
    .input-with-icon input::placeholder {
      color: #9ca3af;
    }
  </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen py-8 px-4">
  <div class="w-full max-w-md">
    <!-- Logo -->
    <div class="text-center mb-8">
      <img src="/uploads/system/logo.png" alt="Supersites" class="h-10 mx-auto">
    </div>
    
    <div class="bg-white shadow-sm rounded-2xl p-8">
      <h1 class="text-2xl font-bold text-gray-900 mb-1">Bem-vindo de volta</h1>
      <p class="text-gray-600 text-sm mb-6">
        Faça login para acessar sua conta
      </p>
      
      <?php if (!empty($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg mb-6 text-sm">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
      
      <form method="POST" action="">
        <!-- E-mail -->
        <div class="input-with-icon">
          <i class="fa-solid fa-envelope"></i>
          <input 
            type="email" 
            id="email" 
            name="email" 
            required
            placeholder="E-mail"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        
        <!-- Senha -->
        <div class="input-with-icon password-field">
          <i class="fa-solid fa-key"></i>
          <input 
            type="password" 
            id="password" 
            name="password" 
            required
            placeholder="Senha">
          <i class="fa-solid fa-eye toggle-password" onclick="togglePassword()"></i>
        </div>
        
        <!-- Link Esqueci a senha -->
        <div class="text-right mb-6">
          <a href="forgot.php" class="text-sm text-[#4EA44B] font-medium hover:underline">
            Esqueci minha senha
          </a>
        </div>
        
        <!-- Botão Submit -->
        <button 
          type="submit" 
          class="w-full btn-supersites text-white font-semibold py-3 rounded-lg transition-all hover:shadow-lg mb-6">
          Entrar
        </button>
        
        <!-- Link para Registro -->
        <div class="text-center text-sm text-gray-600">
          Não tem uma conta? 
          <a href="register.php" class="text-[#4EA44B] font-medium hover:underline">
            Criar conta
          </a>
        </div>
      </form>
    </div>
    
    <!-- Footer -->
    <p class="text-center text-xs text-gray-500 mt-8">
     © 2025 Grupo Pachella- Formtalk. Todos os direitos reservados.
    </p>
  </div>
  
  <script>
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const toggleIcon = document.querySelector('.toggle-password');
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
      }
    }
  </script>
</body>
</html>