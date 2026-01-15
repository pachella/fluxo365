<?php
$config = json_decode($field['config'] ?? '{}', true);
$min = $config['min'] ?? 0;
$max = $config['max'] ?? 10;
$allowRange = isset($config['allow_range']) && $config['allow_range'];
$defaultMin = floor(($min + $max) / 3); // 1/3 do range
$defaultMax = floor(2 * ($min + $max) / 3); // 2/3 do range
?>
<?php if ($allowRange): ?>
    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between">
            <span class="text-lg text-gray-600"><?= $min ?></span>
            <span class="text-lg text-gray-600"><?= $max ?></span>
        </div>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2">Valor Mínimo</label>
                <input type="range"
                       name="<?= $fieldName ?>_min"
                       min="<?= $min ?>"
                       max="<?= $max ?>"
                       value="<?= $defaultMin ?>"
                       <?= $field['required'] ? 'required' : '' ?>
                       class="w-full"
                       oninput="updateRangeValue('<?= $fieldName ?>_min_output', this.value)">
                <div class="flex justify-between mt-1">
                    <span id="<?= $fieldName ?>_min_output" class="range-min-value"><?= $defaultMin ?></span>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2">Valor Máximo</label>
                <input type="range"
                       name="<?= $fieldName ?>_max"
                       min="<?= $min ?>"
                       max="<?= $max ?>"
                       value="<?= $defaultMax ?>"
                       <?= $field['required'] ? 'required' : '' ?>
                       class="w-full"
                       oninput="updateRangeValue('<?= $fieldName ?>_max_output', this.value)"
                       onchange="ensureRangeOrder('<?= $fieldName ?>_min', '<?= $fieldName ?>_max')">
                <div class="flex justify-between mt-1">
                    <span id="<?= $fieldName ?>_max_output" class="range-max-value"><?= $defaultMax ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateRangeValue(elementId, value) {
            const outputElement = document.getElementById(elementId);
            if (outputElement) {
                outputElement.textContent = value;
            }
        }
        
        function ensureRangeOrder(minFieldId, maxFieldId) {
            const minSlider = document.querySelector(`input[name="${minFieldId}"]`);
            const maxSlider = document.querySelector(`input[name="${maxFieldId}"]`);
            
            if (minSlider && maxSlider && parseInt(minSlider.value) > parseInt(maxSlider.value)) {
                // Trocar os valores
                const temp = minSlider.value;
                minSlider.value = maxSlider.value;
                maxSlider.value = temp;
                
                // Atualizar os displays
                updateRangeValue(minFieldId + '_output', minSlider.value);
                updateRangeValue(maxFieldId + '_output', maxSlider.value);
            }
        }
        
        // Garantir que ao carregar a página, os valores estejam corretos
        document.addEventListener('DOMContentLoaded', function() {
            ensureRangeOrder('<?= $fieldName ?>_min', '<?= $fieldName ?>_max');
        });
    </script>
<?php else: ?>
    <!-- Se não permitir intervalo, exibir como slider normal -->
    <div class="flex items-center gap-6">
        <span class="text-lg text-gray-600"><?= $min ?></span>
        <input type="range"
               name="<?= $fieldName ?>"
               min="<?= $min ?>"
               max="<?= $max ?>"
               value="<?= floor(($min + $max) / 2) ?>"
               <?= $field['required'] ? 'required' : '' ?>
               class="flex-1"
               oninput="this.nextElementSibling.textContent = this.value"
               autofocus>
        <span class="slider-value"><?= floor(($min + $max) / 2) ?></span>
        <span class="text-lg text-gray-600"><?= $max ?></span>
    </div>
<?php endif; ?>