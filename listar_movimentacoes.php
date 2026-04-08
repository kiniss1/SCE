<?php
header('Content-Type: application/json; charset=utf-8');
require 'conexao.php';

try {
    $sql  = "SELECT m.*, i.nome, i.numero_item FROM movimentacoes m JOIN itens i ON m.item_id = i.id ORDER BY m.data DESC";
    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao listar movimentações: ' . $e->getMessage()]);
}
?>
