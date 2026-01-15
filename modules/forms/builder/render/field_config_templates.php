<?php
/**
 * Templates de configurações dinâmicas para campos do formulário
 * Cada tipo de campo pode ter configurações específicas
 */

function getFieldConfigTemplate($type) {
    switch($type) {
        case 'radio':
            return '
            <div id="multipleAnswersContainer" style="display: none;">
                <div class="flex items-center justify-between">
                    <label class="text-sm text-gray-700 dark:text-zinc-300">Permitir múltiplas respostas</label>
                    <label class="switch">
                        <input type="checkbox" name="allow_multiple" id="fieldAllowMultiple">
                        <span class="slider"></span>
                    </label>
                </div>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Usuário poderá selecionar mais de uma opção</p>
            </div>';

        case 'select': // Campo de dropdown/select
            // O campo select não tem configurações específicas além das opções, então retornamos vazio
            // para não adicionar configurações adicionais, já que as opções são gerenciadas como no radio
            return '';

        case 'slider':
        case 'range':
            return '
            <div id="sliderConfig" style="display: none;">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Valor Mínimo</label>
                        <input type="number" name="slider_min" id="sliderMin" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Valor Máximo</label>
                        <input type="number" name="slider_max" id="sliderMax" value="10" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100">
                    </div>
                </div>
                <div class="flex items-center justify-between mt-3">
                    <label class="text-sm text-gray-700 dark:text-zinc-300">Permitir intervalo</label>
                    <label class="switch">
                        <input type="checkbox" name="allow_range" id="fieldAllowRange">
                        <span class="slider"></span>
                    </label>
                </div>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Defina a escala do slider</p>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Se o intervalo estiver habilitado, o usuário poderá selecionar dois valores (mínimo e máximo)</p>
            </div>';

        case 'rating':
            return '
            <div id="ratingConfig" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Quantidade de Estrelas</label>
                <input type="number" name="rating_max" id="ratingMax" value="5" min="3" max="10" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100">
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Padrão: 5 estrelas (mínimo 3, máximo 10)</p>
            </div>';

        case 'file':
            return '
            <div id="fileConfig" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Tipos de arquivo permitidos</label>
                <input type="text" name="file_types" id="fileTypes" value=".pdf,.jpg,.jpeg,.png,.doc,.docx" placeholder=".pdf,.jpg,.png" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100">
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Separe por vírgula (ex: .pdf,.jpg,.png)</p>
            </div>';

        case 'terms':
            return '
            <div id="termsConfig" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2">Modo de exibição</label>
                <select name="terms_mode" id="termsMode" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100 mb-3">
                    <option value="inline">Texto Inline (modal)</option>
                    <option value="pdf">PDF Anexado</option>
                    <option value="link">Link Externo</option>
                </select>

                <!-- Texto Inline -->
                <div id="termsInlineContent" class="terms-mode-content">
                    <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Texto do termo</label>
                    <textarea name="terms_text" id="termsText" rows="6" placeholder="Digite o texto completo do termo de uso..." class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100"></textarea>
                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Aparecerá em um modal quando o usuário clicar</p>
                </div>

                <!-- PDF Upload -->
                <div id="termsPdfContent" class="terms-mode-content" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">URL do PDF</label>
                    <input type="url" name="terms_pdf" id="termsPdf" placeholder="https://seusite.com/termos.pdf" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100">
                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Link para o arquivo PDF dos termos</p>
                </div>

                <!-- Link Externo -->
                <div id="termsLinkContent" class="terms-mode-content" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">URL da página</label>
                    <input type="url" name="terms_link" id="termsLink" placeholder="https://seusite.com/termos" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100">
                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Link para página externa dos termos</p>
                </div>
            </div>';

        default:
            return '';
    }
}
?>