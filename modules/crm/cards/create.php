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

$columnId = intval($_POST['column_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$priority = trim($_POST['priority'] ?? 'medium');
$assignedTo = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
$userId = $_SESSION['user_id'];

if (empty($title) || $columnId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

try {
    // Verificar se a coluna existe e pertence ao usuário
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

    // Obter posição máxima
    $stmt = $pdo->prepare("SELECT MAX(position) as max_pos FROM crm_cards WHERE column_id = ?");
    $stmt->execute([$columnId]);
    $maxPos = $stmt->fetch(PDO::FETCH_ASSOC)['max_pos'] ?? -1;
    $position = $maxPos + 1;

    // Criar card
    $stmt = $pdo->prepare("
        INSERT INTO crm_cards (column_id, title, description, position, assigned_to, priority, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$columnId, $title, $description, $position, $assignedTo, $priority, $userId]);

    $cardId = $pdo->lastInsertId();

    // Processar tags se fornecidas
    if (!empty($_POST['tags'])) {
        $tags = json_decode($_POST['tags'], true);
        if (is_array($tags)) {
            $stmtTag = $pdo->prepare("INSERT INTO crm_card_tags (card_id, tag_id) VALUES (?, ?)");
            foreach ($tags as $tagId) {
                $stmtTag->execute([$cardId, intval($tagId)]);
            }
        }
    }

    echo json_encode(['success' => true, 'card_id' => $cardId]);

} catch (PDOException $e) {
    error_log('Erro ao criar card: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao criar card']);
}
