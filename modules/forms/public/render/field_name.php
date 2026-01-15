<input type="text"
       name="<?= $fieldName ?>"
       id="<?= $fieldName ?>"
       data-field-type="name"
       placeholder="<?= htmlspecialchars(getFieldPlaceholder('name')) ?>"
       <?= $field['required'] ? 'required' : '' ?>
       autocomplete="name"
       class="w-full px-4 py-3 text-lg border-b-2 border-gray-300 dark:border-zinc-600 bg-transparent focus:outline-none focus:border-current transition-colors"
       style="border-color: <?= $customization['text_color'] ?>; opacity: 0.3;"
       autofocus>
<style>
    #<?= $fieldName ?>:focus {
        opacity: 1 !important;
        border-color: <?= $customization['primary_color'] ?> !important;
    }
</style>