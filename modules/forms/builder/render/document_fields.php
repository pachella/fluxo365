<?php
/**
 * Renderização de campos de documentos brasileiros
 * Tipos: cpf, cnpj, rg
 */

if (in_array($field['type'], ['cpf', 'cnpj', 'rg'])):
    $typeIcons = [
        'cpf' => 'fa-id-card',
        'cnpj' => 'fa-building',
        'rg' => 'fa-address-card'
    ];
    $typeLabels = [
        'cpf' => 'CPF',
        'cnpj' => 'CNPJ',
        'rg' => 'RG'
    ];
    $icon = $typeIcons[$field['type']] ?? 'fa-question';
    $typeLabel = $typeLabels[$field['type']] ?? strtoupper($field['type']);

    // Decodificar config para campos complementares do RG
    $config = !empty($field['config']) ? json_decode($field['config'], true) : [];
    $hasComplementaryFields = $field['type'] === 'rg' && !empty($config['show_complementary_fields']);

    $field_rendered = true;
?>
    <div class="field-item bg-gray-50 dark:bg-zinc-700 border border-gray-200 dark:border-zinc-600 rounded-lg p-4 cursor-move" data-field-id="<?= $field['id'] ?>">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3 flex-1">
                <div class="text-gray-400 dark:text-zinc-500 mt-1">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fas <?= $icon ?> text-gray-400 dark:text-zinc-500"></i>
                        <h3 class="font-medium text-gray-900 dark:text-zinc-100"><?= htmlspecialchars($field['label']) ?></h3>
                        <?php if ($field['required']): ?>
                            <span class="text-xs bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 px-2 py-0.5 rounded">Obrigatório</span>
                        <?php endif; ?>
                        <span class="text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 px-2 py-0.5 rounded">✨ PRO</span>
                    </div>
                    <?php if (!empty($field['description'])): ?>
                        <p class="text-sm text-gray-600 dark:text-zinc-400 mb-1"><?= htmlspecialchars($field['description']) ?></p>
                    <?php endif; ?>
                    <p class="text-xs text-gray-500 dark:text-zinc-400">Tipo: <?= $typeLabel ?> (com validação e máscara automática)</p>
                    <?php if ($hasComplementaryFields): ?>
                        <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                            <i class="fas fa-plus-circle"></i> Com campos complementares (data nasc., naturalidade, órgão expedidor, etc.)
                        </p>
                    <?php endif; ?>
                    <?php if ($field['placeholder']): ?>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Placeholder: <?= htmlspecialchars($field['placeholder']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="duplicateField(<?= $field['id'] ?>)"
                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                        title="Duplicar">
                    <i class="fas fa-copy"></i>
                </button>
                <button onclick="editField(<?= $field['id'] ?>)" style="color: #4EA44B;" class="hover:opacity-80" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteField(<?= $field['id'] ?>)" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Excluir">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
<?php 
endif;
?>