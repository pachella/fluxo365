<?php
session_start();
require_once(__DIR__ . "/../../core/db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: /auth/login");
    exit;
}

// Pegar filtro de pasta da URL
$folderFilter = $_GET['folder'] ?? null;

// Incluir layout
require_once(__DIR__ . "/../../views/layout/header.php");
require_once(__DIR__ . "/../../views/layout/sidebar.php");
?>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div id="messageContainer" class="mb-4 hidden">
    <div id="messageContent" class="px-4 py-3 rounded-lg"></div>
</div>

<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
    <h1 class="text-xl md:text-2xl font-bold dark:text-zinc-100">Meus Formulários</h1>
    <div class="relative w-full sm:w-64">
        <input type="text" id="searchForm" placeholder="Buscar formulário..."
               class="border rounded-lg px-3 py-2 text-sm w-full focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-100 dark:placeholder-zinc-400 dark:focus:ring-zinc-500">
        <div id="searchLoading" class="hidden absolute right-3 top-2.5">
            <div class="animate-spin h-4 w-4 border-2 border-gray-300 border-t-indigo-600 dark:border-zinc-600 dark:border-t-zinc-400 rounded-full"></div>
        </div>
    </div>
</div>

<div id="forms-table">
    <?php include "table.php"; ?>
</div>

<script>
// Armazenar filtro atual
const currentFolder = <?= json_encode($folderFilter) ?>;

async function loadTable(searchQuery = '') {
    try {
        let url;
        const folderParam = currentFolder ? `folder=${currentFolder}` : '';
        
        if (searchQuery) {
            url = `/modules/forms/search.php?q=${encodeURIComponent(searchQuery)}${folderParam ? '&' + folderParam : ''}`;
        } else {
            url = `/modules/forms/table.php${folderParam ? '?' + folderParam : ''}`;
        }
        
        const res = await fetch(url);
        const html = await res.text();
        document.getElementById("forms-table").innerHTML = html;
    } catch (error) {
        showMessage("Erro ao carregar dados", "error");
    }
}

window.editForm = async function(id) {
    try {
        const res = await fetch(`/modules/forms/edit.php?id=${id}`);
        const data = await res.json();
        
        if (data.error) {
            showMessage(data.error, "error");
            return;
        }
        
        showFormModal(data);
        
    } catch (error) {
        showMessage("Erro ao carregar dados do formulário", "error");
    }
};

window.openBuilder = function(id) {
    window.location.href = `/forms/builder/${id}`;
};

window.viewResponses = function(id) {
    window.location.href = `/forms/${id}/responses`;
};

window.viewForm = function(id) {
    window.open(`/f/${id}`, '_blank');
};

window.deleteForm = async function(id, title) {
    const result = await Swal.fire(getConfirmModalConfig(
        'Tem certeza?',
        `Deseja excluir o formulário "${title}"? Todas as respostas também serão excluídas. Essa ação não pode ser desfeita.`,
        'Sim, excluir!'
    ));
    
    if (!result.isConfirmed) return;
    
    try {
        const res = await fetch(`/modules/forms/delete.php?id=${id}`, {
            method: 'POST'
        });
        
        const resultText = await res.text();
        
        if (res.ok && resultText === "success") {
            await Swal.fire({
                title: 'Excluído!',
                text: 'Formulário excluído com sucesso.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            
            loadTable(document.getElementById("searchForm").value);
        } else {
            Swal.fire({
                title: 'Erro!',
                text: "Erro ao excluir formulário: " + resultText,
                icon: 'error'
            });
        }
    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            text: "Erro de conexão. Tente novamente.",
            icon: 'error'
        });
    }
};

window.duplicateForm = async function(id, title) {
    const result = await Swal.fire(getConfirmModalConfig(
        'Duplicar formulário?',
        `Deseja criar uma cópia do formulário "${title}"? A cópia será criada como rascunho.`,
        'Sim, duplicar!'
    ));

    if (!result.isConfirmed) return;

    try {
        const res = await fetch(`/modules/forms/duplicate.php?id=${id}`, {
            method: 'POST'
        });

        const resultText = await res.text();

        if (res.ok && resultText.startsWith("success:")) {
            const newFormId = resultText.split(':')[1];

            await Swal.fire({
                title: 'Duplicado!',
                text: 'Formulário duplicado com sucesso.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            loadTable(document.getElementById("searchForm").value);
        } else {
            Swal.fire({
                title: 'Erro!',
                text: "Erro ao duplicar formulário: " + resultText,
                icon: 'error'
            });
        }
    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            text: "Erro de conexão. Tente novamente.",
            icon: 'error'
        });
    }
};

// Mover formulário para pasta
window.moveToFolder = async function(formId, formTitle) {
    try {
        // Buscar pastas do usuário
        const res = await fetch('/modules/folders/list_api.php');
        const folders = await res.json();
        
        if (folders.error) {
            Swal.fire('Erro!', folders.error, 'error');
            return;
        }
        
        const isDark = document.documentElement.classList.contains('dark');
        
        // Criar options do select
        let optionsHTML = '<option value="">📥 Sem pasta</option>';
        folders.forEach(folder => {
            optionsHTML += `<option value="${folder.id}">
                ${folder.icon ? '📁' : ''} ${folder.name}
            </option>`;
        });
        
        const result = await Swal.fire({
            title: 'Mover formulário',
            html: `
                <div class="text-left">
                    <p class="mb-4 text-sm ${isDark ? 'text-zinc-300' : 'text-gray-700'}">
                        Mover "<strong>${formTitle}</strong>" para qual pasta?
                    </p>
                    <select id="selectFolder" class="w-full rounded-lg px-3 py-2 text-sm border ${isDark ? 'bg-zinc-700 border-zinc-600 text-zinc-100' : 'bg-white border-gray-300 text-gray-900'} focus:outline-none focus:ring-2 focus:ring-green-500">
                        ${optionsHTML}
                    </select>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Mover',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#4EA44B',
            cancelButtonColor: '#9ca3af',
            reverseButtons: true,
            preConfirm: async () => {
                const folderId = document.getElementById('selectFolder').value;
                
                try {
                    const moveRes = await fetch('/modules/forms/move_folder.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `form_id=${formId}&folder_id=${folderId}`
                    });
                    
                    const result = await moveRes.text();
                    
                    if (moveRes.ok && result === 'success') {
                        return true;
                    } else {
                        throw new Error(result);
                    }
                } catch (error) {
                    Swal.showValidationMessage(`Erro: ${error.message}`);
                    return false;
                }
            }
        });
        
        if (result.isConfirmed) {
            await Swal.fire({
                title: 'Movido!',
                text: 'Formulário movido com sucesso.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            
            loadTable(document.getElementById("searchForm").value);
        }
        
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao carregar pastas', 'error');
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const btnNewForm = document.getElementById("btnNewForm");
    if (btnNewForm) {
        btnNewForm.addEventListener("click", () => {
            showFormModal();
        });
    }

    const searchForm = document.getElementById("searchForm");
    if (searchForm) {
        let searchTimeout;
        searchForm.addEventListener("input", (e) => {
            clearTimeout(searchTimeout);
            const searchLoading = document.getElementById("searchLoading");
            
            searchTimeout = setTimeout(async () => {
                searchLoading.classList.remove("hidden");
                
                try {
                    const q = e.target.value;
                    const folderParam = currentFolder ? `&folder=${currentFolder}` : '';
                    const res = await fetch(`/modules/forms/search.php?q=${encodeURIComponent(q)}${folderParam}`);
                    const html = await res.text();
                    document.getElementById("forms-table").innerHTML = html;
                } catch (error) {
                    showMessage("Erro na pesquisa", "error");
                } finally {
                    searchLoading.classList.add("hidden");
                }
            }, 300);
        });
    }
});
</script>

<?php
// Incluir footer
require_once(__DIR__ . "/../../views/layout/footer.php");
?>