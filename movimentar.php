<?php
header('Content-Type: application/json; charset=utf-8');
require 'conexao.php';

$item_id      = intval($_POST['item_id'] ?? 0);
$tipo         = $_POST['tipo'] ?? '';
$quantidade   = intval($_POST['quantidade'] ?? 0);
$validade     = ($_POST['validade'] ?? '') ?: null;
$responsavel  = trim($_POST['responsavel'] ?? '');
$recebido_por = trim($_POST['recebido_por'] ?? '');
$observacao   = trim($_POST['observacao'] ?? '');

if ($item_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'erro', 'mensagem' => 'item_id inválido.']);
    exit;
}
if (!in_array($tipo, ['entrada', 'saida'], true)) {
    http_response_code(400);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Tipo deve ser "entrada" ou "saida".']);
    exit;
}
if ($quantidade <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Quantidade deve ser maior que zero.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Atualizar estoque
    if ($tipo === 'entrada') {
        $pdo->prepare("UPDATE itens SET quantidade = quantidade + ? WHERE id = ?")->execute([$quantidade, $item_id]);
    } else {
        // Prevent negative stock
        $row = $pdo->prepare("SELECT quantidade FROM itens WHERE id = ?");
        $row->execute([$item_id]);
        $current = (int)($row->fetchColumn() ?? 0);
        if ($current < $quantidade) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['status' => 'erro', 'mensagem' => 'Estoque insuficiente para realizar a saída.']);
            exit;
        }
        $pdo->prepare("UPDATE itens SET quantidade = quantidade - ? WHERE id = ?")->execute([$quantidade, $item_id]);
    }

    // Registrar movimentação
    $stmt = $pdo->prepare("INSERT INTO movimentacoes (item_id, tipo, quantidade, validade, responsavel, recebido_por, observacao) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$item_id, $tipo, $quantidade, $validade, $responsavel, $recebido_por, $observacao]);

    $pdo->commit();
    echo json_encode(['status' => 'ok']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao registrar movimentação: ' . $e->getMessage()]);
}
?>
