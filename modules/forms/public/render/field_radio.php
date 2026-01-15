<?php
$options = json_decode($field['options'], true);
$letters = range('A', 'Z');
$inputType = $field['allow_multiple'] ? 'checkbox' : 'radio';
$fieldNameSuffix = $field['allow_multiple'] ? '[]' : '';
?>
<div class="flex flex-wrap gap-3">
    <?php foreach ($options as $optIndex => $option): ?>
        <?php
        // Verificar se é array (com pontuação) ou string simples
        $label = is_array($option) ? ($option['label'] ?? '') : $option;
        $score = is_array($option) && isset($option['score']) ? $option['score'] : null;
        $value = is_array($option) ? json_encode($option) : $option;
        ?>
        <label class="radio-option flex items-center py-4 px-5 rounded-lg cursor-pointer transition-all" data-radio-group="<?= $fieldName ?>">
            <input type="<?= $inputType ?>"
                   name="<?= $fieldName . $fieldNameSuffix ?>"
                   value="<?= htmlspecialchars($value) ?>"
                   <?= $field['required'] && !$field['allow_multiple'] ? 'required' : '' ?>
                   class="sr-only form-<?= $inputType ?>-input">
            <span class="option-letter flex items-center justify-center mr-3 font-medium rounded-lg transition-all" style="min-width: 32px; height: 32px;"><?= $letters[$optIndex] ?></span>
            <span class="radio-label <?= $displayMode === 'one-by-one' ? 'text-xl' : 'text-lg' ?> transition-all"><?= htmlspecialchars($label) ?></span>
        </label>
    <?php endforeach; ?>
</div>

<style>
    /* Estado DESSELECIONADO */
    .radio-option {
        background-color: transparent !important;
        border: 2px solid;
        border-color: color-mix(in srgb, var(--text-color) 50%, transparent);
        border-radius: 5px;
    }

    .radio-option .radio-label {
        color: color-mix(in srgb, var(--text-color) 50%, transparent);
    }

    .radio-option .option-letter {
        background-color: transparent;
        border: 2px solid;
        border-color: color-mix(in srgb, var(--text-color) 50%, transparent);
        color: color-mix(in srgb, var(--text-color) 50%, transparent);
        border-radius: 5px;
    }

    /* Estado hover (opcional) */
    .radio-option:hover {
        border-color: color-mix(in srgb, var(--text-color) 70%, transparent);
    }

    /* Estado SELECIONADO (ativo) */
    .radio-option:has(input:checked) {
        background-color: transparent !important;
        border: 2px solid var(--primary-color);
    }

    .radio-option:has(input:checked) .radio-label {
        color: var(--primary-color);
    }

    .radio-option:has(input:checked) .option-letter {
        background-color: var(--primary-color);
        color: var(--button-text-color);
        border: none;
    }
</style>