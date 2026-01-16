<?php
/**
 * Configuração CRUD para crm_boards
 */

return [
    'table' => 'crm_boards',
    'primary_key' => 'id',
    'ownership_field' => 'user_id',

    'fields' => [
        'name' => [
            'required' => true,
            'type' => 'string'
        ],
        'description' => [
            'required' => false,
            'type' => 'text'
        ],
        'color' => [
            'required' => false,
            'type' => 'string',
            'default' => '#6366f1'
        ]
    ],

    // Callback executado após criar um novo quadro
    'on_create' => function($pdo, $boardId, $userId) {
        // Criar colunas padrão
        $defaultColumns = [
            ['name' => 'Lead', 'color' => '#64748b', 'position' => 0],
            ['name' => 'Qualificado', 'color' => '#3b82f6', 'position' => 1],
            ['name' => 'Proposta', 'color' => '#8b5cf6', 'position' => 2],
            ['name' => 'Negociação', 'color' => '#f59e0b', 'position' => 3],
            ['name' => 'Ganho', 'color' => '#10b981', 'position' => 4],
            ['name' => 'Perdido', 'color' => '#ef4444', 'position' => 5]
        ];

        $stmt = $pdo->prepare("
            INSERT INTO crm_columns (board_id, name, color, position)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($defaultColumns as $column) {
            $stmt->execute([
                $boardId,
                $column['name'],
                $column['color'],
                $column['position']
            ]);
        }
    }
];
