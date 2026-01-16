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
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $color = trim($_POST['color'] ?? '#6366f1');
    $userId = $_SESSION['user_id'];

    if (empty($name) || $id <= 0) {
        echo "Dados inválidos";
        exit;
    }

    // Verificar se o quadro pertence ao usuário
    $stmt = $pdo->prepare("SELECT id FROM crm_boards WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    if (!$stmt->fetch()) {
        echo "Quadro não encontrado";
        exit;
    }

    // Atualizar quadro
    $stmt = $pdo->prepare("
        UPDATE crm_boards
        SET name = ?, description = ?, color = ?
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$name, $description, $color, $id, $userId]);

    echo "success";

} catch (PDOException $e) {
    http_response_code(500);
    echo "Erro no banco de dados: " . $e->getMessage();
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro interno: " . $e->getMessage();
}
