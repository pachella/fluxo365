<?php
session_start();
require_once(__DIR__ . "/../../core/db.php");
require_once __DIR__ . '/../../core/PermissionManager.php';
require_once __DIR__ . '/../../core/PlanService.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: /auth/login");
    exit;
}

$permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

// Buscar pastas do usuário
$sql = "SELECT 
    f.*,
    COUNT(forms.id) as form_count
FROM form_folders f
LEFT JOIN forms ON forms.folder_id = f.id AND forms.status != 'arquivado'
WHERE f.user_id = :user_id
GROUP BY f.id
ORDER BY f.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar formulários sem pasta
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM forms 
    WHERE user_id = :user_id 
    AND folder_id IS NULL 
    AND status != 'arquivado'
");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$formsWithoutFolder = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Verificar limite de pastas no plano FREE
$isFree = PlanService::isFree();
$folderLimit = $isFree ? 3 : null; // FREE: 3 pastas, PRO: ilimitado
$canCreateFolder = !$isFree || count($folders) < $folderLimit;

require_once __DIR__ . '/../../views/layout/header.php';
?>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- SweetAlert2 -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .folder-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .folder-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }
    .dark .folder-card:hover {
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
    }
    .folder-icon-preview {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 28px;
    }
</style>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-zinc-100">📁 Minhas Pastas</h1>
                <p class="text-sm text-gray-600 dark:text-zinc-400 mt-1">
                    Organize seus formulários em pastas
                    <?php if ($isFree): ?>
                        <span class="ml-2 text-xs px-2 py-0.5 rounded" style="background-color: #4EA44B; color: white;">
                            <?= count($folders) ?>/<?= $folderLimit ?> pastas (FREE)
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="flex gap-3">
                <a href="/modules/forms/list.php" 
                   class="px-4 py-2 bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-zinc-300 rounded-lg hover:bg-gray-200 dark:hover:bg-zinc-600 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Voltar para Formulários
                </a>
                <?php if ($canCreateFolder): ?>
                    <button onclick="openCreateFolderModal()" 
                            class="px-4 py-2 text-white rounded-lg transition-colors"
                            style="background-color: #4EA44B;">
                        <i class="fas fa-plus mr-2"></i>Nova Pasta
                    </button>
                <?php else: ?>
                    <button onclick="showUpgradeModal()" 
                            class="px-4 py-2 bg-gray-300 dark:bg-zinc-600 text-gray-500 dark:text-zinc-400 rounded-lg cursor-not-allowed">
                        <i class="fas fa-lock mr-2"></i>Nova Pasta (Limite atingido)
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Grid de Pastas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- Card: Sem Pasta -->
        <a href="/forms/list?folder=none" 
           class="folder-card bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-2 border-dashed border-gray-300 dark:border-zinc-600">
            <div class="flex items-start justify-between mb-4">
                <div class="folder-icon-preview bg-gray-100 dark:bg-zinc-700" style="color: #9ca3af;">
                    <i class="fas fa-inbox"></i>
                </div>
            </div>
            <h3 class="font-semibold text-lg text-gray-900 dark:text-zinc-100 mb-2">Sem Pasta</h3>
            <p class="text-sm text-gray-500 dark:text-zinc-400">
                <?= $formsWithoutFolder ?> <?= $formsWithoutFolder == 1 ? 'formulário' : 'formulários' ?>
            </p>
        </a>

        <!-- Cards de Pastas -->
        <?php foreach ($folders as $folder): ?>
            <div class="folder-card bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border border-gray-200 dark:border-zinc-700"
                 onclick="window.location.href='/forms/list?folder=<?= $folder['id'] ?>'">
                <div class="flex items-start justify-between mb-4">
                    <div class="folder-icon-preview" style="background-color: <?= htmlspecialchars($folder['color']) ?>20; color: <?= htmlspecialchars($folder['color']) ?>;">
                        <i class="fas fa-<?= htmlspecialchars($folder['icon']) ?>"></i>
                    </div>
                    <div class="flex gap-1" onclick="event.stopPropagation();">
                        <button onclick="editFolder(<?= $folder['id'] ?>)" 
                                class="p-2 text-gray-600 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteFolder(<?= $folder['id'] ?>)" 
                                class="p-2 text-gray-600 dark:text-zinc-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                                title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <h3 class="font-semibold text-lg text-gray-900 dark:text-zinc-100 mb-2">
                    <?= htmlspecialchars($folder['name']) ?>
                </h3>
                <p class="text-sm text-gray-500 dark:text-zinc-400 mb-3">
                    <?= $folder['form_count'] ?> <?= $folder['form_count'] == 1 ? 'formulário' : 'formulários' ?>
                </p>
                <?php if ($folder['description']): ?>
                    <p class="text-xs text-gray-600 dark:text-zinc-500 line-clamp-2">
                        <?= htmlspecialchars($folder['description']) ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if (empty($folders)): ?>
            <!-- Estado vazio -->
            <div class="col-span-full flex flex-col items-center justify-center py-16">
                <div class="text-center">
                    <i class="fas fa-folder-open text-6xl text-gray-300 dark:text-zinc-600 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-zinc-100 mb-2">Nenhuma pasta criada</h3>
                    <p class="text-gray-600 dark:text-zinc-400 mb-6">
                        Crie sua primeira pasta para organizar seus formulários
                    </p>
                    <?php if ($canCreateFolder): ?>
                        <button onclick="openCreateFolderModal()" 
                                class="px-6 py-3 text-white rounded-lg transition-colors"
                                style="background-color: #4EA44B;">
                            <i class="fas fa-plus mr-2"></i>Criar Primeira Pasta
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="../../scripts/js/global/theme.js"></script>
<script src="../../scripts/js/global/ui.js"></script>
<script src="../../scripts/js/global/modals.js"></script>
<script src="../../scripts/js/global/helpers.js"></script>

<script>
// Ícones disponíveis
const availableIcons = [
    'folder', 'folder-open', 'briefcase', 'building', 'graduation-cap', 
    'heart', 'star', 'bookmark', 'tag', 'inbox', 'archive', 'box',
    'shopping-cart', 'users', 'user-tie', 'chart-line', 'rocket', 'lightbulb'
];

// Abrir modal de criar pasta
function openCreateFolderModal() {
    const isDark = document.documentElement.classList.contains('dark');
    const inputClass = isDark 
        ? 'w-full rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-zinc-700 border border-zinc-600 text-zinc-100' 
        : 'w-full rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-white border border-gray-300 text-gray-900';
    
    const isFree = <?= PlanService::isFree() ? 'true' : 'false' ?>;
    const disabledClass = isFree ? 'opacity-50 cursor-not-allowed' : '';
    const disabledAttr = isFree ? 'disabled' : '';
    
    const iconsHTML = availableIcons.map(icon => 
        `<div class="icon-option p-3 border-2 border-gray-300 dark:border-zinc-600 rounded-lg ${isFree && icon !== 'folder' ? 'opacity-30 cursor-not-allowed' : 'cursor-pointer hover:border-green-500'} transition-all text-center" 
              data-icon="${icon}" onclick="${isFree && icon !== 'folder' ? 'showProFeature()' : `selectIcon('${icon}')`}">
            <i class="fas fa-${icon} text-2xl text-gray-600 dark:text-zinc-400"></i>
        </div>`
    ).join('');
    
    Swal.fire({
        title: 'Nova Pasta',
        html: `
            <form id="folderForm" class="text-left space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2 ${isDark ? 'text-zinc-100' : 'text-gray-900'}">Nome da Pasta *</label>
                    <input type="text" id="folderName" required placeholder="Ex: Marketing" class="${inputClass}">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2 ${isDark ? 'text-zinc-100' : 'text-gray-900'}">Descrição (opcional)</label>
                    <textarea id="folderDescription" rows="2" placeholder="Descreva o propósito desta pasta..." class="${inputClass}"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2 ${isDark ? 'text-zinc-100' : 'text-gray-900'}">
                        Cor
                        ${isFree ? '<span class="ml-2 text-xs px-2 py-0.5 rounded" style="background-color: #4EA44B; color: white;">✨ PRO</span>' : ''}
                    </label>
                    <input type="color" id="folderColor" value="#4EA44B" 
                           class="w-full h-10 rounded border ${isFree ? 'cursor-not-allowed' : 'cursor-pointer'}"
                           ${disabledAttr}>
                    ${isFree ? '<p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Cor verde padrão (personalize com PRO)</p>' : ''}
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2 ${isDark ? 'text-zinc-100' : 'text-gray-900'}">
                        Ícone
                        ${isFree ? '<span class="ml-2 text-xs px-2 py-0.5 rounded" style="background-color: #4EA44B; color: white;">✨ PRO</span>' : ''}
                    </label>
                    <input type="hidden" id="folderIcon" value="folder">
                    ${isFree ? '<p class="text-xs text-gray-500 dark:text-zinc-400 mb-2">Ícone pasta padrão (personalize com PRO)</p>' : ''}
                    <div class="grid grid-cols-6 gap-2 max-h-48 overflow-y-auto">
                        ${iconsHTML}
                    </div>
                </div>
            </form>
        `,
        width: '600px',
        showCancelButton: true,
        confirmButtonText: 'Criar Pasta',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#4EA44B',
        cancelButtonColor: '#9ca3af',
        reverseButtons: true,
        preConfirm: async () => {
            const name = document.getElementById('folderName').value;
            const description = document.getElementById('folderDescription').value;
            const color = document.getElementById('folderColor').value;
            const icon = document.getElementById('folderIcon').value;
            
            if (!name) {
                Swal.showValidationMessage('Digite o nome da pasta');
                return false;
            }
            
            try {
                const res = await fetch('/modules/folders/create.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `name=${encodeURIComponent(name)}&description=${encodeURIComponent(description)}&color=${encodeURIComponent(color)}&icon=${encodeURIComponent(icon)}`
                });
                
                const result = await res.text();
                
                if (res.ok && result === 'success') {
                    return true;
                } else {
                    throw new Error(result);
                }
            } catch (error) {
                Swal.showValidationMessage(`Erro: ${error.message}`);
                return false;
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Criada!',
                text: 'Pasta criada com sucesso.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.reload();
            });
        }
    });
    
    // Selecionar primeiro ícone por padrão
    setTimeout(() => selectIcon('folder'), 100);
}

// Função showProFeature() agora é global (carregada do pro-features.js)

// Selecionar ícone
function selectIcon(icon) {
    document.querySelectorAll('.icon-option').forEach(el => {
        el.classList.remove('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
        el.classList.add('border-gray-300', 'dark:border-zinc-600');
    });
    
    const selected = document.querySelector(`[data-icon="${icon}"]`);
    if (selected) {
        selected.classList.remove('border-gray-300', 'dark:border-zinc-600');
        selected.classList.add('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
    }
    
    document.getElementById('folderIcon').value = icon;
}

// Editar pasta
async function editFolder(folderId) {
    try {
        const res = await fetch(`/modules/folders/get.php?id=${folderId}`);
        const folder = await res.json();
        
        if (folder.error) {
            Swal.fire('Erro!', folder.error, 'error');
            return;
        }
        
        const isDark = document.documentElement.classList.contains('dark');
        const inputClass = isDark 
            ? 'w-full rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-zinc-700 border border-zinc-600 text-zinc-100' 
            : 'w-full rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-white border border-gray-300 text-gray-900';
        
        const isFree = <?= PlanService::isFree() ? 'true' : 'false' ?>;
        const disabledClass = isFree ? 'opacity-50 cursor-not-allowed' : '';
        const disabledAttr = isFree ? 'disabled' : '';
        
        const iconsHTML = availableIcons.map(icon => 
            `<div class="icon-option p-3 border-2 ${folder.icon === icon ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 dark:border-zinc-600'} rounded-lg ${isFree && icon !== 'folder' ? 'opacity-30 cursor-not-allowed' : 'cursor-pointer hover:border-green-500'} transition-all text-center" 
                  data-icon="${icon}" onclick="${isFree && icon !== 'folder' ? 'showProFeature()' : `selectIcon('${icon}')`}">
                <i class="fas fa-${icon} text-2xl text-gray-600 dark:text-zinc-400"></i>
            </div>`
        ).join('');
        
        Swal.fire({
            title: 'Editar Pasta',
            html: `
                <form id="folderEditForm" class="text-left space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2 ${isDark ? 'text-zinc-100' : 'text-gray-900'}">Nome da Pasta *</label>
                        <input type="text" id="folderName" value="${folder.name}" required placeholder="Ex: Marketing" class="${inputClass}">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2 ${isDark ? 'text-zinc-100' : 'text-gray-900'}">Descrição (opcional)</label>
                        <textarea id="folderDescription" rows="2" placeholder="Descreva o propósito desta pasta..." class="${inputClass}">${folder.description || ''}</textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2 ${isDark ? 'text-zinc-100' : 'text-gray-900'}">
                            Cor
                            ${isFree ? '<span class="ml-2 text-xs px-2 py-0.5 rounded" style="background-color: #4EA44B; color: white;">✨ PRO</span>' : ''}
                        </label>
                        <input type="color" id="folderColor" value="${folder.color}" 
                               class="w-full h-10 rounded border ${isFree ? 'cursor-not-allowed' : 'cursor-pointer'}"
                               ${disabledAttr}>
                        ${isFree ? '<p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Personalize cores com PRO</p>' : ''}
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2 ${isDark ? 'text-zinc-100' : 'text-gray-900'}">
                            Ícone
                            ${isFree ? '<span class="ml-2 text-xs px-2 py-0.5 rounded" style="background-color: #4EA44B; color: white;">✨ PRO</span>' : ''}
                        </label>
                        <input type="hidden" id="folderIcon" value="${folder.icon}">
                        ${isFree ? '<p class="text-xs text-gray-500 dark:text-zinc-400 mb-2">Personalize ícones com PRO</p>' : ''}
                        <div class="grid grid-cols-6 gap-2 max-h-48 overflow-y-auto">
                            ${iconsHTML}
                        </div>
                    </div>
                </form>
            `,
            width: '600px',
            showCancelButton: true,
            confirmButtonText: 'Salvar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#4EA44B',
            cancelButtonColor: '#9ca3af',
            reverseButtons: true,
            preConfirm: async () => {
                const name = document.getElementById('folderName').value;
                const description = document.getElementById('folderDescription').value;
                const color = document.getElementById('folderColor').value;
                const icon = document.getElementById('folderIcon').value;
                
                if (!name) {
                    Swal.showValidationMessage('Digite o nome da pasta');
                    return false;
                }
                
                try {
                    const res = await fetch('/modules/folders/edit.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id=${folderId}&name=${encodeURIComponent(name)}&description=${encodeURIComponent(description)}&color=${encodeURIComponent(color)}&icon=${encodeURIComponent(icon)}`
                    });
                    
                    const result = await res.text();
                    
                    if (res.ok && result === 'success') {
                        return true;
                    } else {
                        throw new Error(result);
                    }
                } catch (error) {
                    Swal.showValidationMessage(`Erro: ${error.message}`);
                    return false;
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Salvo!',
                    text: 'Pasta atualizada com sucesso.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            }
        });
        
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao carregar pasta', 'error');
    }
}

// Excluir pasta
async function deleteFolder(folderId) {
    const result = await Swal.fire({
        title: 'Tem certeza?',
        text: 'Os formulários desta pasta não serão excluídos, apenas ficarão sem pasta.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const res = await fetch(`/modules/folders/delete.php?id=${folderId}`, {
            method: 'POST'
        });
        
        const resultText = await res.text();
        
        if (res.ok && resultText === 'success') {
            await Swal.fire({
                title: 'Excluída!',
                text: 'Pasta excluída com sucesso.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            window.location.reload();
        } else {
            Swal.fire('Erro!', 'Erro ao excluir pasta: ' + resultText, 'error');
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro de conexão', 'error');
    }
}

// Função showUpgradeModal() agora é global (carregada do pro-features.js)
</script>

<?php
require_once __DIR__ . '/../../views/layout/footer.php';
?>