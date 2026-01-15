/**
 * Sistema de exibição padronizada de recursos PRO
 * Formtalk - Sistema de Formulários
 */

// Função global para exibir popup de recurso PRO padronizado
function showProFeature() {
    // Obter dados do usuário das variáveis globais (se existirem)
    const userName = typeof USER_NAME !== 'undefined' ? USER_NAME : (typeof window.userName !== 'undefined' ? window.userName : '');
    const userEmail = typeof USER_EMAIL !== 'undefined' ? USER_EMAIL : (typeof window.userEmail !== 'undefined' ? window.userEmail : '');

    Swal.fire({
        title: '✨ Desbloqueie todo o potencial do Formtalk',
        html: `
            <p class="text-gray-600 dark:text-gray-300 mb-4">
                Este recurso está disponível no plano PRO, que inclui:
            </p>
            <ul class="text-left text-sm text-gray-600 dark:text-gray-300 space-y-2 mb-4">
                <li>✓ Formulários ilimitados</li>
                <li>✓ Respostas ilimitadas</li>
                <li>✓ Pastas ilimitadas com cores e ícones personalizados</li>
                <li>✓ Remover marca Formtalk</li>
                <li>✓ Redirecionamento customizado</li>
                <li>✓ Lógica condicional avançada</li>
                <li>✓ Sistema de pontuação</li>
                <li>✓ Suporte prioritário</li>
            </ul>
            <p class="text-purple-600 dark:text-purple-400 font-semibold">
                Experimente grátis por 30 dias!
            </p>
        `,
        icon: 'info',
        iconColor: '#a855f7',
        showCancelButton: true,
        confirmButtonText: '🚀 Testar PRO por 30 dias',
        cancelButtonText: 'Agora não',
        confirmButtonColor: '#a855f7',
        cancelButtonColor: '#6b7280',
        customClass: {
            popup: 'rounded-xl',
            confirmButton: 'px-6 py-2.5 rounded-lg font-semibold',
            cancelButton: 'px-6 py-2.5 rounded-lg'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirecionar para checkout com dados pré-populados
            const checkoutUrl = `https://checkout.ticto.app/OEDEF53ED?name=${encodeURIComponent(userName)}&email=${encodeURIComponent(userEmail)}`;
            window.open(checkoutUrl, '_blank');
        }
    });
}

// Função para mostrar modal de upgrade (limite atingido)
function showUpgradeModal() {
    // Obter dados do usuário das variáveis globais (se existirem)
    const userName = typeof USER_NAME !== 'undefined' ? USER_NAME : (typeof window.userName !== 'undefined' ? window.userName : '');
    const userEmail = typeof USER_EMAIL !== 'undefined' ? USER_EMAIL : (typeof window.userEmail !== 'undefined' ? window.userEmail : '');

    Swal.fire({
        title: '✨ Upgrade para PRO',
        html: `
            <div class="text-left">
                <p class="mb-4 text-gray-600 dark:text-gray-300">Você atingiu o limite do plano FREE.</p>
                <p class="mb-4 text-gray-600 dark:text-gray-300">Com o plano <strong class="text-purple-600">PRO</strong>, você terá:</p>
                <ul class="list-disc list-inside space-y-2 mb-4 text-gray-600 dark:text-gray-300">
                    <li>✅ Formulários ilimitados</li>
                    <li>✅ Respostas ilimitadas</li>
                    <li>✅ Pastas ilimitadas</li>
                    <li>✅ Cores e ícones personalizados</li>
                    <li>✅ Remover marca Formtalk</li>
                    <li>✅ Redirecionamento customizado</li>
                    <li>✅ Lógica condicional avançada</li>
                    <li>✅ Sistema de pontuação</li>
                    <li>✅ E muito mais!</li>
                </ul>
                <p class="text-purple-600 dark:text-purple-400 font-semibold">
                    Experimente grátis por 30 dias!
                </p>
            </div>
        `,
        icon: 'info',
        iconColor: '#a855f7',
        confirmButtonText: '🚀 Testar PRO por 30 dias',
        showCancelButton: true,
        cancelButtonText: 'Agora não',
        confirmButtonColor: '#a855f7',
        cancelButtonColor: '#6b7280',
        customClass: {
            popup: 'rounded-xl',
            confirmButton: 'px-6 py-2.5 rounded-lg font-semibold',
            cancelButton: 'px-6 py-2.5 rounded-lg'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirecionar para checkout com dados pré-populados
            const checkoutUrl = `https://checkout.ticto.app/OEDEF53ED?name=${encodeURIComponent(userName)}&email=${encodeURIComponent(userEmail)}`;
            window.open(checkoutUrl, '_blank');
        }
    });
}
