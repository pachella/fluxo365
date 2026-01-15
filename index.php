<?php
/**
 * Página Inicial - Fluxo365
 */

session_start();

// Verificar se o usuário está logado
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Usuário logado - redirecionar para dashboard
    header("Location: /dashboard/");
    exit;
} else {
    // Usuário não logado - redirecionar para login
    header("Location: /auth/login.php");
    exit;
}
?>