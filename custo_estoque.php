<?php
require 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

$stmt = $pdo->query("
    SELECT 
        SUM(quantidade * custo_unitario) AS custo_estoque,
        COUNT(*) AS total_itens,
        SUM(quantidade) AS total_unidades
    FROM itens
    WHERE custo_unitario > 0
");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'status'          => 'ok',
    'custo_estoque'   => floatval($row['custo_estoque'] ?? 0),
    'total_itens'     => intval($row['total_itens'] ?? 0),
    'total_unidades'  => intval($row['total_unidades'] ?? 0)
]);
?>
