<?php
require 'conexao.php';
$start = $_GET['start'] ?? null;
$end   = $_GET['end']   ?? null;

if ($start && $end) {
    $sql = "SELECT m.*, i.nome, i.numero_item
            FROM movimentacoes m
            JOIN itens i ON m.item_id = i.id
            WHERE m.data BETWEEN :start AND :end
            ORDER BY m.data DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':start' => $start . ' 00:00:00',
        ':end'   => $end   . ' 23:59:59'
    ]);
} else {
    $sql = "SELECT m.*, i.nome, i.numero_item
            FROM movimentacoes m
            JOIN itens i ON m.item_id = i.id
            ORDER BY m.data DESC";
    $stmt = $pdo->query($sql);
}

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
