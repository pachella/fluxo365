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

$boardId = intval($_POST['board_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$color = trim($_POST['color'] ?? '#6366f1');
$userId = $_SESSION['user_id'];

if (empty($name) || $boardId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

try {
    // Verificar se o quadro pertence ao usuário
    $stmt = $pdo->prepare("SELECT id FROM crm_boards WHERE id = ? AND user_id = ?");
    $stmt->execute([$boardId, $userId]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Quadro não encontrado']);
        exit;
    }

    // Criar tag
    $stmt = $pdo->prepare("INSERT INTO crm_tags (board_id, name, color) VALUES (?, ?, ?)");
    $stmt->execute([$boardId, $name, $color]);

    $tagId = $pdo->lastInsertId();

    echo json_encode(['success' => true, 'tag_id' => $tagId]);

} catch (PDOException $e) {
    error_log('Erro ao criar tag: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao criar tag']);
}
