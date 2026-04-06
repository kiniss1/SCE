<?php
// ultimo_lote_limpeza_historico.php
// - Retorna info do último lote de limpeza realizado (batch_id, data, affected count, se existe e não foi restaurado)
header('Content-Type: application/json; charset=utf-8');
require 'conexao.php';

try {
    // Procura pelo último batch com restored = 0 (ou o mais recente backup se preferir)
    $stmt = $pdo->query("SELECT batch_id, MAX(backup_created) AS created_at, SUM(1) AS total FROM backup_movimentacoes GROUP BY batch_id ORDER BY created_at DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['status'=>'ok','found'=>false]);
        exit;
    }
    // Verificar se já foi totalmente restaurado (se houve restored=1 para todos)
    $batch = $row['batch_id'];
    $check = $pdo->prepare("SELECT COUNT(*) AS total_rows, SUM(restored) AS restored_rows FROM backup_movimentacoes WHERE batch_id = ?");
    $check->execute([$batch]);
    $c = $check->fetch(PDO::FETCH_ASSOC);
    $allRestored = ($c && intval($c['total_rows'] ?? 0) > 0 && intval($c['restored_rows'] ?? 0) == intval($c['total_rows'] ?? 0));
    echo json_encode([
        'status'=>'ok',
        'found'=>true,
        'batch_id'=>$batch,
        'created_at'=>$row['created_at'],
        'total'=>$row['total'],
        'all_restored'=>$allRestored
    ]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>$e->getMessage()]);
    exit;
}
?>