<?php
ob_clean();

session_start();
require_once(__DIR__ . "/../../core/db.php");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo "Não autorizado";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Método não permitido";
    exit;
}

$id = $_GET['id'] ?? '';

if (empty($id) || !is_numeric($id)) {
    http_response_code(400);
    echo "ID inválido";
    exit;
}

try {
    // Verificar se o usuário existe
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo "Usuário não encontrado";
        exit;
    }
    
    // Verificar se não está tentando excluir a si mesmo
    if ($id == $_SESSION["user_id"]) {
        echo "Não é possível excluir seu próprio usuário";
        exit;
    }
    
    // Verificar se há apenas 1 admin e está tentando excluir o último
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminCount = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $userRole = $stmt->fetch()['role'];
    
    if ($adminCount <= 1 && $userRole === 'admin') {
        echo "Não é possível excluir o último administrador do sistema";
        exit;
    }
    
    // Excluir o usuário
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo "success";
    } else {
        echo "Erro ao excluir usuário";
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo "Erro no banco de dados: " . $e->getMessage();
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro interno: " . $e->getMessage();
}
?>