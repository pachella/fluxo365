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

$id = intval($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
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

    // Deletar quadro (CASCADE deleta colunas, cards e tags automaticamente)
    $stmt = $pdo->prepare("DELETE FROM crm_boards WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('Erro ao deletar quadro: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao deletar quadro']);
}
