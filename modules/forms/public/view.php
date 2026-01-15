<?php
session_start();

// Header UTF-8 para suportar emojis
header('Content-Type: text/html; charset=UTF-8');

// Headers anti-cache para garantir que a página sempre seja recarregada
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Data no passado

require_once(__DIR__ . "/../../../core/db.php");
require_once(__DIR__ . "/../../../core/cache_helper.php");
require_once(__DIR__ . "/../helpers/field_placeholders.php");

// Função helper para renderizar mídia
function renderMedia($mediaData) {
    if (empty($mediaData)) {
        return '';
    }

    // Tentar decodificar como JSON
    $media = json_decode($mediaData, true);

    // Se é JSON válido
    if ($media && isset($media['type'])) {
        if ($media['type'] === 'video') {
            $url = $media['url'] ?? '';
            $service = $media['service'] ?? 'direct';

            if ($service === 'youtube') {
                // Extrair ID do YouTube
                $videoId = '';
                if (strpos($url, 'youtu.be/') !== false) {
                    $videoId = explode('youtu.be/', $url)[1];
                    $videoId = explode('?', $videoId)[0];
                } elseif (strpos($url, 'youtube.com/watch?v=') !== false) {
                    parse_str(parse_url($url, PHP_URL_QUERY), $params);
                    $videoId = $params['v'] ?? '';
                }

                if ($videoId) {
                    return '<div class="media-container mb-4 aspect-video">
                        <iframe class="w-full h-full rounded-lg border border-gray-200 dark:border-zinc-700"
                                src="https://www.youtube.com/embed/' . htmlspecialchars($videoId) . '"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen>
                        </iframe>
                    </div>';
                }
            } elseif ($service === 'vimeo') {
                // Extrair ID do Vimeo
                $videoId = '';
                if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
                    $videoId = $matches[1];
                }

                if ($videoId) {
                    return '<div class="media-container mb-4 aspect-video">
                        <iframe class="w-full h-full rounded-lg border border-gray-200 dark:border-zinc-700"
                                src="https://player.vimeo.com/video/' . htmlspecialchars($videoId) . '"
                                frameborder="0"
                                allow="autoplay; fullscreen; picture-in-picture"
                                allowfullscreen>
                        </iframe>
                    </div>';
                }
            } else {
                // Vídeo direto
                return '<div class="media-container mb-4 aspect-video">
                    <video controls class="w-full h-full rounded-lg border border-gray-200 dark:border-zinc-700">
                        <source src="' . htmlspecialchars($url) . '" type="video/mp4">
                        Seu navegador não suporta vídeos.
                    </video>
                </div>';
            }
        } elseif ($media['type'] === 'image') {
            $url = $media['url'] ?? '';
            return '<div class="media-container mb-4 text-center">
                <img src="' . htmlspecialchars($url) . '"
                     alt="Imagem do campo"
                     class="max-w-full h-auto rounded-lg border border-gray-200 dark:border-zinc-700">
            </div>';
        }
    } else {
        // Compatibilidade com formato antigo (apenas URL)
        $extension = strtolower(pathinfo($mediaData, PATHINFO_EXTENSION));
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return '<div class="media-container mb-4 text-center">
                <img src="' . htmlspecialchars($mediaData) . '"
                     alt="Imagem do campo"
                     class="max-w-full h-auto rounded-lg border border-gray-200 dark:border-zinc-700">
            </div>';
        } else {
            return '<div class="media-container mb-4 aspect-video">
                <video controls class="w-full h-full rounded-lg border border-gray-200 dark:border-zinc-700">
                    <source src="' . htmlspecialchars($mediaData) . '" type="video/mp4">
                    Seu navegador não suporta vídeos.
                </video>
            </div>';
        }
    }

    return '';
}

$formId = $_GET['id'] ?? null;

if (!$formId) {
    http_response_code(404);
    die("Formulário não encontrado");
}

// Buscar dados do formulário (sem filtro de status inicialmente)
$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = :id");
$stmt->execute([':id' => $formId]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) {
    http_response_code(404);
    die("Formulário não encontrado");
}

// Buscar personalização do formulário ANTES de verificar bloqueio (para usar cores/fontes na tela de bloqueio)
$customStmt = $pdo->prepare("SELECT * FROM form_customizations WHERE form_id = :form_id");
$customStmt->execute([':form_id' => $formId]);
$customization = $customStmt->fetch(PDO::FETCH_ASSOC);

// Valores padrão caso não tenha personalização
if (!$customization) {
    $customization = [
        'background_color' => '#ffffff',
        'text_color' => '#000000',
        'primary_color' => '#4f46e5',
        'button_text_color' => '#ffffff',
        'hide_formtalk_branding' => 0,
        'font_family' => 'Inter',
        'content_alignment' => 'center'
    ];
} else {
    // Garantir que campos existem (fallback para registros antigos)
    $customization['button_text_color'] = $customization['button_text_color'] ?? '#ffffff';
    $customization['font_family'] = $customization['font_family'] ?? 'Inter';
    $customization['hide_formtalk_branding'] = $customization['hide_formtalk_branding'] ?? 0;
    $customization['content_alignment'] = $customization['content_alignment'] ?? 'center';
}

// Definir classe de alinhamento de texto baseado na configuração
// Container continua centralizado (mx-auto), mas o conteúdo interno alinha conforme escolha
$textAlignmentClass = 'text-' . ($customization['content_alignment'] ?? 'center');

// Definir classe de justificação para botões (flex justify)
$buttonJustifyClass = match($customization['content_alignment'] ?? 'center') {
    'left' => 'justify-start',
    'right' => 'justify-end',
    default => 'justify-center'
};

// Se o formulário estiver em rascunho, apenas o criador pode visualizar
if ($form['status'] === 'rascunho') {
    // Verificar se o usuário está logado e é o dono do formulário
    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $form['user_id']) {
        http_response_code(403);
        die("Este formulário está em rascunho e não está disponível publicamente");
    }
}

// Verificar se o formulário está bloqueado
$isBlocked = false;
$blockingMessage = '';

// Debug: Log dos valores de bloqueio
error_log("=== DEBUG BLOQUEIO ===");
error_log("Form ID: $formId");
error_log("blocking_enabled value: " . var_export($form['blocking_enabled'] ?? null, true));
error_log("blocking_enabled type: " . gettype($form['blocking_enabled'] ?? null));
error_log("blocking_type: " . var_export($form['blocking_type'] ?? null, true));
error_log("blocking_date: " . var_export($form['blocking_date'] ?? null, true));
error_log("blocking_response_limit: " . var_export($form['blocking_response_limit'] ?? null, true));
error_log("blocking_message: " . var_export($form['blocking_message'] ?? null, true));

// Mostrar TODAS as chaves do array $form
error_log("Chaves disponíveis em \$form: " . implode(', ', array_keys($form)));

if (isset($form['blocking_enabled']) && $form['blocking_enabled'] == 1) {
    error_log("✓ Bloqueio está ATIVADO");

    if ($form['blocking_type'] === 'date' && !empty($form['blocking_date'])) {
        // Bloqueio por data e hora
        $blockingDateTime = strtotime($form['blocking_date']);
        $currentDateTime = time();

        error_log("Tipo: DATA - Bloqueio: " . date('Y-m-d H:i:s', $blockingDateTime) . " | Atual: " . date('Y-m-d H:i:s', $currentDateTime));

        if ($currentDateTime >= $blockingDateTime) {
            $isBlocked = true;
            $blockingMessage = !empty($form['blocking_message'])
                ? $form['blocking_message']
                : 'Este formulário não está mais aceitando respostas.';
            error_log("FORMULÁRIO BLOQUEADO POR DATA");
        }
    } elseif ($form['blocking_type'] === 'responses' && !empty($form['blocking_response_limit'])) {
        // Bloqueio por número de respostas
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM form_responses WHERE form_id = :form_id");
        $countStmt->execute([':form_id' => $formId]);
        $responseCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        error_log("Tipo: RESPOSTAS - Limite: " . $form['blocking_response_limit'] . " | Atual: $responseCount");

        if ($responseCount >= $form['blocking_response_limit']) {
            $isBlocked = true;
            $blockingMessage = !empty($form['blocking_message'])
                ? $form['blocking_message']
                : 'Este formulário atingiu o limite de respostas e não está mais aceitando novas submissões.';
            error_log("FORMULÁRIO BLOQUEADO POR RESPOSTAS");
        }
    }
} else {
    error_log("Bloqueio está DESATIVADO ou não configurado");
}

// Se estiver bloqueado, mostrar mensagem e parar execução
if ($isBlocked) {
    // Permitir que o dono visualize mesmo bloqueado
    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $form['user_id']) {
        // Converter cor hex para rgb para opacidade
        function hexToRgb($hex) {
            $hex = ltrim($hex, '#');
            if (strlen($hex) == 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            return [
                'r' => hexdec(substr($hex, 0, 2)),
                'g' => hexdec(substr($hex, 2, 2)),
                'b' => hexdec(substr($hex, 4, 2))
            ];
        }

        $rgb = hexToRgb($customization['text_color']);
        $textColorWithOpacity = "rgba({$rgb['r']}, {$rgb['g']}, {$rgb['b']}, 0.5)";
        $hideBranding = $customization['hide_formtalk_branding'] ?? 0;
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($form['title']) ?> - Formulário fechado</title>
            <link rel="icon" type="image/webp" href="https://formtalk.app/wp-content/uploads/2025/11/cropped-favicon-20251107044740-32x32.webp">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=<?= urlencode($customization['font_family']) ?>:wght@400;500;600;700&display=swap" rel="stylesheet">
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    font-family: '<?= $customization['font_family'] ?>', sans-serif;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-center;
                    background-color: <?= $customization['background_color'] ?>;
                    padding: 2rem;
                }
                .text-center {
                    text-align: center;
                }
                .fade-in {
                    animation: fadeIn 0.6s ease-out;
                }
                @keyframes fadeIn {
                    from {
                        opacity: 0;
                        transform: translateY(20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                @keyframes bounceInUp {
                    0% {
                        opacity: 0;
                        transform: translate(-50%, 100px);
                    }
                    60% {
                        opacity: 1;
                        transform: translate(-50%, -10px);
                    }
                    80% {
                        transform: translate(-50%, 5px);
                    }
                    100% {
                        opacity: 1;
                        transform: translate(-50%, 0);
                    }
                }
                #formtalkBadge {
                    animation: bounceInUp 0.8s ease-out forwards;
                    left: 50%;
                }
                .text-4xl {
                    font-size: 2.25rem;
                }
                .text-xl {
                    font-size: 1.25rem;
                }
                .font-bold {
                    font-weight: 700;
                }
                .text-gray-900 {
                    color: #111827;
                }
                .text-gray-600 {
                    color: #4b5563;
                }
                .mb-3 {
                    margin-bottom: 0.75rem;
                }
                .mb-6 {
                    margin-bottom: 1.5rem;
                }
            </style>
        </head>
        <body>
            <div class="text-center fade-in">
                <div style="display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.5rem; width: 120px; height: 120px;">
                    <div style="width: 5rem; height: 5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; background-color: <?= $customization['primary_color'] ?>;">
                        <i class="fas fa-lock" style="font-size: 2.25rem; color: <?= $customization['button_text_color'] ?>;"></i>
                    </div>
                </div>
                <h2 class="text-4xl font-bold text-gray-900 mb-3">Formulário fechado!</h2>
                <p class="text-xl text-gray-600 mb-6"><?= nl2br(htmlspecialchars($blockingMessage)) ?></p>
            </div>

            <?php if (!$hideBranding): ?>
            <div id="formtalkBadge" style="position: fixed; bottom: 2rem; z-index: 50;">
                <a href="https://formtalk.app" target="_blank" rel="noopener noreferrer"
                   style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.25rem; background: transparent; border-radius: 9999px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border: 1px solid <?= $textColorWithOpacity ?>; transition: all 0.2s; text-decoration: none;"
                   onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 20px 25px -5px rgba(0, 0, 0, 0.1)'"
                   onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 10px 15px -3px rgba(0, 0, 0, 0.1)'">
                    <span style="font-size: 0.875rem; font-weight: 500; color: <?= $textColorWithOpacity ?>;">Gostou deste formulário?</span>
                    <span style="font-size: 0.875rem; font-weight: 600; color: <?= $textColorWithOpacity ?>;">
                        Crie um igual a este grátis!
                    </span>
                    <i class="fas fa-arrow-right" style="font-size: 0.75rem; color: #4EA44B;"></i>
                </a>
            </div>
            <?php endif; ?>
        </body>
        </html>
        <?php
        exit;
    }
}

// Adicionar campos adicionais de personalização (valores padrão já foram definidos antes)
// Campos necessários para o resto do formulário
if ($customization) {
    $customization['background_image'] = $customization['background_image'] ?? '';
    $customization['logo'] = $customization['logo'] ?? '';
    $customization['button_radius'] = $customization['button_radius'] ?? 8;
    $customization['success_message_title'] = $customization['success_message_title'] ?? 'Tudo certo!';
    $customization['success_message_description'] = $customization['success_message_description'] ?? 'Obrigado por responder nosso formulário.';
    $customization['success_redirect_enabled'] = $customization['success_redirect_enabled'] ?? 0;
    $customization['success_redirect_url'] = $customization['success_redirect_url'] ?? '';
    $customization['success_redirect_type'] = $customization['success_redirect_type'] ?? 'automatic';
    $customization['success_bt_redirect'] = $customization['success_bt_redirect'] ?? 'Continuar';
    $customization['show_score'] = $customization['show_score'] ?? 0;
} else {
    // Fallback completo se não houver personalização
    $customization = [
        'background_color' => '#ffffff',
        'text_color' => '#000000',
        'primary_color' => '#4f46e5',
        'button_text_color' => '#ffffff',
        'background_image' => '',
        'logo' => '',
        'button_radius' => 8,
        'font_family' => 'Inter',
        'hide_formtalk_branding' => 0,
        'success_message_title' => 'Tudo certo!',
        'success_message_description' => 'Obrigado por responder nosso formulário.',
        'success_redirect_enabled' => 0,
        'success_redirect_url' => '',
        'success_redirect_type' => 'automatic',
        'success_bt_redirect' => 'Continuar',
        'show_score' => 0
    ];
}

// Buscar campos do formulário
$fieldsStmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = :form_id ORDER BY order_index ASC");
$fieldsStmt->execute([':form_id' => $formId]);
$fields = $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar fluxos condicionais do formulário
$flowsStmt = $pdo->prepare("SELECT * FROM form_flows WHERE form_id = :form_id ORDER BY order_index ASC");
$flowsStmt->execute([':form_id' => $formId]);
$flows = $flowsStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($fields)) {
    die("Este formulário ainda não possui perguntas.");
}

$displayMode = $form['display_mode'];
$baseDir = dirname(__FILE__);

// Montar URL da fonte do Google Fonts
$fontFamilyUrl = str_replace(' ', '+', $customization['font_family']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($form['title']) ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/webp" href="https://formtalk.app/wp-content/uploads/2025/11/cropped-favicon-20251107044740-32x32.webp">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/imask"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>

    <!-- International Telephone Input (bandeirinhas de países) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/css/intlTelInput.css">
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js"></script>

    <script src="<?= assetUrl('/scripts/js/masks.js') ?>"></script>

    <!-- Fonte personalizada -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=<?= $fontFamilyUrl ?>:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="<?= assetUrl('/modules/forms/public/assets/styles.css') ?>">

    <style>
        :root {
            --primary-color: <?= $customization['primary_color'] ?>;
            --button-text-color: <?= $customization['button_text_color'] ?>;
            --text-color: <?= $customization['text_color'] ?>;
            --button-radius: <?= $customization['button_radius'] ?>px;
        }

        body {
            background-color: <?= $customization['background_color'] ?>;
            <?php if (!empty($customization['background_image'])): ?>
            background-image: url('<?= htmlspecialchars($customization['background_image']) ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            <?php endif; ?>
            color: <?= $customization['text_color'] ?>;
            font-family: '<?= $customization['font_family'] ?>', sans-serif;
        }

        .btn-primary {
            background-color: var(--primary-color) !important;
            color: var(--button-text-color) !important;
            border-radius: var(--button-radius) !important;
        }

        .btn-primary:hover {
            filter: brightness(0.9);
        }

        .progress-bar {
            background-color: var(--primary-color) !important;
        }

        /* Segmentos da barra de progresso estilo Stories */
        .progress-segment {
            transition: width 0.3s ease-out, opacity 0.3s ease-out;
            will-change: width, opacity;
        }

        /* Animação de piscada para o segmento atual */
        .progress-segment.current {
            animation: storiesPulse 1.5s ease-in-out infinite;
        }

        @keyframes storiesPulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.6;
            }
        }

        h1, h2, h3, h4, h5, h6, label, p, span {
            color: <?= $customization['text_color'] ?> !important;
        }

        /* Estilo para imagens flutuantes */
        .float-layout {
            display: flex;
            min-height: 100vh;
            gap: 2rem;
        }

        .float-layout.left {
            flex-direction: row;
        }

        .float-layout.right {
            flex-direction: row-reverse;
        }

        .float-image-container {
            flex-shrink: 0;
            overflow: hidden;
            border-radius: 0.5rem;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .float-content-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 1.5rem;
        }

        .float-image-container.large {
            width: 50vw;
        }

        .float-image-container.small {
            width: 30vw;
        }

        .float-content-container.large {
            width: calc(50vw - 2rem);
        }

        .float-content-container.small {
            width: calc(70vw - 2rem);
        }

        /* Fix: Campos de formulário não devem herdar text-alignment do container */
        /* Isso garante que players de áudio, VSL, e outros campos com layouts flex não sejam afetados */
        input, select, textarea,
        .audio-message-container,
        .audio-player-wrapper,
        .audio-visualizer-futuristic,
        .video-container {
            text-align: initial !important;
        }

        /* Estilos para intl-tel-input (seletor de país) */
        .iti {
            width: 100% !important;
            display: block !important;
        }

        .iti__flag-container {
            position: absolute;
            top: 0;
            bottom: 0;
            right: 0;
            padding: 0;
        }

        .iti__selected-flag {
            padding: 0 8px 0 16px;
            display: flex;
            align-items: center;
            height: 100%;
            background-color: transparent !important;
        }

        .iti__arrow {
            margin-left: 6px;
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
            border-top: 5px solid var(--text-color);
        }

        .iti input.iti__tel-input {
            padding-right: 80px !important;
            width: 100%;
            background-color: transparent !important;
        }

        /* Dropdown de países - usa cores dinâmicas do formulário */
        .iti__country-list {
            background-color: <?= $customization['background_color'] ?> !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 9999;
        }

        .iti__country {
            color: var(--text-color) !important;
            padding: 8px 12px;
        }

        .iti__country:hover {
            background-color: rgba(0,0,0,0.05) !important;
        }

        .iti__country.iti__highlight {
            background-color: var(--primary-color) !important;
            color: white !important;
        }

        .iti__country-name,
        .iti__dial-code {
            color: var(--text-color) !important;
        }

        .iti__country.iti__highlight .iti__country-name,
        .iti__country.iti__highlight .iti__dial-code {
            color: white !important;
        }

        .iti__selected-dial-code {
            color: var(--text-color);
            margin-left: 6px;
            font-weight: 500;
        }

        /* Remover background branco no focus dos inputs */
        input:focus,
        textarea:focus,
        select:focus {
            background-color: transparent !important;
            outline: none;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col"
      data-success-title="<?= htmlspecialchars($customization['success_message_title']) ?>"
      data-success-description="<?= htmlspecialchars($customization['success_message_description']) ?>"
      data-success-media="<?= htmlspecialchars($customization['success_message_media'] ?? '') ?>"
      data-redirect-enabled="<?= $customization['success_redirect_enabled'] ?? 0 ?>"
      data-redirect-url="<?= htmlspecialchars($customization['success_redirect_url'] ?? '') ?>"
      data-redirect-type="<?= htmlspecialchars($customization['success_redirect_type'] ?? 'automatic') ?>"
      data-redirect-button-text="<?= htmlspecialchars($customization['success_bt_redirect'] ?? 'Continuar') ?>"
      data-hide-branding="<?= $customization['hide_formtalk_branding'] ?? 0 ?>"
      data-show-score="<?= $customization['show_score'] ?? 0 ?>"
      data-text-color="<?= htmlspecialchars($customization['text_color']) ?>"
      data-primary-color="<?= htmlspecialchars($customization['primary_color']) ?>"
      data-button-text-color="<?= htmlspecialchars($customization['button_text_color']) ?>"
      data-button-radius="<?= $customization['button_radius'] ?? 8 ?>"
      data-flows="<?= htmlspecialchars(json_encode($flows)) ?>">

<?php if ($form['status'] === 'rascunho'): ?>
    <!-- Badge de Rascunho -->
    <div class="fixed top-0 left-0 right-0 z-50 bg-yellow-500 text-yellow-900 py-3 px-4 text-center font-medium shadow-lg">
        <i class="fas fa-eye mr-2"></i>
        Modo de Visualização: Este formulário está em rascunho e não está disponível publicamente
    </div>
    <style>
        /* Adicionar padding ao body para compensar o badge fixo */
        body { padding-top: 3rem; }
    </style>
<?php endif; ?>

<?php if (!empty($customization['logo'])): ?>
                    <div class="text-left" style="position:fixed; bottom:20px; right:20px;">
                        <img src="<?= htmlspecialchars($customization['logo']) ?>"
                             alt="Logo"
                             class="max-w-[175px] h-auto mx-auto">
                    </div>
                <?php endif; ?>
                
    <?php if ($displayMode === 'one-by-one'): ?>
        <!-- Modo One-by-One (TypeForm Style com Scroll) -->

        <!-- Barra de progresso estilo Stories -->
        <div class="fixed top-0 left-0 right-0 z-50 px-2.5 pt-2.5">
            <div class="flex gap-1" id="storiesProgress">
                <?php foreach ($fields as $idx => $f): ?>
                    <div class="flex-1 h-1 rounded-full bg-white bg-opacity-20 overflow-hidden">
                        <div class="progress-segment h-full rounded-full transition-all duration-300"
                             data-segment-index="<?= $idx ?>"
                             style="width: 0%; background-color: <?= $customization['primary_color'] ?>;"></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex-1 flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-3xl mx-auto <?= $textAlignmentClass ?>">

                <form id="formOneByOne">
                    <input type="hidden" name="form_id" value="<?= $formId ?>">

                    <?php foreach ($fields as $index => $field): ?>
                        <div class="question-slide fade-in"
                             data-index="<?= $index ?>"
                             data-field-id="<?= $field['id'] ?>"
                             data-order-index="<?= $field['order_index'] ?>"
                             data-flow-id="<?= $field['flow_id'] ?? '' ?>"
                             data-conditional-logic="<?= htmlspecialchars($field['conditional_logic'] ?? '') ?>"
                             style="<?= $index === 0 ? '' : 'display: none;' ?>">

                            <div class="question-number mb-3" style="color: <?= $customization['primary_color'] ?>;">
                                <?= $index + 1 ?> <i class="fas fa-arrow-right text-xs"></i>
                            </div>

                            <?php if ($field['type'] !== 'terms' && $field['type'] !== 'loading'): ?>
                                <label class="block text-3xl md:text-4xl font-bold mb-4 leading-tight" style="color: <?= $customization['text_color'] ?> !important;">
                                    <?= htmlspecialchars($field['label']) ?>
                                    <?php if ($field['required'] && !in_array($field['type'], ['welcome', 'message'])): ?>
                                        <span class="text-red-500">*</span>
                                    <?php endif; ?>
                                </label>
                                <?php if (!empty($field['description'])): ?>
                                    <p class="text-sm md:text-base mb-8" style="color: <?= $customization['text_color'] ?>; opacity: 0.7;">
                                        <?= nl2br(htmlspecialchars($field['description'])) ?>
                                    </p>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php
                            // Verificar se há mídia flutuante para alterar o layout
                            $hasFloatingMedia = !empty($field['media']) && $field['media_style'] === 'float';
                            
                            if ($hasFloatingMedia):
                                $sizeClass = $field['media_size'] === 'large' ? 'large' : 'small';
                                $layoutClass = $field['media_position'] === 'left' ? 'left' : 'right';
                                $backgroundStyle = 'background-image: url("' . htmlspecialchars($field['media']) . '");';
                            ?>
                                <div class="float-layout <?= $layoutClass ?>">
                                    <div class="float-image-container <?= $sizeClass ?>" style="<?= $backgroundStyle ?>"></div>
                                    <div class="float-content-container <?= $sizeClass ?>">
                                        <?php
                                        // Não renderizar arquivo para welcome e message (apenas exibem texto)
                                        if (!in_array($field['type'], ['welcome', 'message'])):
                                            $fieldName = "field_" . $field['id'];
                                            $renderFile = $baseDir . "/render/field_{$field['type']}.php";

                                            if (file_exists($renderFile)) {
                                                include $renderFile;
                                            } else {
                                                echo "<p class='text-red-500'>Tipo de campo '{$field['type']}' não implementado</p>";
                                            }
                                        endif;
                                        ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php
                                // Exibir mídia se existir
                                if (!empty($field['media']) && $field['media'] !== ''):
                                    echo renderMedia($field['media']);
                                endif;
                                ?>

                                <?php
                                // Não renderizar arquivo para welcome e message (apenas exibem texto)
                                if (!in_array($field['type'], ['welcome', 'message'])):
                                    $fieldName = "field_" . $field['id'];
                                    $renderFile = $baseDir . "/render/field_{$field['type']}.php";

                                    if (file_exists($renderFile)) {
                                        include $renderFile;
                                    } else {
                                        echo "<p class='text-red-500'>Tipo de campo '{$field['type']}' não implementado</p>";
                                    }
                                endif;
                                ?>
                            <?php endif; ?>

                            <div class="flex items-center gap-4 mt-12 <?= $buttonJustifyClass ?>">
                                <?php 
                                // Definir texto do botão baseado no tipo de campo
                                $buttonText = 'OK';
                                $buttonIcon = 'fa-check';
                                
                                if ($field['type'] === 'welcome') {
                                    $buttonText = 'Começar';
                                    $buttonIcon = 'fa-arrow-right';
                                } elseif ($field['type'] === 'message') {
                                    $buttonText = 'Continuar';
                                    $buttonIcon = 'fa-arrow-right';
                                }
                                ?>
                                
                                <?php if ($index < count($fields) - 1): ?>
                                    <button type="button"
                                            onclick="nextQuestion()"
                                            class="btn-primary px-6 py-3 flex items-center gap-2 text-lg font-medium">
                                        <?= $buttonText ?> <i class="fas <?= $buttonIcon ?> text-sm"></i>
                                    </button>
                                    <span class="text-sm opacity-60">pressione <strong>Enter ↵</strong></span>
                                <?php else: ?>
                                    <button type="submit"
                                            class="btn-primary px-6 py-3 flex items-center gap-2 text-lg font-medium">
                                        Enviar <i class="fas fa-paper-plane text-sm"></i>
                                    </button>
                                <?php endif; ?>
                            </div>

                            <?php if ($index > 0): ?>
                                <div class="flex <?= $buttonJustifyClass ?>">
                                    <button type="button"
                                            onclick="previousQuestion()"
                                            class="btn-secondary mt-6 flex items-center gap-2"
                                            style="color: <?= $customization['text_color'] ?>;">
                                        <i class="fas fa-arrow-up"></i> Voltar
                                    </button>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- Modo All-at-Once (Tradicional Clean) -->

        <div class="min-h-screen py-12 px-6">
            <div class="max-w-3xl mx-auto <?= $textAlignmentClass ?>">

                <?php if (!empty($customization['logo'])): ?>
                    <div class="text-center mb-8">
                        <img src="<?= htmlspecialchars($customization['logo']) ?>"
                             alt="Logo"
                             class="max-w-[200px] h-auto mx-auto">
                    </div>
                <?php endif; ?>

                <div>
                    <div class="mb-12">
                        <h1 class="text-4xl md:text-5xl font-bold mb-4" style="color: <?= $customization['text_color'] ?> !important;">
                            <?= htmlspecialchars($form['title']) ?>
                        </h1>
                        <?php if ($form['description']): ?>
                            <p class="text-xl opacity-80" style="color: <?= $customization['text_color'] ?> !important;">
                                <?= htmlspecialchars($form['description']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <form id="formAllAtOnce" class="space-y-12">
                        <input type="hidden" name="form_id" value="<?= $formId ?>">

                        <?php foreach ($fields as $index => $field): ?>
                            <div class="fade-in field-container"
                                 data-field-id="<?= $field['id'] ?>"
                                 data-conditional-logic="<?= htmlspecialchars($field['conditional_logic'] ?? '') ?>"
                                 style="animation-delay: <?= $index * 0.1 ?>s;">

                                <?php if ($field['type'] !== 'terms' && $field['type'] !== 'loading'): ?>
                                    <label class="block text-2xl font-bold mb-2" style="color: <?= $customization['text_color'] ?> !important;">
                                        <?= htmlspecialchars($field['label']) ?>
                                        <?php if ($field['required'] && !in_array($field['type'], ['welcome', 'message'])): ?>
                                            <span class="text-red-500">*</span>
                                        <?php endif; ?>
                                    </label>
                                    <?php if (!empty($field['description'])): ?>
                                        <p class="text-sm mb-4" style="color: <?= $customization['text_color'] ?>; opacity: 0.7;">
                                            <?= nl2br(htmlspecialchars($field['description'])) ?>
                                        </p>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php
                                // Verificar se há mídia flutuante para alterar o layout
                                $hasFloatingMedia = !empty($field['media']) && $field['media_style'] === 'float';
                                
                                if ($hasFloatingMedia):
                                    $sizeClass = $field['media_size'] === 'large' ? 'large' : 'small';
                                    $layoutClass = $field['media_position'] === 'left' ? 'left' : 'right';
                                    $backgroundStyle = 'background-image: url("' . htmlspecialchars($field['media']) . '");';
                                ?>
                                    <div class="float-layout <?= $layoutClass ?>">
                                        <div class="float-image-container <?= $sizeClass ?>" style="<?= $backgroundStyle ?>"></div>
                                        <div class="float-content-container <?= $sizeClass ?>">
                                            <?php
                                            // Não renderizar arquivo para welcome e message (apenas exibem texto)
                                            if (!in_array($field['type'], ['welcome', 'message'])):
                                                $fieldName = "field_" . $field['id'];
                                                $renderFile = $baseDir . "/render/field_{$field['type']}.php";

                                                if (file_exists($renderFile)) {
                                                    include $renderFile;
                                                } else {
                                                    echo "<p class='text-red-500'>Tipo de campo '{$field['type']}' não implementado</p>";
                                                }
                                            endif;
                                            ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php
                                    // Exibir mídia se existir
                                    if (!empty($field['media']) && $field['media'] !== ''):
                                        echo renderMedia($field['media']);
                                    endif;
                                    ?>

                                    <?php
                                    // Não renderizar arquivo para welcome e message (apenas exibem texto)
                                    if (!in_array($field['type'], ['welcome', 'message'])):
                                        $fieldName = "field_" . $field['id'];
                                        $renderFile = $baseDir . "/render/field_{$field['type']}.php";

                                        if (file_exists($renderFile)) {
                                            include $renderFile;
                                        } else {
                                            echo "<p class='text-red-500'>Tipo de campo '{$field['type']}' não implementado</p>";
                                        }
                                    endif;
                                    ?>
                                <?php endif; ?>

                            </div>
                        <?php endforeach; ?>

                        <div class="pt-6 flex <?= $buttonJustifyClass ?>">
                            <button type="submit" class="btn-primary px-6 py-3 text-lg font-medium">
                                Enviar respostas <i class="fas fa-paper-plane ml-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <!-- Scripts -->
    <script src="<?= assetUrl('/modules/forms/public/assets/scripts.js') ?>"></script>

</body>
</html>