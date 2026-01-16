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

$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$color = trim($_POST['color'] ?? '#6366f1');
$userId = $_SESSION['user_id'];

if (empty($name) || $id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

try {
    // Verificar se o quadro pertence ao usuário
    $stmt = $pdo->prepare("SELECT id FROM crm_boards WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Quadro não encontrado']);
        exit;
    }

    // Atualizar quadro
    $stmt = $pdo->prepare("
        UPDATE crm_boards
        SET name = ?, description = ?, color = ?
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$name, $description, $color, $id, $userId]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('Erro ao atualizar quadro: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar quadro']);
}
