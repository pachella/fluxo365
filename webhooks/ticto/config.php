<?php
return [
    'auth' => [
        'webhook_secret' => '', // Preencher se a Ticto usar
        'verify_signature' => false,
        'integration_active' => true,
    ],
    
    'logging' => [
        'enabled' => true,
        'log_file' => __DIR__ . '/logs/ticto.log',
    ],
    
    'allowed_events' => [
        'authorized',           // Pagamento aprovado
        'canceled',             // Assinatura cancelada
        'overdue',              // Pagamento atrasado
        'charged',              // Assinatura renovada
        'subscription_renewed', // Possível alternativa para renovação
    ],
    
    'debug' => [
        'enabled' => true,
        'log_raw_payload' => true,
    ]
];