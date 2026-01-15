<?php
session_start();
require_once(__DIR__ . "/../../core/db.php");
require_once __DIR__ . '/../../core/PermissionManager.php';

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo "<p class='text-center py-8 text-gray-500 dark:text-zinc-400'>Não autorizado</p>";
    exit();
}

$permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

$searchQuery = trim($_GET['q'] ?? '');
$folderFilter = $_GET['folder'] ?? null;

// Query base
$sql = "SELECT f.*, 
        folder.name as folder_name,
        folder.color as folder_color,
        folder.icon as folder_icon,
        (SELECT COUNT(*) FROM form_responses WHERE form_id = f.id) as total_responses
        FROM forms f
        LEFT JOIN form_folders folder ON folder.id = f.folder_id
        WHERE 1=1";

// Filtrar por usuário se não for admin
if (!$permissionManager->canViewAllRecords()) {
    $sql .= " AND f.user_id = :user_id";
}

// Filtrar por pasta
if ($folderFilter === 'none') {
    $sql .= " AND f.folder_id IS NULL";
} elseif ($folderFilter && is_numeric($folderFilter)) {
    $sql .= " AND f.folder_id = :folder_id";
}

// Adicionar busca
if (!empty($searchQuery)) {
    $sql .= " AND (f.title LIKE :search OR f.description LIKE :search)";
}

$sql .= " ORDER BY f.created_at DESC";

$stmt = $pdo->prepare($sql);

if (!$permissionManager->canViewAllRecords()) {
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
}

if ($folderFilter && is_numeric($folderFilter)) {
    $stmt->bindValue(':folder_id', $folderFilter, PDO::PARAM_INT);
}

if (!empty($searchQuery)) {
    $searchParam = '%' . $searchQuery . '%';
    $stmt->bindValue(':search', $searchParam, PDO::PARAM_STR);
}

$stmt->execute();
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar nome da pasta atual (se houver filtro)
$currentFolderName = null;
if ($folderFilter === 'none') {
    $currentFolderName = 'Sem Pasta';
} elseif ($folderFilter && is_numeric($folderFilter)) {
    $folderStmt = $pdo->prepare("SELECT name FROM form_folders WHERE id = :id AND user_id = :user_id");
    $folderStmt->execute([':id' => $folderFilter, ':user_id' => $_SESSION['user_id']]);
    $folderData = $folderStmt->fetch(PDO::FETCH_ASSOC);
    $currentFolderName = $folderData['name'] ?? null;
}
?>

<?php if ($currentFolderName): ?>
    <div class="mb-4 flex items-center gap-3">
        <a href="/forms/list" class="text-sm text-gray-600 dark:text-zinc-400 hover:text-gray-900 dark:hover:text-zinc-100">
            <i class="fas fa-arrow-left mr-1"></i> Voltar para todos
        </a>
        <span class="text-gray-400 dark:text-zinc-600">|</span>
        <span class="text-sm font-medium text-gray-900 dark:text-zinc-100">
            <i class="fas fa-folder mr-2" style="color: #4EA44B;"></i>
            <?= htmlspecialchars($currentFolderName) ?>
        </span>
    </div>
<?php endif; ?>

<div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                        Título
                    </th>
                    <?php if (!$folderFilter): ?>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider hidden xl:table-cell">
                        Pasta
                    </th>
                    <?php endif; ?>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider hidden md:table-cell">
                        Modo
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider hidden sm:table-cell">
                        Status
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider hidden lg:table-cell">
                        Respostas
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                        Ações
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                <?php if (empty($forms)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-zinc-400">
                            <i class="fas fa-search text-4xl mb-3 opacity-50"></i>
                            <p>Nenhum formulário encontrado.</p>
                            <?php if (!empty($searchQuery)): ?>
                                <p class="text-sm mt-1">Tente buscar por outro termo.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($forms as $form): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                            <td class="px-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-zinc-100">
                                        <?= htmlspecialchars($form['title']) ?>
                                    </p>
                                    <?php if ($form['description']): ?>
                                        <?php 
                                        $description = htmlspecialchars($form['description']);
                                        $maxLength = 50;
                                        if (mb_strlen($description) > $maxLength) {
                                            $description = mb_substr($description, 0, $maxLength) . '...';
                                        }
                                        ?>
                                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                                            <?= $description ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php if (!$folderFilter): ?>
                            <td class="px-4 py-3 hidden xl:table-cell">
                                <?php if ($form['folder_name']): ?>
                                    <a href="/forms/list?folder=<?= $form['folder_id'] ?>" 
                                       class="inline-flex items-center text-xs px-2 py-1 rounded hover:opacity-80 transition-opacity"
                                       style="background-color: <?= htmlspecialchars($form['folder_color']) ?>20; color: <?= htmlspecialchars($form['folder_color']) ?>;">
                                        <i class="fas fa-<?= htmlspecialchars($form['folder_icon']) ?> mr-1"></i>
                                        <?= htmlspecialchars($form['folder_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400 dark:text-zinc-500">
                                        <i class="fas fa-inbox mr-1"></i> Sem pasta
                                    </span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="text-xs text-gray-600 dark:text-zinc-300">
                                    <?php if ($form['display_mode'] === 'one-by-one'): ?>
                                        <i class="fas fa-layer-group mr-1"></i> Uma por vez
                                    <?php else: ?>
                                        <i class="fas fa-list mr-1"></i> Todas
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                <?php
                                $statusColors = [
                                    'ativo' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                    'inativo' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
                                    'rascunho' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
                                ];
                                $statusColor = $statusColors[$form['status']] ?? $statusColors['rascunho'];
                                ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?= $statusColor ?>">
                                    <?= ucfirst($form['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell">
                                <div class="flex items-center text-sm text-gray-600 dark:text-zinc-300">
                                    <i class="fas fa-inbox mr-2"></i>
                                    <span><?= number_format($form['total_responses']) ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right text-sm space-x-2">
                                <button onclick="moveToFolder(<?= $form['id'] ?>, '<?= htmlspecialchars(addslashes($form['title'])) ?>')"
                                        class="text-gray-600 hover:text-gray-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                                        title="Mover para pasta">
                                    <i class="fas fa-folder-open"></i>
                                </button>

                                <button onclick="openBuilder(<?= $form['id'] ?>)"
                                        class="text-gray-600 hover:text-gray-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                                        title="Editar perguntas">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <button onclick="viewResponses(<?= $form['id'] ?>)"
                                        class="text-gray-600 hover:text-gray-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                                        title="Ver respostas">
                                    <i class="fas fa-inbox"></i>
                                </button>

                                <button onclick="viewForm(<?= $form['id'] ?>)"
                                        class="text-gray-600 hover:text-gray-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                                        title="Visualizar formulário">
                                    <i class="fas fa-eye"></i>
                                </button>

                                <button onclick="editForm(<?= $form['id'] ?>)"
                                        class="text-gray-600 hover:text-gray-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                                        title="Configurações">
                                    <i class="fas fa-cog"></i>
                                </button>

                                <button onclick="duplicateForm(<?= $form['id'] ?>, '<?= htmlspecialchars(addslashes($form['title'])) ?>')"
                                        class="text-gray-600 hover:text-gray-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                                        title="Duplicar formulário">
                                    <i class="fas fa-clone"></i>
                                </button>

                                <?php if ($permissionManager->canDeleteRecord($form['user_id'])): ?>
                                    <button onclick="deleteForm(<?= $form['id'] ?>, '<?= htmlspecialchars(addslashes($form['title'])) ?>')"
                                            class="text-gray-600 hover:text-red-600 dark:text-zinc-400 dark:hover:text-red-400"
                                            title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>