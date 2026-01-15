<?php
session_start();
require_once(__DIR__ . "/../../../core/db.php");
require_once __DIR__ . '/../../../core/PermissionManager.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: /auth/login");
    exit;
}

$formId = $_GET['id'] ?? null;

if (!$formId) {
    header("Location: /modules/forms/list.php");
    exit;
}

$permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

// Buscar dados do formulário
$sql = "SELECT * FROM forms WHERE id = :id";
if (!$permissionManager->canViewAllRecords()) {
    $sql .= " AND user_id = :user_id";
}

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $formId, PDO::PARAM_INT);
if (!$permissionManager->canViewAllRecords()) {
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
}

$stmt->execute();
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) {
    header("Location: /modules/forms/list.php");
    exit;
}

// Buscar todos os campos do formulário
$fieldsStmt = $pdo->prepare("
    SELECT id, label, type
    FROM form_fields
    WHERE form_id = :form_id
    ORDER BY order_index ASC
");
$fieldsStmt->execute([':form_id' => $formId]);
$fields = $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar todas as respostas
$responsesStmt = $pdo->prepare("
    SELECT id, created_at
    FROM form_responses
    WHERE form_id = :form_id
    ORDER BY created_at DESC
");
$responsesStmt->execute([':form_id' => $formId]);
$responses = $responsesStmt->fetchAll(PDO::FETCH_ASSOC);

// Preparar CSV
$filename = "respostas_" . preg_replace('/[^a-zA-Z0-9]/', '_', $form['title']) . "_" . date('Y-m-d_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Criar output stream
$output = fopen('php://output', 'w');

// Adicionar BOM para UTF-8 (importante para Excel reconhecer acentos)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Criar cabeçalho do CSV
$headers = ['#', 'Data', 'Hora'];
foreach ($fields as $field) {
    $headers[] = $field['label'];
}
fputcsv($output, $headers, ';');

// Preencher dados
foreach ($responses as $index => $response) {
    $row = [
        count($responses) - $index, // Número da resposta
        date('d/m/Y', strtotime($response['created_at'])), // Data
        date('H:i', strtotime($response['created_at'])) // Hora
    ];

    // Buscar respostas para cada campo
    foreach ($fields as $field) {
        $answerStmt = $pdo->prepare("
            SELECT answer
            FROM response_answers
            WHERE response_id = :response_id AND field_id = :field_id
        ");
        $answerStmt->execute([
            ':response_id' => $response['id'],
            ':field_id' => $field['id']
        ]);
        $answer = $answerStmt->fetch(PDO::FETCH_ASSOC);

        $row[] = $answer ? $answer['answer'] : '';
    }

    fputcsv($output, $row, ';');
}

fclose($output);
exit;
?>
