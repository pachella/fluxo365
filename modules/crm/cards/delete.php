<?php
session_start();
require_once("../../core/db.php");

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

$cardId = intval($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($cardId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

try {
    // Verificar se o card pertence ao usuário
    $stmt = $pdo->prepare("
        SELECT c.id, c.column_id, c.position
        FROM crm_cards c
        INNER JOIN crm_columns col ON c.column_id = col.id
        INNER JOIN crm_boards b ON col.board_id = b.id
        WHERE c.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$cardId, $userId]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$card) {
        echo json_encode(['success' => false, 'error' => 'Card não encontrado']);
        exit;
    }

    // Deletar card
    $stmt = $pdo->prepare("DELETE FROM crm_cards WHERE id = ?");
    $stmt->execute([$cardId]);

    // Reordenar cards restantes na mesma coluna
    $stmt = $pdo->prepare("
        UPDATE crm_cards
        SET position = position - 1
        WHERE column_id = ? AND position > ?
    ");
    $stmt->execute([$card['column_id'], $card['position']]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('Erro ao deletar card: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao deletar card']);
}
