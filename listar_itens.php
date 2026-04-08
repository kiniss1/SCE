<?php
header('Content-Type: application/json; charset=utf-8');
require 'conexao.php';

try {
    $stmt = $pdo->query("SELECT * FROM itens ORDER BY nome");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao listar itens: ' . $e->getMessage()]);
}
?>
