<?php
require 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

$item_id    = intval($_POST['item_id']    ?? 0);
$quantidade = intval($_POST['quantidade'] ?? 0);
$motivo     = trim($_POST['motivo']       ?? '');
$observacao = trim($_POST['observacao']   ?? '');
$responsavel = trim($_POST['responsavel'] ?? '');
$matricula  = trim($_POST['matricula']    ?? '');

if (!$item_id || $quantidade <= 0 || !$motivo) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Dados inválidos.']);
    exit;
}

// Busca item para pegar nome, numero e custo
$stmt = $pdo->prepare("SELECT nome, numero_item, custo_unitario FROM itens WHERE id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Item não encontrado.']);
    exit;
}

$custo_unitario = floatval($item['custo_unitario'] ?? 0);
$custo_total    = $custo_unitario * $quantidade;

$ins = $pdo->prepare("
    INSERT INTO descartes (item_id, nome, numero_item, quantidade, motivo, observacao, custo_unitario, custo_total, responsavel, matricula)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$ins->execute([
    $item_id,
    $item['nome'],
    $item['numero_item'],
    $quantidade,
    $motivo,
    $observacao,
    $custo_unitario,
    $custo_total,
    $responsavel,
    $matricula
]);

echo json_encode(['status' => 'ok', 'custo_total' => $custo_total]);
?>
