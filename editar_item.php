<?php
require 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

$id     = intval($_POST['id']     ?? 0);
$nome   = trim($_POST['nome']     ?? '');
$numero = trim($_POST['numero']   ?? '');

if (!$id || !$nome || !$numero) {
    echo json_encode(['status' => 'error', 'mensagem' => 'Dados inválidos.']);
    exit;
}

$stmt = $pdo->prepare("UPDATE itens SET nome = ?, numero_item = ? WHERE id = ?");
$stmt->execute([$nome, $numero, $id]);

echo json_encode(['status' => 'ok']);
?>
