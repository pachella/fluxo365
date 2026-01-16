<?php
/**
 * Endpoint genérico para deletar registros
 * Uso: POST /core/crud/delete.php?module=nome_do_modulo&entity=nome_da_entidade&id=123
 */

ob_clean();
session_start();
require_once(__DIR__ . "/../db.php");
require_once(__DIR__ . "/../CrudHandler.php");

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
    // Obter módulo, entidade e ID
    $module = $_GET['module'] ?? '';
    $entity = $_GET['entity'] ?? '';
    $id = intval($_GET['id'] ?? 0);

    if (empty($module) || empty($entity) || $id <= 0) {
        echo "Parâmetros inválidos";
        exit;
    }

    // Carregar configuração do módulo
    $configFile = __DIR__ . "/../../modules/$module/crud-configs/$entity.php";

    if (!file_exists($configFile)) {
        echo "Configuração não encontrada";
        exit;
    }

    $config = require $configFile;

    // Criar handler e deletar
    $crud = new CrudHandler($pdo, $config, $_SESSION['user_id']);
    $crud->delete($id);

    echo "success";

} catch (PDOException $e) {
    http_response_code(500);
    echo "Erro no banco de dados: " . $e->getMessage();
} catch (Exception $e) {
    http_response_code(400);
    echo $e->getMessage();
}
