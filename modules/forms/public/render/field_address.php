<div>
    <input type="text"
           id="<?= $fieldName ?>_cep"
           name="<?= $fieldName ?>[cep]"
           placeholder="00000-000"
           <?= $field['required'] ? 'required' : '' ?>
           autocomplete="postal-code"
           class="w-full px-4 py-3 text-lg border-b-2 border-gray-300 dark:border-zinc-600 bg-transparent focus:outline-none focus:border-current transition-colors cep-mask"
           style="border-color: <?= $customization['text_color'] ?>; opacity: 0.3;"
           maxlength="9"
           data-address-trigger="<?= $fieldName ?>"
           autofocus>

    <div id="address-fields-<?= $fieldName ?>" class="address-fields" style="display: none;">
        <!-- Rua + Número na mesma linha -->
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-8">
                <input type="text"
                       id="<?= $fieldName ?>_rua"
                       name="<?= $fieldName ?>[rua]"
                       placeholder="Rua"
                       autocomplete="street-address"
                       class="w-full px-4 py-3 text-lg border-b-2 border-gray-300 dark:border-zinc-600 bg-transparent focus:outline-none focus:border-current transition-colors"
                       style="border-color: <?= $customization['text_color'] ?>; opacity: 0.3;"
                       readonly>
            </div>
            <div class="col-span-4">
                <input type="text"
                       id="<?= $fieldName ?>_numero"
                       name="<?= $fieldName ?>[numero]"
                       placeholder="Número"
                       autocomplete="off"
                       class="w-full px-4 py-3 text-lg border-b-2 border-gray-300 dark:border-zinc-600 bg-transparent focus:outline-none focus:border-current transition-colors"
                       style="border-color: <?= $customization['text_color'] ?>; opacity: 0.3;"
                       required>
            </div>
        </div>

        <!-- Complemento + Bairro na mesma linha -->
        <div class="grid grid-cols-2 gap-4">
            <input type="text"
                   id="<?= $fieldName ?>_complemento"
                   name="<?= $fieldName ?>[complemento]"
                   placeholder="Complemento"
                   autocomplete="off"
                   class="w-full px-4 py-3 text-lg border-b-2 border-gray-300 dark:border-zinc-600 bg-transparent focus:outline-none focus:border-current transition-colors"
                   style="border-color: <?= $customization['text_color'] ?>; opacity: 0.3;">
            <input type="text"
                   id="<?= $fieldName ?>_bairro"
                   name="<?= $fieldName ?>[bairro]"
                   placeholder="Bairro"
                   autocomplete="off"
                   class="w-full px-4 py-3 text-lg border-b-2 border-gray-300 dark:border-zinc-600 bg-transparent focus:outline-none focus:border-current transition-colors"
                   style="border-color: <?= $customization['text_color'] ?>; opacity: 0.3;"
                   readonly>
        </div>

        <!-- Cidade + Estado na mesma linha -->
        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-9">
                <input type="text"
                       id="<?= $fieldName ?>_cidade"
                       name="<?= $fieldName ?>[cidade]"
                       placeholder="Cidade"
                       autocomplete="address-level2"
                       class="w-full px-4 py-3 text-lg border-b-2 border-gray-300 dark:border-zinc-600 bg-transparent focus:outline-none focus:border-current transition-colors"
                       style="border-color: <?= $customization['text_color'] ?>; opacity: 0.3;"
                       readonly>
            </div>
            <div class="col-span-3">
                <input type="text"
                       id="<?= $fieldName ?>_estado"
                       name="<?= $fieldName ?>[estado]"
                       placeholder="UF"
                       autocomplete="address-level1"
                       class="w-full px-4 py-3 text-lg border-b-2 border-gray-300 dark:border-zinc-600 bg-transparent focus:outline-none focus:border-current transition-colors"
                       style="border-color: <?= $customization['text_color'] ?>; opacity: 0.3;"
                       maxlength="2"
                       readonly>
            </div>
        </div>
    </div>
</div>
<style>
    #<?= $fieldName ?>_cep:focus,
    #<?= $fieldName ?>_rua:focus,
    #<?= $fieldName ?>_numero:focus,
    #<?= $fieldName ?>_complemento:focus,
    #<?= $fieldName ?>_bairro:focus,
    #<?= $fieldName ?>_cidade:focus,
    #<?= $fieldName ?>_estado:focus {
        opacity: 1 !important;
        border-color: <?= $customization['primary_color'] ?> !important;
    }
</style>