<?php
/**
 * Cache Helper
 * Usa versão do sistema para cache busting de CSS/JS
 * Garante que todos os assets sejam atualizados juntos
 */

require_once __DIR__ . '/version.php';

/**
 * Gera URL completa com cache busting
 * @param string $url URL relativa do recurso
 * @return string URL com parâmetro de versão
 */
function assetUrl($url) {
    return $url . '?v=' . APP_VERSION;
}

/**
 * Gera versão de cache para um arquivo específico (legacy)
 * @param string $filePath Caminho absoluto ou relativo a partir da raiz
 * @return string Timestamp do arquivo ou fallback
 * @deprecated Use APP_VERSION diretamente
 */
function getCacheVersion($filePath) {
    // Se o caminho for relativo, converter para absoluto
    if ($filePath[0] !== '/') {
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($filePath, '/');
    }

    // Se arquivo existe, retornar filemtime
    if (file_exists($filePath)) {
        return filemtime($filePath);
    }

    // Fallback: usar versão do sistema
    return APP_VERSION;
}

