<?php
ob_clean();

session_start();
require_once(__DIR__ . "/../../../core/db.php");
require_once __DIR__ . '/../../../core/PermissionManager.php';
require_once __DIR__ . '/../../../core/PlanService.php';

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo "Não autorizado";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Método não permitido";
    exit();
}

try {
    $permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

    $form_id = trim($_POST['form_id'] ?? '');
    $field_id = trim($_POST['field_id'] ?? '');
    $type = trim($_POST['type'] ?? '');

    // Verificar se tipo 'file' requer plano PRO
    if ($type === 'file' && !PlanService::hasProAccess()) {
        http_response_code(403);
        echo "Campo 'Upload de Arquivo' é exclusivo para usuários PRO";
        exit();
    }
    $label = trim($_POST['label'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $placeholder = ''; // Placeholder é gerado automaticamente no frontend
    $required = isset($_POST['required']) ? 1 : 0;
    $allow_multiple = isset($_POST['allow_multiple']) ? 1 : 0;
    $options = trim($_POST['options'] ?? '');
    
    // Obter dados da mídia (salvamos o JSON completo agora)
    $media = trim($_POST['media'] ?? '');

    // Para compatibilidade com código antigo, mantemos campos vazios
    $media_style = '';
    $media_position = '';
    $media_size = '';

    // Capturar configurações extras
    $config = [];

    // Slider config
    if ($type === 'slider') {
        $config['min'] = intval($_POST['slider_min'] ?? 0);
        $config['max'] = intval($_POST['slider_max'] ?? 10);
        $config['allow_range'] = isset($_POST['slider_allow_range']) ? 1 : 0;
    }

    // Range config
    if ($type === 'range') {
        $config['min'] = intval($_POST['slider_min'] ?? 0);
        $config['max'] = intval($_POST['slider_max'] ?? 10);
        $config['allow_range'] = isset($_POST['slider_allow_range']) ? 1 : 0;
    }

    // Rating config
    if ($type === 'rating') {
        $config['max'] = intval($_POST['rating_max'] ?? 5);
    }

    // Date config
    if ($type === 'date') {
        $config['show_time'] = isset($_POST['date_show_time']) ? 1 : 0;
    }

    // File config
    if ($type === 'file') {
        $config['types'] = trim($_POST['file_types'] ?? '.pdf,.jpg,.jpeg,.png,.doc,.docx');
    }

    // Terms config
    if ($type === 'terms') {
        $config['mode'] = trim($_POST['terms_mode'] ?? 'inline');
        $config['text'] = trim($_POST['terms_text'] ?? '');
        $config['pdf'] = trim($_POST['terms_pdf'] ?? '');
        $config['link'] = trim($_POST['terms_link'] ?? '');
    }

    // RG config - Campos complementares
    if ($type === 'rg') {
        $config['show_complementary_fields'] = isset($_POST['rg_show_complementary']) ? 1 : 0;
    }

    // VSL config
    if ($type === 'vsl') {
        $config['video_url'] = trim($_POST['vsl_video_url'] ?? '');
        $config['wait_time'] = intval($_POST['vsl_wait_time'] ?? 0);
        $config['button_text'] = trim($_POST['vsl_button_text'] ?? 'Continuar');
        $config['autoplay'] = isset($_POST['vsl_autoplay']) ? 1 : 0;
        $config['hide_controls'] = isset($_POST['vsl_hide_controls']) ? 1 : 0;
    }

    // Audio Message config
    if ($type === 'audio_message') {
        $config['audio_url'] = trim($_POST['audio_url'] ?? '');
        $config['wait_time'] = intval($_POST['audio_wait_time'] ?? 0);
        $config['button_text'] = trim($_POST['audio_button_text'] ?? 'Continuar');
        $config['autoplay'] = isset($_POST['audio_autoplay']) ? 1 : 0;
    }

    // Loading config
    if ($type === 'loading') {
        $config['phrase_1'] = trim($_POST['loading_phrase_1'] ?? 'Analisando suas respostas...');
        $config['phrase_2'] = trim($_POST['loading_phrase_2'] ?? 'Processando informações...');
        $config['phrase_3'] = trim($_POST['loading_phrase_3'] ?? 'Preparando resultado...');
    }

    $configJson = !empty($config) ? json_encode($config) : null;

    // Capturar lógica condicional
    $conditionalLogic = trim($_POST['conditional_logic'] ?? '');
    $conditionalLogicJson = !empty($conditionalLogic) ? $conditionalLogic : null;

    // Validações
    if (empty($form_id)) {
        http_response_code(400);
        echo "ID do formulário é obrigatório";
        exit();
    }

    // Label é opcional para tipos específicos (message, welcome, loading)
    $typesWithoutLabel = ['message', 'welcome', 'loading'];
    if (empty($label) && !in_array($type, $typesWithoutLabel)) {
        http_response_code(400);
        echo "Label/Pergunta é obrigatória";
        exit();
    }

    $allowedTypes = ['text', 'textarea', 'email', 'phone', 'date', 'cpf', 'cnpj', 'rg', 'money', 'slider', 'rating', 'address', 'file', 'terms', 'radio', 'select', 'name', 'message', 'welcome', 'url', 'number', 'range', 'image_choice', 'vsl', 'loading', 'audio_message'];
    if (!in_array($type, $allowedTypes)) {
        http_response_code(400);
        echo "Tipo de campo inválido";
        exit();
    }

    // Verificar se o usuário tem permissão para editar este formulário
    $formStmt = $pdo->prepare("SELECT user_id FROM forms WHERE id = :form_id");
    $formStmt->execute([':form_id' => $form_id]);
    $form = $formStmt->fetch(PDO::FETCH_ASSOC);

    if (!$form) {
        http_response_code(404);
        echo "Formulário não encontrado";
        exit();
    }

    if (!$permissionManager->canEditRecord($form['user_id'])) {
        http_response_code(403);
        echo "Você não tem permissão para editar este formulário";
        exit();
    }

    // Processar opções para radio, select e image_choice
    $optionsJson = null;
    $scoringEnabled = isset($_POST['scoring_enabled']) && $_POST['scoring_enabled'] === '1';

    if (in_array($type, ['radio', 'select', 'image_choice']) && !empty($options)) {
        // image_choice sempre vem como JSON com {label, image, score}
        if ($type === 'image_choice') {
            $imageOptions = json_decode($options, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($imageOptions) && count($imageOptions) > 0) {
                $optionsJson = $options; // Já está em JSON
                // Salvar flag de scoring no config se houver pontuação
                $hasScoring = false;
                foreach ($imageOptions as $opt) {
                    if (isset($opt['score']) && $opt['score'] > 0) {
                        $hasScoring = true;
                        break;
                    }
                }
                if ($hasScoring) {
                    $config['scoring_enabled'] = true;
                }
            } else {
                http_response_code(400);
                echo "Opções de imagem inválidas";
                exit();
            }
        }
        // Verificar se é modo pontuação no radio (já vem como JSON)
        elseif ($scoringEnabled && $type === 'radio') {
            // Validar JSON
            $scoringOptions = json_decode($options, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($scoringOptions) && count($scoringOptions) > 0) {
                $optionsJson = $options; // Já está em JSON
                // Salvar flag de scoring no config
                $config['scoring_enabled'] = true;
            } else {
                http_response_code(400);
                echo "Opções de pontuação inválidas";
                exit();
            }
        } else {
            // Modo normal (texto separado por linha)
            $optionsArray = array_filter(array_map('trim', explode("\n", $options)));
            if (empty($optionsArray)) {
                http_response_code(400);
                echo "Campos de múltipla escolha precisam ter pelo menos uma opção";
                exit();
            }
            $optionsJson = json_encode(array_values($optionsArray));
        }
    }

    $pdo->beginTransaction();

    if (empty($field_id)) {
        // CRIAR NOVO CAMPO

        if ($type === 'welcome') {
            // Para o campo "welcome", sempre posicionar no topo (order_index = 0) e deslocar os demais
            $pdo->prepare("UPDATE form_fields SET order_index = order_index + 1 WHERE form_id = :form_id")->execute([':form_id' => $form_id]);
            $nextOrder = 0;
        } else {
            // Buscar o próximo order_index para campos normais
            $orderStmt = $pdo->prepare("SELECT COALESCE(MAX(order_index), 0) + 1 as next_order FROM form_fields WHERE form_id = :form_id");
            $orderStmt->execute([':form_id' => $form_id]);
            $nextOrder = $orderStmt->fetch(PDO::FETCH_ASSOC)['next_order'];
        }

        $sql = "INSERT INTO form_fields (form_id, type, label, description, placeholder, options, required, allow_multiple, config, media, media_style, media_position, media_size, order_index, conditional_logic)
                VALUES (:form_id, :type, :label, :description, :placeholder, :options, :required, :allow_multiple, :config, :media, :media_style, :media_position, :media_size, :order_index, :conditional_logic)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':form_id' => $form_id,
            ':type' => $type,
            ':label' => $label,
            ':description' => $description,
            ':placeholder' => $placeholder,
            ':options' => $optionsJson,
            ':required' => $required,
            ':allow_multiple' => $allow_multiple,
            ':config' => $configJson,
            ':media' => $media,
            ':media_style' => $media_style,
            ':media_position' => $media_position,
            ':media_size' => $media_size,
            ':order_index' => $nextOrder,
            ':conditional_logic' => $conditionalLogicJson
        ]);

        $fieldId = $pdo->lastInsertId();

        $pdo->commit();
        echo "success:" . $fieldId;

    } else {
        // EDITAR CAMPO EXISTENTE

        // Verificar se o campo pertence ao formulário
        $checkStmt = $pdo->prepare("SELECT id FROM form_fields WHERE id = :id AND form_id = :form_id");
        $checkStmt->execute([':id' => $field_id, ':form_id' => $form_id]);

        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo "Campo não encontrado";
            exit();
        }

        $sql = "UPDATE form_fields SET
                type = :type,
                label = :label,
                description = :description,
                placeholder = :placeholder,
                options = :options,
                required = :required,
                allow_multiple = :allow_multiple,
                config = :config,
                media = :media,
                media_style = :media_style,
                media_position = :media_position,
                media_size = :media_size,
                conditional_logic = :conditional_logic
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':type' => $type,
            ':label' => $label,
            ':description' => $description,
            ':placeholder' => $placeholder,
            ':options' => $optionsJson,
            ':required' => $required,
            ':allow_multiple' => $allow_multiple,
            ':config' => $configJson,
            ':media' => $media,
            ':media_style' => $media_style,
            ':media_position' => $media_position,
            ':media_size' => $media_size,
            ':conditional_logic' => $conditionalLogicJson,
            ':id' => $field_id
        ]);

        $pdo->commit();
        echo "success:" . $field_id;
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro no banco de dados: " . $e->getMessage());
    http_response_code(500);
    echo "Erro no banco de dados: " . $e->getMessage();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro interno: " . $e->getMessage());
    http_response_code(500);
    echo "Erro interno";
}

exit();