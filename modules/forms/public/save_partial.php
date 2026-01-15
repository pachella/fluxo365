<?php
/**
 * Endpoint para salvar respostas parciais de formulários
 *
 * Este endpoint é chamado automaticamente pelo JavaScript enquanto o usuário
 * preenche o formulário, salvando as respostas progressivamente.
 */

session_start();
require_once(__DIR__ . '/../../../core/db.php');

// Garantir que sempre retornamos JSON
header('Content-Type: application/json');

// Capturar erros fatais
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro interno do servidor'
        ]);
    }
});

// Configurar para não mostrar erros direto na saída
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Obter dados do POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

// Validar dados obrigatórios
if (!isset($data['form_id']) || !isset($data['answers'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

$formId = intval($data['form_id']);
$answers = $data['answers'];
$progress = intval($data['progress'] ?? 0);
$lastFieldId = isset($data['last_field_id']) ? intval($data['last_field_id']) : null;

// Gerar/obter session_id único para este visitante
if (!isset($_SESSION['partial_response_session_id'])) {
    $_SESSION['partial_response_session_id'] = bin2hex(random_bytes(16));
}
$sessionId = $_SESSION['partial_response_session_id'];

try {
    // Verificar se já existe uma resposta parcial para esta sessão
    $checkStmt = $pdo->prepare("
        SELECT id FROM partial_responses
        WHERE form_id = :form_id AND session_id = :session_id AND completed = 0
    ");
    $checkStmt->execute([
        'form_id' => $formId,
        'session_id' => $sessionId
    ]);
    $existingResponse = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingResponse) {
        // Atualizar resposta parcial existente
        $updateStmt = $pdo->prepare("
            UPDATE partial_responses
            SET answers_data = :answers_data,
                progress = :progress,
                last_field_id = :last_field_id,
                last_updated = NOW()
            WHERE id = :id
        ");
        $updateStmt->execute([
            'answers_data' => json_encode($answers),
            'progress' => $progress,
            'last_field_id' => $lastFieldId,
            'id' => $existingResponse['id']
        ]);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Progresso atualizado',
            'partial_response_id' => $existingResponse['id']
        ]);
    } else {
        // Criar nova resposta parcial
        $insertStmt = $pdo->prepare("
            INSERT INTO partial_responses (form_id, session_id, answers_data, progress, last_field_id)
            VALUES (:form_id, :session_id, :answers_data, :progress, :last_field_id)
        ");
        $insertStmt->execute([
            'form_id' => $formId,
            'session_id' => $sessionId,
            'answers_data' => json_encode($answers),
            'progress' => $progress,
            'last_field_id' => $lastFieldId
        ]);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Progresso salvo',
            'partial_response_id' => $pdo->lastInsertId()
        ]);
    }
} catch (PDOException $e) {
    error_log('Erro ao salvar resposta parcial: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao salvar progresso'
    ]);
}
