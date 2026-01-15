<?php
$config = json_decode($field['config'] ?? '{}', true);
$min = $config['min'] ?? 0;
$max = $config['max'] ?? 10;
$default = floor(($min + $max) / 2);
?>
<div class="flex items-center gap-6">
    <span class="text-lg text-gray-600"><?= $min ?></span>
    <input type="range" 
           name="<?= $fieldName ?>" 
           min="<?= $min ?>" 
           max="<?= $max ?>" 
           value="<?= $default ?>"
           <?= $field['required'] ? 'required' : '' ?>
           class="flex-1"
           oninput="this.nextElementSibling.textContent = this.value"
           autofocus>
    <span class="slider-value"><?= $default ?></span>
    <span class="text-lg text-gray-600"><?= $max ?></span>
</div>