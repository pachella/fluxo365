<?php
// select_field.php

function render_select_field($field) {
    $id = htmlspecialchars($field['id'] ?? '');
    $label = htmlspecialchars($field['label'] ?? 'Campo sem título');
    $name = htmlspecialchars($field['name'] ?? ('field_' . $id));
    $required = !empty($field['required']) ? 'required' : '';
    $multiple = !empty($field['multiple']) ? 'multiple' : '';
    $options_raw = $field['options'] ?? '';

    // Converte opções em array (caso venham como string "opção 1|opção 2|opção 3")
    if (is_string($options_raw)) {
        $options = array_filter(array_map('trim', explode('|', $options_raw)));
    } elseif (is_array($options_raw)) {
        $options = $options_raw;
    } else {
        $options = [];
    }

    ob_start();
    ?>
    <div class="field field-select" data-field-id="<?php echo $id; ?>">
        <label for="<?php echo $name; ?>" class="field-label">
            <?php echo $label; ?>
            <?php if ($required): ?><span class="required">*</span><?php endif; ?>
        </label>

        <select 
            name="<?php echo $name; ?><?php echo $multiple ? '[]' : ''; ?>" 
            id="<?php echo $name; ?>" 
            class="field-input" 
            <?php echo "$required $multiple"; ?>
        >
            <?php foreach ($options as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>">
                    <?php echo htmlspecialchars($option); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if (empty($options)): ?>
            <small class="text-gray-500 italic">Nenhuma opção configurada</small>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}