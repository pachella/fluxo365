<?php
/**
 * Endpoint genérico para buscar um registro
 * Uso: GET /core/crud/get.php?module=nome_do_modulo&entity=nome_da_entidade&id=123
 */

ob_clean();
session_start();
require_once(__DIR__ . "/../db.php");
require_once(__DIR__ . "/../CrudHandler.php");

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

try {
    // Obter módulo, entidade e ID
    $module = $_GET['module'] ?? '';
    $entity = $_GET['entity'] ?? '';
    $id = intval($_GET['id'] ?? 0);

    if (empty($module) || empty($entity) || $id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Parâmetros inválidos']);
        exit;
    }

    // Carregar configuração do módulo
    $configFile = __DIR__ . "/../../modules/$module/crud-configs/$entity.php";

    if (!file_exists($configFile)) {
        http_response_code(404);
        echo json_encode(['error' => 'Configuração não encontrada']);
        exit;
    }

    $config = require $configFile;

    // Criar handler e buscar
    $crud = new CrudHandler($pdo, $config, $_SESSION['user_id']);
    $data = $crud->get($id);

    echo json_encode($data);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro no banco de dados: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['error' => $e->getMessage()]);
}
