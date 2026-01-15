<?php
require_once(__DIR__ . "/../../core/db.php");

$q = $_GET['q'] ?? '';

try {
    if (!empty($q)) {
        // Busca com filtro
        $stmt = $pdo->prepare("
            SELECT id, name, email, role, 'ativo' as status 
            FROM users 
            WHERE name LIKE ? OR email LIKE ? OR role LIKE ?
            ORDER BY id DESC
        ");
        $searchTerm = '%' . $q . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    } else {
        // Listagem completa
        $stmt = $pdo->query("
            SELECT id, name, email, role, 'ativo' as status 
            FROM users 
            ORDER BY id DESC
        ");
    }
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $users = [];
}
?>

<!-- Desktop: Tabela -->
<div class="hidden md:block bg-white dark:bg-zinc-800 shadow-md rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-300 uppercase tracking-wider">
                        ID
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-300 uppercase tracking-wider">
                        Nome
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-300 uppercase tracking-wider">
                        E-mail
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-300 uppercase tracking-wider">
                        Perfil
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
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-zinc-100">
                                #<?= $user['id'] ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-zinc-100">
                                    <?= htmlspecialchars($user['name']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-zinc-100">
                                    <?= htmlspecialchars($user['email']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $roleColors = [
                                    'admin' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                    'user' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                    'moderator' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                    'client' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                    'affiliate' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400'
                                ];
                                $roleNames = [
                                    'admin' => 'Administrador',
                                    'user' => 'Usuário',
                                    'moderator' => 'Moderador',
                                    'client' => 'Cliente',
                                    'affiliate' => 'Afiliado'
                                ];
                                $roleColor = $roleColors[$user['role']] ?? 'bg-gray-100 text-gray-800 dark:bg-zinc-700 dark:text-zinc-300';
                                $roleName = $roleNames[$user['role']] ?? ucfirst($user['role']);
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $roleColor ?>">
                                    <?= $roleName ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $statusColors = [
                                    'ativo' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                    'inativo' => 'bg-gray-100 text-gray-800 dark:bg-zinc-700 dark:text-zinc-300',
                                    'suspenso' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                ];
                                $statusColor = $statusColors[$user['status']] ?? 'bg-gray-100 text-gray-800 dark:bg-zinc-700 dark:text-zinc-300';
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusColor ?>">
                                    <?= ucfirst($user['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <button onclick="editUser(<?= $user['id'] ?>)" 
                                            class="bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-500 text-white px-3 py-1 rounded text-xs transition-colors">
                                        Editar
                                    </button>
                                    <button onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" 
                                            class="bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-500 text-white px-3 py-1 rounded text-xs transition-colors">
                                        Excluir
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-zinc-400">
                            <div class="flex flex-col items-center justify-center py-8">
                                <svg class="w-12 h-12 text-gray-400 dark:text-zinc-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                </svg>
                                <p class="text-gray-500 dark:text-zinc-400">
                                    <?= !empty($q) ? 'Nenhum usuário encontrado para "' . htmlspecialchars($q) . '"' : 'Nenhum usuário cadastrado.' ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Mobile: Cards -->
<div class="md:hidden space-y-4">
    <?php if (!empty($users)): ?>
        <?php foreach ($users as $user): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 border border-gray-200 dark:border-zinc-700">
                <!-- Header: Nome e ID -->
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-zinc-100">
                            <?= htmlspecialchars($user['name']) ?>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                            ID: #<?= $user['id'] ?>
                        </p>
                    </div>
                    <?php
                    $statusColors = [
                        'ativo' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                        'inativo' => 'bg-gray-100 text-gray-800 dark:bg-zinc-700 dark:text-zinc-300',
                        'suspenso' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                    ];
                    $statusColor = $statusColors[$user['status']] ?? 'bg-gray-100 text-gray-800 dark:bg-zinc-700 dark:text-zinc-300';
                    ?>
                    <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full <?= $statusColor ?> whitespace-nowrap">
                        <?= ucfirst($user['status']) ?>
                    </span>
                </div>

                <!-- E-mail -->
                <div class="mb-3 pb-3 border-b border-gray-200 dark:border-zinc-700">
                    <div class="text-sm text-gray-600 dark:text-zinc-400 flex items-center">
                        <svg class="w-4 h-4 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span class="truncate"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                </div>

                <!-- Perfil -->
                <div class="mb-3">
                    <div class="text-xs text-gray-500 dark:text-zinc-400 mb-1">Perfil</div>
                    <?php
                    $roleColors = [
                        'admin' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                        'user' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                        'moderator' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                        'client' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                        'affiliate' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400'
                    ];
                    $roleNames = [
                        'admin' => 'Administrador',
                        'user' => 'Usuário',
                        'moderator' => 'Moderador',
                        'client' => 'Cliente',
                        'affiliate' => 'Afiliado'
                    ];
                    $roleColor = $roleColors[$user['role']] ?? 'bg-gray-100 text-gray-800 dark:bg-zinc-700 dark:text-zinc-300';
                    $roleName = $roleNames[$user['role']] ?? ucfirst($user['role']);
                    ?>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $roleColor ?>">
                        <?= $roleName ?>
                    </span>
                </div>

                <!-- Ações -->
                <div class="flex gap-2">
                    <button onclick="editUser(<?= $user['id'] ?>)" 
                            class="flex-1 bg-indigo-500 hover:bg-indigo-600 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white px-3 py-2 rounded text-sm font-medium transition-colors">
                        Editar
                    </button>
                    <button onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" 
                            class="flex-1 bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-500 text-white px-3 py-2 rounded text-sm font-medium transition-colors">
                        Excluir
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-8 text-center border border-gray-200 dark:border-zinc-700">
            <svg class="w-12 h-12 text-gray-400 dark:text-zinc-500 mb-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
            </svg>
            <p class="text-gray-500 dark:text-zinc-400">
                <?= !empty($q) ? 'Nenhum usuário encontrado para "' . htmlspecialchars($q) . '"' : 'Nenhum usuário cadastrado.' ?>
            </p>
        </div>
    <?php endif; ?>
</div>