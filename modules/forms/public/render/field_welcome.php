<?php
// ============================================
// CAMPO WELCOME - Renderização Pública
// Apenas exibe título, descrição e botão "Começar"
// ============================================

$isOneByOne = ($displayMode === 'one-by-one');
?>

<?php if ($isOneByOne): ?>
    <!-- Modo One-by-One -->
    <div class="welcome-screen">
        <?php if (!empty($field['media'])): ?>
            <div class="media-container mb-6 text-center">
                <img src="<?= htmlspecialchars($field['media']) ?>" 
                     alt="Welcome" 
                     class="max-w-full max-h-64 mx-auto object-contain rounded-lg">
            </div>
        <?php endif; ?>
        
        <p class="text-lg opacity-80 max-w-2xl mx-auto">
            <?= nl2br(htmlspecialchars($field['description'])) ?>
        </p>
    </div>
<?php else: ?>
    <!-- Modo All-at-Once -->
    <div class="welcome-section text-center py-8">
        <?php if (!empty($field['media'])): ?>
            <div class="media-container mb-6">
                <img src="<?= htmlspecialchars($field['media']) ?>" 
                     alt="Welcome" 
                     class="max-w-full max-h-64 mx-auto object-contain rounded-lg">
            </div>
        <?php endif; ?>
        
        <p class="text-xl opacity-80 max-w-3xl mx-auto">
            <?= nl2br(htmlspecialchars($field['description'])) ?>
        </p>
    </div>
<?php endif; ?>