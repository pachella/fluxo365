<?php
session_start();
require_once("../core/db.php");
require_once("../core/PermissionManager.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: /auth/login");
    exit;
}

$permissionManager = new PermissionManager(
    $_SESSION['user_role'],
    $_SESSION['user_id'] ?? null
);

$userId = $_SESSION['user_id'];

// Buscar quadros do usuário
try {
    $stmt = $pdo->prepare("
        SELECT b.*,
               (SELECT COUNT(*) FROM crm_columns WHERE board_id = b.id) as columns_count,
               (SELECT COUNT(*) FROM crm_cards c
                INNER JOIN crm_columns col ON c.column_id = col.id
                WHERE col.board_id = b.id) as cards_count
        FROM crm_boards b
        WHERE b.user_id = ?
        ORDER BY b.updated_at DESC
    ");
    $stmt->execute([$userId]);
    $boards = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erro ao buscar quadros: ' . $e->getMessage());
    $boards = [];
}
?>

<style>
.board-card {
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.board-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.board-card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 1.25rem;
}

.board-card-footer {
    border-top: 1px solid;
    padding: 0.75rem 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
}

.dark .board-card-footer {
    border-color: #3f3f46;
}

.board-card-footer {
    border-color: #e5e7eb;
}

.stat-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-top: 1rem;
}

.stat-item {
    text-align: center;
}

.stat-label {
    font-size: 0.75rem;
    opacity: 0.6;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.125rem;
    font-weight: 700;
}
</style>

<div class="w-full max-w-full overflow-x-hidden">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold">CRM - Meus Quadros</h1>
            <p class="text-sm opacity-60 mt-1">Gerencie seus pipelines e oportunidades</p>
        </div>
        <button onclick="showCreateBoardModal()" class="btn btn-primary">
            <i data-feather="plus" class="w-5 h-5"></i>
            Novo Quadro
        </button>
    </div>

    <?php if (empty($boards)): ?>
        <!-- Estado vazio -->
        <div class="card bg-base-200 shadow">
            <div class="card-body text-center py-16">
                <i data-feather="trello" class="w-16 h-16 mx-auto mb-4 opacity-40"></i>
                <h2 class="text-xl font-bold mb-2">Nenhum quadro criado</h2>
                <p class="opacity-60 mb-6">Crie seu primeiro quadro para começar a organizar suas oportunidades</p>
                <button onclick="showCreateBoardModal()" class="btn btn-primary mx-auto">
                    <i data-feather="plus" class="w-5 h-5"></i>
                    Criar Primeiro Quadro
                </button>
            </div>
        </div>
    <?php else: ?>
        <!-- Grid de Quadros -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($boards as $board): ?>
                <div class="card bg-base-200 shadow board-card" onclick="window.location.href='/crm/board?id=<?= $board['id'] ?>'">
                    <div class="board-card-body">
                        <!-- Barra de cor -->
                        <div class="w-full h-2 rounded-full mb-4" style="background-color: <?= htmlspecialchars($board['color']) ?>"></div>

                        <h2 class="font-bold text-lg mb-2">
                            <?= htmlspecialchars($board['name']) ?>
                        </h2>

                        <?php if ($board['description']): ?>
                            <p class="text-sm opacity-60 line-clamp-2 mb-3">
                                <?= htmlspecialchars($board['description']) ?>
                            </p>
                        <?php endif; ?>

                        <!-- Estatísticas -->
                        <div class="stat-grid">
                            <div class="stat-item">
                                <div class="stat-label">Colunas</div>
                                <div class="stat-value"><?= $board['columns_count'] ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Cards</div>
                                <div class="stat-value"><?= $board['cards_count'] ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer com Ações -->
                    <div class="board-card-footer">
                        <button onclick="event.stopPropagation(); window.location.href='/crm/board?id=<?= $board['id'] ?>'" class="btn btn-ghost btn-sm btn-square" title="Abrir quadro">
                            <i data-feather="external-link" class="w-4 h-4"></i>
                        </button>
                        <div class="flex gap-1">
                            <button onclick="event.stopPropagation(); editBoard(<?= $board['id'] ?>, '<?= htmlspecialchars($board['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($board['description'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($board['color']) ?>')" class="btn btn-ghost btn-sm btn-square" title="Editar">
                                <i data-feather="edit-2" class="w-4 h-4"></i>
                            </button>
                            <button onclick="event.stopPropagation(); deleteBoard(<?= $board['id'] ?>, '<?= htmlspecialchars($board['name'], ENT_QUOTES) ?>')" class="btn btn-ghost btn-sm btn-square text-error" title="Excluir">
                                <i data-feather="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Renderizar ícones
if (typeof feather !== 'undefined') {
    feather.replace();
}

// Modal de criar quadro
function showCreateBoardModal() {
    Swal.fire({
        title: 'Novo Quadro',
        html: `
            <div class="space-y-4 text-left">
                <label class="input input-bordered flex items-center gap-2">
                    <i data-feather="trello" class="w-5 h-5 opacity-60"></i>
                    <input type="text" id="board_name" placeholder="Nome do quadro" class="grow" />
                </label>

                <label class="input input-bordered flex items-center gap-2">
                    <i data-feather="align-left" class="w-5 h-5 opacity-60"></i>
                    <input type="text" id="board_description" placeholder="Descrição (opcional)" class="grow" />
                </label>

                <div>
                    <label class="block text-sm opacity-60 mb-2">Cor do quadro:</label>
                    <input type="color" id="board_color" value="#6366f1" class="w-full h-10" style="border-radius: 8px;" />
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Criar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const name = document.getElementById('board_name').value.trim();
            const description = document.getElementById('board_description').value.trim();
            const color = document.getElementById('board_color').value;

            if (!name) {
                Swal.showValidationMessage('Nome do quadro é obrigatório');
                return false;
            }

            return { name, description, color };
        },
        didOpen: () => {
            feather.replace();
        }
    }).then(result => {
        if (result.isConfirmed) {
            createBoard(result.value);
        }
    });
}

// Criar quadro
async function createBoard(data) {
    try {
        const formData = new FormData();
        formData.append('name', data.name);
        formData.append('description', data.description);
        formData.append('color', data.color);

        const res = await fetch('/core/crud/save.php?module=crm&entity=board', {
            method: 'POST',
            body: formData
        });

        const result = await res.text();

        if (res.ok && result === "success") {
            Swal.fire('Sucesso!', 'Quadro criado com sucesso', 'success').then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire('Erro!', result || 'Erro ao criar quadro', 'error');
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao criar quadro', 'error');
    }
}

// Editar quadro
function editBoard(id, name, description, color) {
    Swal.fire({
        title: 'Editar Quadro',
        html: `
            <div class="space-y-4 text-left">
                <label class="input input-bordered flex items-center gap-2">
                    <i data-feather="trello" class="w-5 h-5 opacity-60"></i>
                    <input type="text" id="board_name" placeholder="Nome do quadro" class="grow" value="${name}" />
                </label>

                <label class="input input-bordered flex items-center gap-2">
                    <i data-feather="align-left" class="w-5 h-5 opacity-60"></i>
                    <input type="text" id="board_description" placeholder="Descrição (opcional)" class="grow" value="${description}" />
                </label>

                <div>
                    <label class="block text-sm opacity-60 mb-2">Cor do quadro:</label>
                    <input type="color" id="board_color" value="${color}" class="w-full h-10" style="border-radius: 8px;" />
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Salvar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const name = document.getElementById('board_name').value.trim();
            const description = document.getElementById('board_description').value.trim();
            const color = document.getElementById('board_color').value;

            if (!name) {
                Swal.showValidationMessage('Nome do quadro é obrigatório');
                return false;
            }

            return { id, name, description, color };
        },
        didOpen: () => {
            feather.replace();
        }
    }).then(result => {
        if (result.isConfirmed) {
            updateBoard(result.value);
        }
    });
}

// Atualizar quadro
async function updateBoard(data) {
    try {
        const formData = new FormData();
        formData.append('id', data.id);
        formData.append('name', data.name);
        formData.append('description', data.description);
        formData.append('color', data.color);

        const res = await fetch('/core/crud/save.php?module=crm&entity=board', {
            method: 'POST',
            body: formData
        });

        const result = await res.text();

        if (res.ok && result === "success") {
            Swal.fire('Sucesso!', 'Quadro atualizado com sucesso', 'success').then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire('Erro!', result || 'Erro ao atualizar quadro', 'error');
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao atualizar quadro', 'error');
    }
}

// Deletar quadro
async function deleteBoard(id, name) {
    const result = await Swal.fire({
        title: 'Tem certeza?',
        text: `Deseja realmente excluir o quadro "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('id', id);

            const res = await fetch('/core/crud/delete.php?module=crm&entity=board', {
                method: 'POST',
                body: formData
            });

            const responseText = await res.text();

            if (res.ok && responseText === "success") {
                Swal.fire('Excluído!', 'Quadro excluído com sucesso!', 'success').then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire('Erro!', responseText || 'Erro ao excluir quadro', 'error');
            }
        } catch (error) {
            Swal.fire('Erro!', 'Erro ao excluir quadro', 'error');
        }
    }
}
</script>
