<?php
// desfazer_zeramento.php
// Restaura as quantidades salvas no último zeramento (ou batch_id informado).
// POST params: token, batch_id, responsavel, comentario
// Retorno JSON: { status:'ok', reversed: N } ou erro com code invalid_token

header('Content-Type: application/json; charset=utf-8');
session_start();
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status'=>'error','error'=>'Método não suportado']);
    exit;
}

$token = $_POST['token'] ?? '';
$batch_id = trim($_POST['batch_id'] ?? '');
$responsavel = trim($_POST['responsavel'] ?? '');
$comentario = trim($_POST['comentario'] ?? '');

if (!$token || !isset($_SESSION['desfazer_token']) || !hash_equals($_SESSION['desfazer_token'], $token)) {
    http_response_code(403);
    echo json_encode(['status'=>'error','code'=>'invalid_token','error'=>'Token inválido ou expirado']);
    exit;
}
if ($responsavel === '') {
    http_response_code(400);
    echo json_encode(['status'=>'error','error'=>'Responsável obrigatório']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Encontrar zeramento a restaurar
    if ($batch_id === '') {
        // pega último não restaurado
        $stmt = $pdo->prepare("SELECT * FROM zeramentos WHERE restored = 0 ORDER BY created_at DESC LIMIT 1 FOR UPDATE");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM zeramentos WHERE batch_id = ? LIMIT 1 FOR UPDATE");
        $stmt->execute([$batch_id]);
    }
    $zer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$zer) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['status'=>'error','error'=>'Nenhum lote encontrado para desfazer']);
        exit;
    }
    if ((int)$zer['restored'] === 1) {
        $pdo->rollBack();
        echo json_encode(['status'=>'error','error'=>'Lote já foi restaurado anteriormente']);
        exit;
    }
    $zeramento_id = (int)$zer['id'];
    $batch_id = $zer['batch_id'];

    // Buscar itens do zeramento
    $stmtItems = $pdo->prepare("SELECT * FROM zeramento_itens WHERE zeramento_id = ?");
    $stmtItems->execute([$zeramento_id]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    $reverted = 0;
    $upd = $pdo->prepare("UPDATE itens SET quantidade = ? WHERE id = ?");
    $insMov = $pdo->prepare("INSERT INTO movimentacoes (item_id, tipo, quantidade, validade, responsavel, recebido_por, observacao, data) VALUES (?, 'entrada', ?, NULL, ?, NULL, ?, NOW())");

    foreach ($items as $it) {
        $prev = (int)$it['previous_qty'];
        // atualizar quantidade para o valor anterior (sobrescreve, pois zeramos antes)
        $upd->execute([$prev, $it['item_id']]);
        if ($prev > 0) {
            $insMov->execute([$it['item_id'], $prev, $responsavel, "Restauração zeramento batch {$batch_id}"]);
            $reverted += 1;
        }
    }

    // marcar zeramento como restaurado
    $updZ = $pdo->prepare("UPDATE zeramentos SET restored = 1, restored_at = NOW(), restored_by = ?, restored_comment = ? WHERE id = ?");
    $updZ->execute([$responsavel, $comentario, $zeramento_id]);

    // invalidar token (single-use)
    unset($_SESSION['desfazer_token']);
    unset($_SESSION['zerar_token']);

    $pdo->commit();
    echo json_encode(['status'=>'ok','reverted'=>$reverted,'batch_id'=>$batch_id]);
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>$e->getMessage()]);
    exit;
}
?>