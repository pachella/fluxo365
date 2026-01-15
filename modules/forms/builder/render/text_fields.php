<?php
// ============================================
// RENDERIZAÇÃO DE CAMPOS DE TEXTO NO BUILDER
// Tipos: text, name, email, url, phone, date, textarea, money, number
// ============================================

if (in_array($field['type'], ['text', 'name', 'email', 'url', 'phone', 'date', 'textarea', 'money', 'number'])):
    $field_rendered = true;
    
    // Definir ícones para cada tipo
    $icons = [
        'text' => 'fa-font',
        'name' => 'fa-user',
        'email' => 'fa-envelope',
        'url' => 'fa-link',
        'phone' => 'fa-phone',
        'date' => 'fa-calendar',
        'textarea' => 'fa-align-left',
        'money' => 'fa-dollar-sign',
        'number' => 'fa-hashtag'
    ];
    
    $typeLabels = [
        'text' => 'Texto Curto',
        'name' => 'Nome Completo',
        'email' => 'E-mail',
        'url' => 'URL/Website',
        'phone' => 'Telefone',
        'date' => 'Data',
        'textarea' => 'Texto Longo',
        'money' => 'Valor Monetário',
        'number' => 'Número'
    ];

    $icon = $icons[$field['type']] ?? 'fa-font';
    $typeLabel = $typeLabels[$field['type']] ?? 'Campo de Texto';

    // Verificar se é campo de data com hora
    if ($field['type'] === 'date') {
        $config = !empty($field['config']) ? json_decode($field['config'], true) : [];
        if (isset($config['show_time']) && $config['show_time'] == 1) {
            $typeLabel = 'Data e Hora';
            $icon = 'fa-calendar-alt';
        }
    }
    
    // Verificar se há mídia
    $hasMedia = !empty($field['media']);
    $mediaIcon = $hasMedia ? '<i class="fas fa-image ml-2" style="color: #4EA44B;" title="Com mídia"></i>' : '';
?>
    <div class="field-item bg-white dark:bg-zinc-700 border border-gray-200 dark:border-zinc-600 rounded-lg p-4 cursor-move hover:shadow-md transition-shadow"
         data-field-id="<?= $field['id'] ?>">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3 flex-1 min-w-0">
                <div class="text-gray-400 dark:text-zinc-500 mt-1 flex-shrink-0">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="text-gray-400 dark:text-zinc-500 mt-1 flex-shrink-0">
                    <i class="fas <?= $icon ?>"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-medium text-gray-900 dark:text-zinc-100 truncate"><?= htmlspecialchars($field['label']) ?></h3>
                        <?php if ($field['required']): ?>
                            <span class="text-red-500 text-xs">*</span>
                        <?php endif; ?>
                        <?= $mediaIcon ?>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-zinc-400"><?= $typeLabel ?></p>
                    <?php if (!empty($field['description'])): ?>
                        <p class="text-xs text-gray-600 dark:text-zinc-400 mt-1 line-clamp-2"><?= htmlspecialchars($field['description']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($field['placeholder'])): ?>
                        <p class="text-xs text-gray-400 dark:text-zinc-500 mt-1 italic">Placeholder: <?= htmlspecialchars($field['placeholder']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex gap-2 flex-shrink-0">
                <button onclick="duplicateField(<?= $field['id'] ?>)"
                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                        title="Duplicar">
                    <i class="fas fa-copy"></i>
                </button>
                <button onclick="editField(<?= $field['id'] ?>)"
                        style="color: #4EA44B;"
                        class="hover:opacity-80"
                        title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteField(<?= $field['id'] ?>)"
                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                        title="Excluir">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
<?php
endif;
?>