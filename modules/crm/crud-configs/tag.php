<?php
/**
 * Configuração CRUD para crm_tags
 */

return [
    'table' => 'crm_tags',
    'primary_key' => 'id',
    // Ownership verificado via board_id

    'fields' => [
        'board_id' => [
            'required' => true,
            'type' => 'int'
        ],
        'name' => [
            'required' => true,
            'type' => 'string'
        ],
        'color' => [
            'required' => false,
            'type' => 'string',
            'default' => '#6366f1'
        ]
    ]
];
