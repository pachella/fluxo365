<?php
/**
 * Configurações do Banco de Dados
 *
 * EDITE OS VALORES ABAIXO:
 */

$host = "localhost";              // Host do MySQL
$db   = "SEU_BANCO";              // Nome do banco de dados
$user = "SEU_USUARIO";            // Usuário do MySQL
$pass = "SUA_SENHA";              // Senha do MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}