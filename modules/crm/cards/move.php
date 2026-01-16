<?php
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

$cardId = intval($_POST['card_id'] ?? 0);
$newColumnId = intval($_POST['column_id'] ?? 0);
$newPosition = intval($_POST['position'] ?? 0);
$userId = $_SESSION['user_id'];

if ($cardId <= 0 || $newColumnId <= 0 || $newPosition < 0) {
    http_response_code(400);
    echo "Dados inválidos";
    exit;
}

try {
    $pdo->beginTransaction();

    // Verificar se o card e a coluna pertencem ao usuário
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
        $pdo->rollBack();
        http_response_code(404);
        echo "Card não encontrado";
        exit;
    }

    // Verificar se a nova coluna pertence ao mesmo quadro
    $stmt = $pdo->prepare("
        SELECT col.id, col.board_id
        FROM crm_columns col
        INNER JOIN crm_boards b ON col.board_id = b.id
        WHERE col.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$newColumnId, $userId]);
    $newColumn = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$newColumn) {
        $pdo->rollBack();
        http_response_code(404);
        echo "Coluna destino não encontrada";
        exit;
    }

    $oldColumnId = $card['column_id'];
    $oldPosition = $card['position'];

    // Se moveu para outra coluna
    if ($oldColumnId != $newColumnId) {
        // Ajustar posições na coluna antiga (fechar o gap)
        $stmt = $pdo->prepare("
            UPDATE crm_cards
            SET position = position - 1
            WHERE column_id = ? AND position > ?
        ");
        $stmt->execute([$oldColumnId, $oldPosition]);

        // Abrir espaço na nova coluna
        $stmt = $pdo->prepare("
            UPDATE crm_cards
            SET position = position + 1
            WHERE column_id = ? AND position >= ?
        ");
        $stmt->execute([$newColumnId, $newPosition]);

        // Mover o card
        $stmt = $pdo->prepare("
            UPDATE crm_cards
            SET column_id = ?, position = ?
            WHERE id = ?
        ");
        $stmt->execute([$newColumnId, $newPosition, $cardId]);
    } else {
        // Mesma coluna, apenas reordenar
        if ($newPosition < $oldPosition) {
            // Movendo para cima
            $stmt = $pdo->prepare("
                UPDATE crm_cards
                SET position = position + 1
                WHERE column_id = ? AND position >= ? AND position < ?
            ");
            $stmt->execute([$newColumnId, $newPosition, $oldPosition]);
        } else if ($newPosition > $oldPosition) {
            // Movendo para baixo
            $stmt = $pdo->prepare("
                UPDATE crm_cards
                SET position = position - 1
                WHERE column_id = ? AND position > ? AND position <= ?
            ");
            $stmt->execute([$newColumnId, $oldPosition, $newPosition]);
        }

        // Atualizar posição do card
        $stmt = $pdo->prepare("UPDATE crm_cards SET position = ? WHERE id = ?");
        $stmt->execute([$newPosition, $cardId]);
    }

    $pdo->commit();
    echo "success";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log('Erro ao mover card: ' . $e->getMessage());
    echo "Erro ao mover card: " . $e->getMessage();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo "Erro interno: " . $e->getMessage();
}
