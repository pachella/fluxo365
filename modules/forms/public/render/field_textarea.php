<textarea name="<?= $fieldName ?>"
          id="<?= $fieldName ?>"
          rows="4"
          placeholder="<?= htmlspecialchars(getFieldPlaceholder('textarea')) ?>"
          <?= $field['required'] ? 'required' : '' ?>
          autocomplete="off"
          class="w-full px-4 py-3 text-lg border-b-2 border-gray-300 dark:border-zinc-600 bg-transparent focus:outline-none focus:border-current transition-colors resize-none"
          style="border-color: <?= $customization['text_color'] ?>; opacity: 0.3;"
          autofocus></textarea>
<style>
    #<?= $fieldName ?>:focus {
        opacity: 1 !important;
        border-color: <?= $customization['primary_color'] ?> !important;
    }
</style>