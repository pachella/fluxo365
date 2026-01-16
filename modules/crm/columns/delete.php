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

$columnId = intval($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($columnId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

try {
    // Verificar se a coluna pertence ao usuário
    $stmt = $pdo->prepare("
        SELECT c.id, c.board_id, c.position
        FROM crm_columns c
        INNER JOIN crm_boards b ON c.board_id = b.id
        WHERE c.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$columnId, $userId]);
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$column) {
        echo json_encode(['success' => false, 'error' => 'Coluna não encontrada']);
        exit;
    }

    // Verificar se há cards na coluna
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM crm_cards WHERE column_id = ?");
    $stmt->execute([$columnId]);
    $cardCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($cardCount > 0) {
        echo json_encode(['success' => false, 'error' => 'Não é possível excluir coluna com cards. Mova ou delete os cards primeiro.']);
        exit;
    }

    // Deletar coluna
    $stmt = $pdo->prepare("DELETE FROM crm_columns WHERE id = ?");
    $stmt->execute([$columnId]);

    // Reordenar colunas restantes
    $stmt = $pdo->prepare("
        UPDATE crm_columns
        SET position = position - 1
        WHERE board_id = ? AND position > ?
    ");
    $stmt->execute([$column['board_id'], $column['position']]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('Erro ao deletar coluna: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao deletar coluna']);
}
