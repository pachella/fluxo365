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

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$color = trim($_POST['color'] ?? '#6366f1');
$userId = $_SESSION['user_id'];

if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Nome é obrigatório']);
    exit;
}

try {
    // Criar quadro
    $stmt = $pdo->prepare("
        INSERT INTO crm_boards (name, description, color, user_id)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$name, $description, $color, $userId]);

    $boardId = $pdo->lastInsertId();

    // Criar colunas padrão
    $defaultColumns = [
        ['name' => 'Lead', 'color' => '#64748b', 'position' => 0],
        ['name' => 'Qualificado', 'color' => '#3b82f6', 'position' => 1],
        ['name' => 'Proposta', 'color' => '#8b5cf6', 'position' => 2],
        ['name' => 'Negociação', 'color' => '#f59e0b', 'position' => 3],
        ['name' => 'Ganho', 'color' => '#10b981', 'position' => 4],
        ['name' => 'Perdido', 'color' => '#ef4444', 'position' => 5]
    ];

    $stmtColumn = $pdo->prepare("
        INSERT INTO crm_columns (board_id, name, color, position)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($defaultColumns as $column) {
        $stmtColumn->execute([
            $boardId,
            $column['name'],
            $column['color'],
            $column['position']
        ]);
    }

    echo json_encode(['success' => true, 'board_id' => $boardId]);

} catch (PDOException $e) {
    error_log('Erro ao criar quadro: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao criar quadro']);
}
