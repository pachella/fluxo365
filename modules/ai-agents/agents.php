<?php
session_start();
require_once("../../core/db.php");
require_once("../../core/PermissionManager.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: /auth/login");
    exit;
}

$permissionManager = new PermissionManager(
    $_SESSION['user_role'],
    $_SESSION['user_id'] ?? null
);

$userId = $_SESSION['user_id'];

// Buscar agentes do usuário
try {
    $stmt = $pdo->prepare("
        SELECT a.*,
               COALESCE((SELECT COUNT(*) FROM ai_agent_knowledge WHERE agent_id = a.id), 0) as knowledge_count
        FROM ai_agents a
        WHERE a.user_id = ?
        ORDER BY a.updated_at DESC
    ");
    $stmt->execute([$userId]);
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erro ao buscar agentes: ' . $e->getMessage());
    $agents = [];
}

// Buscar quadros CRM do usuário (para o dropdown na aba CRM)
try {
    $stmt = $pdo->prepare("SELECT id, name FROM crm_boards WHERE user_id = ? ORDER BY name ASC");
    $stmt->execute([$userId]);
    $crmBoards = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erro ao buscar quadros CRM: ' . $e->getMessage());
    $crmBoards = [];
}
?>

<style>
.agent-card {
    transition: all 0.3s ease;
}

.agent-card:hover {
    transform: translateY(-4px);
}

.agent-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.agent-status-badge.active {
    background-color: #10b98120;
    color: #10b981;
}

.agent-status-badge.paused {
    background-color: #6b728020;
    color: #6b7280;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
}

.stat-item-label {
    font-size: 0.75rem;
    opacity: 0.6;
}

.stat-item-value {
    font-size: 1rem;
    font-weight: 700;
}

/* Tabs lifted style */
.tabs-lifted {
    border-bottom: 2px solid;
}
</style>

<div class="w-full max-w-full overflow-x-hidden">
    <!-- Header com Skeleton Loading -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold">Agentes de IA</h1>
            <p class="text-sm opacity-60 mt-1">Gerencie seus agentes de inteligência artificial</p>
        </div>
        <button onclick="showCreateAgentModal()" class="btn btn-primary">
            <i data-feather="plus" class="w-5 h-5"></i>
            Novo Agente
        </button>
    </div>

    <?php if (empty($agents)): ?>
        <!-- Estado vazio -->
        <div class="card bg-base-200 shadow">
            <div class="card-body text-center py-16">
                <i data-feather="cpu" class="w-16 h-16 mx-auto mb-4 opacity-40"></i>
                <h2 class="text-xl font-bold mb-2">Nenhum agente criado</h2>
                <p class="opacity-60 mb-6">Configure um novo agente para criar um novo agente de inteligência artificial</p>
                <button onclick="showCreateAgentModal()" class="btn btn-primary mx-auto">
                    <i data-feather="plus" class="w-5 h-5"></i>
                    Criar Primeiro Agente
                </button>
            </div>
        </div>
    <?php else: ?>
        <!-- Grid de Agentes -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($agents as $agent): ?>
                <div class="card bg-base-200 shadow agent-card">
                    <div class="card-body">
                        <!-- Header do Card -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="avatar placeholder">
                                <div class="w-12 h-12 rounded-full" style="background-color: <?= htmlspecialchars($agent['color']) ?>">
                                    <span class="text-xl text-white font-bold">
                                        <i data-feather="cpu" class="w-6 h-6"></i>
                                    </span>
                                </div>
                            </div>

                            <!-- Status Toggle -->
                            <label class="swap swap-rotate" onclick="event.stopPropagation(); toggleAgentStatus(<?= $agent['id'] ?>, '<?= $agent['status'] ?>')">
                                <input type="checkbox" <?= $agent['status'] === 'active' ? 'checked' : '' ?> />
                                <span class="swap-on agent-status-badge active">
                                    <i data-feather="play-circle" class="w-3 h-3"></i>
                                    Ativo
                                </span>
                                <span class="swap-off agent-status-badge paused">
                                    <i data-feather="pause-circle" class="w-3 h-3"></i>
                                    Pausado
                                </span>
                            </label>
                        </div>

                        <h2 class="card-title text-lg mb-1">
                            <?= htmlspecialchars($agent['name']) ?>
                        </h2>

                        <?php if ($agent['whatsapp_number']): ?>
                            <p class="text-xs opacity-60 mb-2">
                                <i data-feather="smartphone" class="w-3 h-3 inline"></i>
                                <?= htmlspecialchars($agent['whatsapp_number']) ?>
                            </p>
                        <?php endif; ?>

                        <div class="badge badge-sm badge-outline mb-3">
                            <i data-feather="zap" class="w-3 h-3 mr-1"></i>
                            <?= htmlspecialchars(strtoupper($agent['model'])) ?>
                        </div>

                        <!-- Estatísticas -->
                        <div class="grid grid-cols-3 gap-2 mt-4 pt-4 border-t border-base-300">
                            <div class="stat-item">
                                <span class="stat-item-label">Créditos</span>
                                <span class="stat-item-value"><?= number_format($agent['credits_spent']) ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-item-label">Conversas</span>
                                <span class="stat-item-value"><?= $agent['conversations_count'] ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-item-label">Pausadas</span>
                                <span class="stat-item-value"><?= $agent['paused_conversations'] ?></span>
                            </div>
                        </div>

                        <!-- Ações -->
                        <div class="card-actions justify-end mt-4">
                            <button onclick="viewAgentConversations(<?= $agent['id'] ?>)" class="btn btn-sm btn-ghost" title="Ver conversas">
                                <i data-feather="message-square" class="w-4 h-4"></i>
                            </button>
                            <button onclick="editAgent(<?= $agent['id'] ?>)" class="btn btn-sm btn-ghost" title="Configurar">
                                <i data-feather="settings" class="w-4 h-4"></i>
                            </button>
                            <button onclick="deleteAgent(<?= $agent['id'] ?>, '<?= htmlspecialchars($agent['name'], ENT_QUOTES) ?>')" class="btn btn-sm btn-ghost text-error" title="Excluir">
                                <i data-feather="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Criar/Editar Agente com Tabs Lifted -->
<dialog id="agentModal" class="modal">
    <div class="modal-box max-w-4xl">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
        </form>
        <h3 class="font-bold text-lg mb-4" id="modalTitle">Criar Novo Agente</h3>

        <form id="agentForm" onsubmit="saveAgent(event)">
            <input type="hidden" id="agent_id" name="id" value="">

            <!-- Tabs Lifted -->
            <div role="tablist" class="tabs tabs-lifted">
                <!-- Aba Instruções -->
                <input type="radio" name="agent_tabs" role="tab" class="tab" aria-label="Instruções" checked />
                <div role="tabpanel" class="tab-content bg-base-100 border-base-300 rounded-box p-6">
                    <div class="flex items-center gap-4 mb-6">
                        <!-- Avatar/Cor do Agente -->
                        <div>
                            <label class="label">
                                <span class="label-text font-semibold">Cor do Agente</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="color" name="color" value="#8b5cf6" class="w-16 h-16 rounded-lg cursor-pointer" />
                                <div class="avatar placeholder">
                                    <div id="previewAvatar" class="w-16 h-16 rounded-full" style="background-color: #8b5cf6">
                                        <i data-feather="cpu" class="w-8 h-8 text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Nome e WhatsApp -->
                        <div class="flex-1">
                            <div class="form-control mb-3">
                                <label class="label">
                                    <span class="label-text font-semibold">Nome do Agente *</span>
                                </label>
                                <input type="text" id="name" name="name" placeholder="Ex: Romildo" class="input input-bordered" required />
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text font-semibold">WhatsApp Vinculado</span>
                                </label>
                                <input type="text" id="whatsapp_number" name="whatsapp_number" placeholder="Ex: Xiaomi" class="input input-bordered" />
                            </div>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Instruções do Sistema</span>
                            <a href="#" class="label-text-alt link link-primary">Criar instruções com ajuda</a>
                        </label>
                        <textarea id="system_instructions" name="system_instructions" rows="8" class="textarea textarea-bordered" placeholder="Você é um agente chamado Romildo, seu papel é atender os clientes da Suportezi. A Suportezi é uma plataforma de atendimento com foco em uma aplicação de criação de sites com inteligência artificial, email, suporte e manutenção, além de, claro, o recurso de atendimento com IA sem a"></textarea>
                        <div class="flex items-center justify-between mt-2">
                            <div class="flex flex-wrap gap-1">
                                <button type="button" class="btn btn-xs btn-outline">
                                    <i data-feather="flag" class="w-3 h-3"></i>
                                    Negrito
                                </button>
                                <button type="button" class="btn btn-xs btn-outline">
                                    <i data-feather="italic" class="w-3 h-3"></i>
                                    Itálico
                                </button>
                                <button type="button" class="btn btn-xs btn-outline">
                                    <i data-feather="list" class="w-3 h-3"></i>
                                    H1
                                </button>
                                <button type="button" class="btn btn-xs btn-outline">
                                    <i data-feather="hash" class="w-3 h-3"></i>
                                    H2
                                </button>
                                <button type="button" class="btn btn-xs btn-outline">
                                    <i data-feather="list" class="w-3 h-3"></i>
                                    UL
                                </button>
                                <button type="button" class="btn btn-xs btn-outline">
                                    <i data-feather="list" class="w-3 h-3"></i>
                                    Link
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aba Conhecimento -->
                <input type="radio" name="agent_tabs" role="tab" class="tab" aria-label="Conhecimento" />
                <div role="tabpanel" class="tab-content bg-base-100 border-base-300 rounded-box p-6">
                    <h4 class="font-semibold mb-4">Adicionar Conhecimentos</h4>
                    <div class="text-center py-12 bg-base-200 rounded-lg border-2 border-dashed border-base-300">
                        <i data-feather="upload-cloud" class="w-12 h-12 mx-auto mb-3 opacity-40"></i>
                        <p class="text-sm mb-2">Clique para fazer upload do arquivo escolhido aqui</p>
                        <p class="text-xs opacity-60">Formatos suportados: PDF, TXT, CSV (máx. 10MB por arquivo)</p>
                        <input type="file" id="knowledge_file" name="knowledge_file" accept=".pdf,.txt,.csv" multiple class="file-input file-input-bordered file-input-sm mt-4 max-w-xs" />
                    </div>

                    <!-- Lista de arquivos (será populada dinamicamente) -->
                    <div id="knowledgeFilesList" class="mt-4"></div>

                    <div class="flex justify-between mt-6">
                        <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('knowledge_file').click()">
                            <i data-feather="upload" class="w-4 h-4"></i>
                            Enviar
                        </button>
                    </div>
                </div>

                <!-- Aba Configurações -->
                <input type="radio" name="agent_tabs" role="tab" class="tab" aria-label="Configurações" />
                <div role="tabpanel" class="tab-content bg-base-100 border-base-300 rounded-box p-6">
                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text font-semibold">Modelo de IA *</span>
                        </label>
                        <select id="model" name="model" class="select select-bordered">
                            <option value="gpt-5-nano">GPT-5 nano</option>
                            <option value="gpt-4-turbo">GPT-4 Turbo</option>
                            <option value="gpt-4">GPT-4</option>
                            <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                        </select>
                        <label class="label">
                            <span class="label-text-alt text-warning">
                                <i data-feather="alert-circle" class="w-3 h-3 inline"></i>
                                Modelo mais leve e econômico do GPT-5
                            </span>
                        </label>
                    </div>

                    <h4 class="font-semibold mb-3">Opções do Agente</h4>

                    <div class="form-control mb-3">
                        <label class="label cursor-pointer justify-start gap-4">
                            <input type="checkbox" id="has_audio" name="has_audio" value="1" class="toggle toggle-success" checked />
                            <span class="label-text">
                                <span class="font-semibold">Ouvir áudio</span>
                                <span class="badge badge-warning badge-xs ml-2">Consome 1 crédito por áudio</span>
                            </span>
                        </label>
                    </div>

                    <div class="form-control mb-3">
                        <label class="label cursor-pointer justify-start gap-4">
                            <input type="checkbox" id="analyze_images" name="analyze_images" value="1" class="toggle toggle-success" checked />
                            <span class="label-text">
                                <span class="font-semibold">Analisar imagens</span>
                                <span class="badge badge-warning badge-xs ml-2">Consome 1 crédito por imagem</span>
                            </span>
                        </label>
                    </div>

                    <div class="form-control mb-3">
                        <label class="label cursor-pointer justify-start gap-4">
                            <input type="checkbox" id="quotes_enabled" name="quotes_enabled" value="1" class="toggle toggle-success" checked />
                            <span class="label-text font-semibold">Aparecer "Digite..." / "Gravando..."</span>
                        </label>
                    </div>

                    <div class="form-control mb-3">
                        <label class="label cursor-pointer justify-start gap-4">
                            <input type="checkbox" id="pause_attendance" name="pause_attendance" value="1" class="toggle toggle-success" checked />
                            <span class="label-text font-semibold">Pausar agente no atendimento humano</span>
                        </label>
                    </div>

                    <div class="form-control mb-4">
                        <label class="label cursor-pointer justify-start gap-4">
                            <input type="checkbox" id="group_messages" name="group_messages" value="1" class="toggle toggle-success" checked />
                            <span class="label-text font-semibold">Agrupar mensagens</span>
                        </label>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Quantidade de mensagens de histórico</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="number" id="history_limit" name="history_limit" value="10" min="1" max="100" class="input input-bordered w-24" />
                            <span class="text-sm opacity-60">segundos</span>
                        </div>
                        <label class="label">
                            <span class="label-text-alt opacity-60">O agente irá manter na quantização de mensagens do histórico o quanto maior, mais mensagens o agente vai ter pra lembrar, mas mais créditos</span>
                        </label>
                    </div>
                </div>

                <!-- Aba CRM -->
                <input type="radio" name="agent_tabs" role="tab" class="tab" aria-label="CRM" />
                <div role="tabpanel" class="tab-content bg-base-100 border-base-300 rounded-box p-6">
                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text font-semibold">Quadro CRM *</span>
                        </label>
                        <select id="crm_board_id" name="crm_board_id" class="select select-bordered">
                            <option value="">Selecione um quadro</option>
                            <?php foreach ($crmBoards as $board): ?>
                                <option value="<?= $board['id'] ?>"><?= htmlspecialchars($board['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text font-semibold">Etapa *</span>
                        </label>
                        <input type="text" id="crm_stage" name="crm_stage" placeholder="Ex: Onboarding" class="input input-bordered" />
                    </div>

                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text font-semibold">Valor padrão no card</span>
                        </label>
                        <input type="text" id="crm_default_value" name="crm_default_value" placeholder="R$ 0,00" class="input input-bordered" />
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Observação padrão</span>
                        </label>
                        <textarea id="crm_default_observation" name="crm_default_observation" rows="4" class="textarea textarea-bordered" placeholder="Coletar o nome e mail e necessidade do cliente e joga lá imediatamente no CRM."></textarea>
                    </div>

                    <div class="alert alert-info mt-6">
                        <i data-feather="info" class="w-5 h-5"></i>
                        <span class="text-sm">Ao configurar o CRM, os leads serão automaticamente adicionados ao quadro selecionado na etapa especificada.</span>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline btn-success mt-4">
                        <i data-feather="plus" class="w-4 h-4"></i>
                        Adicionar Tarefas
                    </button>
                </div>
            </div>

            <div class="modal-action mt-6">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('agentModal').close()">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i data-feather="save" class="w-4 h-4"></i>
                    Salvar
                </button>
            </div>
        </form>
    </div>
</dialog>

<script>
const crmBoards = <?= json_encode($crmBoards) ?>;

// Renderizar ícones
if (typeof feather !== 'undefined') {
    feather.replace();
}

// Preview da cor do agente
document.getElementById('color')?.addEventListener('input', function(e) {
    const color = e.target.value;
    document.getElementById('previewAvatar').style.backgroundColor = color;
});

// Modal de criar agente
function showCreateAgentModal() {
    document.getElementById('modalTitle').textContent = 'Criar Novo Agente';
    document.getElementById('agentForm').reset();
    document.getElementById('agent_id').value = '';
    document.getElementById('agentModal').showModal();
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
}

// Editar agente
async function editAgent(agentId) {
    try {
        const res = await fetch(`/core/crud/get.php?module=ai-agents&entity=agent&id=${agentId}`);
        const agent = await res.json();

        document.getElementById('modalTitle').textContent = 'Configurar Agente';
        document.getElementById('agent_id').value = agent.id;
        document.getElementById('name').value = agent.name || '';
        document.getElementById('whatsapp_number').value = agent.whatsapp_number || '';
        document.getElementById('system_instructions').value = agent.system_instructions || '';
        document.getElementById('model').value = agent.model || 'gpt-5-nano';
        document.getElementById('color').value = agent.color || '#8b5cf6';
        document.getElementById('previewAvatar').style.backgroundColor = agent.color || '#8b5cf6';

        // Checkboxes
        document.getElementById('has_audio').checked = agent.has_audio == 1;
        document.getElementById('analyze_images').checked = agent.analyze_images == 1;
        document.getElementById('quotes_enabled').checked = agent.quotes_enabled == 1;
        document.getElementById('pause_attendance').checked = agent.pause_attendance == 1;
        document.getElementById('group_messages').checked = agent.group_messages == 1;
        document.getElementById('history_limit').value = agent.history_limit || 10;

        // Buscar configuração CRM do agente
        const crmRes = await fetch(`/modules/ai-agents/get-crm-config.php?agent_id=${agentId}`);
        if (crmRes.ok) {
            const crmConfig = await crmRes.json();
            if (crmConfig) {
                document.getElementById('crm_board_id').value = crmConfig.board_id || '';
                document.getElementById('crm_stage').value = crmConfig.stage || '';
                document.getElementById('crm_default_value').value = crmConfig.default_value || '';
                document.getElementById('crm_default_observation').value = crmConfig.default_observation || '';
            }
        }

        document.getElementById('agentModal').showModal();
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao carregar dados do agente', 'error');
    }
}

// Salvar agente
async function saveAgent(event) {
    event.preventDefault();

    try {
        const formData = new FormData(event.target);
        const agentId = formData.get('id');

        // Converter checkboxes para 0/1
        formData.set('has_audio', document.getElementById('has_audio').checked ? '1' : '0');
        formData.set('analyze_images', document.getElementById('analyze_images').checked ? '1' : '0');
        formData.set('quotes_enabled', document.getElementById('quotes_enabled').checked ? '1' : '0');
        formData.set('pause_attendance', document.getElementById('pause_attendance').checked ? '1' : '0');
        formData.set('group_messages', document.getElementById('group_messages').checked ? '1' : '0');

        const res = await fetch(`/core/crud/save.php?module=ai-agents&entity=agent`, {
            method: 'POST',
            body: formData
        });

        const result = await res.text();

        if (res.ok && result === "success") {
            await Swal.fire('Sucesso!', agentId ? 'Agente atualizado!' : 'Agente criado!', 'success');
            location.reload();
        } else {
            Swal.fire('Erro!', result || 'Erro ao salvar agente', 'error');
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao salvar agente', 'error');
    }
}

// Deletar agente
async function deleteAgent(agentId, agentName) {
    const result = await Swal.fire({
        title: 'Tem certeza?',
        text: `Deseja realmente excluir o agente "${agentName}"?`,
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
            formData.append('id', agentId);

            const res = await fetch('/core/crud/delete.php?module=ai-agents&entity=agent', {
                method: 'POST',
                body: formData
            });

            const responseText = await res.text();

            if (res.ok && responseText === "success") {
                await Swal.fire('Excluído!', 'Agente excluído com sucesso!', 'success');
                location.reload();
            } else {
                Swal.fire('Erro!', responseText || 'Erro ao excluir agente', 'error');
            }
        } catch (error) {
            Swal.fire('Erro!', 'Erro ao excluir agente', 'error');
        }
    }
}

// Toggle status do agente
async function toggleAgentStatus(agentId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'paused' : 'active';

    try {
        const formData = new FormData();
        formData.append('id', agentId);
        formData.append('status', newStatus);

        const res = await fetch('/core/crud/save.php?module=ai-agents&entity=agent', {
            method: 'POST',
            body: formData
        });

        const result = await res.text();

        if (res.ok && result === "success") {
            location.reload();
        } else {
            Swal.fire('Erro!', result || 'Erro ao alterar status', 'error');
            location.reload();
        }
    } catch (error) {
        Swal.fire('Erro!', 'Erro ao alterar status', 'error');
        location.reload();
    }
}

// Ver conversas do agente (placeholder)
function viewAgentConversations(agentId) {
    Swal.fire('Em desenvolvimento', 'Funcionalidade de conversas em breve!', 'info');
}
</script>
