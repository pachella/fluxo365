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

$cardId = intval($_POST['id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$priority = trim($_POST['priority'] ?? 'medium');
$assignedTo = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
$userId = $_SESSION['user_id'];

if (empty($title) || $cardId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

try {
    // Verificar se o card pertence ao usuário
    $stmt = $pdo->prepare("
        SELECT c.id
        FROM crm_cards c
        INNER JOIN crm_columns col ON c.column_id = col.id
        INNER JOIN crm_boards b ON col.board_id = b.id
        WHERE c.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$cardId, $userId]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Card não encontrado']);
        exit;
    }

    // Atualizar card
    $stmt = $pdo->prepare("
        UPDATE crm_cards
        SET title = ?, description = ?, priority = ?, assigned_to = ?
        WHERE id = ?
    ");
    $stmt->execute([$title, $description, $priority, $assignedTo, $cardId]);

    // Atualizar tags se fornecidas
    if (isset($_POST['tags'])) {
        // Remover tags antigas
        $stmt = $pdo->prepare("DELETE FROM crm_card_tags WHERE card_id = ?");
        $stmt->execute([$cardId]);

        // Adicionar novas tags
        $tags = json_decode($_POST['tags'], true);
        if (is_array($tags) && !empty($tags)) {
            $stmtTag = $pdo->prepare("INSERT INTO crm_card_tags (card_id, tag_id) VALUES (?, ?)");
            foreach ($tags as $tagId) {
                $stmtTag->execute([$cardId, intval($tagId)]);
            }
        }
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('Erro ao atualizar card: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar card']);
}
