<?php
// Buscar dados reais do banco
require_once("../core/db.php");
require_once("../core/PermissionManager.php");

// Criar instância do PermissionManager
$permissionManager = new PermissionManager(
    $_SESSION['user_role'],
    $_SESSION['user_id'] ?? null
);

try {
    // Usuários (apenas para admin)
    $usersCount = 0;
    if ($permissionManager->isAdmin()) {
        $usersCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
    }

} catch (PDOException $e) {
    $usersCount = 0;
    error_log('Erro ao buscar dados do dashboard: ' . $e->getMessage());
}
?>

<div class="w-full max-w-full overflow-x-hidden">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 sm:mb-6 gap-2">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100">Dashboard</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                <?php if ($permissionManager->isAdmin()): ?>
                    Visão geral do sistema
                <?php else: ?>
                    Bem-vindo, <?= htmlspecialchars($_SESSION['user_name']) ?>!
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Cards de estatísticas principais -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <?php if ($permissionManager->isAdmin()): ?>
            <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Usuários Cadastrados</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?= number_format($usersCount) ?></p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">clientes ativos</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                        <i data-feather="users" class="w-6 h-6 text-blue-600 dark:text-blue-300"></i>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Mensagem de boas-vindas -->
    <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-6 mb-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                <i data-feather="zap" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    Bem-vindo ao Sistema Base!
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    Este é o sistema base limpo e organizado, pronto para receber novos módulos.
                    A estrutura está preparada para crescer de forma modular e escalável.
                </p>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        <strong>Estrutura Modular:</strong> Os próximos módulos (como CRM, SDR, etc.) serão adicionados em
                        <code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-800 rounded text-xs">/modules/</code>
                        mantendo o código organizado e separado por funcionalidade.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards informativos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                <i data-feather="package" class="w-5 h-5 mr-2 text-green-600"></i>
                Módulos Disponíveis
            </h3>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <li class="flex items-center">
                    <i data-feather="check" class="w-4 h-4 mr-2 text-green-600"></i>
                    Dashboard - Visão geral do sistema
                </li>
                <li class="flex items-center">
                    <i data-feather="check" class="w-4 h-4 mr-2 text-green-600"></i>
                    Usuários - Gerenciamento de usuários
                </li>
                <li class="flex items-center text-gray-400 dark:text-gray-600">
                    <i data-feather="clock" class="w-4 h-4 mr-2"></i>
                    Próximos módulos em desenvolvimento...
                </li>
            </ul>
        </div>

        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                <i data-feather="settings" class="w-5 h-5 mr-2 text-purple-600"></i>
                Funcionalidades Base
            </h3>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <li class="flex items-center">
                    <i data-feather="check" class="w-4 h-4 mr-2 text-green-600"></i>
                    Sistema de autenticação completo
                </li>
                <li class="flex items-center">
                    <i data-feather="check" class="w-4 h-4 mr-2 text-green-600"></i>
                    Controle de permissões por role
                </li>
                <li class="flex items-center">
                    <i data-feather="check" class="w-4 h-4 mr-2 text-green-600"></i>
                    Layout responsivo com dark mode
                </li>
                <li class="flex items-center">
                    <i data-feather="check" class="w-4 h-4 mr-2 text-green-600"></i>
                    Estrutura modular escalável
                </li>
            </ul>
        </div>
    </div>
</div>
