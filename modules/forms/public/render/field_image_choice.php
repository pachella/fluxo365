<?php
/**
 * Renderização de campo: Múltipla Escolha com Imagem (image_choice)
 *
 * Exibe opções como grid clean de cards com imagens
 * Suporta múltiplas respostas (checkbox) e pontuação
 */

$options = json_decode($field['options'], true);
$letters = range('A', 'Z');
$inputType = $field['allow_multiple'] ? 'checkbox' : 'radio';
$fieldNameSuffix = $field['allow_multiple'] ? '[]' : '';
?>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <?php foreach ($options as $optIndex => $option): ?>
        <?php
        $label = is_array($option) ? $option['label'] : $option;
        $image = is_array($option) ? ($option['image'] ?? '') : '';
        ?>
        <label class="image-choice-option cursor-pointer" data-image-choice-group="<?= $fieldName ?>">
            <input type="<?= $inputType ?>"
                   name="<?= $fieldName . $fieldNameSuffix ?>"
                   value="<?= htmlspecialchars($label) ?>"
                   <?= $field['required'] && !$field['allow_multiple'] ? 'required' : '' ?>
                   class="hidden image-choice-input">

            <div class="image-choice-card-wrapper relative">
                <!-- Imagem -->
                <div class="aspect-square overflow-hidden bg-transparent mb-2">
                    <?php if (!empty($image)): ?>
                        <img src="<?= htmlspecialchars($image) ?>"
                             alt="<?= htmlspecialchars($label) ?>"
                             class="w-full h-full object-cover rounded-lg transition-all duration-200"
                             style="opacity: 0.7;">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center rounded-lg bg-gray-100 dark:bg-zinc-800/30" style="opacity: 0.7;">
                            <i class="fas fa-image text-3xl text-gray-400 dark:text-zinc-600"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Label com letra -->
                <div class="flex items-center gap-2">
                    <span class="option-letter font-medium text-sm" style="color: var(--text-color); opacity: 0.7;"><?= $letters[$optIndex] ?></span>
                    <span class="image-label text-sm font-medium line-clamp-2" style="color: var(--text-color); opacity: 0.7;"><?= htmlspecialchars($label) ?></span>
                </div>

                <!-- Indicador de seleção (canto superior direito da imagem) -->
                <div class="image-choice-indicator">
                    <i class="fas fa-check-circle text-lg"></i>
                </div>
            </div>
        </label>
    <?php endforeach; ?>
</div>

<style>
    .image-choice-option {
        transition: all 0.2s ease;
    }

    /* Estado hover */
    .image-choice-option:hover .image-choice-card-wrapper img,
    .image-choice-option:hover .image-choice-card-wrapper > div:first-child {
        opacity: 0.9 !important;
    }

    /* Estado selecionado */
    .image-choice-option input:checked ~ .image-choice-card-wrapper img,
    .image-choice-option input:checked ~ .image-choice-card-wrapper > div:first-child {
        opacity: 1 !important;
        box-shadow: 0 0 0 3px var(--primary-color);
        border-radius: 0.5rem;
    }

    .image-choice-option input:checked ~ .image-choice-card-wrapper .option-letter,
    .image-choice-option input:checked ~ .image-choice-card-wrapper .image-label {
        color: var(--primary-color) !important;
        opacity: 1 !important;
        font-weight: 600;
    }

    /* Indicador de check (invisível por padrão) */
    .image-choice-indicator {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        opacity: 0;
        transform: scale(0.8);
        transition: all 0.2s ease;
        color: var(--primary-color);
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
    }

    .image-choice-option input:checked ~ .image-choice-card-wrapper .image-choice-indicator {
        opacity: 1;
        transform: scale(1);
    }

    /* Line clamp para truncar texto longo */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
