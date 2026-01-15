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
    
    // Formulários do cliente
    $formsSQL = "SELECT * FROM forms WHERE user_id = ? ORDER BY created_at DESC";
    $formsData = $pdo->prepare($formsSQL);
    $formsData->execute([$userId]);
    $forms = $formsData->fetchAll(PDO::FETCH_ASSOC);
    
    $totalForms = count($forms);
    $activeForms = count(array_filter($forms, fn($f) => $f['status'] === 'ativo'));
    $draftForms = count(array_filter($forms, fn($f) => $f['status'] === 'rascunho'));
    
    // Total de respostas nos formulários do cliente
    $responsesSQL = "
        SELECT COUNT(*) as total 
        FROM form_responses fr
        INNER JOIN forms f ON fr.form_id = f.id
        WHERE f.user_id = ?
    ";
    $responsesData = $pdo->prepare($responsesSQL);
    $responsesData->execute([$userId]);
    $totalResponses = $responsesData->fetchColumn();
    
    // Respostas este mês
    $responsesMonthSQL = "
        SELECT COUNT(*) as total 
        FROM form_responses fr
        INNER JOIN forms f ON fr.form_id = f.id
        WHERE f.user_id = ? AND fr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";
    $responsesMonthData = $pdo->prepare($responsesMonthSQL);
    $responsesMonthData->execute([$userId]);
    $responsesThisMonth = $responsesMonthData->fetchColumn();
    
    // Formulários criados este mês
    $newThisMonth = count(array_filter($forms, fn($f) => 
        date('Y-m', strtotime($f['created_at'])) === date('Y-m')
    ));
    
    // Top 5 formulários com mais respostas
    $topFormsSQL = "
        SELECT f.id, f.title, COUNT(fr.id) as responses_count
        FROM forms f
        LEFT JOIN form_responses fr ON f.id = fr.form_id
        WHERE f.user_id = ?
        GROUP BY f.id
        ORDER BY responses_count DESC
        LIMIT 5
    ";
    $topFormsData = $pdo->prepare($topFormsSQL);
    $topFormsData->execute([$userId]);
    $topForms = $topFormsData->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro na dashboard do cliente: " . $e->getMessage());
    $user = ['name' => 'Cliente', 'email' => '', 'created_at' => date('Y-m-d')];
    $forms = [];
    $totalForms = 0;
    $activeForms = 0;
    $draftForms = 0;
    $totalResponses = 0;
    $responsesThisMonth = 0;
    $newThisMonth = 0;
    $topForms = [];
}

function formatDate($date) { 
    return date('d/m/Y', strtotime($date)); 
}

function getStatusBadge($status) {
    $classes = [
        'ativo' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
        'rascunho' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
        'inativo' => 'bg-gray-100 dark:bg-zinc-700 text-gray-800 dark:text-gray-200'
    ];
    $names = [
        'ativo' => 'Ativo',
        'rascunho' => 'Rascunho',
        'inativo' => 'Inativo'
    ];
    $class = $classes[$status] ?? 'bg-gray-100 dark:bg-zinc-700 text-gray-800 dark:text-gray-200';
    $name = $names[$status] ?? ucfirst($status);
    return "<span class='px-2 py-1 text-sm rounded-full {$class}'>{$name}</span>";
}
?>

<div class="w-full max-w-full overflow-x-hidden">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-6 gap-2">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100">
                Olá, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! 👋
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Gerencie seus formulários de forma simples</p>
        </div>
        <div class="flex flex-col items-end gap-2">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Usuário desde: <?= formatDate($user['created_at']) ?>
            </div>
        </div>
    </div>
    
    <?php if (PlanService::isFree()): ?>
    <!-- Banner Promocional (somente para clientes Free) -->
    <div id="promo-banner" class="relative mb-6 hidden">
        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg overflow-hidden">
            <button id="close-banner" 
                    class="absolute top-2 right-2 bg-white/70 dark:bg-zinc-700/70 hover:bg-white dark:hover:bg-zinc-600 rounded-full p-1.5 shadow-sm transition"
                    title="Fechar oferta">
                <i data-feather="x" class="w-4 h-4 text-gray-700 dark:text-gray-200"></i>
            </button>
            <a href="https://payment.ticto.app/OC40D8ADE" target="_blank" rel="noopener">
                <img src="/uploads/system/oferta_black.png" 
                     alt="Oferta Black" 
                     class="w-full rounded-lg object-cover">
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const banner = document.getElementById('promo-banner');
            const closeBtn = document.getElementById('close-banner');

            // Só mostra se o cliente não tiver fechado antes
            if (!localStorage.getItem('hidePromoBanner')) {
                banner.classList.remove('hidden');
            }

            closeBtn.addEventListener('click', function () {
                banner.classList.add('hidden');
                localStorage.setItem('hidePromoBanner', 'true');
            });
        });
    </script>
<?php endif; ?>


    <!-- Cards principais -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Total de Formulários</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?= $totalForms ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                    <i data-feather="file-text" class="w-6 h-6 text-blue-600 dark:text-blue-300"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Formulários Ativos</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?= $activeForms ?></p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                        <?= $totalForms > 0 ? number_format(($activeForms/$totalForms)*100, 0) : 0 ?>% do total
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
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Total de Respostas</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?= number_format($totalResponses) ?></p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">em todos os formulários</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                    <i data-feather="message-square" class="w-6 h-6 text-purple-600 dark:text-purple-300"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4 border-l-4 border-orange-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Respostas este Mês</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?= number_format($responsesThisMonth) ?></p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">novas respostas</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                    <i data-feather="trending-up" class="w-6 h-6 text-orange-600 dark:text-orange-300"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Seção principal -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <!-- Meus Formulários -->
        <div class="lg:col-span-2 bg-white dark:bg-zinc-800 shadow rounded-lg p-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                    <i data-feather="file-text" class="w-5 h-5 mr-2"></i>
                    Meus Formulários
                </h3>
                <a href="/modules/forms/list.php" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">Ver todos</a>
            </div>
            
            <div class="space-y-3">
                <?php if ($forms): ?>
                    <?php foreach (array_slice($forms, 0, 5) as $form): 
                        // Buscar contagem de respostas para cada form
                        $responsesCountStmt = $pdo->prepare("SELECT COUNT(*) FROM form_responses WHERE form_id = ?");
                        $responsesCountStmt->execute([$form['id']]);
                        $formResponsesCount = $responsesCountStmt->fetchColumn();
                    ?>
                        <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                            <div class="flex justify-between items-start gap-3">
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">
                                        <?= htmlspecialchars($form['title']) ?>
                                    </h4>
                                    <?php if ($form['description']): ?>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 line-clamp-2">
                                            <?= htmlspecialchars($form['description']) ?>
                                        </p>
                                    <?php endif; ?>
                                    <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="flex items-center">
                                            <i data-feather="calendar" class="w-3 h-3 mr-1"></i>
                                            <?= formatDate($form['created_at']) ?>
                                        </span>
                                        <span class="flex items-center">
                                            <i data-feather="message-square" class="w-3 h-3 mr-1"></i>
                                            <?= $formResponsesCount ?> respostas
                                        </span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <?= getStatusBadge($form['status']) ?>
                                    <div class="flex gap-2">
                                        <a href="/f/<?= $form['id'] ?>" 
                                           target="_blank" 
                                           class="p-1.5 rounded-lg bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors"
                                           title="Visualizar formulário">
                                            <i data-feather="external-link" class="w-4 h-4"></i>
                                        </a>
                                        <a href="/forms/builder/<?= $form['id'] ?>" 
                                           class="p-1.5 rounded-lg bg-gray-100 dark:bg-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-zinc-600 transition-colors"
                                           title="Editar">
                                            <i data-feather="edit-2" class="w-4 h-4"></i>
                                        </a>
                                        <a href="/forms/<?= $form['id'] ?>/responses" 
                                           class="p-1.5 rounded-lg bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-300 hover:bg-purple-200 dark:hover:bg-purple-800 transition-colors"
                                           title="Ver respostas">
                                            <i data-feather="list" class="w-4 h-4"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <i data-feather="file-text" class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600"></i>
                        <p class="text-base font-medium mb-2">Nenhum formulário criado ainda</p>
                        <p class="text-sm mb-4">Comece criando seu primeiro formulário!</p>
                        <a href="/modules/forms/list.php" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors text-sm font-medium">
                            <i data-feather="plus" class="w-4 h-4 mr-2"></i>
                            Criar Formulário
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
            <!-- Estatísticas Rápidas -->
            <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                    <i data-feather="bar-chart-2" class="w-5 h-5 mr-2"></i>
                    Estatísticas
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mr-3">
                                <i data-feather="check" class="w-4 h-4 text-green-600 dark:text-green-300"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Ativos</span>
                        </div>
                        <span class="text-lg font-bold text-gray-900 dark:text-gray-100"><?= $activeForms ?></span>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center mr-3">
                                <i data-feather="edit" class="w-4 h-4 text-yellow-600 dark:text-yellow-300"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Rascunhos</span>
                        </div>
                        <span class="text-lg font-bold text-gray-900 dark:text-gray-100"><?= $draftForms ?></span>
                    </div>
                </div>
            </div>

            <!-- Top Formulários -->
            <?php if ($topForms && array_sum(array_column($topForms, 'responses_count')) > 0): ?>
            <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                    <i data-feather="trending-up" class="w-5 h-5 mr-2"></i>
                    Mais Respondidos
                </h3>
                <div class="space-y-2">
                    <?php foreach ($topForms as $topForm): 
                        if ($topForm['responses_count'] == 0) continue;
                    ?>
                        <div class="flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                            <div class="flex-1 min-w-0 mr-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                    <?= htmlspecialchars($topForm['title']) ?>
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200">
                                <?= number_format($topForm['responses_count']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ajuda e Suporte -->
            <div class="bg-gradient-to-br from-indigo-500 to-purple-600 shadow rounded-lg p-4 text-white">
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

            <!-- Dica do Dia -->
            <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2 flex items-center">
                    <i data-feather="lightbulb" class="w-5 h-5 mr-2 text-yellow-500"></i>
                    Dica
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Use a opção "Visualizar" para testar como seus formulários aparecem para os respondentes antes de compartilhar!
                </p>
            </div>
        </div>
    </div>

    <!-- Atividade Recente -->
    <?php if ($forms): ?>
    <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
            <i data-feather="clock" class="w-5 h-5 mr-2"></i>
            Atividade Recente
        </h3>
        
        <div class="overflow-x-auto">
            <table class="w-full min-w-full">
                <thead class="border-b border-gray-200 dark:border-zinc-700">
                    <tr>
                        <th class="text-left py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400">Formulário</th>
                        <th class="text-center py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400 hidden md:table-cell">Respostas</th>
                        <th class="text-left py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="text-left py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400 hidden sm:table-cell">Criado</th>
                        <th class="text-right py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    <?php 
                    // Ordenar por created_at
                    usort($forms, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
                    foreach (array_slice($forms, 0, 5) as $form):
                        $responsesCountStmt = $pdo->prepare("SELECT COUNT(*) FROM form_responses WHERE form_id = ?");
                        $responsesCountStmt->execute([$form['id']]);
                        $formResponsesCount = $responsesCountStmt->fetchColumn();
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                            <td class="py-3 px-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                <?= htmlspecialchars($form['title']) ?>
                            </td>
                            <td class="py-3 px-2 text-sm text-center hidden md:table-cell">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200">
                                    <?= number_format($formResponsesCount) ?>
                                </span>
                            </td>
                            <td class="py-3 px-2 text-sm">
                                <?= getStatusBadge($form['status']) ?>
                            </td>
                            <td class="py-3 px-2 text-sm text-gray-500 dark:text-gray-400 hidden sm:table-cell">
                                <?= formatDate($form['created_at']) ?>
                            </td>
                            <td class="py-3 px-2 text-sm text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="/f/<?= $form['id'] ?>" 
                                       target="_blank" 
                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                        <i data-feather="external-link" class="w-4 h-4 inline"></i>
                                    </a>
                                    <a href="/forms/builder/<?= $form['id'] ?>" 
                                       class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300">
                                        <i data-feather="edit-2" class="w-4 h-4 inline"></i>
                                    </a>
                                    <a href="/forms/<?= $form['id'] ?>/responses" 
                                       class="text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300">
                                        <i data-feather="list" class="w-4 h-4 inline"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>