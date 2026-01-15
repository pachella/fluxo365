<?php
// Verificar se deve mostrar hora
$config = !empty($field['config']) ? json_decode($field['config'], true) : [];
$showTime = isset($config['show_time']) && $config['show_time'] == 1;
$inputType = $showTime ? 'datetime-local' : 'date';
?>
<input type="<?= $inputType ?>"
       name="<?= $fieldName ?>"
       id="<?= $fieldName ?>"
       <?= $field['required'] ? 'required' : '' ?>
       autocomplete="off"
       class="w-full px-4 py-3 text-lg border-b-2 border-gray-300 dark:border-zinc-600 bg-transparent focus:outline-none focus:border-current transition-colors"
       style="border-color: <?= $customization['text_color'] ?>; opacity: 0.3;"
       autofocus>
<style>
    #<?= $fieldName ?>:focus {
        opacity: 1 !important;
        border-color: <?= $customization['primary_color'] ?> !important;
    }
</style>