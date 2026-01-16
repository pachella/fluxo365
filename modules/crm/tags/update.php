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

$tagId = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$color = trim($_POST['color'] ?? '#6366f1');
$userId = $_SESSION['user_id'];

if (empty($name) || $tagId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
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

    // Atualizar tag
    $stmt = $pdo->prepare("UPDATE crm_tags SET name = ?, color = ? WHERE id = ?");
    $stmt->execute([$name, $color, $tagId]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('Erro ao atualizar tag: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar tag']);
}
