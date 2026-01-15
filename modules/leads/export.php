<?php
session_start();
require_once(__DIR__ . "/../../core/db.php");
require_once(__DIR__ . "/../../core/PermissionManager.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: /auth/login");
    exit;
}

$permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

// Se for exportação de um lead específico
$leadId = $_GET['id'] ?? null;

// Parâmetros de filtro (mesmos da listagem)
$filterForm = $_GET['form_id'] ?? '';
$filterSearch = $_GET['search'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

// Construir query
$sql = "SELECT fr.*, f.title as form_title
        FROM form_responses fr
        INNER JOIN forms f ON fr.form_id = f.id
        WHERE 1=1";

$params = [];

// Filtro de permissão
if (!$permissionManager->canViewAllRecords()) {
    $sql .= " AND f.user_id = :user_id";
    $params[':user_id'] = $_SESSION['user_id'];
}

// Se for um lead específico
if ($leadId) {
    $sql .= " AND fr.id = :id";
    $params[':id'] = $leadId;
} else {
    // Aplicar filtros
    if (!empty($filterForm)) {
        $sql .= " AND fr.form_id = :form_id";
        $params[':form_id'] = $filterForm;
    }

    if (!empty($filterSearch)) {
        $sql .= " AND (fr.answers LIKE :search)";
        $params[':search'] = '%' . $filterSearch . '%';
    }

    if (!empty($filterDateFrom)) {
        $sql .= " AND DATE(fr.created_at) >= :date_from";
        $params[':date_from'] = $filterDateFrom;
    }
    if (!empty($filterDateTo)) {
        $sql .= " AND DATE(fr.created_at) <= :date_to";
        $params[':date_to'] = $filterDateTo;
    }
}

$sql .= " ORDER BY fr.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($leads)) {
    header("Location: index.php?error=no_data");
    exit;
}

// Buscar todos os campos únicos
$allFields = [];
foreach ($leads as $lead) {
    $answersStmt = $pdo->prepare("
        SELECT ra.*, ff.label, ff.order_index
        FROM response_answers ra
        INNER JOIN form_fields ff ON ra.field_id = ff.id
        WHERE ra.response_id = :response_id
        ORDER BY ff.order_index ASC
    ");
    $answersStmt->execute([':response_id' => $lead['id']]);
    $answers = $answersStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($answers as $answer) {
        if (!in_array($answer['label'], $allFields)) {
            $allFields[] = $answer['label'];
        }
    }
}

// Preparar CSV
$filename = 'leads_' . date('Y-m-d_H-i-s') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// BOM para UTF-8 (para abrir corretamente no Excel)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Cabeçalho
$headers = array_merge(
    ['ID', 'Formulário', 'Data', 'Pontuação', 'IP'],
    $allFields
);
fputcsv($output, $headers, ';');

// Dados
foreach ($leads as $lead) {
    // Buscar respostas deste lead
    $answersStmt = $pdo->prepare("
        SELECT ra.*, ff.label, ff.order_index
        FROM response_answers ra
        INNER JOIN form_fields ff ON ra.field_id = ff.id
        WHERE ra.response_id = :response_id
        ORDER BY ff.order_index ASC
    ");
    $answersStmt->execute([':response_id' => $lead['id']]);
    $answers = $answersStmt->fetchAll(PDO::FETCH_ASSOC);

    // Organizar respostas por label
    $answersMap = [];
    foreach ($answers as $answer) {
        $value = $answer['answer'];

        // Decodificar se for JSON
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $value = implode(', ', $decoded);
        }

        $answersMap[$answer['label']] = $value;
    }

    // Montar linha
    $row = [
        $lead['id'],
        $lead['form_title'],
        date('d/m/Y H:i', strtotime($lead['created_at'])),
        $lead['score'] ?? '',
        $lead['user_ip'] ?? ''
    ];

    // Adicionar respostas na ordem dos campos
    foreach ($allFields as $fieldLabel) {
        $row[] = $answersMap[$fieldLabel] ?? '';
    }

    fputcsv($output, $row, ';');
}

fclose($output);
exit;
