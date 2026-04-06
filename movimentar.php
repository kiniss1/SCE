<?php
require 'conexao.php';
$item_id = intval($_POST['item_id']);
$tipo = $_POST['tipo'];
$quantidade = intval($_POST['quantidade']);
$validade = $_POST['validade'] ?: null;
$responsavel = $_POST['responsavel'] ?? '';
$recebido_por = $_POST['recebido_por'] ?? '';
$observacao = $_POST['observacao'] ?? '';

// Atualizar estoque
if ($tipo === 'entrada') {
    $pdo->prepare("UPDATE itens SET quantidade = quantidade + ? WHERE id = ?")->execute([$quantidade, $item_id]);
} else {
    $pdo->prepare("UPDATE itens SET quantidade = quantidade - ? WHERE id = ?")->execute([$quantidade, $item_id]);
}

// Registrar movimentação
$stmt = $pdo->prepare("INSERT INTO movimentacoes (item_id, tipo, quantidade, validade, responsavel, recebido_por, observacao) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$item_id, $tipo, $quantidade, $validade, $responsavel, $recebido_por, $observacao]);
echo "ok";
?>