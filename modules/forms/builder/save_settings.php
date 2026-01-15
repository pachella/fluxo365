<?php
/**
 * Salvar configurações do formulário (3 abas)
 *
 * Processa dados de: Geral, Personalização e Configurações
 */

session_start();
require_once(__DIR__ . "/../../../core/db.php");
require_once(__DIR__ . "/../../../core/PlanService.php");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo "Não autorizado";
    exit();
}

try {
    $formId = intval($_POST['form_id'] ?? 0);

    if (!$formId) {
        throw new Exception("ID do formulário é obrigatório");
    }

    // Verificar permissão
    $stmt = $pdo->prepare("SELECT user_id FROM forms WHERE id = :id");
    $stmt->execute([':id' => $formId]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$form) {
        throw new Exception("Formulário não encontrado");
    }

    if ($_SESSION['user_role'] !== 'admin' && $form['user_id'] != $_SESSION['user_id']) {
        throw new Exception("Sem permissão para editar este formulário");
    }

    // ==================================================
    // ABA 1: GERAL - Atualizar tabela forms
    // ==================================================
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $displayMode = trim($_POST['display_mode'] ?? 'one-by-one');
    $status = trim($_POST['status'] ?? 'ativo');

    // Campos de bloqueio (recurso PRO)
    $blockingEnabled = 0;
    $blockingType = 'date';
    $blockingDate = null;
    $blockingResponseLimit = null;
    $blockingMessage = '';

    // Apenas usuários PRO podem habilitar sistema de bloqueio
    if (PlanService::hasProAccess()) {
        $blockingEnabled = isset($_POST['blocking_enabled']) ? 1 : 0;
        $blockingType = trim($_POST['blocking_type'] ?? 'date');
        $blockingDate = !empty($_POST['blocking_date']) ? $_POST['blocking_date'] : null;
        $blockingResponseLimit = !empty($_POST['blocking_response_limit']) ? intval($_POST['blocking_response_limit']) : null;
        $blockingMessage = trim($_POST['blocking_message'] ?? '');
    }

    // Debug: Log dos valores sendo salvos
    error_log("=== SALVANDO CONFIGURAÇÕES ===");
    error_log("Form ID: $formId");
    error_log("blocking_enabled (salvar): " . var_export($blockingEnabled, true));
    error_log("blocking_type (salvar): " . var_export($blockingType, true));
    error_log("blocking_date (salvar): " . var_export($blockingDate, true));
    error_log("blocking_response_limit (salvar): " . var_export($blockingResponseLimit, true));
    error_log("blocking_message (salvar): " . var_export($blockingMessage, true));

    // Verificar se os campos de bloqueio existem, se não, criar automaticamente
    $checkColStmt = $pdo->query("SHOW COLUMNS FROM forms LIKE 'blocking_enabled'");
    if ($checkColStmt->rowCount() === 0) {
        // Executar migração automaticamente
        $pdo->exec("ALTER TABLE forms
            ADD COLUMN blocking_enabled TINYINT(1) DEFAULT 0 AFTER status,
            ADD COLUMN blocking_type ENUM('date', 'responses') DEFAULT 'date' AFTER blocking_enabled,
            ADD COLUMN blocking_date DATETIME NULL AFTER blocking_type,
            ADD COLUMN blocking_response_limit INT NULL AFTER blocking_date,
            ADD COLUMN blocking_message TEXT NULL AFTER blocking_response_limit");

        // Criar índices
        $pdo->exec("CREATE INDEX idx_blocking_enabled ON forms(blocking_enabled)");
        $pdo->exec("CREATE INDEX idx_blocking_date ON forms(blocking_date)");
    }

    if (empty($title)) {
        throw new Exception("Título é obrigatório");
    }

    $updateFormSql = "UPDATE forms SET
        title = :title,
        description = :description,
        display_mode = :display_mode,
        status = :status,
        blocking_enabled = :blocking_enabled,
        blocking_type = :blocking_type,
        blocking_date = :blocking_date,
        blocking_response_limit = :blocking_response_limit,
        blocking_message = :blocking_message";

    // Admin pode editar ícone e cor
    if ($_SESSION['user_role'] === 'admin' && isset($_POST['icon'], $_POST['color'])) {
        $icon = trim($_POST['icon']);
        $color = trim($_POST['color']);
        $updateFormSql .= ", icon = :icon, color = :color";
    }

    $updateFormSql .= " WHERE id = :id";

    $updateStmt = $pdo->prepare($updateFormSql);
    $updateStmt->bindValue(':title', $title);
    $updateStmt->bindValue(':description', $description);
    $updateStmt->bindValue(':display_mode', $displayMode);
    $updateStmt->bindValue(':status', $status);
    $updateStmt->bindValue(':blocking_enabled', $blockingEnabled, PDO::PARAM_INT);
    $updateStmt->bindValue(':blocking_type', $blockingType);
    $updateStmt->bindValue(':blocking_date', $blockingDate);
    $updateStmt->bindValue(':blocking_response_limit', $blockingResponseLimit, PDO::PARAM_INT);
    $updateStmt->bindValue(':blocking_message', $blockingMessage);

    if ($_SESSION['user_role'] === 'admin' && isset($_POST['icon'], $_POST['color'])) {
        $updateStmt->bindValue(':icon', $icon);
        $updateStmt->bindValue(':color', $color);
    }

    $updateStmt->bindValue(':id', $formId, PDO::PARAM_INT);
    $updateStmt->execute();

    // Debug: Confirmar que foi salvo
    error_log("✓ UPDATE executado com sucesso");

    // Verificar o que foi realmente salvo no banco
    $verifyStmt = $pdo->prepare("SELECT blocking_enabled, blocking_type, blocking_date, blocking_response_limit FROM forms WHERE id = :id");
    $verifyStmt->execute([':id' => $formId]);
    $savedData = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    error_log("Valores salvos no DB: " . json_encode($savedData));

    // ==================================================
    // ABA 2: PERSONALIZAÇÃO - Atualizar/Inserir form_customizations
    // IMPORTANTE: Só atualiza se os campos de personalização forem enviados
    // ==================================================

    // Verificar se campos de personalização foram enviados (evita resetar personalizações ao salvar outras configs)
    if (isset($_POST['background_color']) || isset($_POST['primary_color'])) {
        $backgroundColor = trim($_POST['background_color'] ?? '#ffffff');
        $textColor = trim($_POST['text_color'] ?? '#000000');
        $primaryColor = trim($_POST['primary_color'] ?? '#4f46e5');
        $buttonTextColor = trim($_POST['button_text_color'] ?? '#ffffff');
        $fontFamily = trim($_POST['font_family'] ?? 'Inter');
        $buttonRadius = intval($_POST['button_radius'] ?? 8);

        // Verificar se já existe customização
        $checkCustom = $pdo->prepare("SELECT id FROM form_customizations WHERE form_id = :form_id");
        $checkCustom->execute([':form_id' => $formId]);
        $existingCustom = $checkCustom->fetch();

        if ($existingCustom) {
            // Atualizar
            $updateCustomSql = "UPDATE form_customizations SET
                background_color = :background_color,
                text_color = :text_color,
                primary_color = :primary_color,
                button_text_color = :button_text_color,
                font_family = :font_family,
                button_radius = :button_radius
                WHERE form_id = :form_id";

            $updateCustomStmt = $pdo->prepare($updateCustomSql);
            $updateCustomStmt->execute([
                ':background_color' => $backgroundColor,
                ':text_color' => $textColor,
                ':primary_color' => $primaryColor,
                ':button_text_color' => $buttonTextColor,
                ':font_family' => $fontFamily,
                ':button_radius' => $buttonRadius,
                ':form_id' => $formId
            ]);
        } else {
            // Inserir
            $insertCustomSql = "INSERT INTO form_customizations (
                form_id, background_color, text_color, primary_color,
                button_text_color, font_family, button_radius
            ) VALUES (
                :form_id, :background_color, :text_color, :primary_color,
                :button_text_color, :font_family, :button_radius
            )";

            $insertCustomStmt = $pdo->prepare($insertCustomSql);
            $insertCustomStmt->execute([
                ':form_id' => $formId,
                ':background_color' => $backgroundColor,
                ':text_color' => $textColor,
                ':primary_color' => $primaryColor,
                ':button_text_color' => $buttonTextColor,
                ':font_family' => $fontFamily,
                ':button_radius' => $buttonRadius
            ]);
        }
    }

    echo "success";

} catch (Exception $e) {
    http_response_code(400);
    echo $e->getMessage();
}
?>
