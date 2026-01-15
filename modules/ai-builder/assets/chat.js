// Histórico da conversa (armazenado em memória durante a sessão)
let conversationHistory = [];

// Elementos DOM
const chatMessages = document.getElementById('chatMessages');
const chatForm = document.getElementById('chatForm');
const userInput = document.getElementById('userInput');
const sendBtn = document.getElementById('sendBtn');

// Event listener para envio de mensagem
chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const message = userInput.value.trim();
    if (!message) return;

    // Adicionar mensagem do usuário
    addUserMessage(message);

    // Limpar input
    userInput.value = '';

    // Desabilitar input enquanto processa
    setLoading(true);

    // Adicionar ao histórico
    conversationHistory.push({
        role: 'user',
        content: message
    });

    try {
        // Enviar para a API
        const response = await fetch('/modules/ai-builder/api/chat.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: message,
                history: conversationHistory
            })
        });

        const data = await response.json();

        if (data.success) {
            // Adicionar resposta da IA
            addAIMessage(data.message);

            // Adicionar ao histórico
            conversationHistory.push({
                role: 'assistant',
                content: data.message
            });

            // Se a IA sinalizou que deve criar o formulário
            if (data.shouldCreate && data.formStructure) {
                await createForm(data.formStructure);
            }
        } else {
            addErrorMessage(data.error || 'Erro ao processar mensagem');
        }
    } catch (error) {
        console.error('Erro:', error);
        addErrorMessage('Erro de conexão. Tente novamente.');
    } finally {
        setLoading(false);
    }
});

// Adicionar mensagem do usuário
function addUserMessage(text) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'flex gap-3 justify-end';
    messageDiv.innerHTML = `
        <div class="flex-1 max-w-[80%]">
            <div class="bg-[#4EA44B] text-white rounded-lg p-4">
                <p>${escapeHtml(text)}</p>
            </div>
        </div>
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-300 dark:bg-zinc-600 flex items-center justify-center text-gray-700 dark:text-zinc-200 font-semibold">
            <i class="fas fa-user"></i>
        </div>
    `;
    chatMessages.appendChild(messageDiv);
    scrollToBottom();
}

// Adicionar mensagem da IA
function addAIMessage(text) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'flex gap-3';
    messageDiv.innerHTML = `
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-[#4EA44B] flex items-center justify-center text-white font-semibold">
            AI
        </div>
        <div class="flex-1">
            <div class="bg-gray-100 dark:bg-zinc-700 rounded-lg p-4">
                <p class="text-gray-900 dark:text-zinc-100 whitespace-pre-line">${escapeHtml(text)}</p>
            </div>
        </div>
    `;
    chatMessages.appendChild(messageDiv);
    scrollToBottom();
}

// Adicionar mensagem de erro
function addErrorMessage(text) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'flex gap-3';
    messageDiv.innerHTML = `
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-red-500 flex items-center justify-center text-white">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="flex-1">
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <p class="text-red-700 dark:text-red-300">${escapeHtml(text)}</p>
            </div>
        </div>
    `;
    chatMessages.appendChild(messageDiv);
    scrollToBottom();
}

// Adicionar mensagem de sucesso com link
function addSuccessMessage(formId, formTitle) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'flex gap-3';
    messageDiv.innerHTML = `
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-white">
            <i class="fas fa-check"></i>
        </div>
        <div class="flex-1">
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <p class="text-green-700 dark:text-green-300 font-semibold mb-2">
                    ✅ Formulário "${formTitle}" criado com sucesso!
                </p>
                <div class="flex gap-2">
                    <a href="/modules/forms/builder/?id=${formId}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition-colors inline-flex items-center gap-2">
                        <i class="fas fa-edit"></i>
                        Editar Formulário
                    </a>
                    <a href="/modules/forms/list.php" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg text-sm transition-colors inline-flex items-center gap-2">
                        <i class="fas fa-list"></i>
                        Ver Todos
                    </a>
                </div>
            </div>
        </div>
    `;
    chatMessages.appendChild(messageDiv);
    scrollToBottom();
}

// Criar formulário
async function createForm(formStructure) {
    try {
        const response = await fetch('/modules/ai-builder/api/create_form.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formStructure)
        });

        const data = await response.json();

        if (data.success) {
            addSuccessMessage(data.form_id, formStructure.title);
        } else {
            addErrorMessage('Erro ao criar formulário: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('Erro ao criar formulário:', error);
        addErrorMessage('Erro ao criar formulário. Tente novamente.');
    }
}

// Controlar estado de loading
function setLoading(isLoading) {
    sendBtn.disabled = isLoading;
    userInput.disabled = isLoading;

    if (isLoading) {
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Pensando...';
    } else {
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar';
    }
}

// Scroll para o final
function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Resetar chat
function resetChat() {
    if (confirm('Deseja iniciar uma nova conversa? O histórico atual será perdido.')) {
        conversationHistory = [];
        chatMessages.innerHTML = `
            <div class="flex gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-[#4EA44B] flex items-center justify-center text-white font-semibold">
                    AI
                </div>
                <div class="flex-1">
                    <div class="bg-gray-100 dark:bg-zinc-700 rounded-lg p-4">
                        <p class="text-gray-900 dark:text-zinc-100">
                            Olá! 👋 Sou seu assistente de criação de formulários.
                        </p>
                        <p class="text-gray-900 dark:text-zinc-100 mt-2">
                            Descreva o tipo de formulário que você precisa e vou te ajudar a criar a estrutura perfeita!
                        </p>
                        <p class="text-sm text-gray-600 dark:text-zinc-400 mt-2">
                            <strong>Exemplo:</strong> "Preciso de um formulário para captar leads de uma loja de roupas"
                        </p>
                    </div>
                </div>
            </div>
        `;
        userInput.focus();
    }
}

// Escapar HTML para prevenir XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Focus no input ao carregar
userInput.focus();
