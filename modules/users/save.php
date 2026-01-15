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

try {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'client';
    $status = $_POST['status'] ?? 'active';
    $password = trim($_POST['password'] ?? '');
    $passwordConfirm = trim($_POST['password_confirm'] ?? '');
    
    $isEdit = !empty($id);
    
    if (empty($name)) {
        echo "Nome é obrigatório";
        exit;
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "E-mail válido é obrigatório";
        exit;
    }
    
    if (!$isEdit && empty($password)) {
        echo "Senha é obrigatória para novos usuários";
        exit;
    }
    
    if (!empty($password)) {
        if (strlen($password) < 6) {
            echo "Senha deve ter pelo menos 6 caracteres";
            exit;
        }
        
        if ($password !== $passwordConfirm) {
            echo "Senhas não conferem";
            exit;
        }
    }
    
    if ($isEdit) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
    }
    
    if ($stmt->fetch()) {
        echo "Este e-mail já está em uso por outro usuário";
        exit;
    }
    
    $allowedRoles = ['admin', 'client', 'affiliate'];
    if (!in_array($role, $allowedRoles)) {
        echo "Perfil inválido";
        exit;
    }
    
    $allowedStatuses = ['active', 'inactive', 'suspended'];
    if (!in_array($status, $allowedStatuses)) {
        echo "Status inválido";
        exit;
    }
    
    if ($isEdit) {
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, email = ?, role = ?, status = ?, password = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$name, $email, $role, $status, $hashedPassword, $id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, email = ?, role = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$name, $email, $role, $status, $id]);
        }
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, role, status, password, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$name, $email, $role, $status, $hashedPassword]);
    }
    
    echo "success";
    
} catch (PDOException $e) {
    http_response_code(500);
    echo "Erro no banco de dados: " . $e->getMessage();
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro interno: " . $e->getMessage();
}