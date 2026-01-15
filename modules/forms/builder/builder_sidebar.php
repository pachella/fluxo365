<?php
// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login");
    exit;
}

// Verificar se temos o ID do formulário
$formId = $_GET['id'] ?? null;
if (!$formId) {
    header("Location: /modules/forms/list.php");
    exit;
}

// Definir página atual
$currentPath = $_SERVER['REQUEST_URI'];
$currentFile = basename($_SERVER['PHP_SELF']);

function isActiveBuilder($page, $currentFile) {
    return $currentFile === $page
        ? 'bg-gray-100 dark:bg-zinc-700 text-gray-900 dark:text-zinc-100'
        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 hover:text-gray-900 dark:hover:text-zinc-100';
}
?>

<!-- Overlay (mobile) -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed lg:static inset-y-0 left-0 transform -translate-x-full lg:translate-x-0 w-64 bg-white dark:bg-zinc-800 shadow-lg min-h-screen transition-transform duration-300 ease-in-out z-50 flex flex-col">
  <!-- Header da sidebar (mobile) -->
  <div class="lg:hidden flex items-center justify-between p-4 border-b border-gray-200 dark:border-zinc-700">
    <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200">Editor de Formulário</h2>
    <button onclick="closeSidebar()" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-zinc-700 transition-colors">
      <i data-feather="x" class="w-5 h-5 text-gray-600 dark:text-gray-400"></i>
    </button>
  </div>

  <!-- Header da sidebar (desktop) -->
  <div class="hidden lg:block p-6 border-b border-gray-200 dark:border-zinc-700">
    <a href="/forms/list" class="text-sm text-gray-600 dark:text-gray-400 hover:underline inline-flex items-center">
      <i data-feather="arrow-left" class="w-4 h-4 mr-1"></i> Voltar para formulários
    </a>
  </div>

  <nav class="flex-1 p-4 overflow-y-auto">
    <ul class="space-y-2">
      
      <!-- Editar Perguntas -->
      <li>
        <a href="/modules/forms/builder/?id=<?= $formId ?>" 
           class="flex items-center px-3 py-2 rounded-md transition-colors <?= isActiveBuilder('index.php', $currentFile) ?>">
          <i data-feather="edit-3" class="w-5 h-5 mr-2"></i> Editar Perguntas
        </a>
      </li>
      
      <!-- Ver Respostas -->
      <li>
        <a href="/forms/<?= $formId ?>/responses" 
           class="flex items-center px-3 py-2 rounded-md transition-colors <?= isActiveBuilder('list.php', $currentFile) ?>">
          <i data-feather="inbox" class="w-5 h-5 mr-2"></i> Ver Respostas
        </a>
      </li>
      
      <!-- Visualizar Formulário -->
      <li>
        <a href="<?php
             // Carregar config se não estiver carregado
             if (!function_exists('getPublicFormUrl')) {
                 require_once(__DIR__ . '/../../../core/config.php');
             }
             echo getPublicFormUrl($formId);
           ?>"
           target="_blank"
           class="flex items-center px-3 py-2 rounded-md transition-colors text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 hover:text-gray-900 dark:hover:text-zinc-100">
          <i data-feather="eye" class="w-5 h-5 mr-2"></i> Visualizar Formulário
        </a>
      </li>
      
      <!-- Configurações -->
      <li>
        <button onclick="openFormSettings(<?= $formId ?>)" 
           class="w-full flex items-center px-3 py-2 rounded-md transition-colors text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 hover:text-gray-900 dark:hover:text-zinc-100">
          <i data-feather="settings" class="w-5 h-5 mr-2"></i> Configurações
        </button>
      </li>
      
      <!-- Personalização -->
      <li>
        <button onclick="openCustomization(<?= $formId ?>)" 
           class="w-full flex items-center px-3 py-2 rounded-md transition-colors text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 hover:text-gray-900 dark:hover:text-zinc-100">
          <i data-feather="sliders" class="w-5 h-5 mr-2"></i> Personalização
        </button>
      </li>
      
      <!-- Opções de envio -->
      <li>
        <button onclick="openSendOptions(<?= $formId ?>)"
           class="w-full flex items-center px-3 py-2 rounded-md transition-colors text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 hover:text-gray-900 dark:hover:text-zinc-100">
          <i data-feather="send" class="w-5 h-5 mr-2"></i> Opções de envio
        </button>
      </li>

      <!-- Integrações -->
      <li>
        <button onclick="openIntegrations(<?= $formId ?>)"
           class="w-full flex items-center px-3 py-2 rounded-md transition-colors text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 hover:text-gray-900 dark:hover:text-zinc-100">
          <i data-feather="link" class="w-5 h-5 mr-2"></i> Integrações
        </button>
      </li>

      <!-- Divisor -->
      <li class="border-t border-gray-200 dark:border-zinc-700 my-3"></li>
      
      <!-- Excluir Formulário -->
      <li>
        <button onclick="deleteForm(<?= $formId ?>)"
           class="w-full flex items-center justify-center px-3 py-2.5 rounded-md transition-colors text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 hover:bg-red-100 dark:hover:bg-red-900/30">
          <i data-feather="trash-2" class="w-5 h-5 mr-2"></i> Excluir Formulário
        </button>
      </li>
      
    </ul>
  </nav>
  
  <!-- Perfil do usuário -->
  <div class="p-4 border-t border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 transition-colors duration-200">
    <div class="flex items-center space-x-3">
      <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold" style="background-color: #4EA44B;">
        <?= strtoupper(substr($_SESSION["user_name"] ?? 'U', 0, 1)) ?>
      </div>
      <div>
        <p class="text-sm font-medium text-gray-800 dark:text-gray-200"><?= htmlspecialchars($_SESSION["user_name"] ?? 'Usuário') ?></p>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          <?php 
          if (isset($_SESSION['user_role'])) {
              echo $_SESSION['user_role'] === 'admin' ? 'Administrador' : 'Cliente';
          } else {
              echo 'Usuário';
          }
          ?>
        </p>
        <a href="/auth/logout.php" class="text-xs text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">Sair</a>
      </div>
    </div>
  </div>
</aside>

<script>
// Abrir sidebar (mobile)
function openSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    sidebar.classList.remove('-translate-x-full');
    overlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

// Fechar sidebar (mobile)
function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
    document.body.style.overflow = '';
}

// Fechar sidebar ao clicar em links (mobile)
document.addEventListener('DOMContentLoaded', function() {
    const sidebarLinks = document.querySelectorAll('#sidebar a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 1024) {
                closeSidebar();
            }
        });
    });
});

// Função para excluir formulário
async function deleteForm(formId) {
    const result = await Swal.fire({
        title: 'Tem certeza?',
        text: 'Deseja realmente excluir este formulário? Esta ação não pode ser desfeita e todas as respostas serão perdidas.',
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
        const res = await fetch(`/modules/forms/delete.php?id=${formId}`, {
            method: 'POST'
        });
        
        const resultText = await res.text();
        
        if (res.ok && resultText === 'success') {
            await Swal.fire({
                title: 'Excluído!',
                text: 'Formulário excluído com sucesso.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            window.location.href = '/modules/forms/list.php';
        } else {
            Swal.fire({
                title: 'Erro!',
                text: 'Erro ao excluir formulário: ' + resultText,
                icon: 'error'
            });
        }
    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            text: 'Erro de conexão',
            icon: 'error'
        });
    }
}

// Função para abrir configurações do formulário
async function openFormSettings(formId) {
    try {
        const res = await fetch(`/modules/forms/edit.php?id=${formId}`);
        const formData = await res.json();

        if (formData.error) {
            Swal.fire({
                title: 'Erro!',
                text: formData.error,
                icon: 'error'
            });
            return;
        }

        showFormSettingsModal(formId, formData);

    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            text: 'Erro ao carregar configurações',
            icon: 'error'
        });
    }
}

// Modal de Configurações do Formulário
function showFormSettingsModal(formId, formData) {
    const isDark = document.documentElement.classList.contains('dark');
    const inputClass = isDark
        ? 'w-full px-4 py-2 text-sm border border-zinc-600 rounded-md focus:ring-2 focus:ring-green-500 bg-zinc-700 text-zinc-100'
        : 'w-full px-4 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 bg-white text-gray-900';
    const labelClass = isDark ? 'block text-sm font-medium mb-2 text-zinc-300' : 'block text-sm font-medium mb-2 text-gray-700';

    const settingsHTML = `
        <form id="settingsForm" class="text-left">
            <input type="hidden" name="form_id" value="${formId}">

            <!-- TABS -->
            <div class="flex border-b ${isDark ? 'border-zinc-700' : 'border-gray-200'} mb-6">
                <button type="button" onclick="switchSettingsTab('geral')" id="tabGeral"
                        class="px-4 py-2 font-medium text-sm border-b-2 transition-colors focus:outline-none"
                        style="border-color: #4EA44B; color: ${isDark ? '#fff' : '#000'}">
                    Geral
                </button>
                <button type="button" onclick="switchSettingsTab('configuracoes')" id="tabConfiguracoes"
                        class="px-4 py-2 font-medium text-sm border-b-2 border-transparent transition-colors focus:outline-none ${isDark ? 'text-zinc-400 hover:text-zinc-200' : 'text-gray-500 hover:text-gray-700'}">
                    Configurações
                </button>
            </div>

            <!-- TAB 1: Geral -->
            <div id="contentGeral" class="space-y-4">
                <div>
                    <label class="${labelClass}">Título do Formulário <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="${formData.title || ''}" required
                           placeholder="Ex: Pesquisa de Satisfação" class="${inputClass}">
                </div>

                <div>
                    <label class="${labelClass}">Descrição</label>
                    <textarea name="description" rows="3" placeholder="Descreva brevemente o objetivo deste formulário"
                              class="${inputClass}">${formData.description || ''}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="${labelClass}">Modo de Exibição</label>
                        <select name="display_mode" class="${inputClass}">
                            <option value="one-by-one" ${formData.display_mode === 'one-by-one' ? 'selected' : ''}>Uma pergunta por vez</option>
                            <option value="all-at-once" ${formData.display_mode === 'all-at-once' ? 'selected' : ''}>Todas as perguntas</option>
                        </select>
                    </div>

                    <div>
                        <label class="${labelClass}">Status</label>
                        <select name="status" class="${inputClass}">
                            <option value="ativo" ${formData.status === 'ativo' ? 'selected' : ''}>Ativo</option>
                            <option value="rascunho" ${formData.status === 'rascunho' ? 'selected' : ''}>Rascunho</option>
                        </select>
                    </div>
                </div>

                ${window.userRole === 'admin' ? `
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="${labelClass}">Ícone <span class="text-xs ${isDark ? 'text-zinc-400' : 'text-gray-500'}">(Admin)</span></label>
                        <select name="icon" class="${inputClass}">
                            <option value="file-alt" ${formData.icon === 'file-alt' ? 'selected' : ''}>Documento</option>
                            <option value="clipboard-list" ${formData.icon === 'clipboard-list' ? 'selected' : ''}>Clipboard</option>
                            <option value="poll-h" ${formData.icon === 'poll-h' ? 'selected' : ''}>Poll</option>
                            <option value="chart-bar" ${formData.icon === 'chart-bar' ? 'selected' : ''}>Gráfico</option>
                            <option value="check-square" ${formData.icon === 'check-square' ? 'selected' : ''}>Checklist</option>
                            <option value="star" ${formData.icon === 'star' ? 'selected' : ''}>Estrela</option>
                            <option value="heart" ${formData.icon === 'heart' ? 'selected' : ''}>Coração</option>
                        </select>
                    </div>
                    <div>
                        <label class="${labelClass}">Cor <span class="text-xs ${isDark ? 'text-zinc-400' : 'text-gray-500'}">(Admin)</span></label>
                        <input type="color" name="color" value="${formData.color || '#4EA44B'}"
                               class="w-full h-10 px-1 border ${isDark ? 'border-zinc-600' : 'border-gray-300'} rounded-md">
                    </div>
                </div>
                ` : ''}
            </div>

            <!-- TAB 2: Configurações -->
            <div id="contentConfiguracoes" class="space-y-6" style="display: none;">
                <div class="${isDark ? 'bg-zinc-900/50' : 'bg-gray-50'} rounded-md p-4">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-medium ${isDark ? 'text-zinc-100' : 'text-gray-900'}">
                                <i class="fas fa-lock mr-2"></i>Sistema de Bloqueio
                            </h3>
                            <p class="text-sm ${isDark ? 'text-zinc-400' : 'text-gray-600'} mt-1">
                                Bloqueie o formulário após uma data ou número de respostas
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="blocking_enabled" id="blockingEnabled"
                                   ${formData.blocking_enabled ? 'checked' : ''}
                                   class="sr-only peer"
                                   onchange="toggleBlockingFields()">
                            <div class="w-11 h-6 bg-gray-300 dark:bg-zinc-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500 dark:peer-checked:bg-green-500"></div>
                        </label>
                    </div>

                    <div id="blockingFields" style="display: ${formData.blocking_enabled ? 'block' : 'none'};" class="space-y-4">
                        <div>
                            <label class="${labelClass}">Bloquear por</label>
                            <select name="blocking_type" id="blockingType" onchange="toggleBlockingType()" class="${inputClass}">
                                <option value="date" ${(formData.blocking_type || 'date') === 'date' ? 'selected' : ''}>Data e Hora</option>
                                <option value="responses" ${(formData.blocking_type || 'date') === 'responses' ? 'selected' : ''}>Número de Respostas</option>
                            </select>
                        </div>

                        <div id="blockingDateField" style="display: ${(formData.blocking_type || 'date') === 'date' ? 'block' : 'none'};">
                            <label class="${labelClass}">Data e Hora de Bloqueio</label>
                            <input type="datetime-local" name="blocking_date"
                                   value="${formData.blocking_date ? formData.blocking_date.slice(0, 16) : ''}"
                                   class="${inputClass}">
                        </div>

                        <div id="blockingResponsesField" style="display: ${(formData.blocking_type || 'date') === 'responses' ? 'block' : 'none'};">
                            <label class="${labelClass}">Número Máximo de Respostas</label>
                            <input type="number" name="blocking_response_limit" min="1"
                                   value="${formData.blocking_response_limit || ''}" placeholder="Ex: 100"
                                   class="${inputClass}">
                        </div>

                        <div>
                            <label class="${labelClass}">Mensagem quando Bloqueado</label>
                            <textarea name="blocking_message" rows="3"
                                      placeholder="Este formulário não está mais aceitando respostas."
                                      class="${inputClass}">${formData.blocking_message || ''}</textarea>
                            <p class="text-xs ${isDark ? 'text-zinc-400' : 'text-gray-500'} mt-1">
                                Esta mensagem será exibida no lugar do formulário quando ele estiver bloqueado
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    `;

    const footerLeft = `
        <button type="button" onclick="saveFormSettings()"
                class="inline-flex items-center px-5 py-2.5 text-white text-sm font-medium rounded-md transition-colors"
                style="background-color: #4EA44B;"
                onmouseover="this.style.backgroundColor='#3d8b40'"
                onmouseout="this.style.backgroundColor='#4EA44B'">
            <i class="fas fa-save mr-2"></i>Salvar Configurações
        </button>
    `;

    const footerRight = `
        <button type="button" onclick="Swal.close()"
                class="text-sm ${isDark ? 'text-zinc-400 hover:text-zinc-100' : 'text-gray-600 hover:text-gray-900'} transition-colors">
            Cancelar
        </button>
    `;

    Swal.fire({
        html: createFormModal({
            title: 'Configurações do Formulário',
            content: settingsHTML,
            footer: {
                left: footerLeft,
                right: footerRight
            }
        }),
        width: window.innerWidth < 640 ? '95%' : '800px',
        showConfirmButton: false,
        showCancelButton: false,
        didOpen: () => {
            switchSettingsTab('geral');
        }
    });
}

// Função para alternar abas de Configurações
window.switchSettingsTab = function(tab) {
    const isDark = document.documentElement.classList.contains('dark');

    // Tabs
    const tabGeral = document.getElementById('tabGeral');
    const tabConfiguracoes = document.getElementById('tabConfiguracoes');

    // Content
    const contentGeral = document.getElementById('contentGeral');
    const contentConfiguracoes = document.getElementById('contentConfiguracoes');

    if (tab === 'geral') {
        // Ativar aba Geral
        tabGeral.style.borderColor = '#4EA44B';
        tabGeral.style.color = isDark ? '#fff' : '#000';
        tabConfiguracoes.style.borderColor = 'transparent';
        tabConfiguracoes.className = 'px-4 py-2 font-medium text-sm border-b-2 border-transparent transition-colors ' + (isDark ? 'text-zinc-400 hover:text-zinc-200' : 'text-gray-500 hover:text-gray-700');

        contentGeral.style.display = 'block';
        contentConfiguracoes.style.display = 'none';
    } else {
        // Ativar aba Configurações
        tabConfiguracoes.style.borderColor = '#4EA44B';
        tabConfiguracoes.style.color = isDark ? '#fff' : '#000';
        tabGeral.style.borderColor = 'transparent';
        tabGeral.className = 'px-4 py-2 font-medium text-sm border-b-2 border-transparent transition-colors ' + (isDark ? 'text-zinc-400 hover:text-zinc-200' : 'text-gray-500 hover:text-gray-700');

        contentGeral.style.display = 'none';
        contentConfiguracoes.style.display = 'block';
    }
};

// Toggle para campos de bloqueio
window.toggleBlockingFields = function() {
    const enabled = document.getElementById('blockingEnabled').checked;
    document.getElementById('blockingFields').style.display = enabled ? 'block' : 'none';
};

// Toggle tipo de bloqueio
window.toggleBlockingType = function() {
    const type = document.getElementById('blockingType').value;
    document.getElementById('blockingDateField').style.display = type === 'date' ? 'block' : 'none';
    document.getElementById('blockingResponsesField').style.display = type === 'responses' ? 'block' : 'none';
};

// Salvar configurações do formulário
window.saveFormSettings = async function() {
    const form = document.getElementById('settingsForm');
    const formData = new FormData(form);

    try {
        const res = await fetch('/modules/forms/builder/save_settings.php', {
            method: 'POST',
            body: formData
        });

        const result = await res.text();

        if (res.ok && result === 'success') {
            Swal.fire({
                title: 'Sucesso!',
                text: 'Configurações salvas com sucesso!',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                title: 'Erro!',
                text: 'Erro ao salvar configurações: ' + result,
                icon: 'error'
            });
        }
    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            text: 'Erro de conexão',
            icon: 'error'
        });
    }
};

// Função para abrir personalização do formulário
async function openCustomization(formId) {
    try {
        const res = await fetch(`/modules/forms/customization/get.php?id=${formId}`);
        const data = await res.json();
        
        if (data.error) {
            Swal.fire({
                title: 'Erro!',
                text: data.error,
                icon: 'error'
            });
            return;
        }
        
        showCustomizationModal(formId, data);
        
    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            text: 'Erro ao carregar personalizações',
            icon: 'error'
        });
    }
}

// Função para abrir integrações do formulário
async function openSendOptions(formId) {
    try {
        const res = await fetch(`/modules/forms/send_options/get.php?id=${formId}`);
        const data = await res.json();

        if (data.error) {
            Swal.fire({
                title: 'Erro!',
                text: data.error,
                icon: 'error'
            });
            return;
        }

        showSendOptionsModal(formId, data);

    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            text: 'Erro ao carregar opções de envio',
            icon: 'error'
        });
    }
}

// Modal de Opções de Envio
function showSendOptionsModal(formId, data) {
    const isDark = document.documentElement.classList.contains('dark');
    const inputClass = isDark
        ? 'w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-zinc-700 border border-zinc-600 text-zinc-100'
        : 'w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-white border border-gray-300 text-gray-900';
    const labelClass = isDark ? 'block text-sm font-medium mb-2 text-zinc-100' : 'block text-sm font-medium mb-2 text-gray-900';

    const sendOptionsHTML = `
        <form id="sendOptionsForm" class="text-left space-y-6">
            <input type="hidden" name="form_id" value="${formId}">

            <p class="text-sm ${isDark ? 'text-zinc-400' : 'text-gray-600'} mb-4">
                Configure para onde as respostas do formulário serão enviadas
            </p>

            <!-- E-mail Principal -->
            <div class="border-b ${isDark ? 'border-zinc-700' : 'border-gray-200'} pb-4">
                <h3 class="font-semibold text-base mb-3 ${isDark ? 'text-zinc-100' : 'text-gray-900'} flex items-center">
                    
                    E-mail Principal
                </h3>
                <div>
                    <label class="${labelClass}">E-mail de destino *</label>
                    <input
                        type="email"
                        name="email_to"
                        value="${data.email_to || ''}"
                        placeholder="seu@email.com"
                        required
                        class="${inputClass}">
                    <p class="text-xs ${isDark ? 'text-zinc-400' : 'text-gray-500'} mt-1">
                        Sempre que alguém responder, você receberá um e-mail aqui
                    </p>
                </div>
            </div>

            <!-- E-mails Cópia -->
            <div>
                <h3 class="font-semibold text-base mb-3 ${isDark ? 'text-zinc-100' : 'text-gray-900'} flex items-center gap-2">
                    E-mails Cópia (CC)
                    ${!IS_PRO_USER ? '<span class="text-xs bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-2 py-1 rounded-full font-semibold">✨ PRO</span>' : ''}
                </h3>
                <div class="${!IS_PRO_USER ? 'opacity-50 pointer-events-none' : ''}">
                    <label class="${labelClass}">E-mails adicionais (separados por vírgula)</label>
                    <textarea
                        name="email_cc"
                        rows="2"
                        placeholder="${!IS_PRO_USER ? 'Recurso disponível apenas no plano PRO' : 'email1@exemplo.com, email2@exemplo.com'}"
                        class="${inputClass}"
                        ${!IS_PRO_USER ? 'disabled' : ''}
                    >${data.email_cc || ''}</textarea>
                    <p class="text-xs ${isDark ? 'text-zinc-400' : 'text-gray-500'} mt-1">
                        ${!IS_PRO_USER ? 'Faça upgrade para enviar cópias para múltiplos e-mails' : 'Outros e-mails que também receberão as respostas'}
                    </p>
                </div>
            </div>
        </form>
    `;

    const footerLeft = `
        <button type="button" onclick="saveSendOptions()"
                class="inline-flex items-center px-5 py-2.5 text-white text-sm font-medium rounded-md transition-colors"
                style="background-color: #4EA44B;"
                onmouseover="this.style.backgroundColor='#3d8b40'"
                onmouseout="this.style.backgroundColor='#4EA44B'">
            <i class="fas fa-save mr-2"></i>Salvar
        </button>
    `;

    const footerRight = `
        <button type="button" onclick="Swal.close()"
                class="text-sm ${isDark ? 'text-zinc-400 hover:text-zinc-100' : 'text-gray-600 hover:text-gray-900'} transition-colors">
            Cancelar
        </button>
    `;

    Swal.fire({
        html: createFormModal({
            title: 'Opções de Envio',
            content: sendOptionsHTML,
            footer: {
                left: footerLeft,
                right: footerRight
            }
        }),
        width: '600px',
        showConfirmButton: false,
        showCancelButton: false
    });

    // Função para salvar opções de envio
    window.saveSendOptions = async function() {
        const form = document.getElementById('sendOptionsForm');
        const formData = new FormData(form);

        try {
            const res = await fetch("/modules/forms/send_options/save.php", {
                method: "POST",
                body: formData
            });

            const result = await res.text();

            if (res.ok && result === "success") {
                Swal.close();
                Swal.fire({
                    title: 'Salvo!',
                    text: 'Opções de envio configuradas com sucesso.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                throw new Error(result);
            }
        } catch (error) {
            Swal.fire({
                title: 'Erro!',
                text: `Erro ao salvar: ${error.message}`,
                icon: 'error'
            });
        }
    };
}

// Função para abrir integrações do formulário
async function openIntegrations(formId) {
    try {
        const res = await fetch(`/modules/forms/integrations/get.php?id=${formId}`);
        const data = await res.json();

        if (data.error) {
            Swal.fire({
                title: 'Erro!',
                text: data.error,
                icon: 'error'
            });
            return;
        }

        showIntegrationsModal(formId, data);

    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            text: 'Erro ao carregar integrações',
            icon: 'error'
        });
    }
}

// Modal de Integrações
function showIntegrationsModal(formId, data) {
    const isDark = document.documentElement.classList.contains('dark');
    const inputClass = isDark
        ? 'w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-zinc-700 border border-zinc-600 text-zinc-100'
        : 'w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-white border border-gray-300 text-gray-900';
    const labelClass = isDark ? 'block text-sm font-medium mb-2 text-zinc-100' : 'block text-sm font-medium mb-2 text-gray-900';
    const switchClass = 'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2';
    const sectionClass = isDark ? 'border-b border-zinc-700 pb-6' : 'border-b border-gray-200 pb-6';
    const descClass = isDark ? 'text-xs text-zinc-400 mt-1' : 'text-xs text-gray-500 mt-1';

    const integrationsHTML = `
        <form id="integrationsForm" class="text-left">
            <input type="hidden" name="form_id" value="${formId}">

            <!-- TABS -->
            <div class="flex border-b ${isDark ? 'border-zinc-700' : 'border-gray-200'} mb-6">
                <button type="button" onclick="switchIntegrationTab('integrations')" id="tabIntegrations"
                        class="px-4 py-2 font-medium text-sm border-b-2 transition-colors focus:outline-none"
                        style="border-color: #4EA44B; color: ${isDark ? '#fff' : '#000'}">
                    Integrações
                </button>
                <button type="button" onclick="switchIntegrationTab('tracking')" id="tabTracking"
                        class="px-4 py-2 font-medium text-sm border-b-2 border-transparent transition-colors focus:outline-none ${isDark ? 'text-zinc-400 hover:text-zinc-200' : 'text-gray-500 hover:text-gray-700'}">
                    Rastreamento
                </button>
            </div>

            <!-- TAB 1: Integrações -->
            <div id="contentIntegrations" class="space-y-6">
                <p class="text-sm ${isDark ? 'text-zinc-400' : 'text-gray-600'} mb-4">
                    Conecte seu formulário com CRMs, planilhas e agendadores
                </p>

            <!-- WEBHOOK -->
            <div class="${sectionClass} ${!IS_PRO_USER ? 'opacity-50 pointer-events-none' : ''}">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-base ${isDark ? 'text-zinc-100' : 'text-gray-900'} flex items-center gap-2">
                        Webhook
                        ${!IS_PRO_USER ? '<span class="text-xs bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-2 py-1 rounded-full font-semibold">✨ PRO</span>' : ''}
                    </h3>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="webhook_enabled" id="webhookEnabled"
                               ${data.webhook_enabled ? 'checked' : ''}
                               class="sr-only peer"
                               onchange="toggleWebhookFields()">
                        <div class="w-11 h-6 bg-gray-300 dark:bg-zinc-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500 dark:peer-checked:bg-green-500"></div>
                    </label>
                </div>

                <div id="webhookFields" style="display: ${data.webhook_enabled ? 'block' : 'none'};">
                    <div class="space-y-3">
                        <div>
                            <label class="${labelClass}">URL do Webhook *</label>
                            <input type="url" name="webhook_url" id="webhookUrl"
                                   value="${data.webhook_url || ''}"
                                   placeholder="https://seu-servidor.com/webhook"
                                   class="${inputClass}">
                            <p class="${descClass}">
                                💡 Use serviços como Zapier, Make.com ou n8n para integrar com qualquer CRM
                            </p>
                        </div>

                        <div>
                            <label class="${labelClass}">Método HTTP</label>
                            <select name="webhook_method" class="${inputClass}">
                                <option value="POST" ${data.webhook_method === 'POST' ? 'selected' : ''}>POST</option>
                                <option value="GET" ${data.webhook_method === 'GET' ? 'selected' : ''}>GET</option>
                            </select>
                        </div>

                        <div>
                            <label class="${labelClass}">Headers Personalizados (opcional)</label>
                            <textarea name="webhook_headers" rows="3"
                                      placeholder='{"Authorization": "Bearer token", "Content-Type": "application/json"}'
                                      class="${inputClass}">${data.webhook_headers || ''}</textarea>
                            <p class="${descClass}">
                                Formato JSON para headers customizados de autenticação
                            </p>
                        </div>

                        <button type="button" onclick="testWebhook()"
                                class="w-full px-4 py-2 text-sm font-medium rounded-md transition-colors"
                                style="background-color: #4EA44B; color: white;">
                            <i class="fas fa-bolt mr-2"></i>Testar Webhook
                        </button>
                    </div>
                </div>
            </div>

            <!-- GOOGLE SHEETS -->
            <div class="${sectionClass}">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-base ${isDark ? 'text-zinc-100' : 'text-gray-900'} flex items-center">
                        
                        Google Sheets
                    </h3>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="sheets_enabled" id="sheetsEnabled"
                               ${data.sheets_enabled ? 'checked' : ''}
                               class="sr-only peer"
                               onchange="toggleSheetsFields()">
                        <div class="w-11 h-6 bg-gray-300 dark:bg-zinc-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500 dark:peer-checked:bg-green-500"></div>
                    </label>
                </div>

                <div id="sheetsFields" style="display: ${data.sheets_enabled ? 'block' : 'none'};">
                    <div class="space-y-3">
                        <div>
                            <label class="${labelClass}">URL da Planilha *</label>
                            <input type="url" name="sheets_url" id="sheetsUrl"
                                   value="${data.sheets_url || ''}"
                                   placeholder="https://docs.google.com/spreadsheets/d/..."
                                   class="${inputClass}">
                            <p class="${descClass}">
                                📊 Cole a URL completa da planilha do Google Sheets
                            </p>
                        </div>

                        <div class="p-3 rounded-md ${isDark ? 'bg-zinc-700' : 'bg-blue-50'} border ${isDark ? 'border-zinc-600' : 'border-blue-200'}">
                            <p class="text-xs ${isDark ? 'text-zinc-300' : 'text-blue-800'} mb-2">
                                <strong>📝 Como configurar:</strong>
                            </p>
                            <ol class="text-xs ${isDark ? 'text-zinc-400' : 'text-blue-700'} list-decimal list-inside space-y-1">
                                <li>Crie uma planilha no Google Sheets</li>
                                <li>Compartilhe com permissão de editor</li>
                                <li>Cole a URL da planilha acima</li>
                                <li>As respostas serão adicionadas automaticamente</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CALENDLY -->
            <div class="${!IS_PRO_USER ? 'opacity-50 pointer-events-none' : ''}">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-base ${isDark ? 'text-zinc-100' : 'text-gray-900'} flex items-center gap-2">
                        Calendly
                        ${!IS_PRO_USER ? '<span class="text-xs bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-2 py-1 rounded-full font-semibold">✨ PRO</span>' : ''}
                    </h3>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="calendly_enabled" id="calendlyEnabled"
                               ${data.calendly_enabled ? 'checked' : ''}
                               class="sr-only peer"
                               onchange="toggleCalendlyFields()">
                        <div class="w-11 h-6 bg-gray-300 dark:bg-zinc-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500 dark:peer-checked:bg-green-500"></div>
                    </label>
                </div>

                <div id="calendlyFields" style="display: ${data.calendly_enabled ? 'block' : 'none'};">
                    <div class="space-y-3">
                        <div>
                            <label class="${labelClass}">URL do Calendly *</label>
                            <input type="url" name="calendly_url" id="calendlyUrl"
                                   value="${data.calendly_url || ''}"
                                   placeholder="https://calendly.com/seu-usuario/evento"
                                   class="${inputClass}">
                            <p class="${descClass}">
                                📅 Link do seu evento no Calendly que será exibido na mensagem de sucesso
                            </p>
                        </div>

                        <div class="p-3 rounded-md ${isDark ? 'bg-zinc-700' : 'bg-purple-50'} border ${isDark ? 'border-zinc-600' : 'border-purple-200'}">
                            <p class="text-xs ${isDark ? 'text-zinc-300' : 'text-purple-800'}">
                                <strong>💡 Dica:</strong> Após enviar o formulário, o usuário verá um botão para agendar no seu Calendly
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <!-- TAB 2: Rastreamento -->
            <div id="contentTracking" class="space-y-6" style="display: none;">
                <p class="text-sm ${isDark ? 'text-zinc-400' : 'text-gray-600'} mb-4">
                    Configure ferramentas de rastreamento e análise de conversões
                </p>

                <!-- UTM TRACKING -->
                <div class="${sectionClass}">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-base ${isDark ? 'text-zinc-100' : 'text-gray-900'} flex items-center">
                            
                            UTM Parameters
                        </h3>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="utm_enabled" id="utmEnabled"
                                   ${data.utm_enabled ? 'checked' : ''}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 dark:bg-zinc-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500 dark:peer-checked:bg-green-500"></div>
                        </label>
                    </div>

                    <div class="p-3 rounded-md ${isDark ? 'bg-zinc-700' : 'bg-blue-50'} border ${isDark ? 'border-zinc-600' : 'border-blue-200'}">
                        <p class="text-xs ${isDark ? 'text-zinc-300' : 'text-blue-800'} mb-2">
                            <strong>📊 Como funciona:</strong>
                        </p>
                        <p class="text-xs ${isDark ? 'text-zinc-400' : 'text-blue-700'} mb-2">
                            Quando habilitado, o sistema captura automaticamente os parâmetros UTM da URL e salva junto com cada resposta.
                        </p>
                        <p class="text-xs ${isDark ? 'text-zinc-400' : 'text-blue-700'} font-mono">
                            Exemplo: formulario.com?utm_source=facebook&utm_campaign=black_friday
                        </p>
                        <p class="text-xs ${isDark ? 'text-zinc-400' : 'text-blue-700'} mt-2">
                            <strong>Parâmetros capturados:</strong> utm_source, utm_medium, utm_campaign, utm_term, utm_content
                        </p>
                    </div>
                </div>

                <!-- FACEBOOK PIXEL -->
                <div class="${sectionClass} ${!IS_PRO_USER ? 'opacity-50 pointer-events-none' : ''}">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-base ${isDark ? 'text-zinc-100' : 'text-gray-900'} flex items-center gap-2">
                            Facebook Pixel
                            ${!IS_PRO_USER ? '<span class="text-xs bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-2 py-1 rounded-full font-semibold">✨ PRO</span>' : ''}
                        </h3>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="fb_pixel_enabled" id="fbPixelEnabled"
                                   ${data.fb_pixel_enabled ? 'checked' : ''}
                                   class="sr-only peer"
                                   onchange="toggleFbPixelFields()">
                            <div class="w-11 h-6 bg-gray-300 dark:bg-zinc-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500 dark:peer-checked:bg-green-500"></div>
                        </label>
                    </div>

                    <div id="fbPixelFields" style="display: ${data.fb_pixel_enabled ? 'block' : 'none'};">
                        <div>
                            <label class="${labelClass}">Pixel ID *</label>
                            <input type="text" name="fb_pixel_id" id="fbPixelId"
                                   value="${data.fb_pixel_id || ''}"
                                   placeholder="123456789012345"
                                   class="${inputClass}">
                            <p class="${descClass}">
                                📱 Encontre seu Pixel ID no Gerenciador de Eventos do Facebook
                            </p>
                        </div>
                    </div>
                </div>

                <!-- GOOGLE TAG MANAGER -->
                <div class="${sectionClass} ${!IS_PRO_USER ? 'opacity-50 pointer-events-none' : ''}">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-base ${isDark ? 'text-zinc-100' : 'text-gray-900'} flex items-center gap-2">
                            Google Tag Manager
                            ${!IS_PRO_USER ? '<span class="text-xs bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-2 py-1 rounded-full font-semibold">✨ PRO</span>' : ''}
                        </h3>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="gtm_enabled" id="gtmEnabled"
                                   ${data.gtm_enabled ? 'checked' : ''}
                                   class="sr-only peer"
                                   onchange="toggleGtmFields()">
                            <div class="w-11 h-6 bg-gray-300 dark:bg-zinc-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500 dark:peer-checked:bg-green-500"></div>
                        </label>
                    </div>

                    <div id="gtmFields" style="display: ${data.gtm_enabled ? 'block' : 'none'};">
                        <div>
                            <label class="${labelClass}">GTM Container ID *</label>
                            <input type="text" name="gtm_id" id="gtmId"
                                   value="${data.gtm_id || ''}"
                                   placeholder="GTM-XXXXXXX"
                                   class="${inputClass}">
                            <p class="${descClass}">
                                🏷️ Formato: GTM-XXXXXXX (encontre no Google Tag Manager)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- GOOGLE ANALYTICS -->
                <div class="${!IS_PRO_USER ? 'opacity-50 pointer-events-none' : ''}">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-base ${isDark ? 'text-zinc-100' : 'text-gray-900'} flex items-center gap-2">
                            Google Analytics
                            ${!IS_PRO_USER ? '<span class="text-xs bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-2 py-1 rounded-full font-semibold">✨ PRO</span>' : ''}
                        </h3>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="ga_enabled" id="gaEnabled"
                                   ${data.ga_enabled ? 'checked' : ''}
                                   class="sr-only peer"
                                   onchange="toggleGaFields()">
                            <div class="w-11 h-6 bg-gray-300 dark:bg-zinc-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500 dark:peer-checked:bg-green-500"></div>
                        </label>
                    </div>

                    <div id="gaFields" style="display: ${data.ga_enabled ? 'block' : 'none'};">
                        <div class="space-y-3">
                            <div>
                                <label class="${labelClass}">Measurement ID *</label>
                                <input type="text" name="ga_id" id="gaId"
                                       value="${data.ga_id || ''}"
                                       placeholder="G-XXXXXXXXXX ou UA-XXXXXXXXX-X"
                                       class="${inputClass}">
                                <p class="${descClass}">
                                    📈 GA4: G-XXXXXXXXXX | Universal Analytics: UA-XXXXXXXXX-X
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    `;

    const footerLeft = `
        <button type="button" onclick="saveIntegrations()"
                class="inline-flex items-center px-5 py-2.5 text-white text-sm font-medium rounded-md transition-colors"
                style="background-color: #4EA44B;"
                onmouseover="this.style.backgroundColor='#3d8b40'"
                onmouseout="this.style.backgroundColor='#4EA44B'">
            <i class="fas fa-save mr-2"></i>Salvar Integrações
        </button>
    `;

    const footerRight = `
        <button type="button" onclick="Swal.close()"
                class="text-sm ${isDark ? 'text-zinc-400 hover:text-zinc-100' : 'text-gray-600 hover:text-gray-900'} transition-colors">
            Cancelar
        </button>
    `;

    Swal.fire({
        html: createFormModal({
            title: 'Integrações',
            content: integrationsHTML,
            footer: {
                left: footerLeft,
                right: footerRight
            }
        }),
        width: '700px',
        showConfirmButton: false,
        showCancelButton: false
    });

    // Função para salvar integrações
    window.saveIntegrations = async function() {
        const form = document.getElementById('integrationsForm');
        const formData = new FormData(form);

        try {
            const res = await fetch("/modules/forms/integrations/save.php", {
                method: "POST",
                body: formData
            });

            const result = await res.text();

            if (res.ok && result === "success") {
                Swal.close();
                Swal.fire({
                    title: 'Salvo!',
                    text: 'Integrações configuradas com sucesso.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                throw new Error(result);
            }
        } catch (error) {
            Swal.fire({
                title: 'Erro!',
                text: `Erro ao salvar: ${error.message}`,
                icon: 'error'
            });
        }
    };
}

// Funções auxiliares para toggle de campos
window.toggleWebhookFields = function() {
    const enabled = document.getElementById('webhookEnabled').checked;
    document.getElementById('webhookFields').style.display = enabled ? 'block' : 'none';
};

window.toggleSheetsFields = function() {
    const enabled = document.getElementById('sheetsEnabled').checked;
    document.getElementById('sheetsFields').style.display = enabled ? 'block' : 'none';
};

window.toggleCalendlyFields = function() {
    const enabled = document.getElementById('calendlyEnabled').checked;
    document.getElementById('calendlyFields').style.display = enabled ? 'block' : 'none';
};

window.testWebhook = async function() {
    const url = document.getElementById('webhookUrl').value;
    const method = document.querySelector('select[name="webhook_method"]').value;

    if (!url) {
        Swal.fire({
            title: 'Atenção!',
            text: 'Digite a URL do webhook primeiro',
            icon: 'warning'
        });
        return;
    }

    Swal.fire({
        title: 'Testando webhook...',
        html: 'Enviando dados de teste para o webhook',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const testData = {
            test: true,
            timestamp: new Date().toISOString(),
            message: 'Este é um teste do webhook do TalkForm'
        };

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(testData)
        });

        if (response.ok) {
            Swal.fire({
                title: 'Sucesso!',
                text: `Webhook respondeu com status ${response.status}`,
                icon: 'success'
            });
        } else {
            Swal.fire({
                title: 'Webhook respondeu',
                text: `Status: ${response.status} - ${response.statusText}`,
                icon: 'info'
            });
        }
    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            text: `Falha ao conectar: ${error.message}`,
            icon: 'error'
        });
    }
};

// Função para alternar abas de Integrações
window.switchIntegrationTab = function(tab) {
    const isDark = document.documentElement.classList.contains('dark');

    // Tabs
    const tabIntegrations = document.getElementById('tabIntegrations');
    const tabTracking = document.getElementById('tabTracking');

    // Content
    const contentIntegrations = document.getElementById('contentIntegrations');
    const contentTracking = document.getElementById('contentTracking');

    if (tab === 'integrations') {
        // Ativar aba Integrações
        tabIntegrations.style.borderColor = '#4EA44B';
        tabIntegrations.style.color = isDark ? '#fff' : '#000';
        tabTracking.style.borderColor = 'transparent';
        tabTracking.className = 'px-4 py-2 font-medium text-sm border-b-2 border-transparent transition-colors ' + (isDark ? 'text-zinc-400 hover:text-zinc-200' : 'text-gray-500 hover:text-gray-700');

        contentIntegrations.style.display = 'block';
        contentTracking.style.display = 'none';
    } else {
        // Ativar aba Rastreamento
        tabTracking.style.borderColor = '#4EA44B';
        tabTracking.style.color = isDark ? '#fff' : '#000';
        tabIntegrations.style.borderColor = 'transparent';
        tabIntegrations.className = 'px-4 py-2 font-medium text-sm border-b-2 border-transparent transition-colors ' + (isDark ? 'text-zinc-400 hover:text-zinc-200' : 'text-gray-500 hover:text-gray-700');

        contentIntegrations.style.display = 'none';
        contentTracking.style.display = 'block';
    }
};

// Toggle para campos de rastreamento
window.toggleFbPixelFields = function() {
    const enabled = document.getElementById('fbPixelEnabled').checked;
    document.getElementById('fbPixelFields').style.display = enabled ? 'block' : 'none';
};

window.toggleGtmFields = function() {
    const enabled = document.getElementById('gtmEnabled').checked;
    document.getElementById('gtmFields').style.display = enabled ? 'block' : 'none';
};

window.toggleGaFields = function() {
    const enabled = document.getElementById('gaEnabled').checked;
    document.getElementById('gaFields').style.display = enabled ? 'block' : 'none';
};

// Função auxiliar para fazer upload de imagem
async function uploadImage(file, formId, fieldName) {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('form_id', formId);
    formData.append('field_name', fieldName);
    
    try {
        const res = await fetch('/modules/forms/customization/upload_image.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await res.json();
        
        if (result.success) {
            return result.url;
        } else {
            throw new Error(result.error || 'Erro ao fazer upload');
        }
    } catch (error) {
        throw error;
    }
}

// Modal de personalização
function showCustomizationModal(formId, data) {
    const isDark = document.documentElement.classList.contains('dark');
    const inputClass = isDark 
        ? 'w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-500 bg-zinc-700 border border-zinc-600 text-zinc-100' 
        : 'w-full rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-500 bg-white border border-gray-300 text-gray-900';
    const labelClass = isDark ? 'block text-sm font-medium mb-2 text-zinc-100' : 'block text-sm font-medium mb-2 text-gray-900';
    const fileInputClass = isDark
        ? 'w-full text-sm text-zinc-100 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-zinc-700 file:text-zinc-100 hover:file:bg-zinc-600 cursor-pointer'
        : 'w-full text-sm text-gray-900 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer';
    
    const fonts = ['Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Raleway', 'Ubuntu', 'Nunito', 'Playfair Display'];
    
    const customizationHTML = `
        <form id="customizationForm" class="text-left">
            <input type="hidden" name="form_id" value="${formId}">
            <input type="hidden" name="background_image_url" id="backgroundImageUrl" value="${data.background_image || ''}">
            <input type="hidden" name="logo_url" id="logoUrl" value="${data.logo || ''}">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Coluna Esquerda: Configurações -->
                <div class="space-y-5">
                    <!-- Seção: Cores -->
                    <div class="space-y-3">
                        <h3 class="font-semibold text-sm ${isDark ? 'text-zinc-100' : 'text-gray-900'} border-b ${isDark ? 'border-zinc-700' : 'border-gray-200'} pb-2">Cores</h3>

                        <div class="space-y-2">
                            <!-- Cor de Fundo -->
                            <div class="flex items-center gap-2">
                                <label class="text-xs ${isDark ? 'text-zinc-300' : 'text-gray-700'} w-28 flex-shrink-0">Cor de Fundo</label>
                                <div class="flex items-center gap-2 flex-1">
                                    <input type="color" id="backgroundColorPicker" value="${data.background_color || '#ffffff'}"
                                           class="w-10 h-8 rounded border cursor-pointer flex-shrink-0"
                                           onchange="document.getElementById('backgroundColor').value = this.value; updatePreview()">
                                    <input type="text" name="background_color" id="backgroundColor" value="${data.background_color || '#ffffff'}"
                                           class="flex-1 px-2 py-1 text-xs rounded border ${isDark ? 'bg-zinc-700 border-zinc-600 text-zinc-100' : 'bg-white border-gray-300 text-gray-900'}"
                                           placeholder="#ffffff"
                                           maxlength="7"
                                           onchange="document.getElementById('backgroundColorPicker').value = this.value; updatePreview()">
                                </div>
                            </div>

                            <!-- Cor do Texto -->
                            <div class="flex items-center gap-2">
                                <label class="text-xs ${isDark ? 'text-zinc-300' : 'text-gray-700'} w-28 flex-shrink-0">Cor do Texto</label>
                                <div class="flex items-center gap-2 flex-1">
                                    <input type="color" id="textColorPicker" value="${data.text_color || '#000000'}"
                                           class="w-10 h-8 rounded border cursor-pointer flex-shrink-0"
                                           onchange="document.getElementById('textColor').value = this.value; updatePreview()">
                                    <input type="text" name="text_color" id="textColor" value="${data.text_color || '#000000'}"
                                           class="flex-1 px-2 py-1 text-xs rounded border ${isDark ? 'bg-zinc-700 border-zinc-600 text-zinc-100' : 'bg-white border-gray-300 text-gray-900'}"
                                           placeholder="#000000"
                                           maxlength="7"
                                           onchange="document.getElementById('textColorPicker').value = this.value; updatePreview()">
                                </div>
                            </div>

                            <!-- Cor do Botão -->
                            <div class="flex items-center gap-2">
                                <label class="text-xs ${isDark ? 'text-zinc-300' : 'text-gray-700'} w-28 flex-shrink-0">Cor do Botão</label>
                                <div class="flex items-center gap-2 flex-1">
                                    <input type="color" id="primaryColorPicker" value="${data.primary_color || '#4f46e5'}"
                                           class="w-10 h-8 rounded border cursor-pointer flex-shrink-0"
                                           onchange="document.getElementById('primaryColor').value = this.value; updatePreview()">
                                    <input type="text" name="primary_color" id="primaryColor" value="${data.primary_color || '#4f46e5'}"
                                           class="flex-1 px-2 py-1 text-xs rounded border ${isDark ? 'bg-zinc-700 border-zinc-600 text-zinc-100' : 'bg-white border-gray-300 text-gray-900'}"
                                           placeholder="#4f46e5"
                                           maxlength="7"
                                           onchange="document.getElementById('primaryColorPicker').value = this.value; updatePreview()">
                                </div>
                            </div>

                            <!-- Cor Texto Botão -->
                            <div class="flex items-center gap-2">
                                <label class="text-xs ${isDark ? 'text-zinc-300' : 'text-gray-700'} w-28 flex-shrink-0">Cor Texto Botão</label>
                                <div class="flex items-center gap-2 flex-1">
                                    <input type="color" id="buttonTextColorPicker" value="${data.button_text_color || '#ffffff'}"
                                           class="w-10 h-8 rounded border cursor-pointer flex-shrink-0"
                                           onchange="document.getElementById('buttonTextColor').value = this.value; updatePreview()">
                                    <input type="text" name="button_text_color" id="buttonTextColor" value="${data.button_text_color || '#ffffff'}"
                                           class="flex-1 px-2 py-1 text-xs rounded border ${isDark ? 'bg-zinc-700 border-zinc-600 text-zinc-100' : 'bg-white border-gray-300 text-gray-900'}"
                                           placeholder="#ffffff"
                                           maxlength="7"
                                           onchange="document.getElementById('buttonTextColorPicker').value = this.value; updatePreview()">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Seção: Imagens -->
                    <div class="space-y-3">
                        <h3 class="font-semibold text-sm ${isDark ? 'text-zinc-100' : 'text-gray-900'} border-b ${isDark ? 'border-zinc-700' : 'border-gray-200'} pb-2">Imagens</h3>

                        <div class="space-y-3">
                            <!-- Imagem de Fundo -->
                            <div>
                                <label class="text-xs ${isDark ? 'text-zinc-300' : 'text-gray-700'} mb-1 block">Imagem de Fundo</label>
                                <input type="file" id="backgroundImageFile" accept="image/*" class="hidden"
                                       onchange="handleImageUpload(this, 'background_image')">
                                <button type="button"
                                        onclick="document.getElementById('backgroundImageFile').click()"
                                        class="w-full px-3 py-2 ${isDark ? 'bg-zinc-700 border-zinc-600 hover:bg-zinc-600' : 'bg-white border-gray-300 hover:bg-gray-50'} border rounded text-xs transition-colors">
                                    <i class="fas fa-upload mr-1"></i> ${data.background_image ? 'Trocar imagem' : 'Fazer upload'}
                                </button>
                                <div class="upload-feedback-bg text-xs mt-1"></div>
                                ${data.background_image ? `
                                    <button type="button" onclick="removeImage('background_image')"
                                            class="text-xs text-red-600 dark:text-red-400 hover:underline mt-1 inline-block">
                                        ✗ Remover imagem
                                    </button>
                                ` : ''}
                            </div>

                            <!-- Logotipo -->
                            <div class="${window.userPlan === 'free' ? 'opacity-50' : ''}">
                                <label class="text-xs ${isDark ? 'text-zinc-300' : 'text-gray-700'} mb-1 block">
                                    Logotipo
                                    ${window.userPlan === 'free' ? '<span class="text-xs bg-gradient-to-r from-purple-600 to-pink-600 text-white px-2 py-1 rounded-full font-semibold ml-1">✨ PRO</span>' : ''}
                                </label>
                                <input type="file" id="logoFile" accept="image/*" class="hidden"
                                       ${window.userPlan === 'free' ? 'disabled' : ''}
                                       onchange="handleImageUpload(this, 'logo')">
                                <button type="button"
                                        onclick="document.getElementById('logoFile').click()"
                                        ${window.userPlan === 'free' ? 'disabled' : ''}
                                        class="w-full px-3 py-2 ${isDark ? 'bg-zinc-700 border-zinc-600 hover:bg-zinc-600' : 'bg-white border-gray-300 hover:bg-gray-50'} border rounded text-xs transition-colors ${window.userPlan === 'free' ? 'cursor-not-allowed' : ''}">
                                    <i class="fas fa-upload mr-1"></i> ${data.logo ? 'Trocar logotipo' : 'Fazer upload'}
                                </button>
                                <div class="upload-feedback-logo text-xs mt-1"></div>
                                ${data.logo ? `
                                    <button type="button" onclick="removeImage('logo')"
                                            class="text-xs text-red-600 dark:text-red-400 hover:underline mt-1 inline-block">
                                        ✗ Remover logotipo
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Seção: Estilo -->
                    <div class="space-y-3">
                        <h3 class="font-semibold text-sm ${isDark ? 'text-zinc-100' : 'text-gray-900'} border-b ${isDark ? 'border-zinc-700' : 'border-gray-200'} pb-2">Estilo</h3>
                        
                        <div>
                            <label class="${labelClass}">Arredondamento: <span id="radiusValue">${data.button_radius || 8}</span>px</label>
                            <input type="range" name="button_radius" id="buttonRadius" 
                                   min="0" max="50" value="${data.button_radius || 8}" 
                                   class="w-full accent-gray-600"
                                   oninput="document.getElementById('radiusValue').textContent = this.value; updatePreview()">
                        </div>
                        
                        <div>
                            <label class="${labelClass}">Fonte</label>
                            <select name="font_family" id="fontFamily" class="${inputClass}" onchange="updatePreview()">
                                ${fonts.map(font => `<option value="${font}" ${data.font_family === font ? 'selected' : ''} style="font-family: ${font}">${font}</option>`).join('')}
                            </select>
                        </div>

                        <!-- Alinhamento -->
                        <div>
                            <label class="${labelClass}">Alinhamento do Conteúdo</label>
                            <input type="hidden" name="content_alignment" id="contentAlignment" value="${data.content_alignment || 'center'}">
                            <div class="flex gap-2">
                                <button type="button"
                                        onclick="setAlignment('left')"
                                        id="alignLeft"
                                        class="flex-1 px-3 py-2 border rounded transition-colors ${(data.content_alignment || 'center') === 'left' ? (isDark ? 'bg-zinc-600 border-zinc-500' : 'bg-gray-200 border-gray-400') : (isDark ? 'bg-zinc-700 border-zinc-600 hover:bg-zinc-600' : 'bg-white border-gray-300 hover:bg-gray-50')}"
                                        title="Alinhar à esquerda">
                                    <i class="fas fa-align-left"></i>
                                </button>
                                <button type="button"
                                        onclick="setAlignment('center')"
                                        id="alignCenter"
                                        class="flex-1 px-3 py-2 border rounded transition-colors ${(data.content_alignment || 'center') === 'center' ? (isDark ? 'bg-zinc-600 border-zinc-500' : 'bg-gray-200 border-gray-400') : (isDark ? 'bg-zinc-700 border-zinc-600 hover:bg-zinc-600' : 'bg-white border-gray-300 hover:bg-gray-50')}"
                                        title="Centralizar">
                                    <i class="fas fa-align-center"></i>
                                </button>
                                <button type="button"
                                        onclick="setAlignment('right')"
                                        id="alignRight"
                                        class="flex-1 px-3 py-2 border rounded transition-colors ${(data.content_alignment || 'center') === 'right' ? (isDark ? 'bg-zinc-600 border-zinc-500' : 'bg-gray-200 border-gray-400') : (isDark ? 'bg-zinc-700 border-zinc-600 hover:bg-zinc-600' : 'bg-white border-gray-300 hover:bg-gray-50')}"
                                        title="Alinhar à direita">
                                    <i class="fas fa-align-right"></i>
                                </button>
                            </div>
                        </div>

                        <div class="pt-3 border-t ${isDark ? 'border-zinc-700' : 'border-gray-200'}">
                            <button type="button" onclick="restoreDefaults()"
                                    class="text-sm ${isDark ? 'text-zinc-400 hover:text-zinc-200' : 'text-gray-600 hover:text-gray-900'} hover:underline">
                                🔄 Restaurar padrões
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Coluna Direita: Preview -->
                <div>
                    <h3 class="font-semibold text-sm ${isDark ? 'text-zinc-100' : 'text-gray-900'} border-b ${isDark ? 'border-zinc-700' : 'border-gray-200'} pb-2 mb-3">Preview</h3>
                    <div id="customizationPreview" class="border-2 border-dashed ${isDark ? 'border-zinc-600' : 'border-gray-300'} rounded-md p-6 min-h-[450px] flex flex-col items-center justify-center"
                         style="background-color: ${data.background_color || '#ffffff'}; ${data.background_image ? `background-image: url('${data.background_image}'); background-size: cover; background-position: center;` : ''}">
                        ${data.logo ? `<img src="${data.logo}" alt="Logo" class="max-w-[150px] h-auto mb-6" id="previewLogo">` : '<div id="previewLogo"></div>'}
                        <div id="previewContent" class="max-w-md w-full text-${data.content_alignment || 'center'}">
                            <h4 class="text-2xl font-bold mb-3" id="previewTitle" style="color: ${data.text_color || '#000000'}; font-family: ${data.font_family || 'Inter'}">Título do Formulário</h4>
                            <p class="text-sm mb-6 opacity-90" id="previewText" style="color: ${data.text_color || '#000000'}; font-family: ${data.font_family || 'Inter'}">Esta é uma prévia de como seu formulário ficará.</p>
                            <button type="button" id="previewButton" class="px-8 py-3 font-medium text-sm transition-colors"
                                    style="background-color: ${data.primary_color || '#4f46e5'}; color: ${data.button_text_color || '#ffffff'}; border-radius: ${data.button_radius || 8}px">
                                Começar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    `;

    const footerLeft = `
        <button type="button" onclick="saveCustomization()"
                class="inline-flex items-center px-5 py-2.5 text-white text-sm font-medium rounded-md transition-colors"
                style="background-color: #4EA44B;"
                onmouseover="this.style.backgroundColor='#3d8b40'"
                onmouseout="this.style.backgroundColor='#4EA44B'">
            <i class="fas fa-save mr-2"></i>Salvar Personalização
        </button>
    `;

    const footerRight = `
        <button type="button" onclick="Swal.close()"
                class="text-sm ${isDark ? 'text-zinc-400 hover:text-zinc-100' : 'text-gray-600 hover:text-gray-900'} transition-colors">
            Cancelar
        </button>
    `;

    Swal.fire({
        html: createFormModal({
            title: 'Personalização do Formulário',
            content: customizationHTML,
            footer: {
                left: footerLeft,
                right: footerRight
            }
        }),
        width: window.innerWidth < 1024 ? '95%' : '1000px',
        showConfirmButton: false,
        showCancelButton: false,
        customClass: {
            popup: 'swal-wide'
        }
    });

    // Função para salvar personalização
    window.saveCustomization = async function() {
        const form = document.getElementById('customizationForm');
        const formData = new FormData(form);

        formData.set('form_id', formId);
        formData.set('background_color', document.getElementById('backgroundColor').value);
        formData.set('text_color', document.getElementById('textColor').value);
        formData.set('primary_color', document.getElementById('primaryColor').value);
        formData.set('button_text_color', document.getElementById('buttonTextColor').value);
        formData.set('background_image', document.getElementById('backgroundImageUrl').value);
        formData.set('logo', document.getElementById('logoUrl').value);
        formData.set('button_radius', document.getElementById('buttonRadius').value);
        formData.set('font_family', document.getElementById('fontFamily').value);
        formData.set('content_alignment', document.getElementById('contentAlignment').value);

        try {
            const res = await fetch("/modules/forms/customization/save.php", {
                method: "POST",
                body: formData
            });

            const result = await res.text();

            if (res.ok && result === "success") {
                Swal.close();
                Swal.fire({
                    title: 'Salvo!',
                    text: 'Personalização aplicada com sucesso.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                throw new Error(result);
            }
        } catch (error) {
            Swal.fire({
                title: 'Erro!',
                text: `Erro ao salvar: ${error.message}`,
                icon: 'error'
            });
        }
    };
    
    const link = document.createElement('link');
    link.href = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto:wght@400;500;700&family=Open+Sans:wght@400;600;700&family=Lato:wght@400;700&family=Montserrat:wght@400;600;700&family=Poppins:wght@400;500;600;700&family=Raleway:wght@400;600;700&family=Ubuntu:wght@400;500;700&family=Nunito:wght@400;600;700&family=Playfair+Display:wght@400;700&display=swap';
    link.rel = 'stylesheet';
    document.head.appendChild(link);
}

window.handleImageUpload = async function(input, fieldName) {
    if (!input.files || !input.files[0]) return;
    
    const file = input.files[0];
    const formIdField = document.querySelector('input[name="form_id"]');
    if (!formIdField) {
        alert('Erro: form_id não encontrado');
        return;
    }
    const formId = formIdField.value;
    
    const feedbackClass = fieldName === 'background_image' ? '.upload-feedback-bg' : '.upload-feedback-logo';
    let feedbackEl = input.parentElement.querySelector(feedbackClass);
    if (!feedbackEl) {
        feedbackEl = document.createElement('div');
        feedbackEl.className = feedbackClass.substring(1) + ' text-xs mt-1';
        input.parentElement.appendChild(feedbackEl);
    }
    
    feedbackEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    feedbackEl.className = feedbackClass.substring(1) + ' text-xs mt-1 text-blue-600 dark:text-blue-400';
    
    try {
        const imageUrl = await uploadImage(file, formId, fieldName);
        
        const urlInput = fieldName === 'background_image' 
            ? document.getElementById('backgroundImageUrl') 
            : document.getElementById('logoUrl');
        
        if (urlInput) {
            urlInput.value = imageUrl;
        }
        
        updatePreview();
        
        feedbackEl.innerHTML = '✓ Imagem carregada com sucesso!';
        feedbackEl.className = feedbackClass.substring(1) + ' text-xs mt-1 text-green-600 dark:text-green-400';
        
    } catch (error) {
        feedbackEl.innerHTML = '✗ Erro: ' + (error.message || 'Falha ao enviar');
        feedbackEl.className = feedbackClass.substring(1) + ' text-xs mt-1 text-red-600 dark:text-red-400';
    }
};

window.removeImage = function(fieldName) {
    const urlInput = fieldName === 'background_image' 
        ? document.getElementById('backgroundImageUrl') 
        : document.getElementById('logoUrl');
    
    if (urlInput) {
        urlInput.value = '';
    }
    
    const fileInput = fieldName === 'background_image' 
        ? document.getElementById('backgroundImageFile') 
        : document.getElementById('logoFile');
    
    if (fileInput) {
        fileInput.value = '';
    }
    
    updatePreview();
    
    const feedbackClass = fieldName === 'background_image' ? '.upload-feedback-bg' : '.upload-feedback-logo';
    const feedbackEl = document.querySelector(feedbackClass);
    if (feedbackEl) {
        feedbackEl.innerHTML = '✓ Imagem removida';
        feedbackEl.className = feedbackClass.substring(1) + ' text-xs mt-1 text-gray-600 dark:text-gray-400';
    }
};

window.restoreDefaults = function() {
    if (!confirm('Restaurar padrões? Isso irá resetar todas as cores, fonte, alinhamento e imagens para os valores padrão.')) {
        return;
    }

    // Cores
    document.getElementById('backgroundColor').value = '#ffffff';
    document.getElementById('backgroundColorPicker').value = '#ffffff';
    document.getElementById('textColor').value = '#000000';
    document.getElementById('textColorPicker').value = '#000000';
    document.getElementById('primaryColor').value = '#4f46e5';
    document.getElementById('primaryColorPicker').value = '#4f46e5';
    document.getElementById('buttonTextColor').value = '#ffffff';
    document.getElementById('buttonTextColorPicker').value = '#ffffff';

    // Imagens
    document.getElementById('backgroundImageUrl').value = '';
    document.getElementById('logoUrl').value = '';
    document.getElementById('backgroundImageFile').value = '';
    document.getElementById('logoFile').value = '';

    // Estilo
    document.getElementById('buttonRadius').value = 8;
    document.getElementById('radiusValue').textContent = 8;
    document.getElementById('fontFamily').value = 'Inter';

    // Alinhamento
    setAlignment('center');

    updatePreview();

    const feedbackBg = document.querySelector('.upload-feedback-bg');
    const feedbackLogo = document.querySelector('.upload-feedback-logo');

    if (feedbackBg) {
        feedbackBg.innerHTML = '✓ Padrões restaurados';
        feedbackBg.className = 'upload-feedback-bg text-xs mt-1 text-green-600 dark:text-green-400';
    }
    if (feedbackLogo) {
        feedbackLogo.innerHTML = '✓ Padrões restaurados';
        feedbackLogo.className = 'upload-feedback-logo text-xs mt-1 text-green-600 dark:text-green-400';
    }
};

window.setAlignment = function(alignment) {
    document.getElementById('contentAlignment').value = alignment;

    // Obter se está em modo dark
    const isDark = document.documentElement.classList.contains('dark');

    // Atualizar visual dos botões
    const buttons = ['alignLeft', 'alignCenter', 'alignRight'];
    const alignments = { alignLeft: 'left', alignCenter: 'center', alignRight: 'right' };

    buttons.forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (btn) {
            if (alignments[btnId] === alignment) {
                // Botão ativo
                btn.className = `flex-1 px-3 py-2 border rounded transition-colors ${isDark ? 'bg-zinc-600 border-zinc-500' : 'bg-gray-200 border-gray-400'}`;
            } else {
                // Botão inativo
                btn.className = `flex-1 px-3 py-2 border rounded transition-colors ${isDark ? 'bg-zinc-700 border-zinc-600 hover:bg-zinc-600' : 'bg-white border-gray-300 hover:bg-gray-50'}`;
            }
        }
    });

    updatePreview();
};

window.updatePreview = function() {
    const bgColor = document.getElementById('backgroundColor')?.value;
    const textColor = document.getElementById('textColor')?.value;
    const primaryColor = document.getElementById('primaryColor')?.value;
    const buttonTextColor = document.getElementById('buttonTextColor')?.value;
    const bgImageUrl = document.getElementById('backgroundImageUrl')?.value;
    const logoUrl = document.getElementById('logoUrl')?.value;
    const buttonRadius = document.getElementById('buttonRadius')?.value;
    const fontFamily = document.getElementById('fontFamily')?.value;
    const alignment = document.getElementById('contentAlignment')?.value || 'center';

    const preview = document.getElementById('customizationPreview');
    const previewTitle = document.getElementById('previewTitle');
    const previewText = document.getElementById('previewText');
    const previewButton = document.getElementById('previewButton');
    const previewLogo = document.getElementById('previewLogo');
    const previewContent = document.getElementById('previewContent');

    if (!preview) return;

    if (bgColor) preview.style.backgroundColor = bgColor;
    if (bgImageUrl) {
        preview.style.backgroundImage = `url('${bgImageUrl}')`;
        preview.style.backgroundSize = 'cover';
        preview.style.backgroundPosition = 'center';
    } else {
        preview.style.backgroundImage = 'none';
    }

    if (previewLogo) {
        if (logoUrl) {
            previewLogo.innerHTML = `<img src="${logoUrl}" alt="Logo" class="max-w-[150px] h-auto mb-6">`;
        } else {
            previewLogo.innerHTML = '';
        }
    }

    if (previewTitle && textColor && fontFamily) {
        previewTitle.style.color = textColor;
        previewTitle.style.fontFamily = fontFamily;
    }
    if (previewText && textColor && fontFamily) {
        previewText.style.color = textColor;
        previewText.style.fontFamily = fontFamily;
    }

    if (previewButton) {
        if (primaryColor) previewButton.style.backgroundColor = primaryColor;
        if (buttonTextColor) previewButton.style.color = buttonTextColor;
        if (buttonRadius) previewButton.style.borderRadius = buttonRadius + 'px';
    }

    // Aplicar alinhamento de texto (container continua centralizado)
    if (previewContent) {
        // Remover classes antigas de alinhamento de texto
        previewContent.classList.remove('text-left', 'text-right', 'text-center');

        // Adicionar nova classe baseado no alinhamento
        if (alignment === 'left') {
            previewContent.classList.add('text-left');
        } else if (alignment === 'right') {
            previewContent.classList.add('text-right');
        } else {
            previewContent.classList.add('text-center');
        }
    }
};
</script>

<!-- Área principal -->
<main class="flex-1 p-4 lg:p-6 bg-gray-100 dark:bg-zinc-900 transition-colors duration-200">