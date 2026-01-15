<?php
session_start();
require_once(__DIR__ . "/../../core/db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: /auth/login");
    exit;
}
?>

<h1 class="text-xl md:text-2xl font-bold mb-4 md:mb-6 dark:text-zinc-100">Templates de E-mail</h1>

<!-- Mensagens -->
<div id="messageContainer" class="mb-4 hidden">
    <div id="messageContent" class="px-4 py-3 rounded-lg"></div>
</div>

<div class="mb-6">
    <p class="text-sm md:text-base text-gray-600 dark:text-zinc-400">Gerencie os templates de e-mail que são enviados automaticamente pelo sistema.</p>
</div>

<!-- Tabela -->
<div id="emails-table">
    <?php include "table.php"; ?>
</div>

<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<!-- SweetAlert2 Dark Theme -->
<style>
    /* Quill Editor */
    .ql-editor p:not(:last-child) { margin-bottom: 15px; }
    
    /* Dark Theme - Quill */
    .dark .ql-toolbar.ql-snow { background: #27272a; border-color: #3f3f46; }
    .dark .ql-container.ql-snow { background: #18181b; border-color: #3f3f46; }
    .dark .ql-editor { color: #e4e4e7; }
    .dark .ql-editor.ql-blank::before { color: #71717a; }
    .dark .ql-snow .ql-stroke { stroke: #a1a1aa; }
    .dark .ql-snow .ql-fill { fill: #a1a1aa; }
    .dark .ql-snow .ql-picker-label { color: #e4e4e7; }
    .dark .ql-snow .ql-picker-options { background: #27272a; border-color: #3f3f46; }
    .dark .ql-snow .ql-picker-item { color: #e4e4e7; }
    .dark .ql-snow .ql-picker-item:hover { background: #3f3f46; }
    .dark .ql-toolbar button:hover, .dark .ql-toolbar button:focus, .dark .ql-toolbar button.ql-active { color: #ffffff; }
    .dark .ql-toolbar button:hover .ql-stroke, .dark .ql-toolbar button:focus .ql-stroke, .dark .ql-toolbar button.ql-active .ql-stroke { stroke: #ffffff; }
    .dark .ql-toolbar button:hover .ql-fill, .dark .ql-toolbar button:focus .ql-fill, .dark .ql-toolbar button.ql-active .ql-fill { fill: #ffffff; }
    
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

<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<!-- Arquivos Globais -->
<script src="../../scripts/js/global/theme.js"></script>
<script src="../../scripts/js/global/ui.js"></script>
<script src="../../scripts/js/global/modals.js"></script>
<script src="../../scripts/js/global/helpers.js"></script>

<script>
// ============================================================================
// CONFIGURAÇÃO
// ============================================================================
let quillEditorEmail = null;

// ============================================================================
// FUNÇÕES ESPECÍFICAS DO MÓDULO
// ============================================================================

async function loadTable() {
    try {
        const res = await fetch(`/modules/emails/table.php`);
        const html = await res.text();
        document.getElementById("emails-table").innerHTML = html;
    } catch (error) {
        showMessage("Erro ao carregar dados", "error");
    }
}

// ============================================================================
// MODAL DE FORMULÁRIO
// ============================================================================

function showEmailFormModal(templateData = null) {
    const isEdit = templateData !== null;
    const classes = getThemeClasses();
    
    const formHTML = `
        <form id="modalEmailForm" class="text-left space-y-4">
            <input type="hidden" name="id" value="${isEdit ? (templateData.id ?? '') : ''}">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs ${classes.textMuted} mb-1">Nome do Template</label>
                    <input type="text" name="name" 
                           value="${isEdit ? (templateData.name ?? '') : ''}"
                           class="w-full rounded-lg px-3 py-2 text-sm bg-gray-100 dark:bg-zinc-700 ${classes.textMuted}" readonly>
                </div>
                
                <div>
                    <label class="block text-xs ${classes.textMuted} mb-1">Categoria</label>
                    <input type="text" name="category" 
                           value="${isEdit ? (templateData.category ?? '') : ''}"
                           class="w-full rounded-lg px-3 py-2 text-sm bg-gray-100 dark:bg-zinc-700 ${classes.textMuted}" readonly>
                </div>
            </div>
            
            <div>
                <input type="text" name="subject" placeholder="Assunto do e-mail *"
                       value="${isEdit ? (templateData.subject ?? '') : ''}"
                       class="w-full rounded-lg px-3 py-2 text-sm ${classes.input}" required>
            </div>
            
            <div>
                <label class="block text-xs ${classes.textMuted} mb-1">Conteúdo do E-mail</label>
                <div id="quillEditorEmail" style="height: ${window.innerWidth < 640 ? '250px' : '350px'};"></div>
                <input type="hidden" name="body" id="emailBody">
            </div>
            
            <div class="flex items-center pt-2">
                <input type="checkbox" name="active" value="1" id="emailActive"
                       ${isEdit && Number(templateData.active) === 1 ? 'checked' : ''}
                       class="h-4 w-4 ${classes.checkbox} rounded">
                <label for="emailActive" class="ml-2 block text-sm ${classes.text}">Template ativo</label>
            </div>
        </form>
    `;
    
    const footerLeft = `
        <button type="button" onclick="saveFormEmail()" 
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
            title: 'Editar Template de E-mail',
            content: formHTML,
            footer: {
                left: footerLeft,
                right: footerRight
            }
        }),
        width: window.innerWidth < 640 ? '95%' : '750px',
        showConfirmButton: false,
        showCancelButton: false,
        didOpen: () => {
            // Inicializar Quill
            quillEditorEmail = new Quill('#quillEditorEmail', {
                theme: 'snow',
                placeholder: 'Escreva o conteúdo do e-mail aqui...',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'align': [] }],
                        ['link'],
                        ['clean']
                    ]
                }
            });
            
            // Carregar conteúdo existente
            if (isEdit && templateData.body) {
                quillEditorEmail.root.innerHTML = templateData.body;
            }
        }
    }).then(() => {
        quillEditorEmail = null;
    });
}

// Salvar template
window.saveFormEmail = async function() {
    const form = document.getElementById('modalEmailForm');
    const formData = new FormData(form);
    
    // Pegar conteúdo do Quill
    const quillContent = quillEditorEmail.root.innerHTML;
    formData.set('body', quillContent);
    
    const subject = formData.get('subject').trim();
    
    if (!subject) {
        Swal.showValidationMessage('Assunto é obrigatório');
        return;
    }
    
    if (!quillContent.trim() || quillContent === '<p><br></p>') {
        Swal.showValidationMessage('Conteúdo do e-mail é obrigatório');
        return;
    }
    
    Swal.showLoading();
    
    try {
        const res = await fetch("/modules/emails/save.php", {
            method: "POST",
            body: formData
        });
        
        const result = await res.text();
        
        if (res.ok && result === "success") {
            Swal.close();
            showMessage("Template atualizado com sucesso!");
            loadTable();
        } else {
            Swal.fire({ title: 'Erro!', text: "Erro: " + result, icon: 'error' });
        }
    } catch (error) {
        Swal.fire({ title: 'Erro!', text: "Erro de conexão", icon: 'error' });
    }
};

// ============================================================================
// EDITAR TEMPLATE
// ============================================================================

window.editEmail = async function(id) {
    try {
        const res = await fetch(`/modules/emails/edit.php?id=${id}`);
        const data = await res.json();
        
        if (data.error) {
            showMessage(data.error, "error");
            return;
        }
        
        showEmailFormModal(data);
    } catch (error) {
        showMessage("Erro ao carregar dados", "error");
    }
};

// ============================================================================
// ATIVAR/DESATIVAR TEMPLATE
// ============================================================================

window.toggleStatus = async function(id, newStatus, templateName) {
    const action = newStatus ? 'ativar' : 'desativar';
    
    const result = await Swal.fire({
        title: 'Confirmar ação',
        text: `Deseja ${action} o template "${templateName}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: newStatus ? '#10B981' : '#F59E0B',
        cancelButtonColor: '#6B7280',
        confirmButtonText: `Sim, ${action}`,
        cancelButtonText: 'Cancelar'
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const res = await fetch('/modules/emails/toggle-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}&status=${newStatus}`
        });
        
        const resultText = await res.text();
        
        if (res.ok && resultText === "success") {
            showMessage(`Template ${action === 'ativar' ? 'ativado' : 'desativado'} com sucesso!`);
            loadTable();
        } else {
            showMessage("Erro ao alterar status: " + resultText, "error");
        }
    } catch (error) {
        showMessage("Erro de conexão", "error");
    }
};
</script>