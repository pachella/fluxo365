<?php
// Pegar configurações do Audio Message
$config = json_decode($field['config'] ?? '{}', true);
$audioUrl = $config['audio_url'] ?? '';
$waitTime = intval($config['wait_time'] ?? 0);
$buttonText = $config['button_text'] ?? 'Continuar';
$autoplay = intval($config['autoplay'] ?? 0);

// ID único para o player deste campo
$audioId = 'audio-' . $field['id'];
?>

<?php if (!empty($audioUrl)): ?>
    <div class="audio-message-container mb-6"
         data-audio-id="<?= $audioId ?>"
         data-audio-wait="<?= $waitTime ?>"
         data-audio-autoplay="<?= $autoplay ?>">

        <div style="display: flex; align-items: center; gap: 16px;">
            <!-- Visualizador de áudio FORA da caixa (estilo futurista/Jarvis) -->
            <div id="<?= $audioId ?>-visualizer" class="audio-visualizer-futuristic" style="display: flex; align-items: center; justify-content: center; gap: 2px; height: 50px; width: 50px; flex-shrink: 0;">
                <div class="bar"></div>
                <div class="bar"></div>
                <div class="bar"></div>
                <div class="bar"></div>
                <div class="bar"></div>
                <div class="bar"></div>
                <div class="bar"></div>
            </div>

            <!-- Player de Áudio Minimalista -->
            <div class="audio-player-wrapper" style="flex: 1; background: rgba(var(--primary-color-rgb, 99, 102, 241), 0.05); border: 1px solid rgba(var(--primary-color-rgb, 99, 102, 241), 0.2); border-radius: 16px; padding: 12px 16px;">
                <!-- Elemento de áudio oculto -->
                <audio id="<?= $audioId ?>-player" preload="auto" crossorigin="anonymous"></audio>

                <!-- Layout: Tempo | Barra | Tempo | Botões -->
                <div style="display: flex; align-items: center; gap: 12px;">
                    <!-- Tempo inicial -->
                    <span id="<?= $audioId ?>-current-time" style="font-size: 12px; opacity: 0.7; white-space: nowrap; flex-shrink: 0;">0:00</span>

                    <!-- Barra de progresso -->
                    <div id="<?= $audioId ?>-progress-bar" style="flex: 1; position: relative; height: 6px; background: rgba(var(--primary-color-rgb, 99, 102, 241), 0.15); border-radius: 3px; cursor: pointer;">
                        <div id="<?= $audioId ?>-progress" style="position: absolute; height: 100%; background: var(--primary-color, #6366f1); border-radius: 3px; width: 0%; transition: width 0.1s;"></div>
                    </div>

                    <!-- Tempo final -->
                    <span id="<?= $audioId ?>-duration" style="font-size: 12px; opacity: 0.7; white-space: nowrap; flex-shrink: 0;">0:00</span>

                    <!-- Botões -->
                    <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                        <button type="button" id="<?= $audioId ?>-play-btn"
                                style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary-color, #6366f1); color: white; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;"
                                onmouseover="this.style.opacity='0.9'"
                                onmouseout="this.style.opacity='1'">
                            <i class="fas fa-play" style="font-size: 12px; margin-left: 2px;"></i>
                        </button>

                        <button type="button" id="<?= $audioId ?>-speed-btn"
                                style="padding: 4px 8px; border-radius: 12px; background: rgba(var(--primary-color-rgb, 99, 102, 241), 0.1); border: 1px solid rgba(var(--primary-color-rgb, 99, 102, 241), 0.2); font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s; white-space: nowrap;">
                            1x
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
/* Visualizador futurista estilo Jarvis/IA */
.audio-visualizer-futuristic .bar {
    width: 2px;
    background: var(--primary-color, #6366f1);
    border-radius: 2px;
    height: 6px;
    transition: all 0.15s ease;
    opacity: 0.3;
}

.audio-visualizer-futuristic.playing .bar {
    animation: jarvisWave 1.2s ease-in-out infinite;
    opacity: 1;
}

.audio-visualizer-futuristic.playing .bar:nth-child(1) { animation-delay: 0s; }
.audio-visualizer-futuristic.playing .bar:nth-child(2) { animation-delay: 0.1s; }
.audio-visualizer-futuristic.playing .bar:nth-child(3) { animation-delay: 0.2s; }
.audio-visualizer-futuristic.playing .bar:nth-child(4) { animation-delay: 0.3s; }
.audio-visualizer-futuristic.playing .bar:nth-child(5) { animation-delay: 0.4s; }
.audio-visualizer-futuristic.playing .bar:nth-child(6) { animation-delay: 0.5s; }
.audio-visualizer-futuristic.playing .bar:nth-child(7) { animation-delay: 0.6s; }

@keyframes jarvisWave {
    0%, 100% {
        height: 6px;
        transform: scaleY(1);
    }
    25% {
        height: 35px;
        transform: scaleY(1.1);
    }
    50% {
        height: 20px;
        transform: scaleY(1);
    }
    75% {
        height: 40px;
        transform: scaleY(1.1);
    }
}
</style>

<script>
(function() {
    const audioId = '<?= $audioId ?>';
    const waitTime = <?= $waitTime ?>;
    const buttonText = <?= json_encode($buttonText) ?>;
    const autoplay = <?= $autoplay ?>;

    console.log('🎵 Audio Message inicializado:', {
        audioId: audioId,
        waitTime: waitTime,
        autoplay: autoplay
    });

    const player = document.getElementById(audioId + '-player');
    const playBtn = document.getElementById(audioId + '-play-btn');
    const speedBtn = document.getElementById(audioId + '-speed-btn');
    const progressBar = document.getElementById(audioId + '-progress-bar');
    const progress = document.getElementById(audioId + '-progress');
    const currentTimeEl = document.getElementById(audioId + '-current-time');
    const durationEl = document.getElementById(audioId + '-duration');
    const visualizer = document.getElementById(audioId + '-visualizer');

    if (!player) {
        console.error('❌ Player de áudio não encontrado');
        return;
    }

    // Definir URL do áudio com cache busting e forçar carregamento
    const audioUrl = '<?= htmlspecialchars($audioUrl) ?>';
    const cacheBuster = '?t=' + Date.now();
    player.src = audioUrl + cacheBuster;

    console.log('🎵 Audio URL:', audioUrl);
    console.log('🎵 Loading audio...');

    // Forçar carregamento do áudio
    player.load();

    let isPlaying = false;
    let currentSpeed = 1;
    const speeds = [1, 1.5, 2];
    let audioInitialized = false;

    // Formatar tempo (segundos para MM:SS)
    function formatTime(seconds) {
        if (!seconds || isNaN(seconds)) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return mins + ':' + (secs < 10 ? '0' : '') + secs;
    }

    // Eventos de debug
    player.addEventListener('loadstart', function() {
        console.log('🔄 Iniciando carregamento do áudio...');
    });

    player.addEventListener('loadeddata', function() {
        console.log('✅ Dados do áudio carregados');
    });

    // Atualizar duração quando metadados carregarem
    player.addEventListener('loadedmetadata', function() {
        audioInitialized = true;
        durationEl.textContent = formatTime(player.duration);
        console.log('✅ Áudio carregado! Duração:', player.duration + 's');
    });

    // Detectar erros de carregamento
    player.addEventListener('error', function(e) {
        console.error('❌ Erro ao carregar áudio:', e);
        console.error('❌ Código do erro:', player.error ? player.error.code : 'desconhecido');
        console.error('❌ Mensagem:', player.error ? player.error.message : 'sem mensagem');
    });

    // Atualizar progresso durante reprodução
    player.addEventListener('timeupdate', function() {
        const percent = (player.currentTime / player.duration) * 100;
        progress.style.width = percent + '%';
        currentTimeEl.textContent = formatTime(player.currentTime);
    });

    // Play/Pause
    playBtn.addEventListener('click', function() {
        if (isPlaying) {
            player.pause();
            playBtn.innerHTML = '<i class="fas fa-play" style="font-size: 12px; margin-left: 2px;"></i>';
            visualizer.classList.remove('playing');
            isPlaying = false;
            console.log('⏸️ Áudio pausado');
        } else {
            player.play();
            playBtn.innerHTML = '<i class="fas fa-pause" style="font-size: 12px;"></i>';
            visualizer.classList.add('playing');
            isPlaying = true;
            console.log('▶️ Áudio reproduzindo');
        }
    });

    // Quando áudio terminar
    player.addEventListener('ended', function() {
        playBtn.innerHTML = '<i class="fas fa-play" style="font-size: 12px; margin-left: 2px;"></i>';
        visualizer.classList.remove('playing');
        isPlaying = false;
        progress.style.width = '0%';
        player.currentTime = 0;
        console.log('✅ Áudio finalizado');
    });

    // Click na barra de progresso
    progressBar.addEventListener('click', function(e) {
        const rect = progressBar.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        player.currentTime = percent * player.duration;
        console.log('⏩ Áudio avançado para:', player.currentTime + 's');
    });

    // Controle de velocidade
    speedBtn.addEventListener('click', function() {
        const currentIndex = speeds.indexOf(currentSpeed);
        const nextIndex = (currentIndex + 1) % speeds.length;
        currentSpeed = speeds[nextIndex];
        player.playbackRate = currentSpeed;
        speedBtn.textContent = currentSpeed + 'x';
        console.log('⚡ Velocidade alterada para:', currentSpeed + 'x');
    });

    // Função para bloquear o botão com temporizador
    function blockButton() {
        const audioContainer = document.querySelector('[data-audio-id="' + audioId + '"]');
        if (!audioContainer) {
            console.error('❌ Audio container não encontrado:', audioId);
            return;
        }

        const slide = audioContainer.closest('.question-slide');
        if (!slide) {
            console.error('❌ Slide não encontrado');
            return;
        }

        const navigationDiv = slide.querySelector('.flex.items-center.gap-4');
        if (!navigationDiv) {
            console.error('❌ Div de navegação não encontrada');
            return;
        }

        const button = navigationDiv.querySelector('button[type="button"]');
        if (!button) {
            console.error('❌ Botão de avançar não encontrado');
            return;
        }

        // Encontrar o texto de hint "pressione Enter"
        const hintText = slide.querySelector('.text-xs.text-gray-400');

        console.log('✅ Botão encontrado:', button);

        // Desabilitar botão inicialmente (sem alterar texto)
        button.disabled = true;
        button.classList.add('opacity-50', 'cursor-not-allowed');
        button.setAttribute('data-audio-blocked', 'true');

        const originalHintText = hintText ? hintText.textContent : '';

        // Alterar texto do hint
        if (hintText) {
            hintText.textContent = 'Aguarde para continuar...';
        }

        // Bloquear tecla Enter
        const enterBlocker = function(e) {
            if (e.key === 'Enter' && button.hasAttribute('data-audio-blocked')) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                console.log('⛔ Enter bloqueado - aguarde o timer');
                return false;
            }
        };

        document.addEventListener('keydown', enterBlocker, true);
        slide.addEventListener('keydown', enterBlocker, true);

        console.log('⏱️ Timer iniciado:', waitTime, 'segundos (sem exibir cronômetro)');

        // Aguardar o tempo configurado
        setTimeout(function() {
            button.disabled = false;
            button.classList.remove('opacity-50', 'cursor-not-allowed');
            button.removeAttribute('data-audio-blocked');

            // Restaurar texto do hint
            if (hintText) {
                hintText.textContent = originalHintText;
            }

            document.removeEventListener('keydown', enterBlocker, true);
            slide.removeEventListener('keydown', enterBlocker, true);

            console.log('✅ Áudio liberado! Botão habilitado');
        }, waitTime * 1000);
    }

    // Função para autoplay (simplificada)
    function startAutoplay() {
        if (!autoplay || !audioInitialized) return;

        console.log('▶️ Iniciando autoplay...');
        player.play().catch(function(error) {
            console.log('⚠️ Autoplay bloqueado pelo navegador:', error);
        });
        playBtn.innerHTML = '<i class="fas fa-pause" style="font-size: 12px;"></i>';
        visualizer.classList.add('playing');
        isPlaying = true;
    }

    // Inicializar quando áudio estiver carregado E slide visível
    function checkAndInit() {
        const audioContainer = document.querySelector('[data-audio-id="' + audioId + '"]');
        if (!audioContainer) return;

        const slide = audioContainer.closest('.question-slide');
        if (!slide) return;

        const isVisible = slide.style.display !== 'none';

        if (isVisible && audioInitialized) {
            console.log('✅ Áudio pronto e visível, executando ações...');

            // Bloquear botão se necessário
            if (waitTime > 0) {
                setTimeout(blockButton, 100);
            }

            // Autoplay se habilitado
            if (autoplay) {
                setTimeout(startAutoplay, 300);
            }

            // Remover o observer se existir
            if (window['observer_' + audioId]) {
                window['observer_' + audioId].disconnect();
                delete window['observer_' + audioId];
            }
        }
    }

    // Observer para quando o slide ficar visível
    function setupVisibilityObserver() {
        const audioContainer = document.querySelector('[data-audio-id="' + audioId + '"]');
        if (!audioContainer) return;

        const slide = audioContainer.closest('.question-slide');
        if (!slide) return;

        const observer = new MutationObserver(checkAndInit);
        observer.observe(slide, {
            attributes: true,
            attributeFilter: ['style']
        });

        window['observer_' + audioId] = observer;
    }

    // Quando metadata carregar, tentar inicializar
    player.addEventListener('loadedmetadata', function() {
        checkAndInit();
    });

    // Configurar observer de visibilidade
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setupVisibilityObserver();
            checkAndInit();
        });
    } else {
        setupVisibilityObserver();
        checkAndInit();
    }
})();
</script>
