<?php
session_start();
require_once(__DIR__ . "/../../core/db.php");
require_once(__DIR__ . "/../../core/PermissionManager.php");

$permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

// Parâmetros de filtro e paginação
$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$perPage = 20;
$offset = ($currentPage - 1) * $perPage;

$filterForm = $_GET['form_id'] ?? '';
$filterSearch = $_GET['search'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

try {
    $sqlFilter = $permissionManager->getSQLFilter('forms');

    // Construir query para leads
    $sql = "SELECT fr.*, f.title as form_title,
                   (SELECT ra.answer
                    FROM response_answers ra
                    INNER JOIN form_fields ff ON ra.field_id = ff.id
                    WHERE ra.response_id = fr.id
                    AND ff.type IN ('name', 'text', 'email')
                    ORDER BY CASE ff.type
                        WHEN 'name' THEN 1
                        WHEN 'email' THEN 2
                        WHEN 'text' THEN 3
                    END, ff.order_index ASC
                    LIMIT 1) as respondent_name,
                   (SELECT ra.answer
                    FROM response_answers ra
                    INNER JOIN form_fields ff ON ra.field_id = ff.id
                    WHERE ra.response_id = fr.id
                    ORDER BY ff.order_index ASC
                    LIMIT 1) as first_answer
            FROM form_responses fr
            INNER JOIN forms f ON fr.form_id = f.id";

    if (!empty($filterSearch)) {
        $sql .= " LEFT JOIN response_answers ra_search ON ra_search.response_id = fr.id";
    }

    // Adicionar filtro de permissões
    if (empty($sqlFilter)) {
        // Admin - sem filtro adicional
        $sql .= " WHERE 1=1";
    } else {
        // Cliente - adicionar filtro de usuário
        $sql .= " " . str_replace('WHERE', 'WHERE 1=1 AND', $sqlFilter);
    }

    $params = [];

    if (!empty($filterForm)) {
        $sql .= " AND fr.form_id = :form_id";
        $params[':form_id'] = $filterForm;
    }

    if (!empty($filterSearch)) {
        $sql .= " AND ra_search.answer LIKE :search";
        $params[':search'] = '%' . $filterSearch . '%';
    }

    if (!empty($filterDateFrom)) {
        $sql .= " AND DATE(fr.created_at) >= :date_from";
        $params[':date_from'] = $filterDateFrom;
    }
    if (!empty($filterDateTo)) {
        $sql .= " AND DATE(fr.created_at) <= :date_to";
        $params[':date_to'] = $filterDateTo;
    }

    if (!empty($filterSearch)) {
        $sql .= " GROUP BY fr.id";
    }

    // Contar total
    $countSql = str_replace("SELECT fr.*, f.title as form_title", "SELECT COUNT(DISTINCT fr.id) as total", $sql);
    $countSql = preg_replace('/,\s*\(SELECT.*?\) as \w+/', '', $countSql);

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $perPage);

    // Buscar leads
    $sql .= " ORDER BY fr.created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro table.php: " . $e->getMessage());
    error_log("SQL: " . ($sql ?? 'N/A'));
    $leads = [];
    $totalRecords = 0;
    $totalPages = 0;
}

function buildPaginationUrl($page) {
    $params = $_GET;
    $params['p'] = $page;
    return '?' . http_build_query($params);
}
?>

<div class="overflow-x-auto">
    <table class="w-full min-w-full">
        <thead class="border-b border-gray-200 dark:border-zinc-700">
            <tr>
                <th class="text-left py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400">ID</th>
                <th class="text-left py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400">Nome</th>
                <th class="text-left py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400">Formulário</th>
                <th class="text-left py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400 hidden md:table-cell">Preview</th>
                <th class="text-left py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400 hidden sm:table-cell">Data</th>
                <th class="text-center py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400 hidden lg:table-cell">Pontuação</th>
                <th class="text-right py-2 px-2 text-sm font-medium text-gray-500 dark:text-gray-400">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
            <?php if ($leads): ?>
                <?php foreach ($leads as $lead):
                    $leadName = $lead['respondent_name'] ?? 'Sem nome';
                    $firstAnswer = $lead['first_answer'] ?? 'Sem resposta';

                    if (is_string($firstAnswer) && (substr($firstAnswer, 0, 1) === '[' || substr($firstAnswer, 0, 1) === '{')) {
                        $decoded = json_decode($firstAnswer, true);
                        if (is_array($decoded)) {
                            $firstAnswer = implode(', ', $decoded);
                        }
                    }
                    if (strlen($firstAnswer) > 50) {
                        $firstAnswer = substr($firstAnswer, 0, 50) . '...';
                    }
                ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                        <td class="py-3 px-2 text-sm">
                            <span class="font-medium text-gray-900 dark:text-gray-100">#<?= $lead['id'] ?></span>
                        </td>
                        <td class="py-3 px-2 text-sm">
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                <?= htmlspecialchars($leadName) ?>
                            </div>
                        </td>
                        <td class="py-3 px-2 text-sm">
                            <div class="text-gray-600 dark:text-gray-400">
                                <?= htmlspecialchars($lead['form_title']) ?>
                            </div>
                        </td>
                        <td class="py-3 px-2 text-sm text-gray-600 dark:text-gray-400 hidden md:table-cell">
                            <?= htmlspecialchars($firstAnswer) ?>
                        </td>
                        <td class="py-3 px-2 text-sm text-gray-500 dark:text-gray-400 hidden sm:table-cell">
                            <?= date('d/m/Y H:i', strtotime($lead['created_at'])) ?>
                        </td>
                        <td class="py-3 px-2 text-sm text-center hidden lg:table-cell">
                            <?php if ($lead['score'] && $lead['score'] > 0): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                    <i data-feather="star" class="w-3 h-3 mr-1"></i> <?= $lead['score'] ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-2 text-sm text-right">
                            <div class="flex justify-end gap-2">
                                <button onclick="viewLeadDetails(<?= $lead['id'] ?>)"
                                   class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300"
                                   title="Ver detalhes">
                                    <i data-feather="eye" class="w-4 h-4 inline"></i>
                                </button>
                                <a href="/forms/<?= $lead['form_id'] ?>/responses/<?= $lead['id'] ?>"
                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                                   title="Ver resposta completa">
                                    <i data-feather="file-text" class="w-4 h-4 inline"></i>
                                </a>
                                <button onclick="deleteLead(<?= $lead['id'] ?>)"
                                   class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300"
                                   title="Excluir lead">
                                    <i data-feather="trash-2" class="w-4 h-4 inline"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        <i data-feather="inbox" class="w-12 h-12 mx-auto mb-2 text-gray-400 dark:text-gray-600"></i>
                        <p>Nenhum lead encontrado</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Paginação -->
<?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 dark:border-zinc-700">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Mostrando <?= min($offset + 1, $totalRecords) ?> a <?= min($offset + $perPage, $totalRecords) ?> de <?= $totalRecords ?> leads
        </div>
        <div class="flex gap-2">
            <?php if ($currentPage > 1): ?>
                <a href="<?= buildPaginationUrl($currentPage - 1) ?>" onclick="event.preventDefault(); loadLeadsTable(<?= $currentPage - 1 ?>)"
                   class="px-3 py-1 bg-white dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded hover:bg-gray-50 dark:hover:bg-zinc-600 text-sm">
                    <i data-feather="chevron-left" class="w-4 h-4 inline"></i>
                </a>
            <?php endif; ?>

            <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                <a href="<?= buildPaginationUrl($i) ?>" onclick="event.preventDefault(); loadLeadsTable(<?= $i ?>)"
                   class="px-3 py-1 <?= $i === $currentPage ? 'bg-green-600 text-white' : 'bg-white dark:bg-zinc-700 text-gray-700 dark:text-gray-300' ?> border border-gray-300 dark:border-zinc-600 rounded hover:bg-gray-50 dark:hover:bg-zinc-600 text-sm">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="<?= buildPaginationUrl($currentPage + 1) ?>" onclick="event.preventDefault(); loadLeadsTable(<?= $currentPage + 1 ?>)"
                   class="px-3 py-1 bg-white dark:bg-zinc-700 border border-gray-300 dark:border-zinc-600 rounded hover:bg-gray-50 dark:hover:bg-zinc-600 text-sm">
                    <i data-feather="chevron-right" class="w-4 h-4 inline"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
// Reinicializar ícones feather após carregar tabela
if (typeof feather !== 'undefined') {
    feather.replace();
}
</script>
