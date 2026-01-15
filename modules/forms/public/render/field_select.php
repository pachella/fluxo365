<?php
$options = json_decode($field['options'], true);
?>
<select name="<?= $fieldName ?>"
        <?= $field['required'] ? 'required' : '' ?>
        class="w-full <?= $displayMode === 'one-by-one' ? 'text-xl' : 'text-lg' ?> px-4 py-3 border border-gray-300 dark:border-zinc-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100">
    <option value="" disabled selected>Selecione uma opção</option>
    <?php foreach ($options as $option): ?>
        <?php
        // Verificar se é array (com pontuação) ou string simples
        $label = is_array($option) ? ($option['label'] ?? '') : $option;
        $value = is_array($option) ? json_encode($option) : $option;
        ?>
        <option value="<?= htmlspecialchars($value) ?>" class="bg-white dark:bg-zinc-800"><?= htmlspecialchars($label) ?></option>
    <?php endforeach; ?>
</select>