<?php
require 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

// Suporta tanto JSON body quanto form POST
$input   = json_decode(file_get_contents('php://input'), true) ?? [];
$action  = $input['action']  ?? $_GET['action']  ?? $_POST['action']  ?? '';
$centro  = intval($input['centro']  ?? $_GET['centro']  ?? $_POST['centro']  ?? 0);

// ── Carregar ──────────────────────────────────────────────
if ($action === 'carregar' && $centro) {
    $stmt = $pdo->prepare("SELECT * FROM inventario WHERE centro = ? ORDER BY material ASC");
    $stmt->execute([$centro]);
    echo json_encode(['status' => 'ok', 'itens' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// ── Importar lista ────────────────────────────────────────
if ($action === 'importar' && $centro) {
    $itens = $input['itens'] ?? [];
    if (empty($itens)) { echo json_encode(['status' => 'error', 'mensagem' => 'Lista vazia']); exit; }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM inventario WHERE centro = ?")->execute([$centro]);
        $stmt = $pdo->prepare("INSERT INTO inventario (centro, material, descricao, deposito, qtd_sap) VALUES (?,?,?,?,?)");
        foreach ($itens as $item) {
            $stmt->execute([$centro, $item['material'], $item['descricao'], $item['deposito'], $item['qtd_sap']]);
        }
        $pdo->commit();
        echo json_encode(['status' => 'ok', 'total' => count($itens)]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'mensagem' => $e->getMessage()]);
    }
    exit;
}

// ── Atualizar quantidade física ───────────────────────────
if ($action === 'atualizar' && $centro) {
    $material   = trim($input['material']   ?? $_POST['material']   ?? '');
    $qtd_raw    = $input['qtd_fisica']      ?? $_POST['qtd_fisica'] ?? '';
    $qtd_fisica = ($qtd_raw === '' || $qtd_raw === null) ? null : floatval($qtd_raw);

    $stmt = $pdo->prepare("UPDATE inventario SET qtd_fisica = ? WHERE centro = ? AND material = ?");
    $stmt->execute([$qtd_fisica, $centro, $material]);
    echo json_encode(['status' => 'ok']);
    exit;
}

// ── Limpar ────────────────────────────────────────────────
if ($action === 'limpar' && $centro) {
    $pdo->prepare("DELETE FROM inventario WHERE centro = ?")->execute([$centro]);
    echo json_encode(['status' => 'ok']);
    exit;
}

echo json_encode(['status' => 'error', 'mensagem' => 'Acao invalida: "'.$action.'" centro:'.$centro]);
?>
