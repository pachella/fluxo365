<?php
// ============================================
// TEMPLATES DE CONFIGURAÇÃO DINÂMICA
// Retorna HTML de configurações específicas por tipo
//
// Tipos sem config especial (retornam vazio):
// text, name, email, url, phone, textarea, money,
// number, message, welcome, select
// ============================================

$type = $_GET['type'] ?? '';

// SLIDER - Com opção de intervalo
if ($type === 'slider'):
?>
    <div id="sliderConfig" style="display: none;">
        <div class="space-y-3">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Valor mínimo</label>
                    <input type="number" name="slider_min" id="sliderMin" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Valor máximo</label>
                    <input type="number" name="slider_max" id="sliderMax" value="10" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100">
                </div>
            </div>
            
            <div class="flex items-center justify-between">
                <label class="text-sm text-gray-700 dark:text-zinc-300">Permitir intervalo (min-max)</label>
                <label class="switch">
                    <input type="checkbox" name="slider_allow_range" id="sliderAllowRange">
                    <span class="slider"></span>
                </label>
            </div>
            <p class="text-xs text-gray-500 dark:text-zinc-400">Quando ativado, o usuário poderá selecionar um intervalo de valores</p>
        </div>
    </div>
<?php
endif;

// RATING
if ($type === 'rating'):
?>
    <div id="ratingConfig" style="display: none;">
        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Número máximo de estrelas</label>
        <input type="number" name="rating_max" id="ratingMax" value="5" min="3" max="10" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100">
        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Entre 3 e 10 estrelas</p>
    </div>
<?php
endif;

// RADIO - Múltiplas respostas e pontuação
if ($type === 'radio'):
?>
    <div id="multipleAnswersContainer" style="display: none;">
        <div class="space-y-4">
            <!-- Múltiplas respostas -->
            <div>
                <div class="flex items-center justify-between">
                    <label class="text-sm text-gray-700 dark:text-zinc-300">Permitir múltiplas respostas</label>
                    <label class="switch">
                        <input type="checkbox" name="allow_multiple" id="fieldAllowMultiple">
                        <span class="slider"></span>
                    </label>
                </div>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Usuário poderá selecionar mais de uma opção</p>
            </div>

            <!-- Pontuação -->
            <div>
                <div class="flex items-center justify-between">
                    <label class="text-sm text-gray-700 dark:text-zinc-300">Ativar pontuação</label>
                    <label class="switch">
                        <input type="checkbox" id="enableScoring" onchange="toggleScoringMode()">
                        <span class="slider"></span>
                    </label>
                </div>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Atribua pontos para cada opção (útil para quizzes)</p>
            </div>

            <!-- Container de opções com pontuação (escondido por padrão) -->
            <div id="scoringOptionsContainer" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2">Opções com pontuação</label>
                <div id="scoringOptionsList" class="space-y-2 mb-2">
                    <!-- Opções serão adicionadas dinamicamente aqui -->
                </div>
                <button type="button" onclick="addScoringOption()" class="w-full px-3 py-2 border-2 border-dashed border-gray-300 dark:border-zinc-600 hover:border-indigo-500 dark:hover:border-indigo-500 text-gray-600 dark:text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg text-sm transition-colors">
                    <i class="fas fa-plus mr-1"></i> Adicionar opção
                </button>
            </div>
        </div>
    </div>
<?php
endif;

// IMAGE_CHOICE - Múltipla escolha com imagem
if ($type === 'image_choice'):
?>
    <div id="imageChoiceContainer" style="display: none;">
        <div class="space-y-4">
            <!-- Múltiplas respostas -->
            <div>
                <div class="flex items-center justify-between">
                    <label class="text-sm text-gray-700 dark:text-zinc-300">Permitir múltiplas respostas</label>
                    <label class="switch">
                        <input type="checkbox" name="allow_multiple" id="fieldAllowMultiple">
                        <span class="slider"></span>
                    </label>
                </div>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Usuário poderá selecionar mais de uma opção</p>
            </div>

            <!-- Pontuação -->
            <div>
                <div class="flex items-center justify-between">
                    <label class="text-sm text-gray-700 dark:text-zinc-300">Ativar pontuação</label>
                    <label class="switch">
                        <input type="checkbox" id="enableImageScoring" onchange="toggleImageScoringVisibility()">
                        <span class="slider"></span>
                    </label>
                </div>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Atribua pontos para cada opção (útil para quizzes)</p>
            </div>

            <!-- Container de opções com imagens -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2">Opções com imagens</label>
                <div id="imageChoiceOptionsList" class="space-y-3 mb-2">
                    <!-- Opções serão adicionadas dinamicamente aqui -->
                </div>
                <button type="button" onclick="addImageChoiceOption()" class="w-full px-3 py-2 border-2 border-dashed border-gray-300 dark:border-zinc-600 hover:border-indigo-500 dark:hover:border-indigo-500 text-gray-600 dark:text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg text-sm transition-colors">
                    <i class="fas fa-plus mr-1"></i> Adicionar opção
                </button>
            </div>
        </div>
    </div>
<?php
endif;

// FILE
if ($type === 'file'):
?>
    <div id="fileConfig" style="display: none;">
        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Tipos de arquivo permitidos</label>
        <input type="text" name="file_types" id="fileTypes" value=".pdf,.jpg,.jpeg,.png,.doc,.docx" placeholder=".pdf,.jpg,.png" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100">
        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Extensões separadas por vírgula</p>
    </div>
<?php
endif;

// DATE - Com opção de mostrar hora
if ($type === 'date'):
?>
    <div id="dateConfig" style="display: none;">
        <div class="flex items-center justify-between">
            <label class="text-sm text-gray-700 dark:text-zinc-300">Mostrar hora</label>
            <label class="switch">
                <input type="checkbox" name="date_show_time" id="dateShowTime">
                <span class="slider"></span>
            </label>
        </div>
        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Quando ativado, o campo permitirá selecionar data e hora</p>
    </div>
<?php
endif;

// RG - Com campos complementares opcionais
if ($type === 'rg'):
?>
    <div id="rgConfig" style="display: none;">
        <div class="flex items-center justify-between">
            <label class="text-sm text-gray-700 dark:text-zinc-300">Mostrar campos complementares</label>
            <label class="switch">
                <input type="checkbox" name="rg_show_complementary" id="rgShowComplementary">
                <span class="slider"></span>
            </label>
        </div>
        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Quando ativado, campos de data de nascimento, naturalidade, órgão expedidor, UF e data de expedição serão exibidos após o preenchimento do RG</p>
    </div>
<?php
endif;

// TERMS
if ($type === 'terms'):
?>
    <div id="termsConfig" style="display: none;">
        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Modo de exibição</label>
                <select name="terms_mode" id="termsMode" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100">
                    <option value="inline">Texto inline</option>
                    <option value="pdf">Link para PDF</option>
                    <option value="link">Link externo</option>
                </select>
            </div>

            <div id="termsInlineContent" class="terms-mode-content">
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Texto dos termos</label>
                <textarea name="terms_text" id="termsText" rows="4" placeholder="Digite os termos de uso aqui..." class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100"></textarea>
            </div>

            <div id="termsPdfContent" class="terms-mode-content" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">URL do PDF</label>
                <input type="url" name="terms_pdf" id="termsPdf" placeholder="https://exemplo.com/termos.pdf" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100">
            </div>

            <div id="termsLinkContent" class="terms-mode-content" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">URL externa</label>
                <input type="url" name="terms_link" id="termsLink" placeholder="https://exemplo.com/termos" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100">
            </div>
        </div>
    </div>
<?php
endif;

// LOADING - Carregamento
if ($type === 'loading'):
?>
    <div id="loadingConfig" style="display: none;">
        <div class="space-y-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg mb-4">
                <p class="text-xs text-blue-700 dark:text-blue-300">
                    <i class="fas fa-info-circle mr-1"></i>
                    <strong>Sobre:</strong> O campo Carregamento não possui título nem descrição. Apenas exibe 3 frases de impacto com barra de progresso (2s cada) e avança automaticamente após 6 segundos.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                    <i class="fas fa-quote-left mr-1"></i> Frase 1 (0-2s) *
                </label>
                <input type="text"
                       name="loading_phrase_1"
                       id="loadingPhrase1"
                       placeholder="Analisando suas respostas..."
                       value="Analisando suas respostas..."
                       maxlength="150"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                    <i class="fas fa-quote-left mr-1"></i> Frase 2 (2-4s) *
                </label>
                <input type="text"
                       name="loading_phrase_2"
                       id="loadingPhrase2"
                       placeholder="Processando informações..."
                       value="Processando informações..."
                       maxlength="150"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                    <i class="fas fa-quote-left mr-1"></i> Frase 3 (4-6s) *
                </label>
                <input type="text"
                       name="loading_phrase_3"
                       id="loadingPhrase3"
                       placeholder="Preparando resultado..."
                       value="Preparando resultado..."
                       maxlength="150"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100"
                       required>
            </div>

            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded-lg">
                <p class="text-xs text-indigo-700 dark:text-indigo-300">
                    <i class="fas fa-clock mr-1"></i>
                    <strong>Duração:</strong> Cada frase aparece por 2 segundos com barra de progresso. Total: 6 segundos. Avança automaticamente para o próximo campo.
                </p>
            </div>
        </div>
    </div>
<?php
endif;

// VSL - Video Sales Letter
if ($type === 'vsl'):
?>
    <div id="vslConfig" style="display: none;">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                    <i class="fas fa-video mr-1"></i> URL do Vídeo *
                </label>
                <input type="url"
                       name="vsl_video_url"
                       id="vslVideoUrl"
                       placeholder="https://www.youtube.com/watch?v=..."
                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100"
                       required>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                    <i class="fas fa-info-circle"></i> Suporta YouTube e Vimeo
                </p>
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label class="text-sm text-gray-700 dark:text-zinc-300">
                        <i class="fas fa-play mr-1"></i> Reprodução Automática
                    </label>
                    <label class="switch">
                        <input type="checkbox" name="vsl_autoplay" id="vslAutoplay">
                        <span class="slider"></span>
                    </label>
                </div>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                    <i class="fas fa-info-circle"></i> O vídeo começará automaticamente quando o usuário chegar nesta etapa
                </p>
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label class="text-sm text-gray-700 dark:text-zinc-300">
                        <i class="fas fa-eye-slash mr-1"></i> Esconder Controles
                    </label>
                    <label class="switch">
                        <input type="checkbox" name="vsl_hide_controls" id="vslHideControls">
                        <span class="slider"></span>
                    </label>
                </div>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                    <i class="fas fa-info-circle"></i> Remove os controles de play/pause e barra de progresso do vídeo
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                    <i class="fas fa-clock mr-1"></i> Tempo de Espera (segundos) *
                </label>
                <input type="number"
                       name="vsl_wait_time"
                       id="vslWaitTime"
                       value="0"
                       min="0"
                       max="3600"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100"
                       required>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                    <i class="fas fa-info-circle"></i> Botão de avançar será liberado após este tempo (0 = imediato)
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                    <i class="fas fa-mouse-pointer mr-1"></i> Texto do Botão
                </label>
                <input type="text"
                       name="vsl_button_text"
                       id="vslButtonText"
                       value="Continuar"
                       maxlength="50"
                       placeholder="Continuar"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100">
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                    <i class="fas fa-info-circle"></i> Personalize o texto do botão de avançar
                </p>
            </div>

            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded-lg">
                <p class="text-xs text-indigo-700 dark:text-indigo-300">
                    <i class="fas fa-lightbulb mr-1"></i>
                    <strong>Dica:</strong> O VSL é ideal para apresentar vídeos de vendas onde você quer que o visitante assista um tempo mínimo antes de avançar.
                </p>
            </div>
        </div>
    </div>
<?php
endif;

// AUDIO_MESSAGE - Mensagem de Áudio
if ($type === 'audio_message'):
?>
    <div id="audioMessageConfig" style="display: none;">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                    <i class="fas fa-microphone mr-1"></i> Arquivo de Áudio *
                </label>
                <input type="file"
                       name="audio_file"
                       id="audioFile"
                       accept="audio/*"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100">
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                    <i class="fas fa-info-circle"></i> Formatos aceitos: MP3, WAV, OGG, M4A (máx: 50MB)
                </p>
                <div id="audioPreview" class="mt-2 hidden">
                    <div class="flex items-center gap-2 p-2 bg-indigo-50 dark:bg-indigo-900/20 rounded">
                        <i class="fas fa-file-audio text-indigo-600"></i>
                        <span id="audioFileName" class="text-sm text-indigo-700 dark:text-indigo-300"></span>
                        <button type="button" onclick="removeAudio()" class="ml-auto text-red-600 hover:text-red-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <input type="hidden" name="audio_url" id="audioUrl" value="">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                    <i class="fas fa-clock mr-1"></i> Tempo de Espera (segundos) *
                </label>
                <input type="number"
                       name="audio_wait_time"
                       id="audioWaitTime"
                       value="0"
                       min="0"
                       max="3600"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100"
                       required>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                    <i class="fas fa-info-circle"></i> Botão de avançar será liberado após este tempo (0 = imediato)
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                    <i class="fas fa-mouse-pointer mr-1"></i> Texto do Botão
                </label>
                <input type="text"
                       name="audio_button_text"
                       id="audioButtonText"
                       value="Continuar"
                       maxlength="50"
                       placeholder="Continuar"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-700 dark:text-zinc-100">
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                    <i class="fas fa-info-circle"></i> Personalize o texto do botão de avançar
                </p>
            </div>

            <div class="flex items-center gap-2 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                <input type="checkbox"
                       name="audio_autoplay"
                       id="audioAutoplay"
                       class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                <label for="audioAutoplay" class="text-sm text-purple-700 dark:text-purple-300 cursor-pointer">
                    <i class="fas fa-play-circle mr-1"></i>
                    <strong>Reprodução Automática</strong> - Iniciar áudio automaticamente quando usuário chegar nesta etapa
                </label>
            </div>

            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded-lg">
                <p class="text-xs text-indigo-700 dark:text-indigo-300">
                    <i class="fas fa-lightbulb mr-1"></i>
                    <strong>Dica:</strong> Use mensagens de áudio para criar uma conexão mais pessoal com seus leads ou para dar instruções importantes.
                </p>
            </div>
        </div>
    </div>
<?php
endif;
?>