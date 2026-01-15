<?php
/**
 * Endpoint: Buscar templates disponíveis
 *
 * Retorna todos os formulários da pasta "Templates" (id=8)
 * que podem ser usados como templates pelos usuários
 */

session_start();
require_once(__DIR__ . "/../../core/db.php");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit();
}

try {
    // Buscar formulários da pasta Templates (id=8) que estão ativos
    $stmt = $pdo->prepare("
        SELECT
            id,
            title,
            description,
            icon,
            color,
            display_mode
        FROM forms
        WHERE folder_id = 8
        AND status = 'ativo'
        ORDER BY title ASC
    ");

    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'templates' => $templates
    ]);

} catch (PDOException $e) {
    error_log("Erro ao buscar templates: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar templates']);
}
