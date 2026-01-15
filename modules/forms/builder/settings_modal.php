<?php
/**
 * Modal de Configurações do Formulário - 2 Abas
 *
 * Aba 1: Geral - Informações básicas (título, descrição, status, modo de exibição)
 * Aba 2: Configurações - Bloqueio e recursos avançados
 *
 * NOTA: A personalização visual (cores, fontes, logo) tem seu próprio modal separado
 * e não está incluída aqui para evitar redundância.
 */
?>

<!-- Modal de Configurações (escondido por padrão) -->
<div id="settingsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4" style="backdrop-filter: blur(4px);">
    <div class="bg-white dark:bg-zinc-800 rounded-md shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header do Modal -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-zinc-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-zinc-100">Configurações do Formulário</h2>
            <button onclick="closeSettingsModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-zinc-300">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Tabs -->
        <div class="px-6 pt-4">
            <div class="flex border-b border-gray-200 dark:border-zinc-700 mb-6">
                <button onclick="switchSettingsTab('geral')" id="tab-geral" type="button"
                        class="settings-tab px-4 py-2 font-medium text-sm border-b-2 border-transparent transition-colors text-gray-500 dark:text-zinc-400 hover:text-gray-700 dark:hover:text-zinc-200">
                    <i class="fas fa-info-circle mr-2"></i>Geral
                </button>
                <button onclick="switchSettingsTab('configuracoes')" id="tab-configuracoes" type="button"
                        class="settings-tab px-4 py-2 font-medium text-sm border-b-2 border-transparent transition-colors text-gray-500 dark:text-zinc-400 hover:text-gray-700 dark:hover:text-zinc-200">
                    <i class="fas fa-lock mr-2"></i>Bloqueio
                </button>
            </div>
        </div>

        <!-- Conteúdo das Tabs -->
        <div class="flex-1 overflow-y-auto px-6 pb-6">
            <!-- ABA 1: GERAL -->
            <div id="content-geral" class="settings-content">
                <form id="formGeralSettings" class="space-y-6">
                    <!-- Título -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2">
                            Título do Formulário <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="formTitle" name="title" value="<?= htmlspecialchars($form['title']) ?>" required
                               class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md focus:ring-2 focus:ring-green-500 dark:bg-zinc-700 dark:text-zinc-100">
                    </div>

                    <!-- Descrição -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2">
                            Descrição
                        </label>
                        <textarea id="formDescription" name="description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md focus:ring-2 focus:ring-green-500 dark:bg-zinc-700 dark:text-zinc-100"><?= htmlspecialchars($form['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Modo de Exibição -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2">
                            Modo de Exibição
                        </label>
                        <select id="formDisplayMode" name="display_mode"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md focus:ring-2 focus:ring-green-500 dark:bg-zinc-700 dark:text-zinc-100">
                            <option value="one-by-one" <?= $form['display_mode'] === 'one-by-one' ? 'selected' : '' ?>>Uma pergunta por vez</option>
                            <option value="all-at-once" <?= $form['display_mode'] === 'all-at-once' ? 'selected' : '' ?>>Todas as perguntas</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2">
                            Status
                        </label>
                        <select id="formStatus" name="status"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md focus:ring-2 focus:ring-green-500 dark:bg-zinc-700 dark:text-zinc-100">
                            <option value="ativo" <?= $form['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="rascunho" <?= $form['status'] === 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                        </select>
                    </div>

                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <!-- Ícone e Cor (Admin Only) -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2">
                                Ícone <span class="text-xs text-gray-500">(Admin)</span>
                            </label>
                            <select id="formIcon" name="icon"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md focus:ring-2 focus:ring-green-500 dark:bg-zinc-700 dark:text-zinc-100">
                                <?php
                                $icons = ['file-alt', 'clipboard-list', 'poll-h', 'chart-bar', 'check-square', 'list-ul', 'edit', 'star', 'heart', 'thumbs-up', 'envelope', 'calendar', 'clock', 'user', 'users', 'briefcase', 'graduation-cap', 'shopping-cart'];
                                foreach ($icons as $icon): ?>
                                    <option value="<?= $icon ?>" <?= ($form['icon'] ?? 'file-alt') === $icon ? 'selected' : '' ?>>
                                        <?= ucfirst(str_replace('-', ' ', $icon)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2">
                                Cor <span class="text-xs text-gray-500">(Admin)</span>
                            </label>
                            <input type="color" id="formColor" name="color" value="<?= $form['color'] ?? '#4EA44B' ?>"
                                   class="w-full h-10 px-1 border border-gray-300 dark:border-zinc-600 rounded-md focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- ABA 2: CONFIGURAÇÕES -->
            <div id="content-configuracoes" class="settings-content hidden">
                <form id="formAdvancedSettings" class="space-y-6">
                    <!-- Sistema de Bloqueio -->
                    <div class="bg-gray-50 dark:bg-zinc-900/50 rounded-md p-4 <?= !PlanService::hasProAccess() ? 'opacity-50' : '' ?>">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-zinc-100 flex items-center gap-2">
                                    <i class="fas fa-lock"></i>Sistema de Bloqueio
                                    <?php if (!PlanService::hasProAccess()): ?>
                                        <span class="text-xs bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-2 py-1 rounded-full font-semibold">✨ PRO</span>
                                    <?php endif; ?>
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-zinc-400 mt-1">
                                    Bloqueie o formulário após uma data ou número de respostas
                                </p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" id="blockingEnabled" name="blocking_enabled"
                                       <?= !PlanService::hasProAccess() ? 'disabled' : '' ?>
                                       <?= ($form['blocking_enabled'] ?? 0) ? 'checked' : '' ?>
                                       onchange="toggleBlockingFields(this.checked)">
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div id="blockingFields" style="display: <?= ($form['blocking_enabled'] ?? 0) ? 'block' : 'none' ?>;" class="space-y-4">
                            <!-- Tipo de Bloqueio -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2">
                                    Bloquear por
                                </label>
                                <select id="blockingType" name="blocking_type" onchange="toggleBlockingType(this.value)"
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md dark:bg-zinc-700 dark:text-zinc-100">
                                    <option value="date" <?= ($form['blocking_type'] ?? 'date') === 'date' ? 'selected' : '' ?>>Data e Hora</option>
                                    <option value="responses" <?= ($form['blocking_type'] ?? 'date') === 'responses' ? 'selected' : '' ?>>Número de Respostas</option>
                                </select>
                            </div>

                            <!-- Data de Bloqueio -->
                            <div id="blockingDateField" style="display: <?= ($form['blocking_type'] ?? 'date') === 'date' ? 'block' : 'none' ?>;">
                                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2">
                                    Data e Hora de Bloqueio
                                </label>
                                <input type="datetime-local" name="blocking_date"
                                       value="<?= !empty($form['blocking_date']) ? date('Y-m-d\TH:i', strtotime($form['blocking_date'])) : '' ?>"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md dark:bg-zinc-700 dark:text-zinc-100">
                            </div>

                            <!-- Limite de Respostas -->
                            <div id="blockingResponsesField" style="display: <?= ($form['blocking_type'] ?? 'date') === 'responses' ? 'block' : 'none' ?>;">
                                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2">
                                    Número Máximo de Respostas
                                </label>
                                <input type="number" name="blocking_response_limit" min="1"
                                       value="<?= $form['blocking_response_limit'] ?? '' ?>" placeholder="Ex: 100"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md dark:bg-zinc-700 dark:text-zinc-100">
                            </div>

                            <!-- Mensagem de Bloqueio -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2">
                                    Mensagem quando Bloqueado
                                </label>
                                <textarea name="blocking_message" rows="3" placeholder="Este formulário não está mais aceitando respostas."
                                          class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-md dark:bg-zinc-700 dark:text-zinc-100"><?= htmlspecialchars($form['blocking_message'] ?? '') ?></textarea>
                                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                                    Esta mensagem será exibida no lugar do formulário quando ele estiver bloqueado
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Footer com Botões -->
        <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200 dark:border-zinc-700">
            <button onclick="closeSettingsModal()" class="px-6 py-2 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-zinc-300 rounded-md hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                Cancelar
            </button>
            <button onclick="saveAllSettings()" class="px-6 py-2 text-white rounded-md transition-colors" style="background-color: #4EA44B;" onmouseover="this.style.backgroundColor='#3d8b40'" onmouseout="this.style.backgroundColor='#4EA44B'">
                <i class="fas fa-save mr-2"></i>Salvar Todas as Configurações
            </button>
        </div>
    </div>
</div>

<style>
    /* Estilo das tabs */
    .settings-tab.active {
        border-bottom-color: #4EA44B !important;
        color: #000 !important;
    }

    .dark .settings-tab.active {
        border-bottom-color: #4EA44B !important;
        color: #fff !important;
    }

    /* Switch toggle */
    .switch {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 24px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: .3s;
        border-radius: 24px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
    }

    input:checked + .slider {
        background-color: #4EA44B;
    }

    input:checked + .slider:before {
        transform: translateX(24px);
    }
</style>

<script>
// Abrir modal de configurações
function openFormSettings(formId) {
    document.getElementById('settingsModal').classList.remove('hidden');
    switchSettingsTab('geral'); // Abrir na primeira aba
}

// Fechar modal
function closeSettingsModal() {
    document.getElementById('settingsModal').classList.add('hidden');
}

// Alternar entre tabs
function switchSettingsTab(tabName) {
    // Remover active de todas as tabs
    document.querySelectorAll('.settings-tab').forEach(tab => {
        tab.classList.remove('active');
    });

    // Esconder todos os conteúdos
    document.querySelectorAll('.settings-content').forEach(content => {
        content.classList.add('hidden');
    });

    // Ativar tab selecionada
    document.getElementById('tab-' + tabName).classList.add('active');
    document.getElementById('content-' + tabName).classList.remove('hidden');
}

// Toggle campos de bloqueio
function toggleBlockingFields(enabled) {
    document.getElementById('blockingFields').style.display = enabled ? 'block' : 'none';
}

// Toggle tipo de bloqueio
function toggleBlockingType(type) {
    document.getElementById('blockingDateField').style.display = type === 'date' ? 'block' : 'none';
    document.getElementById('blockingResponsesField').style.display = type === 'responses' ? 'block' : 'none';
}

// Salvar todas as configurações
async function saveAllSettings() {
    const formData = new FormData();
    formData.append('form_id', <?= $formId ?>);

    // Dados da aba Geral
    const geralForm = document.getElementById('formGeralSettings');
    new FormData(geralForm).forEach((value, key) => formData.append(key, value));

    // Dados da aba Configurações
    const advancedForm = document.getElementById('formAdvancedSettings');
    new FormData(advancedForm).forEach((value, key) => formData.append(key, value));

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
                timer: 2000
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
}

// Fechar modal ao clicar fora
document.getElementById('settingsModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeSettingsModal();
    }
});
</script>
