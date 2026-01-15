<?php
$config = json_decode($field['config'] ?? '{}', true);
$mode = $config['mode'] ?? 'inline';
$text = $config['text'] ?? '';
$pdf = $config['pdf'] ?? '';
$link = $config['link'] ?? '';
$fieldId = $field['id'];
?>

<div class="terms-container">
    
    <?php if ($mode === 'inline' && !empty($text)): ?>
        <!-- Modo Inline: Texto com scroll -->
        <div class="terms-text-box" style="border:1px solid color-mix(in srgb, var(--text-color) 20%, transparent) !important;">
            <div class="whitespace-pre-wrap leading-relaxed" style="opacity:0.5;color: var(--text-color) !important;"><?= nl2br(htmlspecialchars($text)) ?></div>
        </div>
    <?php elseif ($mode === 'pdf' && !empty($pdf)): ?>
        <!-- Modo PDF: Link para download -->
        <div class="terms-link-box">
            <a href="<?= htmlspecialchars($pdf) ?>" target="_blank" class="flex items-center gap-2 text-indigo-600 hover:text-indigo-700">
                <i class="fas fa-file-pdf text-2xl"></i>
                <span class="text-lg">Clique para visualizar os termos (PDF)</span>
            </a>
        </div>
    <?php elseif ($mode === 'link' && !empty($link)): ?>
        <!-- Modo Link: Link externo -->
        <div class="terms-link-box">
            <a href="<?= htmlspecialchars($link) ?>" target="_blank" class="flex items-center gap-2 text-indigo-600 hover:text-indigo-700">
                <i class="fas fa-external-link-alt text-2xl"></i>
                <span class="text-lg">Clique para ler os termos completos</span>
            </a>
        </div>
    <?php endif; ?>
    
    <!-- Checkbox de aceite -->
    <div class="terms-checkbox mt-6">
        <input type="checkbox" 
               name="<?= $fieldName ?>" 
               value="accepted"
               <?= $field['required'] ? 'required' : '' ?>
               id="terms-<?= $fieldId ?>">
        <label for="terms-<?= $fieldId ?>" class="<?= $displayMode === 'one-by-one' ? 'text-xl' : 'text-lg' ?> text-gray-900 font-semibold">
            <?= htmlspecialchars($field['label']) ?>
        </label>
    </div>
    
</div>