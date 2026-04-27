<?php
require 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

$start = $_GET['start'] ?? null;
$end   = $_GET['end']   ?? null;

$sql = "SELECT * FROM descartes";
$params = [];

if ($start && $end) {
    $sql .= " WHERE data BETWEEN :start AND :end";
    $params[':start'] = $start . ' 00:00:00';
    $params[':end']   = $end   . ' 23:59:59';
}

$sql .= " ORDER BY data DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
