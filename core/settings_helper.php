<?php
/**
 * Helper para carregar configurações do sistema
 */

function getSystemSettings($pdo) {
    static $settings = null;

    if ($settings !== null) {
        return $settings;
    }

    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        error_log('Erro ao buscar configurações: ' . $e->getMessage());
        $settings = [];
    }

    // Valores padrão
    $defaults = [
        'company_name' => 'Fluxo365',
        'primary_color' => '#6366f1',
        'secondary_color' => '#8b5cf6',
        'button_text_color' => '#ffffff',
        'use_gradient' => '0',
        'contact_email' => '',
        'contact_phone' => '',
        'logo_url' => 'https://fluxo365.com/wp-content/uploads/2026/01/logo_fluxo.svg'
    ];

    return array_merge($defaults, $settings);
}

function getButtonStyles($pdo) {
    $settings = getSystemSettings($pdo);

    $primaryColor = $settings['primary_color'];
    $secondaryColor = $settings['secondary_color'];
    $textColor = $settings['button_text_color'];
    $useGradient = $settings['use_gradient'] == '1';

    $styles = "
    :root {
        --btn-primary-color: {$primaryColor};
        --btn-secondary-color: {$secondaryColor};
        --btn-text-color: {$textColor};
    }

    /* Botões Primários */
    .btn-primary {
        color: {$textColor} !important;
    ";

    if ($useGradient) {
        $styles .= "
        background: linear-gradient(135deg, {$primaryColor} 0%, {$secondaryColor} 100%) !important;
        border: none !important;
        ";
    } else {
        $styles .= "
        background-color: {$primaryColor} !important;
        border-color: {$primaryColor} !important;
        ";
    }

    $styles .= "
    }

    .btn-primary:hover {
        color: {$textColor} !important;
    ";

    if ($useGradient) {
        $styles .= "
        background: linear-gradient(135deg, {$secondaryColor} 0%, {$primaryColor} 100%) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        ";
    } else {
        // Escurecer um pouco no hover
        $styles .= "
        filter: brightness(0.9);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        ";
    }

    $styles .= "
    }

    /* Badge Primary */
    .badge-primary {
        background-color: {$primaryColor} !important;
        color: {$textColor} !important;
        border-color: {$primaryColor} !important;
    }

    /* Toggle Primary */
    .toggle-primary:checked {
        background-color: {$primaryColor} !important;
        border-color: {$primaryColor} !important;
    }

    /* Links Primary */
    .link-primary {
        color: {$primaryColor} !important;
    }

    /* Avatar com cor primária */
    .bg-primary {
        background-color: {$primaryColor} !important;
    }

    /* Text Primary */
    .text-primary {
        color: {$primaryColor} !important;
    }

    /* Menu ativo - background clean com cor primária */
    .menu li > a.active {
        background-color: {$primaryColor}20 !important;
        color: {$primaryColor} !important;
        border: 1px solid {$primaryColor}40 !important;
        font-weight: 600 !important;
    }

    .dark .menu li > a.active {
        background-color: {$primaryColor}20 !important;
        color: {$primaryColor} !important;
        border: 1px solid {$primaryColor}40 !important;
    }
    ";

    return $styles;
}
