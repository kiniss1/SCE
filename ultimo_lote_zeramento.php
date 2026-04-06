<?php
// ultimo_lote_zeramento.php
// Retorna o último lote de zeramento que ainda não foi restaurado (se existir).
// GET -> { status:'ok', found: true/false, batch_id: '...', all_restored: bool }

header('Content-Type: application/json; charset=utf-8');
require 'conexao.php';

try {
    // Se a tabela não existir, diz que não encontrou
    $res = $pdo->query("SHOW TABLES LIKE 'zeramentos'")->fetchAll();
    if (count($res) === 0) {
        echo json_encode(['status'=>'ok','found'=>false]);
        exit;
    }

    $stmt = $pdo->query("SELECT batch_id, restored FROM zeramentos ORDER BY created_at DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['status'=>'ok','found'=>false]);
        exit;
    }
    $found = true;
    $batch = $row['batch_id'];
    $all_restored = ((int)$row['restored'] === 1);
    echo json_encode(['status'=>'ok','found'=> $found, 'batch_id'=>$batch, 'all_restored'=>$all_restored]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>$e->getMessage()]);
    exit;
}
?>