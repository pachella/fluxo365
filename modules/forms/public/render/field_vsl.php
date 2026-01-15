<?php
// Pegar configurações do VSL
$config = json_decode($field['config'] ?? '{}', true);
$videoUrl = $config['video_url'] ?? '';
$waitTime = intval($config['wait_time'] ?? 0);
$buttonText = $config['button_text'] ?? 'Continuar';
$autoplay = intval($config['autoplay'] ?? 0);
$hideControls = intval($config['hide_controls'] ?? 0);

// Processar URL do vídeo
$embedUrl = '';
$videoService = '';
$videoId = '';

if (!empty($videoUrl)) {
    // YouTube
    if (strpos($videoUrl, 'youtube.com') !== false || strpos($videoUrl, 'youtu.be') !== false) {
        $videoService = 'youtube';
        if (strpos($videoUrl, 'youtu.be/') !== false) {
            $videoId = explode('youtu.be/', $videoUrl)[1];
            $videoId = explode('?', $videoId)[0];
        } elseif (strpos($videoUrl, 'youtube.com/watch?v=') !== false) {
            parse_str(parse_url($videoUrl, PHP_URL_QUERY), $params);
            $videoId = $params['v'] ?? '';
        }
        if ($videoId) {
            // IMPORTANTE: NÃO colocar autoplay=1 aqui, será controlado via JavaScript
            $embedUrl = "https://www.youtube.com/embed/" . htmlspecialchars($videoId) . "?enablejsapi=1";
            if ($hideControls) {
                $embedUrl .= "&controls=0";
            }
        }
    }
    // Vimeo
    elseif (strpos($videoUrl, 'vimeo.com') !== false) {
        $videoService = 'vimeo';
        if (preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $matches)) {
            $videoId = $matches[1];
            // IMPORTANTE: NÃO colocar autoplay=1 aqui, será controlado via JavaScript
            $embedUrl = "https://player.vimeo.com/video/" . htmlspecialchars($videoId);
            $params = [];
            if ($hideControls) {
                $params[] = "controls=false";
            }
            if (!empty($params)) {
                $embedUrl .= "?" . implode("&", $params);
            }
        }
    }
}

// ID único para o botão deste campo
$vslId = 'vsl-' . $field['id'];
?>

<?php if (!empty($embedUrl)): ?>
    <div class="media-container mb-6 aspect-video max-w-4xl mx-auto"
         data-vsl-id="<?= $vslId ?>"
         data-vsl-wait="<?= $waitTime ?>"
         data-vsl-autoplay="<?= $autoplay ?>"
         data-vsl-service="<?= $videoService ?>"
         data-vsl-video-id="<?= $videoId ?>">
        <iframe id="<?= $vslId ?>-iframe"
                class="w-full h-full rounded-lg border border-gray-200 dark:border-zinc-700"
                src="<?= $embedUrl ?>"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen>
        </iframe>
    </div>
<?php endif; ?>

<script>
(function() {
    const vslId = '<?= $vslId ?>';
    const waitTime = <?= $waitTime ?>;
    const buttonText = <?= json_encode($buttonText) ?>;
    const autoplay = <?= $autoplay ?>;
    const videoService = '<?= $videoService ?>';

    console.log('🎬 VSL inicializado:', {
        vslId: vslId,
        waitTime: waitTime,
        autoplay: autoplay,
        service: videoService
    });

    let player = null;
    let isVideoPlaying = false;

    // Função para iniciar o vídeo via API
    function playVideo() {
        if (!autoplay || isVideoPlaying) {
            console.log('⏸️ Autoplay desativado ou vídeo já está tocando');
            return;
        }

        const iframe = document.getElementById(vslId + '-iframe');
        if (!iframe) {
            console.error('❌ Iframe não encontrado');
            return;
        }

        console.log('▶️ Iniciando reprodução automática...');

        if (videoService === 'youtube') {
            // YouTube API via postMessage
            iframe.contentWindow.postMessage('{"event":"command","func":"playVideo","args":""}', '*');
            isVideoPlaying = true;
            console.log('✅ YouTube play comando enviado');
        } else if (videoService === 'vimeo') {
            // Vimeo API via postMessage
            iframe.contentWindow.postMessage('{"method":"play"}', '*');
            isVideoPlaying = true;
            console.log('✅ Vimeo play comando enviado');
        }
    }

    // Função para bloquear o botão com temporizador
    function blockButton() {
        const vslContainer = document.querySelector('[data-vsl-id="' + vslId + '"]');
        if (!vslContainer) {
            console.error('❌ VSL container não encontrado:', vslId);
            return;
        }

        // Encontrar o slide que contém o VSL
        const slide = vslContainer.closest('.question-slide');
        if (!slide) {
            console.error('❌ Slide não encontrado');
            return;
        }

        console.log('✅ Slide encontrado');

        // Buscar o botão dentro da div de navegação
        const navigationDiv = slide.querySelector('.flex.items-center.gap-4');
        if (!navigationDiv) {
            console.error('❌ Div de navegação não encontrada');
            return;
        }

        // Encontrar o botão de avançar (não é submit)
        const button = navigationDiv.querySelector('button[type="button"]');
        if (!button) {
            console.error('❌ Botão de avançar não encontrado');
            return;
        }

        console.log('✅ Botão encontrado:', button);

        // Encontrar o texto de hint "pressione Enter"
        const hintText = slide.querySelector('.text-xs.text-gray-400');

        // Desabilitar botão inicialmente (sem alterar texto)
        button.disabled = true;
        button.classList.add('opacity-50', 'cursor-not-allowed');
        button.setAttribute('data-vsl-blocked', 'true');

        const originalHintText = hintText ? hintText.textContent : '';

        // Alterar texto do hint
        if (hintText) {
            hintText.textContent = 'Aguarde para continuar...';
        }

        // Bloquear tecla Enter no slide inteiro
        const enterBlocker = function(e) {
            if (e.key === 'Enter' && button.hasAttribute('data-vsl-blocked')) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                console.log('⛔ Enter bloqueado - aguarde o timer');
                return false;
            }
        };

        // Capturar no capturing phase para pegar antes de outros handlers
        document.addEventListener('keydown', enterBlocker, true);
        slide.addEventListener('keydown', enterBlocker, true);

        console.log('⏱️ Timer iniciado:', waitTime, 'segundos (sem exibir cronômetro)');

        // Aguardar o tempo configurado
        setTimeout(function() {
            // Habilitar botão
            button.disabled = false;
            button.classList.remove('opacity-50', 'cursor-not-allowed');
            button.removeAttribute('data-vsl-blocked');

            // Restaurar texto do hint
            if (hintText) {
                hintText.textContent = originalHintText;
            }

            // Remover bloqueios de Enter
            document.removeEventListener('keydown', enterBlocker, true);
            slide.removeEventListener('keydown', enterBlocker, true);

            console.log('✅ VSL liberado! Botão habilitado');
        }, waitTime * 1000);
    }

    // Função para inicializar quando o slide ficar visível
    function initWhenVisible() {
        const vslContainer = document.querySelector('[data-vsl-id="' + vslId + '"]');
        if (!vslContainer) {
            console.error('❌ VSL container não encontrado');
            return;
        }

        const slide = vslContainer.closest('.question-slide');
        if (!slide) {
            console.error('❌ Slide não encontrado');
            return;
        }

        // Verificar se o slide já está visível
        const isVisible = slide.style.display !== 'none';
        console.log('👁️ Slide visível?', isVisible);

        if (isVisible) {
            // Slide já está visível, iniciar imediatamente
            console.log('🚀 Slide já visível, iniciando...');

            if (waitTime > 0) {
                setTimeout(blockButton, 300);
            }

            if (autoplay) {
                setTimeout(playVideo, 800);
            }
        } else {
            // Slide não está visível, aguardar ele ficar visível
            console.log('⏳ Aguardando slide ficar visível...');

            const observer = new MutationObserver(function(mutations) {
                const nowVisible = slide.style.display !== 'none';

                if (nowVisible) {
                    console.log('✅ Slide ficou visível!');
                    observer.disconnect();

                    if (waitTime > 0) {
                        setTimeout(blockButton, 300);
                    }

                    if (autoplay) {
                        setTimeout(playVideo, 800);
                    }
                }
            });

            observer.observe(slide, {
                attributes: true,
                attributeFilter: ['style']
            });
        }
    }

    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWhenVisible);
    } else {
        // DOM já está pronto
        setTimeout(initWhenVisible, 100);
    }
})();
</script>
