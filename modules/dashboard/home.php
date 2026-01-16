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
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold">Dashboard</h1>
            <p class="text-sm opacity-60 mt-1">
                <?php if ($permissionManager->isAdmin()): ?>
                    Visão geral do sistema
                <?php else: ?>
                    Bem-vindo, <?= htmlspecialchars($_SESSION['user_name']) ?>!
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Cards de estatísticas principais -->
    <?php if ($permissionManager->isAdmin()): ?>
    <div class="stats stats-vertical lg:stats-horizontal shadow mb-6 w-full">
        <div class="stat">
            <div class="stat-figure text-primary">
                <i data-feather="users" class="w-8 h-8"></i>
            </div>
            <div class="stat-title">Usuários Cadastrados</div>
            <div class="stat-value text-primary"><?= number_format($usersCount) ?></div>
            <div class="stat-desc">clientes ativos</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Mensagem de boas-vindas -->
    <div class="alert alert-info mb-6">
        <i data-feather="zap" class="w-6 h-6"></i>
        <div>
            <h3 class="font-bold">Bem-vindo ao Sistema Base!</h3>
            <div class="text-xs">
                Este é o sistema base limpo e organizado, pronto para receber novos módulos.
                A estrutura está preparada para crescer de forma modular e escalável.
            </div>
            <div class="mt-2">
                <div class="badge badge-outline">
                    Estrutura Modular: /modules/
                </div>
            </div>
        </div>
    </div>

    <!-- Cards informativos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="card bg-base-200 shadow">
            <div class="card-body">
                <h2 class="card-title">
                    <i data-feather="package" class="w-5 h-5 text-success"></i>
                    Módulos Disponíveis
                </h2>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center">
                        <i data-feather="check" class="w-4 h-4 mr-2 text-success"></i>
                        Dashboard - Visão geral do sistema
                    </li>
                    <li class="flex items-center">
                        <i data-feather="check" class="w-4 h-4 mr-2 text-success"></i>
                        Usuários - Gerenciamento de usuários
                    </li>
                    <li class="flex items-center opacity-50">
                        <i data-feather="clock" class="w-4 h-4 mr-2"></i>
                        Próximos módulos em desenvolvimento...
                    </li>
                </ul>
            </div>
        </div>

        <div class="card bg-base-200 shadow">
            <div class="card-body">
                <h2 class="card-title">
                    <i data-feather="settings" class="w-5 h-5 text-secondary"></i>
                    Funcionalidades Base
                </h2>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center">
                        <i data-feather="check" class="w-4 h-4 mr-2 text-success"></i>
                        Sistema de autenticação completo
                    </li>
                    <li class="flex items-center">
                        <i data-feather="check" class="w-4 h-4 mr-2 text-success"></i>
                        Controle de permissões por role
                    </li>
                    <li class="flex items-center">
                        <i data-feather="check" class="w-4 h-4 mr-2 text-success"></i>
                        Layout responsivo com dark mode
                    </li>
                    <li class="flex items-center">
                        <i data-feather="check" class="w-4 h-4 mr-2 text-success"></i>
                        Estrutura modular escalável
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
