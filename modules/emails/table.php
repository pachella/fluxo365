<?php
require_once(__DIR__ . "/../../core/db.php");

$sql = "SELECT * FROM email_templates ORDER BY category ASC, name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mapear categorias para nomes e cores
$categoryNames = [
    'clients' => 'Clientes',
    'affiliates' => 'Afiliados', 
    'tickets' => 'Tickets',
    'system' => 'Sistema'
];

$categoryColors = [
    'clients' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    'affiliates' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400', 
    'tickets' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
    'system' => 'bg-gray-100 text-gray-800 dark:bg-zinc-700 dark:text-zinc-300'
];
?>

<!-- Desktop: Tabela -->
<div class="hidden md:block overflow-x-auto bg-white dark:bg-zinc-800 rounded-lg shadow-md">
    <table class="min-w-full">
        <thead class="bg-gray-50 dark:bg-zinc-700">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-300 uppercase tracking-wider">
                    Template
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-300 uppercase tracking-wider">
                    Categoria
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-300 uppercase tracking-wider">
                    Assunto
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-300 uppercase tracking-wider">
                    Status
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-zinc-300 uppercase tracking-wider">
                    Ações
                </th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
            <?php if ($templates): ?>
                <?php foreach ($templates as $t): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-zinc-100">
                                <?= htmlspecialchars($t['name']) ?>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-zinc-400">
                                <?= htmlspecialchars($t['code']) ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $categoryColor = $categoryColors[$t['category']] ?? 'bg-gray-100 text-gray-800 dark:bg-zinc-700 dark:text-zinc-300';
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $categoryColor ?>">
                                <?= $categoryNames[$t['category']] ?? ucfirst($t['category']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-zinc-100 max-w-xs truncate">
                                <?= htmlspecialchars($t['subject'] ?: 'Não definido') ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $statusClasses = [
                                '1' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                '0' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                            ];
                            $statusClass = $statusClasses[$t['active']] ?? 'bg-gray-100 text-gray-800 dark:bg-zinc-700 dark:text-zinc-300';
                            
                            $statusNames = [
                                '1' => 'Ativo',
                                '0' => 'Inativo'
                            ];
                            $statusName = $statusNames[$t['active']] ?? 'Indefinido';
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                <?= $statusName ?>
                            </span>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <button onclick="editEmail(<?= $t['id'] ?>)" 
                                        class="bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-500 text-white px-3 py-1 rounded text-xs transition-colors">
                                    Editar
                                </button>
                                <?php if ($t['active']): ?>
                                    <button onclick="toggleStatus(<?= $t['id'] ?>, 0, '<?= addslashes($t['name']) ?>')" 
                                            class="bg-orange-500 hover:bg-orange-600 dark:bg-orange-600 dark:hover:bg-orange-500 text-white px-3 py-1 rounded text-xs transition-colors">
                                        Desativar
                                    </button>
                                <?php else: ?>
                                    <button onclick="toggleStatus(<?= $t['id'] ?>, 1, '<?= addslashes($t['name']) ?>')" 
                                            class="bg-green-500 hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-500 text-white px-3 py-1 rounded text-xs transition-colors">
                                        Ativar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                        <div class="text-gray-500 dark:text-zinc-400">
                            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-zinc-500" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-zinc-100">Nenhum template encontrado</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">
                                Os templates de e-mail serão exibidos aqui.
                            </p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Mobile: Cards -->
<div class="md:hidden space-y-4">
    <?php if ($templates): ?>
        <?php foreach ($templates as $t): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 border border-gray-200 dark:border-zinc-700">
                <!-- Header: Nome e Status -->
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-zinc-100">
                            <?= htmlspecialchars($t['name']) ?>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1 font-mono">
                            <?= htmlspecialchars($t['code']) ?>
                        </p>
                    </div>
                    <?php
                    $statusClasses = [
                        '1' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                        '0' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                    ];
                    $statusClass = $statusClasses[$t['active']] ?? 'bg-gray-100 text-gray-800 dark:bg-zinc-700 dark:text-zinc-300';
                    
                    $statusNames = [
                        '1' => 'Ativo',
                        '0' => 'Inativo'
                    ];
                    $statusName = $statusNames[$t['active']] ?? 'Indefinido';
                    ?>
                    <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full <?= $statusClass ?> whitespace-nowrap">
                        <?= $statusName ?>
                    </span>
                </div>

                <!-- Categoria -->
                <div class="mb-2">
                    <?php
                    $categoryColor = $categoryColors[$t['category']] ?? 'bg-gray-100 text-gray-800 dark:bg-zinc-700 dark:text-zinc-300';
                    ?>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $categoryColor ?>">
                        <?= $categoryNames[$t['category']] ?? ucfirst($t['category']) ?>
                    </span>
                </div>

                <!-- Assunto -->
                <div class="mb-3 pb-3 border-b border-gray-200 dark:border-zinc-700">
                    <div class="text-xs text-gray-500 dark:text-zinc-400 mb-1">Assunto</div>
                    <div class="text-sm text-gray-900 dark:text-zinc-100 line-clamp-2">
                        <?= htmlspecialchars($t['subject'] ?: 'Não definido') ?>
                    </div>
                </div>

                <!-- Ações -->
                <div class="flex gap-2">
                    <button onclick="editEmail(<?= $t['id'] ?>)" 
                            class="flex-1 bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-500 text-white px-3 py-2 rounded text-sm font-medium transition-colors">
                        Editar
                    </button>
                    <?php if ($t['active']): ?>
                        <button onclick="toggleStatus(<?= $t['id'] ?>, 0, '<?= addslashes($t['name']) ?>')" 
                                class="flex-1 bg-orange-500 hover:bg-orange-600 dark:bg-orange-600 dark:hover:bg-orange-500 text-white px-3 py-2 rounded text-sm font-medium transition-colors">
                            Desativar
                        </button>
                    <?php else: ?>
                        <button onclick="toggleStatus(<?= $t['id'] ?>, 1, '<?= addslashes($t['name']) ?>')" 
                                class="flex-1 bg-green-500 hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-500 text-white px-3 py-2 rounded text-sm font-medium transition-colors">
                            Ativar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-8 text-center border border-gray-200 dark:border-zinc-700">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-zinc-500" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-zinc-100">Nenhum template encontrado</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">
                Os templates de e-mail serão exibidos aqui.
            </p>
        </div>
    <?php endif; ?>
</div>