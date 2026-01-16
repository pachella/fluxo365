<?php
/**
 * Endpoint para reordenar colunas
 */

ob_clean();
session_start();
require_once(__DIR__ . "/../../core/db.php");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo "Não autorizado";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo "Método não permitido";
    exit;
}

try {
    $boardId = intval($_POST['board_id'] ?? 0);
    $order = json_decode($_POST['order'] ?? '[]', true);
    $userId = $_SESSION['user_id'];

    if ($boardId <= 0 || empty($order)) {
        echo "Dados inválidos";
        exit;
    }

    // Verificar se o quadro pertence ao usuário
    $stmt = $pdo->prepare("SELECT id FROM crm_boards WHERE id = ? AND user_id = ?");
    $stmt->execute([$boardId, $userId]);

    if (!$stmt->fetch()) {
        echo "Quadro não encontrado";
        exit;
    }

    // Atualizar posições das colunas
    $stmt = $pdo->prepare("UPDATE crm_columns SET position = ? WHERE id = ? AND board_id = ?");

    foreach ($order as $position => $columnId) {
        $stmt->execute([$position, intval($columnId), $boardId]);
    }

    echo "success";

} catch (PDOException $e) {
    http_response_code(500);
    echo "Erro no banco de dados: " . $e->getMessage();
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro interno: " . $e->getMessage();
}
