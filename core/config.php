<?php
/**
 * Configurações Globais do Sistema
 *
 * Este arquivo contém constantes e configurações que são usadas
 * em todo o sistema.
 */

// ==========================================
// CONFIGURAÇÕES DE DOMÍNIO
// ==========================================

/**
 * Domínio público para formulários
 *
 * Define qual domínio será usado nos links públicos dos formulários.
 *
 * Exemplos:
 * - 'formtalk.app' -> https://formtalk.app/f/123
 * - 'forms.meusite.com' -> https://forms.meusite.com/f/123
 * - null -> usa o domínio atual (padrão)
 *
 * IMPORTANTE: Para usar um domínio diferente do dashboard:
 * 1. Configure seu DNS para apontar o domínio para o mesmo servidor
 * 2. Configure o virtual host/nginx para aceitar o domínio
 * 3. Certifique-se de que o SSL está configurado para o domínio
 */
define('PUBLIC_FORM_DOMAIN', null); // Detecta automaticamente o domínio atual

/**
 * Protocolo padrão (http ou https)
 * Se null, detecta automaticamente baseado na requisição
 */
define('PUBLIC_FORM_PROTOCOL', null); // Detecta automaticamente (http/https)

// ==========================================
// FUNÇÕES AUXILIARES
// ==========================================

/**
 * Gera a URL completa de um formulário público
 *
 * @param int $formId ID do formulário
 * @return string URL completa do formulário
 */
function getPublicFormUrl($formId) {
    $protocol = PUBLIC_FORM_PROTOCOL ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
    $domain = PUBLIC_FORM_DOMAIN ?? $_SERVER['HTTP_HOST'];

    return "{$protocol}://{$domain}/f/{$formId}";
}

/**
 * Gera a URL base pública (sem o path)
 *
 * @return string URL base (ex: https://formtalk.app)
 */
function getPublicBaseUrl() {
    $protocol = PUBLIC_FORM_PROTOCOL ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
    $domain = PUBLIC_FORM_DOMAIN ?? $_SERVER['HTTP_HOST'];

    return "{$protocol}://{$domain}";
}
