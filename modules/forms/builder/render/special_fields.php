<?php
// ============================================
// RENDERIZAÇÃO DE CAMPOS ESPECIAIS NO BUILDER
// Tipos: message, welcome, slider, rating, address
// ============================================

// MESSAGE
if ($field['type'] === 'message'):
    $field_rendered = true;
?>
    <div class="field-item bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 cursor-move hover:shadow-md transition-shadow" data-field-id="<?= $field['id'] ?>">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3 flex-1">
                <div class="text-green-600 dark:text-green-400 mt-1">
                    <i class="fas fa-comment-dots text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-medium text-blue-900 dark:text-blue-100"><?= htmlspecialchars($field['label']) ?></h3>
                    </div>
                    <?php if (!empty($field['description'])): ?>
                        <p class="text-sm text-blue-800 dark:text-blue-200 mb-2"><?= htmlspecialchars($field['description']) ?></p>
                    <?php endif; ?>
                    <span class="text-xs text-blue-700 dark:text-blue-300">Mensagem • Não coleta resposta</span>
                    
                    <?php if (!empty($field['media'])): ?>
                        <div class="mt-2 flex items-center gap-2 text-xs text-green-600 dark:text-green-400">
                            <i class="fas fa-image"></i>
                            <span>Com mídia anexada</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex gap-2 ml-3">
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
<?php endif; ?>

<?php
// WELCOME
if ($field['type'] === 'welcome'):
    $field_rendered = true;
?>
    <div class="field-item bg-green-50 dark:bg-green-900/20 border border-green-300 dark:border-green-700 rounded-lg p-4 hover:shadow-md transition-shadow" data-field-id="<?= $field['id'] ?>" data-field-type="welcome" style="cursor: not-allowed;">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3 flex-1">
                <div class="text-green-600 dark:text-green-400 mt-1">
                    <i class="fas fa-hand-sparkles text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-medium text-green-900 dark:text-green-100"><?= htmlspecialchars($field['label']) ?></h3>
                        <span class="text-xs px-2 py-0.5 bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200 rounded">Boas-vindas</span>
                    </div>
                    <?php if (!empty($field['description'])): ?>
                        <p class="text-sm text-green-800 dark:text-green-200 mb-2"><?= htmlspecialchars($field['description']) ?></p>
                    <?php endif; ?>
                    <span class="text-xs text-green-700 dark:text-green-300">🔒 Este campo aparecerá sempre em primeiro lugar!</span>
                    
                    <?php if (!empty($field['media'])): ?>
                        <div class="mt-2 flex items-center gap-2 text-xs text-green-600 dark:text-green-400">
                            <i class="fas fa-image"></i>
                            <span>Com mídia anexada</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex gap-2 ml-3">
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
<?php endif; ?>

<?php
// SLIDER
if ($field['type'] === 'slider'):
    $field_rendered = true;
    $config = json_decode($field['config'] ?? '{}', true);
    $min = $config['min'] ?? 0;
    $max = $config['max'] ?? 10;
    $allowRange = ($config['allow_range'] ?? 0) == 1;
?>
    <div class="field-item bg-white dark:bg-zinc-700 border border-gray-200 dark:border-zinc-600 rounded-lg p-4 cursor-move hover:shadow-md transition-shadow" data-field-id="<?= $field['id'] ?>">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3 flex-1">
                <div class="text-gray-400 dark:text-zinc-500 mt-1">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="text-gray-400 dark:text-zinc-500 mt-1">
                    <i class="fas fa-sliders-h"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-medium text-gray-900 dark:text-zinc-100"><?= htmlspecialchars($field['label']) ?></h3>
                        <?php if ($field['required']): ?>
                            <span class="text-xs text-red-500">*obrigatório</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($field['description'])): ?>
                        <p class="text-sm text-gray-600 dark:text-zinc-400 mb-2"><?= htmlspecialchars($field['description']) ?></p>
                    <?php endif; ?>
                    <span class="text-xs text-gray-500 dark:text-zinc-500">
                        Escala de <?= $min ?> a <?= $max ?>
                        <?php if ($allowRange): ?>
                            <span class="text-green-600 dark:text-green-400"> • Permite intervalo</span>
                        <?php endif; ?>
                    </span>
                    
                    <?php if (!empty($field['media'])): ?>
                        <div class="mt-2 flex items-center gap-2 text-xs text-green-600 dark:text-green-400">
                            <i class="fas fa-image"></i>
                            <span>Com mídia anexada</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex gap-2 ml-3">
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
<?php endif; ?>

<?php
// RATING
if ($field['type'] === 'rating'):
    $field_rendered = true;
    $config = json_decode($field['config'] ?? '{}', true);
    $max = $config['max'] ?? 5;
?>
    <div class="field-item bg-white dark:bg-zinc-700 border border-gray-200 dark:border-zinc-600 rounded-lg p-4 cursor-move hover:shadow-md transition-shadow" data-field-id="<?= $field['id'] ?>">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3 flex-1">
                <div class="text-gray-400 dark:text-zinc-500 mt-1">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="text-gray-400 dark:text-zinc-500 mt-1">
                    <i class="fas fa-star"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-medium text-gray-900 dark:text-zinc-100"><?= htmlspecialchars($field['label']) ?></h3>
                        <?php if ($field['required']): ?>
                            <span class="text-xs text-red-500">*obrigatório</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($field['description'])): ?>
                        <p class="text-sm text-gray-600 dark:text-zinc-400 mb-2"><?= htmlspecialchars($field['description']) ?></p>
                    <?php endif; ?>
                    <span class="text-xs text-gray-500 dark:text-zinc-500">Avaliação de 1 a <?= $max ?> estrelas</span>
                    
                    <?php if (!empty($field['media'])): ?>
                        <div class="mt-2 flex items-center gap-2 text-xs text-green-600 dark:text-green-400">
                            <i class="fas fa-image"></i>
                            <span>Com mídia anexada</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex gap-2 ml-3">
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
<?php endif; ?>

<?php
// ADDRESS
if ($field['type'] === 'address'):
    $field_rendered = true;
?>
    <div class="field-item bg-white dark:bg-zinc-700 border border-gray-200 dark:border-zinc-600 rounded-lg p-4 cursor-move hover:shadow-md transition-shadow" data-field-id="<?= $field['id'] ?>">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3 flex-1">
                <div class="text-gray-400 dark:text-zinc-500 mt-1">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-medium text-gray-900 dark:text-zinc-100"><?= htmlspecialchars($field['label']) ?></h3>
                        <?php if ($field['required']): ?>
                            <span class="text-xs text-red-500">*obrigatório</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($field['description'])): ?>
                        <p class="text-sm text-gray-600 dark:text-zinc-400 mb-2"><?= htmlspecialchars($field['description']) ?></p>
                    <?php endif; ?>
                    <span class="text-xs text-gray-500 dark:text-zinc-500">Endereço Completo (CEP, Rua, Número, Cidade, Estado)</span>
                    
                    <?php if (!empty($field['media'])): ?>
                        <div class="mt-2 flex items-center gap-2 text-xs text-green-600 dark:text-green-400">
                            <i class="fas fa-image"></i>
                            <span>Com mídia anexada</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex gap-2 ml-3">
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
<?php endif; ?>
<?php
// VSL (Video Sales Letter)
if ($field['type'] === 'vsl'):
    $field_rendered = true;
    $config = json_decode($field['config'] ?? '{}', true);
    $videoUrl = $config['video_url'] ?? '';
    $waitTime = $config['wait_time'] ?? 0;
?>
    <div class="field-item bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4 cursor-move hover:shadow-md transition-shadow" data-field-id="<?= $field['id'] ?>">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3 flex-1">
                <div class="text-gray-400 dark:text-zinc-500 mt-1">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="text-purple-600 dark:text-purple-400 mt-1">
                    <i class="fas fa-video text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-medium text-purple-900 dark:text-purple-100"><?= htmlspecialchars($field['label']) ?: 'VSL (Video Sales Letter)' ?></h3>
                        <span class="text-xs px-2 py-0.5 bg-purple-200 dark:bg-purple-800 text-purple-800 dark:text-purple-200 rounded">✨ PRO</span>
                    </div>
                    <?php if (!empty($field['description'])): ?>
                        <p class="text-sm text-purple-800 dark:text-purple-200 mb-2"><?= htmlspecialchars($field['description']) ?></p>
                    <?php endif; ?>
                    <span class="text-xs text-purple-700 dark:text-purple-300">
                        Vídeo: <?= $videoUrl ? substr($videoUrl, 0, 40) . '...' : 'Não configurado' ?>
                        <?php if ($waitTime > 0): ?>
                            • Aguardar <?= $waitTime ?>s
                        <?php endif; ?>
                        <?php if (isset($config['autoplay']) && $config['autoplay'] == 1): ?>
                            • <i class="fas fa-play"></i> Autoplay
                        <?php endif; ?>
                        <?php if (isset($config['hide_controls']) && $config['hide_controls'] == 1): ?>
                            • <i class="fas fa-eye-slash"></i> Sem controles
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            <div class="flex gap-2 ml-3">
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
<?php endif; ?>

<?php
// AUDIO_MESSAGE (Mensagem de Áudio)
if ($field['type'] === 'audio_message'):
    $field_rendered = true;
    $config = json_decode($field['config'] ?? '{}', true);
    $audioUrl = $config['audio_url'] ?? '';
    $waitTime = $config['wait_time'] ?? 0;
    $autoplay = $config['autoplay'] ?? 0;
?>
    <div class="field-item bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4 cursor-move hover:shadow-md transition-shadow" data-field-id="<?= $field['id'] ?>">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3 flex-1">
                <div class="text-gray-400 dark:text-zinc-500 mt-1">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="text-purple-600 dark:text-purple-400 mt-1">
                    <i class="fas fa-microphone text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-medium text-purple-900 dark:text-purple-100"><?= htmlspecialchars($field['label']) ?: 'Mensagem de Áudio' ?></h3>
                    </div>
                    <?php if (!empty($field['description'])): ?>
                        <p class="text-sm text-purple-800 dark:text-purple-200 mb-2"><?= htmlspecialchars($field['description']) ?></p>
                    <?php endif; ?>
                    <span class="text-xs text-purple-700 dark:text-purple-300">
                        Áudio: <?= $audioUrl ? '✓ Configurado' : 'Não configurado' ?>
                        <?php if ($waitTime > 0): ?>
                            • Aguardar <?= $waitTime ?>s
                        <?php endif; ?>
                        <?php if ($autoplay): ?>
                            • Autoplay ativado
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            <div class="flex gap-2 ml-3">
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
<?php endif; ?>

<?php
// LOADING (Carregamento)
if ($field['type'] === 'loading'):
    $field_rendered = true;
    $config = json_decode($field['config'] ?? '{}', true);
    $phrase1 = $config['phrase_1'] ?? 'Analisando suas respostas...';
    $phrase2 = $config['phrase_2'] ?? 'Processando informações...';
    $phrase3 = $config['phrase_3'] ?? 'Preparando resultado...';
?>
    <div class="field-item bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4 cursor-move hover:shadow-md transition-shadow" data-field-id="<?= $field['id'] ?>">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-3 flex-1">
                <div class="text-gray-400 dark:text-zinc-500 mt-1">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="text-indigo-600 dark:text-indigo-400 mt-1">
                    <i class="fas fa-spinner text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-medium text-indigo-900 dark:text-indigo-100">Carregamento</h3>
                    </div>
                    <p class="text-sm text-indigo-800 dark:text-indigo-200 mb-2">
                        <span class="opacity-70">Fase 1 (0-2s):</span> <?= htmlspecialchars(substr($phrase1, 0, 30)) ?><?= strlen($phrase1) > 30 ? '...' : '' ?><br>
                        <span class="opacity-70">Fase 2 (2-4s):</span> <?= htmlspecialchars(substr($phrase2, 0, 30)) ?><?= strlen($phrase2) > 30 ? '...' : '' ?><br>
                        <span class="opacity-70">Fase 3 (4-6s):</span> <?= htmlspecialchars(substr($phrase3, 0, 30)) ?><?= strlen($phrase3) > 30 ? '...' : '' ?>
                    </p>
                    <span class="text-xs text-indigo-700 dark:text-indigo-300">Avança automaticamente após 6 segundos</span>
                </div>
            </div>
            <div class="flex gap-2 ml-3">
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
<?php endif; ?>
