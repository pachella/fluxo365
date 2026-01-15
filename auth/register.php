<?php
session_start();
require_once("../core/db.php");

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $whatsapp = trim($_POST["whatsapp"]);
    $password = trim($_POST["password"]);
    
    // Validações
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Todos os campos são obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "E-mail inválido.";
    } elseif (strlen($password) < 8) {
        $error = "A senha deve ter no mínimo 8 caracteres.";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = "A senha deve conter letras maiúsculas, minúsculas e números.";
    } else {
        try {
            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            
            if ($stmt->fetch()) {
                $error = "Este e-mail já está cadastrado.";
            } else {
                // Criar usuário
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (name, email, whatsapp, password, role, created_at) 
                    VALUES (:name, :email, :whatsapp, :password, 'client', NOW())
                ");
                
                $insertStmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':whatsapp' => $whatsapp,
                    ':password' => $hashedPassword
                ]);
                
                // Pegar ID do usuário criado
                $userId = $pdo->lastInsertId();
                
                // Fazer login automático
                $_SESSION["user_id"] = $userId;
                $_SESSION["user_name"] = $name;
                $_SESSION["user_role"] = "client";
                $_SESSION["client_id"] = $userId;
                
                // Atualizar client_id no banco
                $updateStmt = $pdo->prepare("UPDATE users SET client_id = :client_id WHERE id = :id");
                $updateStmt->execute([
                    ':client_id' => $userId,
                    ':id' => $userId
                ]);
                
                // Redirecionar para dashboard
                header("Location: ../dashboard");
                exit;
            }
            
        } catch (PDOException $e) {
            error_log("Erro ao criar conta: " . $e->getMessage());
            $error = "Erro ao criar conta. Tente novamente mais tarde.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Criar Conta | Formtalk</title>
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
    
    .helper-text {
      font-size: 0.75rem;
      color: #6b7280;
      margin-top: 0.25rem;
      padding-left: 0.75rem;
    }
    
    /* Medidor de Força da Senha */
    .password-strength {
      height: 4px;
      background: #e5e7eb;
      border-radius: 2px;
      margin-top: 0.5rem;
      overflow: hidden;
      transition: all 0.3s;
    }
    
    .password-strength-bar {
      height: 100%;
      transition: all 0.3s;
      border-radius: 2px;
    }
    
    .strength-weak { 
      width: 33%; 
      background: #ef4444; 
    }
    
    .strength-medium { 
      width: 66%; 
      background: #f59e0b; 
    }
    
    .strength-strong { 
      width: 100%; 
      background: #00AE5C; 
    }
    
    .strength-text {
      font-size: 0.75rem;
      margin-top: 0.25rem;
      padding-left: 0.75rem;
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
      <h1 class="text-2xl font-bold text-gray-900 mb-1">Criar conta</h1>
      <p class="text-gray-600 text-sm mb-6">
        Crie uma conta em menos de 1 minuto!
      </p>
      
      <?php if (!empty($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg mb-6 text-sm">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
      
      <form method="POST" action="">
        <!-- Nome -->
        <div class="input-with-icon">
          <i class="fa-solid fa-user"></i>
          <input 
            type="text" 
            id="name" 
            name="name" 
            required
            placeholder="Nome completo"
            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>
        
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
        
        <!-- WhatsApp -->
        <div class="input-with-icon">
          <i class="fa-brands fa-whatsapp"></i>
          <input 
            type="tel" 
            id="whatsapp-phone" 
            name="whatsapp" 
            placeholder="WhatsApp (opcional)"
            value="<?= htmlspecialchars($_POST['whatsapp'] ?? '') ?>">
        </div>
        
        <!-- Senha -->
        <div class="input-with-icon password-field">
          <i class="fa-solid fa-key"></i>
          <input 
            type="password" 
            id="password" 
            name="password" 
            required
            placeholder="Senha"
            oninput="checkPasswordStrength(this.value)">
          <i class="fa-solid fa-eye toggle-password" onclick="togglePassword()"></i>
        </div>
        <div class="password-strength" style="margin-top: -0.5rem; margin-bottom: 1rem;">
          <div class="password-strength-bar" id="strengthBar"></div>
        </div>
        <p class="strength-text" id="strengthText" style="margin-top: -0.75rem; margin-bottom: 1rem;"></p>
        
        <!-- Termos -->
        <div class="mb-6 mt-4">
          <p class="text-center text-sm text-gray-600">
            Ao criar uma conta, você concorda com os nossos 
            <a href="/termos" target="_blank" class="text-[#4EA44B] font-medium hover:underline">
              Termos e Condições
            </a>.
          </p>
        </div>
        
        <!-- Botão Submit -->
        <button 
          type="submit" 
          class="w-full btn-supersites text-white font-semibold py-3 rounded-lg transition-all hover:shadow-lg mb-6">
          Criar minha conta
        </button>
        
        <!-- Link para Login -->
        <div class="text-center text-sm text-gray-600">
          Já possui uma conta? 
          <a href="login.php" class="text-[#4EA44B] font-medium hover:underline">
            Fazer login
          </a>
        </div>
      </form>
    </div>
    
    <!-- Footer -->
    <p class="text-center text-xs text-gray-500 mt-8">
     © 2025 Grupo Pachella- Formtalk. Todos os direitos reservados.
    </p>
  </div>
  
  <script src="/scripts/js/masks.js"></script>
  <script>
    // Aplicar máscaras automaticamente
    document.addEventListener('DOMContentLoaded', function() {
      console.log('DOM carregado');
      console.log('InputMasks:', typeof InputMasks);
      
      // Aplicar auto
      if (typeof InputMasks !== 'undefined') {
        InputMasks.autoApply();
        
        // Forçar aplicação manual no campo WhatsApp
        const whatsappInput = document.getElementById('whatsapp-phone');
        if (whatsappInput) {
          console.log('Campo WhatsApp encontrado');
          InputMasks.phone(whatsappInput);
        } else {
          console.log('Campo WhatsApp NÃO encontrado');
        }
      } else {
        console.error('InputMasks não está definido');
      }
    });
    
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
    
    function checkPasswordStrength(password) {
      const strengthBar = document.getElementById('strengthBar');
      const strengthText = document.getElementById('strengthText');
      
      // Critérios
      const hasMinLength = password.length >= 8;
      const hasUpperCase = /[A-Z]/.test(password);
      const hasLowerCase = /[a-z]/.test(password);
      const hasNumber = /[0-9]/.test(password);
      const hasSpecialChar = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
      
      let strength = 0;
      if (hasMinLength) strength++;
      if (hasUpperCase) strength++;
      if (hasLowerCase) strength++;
      if (hasNumber) strength++;
      if (hasSpecialChar) strength++;
      
      // Limpar classes
      strengthBar.className = 'password-strength-bar';
      
      if (password.length === 0) {
        strengthText.textContent = '';
        strengthBar.style.width = '0';
      } else if (strength <= 2) {
        strengthBar.classList.add('strength-weak');
        strengthText.textContent = 'Senha fraca';
        strengthText.style.color = '#ef4444';
      } else if (strength === 3 || strength === 4) {
        strengthBar.classList.add('strength-medium');
        strengthText.textContent = 'Senha média';
        strengthText.style.color = '#f59e0b';
      } else {
        strengthBar.classList.add('strength-strong');
        strengthText.textContent = 'Senha forte';
        strengthText.style.color = '#00AE5C';
      }
    }
  </script>
</body>
</html>