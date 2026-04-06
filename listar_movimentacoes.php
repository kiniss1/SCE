<?php
require 'conexao.php';
$sql = "SELECT m.*, i.nome, i.numero_item FROM movimentacoes m JOIN itens i ON m.item_id = i.id ORDER BY m.data DESC";
$stmt = $pdo->query($sql);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>