#!/usr/bin/env php
<?php
/**
 * CRON Job: Verificar e expirar assinaturas PRO vencidas
 * Executar diariamente à meia-noite
 *
 * Configurar no crontab:
 * 0 0 * * * /usr/bin/php /home/user/form_system/cron/check_expired_subscriptions.php
 */

require_once(__DIR__ . "/../core/db.php");

$logFile = __DIR__ . '/subscriptions_check.log';
$now = date('Y-m-d H:i:s');

file_put_contents($logFile, "\n" . str_repeat('=', 80) . "\n", FILE_APPEND);
file_put_contents($logFile, "[{$now}] Verificando assinaturas PRO expiradas...\n", FILE_APPEND);

try {
    // Buscar usuários PRO com data de expiração vencida
    $stmt = $pdo->prepare("
        SELECT id, email, user_name, pro_expires_at
        FROM users
        WHERE pro_expires_at < NOW()
        AND plan = 'pro'
    ");
    $stmt->execute();
    $expiredUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($expiredUsers) === 0) {
        file_put_contents($logFile, "[{$now}] Nenhuma assinatura expirada.\n", FILE_APPEND);
        echo "Nenhuma assinatura expirada.\n";
        exit(0);
    }

    // Retornar usuários expirados para FREE
    foreach ($expiredUsers as $user) {
        $updateStmt = $pdo->prepare("
            UPDATE users
            SET plan = 'free',
                pro_expires_at = NULL
            WHERE id = :user_id
        ");
        $updateStmt->execute([':user_id' => $user['id']]);

        $message = "✓ Usuário #{$user['id']} ({$user['email']}) retornou para FREE - expirado em {$user['pro_expires_at']}";
        file_put_contents($logFile, "[{$now}] {$message}\n", FILE_APPEND);
        echo "{$message}\n";
    }

    $total = count($expiredUsers);
    file_put_contents($logFile, "[{$now}] Total: {$total} assinaturas expiradas\n", FILE_APPEND);
    echo "\nTotal: {$total} assinaturas expiradas.\n";

} catch (Exception $e) {
    $error = "ERRO: " . $e->getMessage();
    file_put_contents($logFile, "[{$now}] {$error}\n", FILE_APPEND);
    echo "{$error}\n";
    exit(1);
}
