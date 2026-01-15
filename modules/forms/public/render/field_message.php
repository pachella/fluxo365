<div class="text-center py-8">
    <h2 class="text-2xl font-bold mb-4"><?= htmlspecialchars($field['label']) ?></h2>
    <?php if (!empty($field['description'])): ?>
        <p class="text-lg mb-8"><?= htmlspecialchars($field['description']) ?></p>
    <?php endif; ?>
    
    <button type="button" 
            class="btn-primary px-8 py-3 text-lg"
            onclick="<?php if ($displayMode === 'one-by-one'): ?>nextQuestion()<?php else: ?>
                // Para modo all-at-once, avançar para o próximo elemento
                const slide = this.closest('.fade-in');
                if (slide) {
                    const currentIndex = Array.from(slide.parentElement.children).indexOf(slide);
                    const nextSlide = slide.parentElement.children[currentIndex + 1];
                    if (nextSlide) {
                        nextSlide.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            <?php endif; ?>">
        Continuar <i class="fas fa-arrow-right ml-2"></i>
    </button>
</div>