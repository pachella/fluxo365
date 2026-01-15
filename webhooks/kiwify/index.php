<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$config = [
    'auth' => [
        'integration_active' => true,
        'verify_signature' => false
    ],
    'logging' => [
        'enabled' => true, 
        'log_file' => __DIR__ . '/logs/kiwify.log'
    ],
    'allowed_events' => [
        'order_approved', 
        'subscription_renewed', 
        'subscription_canceled',
        'subscription_late',           
        'subscription_suspended',      
        'payment_failed'               
    ],
    'debug' => ['enabled' => true, 'log_raw_payload' => true]
];

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/SubscriptionService.php';
require_once __DIR__ . '/processor.php';

function logWebhook($level, $message, $context = []) {
    global $config;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$level}] {$message}";
    if (!empty($context)) {
        $logMessage .= " | Dados: " . print_r($context, true);
    }
    $logMessage .= "\n";
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
    sendResponse(200, 'Webhook Kiwify funcionando! Pronto para receber POST.');
}

// Processar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $rawPayload = file_get_contents('php://input');
    $payload = json_decode($rawPayload, true);
    
    logWebhook('info', '========== NOVO WEBHOOK RECEBIDO DA KIWIFY ==========');
    
    if ($config['debug']['enabled'] && $config['debug']['log_raw_payload']) {
        logWebhook('debug', 'Raw payload', ['raw' => $rawPayload]);
        logWebhook('debug', 'Payload decodificado', $payload);
        
        if (isset($payload['Customer'])) {
            logWebhook('debug', 'Customer data', $payload['Customer']);
        }
        if (isset($payload['Product'])) {
            logWebhook('debug', 'Product data', $payload['Product']);
        }
        if (isset($payload['Subscription'])) {
            logWebhook('debug', 'Subscription data', $payload['Subscription']);
        }
        if (isset($payload['Commissions'])) {
            logWebhook('debug', 'Commissions data', $payload['Commissions']);
        }
    }
    
    logWebhook('debug', '========== FIM LOG DEBUG ==========');
    
    // Validar payload
    if (!$payload || !isset($payload['webhook_event_type'])) {
        logWebhook('error', 'Payload inválido - sem webhook_event_type');
        sendResponse(400, 'Payload inválido');
    }
    
    $eventType = $payload['webhook_event_type'];
    
    logWebhook('info', 'Webhook recebido', [
        'event' => $eventType,
        'order_id' => $payload['order_id'] ?? 'N/A'
    ]);
    
    // Verificar se evento é permitido
    if (!in_array($eventType, $config['allowed_events'])) {
        logWebhook('info', 'Evento ignorado - não está na lista de permitidos', ['event' => $eventType]);
        sendResponse(200, 'Evento ignorado: ' . $eventType);
    }
    
    // Processar evento
    try {
        $processor = new KiwifyProcessor($pdo, $config);
        $result = $processor->process($eventType, $payload);
        
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