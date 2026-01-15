<?php
// Dashboard do Cliente
require_once("../core/db.php");
require_once("../core/PermissionManager.php");

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo '<div class="p-6 text-center text-red-600 dark:text-red-400">Erro: Usuário não identificado na sessão.</div>';
    return;
}

$permissionManager = new PermissionManager(
    $_SESSION['user_role'],
    $_SESSION['user_id'] ?? null
);

try {
    // Dados do usuário
    $userData = $pdo->prepare("SELECT name, email, created_at FROM users WHERE id = ?");
    $userData->execute([$userId]);
    $user = $userData->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro na dashboard do cliente: " . $e->getMessage());
    $user = ['name' => 'Cliente', 'email' => '', 'created_at' => date('Y-m-d')];
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}
?>

<div class="w-full max-w-full overflow-x-hidden">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-6 gap-2">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100">
                Olá, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! 👋
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Bem-vindo ao seu painel de controle</p>
        </div>
        <div class="flex flex-col items-end gap-2">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Usuário desde: <?= formatDate($user['created_at']) ?>
            </div>
        </div>
    </div>

    <!-- Mensagem de boas-vindas -->
    <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-6 mb-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                <i data-feather="user" class="w-6 h-6 text-white"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    Sua Conta está Ativa
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    Este é seu painel pessoal. Em breve, novos módulos e funcionalidades estarão disponíveis aqui.
                </p>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        <strong>Email:</strong> <?= htmlspecialchars($user['email']) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards informativos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                <i data-feather="zap" class="w-5 h-5 mr-2 text-green-600"></i>
                Funcionalidades Disponíveis
            </h3>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <li class="flex items-center">
                    <i data-feather="check" class="w-4 h-4 mr-2 text-green-600"></i>
                    Acesso ao dashboard personalizado
                </li>
                <li class="flex items-center">
                    <i data-feather="check" class="w-4 h-4 mr-2 text-green-600"></i>
                    Gerenciamento de perfil
                </li>
                <li class="flex items-center text-gray-400 dark:text-gray-600">
                    <i data-feather="clock" class="w-4 h-4 mr-2"></i>
                    Novos módulos em desenvolvimento...
                </li>
            </ul>
        </div>

        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                <i data-feather="info" class="w-5 h-5 mr-2 text-blue-600"></i>
                Informações da Conta
            </h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mr-3">
                            <i data-feather="user" class="w-4 h-4 text-blue-600 dark:text-blue-300"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Conta</span>
                    </div>
                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">Cliente</span>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mr-3">
                            <i data-feather="check-circle" class="w-4 h-4 text-green-600 dark:text-green-300"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Status</span>
                    </div>
                    <span class="text-sm font-bold text-green-600 dark:text-green-400">Ativo</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Ajuda e Suporte -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 shadow rounded-lg p-6 text-white">
            <h3 class="text-base font-semibold mb-2 flex items-center">
                <i data-feather="help-circle" class="w-5 h-5 mr-2"></i>
                Precisa de Ajuda?
            </h3>
            <p class="text-sm mb-4 opacity-90">Nossa equipe está pronta para ajudar você!</p>
            <a href="mailto:suporte@supersites.com.br"
               class="inline-flex items-center px-4 py-2 bg-white text-indigo-600 rounded-lg hover:bg-gray-100 transition-colors text-sm font-medium">
                <i data-feather="mail" class="w-4 h-4 mr-2"></i>
                Entrar em Contato
            </a>
        </div>

        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2 flex items-center">
                <i data-feather="lightbulb" class="w-5 h-5 mr-2 text-yellow-500"></i>
                Dica do Dia
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Explore o menu lateral para acessar todas as funcionalidades disponíveis.
                Em breve, novos módulos serão adicionados ao sistema!
            </p>
        </div>
    </div>
</div>
