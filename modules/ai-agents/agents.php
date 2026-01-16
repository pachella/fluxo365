<?php
// Detectar se já há sessão ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir dependências apenas se ainda não foram incluídas
if (!isset($pdo)) {
    require_once(__DIR__ . "/../../core/db.php");
}

if (!class_exists('PermissionManager')) {
    require_once(__DIR__ . "/../../core/PermissionManager.php");
}

if (!isset($_SESSION["user_id"])) {
    header("Location: /auth/login");
    exit;
}

// Usar PermissionManager global se existir, senão criar
if (!isset($permissionManager)) {
    $permissionManager = new PermissionManager(
        $_SESSION['user_role'],
        $_SESSION['user_id'] ?? null
    );
}

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
    display: flex;
    flex-direction: column;
    height: 100%;
}

.agent-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.agent-card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.agent-card-footer {
    border-top: 1px solid;
    padding: 0.75rem 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
}

.dark .agent-card-footer {
    border-color: #3f3f46;
}

.agent-card-footer {
    border-color: #e5e7eb;
}

.agent-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}

.agent-status-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stat-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-top: 1rem;
}

.stat-item {
    text-align: center;
}

.stat-label {
    font-size: 0.75rem;
    opacity: 0.6;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.125rem;
    font-weight: 700;
}

/* Tabs sem linha intensa */
.tabs-lifted > .tab:not(.tab-active):not([disabled]):hover {
    background-color: transparent;
}

.tabs-lifted .tab {
    border-bottom-color: transparent !important;
}

.tab-content {
    border-top: none !important;
}

/* Sidebar de anexos */
.attachments-sidebar {
    width: 180px;
    border-left: 1px solid;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 400px;
    overflow-y: auto;
}

.dark .attachments-sidebar {
    border-color: #3f3f46;
    background-color: #27272a;
}

.attachments-sidebar {
    border-color: #e5e7eb;
    background-color: #f9fafb;
}

.attachment-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.75rem;
    border-radius: 6px;
    cursor: grab;
    transition: all 0.2s;
    border: 2px dashed transparent;
}

.attachment-item:hover {
    border-color: #6366f1;
    background-color: rgba(99, 102, 241, 0.1);
}

.attachment-item:active {
    cursor: grabbing;
}

.attachment-icon {
    width: 32px;
    height: 32px;
    margin-bottom: 0.25rem;
}

.attachment-name {
    font-size: 0.625rem;
    text-align: center;
    word-break: break-word;
    max-width: 100%;
}

.upload-status {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.625rem;
    margin-top: 0.25rem;
}

.upload-status.success {
    color: #10b981;
}
</style>

<div class="w-full max-w-full overflow-x-hidden">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold">Agentes de IA</h1>
            <p class="text-sm opacity-60 mt-1">Gerencie seus agentes de inteligência artificial</p>
        </div>
        <button onclick="showCreateAgentModal()" class="btn btn-primary">
            <i data-feather="plus" class="w-5 h-5"></i>
            Criar Novo Agente
        </button>
    </div>

    <?php if (empty($agents)): ?>
        <!-- Estado vazio -->
        <div class="card bg-base-200 shadow">
            <div class="card-body text-center py-16">
                <i data-feather="cpu" class="w-16 h-16 mx-auto mb-4 opacity-40"></i>
                <h2 class="text-xl font-bold mb-2">Nenhum agente criado</h2>
                <p class="opacity-60 mb-6">Configure um novo agente de inteligência artificial</p>
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
                    <div class="card-body agent-card-body p-5">
                        <!-- Header do Card -->
                        <div class="flex items-center gap-3 mb-4">
                            <div class="avatar placeholder">
                                <div class="w-14 h-14 rounded-full" style="background-color: <?= htmlspecialchars($agent['color']) ?>">
                                    <i data-feather="cpu" class="w-7 h-7 text-white"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-base truncate"><?= htmlspecialchars($agent['name']) ?></h3>
                                <?php if ($agent['whatsapp_number']): ?>
                                    <p class="text-xs opacity-60 truncate">
                                        <span class="uppercase font-semibold">WhatsApp:</span> <?= htmlspecialchars($agent['whatsapp_number']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Badge do Modelo -->
                        <div class="mb-3">
                            <span class="agent-badge" style="background-color: #6366f120; color: #6366f1; border: 1px solid #6366f140;">
                                <i data-feather="zap" class="w-3 h-3"></i>
                                <?= htmlspecialchars(strtoupper($agent['model'])) ?>
                            </span>
                            <span class="agent-badge ml-2" style="background-color: #9333ea20; color: #9333ea; border: 1px solid #9333ea40;">
                                Pausado
                            </span>
                        </div>

                        <!-- Estatísticas -->
                        <div class="stat-grid">
                            <div class="stat-item">
                                <div class="stat-label">Créditos gastos</div>
                                <div class="stat-value"><?= number_format($agent['credits_spent']) ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Conversas em atendimento</div>
                                <div class="stat-value"><?= $agent['conversations_count'] ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Conversas pausadas</div>
                                <div class="stat-value"><?= $agent['paused_conversations'] ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer com Ações -->
                    <div class="agent-card-footer">
                        <div class="agent-status-toggle">
                            <input
                                type="checkbox"
                                class="toggle toggle-success toggle-sm"
                                <?= $agent['status'] === 'active' ? 'checked' : '' ?>
                                onchange="toggleAgentStatus(<?= $agent['id'] ?>, '<?= $agent['status'] ?>')"
                            />
                        </div>
                        <div class="flex gap-1">
                            <button onclick="viewAgentConversations(<?= $agent['id'] ?>)" class="btn btn-ghost btn-sm btn-square" title="Ver conversas">
                                <i data-feather="message-square" class="w-4 h-4"></i>
                            </button>
                            <button onclick="editAgent(<?= $agent['id'] ?>)" class="btn btn-ghost btn-sm btn-square" title="Configurar">
                                <i data-feather="settings" class="w-4 h-4"></i>
                            </button>
                            <button onclick="deleteAgent(<?= $agent['id'] ?>, '<?= htmlspecialchars($agent['name'], ENT_QUOTES) ?>')" class="btn btn-ghost btn-sm btn-square text-error" title="Excluir">
                                <i data-feather="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Criar/Editar Agente -->
<dialog id="agentModal" class="modal">
    <div class="modal-box max-w-5xl">
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
                <div role="tabpanel" class="tab-content bg-base-100 rounded-box p-6">
                    <!-- Linha superior: Cor, Nome e WhatsApp -->
                    <div class="flex gap-4 mb-6">
                        <!-- Cor do Agente -->
                        <div class="flex flex-col items-center gap-2">
                            <div class="avatar placeholder">
                                <div id="previewAvatar" class="w-16 h-16 rounded-full cursor-pointer" style="background-color: #8b5cf6" onclick="document.getElementById('color').click()">
                                    <i data-feather="cpu" class="w-8 h-8 text-white"></i>
                                </div>
                            </div>
                            <input type="color" id="color" name="color" value="#8b5cf6" class="opacity-0 w-0 h-0" />
                        </div>

                        <!-- Nome e WhatsApp -->
                        <div class="flex-1 flex flex-col gap-3">
                            <label class="input input-bordered flex items-center gap-2">
                                <i data-feather="user" class="w-5 h-5 opacity-60"></i>
                                <input type="text" id="name" name="name" placeholder="Nome do agente" class="grow" required />
                            </label>

                            <label class="input input-bordered flex items-center gap-2">
                                <i data-feather="smartphone" class="w-5 h-5 opacity-60"></i>
                                <input type="text" id="whatsapp_number" name="whatsapp_number" placeholder="WhatsApp vinculado" class="grow" />
                            </label>
                        </div>
                    </div>

                    <!-- Instruções básicas com sidebar de anexos -->
                    <div class="flex gap-0 border border-base-300 rounded-lg overflow-hidden">
                        <div class="flex-1 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <label class="font-semibold text-sm">Instruções básicas</label>
                                <a href="#" class="text-xs link link-primary">Criar instruções com ajuda</a>
                            </div>
                            <textarea
                                id="system_instructions"
                                name="system_instructions"
                                rows="12"
                                class="textarea textarea-bordered w-full"
                                placeholder="Você é um agente chamado Romildo, seu papel é atender os clientes da Suportezi. A Suportezi é uma plataforma de atendimento com foco em uma aplicação de criação de sites com inteligência artificial, email, suporte e manutenção, além de, claro, o recurso de atendimento com IA sem a..."></textarea>

                            <!-- Botões de formatação -->
                            <div class="flex flex-wrap gap-1 mt-2">
                                <button type="button" class="btn btn-xs btn-ghost">
                                    <strong>B</strong>
                                </button>
                                <button type="button" class="btn btn-xs btn-ghost">
                                    <em>I</em>
                                </button>
                                <button type="button" class="btn btn-xs btn-ghost">H1</button>
                                <button type="button" class="btn btn-xs btn-ghost">H2</button>
                                <button type="button" class="btn btn-xs btn-ghost">H3</button>
                                <button type="button" class="btn btn-xs btn-ghost">Lista</button>
                                <button type="button" class="btn btn-xs btn-ghost">Link</button>
                                <button type="button" class="btn btn-xs btn-ghost ml-auto">
                                    <i data-feather="maximize-2" class="w-3 h-3"></i>
                                    Tela Cheia
                                </button>
                            </div>
                        </div>

                        <!-- Sidebar de Anexos -->
                        <div class="attachments-sidebar">
                            <div class="text-center mb-3">
                                <div class="flex flex-col items-center gap-2 p-3 border-2 border-dashed border-base-300 rounded-lg cursor-pointer hover:border-primary" onclick="document.getElementById('knowledge_file').click()">
                                    <i data-feather="upload" class="w-6 h-6 opacity-60"></i>
                                    <span class="text-xs opacity-60">Arraste mais arquivos aqui</span>
                                </div>
                                <input type="file" id="knowledge_file" name="knowledge_file" accept=".pdf,.txt,.csv" multiple class="hidden" onchange="handleFileUpload(this)" />
                            </div>

                            <!-- Lista de arquivos anexados -->
                            <div id="attachmentsList">
                                <!-- Exemplo de arquivo -->
                                <div class="attachment-item" draggable="true" ondragstart="handleDragStart(event, 'capa_v26.pdf')">
                                    <i data-feather="file-text" class="attachment-icon text-error"></i>
                                    <span class="attachment-name">capa_v26.pdf</span>
                                    <div class="upload-status success">
                                        <i data-feather="check-circle" class="w-3 h-3"></i>
                                        <span>Upload concluído • 4.43 MB</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aba Conhecimento -->
                <input type="radio" name="agent_tabs" role="tab" class="tab" aria-label="Conhecimento" />
                <div role="tabpanel" class="tab-content bg-base-100 rounded-box p-6">
                    <h4 class="font-semibold mb-4">Adicionar Conhecimentos</h4>
                    <div class="text-center py-12 bg-base-200 rounded-lg border-2 border-dashed border-base-300">
                        <i data-feather="upload-cloud" class="w-12 h-12 mx-auto mb-3 opacity-40"></i>
                        <p class="text-sm mb-2">Clique para fazer upload do arquivo escolhido aqui</p>
                        <p class="text-xs opacity-60">Formatos suportados: PDF, TXT, CSV (máx. 10MB por arquivo)</p>
                        <input type="file" id="knowledge_file_tab" name="knowledge_file_tab" accept=".pdf,.txt,.csv" multiple class="file-input file-input-bordered file-input-sm mt-4 max-w-xs" />
                    </div>

                    <div id="knowledgeFilesList" class="mt-4"></div>
                </div>

                <!-- Aba Configurações -->
                <input type="radio" name="agent_tabs" role="tab" class="tab" aria-label="Configurações" />
                <div role="tabpanel" class="tab-content bg-base-100 rounded-box p-6">
                    <div class="form-control mb-4">
                        <label class="input input-bordered flex items-center gap-2">
                            <i data-feather="zap" class="w-5 h-5 opacity-60"></i>
                            <select id="model" name="model" class="grow bg-transparent">
                                <option value="gpt-5-nano">GPT-5 nano</option>
                                <option value="gpt-4-turbo">GPT-4 Turbo</option>
                                <option value="gpt-4">GPT-4</option>
                                <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                            </select>
                        </label>
                        <label class="label">
                            <span class="label-text-alt text-warning">
                                <i data-feather="alert-circle" class="w-3 h-3 inline"></i>
                                Modelo mais leve e econômico do GPT-5
                            </span>
                        </label>
                    </div>

                    <h4 class="font-semibold mb-3">Opções do Agente</h4>

                    <div class="space-y-3">
                        <label class="label cursor-pointer justify-start gap-4 p-3 rounded-lg hover:bg-base-200">
                            <input type="checkbox" id="has_audio" name="has_audio" value="1" class="toggle toggle-success toggle-sm" checked />
                            <div class="flex-1">
                                <span class="font-semibold block">Ouvir áudio</span>
                                <span class="text-xs opacity-60">Consome 1 crédito por áudio</span>
                            </div>
                        </label>

                        <label class="label cursor-pointer justify-start gap-4 p-3 rounded-lg hover:bg-base-200">
                            <input type="checkbox" id="analyze_images" name="analyze_images" value="1" class="toggle toggle-success toggle-sm" checked />
                            <div class="flex-1">
                                <span class="font-semibold block">Analisar imagens</span>
                                <span class="text-xs opacity-60">Consome 1 crédito por imagem</span>
                            </div>
                        </label>

                        <label class="label cursor-pointer justify-start gap-4 p-3 rounded-lg hover:bg-base-200">
                            <input type="checkbox" id="quotes_enabled" name="quotes_enabled" value="1" class="toggle toggle-success toggle-sm" checked />
                            <div class="flex-1">
                                <span class="font-semibold block">Aparecer "Digite..." / "Gravando..."</span>
                            </div>
                        </label>

                        <label class="label cursor-pointer justify-start gap-4 p-3 rounded-lg hover:bg-base-200">
                            <input type="checkbox" id="pause_attendance" name="pause_attendance" value="1" class="toggle toggle-success toggle-sm" checked />
                            <div class="flex-1">
                                <span class="font-semibold block">Pausar agente no atendimento humano</span>
                            </div>
                        </label>

                        <label class="label cursor-pointer justify-start gap-4 p-3 rounded-lg hover:bg-base-200">
                            <input type="checkbox" id="group_messages" name="group_messages" value="1" class="toggle toggle-success toggle-sm" checked />
                            <div class="flex-1">
                                <span class="font-semibold block">Agrupar mensagens</span>
                            </div>
                        </label>
                    </div>

                    <div class="form-control mt-6">
                        <label class="label">
                            <span class="label-text font-semibold">Quantidade de mensagens de histórico</span>
                        </label>
                        <label class="input input-bordered flex items-center gap-2">
                            <i data-feather="clock" class="w-5 h-5 opacity-60"></i>
                            <input type="number" id="history_limit" name="history_limit" value="10" min="1" max="100" class="grow" />
                            <span class="text-sm opacity-60">segundos</span>
                        </label>
                        <label class="label">
                            <span class="label-text-alt opacity-60">Quanto maior, mais mensagens o agente vai lembrar, mas mais créditos consome</span>
                        </label>
                    </div>
                </div>

                <!-- Aba CRM -->
                <input type="radio" name="agent_tabs" role="tab" class="tab" aria-label="CRM" />
                <div role="tabpanel" class="tab-content bg-base-100 rounded-box p-6">
                    <div class="space-y-4">
                        <label class="input input-bordered flex items-center gap-2">
                            <i data-feather="trello" class="w-5 h-5 opacity-60"></i>
                            <select id="crm_board_id" name="crm_board_id" class="grow bg-transparent">
                                <option value="">Selecione um quadro CRM</option>
                                <?php foreach ($crmBoards as $board): ?>
                                    <option value="<?= $board['id'] ?>"><?= htmlspecialchars($board['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="input input-bordered flex items-center gap-2">
                            <i data-feather="flag" class="w-5 h-5 opacity-60"></i>
                            <input type="text" id="crm_stage" name="crm_stage" placeholder="Etapa (ex: Onboarding)" class="grow" />
                        </label>

                        <label class="input input-bordered flex items-center gap-2">
                            <i data-feather="dollar-sign" class="w-5 h-5 opacity-60"></i>
                            <input type="text" id="crm_default_value" name="crm_default_value" placeholder="Valor padrão no card (ex: R$ 0,00)" class="grow" />
                        </label>

                        <div>
                            <label class="label">
                                <span class="label-text font-semibold">Observação padrão</span>
                            </label>
                            <textarea id="crm_default_observation" name="crm_default_observation" rows="4" class="textarea textarea-bordered w-full" placeholder="Coletar o nome e mail e necessidade do cliente e adicionar ao CRM."></textarea>
                        </div>
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

// Drag and drop de arquivos
function handleDragStart(event, filename) {
    event.dataTransfer.setData('text/plain', filename);
    event.dataTransfer.effectAllowed = 'copy';
}

// Upload de arquivos
function handleFileUpload(input) {
    const files = input.files;
    const container = document.getElementById('attachmentsList');

    Array.from(files).forEach(file => {
        const item = document.createElement('div');
        item.className = 'attachment-item';
        item.draggable = true;
        item.ondragstart = (e) => handleDragStart(e, file.name);

        const size = (file.size / (1024 * 1024)).toFixed(2);

        item.innerHTML = `
            <i data-feather="file-text" class="attachment-icon text-error"></i>
            <span class="attachment-name">${file.name}</span>
            <div class="upload-status success">
                <i data-feather="check-circle" class="w-3 h-3"></i>
                <span>Upload concluído • ${size} MB</span>
            </div>
        `;

        container.appendChild(item);
        feather.replace();
    });
}

// Modal de criar agente
function showCreateAgentModal() {
    document.getElementById('modalTitle').textContent = 'Criar Novo Agente';
    document.getElementById('agentForm').reset();
    document.getElementById('agent_id').value = '';
    document.getElementById('previewAvatar').style.backgroundColor = '#8b5cf6';
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
