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
$color = trim($_POST['color'] ?? '#64748b');
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

    // Obter posição máxima
    $stmt = $pdo->prepare("SELECT MAX(position) as max_pos FROM crm_columns WHERE board_id = ?");
    $stmt->execute([$boardId]);
    $maxPos = $stmt->fetch(PDO::FETCH_ASSOC)['max_pos'] ?? -1;
    $position = $maxPos + 1;

    // Criar coluna
    $stmt = $pdo->prepare("
        INSERT INTO crm_columns (board_id, name, position, color)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$boardId, $name, $position, $color]);

    $columnId = $pdo->lastInsertId();

    echo json_encode(['success' => true, 'column_id' => $columnId]);

} catch (PDOException $e) {
    error_log('Erro ao criar coluna: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao criar coluna']);
}
