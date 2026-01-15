<?php
session_start();
require_once __DIR__ . '/../../../core/db.php';
require_once __DIR__ . '/../../../core/PlanService.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit();
}

// Verificar plano PRO
if (!PlanService::hasProAccess()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Este recurso é exclusivo para usuários PRO']);
    exit();
}

$userId = $_SESSION['user_id'];

// Receber estrutura do formulário
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['title']) || !isset($input['fields'])) {
    echo json_encode(['success' => false, 'error' => 'Estrutura inválida']);
    exit();
}

$title = trim($input['title']);
$description = trim($input['description'] ?? '');
$status = trim($input['status'] ?? 'rascunho'); // Padrão: rascunho
$fields = $input['fields'] ?? [];

// Validar status
if (!in_array($status, ['rascunho', 'publicado'])) {
    $status = 'rascunho'; // Fallback seguro
}

// Validações
if (empty($title)) {
    echo json_encode(['success' => false, 'error' => 'Título é obrigatório']);
    exit();
}

if (empty($fields) || !is_array($fields)) {
    echo json_encode(['success' => false, 'error' => 'Formulário deve ter pelo menos um campo']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. CRIAR FORMULÁRIO
    $formStmt = $pdo->prepare("
        INSERT INTO forms (user_id, title, description, status, created_at)
        VALUES (:user_id, :title, :description, :status, NOW())
    ");

    $formStmt->execute([
        ':user_id' => $userId,
        ':title' => $title,
        ':description' => $description,
        ':status' => $status
    ]);

    $formId = $pdo->lastInsertId();

    // 2. CRIAR CAMPO DE BOAS-VINDAS (WELCOME) AUTOMÁTICO
    $welcomeStmt = $pdo->prepare("
        INSERT INTO form_fields (form_id, type, label, description, required, order_index, created_at)
        VALUES (:form_id, 'welcome', :title, :description, 0, 0, NOW())
    ");

    $welcomeStmt->execute([
        ':form_id' => $formId,
        ':title' => $title,
        ':description' => $description ?: 'Preencha o formulário abaixo'
    ]);

    // 3. CRIAR CAMPOS DO FORMULÁRIO
    $orderIndex = 1; // Welcome é 0, campos começam em 1

    foreach ($fields as $field) {
        $type = $field['type'] ?? 'text';
        $label = trim($field['label'] ?? '');
        $fieldDescription = trim($field['description'] ?? '');
        $required = isset($field['required']) && $field['required'] ? 1 : 0;
        $options = null;
        $config = null;

        // Validar tipo
        $allowedTypes = [
            'text', 'textarea', 'email', 'phone', 'date', 'cpf', 'cnpj', 'rg',
            'money', 'slider', 'rating', 'address', 'file', 'terms', 'radio',
            'select', 'name', 'message', 'url', 'number', 'range'
        ];

        if (!in_array($type, $allowedTypes)) {
            throw new Exception("Tipo de campo inválido: $type");
        }

        if (empty($label)) {
            throw new Exception("Label é obrigatório para todos os campos");
        }

        // Processar opções para radio e select
        if (in_array($type, ['radio', 'select']) && isset($field['options'])) {
            if (is_array($field['options'])) {
                $options = json_encode(array_values($field['options']));
            } else {
                $options = json_encode([$field['options']]);
            }
        }

        // Processar configurações específicas
        if (isset($field['config']) && is_array($field['config'])) {
            $config = json_encode($field['config']);
        }

        // Inserir campo
        $fieldStmt = $pdo->prepare("
            INSERT INTO form_fields
            (form_id, type, label, description, placeholder, required, options, config, order_index, created_at)
            VALUES
            (:form_id, :type, :label, :description, :placeholder, :required, :options, :config, :order_index, NOW())
        ");

        $fieldStmt->execute([
            ':form_id' => $formId,
            ':type' => $type,
            ':label' => $label,
            ':description' => $fieldDescription,
            ':placeholder' => '', // Placeholder gerado automaticamente no frontend
            ':required' => $required,
            ':options' => $options,
            ':config' => $config,
            ':order_index' => $orderIndex
        ]);

        $orderIndex++;
    }

    // 4. CRIAR MENSAGEM DE SUCESSO PADRÃO
    $successStmt = $pdo->prepare("
        UPDATE forms
        SET success_message_title = 'Obrigado! ✅',
            success_message_description = 'Suas informações foram enviadas com sucesso!'
        WHERE id = :form_id
    ");
    $successStmt->execute([':form_id' => $formId]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'form_id' => $formId,
        'message' => 'Formulário criado com sucesso!'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao criar formulário com IA: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao salvar no banco de dados'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao criar formulário com IA: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
