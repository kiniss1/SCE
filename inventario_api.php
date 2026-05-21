<?php
require 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$centro = intval($_GET['centro'] ?? $_POST['centro'] ?? 0);

// ── Carregar itens do centro ──────────────────────────────
if ($action === 'carregar' && $centro) {
    $stmt = $pdo->prepare("SELECT * FROM inventario WHERE centro = ? ORDER BY material ASC");
    $stmt->execute([$centro]);
    echo json_encode(['status' => 'ok', 'itens' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// ── Salvar lista importada (substitui tudo do centro) ─────
if ($action === 'importar' && $centro) {
    $itens = json_decode(file_get_contents('php://input'), true)['itens'] ?? [];
    if (empty($itens)) { echo json_encode(['status' => 'error', 'mensagem' => 'Lista vazia']); exit; }

    $pdo->beginTransaction();
    try {
        // Apaga lista anterior do centro
        $pdo->prepare("DELETE FROM inventario WHERE centro = ?")->execute([$centro]);
        // Insere nova lista
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

// ── Atualizar quantidade física de um item ────────────────
if ($action === 'atualizar' && $centro) {
    $material   = trim($_POST['material'] ?? '');
    $qtd_fisica = $_POST['qtd_fisica'] === '' ? null : floatval($_POST['qtd_fisica']);

    $stmt = $pdo->prepare("UPDATE inventario SET qtd_fisica = ? WHERE centro = ? AND material = ?");
    $stmt->execute([$qtd_fisica, $centro, $material]);
    echo json_encode(['status' => 'ok']);
    exit;
}

// ── Limpar inventário do centro ───────────────────────────
if ($action === 'limpar' && $centro) {
    $pdo->prepare("DELETE FROM inventario WHERE centro = ?")->execute([$centro]);
    echo json_encode(['status' => 'ok']);
    exit;
}

echo json_encode(['status' => 'error', 'mensagem' => 'Ação inválida']);
?>
