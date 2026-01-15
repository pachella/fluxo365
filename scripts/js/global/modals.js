/**
 * Global Modal Functions
 * Templates padronizados para modais SweetAlert2
 */

/**
 * Cria template de modal para VISUALIZAÇÃO
 * Inclui cabeçalho, conteúdo e rodapé customizado (CTA à esquerda, info à direita)
 */
function createViewModal(config) {
    const classes = getThemeClasses();
    
    return `
        <div class="text-left mb-6">
            <h2 class="text-lg font-medium ${classes.title} mb-3">${config.title}</h2>
            <hr class="${classes.hr}">
        </div>
        <div class="text-left">
            ${config.content}
            ${config.footer ? `
                <div class="mt-6 pt-4 border-t ${classes.border} flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center">
                        ${config.footer.left || ''}
                    </div>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm ${classes.textMuted}">
                        ${config.footer.right || ''}
                    </div>
                </div>
            ` : ''}
        </div>
    `;
}

/**
 * Cria template de modal para FORMULÁRIOS
 * Inclui cabeçalho, conteúdo e rodapé customizado com botões
 */
function createFormModal(config) {
    const classes = getThemeClasses();
    
    return `
        <div class="text-left mb-6">
            <h2 class="text-lg font-medium ${classes.title} mb-3">${config.title}</h2>
            <hr class="${classes.hr}">
        </div>
        <div class="text-left">
            ${config.content}
            ${config.footer ? `
                <div class="mt-6 pt-4 border-t ${classes.border} flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center">
                        ${config.footer.left || ''}
                    </div>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm ${classes.textMuted}">
                        ${config.footer.right || ''}
                    </div>
                </div>
            ` : ''}
        </div>
    `;
}

/**
 * Configuração padrão para modais de confirmação
 */
function getConfirmModalConfig(title = 'Tem certeza?', text = 'Esta ação não pode ser desfeita.', confirmText = 'Sim, confirmar!') {
    return {
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancelar'
    };
}