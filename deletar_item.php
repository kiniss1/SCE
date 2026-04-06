<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'conexao.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
header('Content-Type: application/json');

if ($id === 0) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'ID não enviado'
    ]);
    exit;
}

try {
    // 1. Exclui todas as movimentações relacionadas ao item
    $stmt1 = $pdo->prepare("DELETE FROM movimentacoes WHERE item_id = ?");
    $stmt1->execute([$id]);

    // 2. Exclui o item
    $stmt2 = $pdo->prepare("DELETE FROM itens WHERE id = ?");
    $stmt2->execute([$id]);

    echo json_encode([
        'status' => 'ok',
        'mensagem' => 'Item removido com sucesso'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro ao remover: ' . $e->getMessage()
    ]);
}
?>