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

$boardId = intval($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($boardId <= 0) {
    header("Location: /dashboard?page=crm/boards");
    exit;
}

// Buscar dados do quadro
try {
    $stmt = $pdo->prepare("SELECT * FROM crm_boards WHERE id = ? AND user_id = ?");
    $stmt->execute([$boardId, $userId]);
    $board = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$board) {
        header("Location: /dashboard?page=crm/boards");
        exit;
    }

    // Buscar colunas
    $stmt = $pdo->prepare("SELECT * FROM crm_columns WHERE board_id = ? ORDER BY position ASC");
    $stmt->execute([$boardId]);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar cards de todas as colunas
    $cards = [];
    foreach ($columns as $column) {
        $stmt = $pdo->prepare("
            SELECT c.*, u.name as assigned_name,
                   GROUP_CONCAT(t.id) as tag_ids,
                   GROUP_CONCAT(t.name) as tag_names,
                   GROUP_CONCAT(t.color) as tag_colors
            FROM crm_cards c
            LEFT JOIN users u ON c.assigned_to = u.id
            LEFT JOIN crm_card_tags ct ON c.id = ct.card_id
            LEFT JOIN crm_tags t ON ct.tag_id = t.id
            WHERE c.column_id = ?
            GROUP BY c.id
            ORDER BY c.position ASC
        ");
        $stmt->execute([$column['id']]);
        $cards[$column['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar tags do quadro
    $stmt = $pdo->prepare("SELECT * FROM crm_tags WHERE board_id = ? ORDER BY name ASC");
    $stmt->execute([$boardId]);
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar usuários para atribuição
    $stmt = $pdo->query("SELECT id, name, email FROM users WHERE status = 'active' ORDER BY name ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Erro ao buscar dados do quadro: ' . $e->getMessage());
    header("Location: /dashboard?page=crm/boards");
    exit;
}
?>

<!-- SortableJS para drag-and-drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<style>
.kanban-container {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding-bottom: 1rem;
    min-height: calc(100vh - 200px);
}

.kanban-column {
    flex: 0 0 320px;
    min-width: 320px;
    max-width: 320px;
    display: flex;
    flex-direction: column;
    cursor: move;
}

.kanban-column.sortable-ghost {
    opacity: 0.4;
}

.kanban-column.sortable-drag {
    transform: rotate(2deg);
    cursor: grabbing;
}

.kanban-column-header {
    padding: 0.75rem 1rem;
    border-radius: 8px 8px 0 0;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dark .kanban-column-header {
    border-top-color: #3f3f46;
    border-bottom: 1px solid #3f3f46;
}

.kanban-column-header {
    border-bottom: 1px solid #e5e7eb;
}

.kanban-cards {
    flex: 1;
    padding: 0.5rem;
    min-height: 100px;
    border-radius: 0 0 8px 8px;
}

.kanban-card {
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    border-radius: 8px;
    cursor: grab;
    transition: transform 0.2s, box-shadow 0.2s;
}

.kanban-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.kanban-card.sortable-ghost {
    opacity: 0.4;
}

.kanban-card.sortable-drag {
    cursor: grabbing;
    transform: rotate(2deg);
}

.priority-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.priority-low { background-color: rgba(100, 116, 139, 0.2); color: rgb(100, 116, 139); }
.priority-medium { background-color: rgba(245, 158, 11, 0.2); color: rgb(245, 158, 11); }
.priority-high { background-color: rgba(239, 68, 68, 0.2); color: rgb(239, 68, 68); }

.dark .priority-low { background-color: rgba(148, 163, 184, 0.2); color: rgb(203, 213, 225); }
.dark .priority-medium { background-color: rgba(251, 191, 36, 0.2); color: rgb(253, 224, 71); }
.dark .priority-high { background-color: rgba(248, 113, 113, 0.2); color: rgb(252, 165, 165); }
</style>

<div class="w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-6 gap-3">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <button onclick="window.location.href='/dashboard?page=crm/boards'" class="btn btn-ghost btn-sm btn-circle">
                    <i data-feather="arrow-left" class="w-5 h-5"></i>
                </button>
                <div class="w-4 h-4 rounded-full" style="background-color: <?= htmlspecialchars($board['color']) ?>"></div>
                <h1 class="text-xl sm:text-2xl md:text-3xl font-bold"><?= htmlspecialchars($board['name']) ?></h1>
            </div>
            <?php if ($board['description']): ?>
                <p class="text-sm opacity-60 ml-14"><?= htmlspecialchars($board['description']) ?></p>
            <?php endif; ?>
        </div>
        <div class="flex gap-2">
            <button onclick="showTagsModal()" class="btn btn-outline btn-sm">
                <i data-feather="tag" class="w-4 h-4"></i>
                Tags
            </button>
            <button onclick="showAddColumnModal()" class="btn btn-primary btn-sm">
                <i data-feather="plus" class="w-4 h-4"></i>
                Nova Coluna
            </button>
        </div>
    </div>

    <!-- Kanban Board -->
    <div class="kanban-container">
        <?php foreach ($columns as $column): ?>
            <div class="kanban-column" data-column-id="<?= $column['id'] ?>">
                <div class="kanban-column-header bg-base-200" style="border-top: 3px solid <?= htmlspecialchars($column['color']) ?>">
                    <div class="flex items-center gap-2">
                        <span><?= htmlspecialchars($column['name']) ?></span>
                        <span class="badge badge-sm opacity-60"><?= count($cards[$column['id']] ?? []) ?></span>
                    </div>
                    <div class="flex gap-1">
                        <button onclick="event.stopPropagation(); editColumn(<?= $column['id'] ?>, '<?= htmlspecialchars($column['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($column['color']) ?>')" class="btn btn-ghost btn-sm btn-square">
                            <i data-feather="edit-2" class="w-4 h-4"></i>
                        </button>
                        <button onclick="event.stopPropagation(); deleteColumn(<?= $column['id'] ?>, '<?= htmlspecialchars($column['name'], ENT_QUOTES) ?>')" class="btn btn-ghost btn-sm btn-square text-error">
                            <i data-feather="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
                <div class="kanban-cards bg-base-200" data-column-id="<?= $column['id'] ?>">
                    <?php foreach ($cards[$column['id']] ?? [] as $card): ?>
                        <div class="kanban-card bg-base-100 shadow" data-card-id="<?= $card['id'] ?>" onclick="showCardModal(<?= $card['id'] ?>)">
                            <h3 class="font-semibold text-sm mb-2"><?= htmlspecialchars($card['title']) ?></h3>

                            <?php if ($card['description']): ?>
                                <p class="text-xs opacity-60 mb-2 line-clamp-2"><?= htmlspecialchars($card['description']) ?></p>
                            <?php endif; ?>

                            <div class="flex flex-wrap gap-1 mb-2">
                                <?php if ($card['tag_ids']):
                                    $tagIds = explode(',', $card['tag_ids']);
                                    $tagNames = explode(',', $card['tag_names']);
                                    $tagColors = explode(',', $card['tag_colors']);
                                    for ($i = 0; $i < count($tagIds); $i++): ?>
                                        <span class="badge badge-xs" style="background-color: <?= htmlspecialchars($tagColors[$i]) ?>20; color: <?= htmlspecialchars($tagColors[$i]) ?>; border: 1px solid <?= htmlspecialchars($tagColors[$i]) ?>40;">
                                            <?= htmlspecialchars($tagNames[$i]) ?>
                                        </span>
                                    <?php endfor;
                                endif; ?>
                            </div>

                            <div class="flex justify-between items-center text-xs">
                                <span class="priority-badge priority-<?= htmlspecialchars($card['priority']) ?>">
                                    <?php if ($card['priority'] === 'high'): ?>
                                        <i data-feather="alert-circle" class="w-3 h-3"></i>
                                    <?php elseif ($card['priority'] === 'medium'): ?>
                                        <i data-feather="minus-circle" class="w-3 h-3"></i>
                                    <?php else: ?>
                                        <i data-feather="circle" class="w-3 h-3"></i>
                                    <?php endif; ?>
                                    <?= ucfirst($card['priority']) ?>
                                </span>
                                <?php if ($card['assigned_to']): ?>
                                    <span class="opacity-60" title="<?= htmlspecialchars($card['assigned_name']) ?>">
                                        <i data-feather="user" class="w-3 h-3"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <button onclick="event.stopPropagation(); showAddCardModal(<?= $column['id'] ?>)" class="btn btn-ghost btn-sm w-full justify-start opacity-60 hover:opacity-100">
                        <i data-feather="plus" class="w-4 h-4"></i>
                        Adicionar card
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Dados globais
const boardId = <?= $boardId ?>;
const boardTags = <?= json_encode($tags) ?>;
const boardUsers = <?= json_encode($users) ?>;

// Renderizar ícones
if (typeof feather !== 'undefined') {
    feather.replace();
}

// Inicializar Sortable em cada coluna (para cards)
document.addEventListener('DOMContentLoaded', () => {
    const columns = document.querySelectorAll('.kanban-cards');

    columns.forEach(column => {
        new Sortable(column, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            filter: '.btn',
            preventOnFilter: false,
            delay: 100,
            delayOnTouchOnly: false,
            onEnd: function(evt) {
                const cardId = evt.item.getAttribute('data-card-id');
                const newColumnId = evt.to.getAttribute('data-column-id');
                const newPosition = evt.newIndex;

                moveCard(cardId, newColumnId, newPosition);
            }
        });
    });

    // Inicializar Sortable para as colunas (reordenação)
    const kanbanContainer = document.querySelector('.kanban-container');
    if (kanbanContainer) {
        new Sortable(kanbanContainer, {
            animation: 150,
            handle: '.kanban-column-header',
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: function(evt) {
                // Coletar nova ordem das colunas
                const columns = Array.from(kanbanContainer.querySelectorAll('.kanban-column'));
                const order = columns.map(col => col.getAttribute('data-column-id'));

                reorderColumns(order);
            }
        });
    }
});

// Mover card
async function moveCard(cardId, columnId, position) {
    try {
        const formData = new FormData();
        formData.append('card_id', cardId);
        formData.append('column_id', columnId);
        formData.append('position', position);

        const res = await fetch('/modules/crm/cards/move.php', {
            method: 'POST',
            body: formData
        });

        const result = await res.text();

        if (!res.ok || result !== "success") {
            Swal.fire('Erro!', result || 'Erro ao mover card', 'error');
            location.reload();
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao mover card', 'error');
        location.reload();
    }
}

// Reordenar colunas
async function reorderColumns(order) {
    try {
        const formData = new FormData();
        formData.append('board_id', boardId);
        formData.append('order', JSON.stringify(order));

        const res = await fetch('/modules/crm/columns/reorder.php', {
            method: 'POST',
            body: formData
        });

        const result = await res.text();

        if (!res.ok || result !== "success") {
            Swal.fire('Erro!', result || 'Erro ao reordenar colunas', 'error');
            location.reload();
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao reordenar colunas', 'error');
        location.reload();
    }
}

// Modal de adicionar card
function showAddCardModal(columnId) {
    const tagsCheckboxes = boardTags.map(tag => `
        <label class="flex items-center gap-2 p-2 rounded cursor-pointer hover:bg-base-200" style="border: 2px solid ${tag.color}20;">
            <input type="checkbox" class="checkbox checkbox-sm" value="${tag.id}" data-tag-checkbox />
            <span class="badge badge-sm" style="background-color: ${tag.color}20; color: ${tag.color}; border: 1px solid ${tag.color}40;">
                ${tag.name}
            </span>
        </label>
    `).join('');

    const usersOptions = boardUsers.map(user =>
        `<option value="${user.id}">${user.name}</option>`
    ).join('');

    Swal.fire({
        title: 'Novo Card',
        html: `
            <div class="space-y-3 text-left">
                <label class="input input-bordered flex items-center gap-2">
                    <i data-feather="credit-card" class="w-5 h-5 opacity-60"></i>
                    <input type="text" id="card_title" placeholder="Título do card" class="grow" />
                </label>

                <textarea id="card_description" placeholder="Descrição (opcional)" class="textarea textarea-bordered w-full" rows="3"></textarea>

                <select id="card_priority" class="select select-bordered w-full">
                    <option value="low">Prioridade: Baixa</option>
                    <option value="medium" selected>Prioridade: Média</option>
                    <option value="high">Prioridade: Alta</option>
                </select>

                <select id="card_assigned" class="select select-bordered w-full">
                    <option value="">Não atribuído</option>
                    ${usersOptions}
                </select>

                ${boardTags.length > 0 ? `
                    <div>
                        <label class="block text-sm opacity-60 mb-2">Tags:</label>
                        <div class="space-y-2 max-h-40 overflow-y-auto p-2 border border-base-300 rounded-lg">
                            ${tagsCheckboxes}
                        </div>
                    </div>
                ` : ''}
            </div>
        `,
        width: '600px',
        showCancelButton: true,
        confirmButtonText: 'Criar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const title = document.getElementById('card_title').value.trim();
            if (!title) {
                Swal.showValidationMessage('Título é obrigatório');
                return false;
            }

            const tags = Array.from(document.querySelectorAll('[data-tag-checkbox]:checked')).map(cb => cb.value);

            return {
                column_id: columnId,
                title,
                description: document.getElementById('card_description').value.trim(),
                priority: document.getElementById('card_priority').value,
                assigned_to: document.getElementById('card_assigned').value,
                tags: JSON.stringify(tags)
            };
        },
        didOpen: () => {
            feather.replace();
        }
    }).then(result => {
        if (result.isConfirmed) {
            createCard(result.value);
        }
    });
}

// Criar card
async function createCard(data) {
    try {
        const formData = new FormData();
        Object.keys(data).forEach(key => formData.append(key, data[key]));

        const res = await fetch('/core/crud/save.php?module=crm&entity=card', {
            method: 'POST',
            body: formData
        });

        const result = await res.text();

        if (res.ok && result === "success") {
            Swal.fire('Sucesso!', 'Card criado com sucesso', 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Erro!', result || 'Erro ao criar card', 'error');
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao criar card', 'error');
    }
}

// Modal de detalhes do card
async function showCardModal(cardId) {
    try {
        const res = await fetch(`/modules/crm/cards/get.php?id=${cardId}`);
        const card = await res.json();

        if (!card.id) {
            Swal.fire('Erro!', 'Card não encontrado', 'error');
            return;
        }

        const tagsCheckboxes = boardTags.map(tag => {
            const isChecked = card.tags && card.tags.includes(tag.id) ? 'checked' : '';
            return `
                <label class="flex items-center gap-2 p-2 rounded cursor-pointer hover:bg-base-200" style="border: 2px solid ${tag.color}20;">
                    <input type="checkbox" class="checkbox checkbox-sm" value="${tag.id}" ${isChecked} data-tag-checkbox />
                    <span class="badge badge-sm" style="background-color: ${tag.color}20; color: ${tag.color}; border: 1px solid ${tag.color}40;">
                        ${tag.name}
                    </span>
                </label>
            `;
        }).join('');

        const usersOptions = boardUsers.map(user => {
            const isSelected = card.assigned_to == user.id ? 'selected' : '';
            return `<option value="${user.id}" ${isSelected}>${user.name}</option>`;
        }).join('');

        Swal.fire({
            title: 'Editar Card',
            html: `
                <div class="space-y-3 text-left">
                    <label class="input input-bordered flex items-center gap-2">
                        <i data-feather="credit-card" class="w-5 h-5 opacity-60"></i>
                        <input type="text" id="card_title" placeholder="Título do card" class="grow" value="${card.title}" />
                    </label>

                    <textarea id="card_description" placeholder="Descrição (opcional)" class="textarea textarea-bordered w-full" rows="3">${card.description || ''}</textarea>

                    <select id="card_priority" class="select select-bordered w-full">
                        <option value="low" ${card.priority === 'low' ? 'selected' : ''}>Prioridade: Baixa</option>
                        <option value="medium" ${card.priority === 'medium' ? 'selected' : ''}>Prioridade: Média</option>
                        <option value="high" ${card.priority === 'high' ? 'selected' : ''}>Prioridade: Alta</option>
                    </select>

                    <select id="card_assigned" class="select select-bordered w-full">
                        <option value="">Não atribuído</option>
                        ${usersOptions}
                    </select>

                    ${boardTags.length > 0 ? `
                        <div>
                            <label class="block text-sm opacity-60 mb-2">Tags:</label>
                            <div class="space-y-2 max-h-40 overflow-y-auto p-2 border border-base-300 rounded-lg">
                                ${tagsCheckboxes}
                            </div>
                        </div>
                    ` : ''}

                    <button onclick="deleteCard(${cardId})" class="btn btn-error btn-sm w-full">
                        <i data-feather="trash-2" class="w-4 h-4"></i>
                        Excluir Card
                    </button>
                </div>
            `,
            width: '600px',
            showCancelButton: true,
            confirmButtonText: 'Salvar',
            cancelButtonText: 'Fechar',
            preConfirm: () => {
                const title = document.getElementById('card_title').value.trim();
                if (!title) {
                    Swal.showValidationMessage('Título é obrigatório');
                    return false;
                }

                const tags = Array.from(document.querySelectorAll('[data-tag-checkbox]:checked')).map(cb => cb.value);

                return {
                    id: cardId,
                    title,
                    description: document.getElementById('card_description').value.trim(),
                    priority: document.getElementById('card_priority').value,
                    assigned_to: document.getElementById('card_assigned').value,
                    tags: JSON.stringify(tags)
                };
            },
            didOpen: () => {
                feather.replace();
            }
        }).then(result => {
            if (result.isConfirmed) {
                updateCard(result.value);
            }
        });
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao carregar card', 'error');
    }
}

// Atualizar card
async function updateCard(data) {
    try {
        const formData = new FormData();
        Object.keys(data).forEach(key => formData.append(key, data[key]));

        const res = await fetch('/core/crud/save.php?module=crm&entity=card', {
            method: 'POST',
            body: formData
        });

        const result = await res.text();

        if (res.ok && result === "success") {
            Swal.fire('Sucesso!', 'Card atualizado com sucesso', 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Erro!', result || 'Erro ao atualizar card', 'error');
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao atualizar card', 'error');
    }
}

// Deletar card
async function deleteCard(cardId) {
    const result = await Swal.fire({
        title: 'Tem certeza?',
        text: 'Deseja excluir este card?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444'
    });

    if (!result.isConfirmed) return;

    try {
        const res = await fetch(`/core/crud/delete.php?module=crm&entity=card&id=${cardId}`, {
            method: 'POST'
        });

        const response = await res.text();

        if (res.ok && response === "success") {
            Swal.fire('Excluído!', 'Card excluído com sucesso', 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Erro!', response || 'Erro ao excluir card', 'error');
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao excluir card', 'error');
    }
}

// Modal de adicionar coluna
function showAddColumnModal() {
    Swal.fire({
        title: 'Nova Coluna',
        html: `
            <div class="space-y-3 text-left">
                <label class="input input-bordered flex items-center gap-2">
                    <i data-feather="columns" class="w-5 h-5 opacity-60"></i>
                    <input type="text" id="column_name" placeholder="Nome da coluna" class="grow" />
                </label>

                <div>
                    <label class="block text-sm opacity-60 mb-2">Cor da coluna:</label>
                    <input type="color" id="column_color" value="#64748b" class="w-full h-10" style="border-radius: 8px;" />
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Criar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const name = document.getElementById('column_name').value.trim();
            if (!name) {
                Swal.showValidationMessage('Nome da coluna é obrigatório');
                return false;
            }

            return {
                board_id: boardId,
                name,
                color: document.getElementById('column_color').value
            };
        },
        didOpen: () => {
            feather.replace();
        }
    }).then(result => {
        if (result.isConfirmed) {
            createColumn(result.value);
        }
    });
}

// Criar coluna
async function createColumn(data) {
    try {
        const formData = new FormData();
        Object.keys(data).forEach(key => formData.append(key, data[key]));

        const res = await fetch('/core/crud/save.php?module=crm&entity=column', {
            method: 'POST',
            body: formData
        });

        const result = await res.text();

        if (res.ok && result === "success") {
            Swal.fire('Sucesso!', 'Coluna criada com sucesso', 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Erro!', result || 'Erro ao criar coluna', 'error');
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao criar coluna', 'error');
    }
}

// Editar coluna
function editColumn(id, name, color) {
    Swal.fire({
        title: 'Editar Coluna',
        html: `
            <div class="space-y-3 text-left">
                <label class="input input-bordered flex items-center gap-2">
                    <i data-feather="columns" class="w-5 h-5 opacity-60"></i>
                    <input type="text" id="column_name" placeholder="Nome da coluna" class="grow" value="${name}" />
                </label>

                <div>
                    <label class="block text-sm opacity-60 mb-2">Cor da coluna:</label>
                    <input type="color" id="column_color" value="${color}" class="w-full h-10" style="border-radius: 8px;" />
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Salvar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const name = document.getElementById('column_name').value.trim();
            if (!name) {
                Swal.showValidationMessage('Nome da coluna é obrigatório');
                return false;
            }

            return {
                id,
                name,
                color: document.getElementById('column_color').value
            };
        },
        didOpen: () => {
            feather.replace();
        }
    }).then(result => {
        if (result.isConfirmed) {
            updateColumn(result.value);
        }
    });
}

// Atualizar coluna
async function updateColumn(data) {
    try {
        const formData = new FormData();
        Object.keys(data).forEach(key => formData.append(key, data[key]));

        const res = await fetch('/core/crud/save.php?module=crm&entity=column', {
            method: 'POST',
            body: formData
        });

        const result = await res.text();

        if (res.ok && result === "success") {
            Swal.fire('Sucesso!', 'Coluna atualizada com sucesso', 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Erro!', result || 'Erro ao atualizar coluna', 'error');
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao atualizar coluna', 'error');
    }
}

// Deletar coluna
async function deleteColumn(id, name) {
    const result = await Swal.fire({
        title: 'Tem certeza?',
        text: `Deseja excluir a coluna "${name}"? Ela deve estar vazia.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444'
    });

    if (!result.isConfirmed) return;

    try {
        const res = await fetch(`/core/crud/delete.php?module=crm&entity=column&id=${id}`, {
            method: 'POST'
        });

        const response = await res.text();

        if (res.ok && response === "success") {
            Swal.fire('Excluído!', 'Coluna excluída com sucesso', 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Erro!', response.error || 'Erro ao excluir coluna', 'error');
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao excluir coluna', 'error');
    }
}

// Modal de gerenciar tags
function showTagsModal() {
    const tagsList = boardTags.map(tag => `
        <div class="flex items-center justify-between p-2 rounded" style="background-color: ${tag.color}20;">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full" style="background-color: ${tag.color}"></div>
                <span>${tag.name}</span>
            </div>
            <div class="flex gap-1">
                <button onclick="editTag(${tag.id}, '${tag.name}', '${tag.color}')" class="btn btn-ghost btn-xs">
                    <i data-feather="edit-2" class="w-3 h-3"></i>
                </button>
                <button onclick="deleteTag(${tag.id}, '${tag.name}')" class="btn btn-ghost btn-xs">
                    <i data-feather="trash-2" class="w-3 h-3"></i>
                </button>
            </div>
        </div>
    `).join('');

    Swal.fire({
        title: 'Gerenciar Tags',
        html: `
            <div class="space-y-3 text-left">
                <button onclick="showAddTagModal()" class="btn btn-primary btn-sm w-full">
                    <i data-feather="plus" class="w-4 h-4"></i>
                    Nova Tag
                </button>
                ${boardTags.length > 0 ? `
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        ${tagsList}
                    </div>
                ` : '<p class="text-center opacity-60 py-4">Nenhuma tag criada</p>'}
            </div>
        `,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'Fechar',
        width: '500px',
        didOpen: () => {
            feather.replace();
        }
    });
}

// Adicionar tag
function showAddTagModal() {
    Swal.fire({
        title: 'Nova Tag',
        html: `
            <div class="space-y-3 text-left">
                <label class="input input-bordered flex items-center gap-2">
                    <i data-feather="tag" class="w-5 h-5 opacity-60"></i>
                    <input type="text" id="tag_name" placeholder="Nome da tag" class="grow" />
                </label>

                <div>
                    <label class="block text-sm opacity-60 mb-2">Cor da tag:</label>
                    <input type="color" id="tag_color" value="#6366f1" class="w-full h-10" style="border-radius: 8px;" />
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Criar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const name = document.getElementById('tag_name').value.trim();
            if (!name) {
                Swal.showValidationMessage('Nome da tag é obrigatório');
                return false;
            }

            return {
                board_id: boardId,
                name,
                color: document.getElementById('tag_color').value
            };
        },
        didOpen: () => {
            feather.replace();
        }
    }).then(result => {
        if (result.isConfirmed) {
            createTag(result.value);
        }
    });
}

// Criar tag
async function createTag(data) {
    try {
        const formData = new FormData();
        Object.keys(data).forEach(key => formData.append(key, data[key]));

        const res = await fetch('/core/crud/save.php?module=crm&entity=tag', {
            method: 'POST',
            body: formData
        });

        const result = await res.text();

        if (res.ok && result === "success") {
            Swal.fire('Sucesso!', 'Tag criada com sucesso', 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Erro!', result || 'Erro ao criar tag', 'error');
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao criar tag', 'error');
    }
}

// Editar tag
function editTag(id, name, color) {
    Swal.fire({
        title: 'Editar Tag',
        html: `
            <div class="space-y-3 text-left">
                <label class="input input-bordered flex items-center gap-2">
                    <i data-feather="tag" class="w-5 h-5 opacity-60"></i>
                    <input type="text" id="tag_name" placeholder="Nome da tag" class="grow" value="${name}" />
                </label>

                <div>
                    <label class="block text-sm opacity-60 mb-2">Cor da tag:</label>
                    <input type="color" id="tag_color" value="${color}" class="w-full h-10" style="border-radius: 8px;" />
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Salvar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const name = document.getElementById('tag_name').value.trim();
            if (!name) {
                Swal.showValidationMessage('Nome da tag é obrigatório');
                return false;
            }

            return {
                id,
                name,
                color: document.getElementById('tag_color').value
            };
        },
        didOpen: () => {
            feather.replace();
        }
    }).then(result => {
        if (result.isConfirmed) {
            updateTag(result.value);
        }
    });
}

// Atualizar tag
async function updateTag(data) {
    try {
        const formData = new FormData();
        Object.keys(data).forEach(key => formData.append(key, data[key]));

        const res = await fetch('/core/crud/save.php?module=crm&entity=tag', {
            method: 'POST',
            body: formData
        });

        const result = await res.text();

        if (res.ok && result === "success") {
            Swal.fire('Sucesso!', 'Tag atualizada com sucesso', 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Erro!', result || 'Erro ao atualizar tag', 'error');
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao atualizar tag', 'error');
    }
}

// Deletar tag
async function deleteTag(id, name) {
    const result = await Swal.fire({
        title: 'Tem certeza?',
        text: `Deseja excluir a tag "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444'
    });

    if (!result.isConfirmed) return;

    try {
        const res = await fetch(`/core/crud/delete.php?module=crm&entity=tag&id=${id}`, {
            method: 'POST'
        });

        const response = await res.text();

        if (res.ok && response === "success") {
            Swal.fire('Excluído!', 'Tag excluída com sucesso', 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Erro!', response || 'Erro ao excluir tag', 'error');
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao excluir tag', 'error');
    }
}
</script>
