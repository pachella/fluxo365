/**
 * Global UI Functions
 * Funções de interface e feedback para o usuário
 */

/**
 * Exibe mensagem de feedback para o usuário
 * @param {string} message - Mensagem a ser exibida
 * @param {string} type - Tipo da mensagem: 'success' ou 'error'
 * @param {number} duration - Duração em ms (padrão: 5000)
 */
function showMessage(message, type = 'success', duration = 5000) {
    const container = document.getElementById('messageContainer');
    const content = document.getElementById('messageContent');
    
    if (!container || !content) {
        console.error('Elementos messageContainer ou messageContent não encontrados');
        return;
    }
    
    container.classList.remove('hidden');
    
    const isDark = document.documentElement.classList.contains('dark');
    
    if (type === 'success') {
        content.className = `px-4 py-3 rounded-lg ${isDark ? 'bg-green-900/50 text-green-200 border border-green-800' : 'bg-green-100 text-green-800'}`;
    } else {
        content.className = `px-4 py-3 rounded-lg ${isDark ? 'bg-red-900/50 text-red-200 border border-red-800' : 'bg-red-100 text-red-800'}`;
    }
    
    content.textContent = message;
    
    setTimeout(() => {
        container.classList.add('hidden');
    }, duration);
}

/**
 * Exibe indicador de loading
 * @param {string} elementId - ID do elemento onde exibir o loading
 */
function showLoading(elementId = 'searchLoading') {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.remove('hidden');
    }
}

/**
 * Oculta indicador de loading
 * @param {string} elementId - ID do elemento de loading
 */
function hideLoading(elementId = 'searchLoading') {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.add('hidden');
    }
}