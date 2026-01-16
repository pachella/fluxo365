<?php
/**
 * Configuração CRUD para users
 */

return [
    'table' => 'users',
    'primary_key' => 'id',
    // Usuários não têm ownership_field porque admin pode ver todos

    'fields' => [
        'name' => [
            'required' => true,
            'type' => 'string'
        ],
        'email' => [
            'required' => true,
            'type' => 'string'
        ],
        'password' => [
            'required' => false, // Opcional na edição
            'type' => 'string'
        ],
        'role' => [
            'required' => true,
            'type' => 'string',
            'default' => 'client'
        ],
        'status' => [
            'required' => false,
            'type' => 'string',
            'default' => 'active'
        ]
    ],

    // Callback executado antes de salvar
    'on_create' => function($pdo, $userId, $currentUserId) {
        // Hash da senha já é feito no save.php dos usuários
    }
];
