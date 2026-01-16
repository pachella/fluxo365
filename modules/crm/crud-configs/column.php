<?php
/**
 * Configuração CRUD para crm_columns
 */

return [
    'table' => 'crm_columns',
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
        'position' => [
            'required' => false,
            'type' => 'int',
            'default' => 0
        ],
        'color' => [
            'required' => false,
            'type' => 'string',
            'default' => '#64748b'
        ]
    ],

    'on_create' => function($pdo, $columnId, $userId) {
        // Ajustar posição da coluna
        $boardId = intval($_POST['board_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT MAX(position) as max_pos FROM crm_columns WHERE board_id = ?");
        $stmt->execute([$boardId]);
        $maxPos = $stmt->fetch(PDO::FETCH_ASSOC)['max_pos'] ?? -1;

        $stmt = $pdo->prepare("UPDATE crm_columns SET position = ? WHERE id = ?");
        $stmt->execute([$maxPos + 1, $columnId]);
    }
];
