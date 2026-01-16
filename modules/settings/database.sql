-- Tabela de Configurações do Sistema
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configurações padrão
INSERT INTO system_settings (setting_key, setting_value) VALUES
('company_name', 'Fluxo365'),
('primary_color', '#6366f1'),
('secondary_color', '#8b5cf6'),
('button_text_color', '#ffffff'),
('use_gradient', '0'),
('contact_email', ''),
('contact_phone', ''),
('logo_url', 'https://fluxo365.com/wp-content/uploads/2026/01/logo_fluxo.svg')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
