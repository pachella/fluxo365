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

$tagId = intval($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($tagId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

try {
    // Verificar se a tag pertence ao usuário
    $stmt = $pdo->prepare("
        SELECT t.id
        FROM crm_tags t
        INNER JOIN crm_boards b ON t.board_id = b.id
        WHERE t.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$tagId, $userId]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Tag não encontrada']);
        exit;
    }

    // Deletar tag (CASCADE remove relacionamentos)
    $stmt = $pdo->prepare("DELETE FROM crm_tags WHERE id = ?");
    $stmt->execute([$tagId]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('Erro ao deletar tag: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao deletar tag']);
}
