<?php
$config = json_decode($field['config'] ?? '{}', true);
$maxStars = $config['max'] ?? 5;
?>
<input type="hidden" name="<?= $fieldName ?>" value="" <?= $field['required'] ? 'required' : '' ?>>
<div class="rating-stars" data-field="<?= $fieldName ?>">
    <?php for ($i = 1; $i <= $maxStars; $i++): ?>
        <i class="fas fa-star star" data-value="<?= $i ?>"></i>
    <?php endfor; ?>
</div>
<p class="text-sm text-gray-500 mt-2">Clique para avaliar</p>