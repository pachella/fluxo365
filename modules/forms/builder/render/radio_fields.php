<?php
/**
 * Renderização de campos de múltipla escolha
 * Tipos: radio (com suporte a múltiplas respostas), image_choice
 */

// Campo de múltipla escolha padrão
if ($field['type'] === 'radio'):
    $field_rendered = true;
    $options = json_decode($field['options'], true) ?: [];
?>
    <div class="field-item bg-gray-50 dark:bg-zinc-700 border border-gray-200 dark:border-zinc-600 rounded-lg p-4 cursor-move relative" data-field-id="<?= $field['id'] ?>">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3 flex-1">
                <div class="text-gray-400 dark:text-zinc-500 mt-1">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fas fa-circle-dot text-gray-400 dark:text-zinc-500"></i>
                        <h3 class="font-medium text-gray-900 dark:text-zinc-100"><?= htmlspecialchars($field['label']) ?></h3>
                    </div>
                    <?php if (!empty($field['description'])): ?>
                        <p class="text-sm text-gray-600 dark:text-zinc-400 mb-1"><?= htmlspecialchars($field['description']) ?></p>
                    <?php endif; ?>
                    <p class="text-xs text-gray-500 dark:text-zinc-400">Tipo: Múltipla Escolha</p>
                    <?php if (!empty($field['placeholder'])): ?>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Placeholder: <?= htmlspecialchars($field['placeholder']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($options)): ?>
                        <div class="mt-2">
                            <p class="text-xs text-gray-500 dark:text-zinc-400 mb-1">Opções:</p>
                            <div class="flex flex-wrap gap-1">
                                <?php foreach ($options as $option): ?>
                                    <?php
                                    // Verificar se é array (com pontuação) ou string simples
                                    $label = is_array($option) ? ($option['label'] ?? '') : $option;
                                    $score = is_array($option) && isset($option['score']) ? $option['score'] : null;
                                    ?>
                                    <span class="text-xs bg-gray-200 dark:bg-zinc-600 text-gray-700 dark:text-zinc-300 px-2 py-0.5 rounded">
                                        <?= htmlspecialchars($label) ?>
                                        <?php if ($score !== null): ?>
                                            <span class="text-xs font-semibold ml-1" style="color: #4EA44B;">(<?= $score ?> pts)</span>
                                        <?php endif; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
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

        <!-- Tags no canto inferior direito -->
        <?php if ($field['required'] || (isset($field['allow_multiple']) && $field['allow_multiple'])): ?>
            <div class="absolute bottom-2 right-2 flex gap-1">
                <?php if ($field['required']): ?>
                    <span class="text-xs bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 px-2 py-0.5 rounded">Obrigatório</span>
                <?php endif; ?>
                <?php if (isset($field['allow_multiple']) && $field['allow_multiple']): ?>
                    <span class="text-xs bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400 px-2 py-0.5 rounded">Múltiplas respostas</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php
endif;

// Campo de múltipla escolha com imagem
if ($field['type'] === 'image_choice'):
    $field_rendered = true;
    $config = json_decode($field['config'], true) ?: [];
    $options = $config['options'] ?? [];
?>
    <div class="field-item bg-gray-50 dark:bg-zinc-700 border border-gray-200 dark:border-zinc-600 rounded-lg p-4 cursor-move relative" data-field-id="<?= $field['id'] ?>">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3 flex-1">
                <div class="text-gray-400 dark:text-zinc-500 mt-1">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fas fa-images text-green-600 dark:text-green-400"></i>
                        <h3 class="font-medium text-gray-900 dark:text-zinc-100"><?= htmlspecialchars($field['label']) ?></h3>
                    </div>
                    <?php if (!empty($field['description'])): ?>
                        <p class="text-sm text-gray-600 dark:text-zinc-400 mb-1"><?= htmlspecialchars($field['description']) ?></p>
                    <?php endif; ?>
                    <p class="text-xs text-gray-500 dark:text-zinc-400">Tipo: Múltipla Escolha com Imagem</p>
                    <?php if (!empty($options)): ?>
                        <div class="mt-2">
                            <p class="text-xs text-gray-500 dark:text-zinc-400 mb-1"><?= count($options) ?> opção(ões) com imagem</p>
                        </div>
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

        <!-- Tags no canto inferior direito -->
        <?php if ($field['required'] || (isset($field['allow_multiple']) && $field['allow_multiple'])): ?>
            <div class="absolute bottom-2 right-2 flex gap-1">
                <?php if ($field['required']): ?>
                    <span class="text-xs bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 px-2 py-0.5 rounded">Obrigatório</span>
                <?php endif; ?>
                <?php if (isset($field['allow_multiple']) && $field['allow_multiple']): ?>
                    <span class="text-xs bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400 px-2 py-0.5 rounded">Múltiplas respostas</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php
endif;
?>