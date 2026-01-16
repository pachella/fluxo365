-- Tabela de Agentes de IA
CREATE TABLE IF NOT EXISTS ai_agents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    whatsapp_number VARCHAR(50),
    system_instructions TEXT,
    model VARCHAR(100) DEFAULT 'gpt-5-nano',
    status ENUM('active', 'paused') DEFAULT 'active',
    color VARCHAR(7) DEFAULT '#8b5cf6',

    -- Opções do Agente
    has_audio BOOLEAN DEFAULT TRUE,
    analyze_images BOOLEAN DEFAULT TRUE,
    quotes_enabled BOOLEAN DEFAULT TRUE,
    pause_attendance BOOLEAN DEFAULT TRUE,
    group_messages BOOLEAN DEFAULT TRUE,
    history_limit INT DEFAULT 10,

    -- Estatísticas
    credits_spent INT DEFAULT 0,
    conversations_count INT DEFAULT 0,
    paused_conversations INT DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Conhecimento (arquivos) dos Agentes
CREATE TABLE IF NOT EXISTS ai_agent_knowledge (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    file_type VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (agent_id) REFERENCES ai_agents(id) ON DELETE CASCADE,
    INDEX idx_agent_id (agent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Configuração CRM por Agente
CREATE TABLE IF NOT EXISTS ai_agent_crm_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    board_id INT,
    stage VARCHAR(255),
    default_value DECIMAL(10, 2) DEFAULT 0.00,
    default_observation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (agent_id) REFERENCES ai_agents(id) ON DELETE CASCADE,
    FOREIGN KEY (board_id) REFERENCES crm_boards(id) ON DELETE SET NULL,
    INDEX idx_agent_id (agent_id),
    INDEX idx_board_id (board_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Tarefas dos Agentes (para adicionar tarefas customizadas)
CREATE TABLE IF NOT EXISTS ai_agent_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    task_name VARCHAR(255) NOT NULL,
    task_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (agent_id) REFERENCES ai_agents(id) ON DELETE CASCADE,
    INDEX idx_agent_id (agent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
