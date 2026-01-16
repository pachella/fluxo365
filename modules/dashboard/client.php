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
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold">
                Olá, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! 👋
            </h1>
            <p class="text-sm opacity-60">Bem-vindo ao seu painel de controle</p>
        </div>
        <div class="flex flex-col items-end gap-2">
            <div class="badge badge-outline">
                Usuário desde: <?= formatDate($user['created_at']) ?>
            </div>
        </div>
    </div>

    <!-- Mensagem de boas-vindas -->
    <div class="alert alert-success mb-6">
        <i data-feather="user" class="w-6 h-6"></i>
        <div>
            <h3 class="font-bold">Sua Conta está Ativa</h3>
            <div class="text-xs">
                Este é seu painel pessoal. Em breve, novos módulos e funcionalidades estarão disponíveis aqui.
            </div>
            <div class="mt-2">
                <div class="badge badge-outline badge-sm">
                    <?= htmlspecialchars($user['email']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards informativos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="card bg-base-200 shadow">
            <div class="card-body">
                <h2 class="card-title">
                    <i data-feather="zap" class="w-5 h-5 text-success"></i>
                    Funcionalidades Disponíveis
                </h2>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center">
                        <i data-feather="check" class="w-4 h-4 mr-2 text-success"></i>
                        Acesso ao dashboard personalizado
                    </li>
                    <li class="flex items-center">
                        <i data-feather="check" class="w-4 h-4 mr-2 text-success"></i>
                        Gerenciamento de perfil
                    </li>
                    <li class="flex items-center opacity-50">
                        <i data-feather="clock" class="w-4 h-4 mr-2"></i>
                        Novos módulos em desenvolvimento...
                    </li>
                </ul>
            </div>
        </div>

        <div class="card bg-base-200 shadow">
            <div class="card-body">
                <h2 class="card-title">
                    <i data-feather="info" class="w-5 h-5 text-info"></i>
                    Informações da Conta
                </h2>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i data-feather="user" class="w-4 h-4"></i>
                            <span class="text-sm">Tipo de Conta</span>
                        </div>
                        <span class="badge badge-primary">Cliente</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i data-feather="check-circle" class="w-4 h-4"></i>
                            <span class="text-sm">Status</span>
                        </div>
                        <span class="badge badge-success">Ativo</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ajuda e Suporte -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="card bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow">
            <div class="card-body">
                <h2 class="card-title">
                    <i data-feather="help-circle" class="w-5 h-5"></i>
                    Precisa de Ajuda?
                </h2>
                <p class="text-sm opacity-90">Nossa equipe está pronta para ajudar você!</p>
                <div class="card-actions">
                    <a href="mailto:suporte@supersites.com.br" class="btn btn-sm bg-white text-indigo-600 hover:bg-gray-100">
                        <i data-feather="mail" class="w-4 h-4"></i>
                        Entrar em Contato
                    </a>
                </div>
            </div>
        </div>

        <div class="card bg-base-200 shadow">
            <div class="card-body">
                <h2 class="card-title">
                    <i data-feather="lightbulb" class="w-5 h-5 text-warning"></i>
                    Dica do Dia
                </h2>
                <p class="text-sm">
                    Explore o menu lateral para acessar todas as funcionalidades disponíveis.
                    Em breve, novos módulos serão adicionados ao sistema!
                </p>
            </div>
        </div>
    </div>
</div>
