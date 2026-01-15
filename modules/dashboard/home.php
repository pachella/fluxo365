<?php
// Buscar dados reais do banco
require_once("../core/db.php");
require_once("../core/PermissionManager.php");
require_once("../core/PlanService.php");

// Criar instância do PermissionManager
$permissionManager = new PermissionManager(
    $_SESSION['user_role'],
    $_SESSION['user_id'] ?? null
);

// Buscar limites do plano
$limits = PlanService::getLimits();

try {
    // Filtro SQL baseado no role
    $sqlFilter = $permissionManager->getSQLFilter('forms');
    
    // Contadores principais
    $totalForms = $pdo->query("SELECT COUNT(*) FROM forms" . $sqlFilter)->fetchColumn();
    $activeForms = $pdo->query("SELECT COUNT(*) FROM forms WHERE status = 'ativo'" . $sqlFilter)->fetchColumn();
    $draftForms = $pdo->query("SELECT COUNT(*) FROM forms WHERE status = 'rascunho'" . $sqlFilter)->fetchColumn();
    
    // Total de respostas
    $totalResponses = $pdo->query("
        SELECT COUNT(*) FROM form_responses fr
        INNER JOIN forms f ON fr.form_id = f.id
        " . str_replace('WHERE', 'WHERE 1=1 AND', $sqlFilter)
    )->fetchColumn();
    
    // Respostas este mês
    $responsesThisMonth = $pdo->query("
        SELECT COUNT(*) FROM form_responses fr
        INNER JOIN forms f ON fr.form_id = f.id
        WHERE fr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        " . str_replace('WHERE', 'AND', $sqlFilter)
    )->fetchColumn();
    
    // Respostas esta semana
    $responsesThisWeek = $pdo->query("
        SELECT COUNT(*) FROM form_responses fr
        INNER JOIN forms f ON fr.form_id = f.id
        WHERE fr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        " . str_replace('WHERE', 'AND', $sqlFilter)
    )->fetchColumn();
    
    // Usuários (apenas para admin)
    $usersCount = 0;
    if ($permissionManager->isAdmin()) {
        $usersCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
    }
    
    // Formulários por status
    $formsByStatus = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM forms 
        " . str_replace('WHERE', 'WHERE 1=1 AND', $sqlFilter) . "
        GROUP BY status
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Últimos formulários criados
    $recentForms = $pdo->query("
        SELECT f.id, f.title, f.description, f.status, f.created_at, u.name as user_name,
               (SELECT COUNT(*) FROM form_responses WHERE form_id = f.id) as responses_count
        FROM forms f
        LEFT JOIN users u ON f.user_id = u.id
        " . str_replace('WHERE', 'WHERE 1=1 AND', $sqlFilter) . "
        ORDER BY f.created_at DESC 
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Formulários criados por mês (últimos 6 meses)
    $formsByMonth = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            DATE_FORMAT(created_at, '%b') as month_name,
            COUNT(*) as count
        FROM forms
        " . str_replace('WHERE', 'WHERE 1=1 AND', $sqlFilter) . "
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Top formulários com mais respostas
    $topForms = $pdo->query("
        SELECT f.id, f.title, COUNT(fr.id) as responses_count
        FROM forms f
        LEFT JOIN form_responses fr ON fr.form_id = f.id
        " . str_replace('WHERE', 'WHERE 1=1 AND', $sqlFilter) . "
        GROUP BY f.id
        ORDER BY responses_count DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Valores padrão em caso de erro
    $totalForms = 0;
    $activeForms = 0;
    $draftForms = 0;
    $totalResponses = 0;
    $responsesThisMonth = 0;
    $responsesThisWeek = 0;
    $usersCount = 0;
    $formsByStatus = [];
    $recentForms = [];
    $formsByMonth = [];
    $topForms = [];
}

$maxMonthCount = !empty($formsByMonth) ? max(array_column($formsByMonth, 'count')) : 1;
?>

<div class="w-full max-w-full overflow-x-hidden">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 sm:mb-6 gap-2">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100">Dashboard</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                <?php if ($permissionManager->isAdmin()): ?>
                    Visão geral do sistema de formulários
                <?php else: ?>
                    Bem-vindo, <?= htmlspecialchars($_SESSION['user_name']) ?>!
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Cards de estatísticas principais -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Total de Formulários</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?= number_format($totalForms) ?></p>
                    <?php if ($limits['max_forms'] !== -1): ?>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            de <?= number_format($limits['max_forms']) ?> disponíveis
                        </p>
                    <?php else: ?>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            Ilimitado
                        </p>
                    <?php endif; ?>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                    <i data-feather="file-text" class="w-6 h-6 text-blue-600 dark:text-blue-300"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Formulários Ativos</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?= number_format($activeForms) ?></p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                        <?= $totalForms > 0 ? number_format(($activeForms/$totalForms)*100, 1) : 0 ?>% do total
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                    <i data-feather="check-circle" class="w-6 h-6 text-green-600 dark:text-green-300"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Total de Respostas</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?= number_format($totalResponses) ?></p>
                    <?php if ($limits['max_responses'] !== -1): ?>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            de <?= number_format($limits['max_responses']) ?> disponíveis
                        </p>
                    <?php else: ?>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            Ilimitado
                        </p>
                    <?php endif; ?>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                    <i data-feather="message-square" class="w-6 h-6 text-purple-600 dark:text-purple-300"></i>
                </div>
            </div>
        </div>

        <?php if ($permissionManager->isAdmin()): ?>
            <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4 border-l-4 border-orange-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Usuários Ativos</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?= number_format($usersCount) ?></p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">clientes cadastrados</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                        <i data-feather="users" class="w-6 h-6 text-orange-600 dark:text-orange-300"></i>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4 border-l-4 border-orange-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Respostas este Mês</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?= number_format($responsesThisMonth) ?></p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">novas respostas</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                        <i data-feather="trending-up" class="w-6 h-6 text-orange-600 dark:text-orange-300"></i>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <!-- Status dos Formulários -->
        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                <i data-feather="pie-chart" class="w-5 h-5 mr-2"></i>
                Status dos Formulários
            </h3>
            <div class="space-y-3">
                <?php
                $statusColors = [
                    'ativo' => 'bg-green-500',
                    'rascunho' => 'bg-yellow-500',
                    'inativo' => 'bg-gray-500'
                ];
                
                $statusNames = [
                    'ativo' => 'Ativos',
                    'rascunho' => 'Rascunhos',
                    'inativo' => 'Inativos'
                ];
                
                foreach ($formsByStatus as $item):
                    $percentage = $totalForms > 0 ? ($item['count'] / $totalForms) * 100 : 0;
                    $color = $statusColors[$item['status']] ?? 'bg-gray-500';
                    $name = $statusNames[$item['status']] ?? ucfirst($item['status']);
                ?>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full <?= $color ?> flex-shrink-0"></div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 flex-1 min-w-0 truncate"><?= $name ?></span>
                        <span class="text-sm text-gray-500 dark:text-gray-400"><?= number_format($item['count']) ?></span>
                        <div class="w-20 bg-gray-200 dark:bg-zinc-700 rounded-full h-2">
                            <div class="<?= $color ?> h-2 rounded-full" style="width: <?= $percentage ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($formsByStatus)): ?>
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">Nenhum dado disponível</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulários por Mês -->
        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                <i data-feather="bar-chart-2" class="w-5 h-5 mr-2"></i>
                Criados nos Últimos 6 Meses
            </h3>
            <div class="space-y-3">
                <?php foreach ($formsByMonth as $month):
                    $percentage = ($month['count'] / $maxMonthCount) * 100;
                ?>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 w-12"><?= htmlspecialchars($month['month_name']) ?></span>
                        <div class="flex-1 bg-gray-200 dark:bg-zinc-700 rounded-full h-8 relative">
                            <div class="bg-indigo-500 h-8 rounded-full transition-all duration-500 flex items-center justify-end pr-2" style="width: <?= $percentage ?>%">
                                <span class="text-xs font-medium text-white"><?= number_format($month['count']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($formsByMonth)): ?>
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">Nenhum dado disponível</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Últimos Formulários Criados -->
    <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
            <i data-feather="clock" class="w-5 h-5 mr-2"></i>
            Últimos Formulários Criados
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full min-w-full">
                <thead class="border-b border-gray-200 dark:border-zinc-700">
                    <tr>
                        <th class="text-left py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400">Título</th>
                        <?php if ($permissionManager->isAdmin()): ?>
                            <th class="text-left py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400 hidden lg:table-cell">Proprietário</th>
                        <?php endif; ?>
                        <th class="text-center py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400 hidden md:table-cell">Respostas</th>
                        <th class="text-left py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="text-left py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400 hidden sm:table-cell">Criado em</th>
                        <th class="text-right py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    <?php if ($recentForms): ?>
                        <?php foreach ($recentForms as $form): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                                <td class="py-3 px-2 text-sm">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                        <?= htmlspecialchars($form['title']) ?>
                                    </div>
                                    <?php if ($form['description']): ?>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate max-w-xs">
                                            <?= htmlspecialchars($form['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <?php if ($permissionManager->isAdmin()): ?>
                                    <td class="py-3 px-2 text-sm text-gray-600 dark:text-gray-400 hidden lg:table-cell">
                                        <?= htmlspecialchars($form['user_name']) ?>
                                    </td>
                                <?php endif; ?>
                                <td class="py-3 px-2 text-sm text-center hidden md:table-cell">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200">
                                        <?= number_format($form['responses_count']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-2 text-sm">
                                    <span class="px-2 py-1 text-xs rounded-full <?= $form['status'] === 'ativo' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200' ?>">
                                        <?= $form['status'] === 'ativo' ? 'Ativo' : 'Rascunho' ?>
                                    </span>
                                </td>
                                <td class="py-3 px-2 text-sm text-gray-500 dark:text-gray-400 hidden sm:table-cell">
                                    <?= date('d/m/Y', strtotime($form['created_at'])) ?>
                                </td>
                                <td class="py-3 px-2 text-sm text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="/f/<?= $form['id'] ?>" target="_blank" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300" title="Visualizar">
                                            <i data-feather="external-link" class="w-4 h-4 inline"></i>
                                        </a>
                                        <a href="/forms/builder/<?= $form['id'] ?>" class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300" title="Editar">
                                            <i data-feather="edit-2" class="w-4 h-4 inline"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $permissionManager->isAdmin() ? '6' : '5' ?>" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                <i data-feather="inbox" class="w-12 h-12 mx-auto mb-2 text-gray-400 dark:text-gray-600"></i>
                                <p>Nenhum formulário criado ainda</p>
                                <a href="/forms" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm mt-2 inline-block">
                                    Criar primeiro formulário
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>