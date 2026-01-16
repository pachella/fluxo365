<?php
session_start();
require_once("../core/db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: /auth/login");
    exit;
}
?>

<h1 class="text-xl md:text-2xl font-bold mb-4 md:mb-6">Usuários</h1>

<div id="messageContainer" class="mb-4 hidden">
    <div id="messageContent" class="px-4 py-3 rounded-lg"></div>
</div>

<!-- Header responsivo -->
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
    <button id="btnNewUser" class="btn btn-primary w-full sm:w-auto">
        <i data-feather="plus" class="w-5 h-5"></i>
        Novo Usuário
    </button>
    <label class="input input-bordered flex items-center gap-2 w-full sm:w-80">
        <i data-feather="search" class="w-5 h-5 opacity-60"></i>
        <input type="text" id="searchUser" placeholder="Buscar usuário..." class="grow" />
        <span id="searchLoading" class="loading loading-spinner loading-sm hidden"></span>
    </label>
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

    const formHTML = `
        <form id="modalUserForm" class="text-left space-y-4">
            <input type="hidden" name="id" value="${isEdit ? userData.id : ''}">

            <div class="space-y-3">
                <label class="input input-bordered flex items-center gap-2">
                    <i data-feather="user" class="w-5 h-5 opacity-60"></i>
                    <input type="text" name="name" placeholder="Nome completo" class="grow"
                           value="${isEdit ? userData.name || '' : ''}" required />
                </label>

                <label class="input input-bordered flex items-center gap-2">
                    <i data-feather="mail" class="w-5 h-5 opacity-60"></i>
                    <input type="email" name="email" placeholder="E-mail" class="grow"
                           value="${isEdit ? userData.email || '' : ''}" required />
                </label>

                <label class="input input-bordered flex items-center gap-2">
                    <i data-feather="shield" class="w-5 h-5 opacity-60"></i>
                    <select name="role" class="grow bg-transparent border-0 outline-none focus:outline-none" style="padding:0;">
                        <option value="admin" ${isEdit && userData.role === 'admin' ? 'selected' : ''}>Administrador</option>
                        <option value="client" ${isEdit && userData.role === 'client' ? 'selected' : ''}>Cliente</option>
                        <option value="affiliate" ${isEdit && userData.role === 'affiliate' ? 'selected' : ''}>Afiliado</option>
                    </select>
                </label>

                <label class="input input-bordered flex items-center gap-2">
                    <i data-feather="activity" class="w-5 h-5 opacity-60"></i>
                    <select name="status" class="grow bg-transparent border-0 outline-none focus:outline-none" style="padding:0;">
                        <option value="active" ${isEdit && userData.status === 'active' ? 'selected' : (!isEdit ? 'selected' : '')}>Ativo</option>
                        <option value="inactive" ${isEdit && userData.status === 'inactive' ? 'selected' : ''}>Inativo</option>
                        <option value="suspended" ${isEdit && userData.status === 'suspended' ? 'selected' : ''}>Suspenso</option>
                    </select>
                </label>

                ${!isEdit || true ? `
                <label class="input input-bordered flex items-center gap-2">
                    <i data-feather="lock" class="w-5 h-5 opacity-60"></i>
                    <input type="password" name="password" placeholder="${isEdit ? 'Nova senha (opcional)' : 'Senha'}" class="grow" ${!isEdit ? 'required' : ''} />
                </label>

                <label class="input input-bordered flex items-center gap-2">
                    <i data-feather="lock" class="w-5 h-5 opacity-60"></i>
                    <input type="password" name="password_confirm" placeholder="Confirmar senha" class="grow" ${!isEdit ? 'required' : ''} />
                </label>
                ` : ''}
            </div>
        </form>
    `;
    
    Swal.fire({
        title: isEdit ? 'Editar Usuário' : 'Novo Usuário',
        html: formHTML,
        width: window.innerWidth < 640 ? '95%' : '600px',
        showCancelButton: true,
        confirmButtonText: 'Salvar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const form = document.getElementById('modalUserForm');
            const formData = new FormData(form);

            const name = formData.get('name').trim();
            const email = formData.get('email').trim();
            const password = formData.get('password').trim();
            const passwordConfirm = formData.get('password_confirm').trim();

            if (!name) {
                Swal.showValidationMessage('Nome é obrigatório');
                return false;
            }

            if (!email || !email.includes('@')) {
                Swal.showValidationMessage('E-mail válido é obrigatório');
                return false;
            }

            if (!isEdit && !password) {
                Swal.showValidationMessage('Senha é obrigatória');
                return false;
            }

            if (password && password !== passwordConfirm) {
                Swal.showValidationMessage('Senhas não conferem');
                return false;
            }

            if (password && password.length < 6) {
                Swal.showValidationMessage('Senha deve ter pelo menos 6 caracteres');
                return false;
            }

            return formData;
        },
        didOpen: () => {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            await saveFormUser(isEdit, result.value);
        }
    });
}

// Salvar usuário
window.saveFormUser = async function(isEdit, formData) {
    Swal.fire({
        title: 'Salvando...',
        didOpen: () => {
            Swal.showLoading();
        },
        allowOutsideClick: false,
        showConfirmButton: false
    });
    
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