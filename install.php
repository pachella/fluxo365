<?php
/**
 * Sistema de Instalação - Fluxo365
 * Versão: 1.0.0
 *
 * Este arquivo gerencia a instalação inicial do sistema
 */

session_start();

// Verificar se já está instalado
$configFile = __DIR__ . '/core/db.php';
$isInstalled = false;

if (file_exists($configFile)) {
    $configContent = file_get_contents($configFile);
    // Verifica se o arquivo tem configurações reais (não é o padrão)
    if (strpos($configContent, '___DB_HOST___') === false &&
        strpos($configContent, 'localhost') !== false &&
        strpos($configContent, '$host') !== false) {
        $isInstalled = true;
    }
}

// Se já instalado, redirecionar
if ($isInstalled && !isset($_GET['force'])) {
    header('Location: /');
    exit;
}

// Processar instalação
$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// ==========================================
// STEP 2: TESTAR CONEXÃO
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_connection'])) {
    $host = trim($_POST['db_host'] ?? '');
    $dbname = trim($_POST['db_name'] ?? '');
    $username = trim($_POST['db_user'] ?? '');
    $password = $_POST['db_pass'] ?? '';

    try {
        $testPdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
        $testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Tentar criar o banco se não existir
        $testPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Testar conexão com o banco
        $testPdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Salvar na sessão
        $_SESSION['install_db'] = [
            'host' => $host,
            'name' => $dbname,
            'user' => $username,
            'pass' => $password
        ];

        $success = "Conexão realizada com sucesso! O banco de dados está pronto.";
        $step = 2;

    } catch (PDOException $e) {
        $error = "Erro na conexão: " . $e->getMessage();
    }
}

// ==========================================
// STEP 3: CRIAR TABELAS E ADMIN
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_system'])) {

    if (!isset($_SESSION['install_db'])) {
        $error = "Erro: Dados de conexão não encontrados. Reinicie a instalação.";
    } else {
        $db = $_SESSION['install_db'];
        $adminName = trim($_POST['admin_name'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPassword = $_POST['admin_password'] ?? '';
        $adminPasswordConfirm = $_POST['admin_password_confirm'] ?? '';

        // Validações
        if (empty($adminName) || empty($adminEmail) || empty($adminPassword)) {
            $error = "Todos os campos são obrigatórios!";
        } elseif ($adminPassword !== $adminPasswordConfirm) {
            $error = "As senhas não coincidem!";
        } elseif (strlen($adminPassword) < 6) {
            $error = "A senha deve ter no mínimo 6 caracteres!";
        } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $error = "Email inválido!";
        } else {
            try {
                // Conectar ao banco
                $pdo = new PDO(
                    "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4",
                    $db['user'],
                    $db['pass']
                );
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Executar schema SQL
                $schemaFile = __DIR__ . '/install/schema.sql';
                if (!file_exists($schemaFile)) {
                    throw new Exception("Arquivo schema.sql não encontrado!");
                }

                $sql = file_get_contents($schemaFile);

                // Remover comentários e executar
                $sql = preg_replace('/--.*$/m', '', $sql);
                $statements = array_filter(array_map('trim', explode(';', $sql)));

                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $pdo->exec($statement);
                    }
                }

                // Criar usuário admin
                $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, email, password, role, plan, status, created_at)
                     VALUES (:name, :email, :password, 'admin', 'FULL', 'active', NOW())"
                );
                $stmt->execute([
                    'name' => $adminName,
                    'email' => $adminEmail,
                    'password' => $hashedPassword
                ]);

                // Salvar configurações no db.php
                $configTemplate = <<<'PHP'
<?php
$host = "___DB_HOST___";
$db   = "___DB_NAME___";
$user = "___DB_USER___";
$pass = "___DB_PASS___";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
PHP;

                $configContent = str_replace(
                    ['___DB_HOST___', '___DB_NAME___', '___DB_USER___', '___DB_PASS___'],
                    [$db['host'], $db['name'], $db['user'], $db['pass']],
                    $configTemplate
                );

                file_put_contents($configFile, $configContent);

                // Criar arquivo .installed para marcar
                file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));

                // Limpar sessão
                unset($_SESSION['install_db']);

                $step = 3;
                $success = "Instalação concluída com sucesso!";

            } catch (Exception $e) {
                $error = "Erro durante a instalação: " . $e->getMessage();
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Fluxo365</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">

<div class="w-full max-w-2xl">
    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">🚀 Fluxo365</h1>
        <p class="text-white text-lg opacity-90">Instalação do Sistema Base</p>
    </div>

    <!-- Card Principal -->
    <div class="card rounded-2xl shadow-2xl p-8">

        <!-- Indicador de Progresso -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center <?= $step >= 1 ? 'text-purple-600' : 'text-gray-400' ?>">
                    <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center font-bold <?= $step >= 1 ? 'border-purple-600 bg-purple-100' : 'border-gray-300' ?>">
                        <?= $step > 1 ? '✓' : '1' ?>
                    </div>
                    <span class="ml-2 font-medium hidden sm:inline">Banco de Dados</span>
                </div>
                <div class="flex-1 h-1 mx-4 <?= $step >= 2 ? 'bg-purple-600' : 'bg-gray-300' ?>"></div>
                <div class="flex items-center <?= $step >= 2 ? 'text-purple-600' : 'text-gray-400' ?>">
                    <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center font-bold <?= $step >= 2 ? 'border-purple-600 bg-purple-100' : 'border-gray-300' ?>">
                        <?= $step > 2 ? '✓' : '2' ?>
                    </div>
                    <span class="ml-2 font-medium hidden sm:inline">Administrador</span>
                </div>
                <div class="flex-1 h-1 mx-4 <?= $step >= 3 ? 'bg-purple-600' : 'bg-gray-300' ?>"></div>
                <div class="flex items-center <?= $step >= 3 ? 'text-purple-600' : 'text-gray-400' ?>">
                    <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center font-bold <?= $step >= 3 ? 'border-purple-600 bg-purple-100' : 'border-gray-300' ?>">
                        <?= $step > 3 ? '✓' : '3' ?>
                    </div>
                    <span class="ml-2 font-medium hidden sm:inline">Concluído</span>
                </div>
            </div>
        </div>

        <!-- Mensagens -->
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-start">
                <i class="fas fa-exclamation-circle mt-0.5 mr-3"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-start">
                <i class="fas fa-check-circle mt-0.5 mr-3"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <!-- STEP 1: Configuração do Banco -->
        <?php if ($step == 1): ?>
            <div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-database text-purple-600 mr-2"></i>
                    Configuração do Banco de Dados
                </h2>
                <p class="text-gray-600 mb-6">Configure as credenciais de acesso ao MySQL</p>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Host do Banco <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="db_host" value="localhost" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Geralmente é "localhost"</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nome do Banco <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="db_name" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                               placeholder="fluxo365">
                        <p class="text-xs text-gray-500 mt-1">O banco será criado automaticamente se não existir</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Usuário do MySQL <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="db_user" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                               placeholder="root">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Senha do MySQL
                        </label>
                        <input type="password" name="db_pass"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                               placeholder="Deixe em branco se não houver senha">
                    </div>

                    <button type="submit" name="test_connection"
                            class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white py-3 rounded-lg font-medium hover:from-purple-700 hover:to-blue-700 transition-all shadow-lg">
                        <i class="fas fa-plug mr-2"></i>
                        Testar Conexão e Continuar
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- STEP 2: Criar Administrador -->
        <?php if ($step == 2): ?>
            <div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-user-shield text-purple-600 mr-2"></i>
                    Criar Conta de Administrador
                </h2>
                <p class="text-gray-600 mb-6">Configure o usuário administrador do sistema</p>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nome Completo <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="admin_name" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                               placeholder="João Silva">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="admin_email" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                               placeholder="admin@exemplo.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Senha <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="admin_password" required minlength="6"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                               placeholder="Mínimo 6 caracteres">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Confirmar Senha <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="admin_password_confirm" required minlength="6"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                               placeholder="Digite a senha novamente">
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            Esta conta terá acesso total ao sistema e poderá criar outros usuários.
                        </p>
                    </div>

                    <button type="submit" name="install_system"
                            class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white py-3 rounded-lg font-medium hover:from-purple-700 hover:to-blue-700 transition-all shadow-lg">
                        <i class="fas fa-rocket mr-2"></i>
                        Instalar Sistema
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- STEP 3: Concluído -->
        <?php if ($step == 3): ?>
            <div class="text-center py-8">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check text-4xl text-green-600"></i>
                </div>

                <h2 class="text-3xl font-bold text-gray-800 mb-3">
                    Instalação Concluída!
                </h2>

                <p class="text-gray-600 mb-8">
                    O sistema foi instalado com sucesso e está pronto para uso.
                </p>

                <div class="bg-gray-50 rounded-lg p-6 mb-8 text-left">
                    <h3 class="font-semibold text-gray-800 mb-3">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        Próximos Passos:
                    </h3>
                    <ul class="space-y-2 text-gray-700">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-600 mt-1 mr-3"></i>
                            <span>Banco de dados configurado e tabelas criadas</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-600 mt-1 mr-3"></i>
                            <span>Conta de administrador criada</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-600 mt-1 mr-3"></i>
                            <span>Sistema pronto para adicionar novos módulos</span>
                        </li>
                    </ul>
                </div>

                <div class="space-y-3">
                    <a href="/"
                       class="block w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white py-3 rounded-lg font-medium hover:from-purple-700 hover:to-blue-700 transition-all shadow-lg">
                        <i class="fas fa-arrow-right mr-2"></i>
                        Acessar o Sistema
                    </a>

                    <p class="text-sm text-gray-500">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Por segurança, recomendamos remover o arquivo install.php após o uso
                    </p>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Footer -->
    <div class="text-center mt-6 text-white text-sm opacity-75">
        <p>Fluxo365 v1.0.0 - Sistema Base Modular</p>
    </div>
</div>

</body>
</html>
