<?php
require 'conexao.php';
$stmt = $pdo->query("SELECT * FROM itens ORDER BY nome");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>