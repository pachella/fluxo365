<?php
/**
 * Endpoint genérico para salvar (criar ou atualizar) registros
 * Uso: POST /core/crud/save.php?module=nome_do_modulo&entity=nome_da_entidade
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
    // Obter módulo e entidade
    $module = $_GET['module'] ?? '';
    $entity = $_GET['entity'] ?? '';

    if (empty($module) || empty($entity)) {
        echo "Módulo ou entidade não especificados";
        exit;
    }

    // Carregar configuração do módulo
    $configFile = __DIR__ . "/../../modules/$module/crud-configs/$entity.php";

    if (!file_exists($configFile)) {
        echo "Configuração não encontrada: $configFile";
        exit;
    }

    $config = require $configFile;

    // Criar handler e salvar
    $crud = new CrudHandler($pdo, $config, $_SESSION['user_id']);
    $id = $crud->save($_POST);

    echo "success";

} catch (PDOException $e) {
    http_response_code(500);
    echo "Erro no banco de dados: " . $e->getMessage();
} catch (Exception $e) {
    http_response_code(400);
    echo $e->getMessage();
}
