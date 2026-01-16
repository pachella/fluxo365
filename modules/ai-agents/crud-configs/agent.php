<?php
return [
    'table' => 'ai_agents',
    'primary_key' => 'id',
    'ownership_field' => 'user_id',
    'fields' => [
        'name' => ['required' => true, 'type' => 'string'],
        'whatsapp_number' => ['required' => false, 'type' => 'string'],
        'system_instructions' => ['required' => false, 'type' => 'text'],
        'model' => ['required' => false, 'type' => 'string', 'default' => 'gpt-5-nano'],
        'status' => ['required' => false, 'type' => 'string', 'default' => 'active'],
        'color' => ['required' => false, 'type' => 'string', 'default' => '#8b5cf6'],

        // Opções do Agente
        'has_audio' => ['required' => false, 'type' => 'boolean', 'default' => true],
        'analyze_images' => ['required' => false, 'type' => 'boolean', 'default' => true],
        'quotes_enabled' => ['required' => false, 'type' => 'boolean', 'default' => true],
        'pause_attendance' => ['required' => false, 'type' => 'boolean', 'default' => true],
        'group_messages' => ['required' => false, 'type' => 'boolean', 'default' => true],
        'history_limit' => ['required' => false, 'type' => 'int', 'default' => 10],
    ],
    'on_update' => function($pdo, $agentId, $userId) {
        // Processar configuração CRM se fornecida
        if (isset($_POST['crm_board_id'])) {
            $boardId = intval($_POST['crm_board_id']) ?: null;
            $stage = trim($_POST['crm_stage'] ?? '');
            $defaultValue = floatval($_POST['crm_default_value'] ?? 0);
            $defaultObservation = trim($_POST['crm_default_observation'] ?? '');

            // Verificar se já existe configuração CRM para este agente
            $stmt = $pdo->prepare("SELECT id FROM ai_agent_crm_config WHERE agent_id = ?");
            $stmt->execute([$agentId]);
            $existingConfig = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingConfig) {
                // Atualizar configuração existente
                $stmt = $pdo->prepare("
                    UPDATE ai_agent_crm_config
                    SET board_id = ?, stage = ?, default_value = ?, default_observation = ?
                    WHERE agent_id = ?
                ");
                $stmt->execute([$boardId, $stage, $defaultValue, $defaultObservation, $agentId]);
            } else {
                // Criar nova configuração
                $stmt = $pdo->prepare("
                    INSERT INTO ai_agent_crm_config (agent_id, board_id, stage, default_value, default_observation)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$agentId, $boardId, $stage, $defaultValue, $defaultObservation]);
            }
        }
    },
    'on_create' => function($pdo, $agentId, $userId) {
        // Processar configuração CRM se fornecida
        if (isset($_POST['crm_board_id']) && !empty($_POST['crm_board_id'])) {
            $boardId = intval($_POST['crm_board_id']) ?: null;
            $stage = trim($_POST['crm_stage'] ?? '');
            $defaultValue = floatval($_POST['crm_default_value'] ?? 0);
            $defaultObservation = trim($_POST['crm_default_observation'] ?? '');

            $stmt = $pdo->prepare("
                INSERT INTO ai_agent_crm_config (agent_id, board_id, stage, default_value, default_observation)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$agentId, $boardId, $stage, $defaultValue, $defaultObservation]);
        }
    }
];
