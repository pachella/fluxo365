<?php
/**
 * Configuração CRUD para crm_cards
 */

return [
    'table' => 'crm_cards',
    'primary_key' => 'id',
    'ownership_field' => 'created_by',

    'fields' => [
        'column_id' => [
            'required' => true,
            'type' => 'int'
        ],
        'title' => [
            'required' => true,
            'type' => 'string'
        ],
        'description' => [
            'required' => false,
            'type' => 'text'
        ],
        'position' => [
            'required' => false,
            'type' => 'int',
            'default' => 0
        ],
        'assigned_to' => [
            'required' => false,
            'type' => 'int'
        ],
        'priority' => [
            'required' => false,
            'type' => 'string',
            'default' => 'medium'
        ]
    ],

    'on_create' => function($pdo, $cardId, $userId) {
        // Processar tags se fornecidas via $_POST['tags']
        if (!empty($_POST['tags'])) {
            $tags = json_decode($_POST['tags'], true);
            if (is_array($tags)) {
                $stmt = $pdo->prepare("INSERT INTO crm_card_tags (card_id, tag_id) VALUES (?, ?)");
                foreach ($tags as $tagId) {
                    $stmt->execute([$cardId, intval($tagId)]);
                }
            }
        }

        // Ajustar posição do card na coluna
        $columnId = intval($_POST['column_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT MAX(position) as max_pos FROM crm_cards WHERE column_id = ?");
        $stmt->execute([$columnId]);
        $maxPos = $stmt->fetch(PDO::FETCH_ASSOC)['max_pos'] ?? -1;

        $stmt = $pdo->prepare("UPDATE crm_cards SET position = ? WHERE id = ?");
        $stmt->execute([$maxPos + 1, $cardId]);
    },

    'on_update' => function($pdo, $cardId, $userId) {
        // Remover tags antigas
        $stmt = $pdo->prepare("DELETE FROM crm_card_tags WHERE card_id = ?");
        $stmt->execute([$cardId]);

        // Adicionar novas tags
        if (!empty($_POST['tags'])) {
            $tags = json_decode($_POST['tags'], true);
            if (is_array($tags)) {
                $stmt = $pdo->prepare("INSERT INTO crm_card_tags (card_id, tag_id) VALUES (?, ?)");
                foreach ($tags as $tagId) {
                    $stmt->execute([$cardId, intval($tagId)]);
                }
            }
        }
    }
];
