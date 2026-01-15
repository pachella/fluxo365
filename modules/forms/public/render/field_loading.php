<?php
// Pegar configurações do Loading
$config = json_decode($field['config'] ?? '{}', true);
$phrase1 = $config['phrase_1'] ?? 'Analisando suas respostas...';
$phrase2 = $config['phrase_2'] ?? 'Processando informações...';
$phrase3 = $config['phrase_3'] ?? 'Preparando resultado...';

// Cores personalizadas
$primaryColor = $customization['primary_color'] ?? '#4f46e5';
$textColor = $customization['text_color'] ?? '#000000';

// ID único para este campo
$loadingId = 'loading-' . $field['id'];
?>

<style>
/* Esconder elementos do loading */
#<?= $loadingId ?>-container .question-number,
#<?= $loadingId ?>-container .btn-primary,
#<?= $loadingId ?>-container button,
#<?= $loadingId ?>-container .flex.items-center.gap-4.mt-12 {
    display: none !important;
}
</style>

<div class="text-center py-12" id="<?= $loadingId ?>" data-loading-field="true">
    <!-- Barra de Progresso -->
    <div class="mb-8 max-w-2xl mx-auto">
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
            <div id="<?= $loadingId ?>-progress-bar"
                 class="h-full transition-all duration-[2000ms] ease-linear rounded-full"
                 style="background-color: <?= htmlspecialchars($primaryColor) ?>; width: 0%;">
            </div>
        </div>
    </div>

    <!-- Texto Animado -->
    <div id="<?= $loadingId ?>-text"
         class="text-2xl md:text-3xl font-semibold mb-6"
         style="color: <?= htmlspecialchars($textColor) ?>; min-height: 3rem;">
        <?= htmlspecialchars($phrase1) ?>
    </div>

    <!-- Spinner -->
    <div class="inline-block">
        <svg class="animate-spin h-14 w-14 md:h-16 md:w-16"
             style="color: <?= htmlspecialchars($primaryColor) ?>;"
             xmlns="http://www.w3.org/2000/svg"
             fill="none"
             viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>
</div>

<script>
(function() {
    const loadingId = '<?= $loadingId ?>';
    const loadingElement = document.getElementById(loadingId);

    if (!loadingElement) {
        console.error('Loading element not found:', loadingId);
        return;
    }

    // Encontrar o slide pai (para esconder número e botão)
    const parentSlide = loadingElement.closest('.question-slide, .field-container');
    if (parentSlide) {
        parentSlide.id = loadingId + '-container';

        // Esconder número da questão e botões
        const questionNumber = parentSlide.querySelector('.question-number');
        if (questionNumber) questionNumber.style.display = 'none';

        const buttons = parentSlide.querySelectorAll('button, .btn-primary');
        buttons.forEach(btn => btn.style.display = 'none');

        // Desabilitar enter no loading
        parentSlide.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }, true);
    }

    // Verificar se o slide está visível antes de iniciar
    function isVisible(elem) {
        return elem && elem.offsetParent !== null && getComputedStyle(elem).display !== 'none';
    }

    // Função para iniciar animação
    function startAnimation() {
        if (!isVisible(loadingElement)) {
            console.log('Loading not visible yet, waiting...');
            return;
        }

        console.log('🔄 Loading animation started for:', loadingId);

        const progressBar = document.getElementById(loadingId + '-progress-bar');
        const textElement = document.getElementById(loadingId + '-text');
        const phrases = <?= json_encode([$phrase1, $phrase2, $phrase3]) ?>;

        // Fase 1: 0-33% (2 segundos)
        if (progressBar) progressBar.style.width = '33%';
        if (textElement) textElement.textContent = phrases[0];

        setTimeout(function() {
            // Fase 2: 33-66% (2 segundos)
            if (progressBar) progressBar.style.width = '66%';
            if (textElement) textElement.textContent = phrases[1];

            setTimeout(function() {
                // Fase 3: 66-100% (2 segundos)
                if (progressBar) progressBar.style.width = '100%';
                if (textElement) textElement.textContent = phrases[2];

                // Após 2 segundos (total 6s), avançar para próximo campo
                setTimeout(function() {
                    console.log('✅ Loading complete, advancing...');
                    <?php if ($displayMode === 'one-by-one'): ?>
                        if (typeof nextQuestion === 'function') {
                            nextQuestion();
                        }
                    <?php else: ?>
                        if (parentSlide) {
                            const nextSlide = parentSlide.nextElementSibling;
                            if (nextSlide) {
                                nextSlide.scrollIntoView({ behavior: 'smooth' });
                            }
                        }
                    <?php endif; ?>
                }, 2000);
            }, 2000);
        }, 2000);
    }

    // Se estiver no modo one-by-one, esperar o slide ficar visível
    <?php if ($displayMode === 'one-by-one'): ?>
        // Verificar quando o slide fica visível
        const observer = new MutationObserver(function(mutations) {
            if (isVisible(loadingElement)) {
                observer.disconnect();
                setTimeout(startAnimation, 200); // Pequeno delay para garantir renderização
            }
        });

        observer.observe(parentSlide || loadingElement, {
            attributes: true,
            attributeFilter: ['style']
        });

        // Também verificar imediatamente
        if (isVisible(loadingElement)) {
            setTimeout(startAnimation, 200);
        }
    <?php else: ?>
        // Modo all-at-once: iniciar quando entrar na viewport
        const intersectionObserver = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    intersectionObserver.disconnect();
                    startAnimation();
                }
            });
        }, { threshold: 0.5 });

        intersectionObserver.observe(loadingElement);
    <?php endif; ?>
})();
</script>
