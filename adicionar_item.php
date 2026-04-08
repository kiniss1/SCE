<?php
require 'conexao.php';
$nome = $_POST['nome'] ?? '';
$numero = $_POST['numero'] ?? '';
$quantidade = intval($_POST['quantidade']);

$stmt = $pdo->prepare("INSERT INTO itens (nome, numero_item, quantidade) VALUES (?, ?, ?)");
$stmt->execute([$nome, $numero, $quantidade]);
$id = $pdo->lastInsertId();

header('Content-Type: application/json');
echo json_encode(['status' => 'ok', 'id' => $id]);
?>
