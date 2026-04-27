<?php
require 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'months';
$start  = $_GET['start'] ?? null;
$end    = $_GET['end']   ?? null;
$topN   = 9999; // sem limite — mostra todos

try {

    // ── top_items ────────────────────────────────────────────────────────────
    if ($action === 'top_items') {
        if (!$start || !$end) {
            $end_dt   = new DateTime();
            $start_dt = (clone $end_dt)->modify('-12 months')->modify('first day of this month');
            $start = $start_dt->format('Y-m-01');
            $end   = $end_dt->format('Y-m-t');
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
        $stmt->bindValue(':start', $start.' 00:00:00');
        $stmt->bindValue(':end',   $end.' 23:59:59');
        $stmt->bindValue(':topN',  $topN, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['status'=>'ok','action'=>'top_items','data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    // ── days ─────────────────────────────────────────────────────────────────
    if ($action === 'days') {
        if (!$start || !$end) {
            $end_dt   = new DateTime();
            $start_dt = (clone $end_dt)->modify('-30 days');
            $start = $start_dt->format('Y-m-d');
            $end   = $end_dt->format('Y-m-d');
        }
        $sql = "
            SELECT DATE_FORMAT(m.data, '%Y-%m-%d') AS period,
                   SUM(CASE WHEN m.tipo='entrada' THEN m.quantidade ELSE 0 END) AS entradas,
                   SUM(CASE WHEN m.tipo='saida'   THEN m.quantidade ELSE 0 END) AS saidas
            FROM movimentacoes m
            WHERE m.data BETWEEN :start AND :end
            GROUP BY period
            ORDER BY period ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['start'=>$start.' 00:00:00','end'=>$end.' 23:59:59']);
        echo json_encode(['status'=>'ok','action'=>'days','start'=>$start,'end'=>$end,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    // ── custo ────────────────────────────────────────────────────────────────
    if ($action === 'custo') {
        if (!$start || !$end) {
            $end_dt   = new DateTime();
            $start_dt = (clone $end_dt)->modify('-30 days');
            $start = $start_dt->format('Y-m-d');
            $end   = $end_dt->format('Y-m-d');
        }
        $sql = "
            SELECT i.id, i.nome, i.numero_item, i.custo_unitario,
                   SUM(m.quantidade) AS total_saidas,
                   SUM(m.quantidade) * i.custo_unitario AS custo_total
            FROM movimentacoes m
            JOIN itens i ON m.item_id = i.id
            WHERE m.tipo = 'saida'
              AND m.data BETWEEN :start AND :end
              AND i.custo_unitario > 0
            GROUP BY i.id
            ORDER BY custo_total DESC
            LIMIT :topN
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':start', $start.' 00:00:00');
        $stmt->bindValue(':end',   $end.' 23:59:59');
        $stmt->bindValue(':topN',  $topN, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlTotal = "
            SELECT COALESCE(SUM(m.quantidade * i.custo_unitario), 0) AS custo_geral
            FROM movimentacoes m
            JOIN itens i ON m.item_id = i.id
            WHERE m.tipo = 'saida'
              AND m.data BETWEEN :start AND :end
        ";
        $stmtT = $pdo->prepare($sqlTotal);
        $stmtT->execute(['start'=>$start.' 00:00:00','end'=>$end.' 23:59:59']);
        $custoGeral = (float)$stmtT->fetchColumn();

        echo json_encode(['status'=>'ok','action'=>'custo','custo_geral'=>$custoGeral,'data'=>$rows]);
        exit;
    }

    // ── months (mantido por compatibilidade) ──────────────────────────────────
    if ($action === 'months') {
        if (!$start || !$end) {
            $end_dt   = new DateTime();
            $start_dt = (clone $end_dt)->modify('-5 months')->modify('first day of this month');
            $start = $start_dt->format('Y-m-01');
            $end   = $end_dt->format('Y-m-t');
        }
        $sql = "
            SELECT DATE_FORMAT(m.data,'%Y-%m') AS period,
                   SUM(CASE WHEN m.tipo='entrada' THEN m.quantidade ELSE 0 END) AS entradas,
                   SUM(CASE WHEN m.tipo='saida'   THEN m.quantidade ELSE 0 END) AS saidas
            FROM movimentacoes m
            WHERE m.data BETWEEN :start AND :end
            GROUP BY period
            ORDER BY period ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['start'=>$start.' 00:00:00','end'=>$end.' 23:59:59']);
        echo json_encode(['status'=>'ok','action'=>'months','data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['status'=>'error','error'=>'action inválida']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>$e->getMessage()]);
}
?>
