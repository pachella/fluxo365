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

$columnId = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$color = trim($_POST['color'] ?? '#64748b');
$userId = $_SESSION['user_id'];

if (empty($name) || $columnId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

try {
    // Verificar se a coluna pertence ao usuário
    $stmt = $pdo->prepare("
        SELECT c.id
        FROM crm_columns c
        INNER JOIN crm_boards b ON c.board_id = b.id
        WHERE c.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$columnId, $userId]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Coluna não encontrada']);
        exit;
    }

    // Atualizar coluna
    $stmt = $pdo->prepare("UPDATE crm_columns SET name = ?, color = ? WHERE id = ?");
    $stmt->execute([$name, $color, $columnId]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('Erro ao atualizar coluna: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar coluna']);
}
