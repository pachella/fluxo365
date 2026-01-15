<?php
// Renderizar card de divisor de fluxo
if (!isset($flow)) return;

$flowConditions = json_decode($flow['conditions'] ?? '[]', true) ?: [];
$conditionsType = $flow['conditions_type'] ?? 'all';
$conditionsCount = count($flowConditions);

$conditionsText = 'Nenhuma condição definida';
if ($conditionsCount > 0) {
    $conditionsText = $conditionsCount . ' condição' . ($conditionsCount > 1 ? 'ões' : '');
    $conditionsText .= ' (' . ($conditionsType === 'all' ? 'TODAS' : 'QUALQUER UMA') . ')';
}
?>
<div class="flow-divider-card bg-purple-50 dark:bg-purple-900/20 border-2 border-purple-300 dark:border-purple-700 rounded-lg p-4"
     data-flow-id="<?= $flow['id'] ?>"
     data-type="flow">
    <div class="flow-header flex items-start justify-between mb-3 cursor-move">
        <div class="flex items-start gap-3 flex-1">
            <div class="text-purple-600 dark:text-purple-400 mt-1">
                <i class="fas fa-code-branch text-xl"></i>
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <h3 class="font-semibold text-purple-900 dark:text-purple-100">
                        <?= htmlspecialchars($flow['label']) ?>
                    </h3>
                    <span class="text-xs px-2 py-0.5 bg-purple-200 dark:bg-purple-800 text-purple-800 dark:text-purple-200 rounded-full">
                        Divisor de Fluxo
                    </span>
                </div>
                <p class="text-sm text-purple-700 dark:text-purple-300 mb-2">
                    <i class="fas fa-filter mr-1"></i>
                    <?= $conditionsText ?>
                </p>
                <p class="text-xs text-purple-600 dark:text-purple-400 italic">
                    <i class="fas fa-info-circle mr-1"></i>
                    Arraste campos para dentro deste card para adicioná-los ao fluxo
                </p>
            </div>
        </div>
        <div class="flex gap-2">
            <button onclick="duplicateFlow(<?= $flow['id'] ?>)"
                    class="text-blue-600 dark:text-blue-400 hover:opacity-80"
                    title="Duplicar fluxo">
                <i class="fas fa-copy"></i>
            </button>
            <button onclick="editFlow(<?= $flow['id'] ?>)"
                    style="color: #9333ea;"
                    class="hover:opacity-80"
                    title="Configurar condições">
                <i class="fas fa-sliders-h"></i>
            </button>
            <button onclick="deleteFlow(<?= $flow['id'] ?>)"
                    class="text-red-600 dark:text-red-400 hover:opacity-80"
                    title="Remover fluxo">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>

    <!-- Drop Zone para campos do fluxo -->
    <div class="flow-fields-container min-h-[60px] border-2 border-dashed border-purple-300 dark:border-purple-600 rounded-lg p-3 bg-white dark:bg-zinc-800 space-y-3"
         data-flow-id="<?= $flow['id'] ?>">
        <?php if (empty($flowFields)): ?>
            <div class="flow-empty-state text-center text-purple-400 dark:text-purple-500 text-sm py-2">
                <i class="fas fa-hand-pointer mr-1"></i>
                Arraste campos aqui
            </div>
        <?php else: ?>
            <?php foreach ($flowFields as $field): ?>
                <?php
                $field_rendered = false;

                // Carregar renderização de campos de texto básicos
                include __DIR__ . '/text_fields.php';

                // Carregar renderização de campos de documentos (CPF/CNPJ)
                if (!$field_rendered) {
                    include __DIR__ . '/document_fields.php';
                }

                // Carregar renderização de campos de múltipla escolha
                if (!$field_rendered) {
                    include __DIR__ . '/radio_fields.php';
                }

                // Carregar renderização de campos de seleção (dropdown)
                if (!$field_rendered) {
                    include __DIR__ . '/select_fields.php';
                }

                // Carregar renderização de campos especiais
                if (!$field_rendered) {
                    include __DIR__ . '/special_fields.php';
                }

                // Carregar renderização de campos de arquivo e termos
                if (!$field_rendered) {
                    include __DIR__ . '/file_terms_fields.php';
                }

                // Se chegou aqui, tipo de campo desconhecido
                if (!$field_rendered):
                ?>
                    <div class="field-item bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4" data-field-id="<?= $field['id'] ?>">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                            <span class="text-red-600 dark:text-red-400">Tipo de campo desconhecido: <?= htmlspecialchars($field['type']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
