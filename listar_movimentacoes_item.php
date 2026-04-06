<?php
// listar_movimentacoes_item.php
// Retorna movimentações detalhadas de um item para drill-through com paginação
// GET params:
// - item_id (recomendado) ou nome (opcional)
// - start (YYYY-MM-DD) e end (YYYY-MM-DD) opcionais
// - page (padrão 1), per_page (padrão 50, máximo 500)
// resposta: { status: 'ok', total, page, per_page, data: [...] }

require 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
$nome = isset($_GET['nome']) ? trim($_GET['nome']) : null;
$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = max(1, min(500, intval($_GET['per_page'] ?? 50)));
$offset = ($page - 1) * $per_page;

try {
    $where = [];
    $params = [];

    if ($item_id > 0) {
        $where[] = 'm.item_id = :item_id';
        $params[':item_id'] = $item_id;
    } elseif ($nome) {
        $where[] = 'i.nome LIKE :nome';
        $params[':nome'] = '%' . $nome . '%';
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'error' => 'item_id ou nome obrigatorio']);
        exit;
    }

    if ($start) {
        $where[] = 'm.data >= :start';
        $params[':start'] = $start . ' 00:00:00';
    }
    if ($end) {
        $where[] = 'm.data <= :end';
        $params[':end'] = $end . ' 23:59:59';
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // contar total
    $countSql = "SELECT COUNT(*) FROM movimentacoes m JOIN itens i ON m.item_id = i.id $whereSql";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // buscar page
    $sql = "SELECT m.*, i.nome, i.numero_item FROM movimentacoes m JOIN itens i ON m.item_id = i.id $whereSql ORDER BY m.data DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'ok', 'total' => $total, 'page' => $page, 'per_page' => $per_page, 'data' => $rows]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
    exit;
}
?>