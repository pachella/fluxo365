<?php
session_start();
require_once("../core/db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: /auth/login");
    exit;
}
?>

<h1 class="text-xl md:text-2xl font-bold mb-4 md:mb-6 dark:text-zinc-100">Usuários</h1>

<div id="messageContainer" class="mb-4 hidden">
    <div id="messageContent" class="px-4 py-3 rounded-lg"></div>
</div>

<!-- Header responsivo -->
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
    <button id="btnNewUser" class="bg-indigo-600 hover:bg-indigo-700 dark:bg-zinc-600 dark:hover:bg-zinc-500 text-white px-4 py-2 rounded-lg transition-colors text-sm md:text-base w-full sm:w-auto">
        + Novo Usuário
    </button>
    <div class="relative w-full sm:w-64">
        <input type="text" id="searchUser" placeholder="Buscar usuário..."
               class="border rounded-lg px-3 py-2 text-sm w-full focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-100 dark:placeholder-zinc-400 dark:focus:ring-zinc-500">
        <div id="searchLoading" class="hidden absolute right-3 top-2.5">
            <div class="animate-spin h-4 w-4 border-2 border-gray-300 border-t-indigo-600 dark:border-zinc-600 dark:border-t-zinc-400 rounded-full"></div>
        </div>
    </div>
</div>

<div id="users-table">
    <?php include "table.php"; ?>
</div>

<!-- SweetAlert2 Dark Theme -->
<style>
    .dark .swal2-popup { background: #27272a !important; color: #e4e4e7 !important; }
    .dark .swal2-title { color: #e4e4e7 !important; }
    .dark .swal2-html-container { color: #d4d4d8 !important; }
    .dark .swal2-popup input, .dark .swal2-popup textarea, .dark .swal2-popup select { 
        border: 1px solid #52525b !important; 
    }
    .dark .swal2-popup input:focus, .dark .swal2-popup textarea:focus, .dark .swal2-popup select:focus { 
        border-color: #71717a !important; 
        outline: none; 
    }
    
    .swal2-container { backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); }
    .swal2-container.swal2-backdrop-show { background: rgba(0, 0, 0, 0.5) !important; }
    .dark .swal2-container.swal2-backdrop-show { background: rgba(0, 0, 0, 0.7) !important; }
    
    @media (max-width: 640px) {
        .swal2-popup { width: 95% !important; padding: 1rem !important; }
    }
</style>

<!-- Arquivos Globais -->
<script src="../../scripts/js/global/theme.js"></script>
<script src="../../scripts/js/global/ui.js"></script>
<script src="../../scripts/js/global/modals.js"></script>
<script src="../../scripts/js/global/helpers.js"></script>

<script>
// ============================================================================
// FUNÇÕES ESPECÍFICAS DO MÓDULO
// ============================================================================

async function loadTable(searchQuery = '') {
    try {
        const url = searchQuery 
            ? `/modules/users/search.php?q=${encodeURIComponent(searchQuery)}`
            : `/modules/users/table.php`;
        
        const res = await fetch(url);
        const html = await res.text();
        document.getElementById("users-table").innerHTML = html;
    } catch (error) {
        showMessage("Erro ao carregar dados", "error");
    }
}

// ============================================================================
// MODAL DE FORMULÁRIO
// ============================================================================

function showUserFormModal(userData = null) {
    const isEdit = userData !== null;
    const classes = getThemeClasses();
    
    const formHTML = `
        <form id="modalUserForm" class="text-left space-y-4">
            <input type="hidden" name="id" value="${isEdit ? userData.id : ''}">
            
            <div class="border-b ${classes.border} pb-2 mb-4">
                <h3 class="text-sm font-medium ${classes.text}">Dados Pessoais</h3>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <input type="text" name="name" placeholder="Nome completo *"
                       value="${isEdit ? userData.name || '' : ''}"
                       class="w-full rounded-lg px-3 py-2 text-sm ${classes.input}" required>
                
                <input type="email" name="email" placeholder="E-mail *"
                       value="${isEdit ? userData.email || '' : ''}"
                       class="w-full rounded-lg px-3 py-2 text-sm ${classes.input}" required>
            </div>
            
            <div class="border-b ${classes.border} pb-2 mb-4">
                <h3 class="text-sm font-medium ${classes.text}">Configurações</h3>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <select name="role" class="w-full rounded-lg px-3 py-2 text-sm ${classes.select}">
                    <option value="admin" ${isEdit && userData.role === 'admin' ? 'selected' : ''}>Administrador</option>
                    <option value="client" ${isEdit && userData.role === 'client' ? 'selected' : ''}>Cliente</option>
                    <option value="affiliate" ${isEdit && userData.role === 'affiliate' ? 'selected' : ''}>Afiliado</option>
                </select>
                
                <select name="status" class="w-full rounded-lg px-3 py-2 text-sm ${classes.select}">
                    <option value="active" ${isEdit && userData.status === 'active' ? 'selected' : (!isEdit ? 'selected' : '')}>Ativo</option>
                    <option value="inactive" ${isEdit && userData.status === 'inactive' ? 'selected' : ''}>Inativo</option>
                    <option value="suspended" ${isEdit && userData.status === 'suspended' ? 'selected' : ''}>Suspenso</option>
                </select>
            </div>
            
            <div class="border-b ${classes.border} pb-2 mb-4">
                <h3 class="text-sm font-medium ${classes.text}">Senha ${isEdit ? '(deixe vazio para manter atual)' : ''}</h3>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <input type="password" name="password" placeholder="${isEdit ? 'Nova senha (opcional)' : 'Senha *'}"
                       class="w-full rounded-lg px-3 py-2 text-sm ${classes.input}" ${!isEdit ? 'required' : ''}>
                
                <input type="password" name="password_confirm" placeholder="Confirmar senha"
                       class="w-full rounded-lg px-3 py-2 text-sm ${classes.input}" ${!isEdit ? 'required' : ''}>
            </div>
        </form>
    `;
    
    const footerLeft = `
        <button type="button" onclick="saveFormUser(${isEdit})" 
                class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-zinc-600 dark:hover:bg-zinc-500 text-white text-sm font-medium rounded-lg transition-colors">
            Salvar
        </button>
    `;
    
    const footerRight = `
        <button type="button" onclick="Swal.close()" 
                class="text-sm ${classes.textMuted} hover:${classes.text} transition-colors">
            Cancelar
        </button>
    `;
    
    Swal.fire({
        html: createFormModal({
            title: isEdit ? 'Editar Usuário' : 'Novo Usuário',
            content: formHTML,
            footer: {
                left: footerLeft,
                right: footerRight
            }
        }),
        width: window.innerWidth < 640 ? '95%' : '700px',
        showConfirmButton: false,
        showCancelButton: false
    });
}

// Salvar usuário
window.saveFormUser = async function(isEdit) {
    const form = document.getElementById('modalUserForm');
    const formData = new FormData(form);
    
    const name = formData.get('name').trim();
    const email = formData.get('email').trim();
    const password = formData.get('password').trim();
    const passwordConfirm = formData.get('password_confirm').trim();
    
    if (!name) {
        Swal.showValidationMessage('Nome é obrigatório');
        return;
    }
    
    if (!email || !email.includes('@')) {
        Swal.showValidationMessage('E-mail válido é obrigatório');
        return;
    }
    
    if (!isEdit && !password) {
        Swal.showValidationMessage('Senha é obrigatória');
        return;
    }
    
    if (password && password !== passwordConfirm) {
        Swal.showValidationMessage('Senhas não conferem');
        return;
    }
    
    if (password && password.length < 6) {
        Swal.showValidationMessage('Senha deve ter pelo menos 6 caracteres');
        return;
    }
    
    Swal.showLoading();
    
    try {
        const res = await fetch("/modules/users/save.php", {
            method: "POST",
            body: formData
        });
        
        const result = await res.text();
        
        if (res.ok && result === "success") {
            Swal.close();
            showMessage(isEdit ? "Usuário atualizado com sucesso!" : "Usuário cadastrado com sucesso!");
            loadTable();
        } else {
            Swal.fire({ title: 'Erro!', text: "Erro ao salvar: " + result, icon: 'error' });
        }
    } catch (error) {
        Swal.fire({ title: 'Erro!', text: "Erro de conexão", icon: 'error' });
    }
};

// ============================================================================
// EDITAR USUÁRIO
// ============================================================================

window.editUser = async function(id) {
    try {
        const res = await fetch(`/modules/users/edit.php?id=${id}`);
        const data = await res.json();
        
        if (data.error) {
            showMessage(data.error, "error");
            return;
        }
        
        showUserFormModal(data);
        
    } catch (error) {
        showMessage("Erro ao carregar dados do usuário", "error");
    }
};

// ============================================================================
// DELETAR USUÁRIO
// ============================================================================

window.deleteUser = async function(id, userName) {
    const result = await Swal.fire(getConfirmModalConfig(
        'Tem certeza?',
        `Deseja excluir o usuário "${userName}"?`,
        'Sim, excluir!'
    ));
    
    if (!result.isConfirmed) return;
    
    try {
        const res = await fetch(`/modules/users/delete.php?id=${id}`, {
            method: 'POST'
        });
        
        const resultText = await res.text();
        
        if (res.ok && resultText === "success") {
            await Swal.fire({
                title: 'Excluído!',
                text: 'Usuário excluído com sucesso.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            
            loadTable();
        } else {
            Swal.fire('Erro!', "Erro ao excluir: " + resultText, 'error');
        }
    } catch (error) {
        Swal.fire('Erro!', "Erro de conexão", 'error');
    }
};

// ============================================================================
// EVENT LISTENERS
// ============================================================================

document.getElementById("btnNewUser").addEventListener("click", () => {
    showUserFormModal();
});

// Pesquisa dinâmica
let searchTimeout;
document.getElementById("searchUser").addEventListener("input", (e) => {
    clearTimeout(searchTimeout);
    const searchLoading = document.getElementById("searchLoading");
    
    searchTimeout = setTimeout(async () => {
        searchLoading.classList.remove("hidden");
        
        try {
            const q = e.target.value;
            const res = await fetch("/modules/users/search.php?q=" + encodeURIComponent(q));
            const html = await res.text();
            document.getElementById("users-table").innerHTML = html;
        } catch (error) {
            showMessage("Erro na pesquisa", "error");
        } finally {
            searchLoading.classList.add("hidden");
        }
    }, 300);
});
</script>