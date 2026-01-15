<?php
session_start();
require_once("../core/db.php");

$message = "";
$messageType = "";
$step = "code"; // code ou password
$email = isset($_SESSION['reset_email']) ? $_SESSION['reset_email'] : '';

if (empty($email)) {
    header("Location: forgot.php");
    exit;
}

// Processar verificação do código
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['code'])) {
    $code = trim($_POST['code']);
    
    if (empty($code)) {
        $message = "Por favor, digite o código.";
        $messageType = "error";
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT email, expires_at 
                FROM password_resets 
                WHERE email = :email AND code = :code
                LIMIT 1
            ");
            $stmt->execute([
                ':email' => $email,
                ':code' => $code
            ]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($reset) {
                if (strtotime($reset['expires_at']) > time()) {
                    $_SESSION['reset_code_verified'] = true;
                    $step = "password";
                } else {
                    $message = "Este código expirou. Solicite um novo código.";
                    $messageType = "error";
                }
            } else {
                $message = "Código inválido. Verifique e tente novamente.";
                $messageType = "error";
            }
        } catch (PDOException $e) {
            error_log("Erro ao verificar código: " . $e->getMessage());
            $message = "Erro ao processar sua solicitação.";
            $messageType = "error";
        }
    }
}

// Processar nova senha
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['password']) && isset($_SESSION['reset_code_verified'])) {
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);
    
    if (empty($password) || empty($confirmPassword)) {
        $message = "Por favor, preencha todos os campos.";
        $messageType = "error";
        $step = "password";
    } elseif (strlen($password) < 6) {
        $message = "A senha deve ter no mínimo 6 caracteres.";
        $messageType = "error";
        $step = "password";
    } elseif ($password !== $confirmPassword) {
        $message = "As senhas não coincidem.";
        $messageType = "error";
        $step = "password";
    } else {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $updateStmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
            $updateStmt->execute([
                ':password' => $hashedPassword,
                ':email' => $email
            ]);
            
            $deleteStmt = $pdo->prepare("DELETE FROM password_resets WHERE email = :email");
            $deleteStmt->execute([':email' => $email]);
            
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_code_verified']);
            
            $message = "Senha redefinida com sucesso! Você pode fazer login agora.";
            $messageType = "success";
            $step = "success";
            
        } catch (PDOException $e) {
            error_log("Erro ao redefinir senha: " . $e->getMessage());
            $message = "Erro ao redefinir senha. Tente novamente.";
            $messageType = "error";
            $step = "password";
        }
    }
} elseif (isset($_SESSION['reset_code_verified'])) {
    $step = "password";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Redefinir Senha | Formtalk</title>
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

    .code-input {
      font-size: 24px;
      letter-spacing: 10px;
      text-align: center;
      font-weight: bold;
      padding-left: 0.75rem !important;
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
      <h1 class="text-2xl font-bold text-gray-900 mb-1">
        <?php
        if ($step === 'code') {
            echo 'Verificar código';
        } elseif ($step === 'password') {
            echo 'Nova senha';
        } else {
            echo 'Senha redefinida';
        }
        ?>
      </h1>
      <p class="text-gray-600 text-sm mb-6">
        <?php
        if ($step === 'code') {
            echo 'Digite o código de 6 dígitos enviado para<br><strong>' . htmlspecialchars($email) . '</strong>';
        } elseif ($step === 'password') {
            echo 'Crie uma nova senha segura para sua conta';
        } else {
            echo 'Sua senha foi redefinida com sucesso!';
        }
        ?>
      </p>

      <?php if (!empty($message)): ?>
        <div class="<?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?> p-3 rounded-lg mb-6 text-sm">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <?php if ($step === 'code'): ?>
        <form method="POST" action="">
          <div class="mb-6">
            <label for="code" class="block text-sm font-medium text-gray-700 mb-2 text-center">Código de Verificação</label>
            <input type="text" id="code" name="code" required maxlength="6" pattern="[0-9]{6}"
              class="code-input mt-1 block w-full px-3 py-3 border border-gray-300 rounded-lg shadow-sm
                     focus:outline-none focus:border-[#00AE5C] focus:ring-[#00AE5C]"
              placeholder="000000"
              style="box-shadow: 0 0 0 3px rgba(100, 207, 114, 0) !important; transition: all 0.2s;"
              onfocus="this.style.boxShadow='0 0 0 3px rgba(100, 207, 114, 0.1) !important';"
              onblur="this.style.boxShadow='0 0 0 3px rgba(100, 207, 114, 0) !important';">
            <p class="text-xs text-gray-500 text-center mt-2">Digite os 6 dígitos recebidos por e-mail</p>
          </div>

          <button type="submit"
            class="w-full btn-supersites text-white font-semibold py-3 rounded-lg transition-all hover:shadow-lg mb-6">
            Verificar código
          </button>

          <div class="text-center text-sm text-gray-600">
            Não recebeu o código?
            <a href="forgot.php" class="text-[#4EA44B] font-medium hover:underline">
              Solicitar novo código
            </a>
          </div>
        </form>

      <?php elseif ($step === 'password'): ?>
        <form method="POST" action="">
          <!-- Nova Senha -->
          <div class="input-with-icon password-field">
            <i class="fa-solid fa-key"></i>
            <input
              type="password"
              id="password"
              name="password"
              required
              placeholder="Nova senha (mínimo 6 caracteres)">
            <i class="fa-solid fa-eye toggle-password" onclick="togglePassword('password')"></i>
          </div>

          <!-- Confirmar Senha -->
          <div class="input-with-icon password-field">
            <i class="fa-solid fa-key"></i>
            <input
              type="password"
              id="confirm_password"
              name="confirm_password"
              required
              placeholder="Confirmar nova senha">
            <i class="fa-solid fa-eye toggle-password-confirm" onclick="togglePassword('confirm_password')"></i>
          </div>

          <!-- Botão Submit -->
          <button
            type="submit"
            class="w-full btn-supersites text-white font-semibold py-3 rounded-lg transition-all hover:shadow-lg mb-6">
            Redefinir senha
          </button>

          <!-- Link para Login -->
          <div class="text-center text-sm text-gray-600">
            Lembrou a senha?
            <a href="login.php" class="text-[#4EA44B] font-medium hover:underline">
              Fazer login
            </a>
          </div>
        </form>

      <?php else: ?>
        <div class="text-center">
          <div class="mb-6">
            <i class="fa-solid fa-circle-check text-6xl text-[#4EA44B] mb-4"></i>
          </div>
          <a href="login.php" class="inline-block btn-supersites text-white font-semibold py-3 px-8 rounded-lg transition-all hover:shadow-lg">
            Fazer login
          </a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Footer -->
    <p class="text-center text-xs text-gray-500 mt-8">
     © 2025 Grupo Pachella- Formtalk. Todos os direitos reservados.
    </p>
  </div>

  <script>
    function togglePassword(fieldId) {
      const passwordInput = document.getElementById(fieldId);
      const toggleIcon = fieldId === 'password'
        ? document.querySelector('.toggle-password')
        : document.querySelector('.toggle-password-confirm');

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