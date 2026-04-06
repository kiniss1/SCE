<?php
$host = "localhost";
$db = "estoque"; // troque para o nome correto do seu banco se for diferente
$user = "root"; // troque para o usuário correto do seu MariaDB
$pass = "Passos@2025"; // senha atualizada

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>