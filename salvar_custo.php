<?php
require 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

$id    = intval($_POST['id'] ?? 0);
$custo = floatval(str_replace(',', '.', $_POST['custo'] ?? 0));

if (!$id || $custo < 0) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Dados inválidos']);
    exit;
}

$stmt = $pdo->prepare("UPDATE itens SET custo_unitario = ? WHERE id = ?");
$stmt->execute([$custo, $id]);

echo json_encode(['status' => 'ok']);
?>
