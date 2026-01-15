<?php
// Buscar dados do banco
require_once("../core/db.php");
require_once("../core/PermissionManager.php");

// Criar instância do PermissionManager
$permissionManager = new PermissionManager(
    $_SESSION['user_role'],
    $_SESSION['user_id'] ?? null
);

// Parâmetros iniciais
$filterForm = $_GET['form_id'] ?? '';
$filterSearch = $_GET['search'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

try {
    // Filtro SQL baseado no role
    $sqlFilter = $permissionManager->getSQLFilter('forms');

    // Buscar estatísticas
    $statsSql = "SELECT
        COUNT(*) as total,
        COUNT(CASE WHEN DATE(fr.created_at) = CURDATE() THEN 1 END) as today,
        COUNT(CASE WHEN DATE(fr.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week
        FROM form_responses fr
        INNER JOIN forms f ON fr.form_id = f.id
        " . str_replace('WHERE', 'WHERE 1=1 AND', $sqlFilter);

    $stats = $pdo->query($statsSql)->fetch(PDO::FETCH_ASSOC);

    // Buscar formulários para o filtro
    $formsSql = "SELECT id, title FROM forms " . $sqlFilter . " ORDER BY title ASC";
    $forms = $pdo->query($formsSql)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $stats = ['total' => 0, 'today' => 0, 'week' => 0];
    $forms = [];
}
?>

<style>
    .stat-card {
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
</style>

<div class="w-full max-w-full overflow-x-hidden">
    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 sm:mb-6 gap-2">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100">
                <i data-feather="users" class="w-6 h-6 inline text-green-600"></i> Meus Leads
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Gerencie todos os leads capturados pelos seus formulários
            </p>
        </div>
        <a href="/modules/leads/export.php?<?= http_build_query(array_diff_key($_GET, ['page' => ''])) ?>"
           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
            <i data-feather="download" class="w-4 h-4"></i> Exportar CSV
        </a>
    </div>

    <!-- Cards de estatísticas -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="stat-card bg-white dark:bg-zinc-800 shadow rounded-lg p-4 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Total de Leads</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?= number_format($stats['total']) ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                    <i data-feather="users" class="w-6 h-6 text-blue-600 dark:text-blue-300"></i>
                </div>
            </div>
        </div>

        <div class="stat-card bg-white dark:bg-zinc-800 shadow rounded-lg p-4 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Novos Hoje</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?= number_format($stats['today']) ?></p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                    <i data-feather="user-plus" class="w-6 h-6 text-green-600 dark:text-green-300"></i>
                </div>
            </div>
        </div>

        <div class="stat-card bg-white dark:bg-zinc-800 shadow rounded-lg p-4 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Últimos 7 Dias</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?= number_format($stats['week']) ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                    <i data-feather="trending-up" class="w-6 h-6 text-purple-600 dark:text-purple-300"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4 mb-6">
        <form id="filterForm" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Formulário</label>
                <select name="form_id" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white text-sm">
                    <option value="">Todos os formulários</option>
                    <?php foreach ($forms as $form): ?>
                        <option value="<?= $form['id'] ?>" <?= $filterForm == $form['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($form['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar</label>
                <input type="text" name="search" value="<?= htmlspecialchars($filterSearch) ?>"
                       placeholder="Nome, email..."
                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Início</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($filterDateFrom) ?>"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Fim</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($filterDateTo) ?>"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg dark:bg-zinc-700 dark:text-white text-sm">
            </div>

            <div class="sm:col-span-2 lg:col-span-4 flex gap-2">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors text-sm">
                    <i data-feather="filter" class="w-4 h-4 inline mr-1"></i> Filtrar
                </button>
                <button type="button" onclick="clearFilters()" class="bg-gray-200 dark:bg-zinc-700 hover:bg-gray-300 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-lg transition-colors text-sm">
                    <i data-feather="x" class="w-4 h-4 inline mr-1"></i> Limpar
                </button>
            </div>
        </form>
    </div>

    <!-- Tabela de Leads -->
    <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
            <i data-feather="list" class="w-5 h-5 mr-2"></i>
            Todos os Leads
        </h3>
        <div id="leads-table">
            <?php include "table.php"; ?>
        </div>
    </div>
</div>


<script>
let currentFilters = {
    form_id: '<?= $filterForm ?>',
    search: '<?= $filterSearch ?>',
    date_from: '<?= $filterDateFrom ?>',
    date_to: '<?= $filterDateTo ?>'
};

let currentLeadWhatsApp = '';

// Carregar tabela via AJAX
async function loadLeadsTable(page = 1) {
    try {
        const params = new URLSearchParams({
            ...currentFilters,
            p: page
        });

        const res = await fetch(`/modules/leads/table.php?${params}`);
        const html = await res.text();
        document.getElementById("leads-table").innerHTML = html;

        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    } catch (error) {
        console.error('Erro ao carregar tabela:', error);
    }
}

// Limpar filtros
function clearFilters() {
    document.querySelector('[name="form_id"]').value = '';
    document.querySelector('[name="search"]').value = '';
    document.querySelector('[name="date_from"]').value = '';
    document.querySelector('[name="date_to"]').value = '';

    currentFilters = {
        form_id: '',
        search: '',
        date_from: '',
        date_to: ''
    };

    loadLeadsTable(1);
}

// Atualizar filtros quando o formulário for submetido
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();

            currentFilters = {
                form_id: document.querySelector('[name="form_id"]').value,
                search: document.querySelector('[name="search"]').value,
                date_from: document.querySelector('[name="date_from"]').value,
                date_to: document.querySelector('[name="date_to"]').value
            };

            loadLeadsTable(1);
        });
    }
});

// Ver detalhes do lead
async function viewLeadDetails(leadId) {
    try {
        const res = await fetch(`/modules/leads/get_lead.php?id=${leadId}`);
        const data = await res.json();

        if (data.error) {
            Swal.fire({
                title: 'Erro!',
                text: data.error,
                icon: 'error'
            });
            return;
        }

        const lead = data.lead;
        showLeadModal(lead);

    } catch (error) {
        console.error('Erro ao carregar lead:', error);
        Swal.fire({
            title: 'Erro!',
            text: 'Erro ao carregar detalhes do lead',
            icon: 'error'
        });
    }
}

// Modal de Detalhes do Lead
function showLeadModal(lead) {
    const isDark = document.documentElement.classList.contains('dark');
    const classes = getThemeClasses();

    // Construir seções condicionais
    let emailSection = '';
    if (lead.email) {
        emailSection = `
            <div>
                <label class="block text-sm font-medium ${classes.text} mb-1">
                    <i data-feather="mail" class="w-4 h-4 inline mr-1"></i> Email
                </label>
                <div class="${classes.bg} rounded-lg p-3">
                    <p class="${classes.title}">${lead.email}</p>
                </div>
            </div>
        `;
    }

    let whatsappSection = '';
    let whatsappButton = '';
    if (lead.whatsapp) {
        whatsappSection = `
            <div>
                <label class="block text-sm font-medium ${classes.text} mb-1">
                    <i data-feather="phone" class="w-4 h-4 inline mr-1"></i> WhatsApp
                </label>
                <div class="${classes.bg} rounded-lg p-3">
                    <p class="${classes.title}">${lead.whatsapp}</p>
                </div>
            </div>
        `;

        const message = encodeURIComponent('Olá! Vi sua resposta no formulário e gostaria de conversar.');
        whatsappButton = `
            <a href="https://wa.me/${lead.whatsapp}?text=${message}" target="_blank"
               class="w-full inline-block bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors text-sm text-center">
                <i data-feather="message-circle" class="w-4 h-4 inline mr-1"></i>
                Chamar no WhatsApp
            </a>
        `;
    }

    let scoreSection = '';
    if (lead.score && lead.score > 0) {
        scoreSection = `
            <div>
                <span class="${classes.textMuted}">Pontuação:</span>
                <span class="${classes.title} ml-2">${lead.score}</span>
            </div>
        `;
    }

    // Seção de Observações
    const notesSection = `
        <div>
            <label class="block text-sm font-medium ${classes.text} mb-1">
                <i data-feather="edit-3" class="w-4 h-4 inline mr-1"></i> Observações
            </label>
            <textarea id="leadNotes" rows="4"
                      placeholder="Adicione observações sobre este lead..."
                      class="w-full px-3 py-2 border ${isDark ? 'border-zinc-600 bg-zinc-700 text-zinc-100 placeholder-zinc-400' : 'border-gray-300 bg-white text-gray-900 placeholder-gray-400'} rounded-lg focus:ring-2 focus:ring-green-500 text-sm resize-none">${lead.notes || ''}</textarea>
            ${lead.notes_updated_at ? `<p class="text-xs ${classes.textMuted} mt-1">Última atualização: ${lead.notes_updated_at}</p>` : ''}
            <button onclick="saveLeadNotes(${lead.id})"
                    class="mt-2 w-full bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg transition-colors text-sm">
                <i data-feather="save" class="w-4 h-4 inline mr-1"></i>
                Salvar Observações
            </button>
        </div>
    `;

    const leadContent = `
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Coluna Principal -->
            <div class="lg:col-span-2 space-y-4">
                ${emailSection}
                ${whatsappSection}
                ${notesSection}
            </div>

            <!-- Sidebar Direita -->
            <div class="space-y-4">
                <!-- Informações -->
                <div class="${classes.bg} rounded-lg p-4">
                    <h3 class="text-sm font-semibold ${classes.title} mb-3">
                        <i data-feather="info" class="w-4 h-4 inline mr-1"></i> Informações
                    </h3>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="${classes.textMuted}">ID:</span>
                            <span class="${classes.title} font-medium ml-2">#${lead.id}</span>
                        </div>
                        <div>
                            <span class="${classes.textMuted}">Formulário:</span>
                            <span class="${classes.title} ml-2">${lead.form_title}</span>
                        </div>
                        <div>
                            <span class="${classes.textMuted}">Data:</span>
                            <span class="${classes.title} ml-2">${lead.created_at}</span>
                        </div>
                        ${scoreSection}
                    </div>
                </div>

                <!-- Ações -->
                <div class="${classes.bg} rounded-lg p-4">
                    <h3 class="text-sm font-semibold ${classes.title} mb-3">
                        <i data-feather="zap" class="w-4 h-4 inline mr-1"></i> Ações
                    </h3>
                    <div class="space-y-2">
                        ${whatsappButton}
                        <a href="/forms/${lead.form_id}/responses/${lead.id}"
                           class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors text-sm text-center">
                            <i data-feather="file-text" class="w-4 h-4 inline mr-1"></i>
                            Ver Resposta Completa
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;

    const footerRight = `
        <button type="button" onclick="Swal.close()"
                class="text-sm ${isDark ? 'text-zinc-400 hover:text-zinc-100' : 'text-gray-600 hover:text-gray-900'} transition-colors">
            Fechar
        </button>
    `;

    Swal.fire({
        html: createFormModal({
            title: `<i data-feather="user" class="w-5 h-5 inline mr-2 text-green-600"></i> ${lead.name}`,
            content: leadContent,
            footer: {
                left: '',
                right: footerRight
            }
        }),
        width: window.innerWidth < 1024 ? '95%' : '900px',
        showConfirmButton: false,
        showCancelButton: false,
        didOpen: () => {
            // Reinicializar ícones feather
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }
    });
}

// Salvar observações do lead
async function saveLeadNotes(leadId) {
    const notes = document.getElementById('leadNotes').value;

    try {
        const formData = new FormData();
        formData.append('lead_id', leadId);
        formData.append('notes', notes);

        const res = await fetch('/modules/leads/save_notes.php', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();

        if (data.success) {
            Swal.fire({
                title: 'Sucesso!',
                text: 'Observações salvas com sucesso',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            throw new Error(data.error || 'Erro ao salvar observações');
        }
    } catch (error) {
        console.error('Erro ao salvar observações:', error);
        Swal.fire({
            title: 'Erro!',
            text: error.message || 'Erro ao salvar observações',
            icon: 'error'
        });
    }
}

// Excluir lead
async function deleteLead(leadId) {
    const result = await Swal.fire({
        title: 'Tem certeza?',
        text: 'Deseja realmente excluir este lead? Esta ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    });

    if (!result.isConfirmed) return;

    try {
        const formData = new FormData();
        formData.append('id', leadId);

        const res = await fetch('/modules/leads/delete.php', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();

        if (data.success) {
            await Swal.fire({
                title: 'Excluído!',
                text: 'Lead excluído com sucesso.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            // Recarregar tabela
            loadLeadsTable(1);
        } else {
            throw new Error(data.error || 'Erro ao excluir lead');
        }
    } catch (error) {
        console.error('Erro ao excluir lead:', error);
        Swal.fire({
            title: 'Erro!',
            text: error.message || 'Erro ao excluir lead',
            icon: 'error'
        });
    }
}
</script>
