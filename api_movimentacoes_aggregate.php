<?php
// api_movimentacoes_aggregate.php
// Retorna agregações para o painel de gráficos.
// Parâmetros GET:
// - action=months -> retorna entradas/saídas por mês no intervalo start/end (YYYY-MM-DD)
// - action=top_items -> retorna top N itens por movimentação (start/end/topN)
// Resposta JSON.

require 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'months';
$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;
$topN = intval($_GET['topN'] ?? 10);
if ($topN <= 0) $topN = 10;
$topN = min($topN, 200); // teto por segurança

try {
    if ($action === 'months') {
        // default: últimos 6 meses se não informado
        if (!$start || !$end) {
            $end_dt = new DateTime();
            $start_dt = (clone $end_dt)->modify('-5 months')->modify('first day of this month');
            $start = $start_dt->format('Y-m-01');
            $end = $end_dt->format('Y-m-t');
        }

        // Agrupa por mês (YYYY-MM)
        $sql = "
            SELECT DATE_FORMAT(m.data, '%Y-%m') AS period,
                   SUM(CASE WHEN m.tipo = 'entrada' THEN m.quantidade ELSE 0 END) AS entradas,
                   SUM(CASE WHEN m.tipo = 'saida' THEN m.quantidade ELSE 0 END) AS saidas
            FROM movimentacoes m
            WHERE m.data BETWEEN :start AND :end
            GROUP BY period
            ORDER BY period ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['start' => $start . ' 00:00:00', 'end' => $end . ' 23:59:59']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'ok', 'action' => 'months', 'start' => $start, 'end' => $end, 'data' => $rows]);
        exit;
    }

    if ($action === 'top_items') {
        if (!$start || !$end) {
            $end_dt = new DateTime();
            $start_dt = (clone $end_dt)->modify('-12 months')->modify('first day of this month');
            $start = $start_dt->format('Y-m-01');
            $end = $end_dt->format('Y-m-t');
        }

        $sql = "
            SELECT i.id AS item_id, i.nome, i.numero_item, SUM(m.quantidade) AS total_mov
            FROM movimentacoes m
            JOIN itens i ON m.item_id = i.id
            WHERE m.data BETWEEN :start AND :end
            GROUP BY i.id
            ORDER BY total_mov DESC
            LIMIT :topN
        ";
        $stmt = $pdo->prepare($sql);
        // PDO doesn't accept LIMIT with named param as string on some drivers; bindValue with INT
        $stmt->bindValue(':start', $start . ' 00:00:00');
        $stmt->bindValue(':end', $end . ' 23:59:59');
        $stmt->bindValue(':topN', $topN, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'ok', 'action' => 'top_items', 'start' => $start, 'end' => $end, 'topN' => $topN, 'data' => $rows]);
        exit;
    }

    if ($action === 'days') {
        if (!$start || !$end) {
            $end_dt = new DateTime();
            $start_dt = (clone $end_dt)->modify('-30 days');
            $start = $start_dt->format('Y-m-d');
            $end = $end_dt->format('Y-m-d');
        }
        $sql = "
            SELECT DATE_FORMAT(m.data, '%Y-%m-%d') AS period,
                   SUM(CASE WHEN m.tipo = 'entrada' THEN m.quantidade ELSE 0 END) AS entradas,
                   SUM(CASE WHEN m.tipo = 'saida'   THEN m.quantidade ELSE 0 END) AS saidas
            FROM movimentacoes m
            WHERE m.data BETWEEN :start AND :end
            GROUP BY period
            ORDER BY period ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['start' => $start . ' 00:00:00', 'end' => $end . ' 23:59:59']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'ok', 'action' => 'days', 'start' => $start, 'end' => $end, 'data' => $rows]);
        exit;
    }

    // ação desconhecida
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'action inválida']);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
    exit;
}
?>
