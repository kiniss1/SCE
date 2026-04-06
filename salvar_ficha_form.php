<?php
require 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

// Espera JSON do formulário
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['status'=>'error','error'=>'JSON inválido']);
    exit;
}

// Campos do colaborador / ficha
$nome = substr(trim($data['nome_colaborador'] ?? ''),0,255);
$matricula = substr(trim($data['matricula'] ?? ''),0,64);
$funcao = substr(trim($data['funcao'] ?? ''),0,128);
$area = substr(trim($data['area'] ?? ''),0,128);
$status = in_array($data['status'] ?? 'pending', ['pending','confirmed','cancelled']) ? $data['status'] : 'pending';
$local_data = $data['local_data'] ?? null;
$linhas = $data['linhas'] ?? [];

try {
    $pdo->beginTransaction();

    // Certificar que colaborador exista (ou criar automaticamente)
    $colaborador_id = null;
    if (!empty($matricula)) {
        $stmt = $pdo->prepare("SELECT id FROM colaboradores WHERE matricula = ? LIMIT 1");
        $stmt->execute([$matricula]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $colaborador_id = $row['id'];
    }
    if (!$colaborador_id && $nome !== '') {
        // tentar por nome
        $stmt = $pdo->prepare("SELECT id FROM colaboradores WHERE nome = ? LIMIT 1");
        $stmt->execute([$nome]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $colaborador_id = $row['id'];
    }
    if (!$colaborador_id) {
        // cria novo colaborador
        $stmt = $pdo->prepare("INSERT INTO colaboradores (nome, matricula, funcao, area) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $matricula ?: null, $funcao ?: null, $area ?: null]);
        $colaborador_id = $pdo->lastInsertId();
    } else {
        // opcional: atualizar dados básicos
        $stmt = $pdo->prepare("UPDATE colaboradores SET nome = ?, funcao = ?, area = ? WHERE id = ?");
        $stmt->execute([$nome, $funcao, $area, $colaborador_id]);
    }

    // Inserir ficha
    $ins = $pdo->prepare("INSERT INTO fichas_colaborador (colaborador_id, data_ficha, criado_por, status, observacao, arquivo_scan) VALUES (?, ?, ?, ?, ?, ?)");
    $data_ficha = $local_data ? $local_data : date('Y-m-d H:i:s');
    $ins->execute([$colaborador_id, $data_ficha, $nome, $status, null, null]);
    $ficha_id = $pdo->lastInsertId();

    // Preparar statements auxiliares
    $findItem = $pdo->prepare("SELECT * FROM itens WHERE numero_item = ? LIMIT 1");
    $findItemByName = $pdo->prepare("SELECT * FROM itens WHERE nome = ? LIMIT 1");
    $insertItem = $pdo->prepare("INSERT INTO itens (nome, numero_item, quantidade) VALUES (?, ?, 0)");
    $insertLinha = $pdo->prepare("INSERT INTO ficha_linhas (ficha_id, item_id, descricao, numero_ca, quantidade, validade, tipo, motivo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $insertMov = $pdo->prepare("INSERT INTO movimentacoes (item_id, tipo, quantidade, validade, responsavel, recebido_por, observacao, data) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $updateQty = $pdo->prepare("UPDATE itens SET quantidade = quantidade + ? WHERE id = ?");
    $selectForUpdate = $pdo->prepare("SELECT id, quantidade FROM itens WHERE id = ? FOR UPDATE");

    foreach ($linhas as $l) {
        $descricao = substr(trim($l['descricao'] ?? ''),0,255);
        $numero_ca = substr(trim($l['numero_ca'] ?? ''),0,64);
        $dataLinha = $l['data'] ?? null;
        $qtd_entrega = max(0, intval($l['qtd_entrega'] ?? 0));
        $qtd_devolucao = max(0, intval($l['qtd_devolucao'] ?? 0));
        $motivo = substr(trim($l['motivo'] ?? ''),0,255);
        $rubrica = substr(trim($l['rubrica'] ?? ''),0,64);

        // Determinar item (prioriza numero_ca)
        $itemId = null;
        if ($numero_ca !== '') {
            $findItem->execute([$numero_ca]);
            if ($it = $findItem->fetch(PDO::FETCH_ASSOC)) $itemId = $it['id'];
        }
        if (!$itemId && $descricao !== '') {
            $findItemByName->execute([$descricao]);
            if ($it2 = $findItemByName->fetch(PDO::FETCH_ASSOC)) $itemId = $it2['id'];
        }
        if (!$itemId) {
            // criar item com quantidade 0 e numero único se necessário
            $newNumero = $numero_ca ?: uniqid('N-');
            $insertItem->execute([$descricao ?: 'SEM_NOME', $newNumero]);
            $itemId = $pdo->lastInsertId();
        }

        // inserir linha da ficha
        $tipo = 'saida'; // por padrão consideramos entrega = saída do estoque
        $quantidadeLinha = $qtd_entrega > 0 ? $qtd_entrega : ($qtd_devolucao > 0 ? $qtd_devolucao : 0);
        $validade = null;
        $insertLinha->execute([$ficha_id, $itemId, $descricao, $numero_ca, $quantidadeLinha, $validade, $tipo, $motivo]);

        // se status = confirmed -> gerar movimentações e atualizar estoque
        if ($status === 'confirmed' && $quantidadeLinha > 0) {
            // lock linha do item
            $selectForUpdate->execute([$itemId]);
            $rowItem = $selectForUpdate->fetch(PDO::FETCH_ASSOC);
            if (!$rowItem) throw new Exception("Item $itemId não encontrado");

            // se for entrega, subtrair; se devolução, somar
            $delta = -1 * $quantidadeLinha;
            if ($qtd_devolucao > 0) $delta = +1 * $qtd_devolucao;

            $updateQty->execute([$delta, $itemId]);

            $responsavel = $nome;
            $recebido_por = null;
            $observacao = "Ficha ID $ficha_id - " . ($motivo ?: '');
            $insertMov->execute([$itemId, $tipo, $quantidadeLinha, $validade, $responsavel, $recebido_por, $observacao]);
        }
    }

    $pdo->commit();
    echo json_encode(['status'=>'ok','ficha_id'=>$ficha_id]);
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>$e->getMessage()]);
    exit;
}
?>