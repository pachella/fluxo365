<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$config = require __DIR__ . '/config.php';

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/SubscriptionService.php';
require_once __DIR__ . '/processor.php';

function logWebhook($level, $message, $context = []) {
    global $config;
    
    if (!$config['logging']['enabled']) {
        return;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$level}] {$message}";
    
    if (!empty($context)) {
        $logMessage .= " | Dados: " . print_r($context, true);
    }
    
    $logMessage .= "\n";
    
    if (!file_exists(dirname($config['logging']['log_file']))) {
        mkdir(dirname($config['logging']['log_file']), 0755, true);
    }
    
    file_put_contents($config['logging']['log_file'], $logMessage, FILE_APPEND);
}

function sendResponse($code, $message, $data = []) {
    http_response_code($code);
    echo json_encode([
        'success' => $code === 200,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Resposta para GET (teste de conectividade)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    logWebhook('info', 'Requisição GET recebida - Teste de conectividade');
    sendResponse(200, 'Webhook Ticto funcionando! Pronto para receber POST.');
}

// Processar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Capturar payload bruto
    $rawPayload = file_get_contents('php://input');
    
    logWebhook('info', '========== NOVO WEBHOOK RECEBIDO DA TICTO ==========');
    logWebhook('debug', 'Raw payload completo', ['raw' => $rawPayload]);
    
    // Decodificar JSON
    $payload = json_decode($rawPayload, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logWebhook('error', 'Erro ao decodificar JSON', [
            'erro' => json_last_error_msg(),
            'raw' => substr($rawPayload, 0, 500)
        ]);
        sendResponse(400, 'Payload JSON inválido');
    }
    
    logWebhook('debug', 'Payload decodificado', $payload);
    logWebhook('debug', 'Chaves principais', ['chaves' => array_keys($payload)]);
    
    // Validar payload
    if (!isset($payload['status']) && !isset($payload['event'])) {
        logWebhook('error', 'Payload inválido - campo status ou event não encontrado');
        sendResponse(400, 'Payload inválido - campo status ou event ausente');
    }
    
    // Verificar se é evento de afiliação (campo 'event')
    if (isset($payload['event'])) {
        $event = $payload['event'];
        
        logWebhook('info', 'Webhook Ticto recebido (evento)', [
            'event' => $event,
            'affiliate_email' => $payload['email'] ?? 'N/A'
        ]);
        
        // Processar evento de afiliação
        try {
            $processor = new TictoProcessor($pdo, $config);
            $result = $processor->process($event, $payload);
            
            if ($result['success']) {
                logWebhook('info', 'Webhook processado com sucesso', $result);
                sendResponse(200, 'Webhook processado com sucesso', $result);
            } else {
                logWebhook('error', 'Falha ao processar webhook', $result);
                sendResponse(500, 'Erro ao processar: ' . $result['message'], $result);
            }
            
        } catch (Exception $e) {
            logWebhook('error', 'Erro fatal ao processar webhook', [
                'erro' => $e->getMessage(),
                'linha' => $e->getLine(),
                'arquivo' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            sendResponse(500, 'Erro interno: ' . $e->getMessage());
        }
    }
    
    // Processar eventos de assinatura (campo 'status')
    $status = $payload['status'];
    
    logWebhook('info', 'Webhook Ticto recebido', [
        'status' => $status,
        'order_hash' => $payload['order']['hash'] ?? 'N/A',
        'customer_email' => $payload['customer']['email'] ?? 'N/A'
    ]);
    
    // Ignorar webhooks de waiting_payment
    if ($status === 'waiting_payment') {
        logWebhook('info', 'Status waiting_payment ignorado - aguardando aprovação');
        sendResponse(200, 'Webhook recebido - Status waiting_payment ignorado');
    }
    
    // Processar apenas eventos autorizados
    if (!in_array($status, $config['allowed_events'])) {
        logWebhook('info', 'Evento ignorado - não está na lista de permitidos', ['status' => $status]);
        sendResponse(200, 'Evento ignorado: ' . $status);
    }
    
    try {
        $processor = new TictoProcessor($pdo, $config);
        $result = $processor->process($status, $payload);
        
        if ($result['success']) {
            logWebhook('info', 'Webhook processado com sucesso', $result);
            sendResponse(200, 'Webhook processado com sucesso', $result);
        } else {
            logWebhook('error', 'Falha ao processar webhook', $result);
            sendResponse(500, 'Erro ao processar: ' . $result['message'], $result);
        }
        
    } catch (Exception $e) {
        logWebhook('error', 'Erro fatal ao processar webhook', [
            'erro' => $e->getMessage(),
            'linha' => $e->getLine(),
            'arquivo' => $e->getFile(),
            'trace' => $e->getTraceAsString()
        ]);
        sendResponse(500, 'Erro interno: ' . $e->getMessage());
    }
}

// Método não permitido
sendResponse(405, 'Método não permitido');