<?php
/**
 * Versionamento do Sistema
 * Formato: Semantic Versioning (MAJOR.MINOR.PATCH)
 *
 * MAJOR: Mudanças incompatíveis com versões anteriores
 * MINOR: Novas funcionalidades compatíveis com versões anteriores
 * PATCH: Correções de bugs compatíveis com versões anteriores
 */

define('APP_VERSION', '1.0.0');
define('APP_VERSION_DATE', '2026-01-16');
define('APP_NAME', 'Fluxo365 CRM');

/**
 * Retorna a versão formatada do sistema
 * @param bool $includeDate Incluir data da versão
 * @return string
 */
function getAppVersion($includeDate = false) {
    $version = 'v' . APP_VERSION;
    if ($includeDate) {
        $version .= ' (' . APP_VERSION_DATE . ')';
    }
    return $version;
}
