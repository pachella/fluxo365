<?php
session_start();
require_once(__DIR__ . '/../../../core/ImageProcessor.php');
require_once(__DIR__ . '/../../../core/db.php');
require_once(__DIR__ . '/../../../core/PermissionManager.php');

// Garantir que sempre retornamos JSON
header('Content-Type: application/json');

// Capturar erros fatais
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro interno do servidor: ' . $error['message']
        ]);
    }
});

// Configurar para não mostrar erros direto na saída
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Verificar se recebeu arquivo
if (!isset($_FILES['image']) || !isset($_POST['form_id']) || !isset($_POST['field_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

$file = $_FILES['image'];
$formId = intval($_POST['form_id']);
$fieldName = $_POST['field_name'];

// Validar field_name
$allowedFields = ['background_image', 'logo', 'media_image', 'image_choice', 'audio_message'];
if (!in_array($fieldName, $allowedFields)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Campo inválido']);
    exit;
}

// Verificar permissão de edição do formulário
$permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id']);
if (!$permissionManager->canEditForm($pdo, $formId)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sem permissão para editar este formulário']);
    exit;
}

// Se for áudio, processar de forma diferente
if ($fieldName === 'audio_message') {
    // Validar arquivo de áudio
    $allowedMimeTypes = ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4', 'audio/x-m4a'];
    $allowedExtensions = ['mp3', 'wav', 'ogg', 'm4a'];

    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($fileExtension, $allowedExtensions)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Formato de áudio não suportado. Use MP3, WAV, OGG ou M4A.']);
        exit;
    }

    if ($file['size'] > 50 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Arquivo muito grande. Máximo: 50MB']);
        exit;
    }

    try {
        // Criar diretório se não existir
        $uploadDir = __DIR__ . '/../../../uploads/forms/' . $formId . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Gerar nome único para o arquivo
        $originalFilename = pathinfo($file['name'], PATHINFO_FILENAME);
        $safeFilename = preg_replace('/[^a-zA-Z0-9-_]/', '_', $originalFilename);
        $filename = $fieldName . '-' . $safeFilename . '-' . time() . '.' . $fileExtension;
        $filepath = $uploadDir . $filename;

        // Mover arquivo
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $publicUrl = '/uploads/forms/' . $formId . '/' . $filename;

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'url' => $publicUrl,
                'filename' => $filename
            ]);
        } else {
            throw new Exception('Erro ao mover arquivo de áudio');
        }
    } catch (Exception $e) {
        error_log('Erro no upload de áudio: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao processar áudio: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Validar arquivo (imagem)
$validation = ImageProcessor::validateUpload($file);
if (!$validation['success']) {
    http_response_code(400);
    echo json_encode($validation);
    exit;
}

try {
    // Definir diretório de upload
    $uploadDir = __DIR__ . '/../../../uploads/forms/' . $formId . '/';

    // Gerar nome base do arquivo
    $originalFilename = pathinfo($file['name'], PATHINFO_FILENAME);
    $baseFilename = $fieldName . '-' . $originalFilename;

    // Processar e salvar imagem
    $result = ImageProcessor::processAndSave($file, $uploadDir, $baseFilename);

    if ($result['success']) {
        // Gerar URL pública
        $publicUrl = '/uploads/forms/' . $formId . '/' . $result['filename'];

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'url' => $publicUrl,
            'filename' => $result['filename']
        ]);
    } else {
        http_response_code(500);
        echo json_encode($result);
    }
} catch (Exception $e) {
    error_log('Erro no upload de imagem: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar imagem: ' . $e->getMessage()
    ]);
}
