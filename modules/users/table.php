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
<div class="hidden md:block card bg-base-200 shadow">
    <div class="overflow-x-auto">
        <table class="table table-zebra">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><span class="font-mono">#<?= $user['id'] ?></span></td>
                            <td class="font-medium"><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <?php
                                $roleBadges = [
                                    'admin' => 'badge-secondary',
                                    'user' => 'badge-info',
                                    'moderator' => 'badge-warning',
                                    'client' => 'badge-success',
                                    'affiliate' => 'badge-accent'
                                ];
                                $roleNames = [
                                    'admin' => 'Administrador',
                                    'user' => 'Usuário',
                                    'moderator' => 'Moderador',
                                    'client' => 'Cliente',
                                    'affiliate' => 'Afiliado'
                                ];
                                $roleBadge = $roleBadges[$user['role']] ?? 'badge-ghost';
                                $roleName = $roleNames[$user['role']] ?? ucfirst($user['role']);
                                ?>
                                <span class="badge <?= $roleBadge ?> badge-sm"><?= $roleName ?></span>
                            </td>
                            <td>
                                <?php
                                $statusBadges = [
                                    'ativo' => 'badge-success',
                                    'inativo' => 'badge-ghost',
                                    'suspenso' => 'badge-error'
                                ];
                                $statusBadge = $statusBadges[$user['status']] ?? 'badge-ghost';
                                ?>
                                <span class="badge <?= $statusBadge ?> badge-sm"><?= ucfirst($user['status']) ?></span>
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <button onclick="editUser(<?= $user['id'] ?>)"
                                            class="btn btn-action btn-xs"
                                            title="Editar">
                                        <i data-feather="edit-2" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')"
                                            class="btn btn-action btn-xs"
                                            title="Excluir">
                                        <i data-feather="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="flex flex-col items-center justify-center py-8 opacity-60">
                                <i data-feather="users" class="w-12 h-12 mb-4"></i>
                                <p>
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
            <div class="card bg-base-200 shadow">
                <div class="card-body p-4">
                    <!-- Header: Nome e ID -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="font-semibold"><?= htmlspecialchars($user['name']) ?></h3>
                            <p class="text-xs opacity-60 mt-1 font-mono">ID: #<?= $user['id'] ?></p>
                        </div>
                        <?php
                        $statusBadges = [
                            'ativo' => 'badge-success',
                            'inativo' => 'badge-ghost',
                            'suspenso' => 'badge-error'
                        ];
                        $statusBadge = $statusBadges[$user['status']] ?? 'badge-ghost';
                        ?>
                        <span class="badge <?= $statusBadge ?> badge-sm"><?= ucfirst($user['status']) ?></span>
                    </div>

                    <!-- E-mail -->
                    <div class="mb-3 pb-3 border-b border-base-300">
                        <div class="text-sm flex items-center gap-2">
                            <i data-feather="mail" class="w-4 h-4"></i>
                            <span class="truncate"><?= htmlspecialchars($user['email']) ?></span>
                        </div>
                    </div>

                    <!-- Perfil -->
                    <div class="mb-3">
                        <div class="text-xs opacity-60 mb-1">Perfil</div>
                        <?php
                        $roleBadges = [
                            'admin' => 'badge-secondary',
                            'user' => 'badge-info',
                            'moderator' => 'badge-warning',
                            'client' => 'badge-success',
                            'affiliate' => 'badge-accent'
                        ];
                        $roleNames = [
                            'admin' => 'Administrador',
                            'user' => 'Usuário',
                            'moderator' => 'Moderador',
                            'client' => 'Cliente',
                            'affiliate' => 'Afiliado'
                        ];
                        $roleBadge = $roleBadges[$user['role']] ?? 'badge-ghost';
                        $roleName = $roleNames[$user['role']] ?? ucfirst($user['role']);
                        ?>
                        <span class="badge <?= $roleBadge ?> badge-sm"><?= $roleName ?></span>
                    </div>

                    <!-- Ações -->
                    <div class="flex justify-end gap-1 mt-2">
                        <button onclick="editUser(<?= $user['id'] ?>)"
                                class="btn btn-action btn-sm"
                                title="Editar">
                            <i data-feather="edit-2" class="w-4 h-4"></i>
                        </button>
                        <button onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')"
                                class="btn btn-action btn-sm"
                                title="Excluir">
                            <i data-feather="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card bg-base-200 shadow">
            <div class="card-body text-center opacity-60">
                <i data-feather="users" class="w-12 h-12 mx-auto mb-4"></i>
                <p>
                    <?= !empty($q) ? 'Nenhum usuário encontrado para "' . htmlspecialchars($q) . '"' : 'Nenhum usuário cadastrado.' ?>
                </p>
            </div>
        </div>
    <?php endif; ?>
</div>