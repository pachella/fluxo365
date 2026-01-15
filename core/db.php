<?php
$host = "localhost";
$db   = "webformtalk_forms";
$user = "webformtalk_forms";
$pass = "fDdyA*E]Qq1hFPzM*)_Y";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}