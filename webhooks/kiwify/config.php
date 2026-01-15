<?php
return [
    'auth' => [
        'webhook_secret' => 'teste123',
        'verify_signature' => false,
        'integration_active' => true,
    ],
    
    'logging' => [
        'enabled' => true,
        'log_file' => __DIR__ . '/logs/kiwify.log',
    ],
    
    'allowed_events' => [
        'order.completed'
    ],
    
    'debug' => [
        'enabled' => true,
        'log_raw_payload' => true,
    ]
];
?>