<?php
session_start();
require_once("../core/db.php");
require_once("../core/EmailService.php");

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Por favor, insira um e-mail válido.";
        $messageType = "error";
    } else {
        try {
            // Verificar se o email existe
            $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Gerar código de 6 dígitos
                $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Verificar se já existe um código para este email e deletar
                $deleteStmt = $pdo->prepare("DELETE FROM password_resets WHERE email = :email");
                $deleteStmt->execute([':email' => $email]);
                
                // Inserir novo código
                $insertStmt = $pdo->prepare("
                    INSERT INTO password_resets (email, code, expires_at, created_at) 
                    VALUES (:email, :code, :expires_at, NOW())
                ");
                $insertStmt->execute([
                    ':email' => $email,
                    ':code' => $code,
                    ':expires_at' => $expires
                ]);
                
                // Enviar email com código
                $emailService = new EmailService($pdo);
                $emailSent = $emailService->sendTemplate('password_reset_code', $email, [
                    'user_name' => $user['name'],
                    'reset_code' => $code,
                    'expires_time' => '1 hora'
                ]);
                
                if ($emailSent) {
                    $_SESSION['reset_email'] = $email;
                    header("Location: reset.php");
                    exit;
                } else {
                    $message = "Erro ao enviar o e-mail. Tente novamente mais tarde.";
                    $messageType = "error";
                }
            } else {
                // Por segurança, mostramos a mesma mensagem mesmo se o email não existir
                $_SESSION['reset_email'] = $email;
                header("Location: reset.php");
                exit;
            }
            
        } catch (PDOException $e) {
            error_log("Erro ao processar recuperação de senha: " . $e->getMessage());
            $message = "Erro ao processar sua solicitação. Tente novamente mais tarde.";
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar Senha | Formtalk</title>
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

    .input-with-icon input {
      width: 100%;
      padding: 0.875rem 0.75rem 0.875rem 2.75rem;
      border: 1px solid #d1d5db;
      border-radius: 0.5rem;
      font-size: 0.9375rem;
      outline: none;
      transition: all 0.2s;
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
      <h1 class="text-2xl font-bold text-gray-900 mb-1">Recuperar senha</h1>
      <p class="text-gray-600 text-sm mb-6">
        Informe seu e-mail para receber um código de recuperação
      </p>

      <?php if (!empty($message)): ?>
        <div class="<?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?> p-3 rounded-lg mb-6 text-sm">
          <?php echo htmlspecialchars($message); ?>
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
            value="">
        </div>

        <!-- Botão Submit -->
        <button
          type="submit"
          class="w-full btn-supersites text-white font-semibold py-3 rounded-lg transition-all hover:shadow-lg mb-6">
          Enviar código
        </button>

        <!-- Link para Login -->
        <div class="text-center text-sm text-gray-600">
          Lembrou a senha?
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
</body>
</html>