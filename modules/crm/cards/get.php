<?php
session_start();
require_once("../../core/db.php");

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$cardId = intval($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($cardId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

try {
    // Buscar card
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as assigned_name
        FROM crm_cards c
        LEFT JOIN users u ON c.assigned_to = u.id
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

    // Buscar tags do card
    $stmt = $pdo->prepare("
        SELECT t.id
        FROM crm_tags t
        INNER JOIN crm_card_tags ct ON t.id = ct.tag_id
        WHERE ct.card_id = ?
    ");
    $stmt->execute([$cardId]);
    $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $card['tags'] = $tags;

    echo json_encode($card);

} catch (PDOException $e) {
    error_log('Erro ao buscar card: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao buscar card']);
}
