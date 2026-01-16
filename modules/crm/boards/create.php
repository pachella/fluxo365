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

try {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $color = trim($_POST['color'] ?? '#6366f1');
    $userId = $_SESSION['user_id'];

    if (empty($name)) {
        echo "Nome é obrigatório";
        exit;
    }

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

    echo "success";

} catch (PDOException $e) {
    http_response_code(500);
    echo "Erro no banco de dados: " . $e->getMessage();
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro interno: " . $e->getMessage();
}
