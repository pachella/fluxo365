<?php
$config = json_decode($field['config'] ?? '{}', true);
$acceptTypes = $config['types'] ?? '.pdf,.jpg,.jpeg,.png,.doc,.docx';
?>
<div class="file-upload-area" onclick="document.getElementById('file-<?= $field['id'] ?>').click()">
    <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-3"></i>
    <p class="text-lg text-gray-700 mb-1">Clique para selecionar ou arraste o arquivo</p>
    <p class="text-sm text-gray-500">Tipos permitidos: <?= htmlspecialchars($acceptTypes) ?></p>
    <input type="file" 
           id="file-<?= $field['id'] ?>" 
           name="<?= $fieldName ?>" 
           accept="<?= htmlspecialchars($acceptTypes) ?>"
           <?= $field['required'] ? 'required' : '' ?>
           class="hidden"
           onchange="updateFileName(this)">
    <p class="file-name text-sm text-indigo-600 mt-2" style="display: none;"></p>
</div>