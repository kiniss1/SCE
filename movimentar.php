<?php
require 'conexao.php';
$item_id               = intval($_POST['item_id']);
$tipo                  = $_POST['tipo'];
$quantidade            = intval($_POST['quantidade']);
$validade              = $_POST['validade'] ?: null;
$responsavel           = $_POST['responsavel'] ?? '';
$recebido_por          = $_POST['recebido_por'] ?? '';
$observacao            = $_POST['observacao'] ?? '';
$matricula_responsavel = $_POST['matricula_responsavel'] ?? '';
$matricula_recebido    = $_POST['matricula_recebido'] ?? '';

if ($tipo === 'entrada') {
    $pdo->prepare("UPDATE itens SET quantidade = quantidade + ? WHERE id = ?")->execute([$quantidade, $item_id]);
} else {
    $pdo->prepare("UPDATE itens SET quantidade = quantidade - ? WHERE id = ?")->execute([$quantidade, $item_id]);
}

$stmt = $pdo->prepare("
    INSERT INTO movimentacoes 
        (item_id, tipo, quantidade, validade, responsavel, recebido_por, observacao, matricula_responsavel, matricula_recebido)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $item_id, $tipo, $quantidade, $validade,
    $responsavel, $recebido_por, $observacao,
    $matricula_responsavel, $matricula_recebido
]);

header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);
?>
