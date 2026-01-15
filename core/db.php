<?php
/**
 * Configurações do Banco de Dados
 *
 * EDITE OS VALORES ABAIXO:
 */

$host = "localhost";              // Host do MySQL
$db   = "webfluxo_crm";           // Nome do banco de dados
$user = "webfluxo_crm";           // Usuário do MySQL
$pass = "7Ho7zNpD#H#H.0GAD7yi";   // Senha do MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}