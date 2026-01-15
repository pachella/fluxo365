<?php
/**
 * Webhook da Ticto - Versão Simplificada
 * Apenas ativa/desativa plano PRO
 */

// Log de debug
$logFile = __DIR__ . '/ticto_webhook.log';

// Capturar dados do webhook
$rawPayload = file_get_contents('php://input');
$payload = json_decode($rawPayload, true);

// Log do payload recebido
file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Webhook recebido:\n" . print_r($payload, true) . "\n\n", FILE_APPEND);

// Verificar se é um JSON válido
if (!$payload) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload inválido']);
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "ERRO: Payload inválido\n\n", FILE_APPEND);
    exit;
}

// Conectar ao banco de dados
require_once(__DIR__ . "/../core/db.php");

try {
    // Extrair dados do webhook (formato real da Ticto)
    $status = $payload['status'] ?? '';
    $customerEmail = $payload['customer']['email'] ?? '';
    $customerName = $payload['customer']['name'] ?? '';

    // Definir "evento" baseado no status
    $event = $status;

    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Processando evento: {$event} para {$customerEmail}\n", FILE_APPEND);

    // Buscar usuário pelo email
    $stmt = $pdo->prepare("SELECT id, email, plan FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $customerEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "AVISO: Usuário não encontrado: {$customerEmail}\n\n", FILE_APPEND);
        http_response_code(404);
        echo json_encode(['error' => 'Usuário não encontrado', 'email' => $customerEmail]);
        exit;
    }

    // Processar evento baseado no status
    switch ($status) {
        case 'paid':
        case 'approved':
        case 'active':
            // ATIVAR PRO POR 30 DIAS
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

            $updateStmt = $pdo->prepare("
                UPDATE users
                SET plan = 'pro',
                    pro_expires_at = :expires_at
                WHERE id = :user_id
            ");

            $updateStmt->execute([
                ':expires_at' => $expiresAt,
                ':user_id' => $user['id']
            ]);

            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "✓ Usuário #{$user['id']} ativado como PRO até {$expiresAt}\n\n", FILE_APPEND);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'PRO ativado',
                'user_id' => $user['id'],
                'expires_at' => $expiresAt
            ]);
            break;

        case 'cancelled':
        case 'canceled':
        case 'refunded':
        case 'chargeback':
        case 'expired':
            // DESATIVAR PRO
            $updateStmt = $pdo->prepare("
                UPDATE users
                SET plan = 'free',
                    pro_expires_at = NULL
                WHERE id = :user_id
            ");

            $updateStmt->execute([':user_id' => $user['id']]);

            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "✓ Usuário #{$user['id']} retornou para FREE\n\n", FILE_APPEND);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'PRO desativado',
                'user_id' => $user['id']
            ]);
            break;

        default:
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "AVISO: Evento não processado: {$event}\n\n", FILE_APPEND);
            http_response_code(200);
            echo json_encode(['message' => 'Evento não processado', 'event' => $event]);
            break;
    }

} catch (Exception $e) {
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "ERRO: " . $e->getMessage() . "\n\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
