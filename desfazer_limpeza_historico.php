<?php
// desfazer_limpeza_historico.php
// - Restaura registros do backup_movimentacoes para movimentacoes com base no batch_id
// - Marca as linhas restauradas (restored = 1) para evitar desfazer duplo
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status'=>'error','error'=>'Método não permitido']);
    exit;
}

$token = $_POST['token'] ?? '';
$batch_id = $_POST['batch_id'] ?? '';
$responsavel = trim($_POST['responsavel'] ?? '');
$comentario = trim($_POST['comentario'] ?? '');

// valida token de sessão
if (empty($token) || !isset($_SESSION['desfazer_historico_token']) || !hash_equals($_SESSION['desfazer_historico_token'], $token)) {
    http_response_code(403);
    echo json_encode(['status'=>'error','error'=>'Token inválido ou expirado','code'=>'invalid_token']);
    exit;
}
if ($responsavel === '') {
    http_response_code(400);
    echo json_encode(['status'=>'error','error'=>'Responsável é obrigatório']);
    exit;
}
if (empty($batch_id)) {
    http_response_code(400);
    echo json_encode(['status'=>'error','error'=>'batch_id obrigatório']);
    exit;
}

try {
    // Verifica se já existe restored = 1 para esse batch (impede desfazer duplo)
    $check = $pdo->prepare("SELECT COUNT(*) FROM backup_movimentacoes WHERE batch_id = ? AND restored = 1");
    $check->execute([$batch_id]);
    if ($check->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['status'=>'error','error'=>'Este lote já foi revertido anteriormente.']);
        exit;
    }

    // Buscar backup não restaurado para o batch
    $stmt = $pdo->prepare("SELECT * FROM backup_movimentacoes WHERE batch_id = ? AND restored = 0 ORDER BY id ASC FOR UPDATE");
    $stmt->execute([$batch_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows || count($rows) === 0) {
        http_response_code(404);
        echo json_encode(['status'=>'error','error'=>'Batch não encontrado ou sem registros para restaurar.']);
        exit;
    }

    $pdo->beginTransaction();

    $insert = $pdo->prepare("INSERT INTO movimentacoes (item_id, tipo, quantidade, validade, responsavel, recebido_por, observacao, data) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $mark = $pdo->prepare("UPDATE backup_movimentacoes SET restored = 1 WHERE id = ?");

    $restored = 0;
    foreach ($rows as $r) {
        $insert->execute([
            $r['item_id'],
            $r['tipo'],
            $r['quantidade'],
            $r['validade'] ?: null,
            // registra quem realizou a reversão como responsavel na restauração
            $responsavel,
            $r['recebido_por'] ?? null,
            // adiciona informação de reversão na observação
        (isset($r['observacao']) ? ($r['observacao'] . " ") : "") . "BATCH:HIST:{$batch_id}; RESTAURACAO por {$responsavel}" . ($comentario ? " - {$comentario}" : ''),
            $r['data'] ?: null
        ]);
        $mark->execute([$r['id']]);
        $restored++;
    }

    $pdo->commit();

    unset($_SESSION['desfazer_historico_token']);

    echo json_encode(['status'=>'ok','restored'=>$restored,'batch_id'=>$batch_id]);
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>$e->getMessage()]);
    exit;
}
?>