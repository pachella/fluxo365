<?php
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . "/../../core/db.php");
require_once(__DIR__ . "/../../core/PermissionManager.php");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$leadId = $_GET['id'] ?? null;

if (!$leadId) {
    echo json_encode(['error' => 'ID não fornecido']);
    exit;
}

$permissionManager = new PermissionManager($_SESSION['user_role'], $_SESSION['user_id'] ?? null);

try {
    $sqlFilter = $permissionManager->getSQLFilter('forms');

    // Buscar dados do lead
    $sql = "SELECT fr.*, f.title as form_title, f.id as form_id
            FROM form_responses fr
            INNER JOIN forms f ON fr.form_id = f.id";

    // Adicionar filtro de ID e permissões
    if (empty($sqlFilter)) {
        // Admin - apenas filtrar por ID
        $sql .= " WHERE fr.id = :id";
    } else {
        // Cliente - adicionar ID ao filtro existente
        $sql .= str_replace('WHERE', 'WHERE fr.id = :id AND', $sqlFilter);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $leadId, PDO::PARAM_INT);
    $stmt->execute();
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lead) {
        echo json_encode(['error' => 'Lead não encontrado']);
        exit;
    }

    // Buscar todas as respostas
    $answersSql = "SELECT ra.answer, ff.label, ff.type
                   FROM response_answers ra
                   INNER JOIN form_fields ff ON ra.field_id = ff.id
                   WHERE ra.response_id = :response_id
                   ORDER BY ff.order_index ASC";

    $answersStmt = $pdo->prepare($answersSql);
    $answersStmt->bindValue(':response_id', $leadId, PDO::PARAM_INT);
    $answersStmt->execute();
    $answers = $answersStmt->fetchAll(PDO::FETCH_ASSOC);

    // Extrair nome, email e whatsapp
    $name = '';
    $email = '';
    $whatsapp = '';

    foreach ($answers as $answer) {
        if ($answer['type'] === 'name' && empty($name)) {
            $name = $answer['answer'];
        }
        if ($answer['type'] === 'email' && empty($email)) {
            $email = $answer['answer'];
        }
        if ($answer['type'] === 'phone' && empty($whatsapp)) {
            // Extrair apenas números
            $whatsapp = preg_replace('/[^0-9]/', '', $answer['answer']);
        }
    }

    echo json_encode([
        'success' => true,
        'lead' => [
            'id' => $lead['id'],
            'form_id' => $lead['form_id'],
            'form_title' => $lead['form_title'],
            'name' => $name ?: 'Sem nome',
            'email' => $email,
            'whatsapp' => $whatsapp,
            'score' => $lead['score'] ?? 0,
            'created_at' => date('d/m/Y H:i', strtotime($lead['created_at'])),
            'ip_address' => $lead['ip_address'] ?? '',
            'user_agent' => $lead['user_agent'] ?? '',
            'notes' => $lead['notes'] ?? '',
            'notes_updated_at' => $lead['notes_updated_at'] ? date('d/m/Y H:i', strtotime($lead['notes_updated_at'])) : null
        ]
    ]);

} catch (PDOException $e) {
    error_log("Erro get_lead.php: " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao buscar lead: ' . $e->getMessage()]);
}
