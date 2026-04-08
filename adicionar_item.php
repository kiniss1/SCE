<?php
header('Content-Type: application/json; charset=utf-8');
require 'conexao.php';

$nome       = trim($_POST['nome'] ?? '');
$numero     = trim($_POST['numero'] ?? '');
$quantidade = intval($_POST['quantidade'] ?? 0);

if ($nome === '') {
    http_response_code(400);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Nome do item é obrigatório.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO itens (nome, numero_item, quantidade) VALUES (?, ?, ?)");
    $stmt->execute([$nome, $numero, $quantidade]);
    echo json_encode(['status' => 'ok', 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao adicionar item: ' . $e->getMessage()]);
}
?>
