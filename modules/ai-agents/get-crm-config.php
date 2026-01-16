<?php
ob_clean();
session_start();
require_once(__DIR__ . "/../core/db.php");

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$agentId = intval($_GET['agent_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($agentId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

try {
    // Verificar se o agente pertence ao usuário
    $stmt = $pdo->prepare("SELECT id FROM ai_agents WHERE id = ? AND user_id = ?");
    $stmt->execute([$agentId, $userId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Agente não encontrado']);
        exit;
    }

    // Buscar configuração CRM
    $stmt = $pdo->prepare("
        SELECT board_id, stage, default_value, default_observation
        FROM ai_agent_crm_config
        WHERE agent_id = ?
    ");
    $stmt->execute([$agentId]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($config ?: null);

} catch (PDOException $e) {
    http_response_code(500);
    error_log('Erro ao buscar configuração CRM: ' . $e->getMessage());
    echo json_encode(['error' => 'Erro ao buscar configuração']);
}
