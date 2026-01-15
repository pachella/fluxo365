<input type="number"
       name="<?= $fieldName ?>"
       id="<?= $fieldName ?>"
       placeholder="<?= htmlspecialchars(getFieldPlaceholder('number')) ?>"
       <?= $field['required'] ? 'required' : '' ?>
       autocomplete="off"
       class="w-full px-4 py-3 text-lg border-b-2 border-gray-300 dark:border-zinc-600 bg-transparent focus:outline-none focus:border-current transition-colors"
       style="border-color: <?= $customization['text_color'] ?>; opacity: 0.3;">
<style>
    #<?= $fieldName ?>:focus {
        opacity: 1 !important;
        border-color: <?= $customization['primary_color'] ?> !important;
    }
</style>