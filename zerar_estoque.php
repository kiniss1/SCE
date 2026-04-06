<?php
// zerar_estoque.php
// Cria backup das quantidades atuais, registra um "zeramento" (batch) e define todas as quantidades como 0.
// Requisitos: conexao.php (usa $pdo) e session_start() no topo.
// POST params: token, responsavel, comentario
// Retorno JSON: { status: 'ok', affected: N, batch_id: '...' } ou { status: 'error', code:'invalid_token', error: '...' }

header('Content-Type: application/json; charset=utf-8');
session_start();
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status'=>'error','error'=>'Método não suportado']);
    exit;
}

$token = $_POST['token'] ?? '';
$responsavel = trim($_POST['responsavel'] ?? '');
$comentario = trim($_POST['comentario'] ?? '');

if (!$token || !isset($_SESSION['zerar_token']) || !hash_equals($_SESSION['zerar_token'], $token)) {
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
    // Gera batch_id único
    $batch_id = bin2hex(random_bytes(8)) . '-' . time();

    $pdo->beginTransaction();

    // Criar tabelas se não existirem
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS zeramentos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            batch_id VARCHAR(128) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL,
            responsavel VARCHAR(255) NOT NULL,
            comentario TEXT NULL,
            restored TINYINT(1) DEFAULT 0,
            restored_at DATETIME NULL,
            restored_by VARCHAR(255) NULL,
            restored_comment TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS zeramento_itens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            zeramento_id INT NOT NULL,
            item_id INT NOT NULL,
            previous_qty INT NOT NULL,
            FOREIGN KEY (zeramento_id) REFERENCES zeramentos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Inserir registro do zeramento
    $stmt = $pdo->prepare("INSERT INTO zeramentos (batch_id, created_at, responsavel, comentario) VALUES (?, NOW(), ?, ?)");
    $stmt->execute([$batch_id, $responsavel, $comentario]);
    $zeramento_id = $pdo->lastInsertId();

    // Buscar todas as quantidades atuais
    $stmtItems = $pdo->query("SELECT id, quantidade FROM itens FOR UPDATE");
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    $affected = 0;
    $insItem = $pdo->prepare("INSERT INTO zeramento_itens (zeramento_id, item_id, previous_qty) VALUES (?, ?, ?)");
    $insMov = $pdo->prepare("INSERT INTO movimentacoes (item_id, tipo, quantidade, validade, responsavel, recebido_por, observacao, data) VALUES (?, 'saida', ?, NULL, ?, NULL, ?, NOW())");
    foreach ($items as $it) {
        $prev = (int)$it['quantidade'];
        // registrar apenas se houver quantidade > 0 para economizar registros
        $insItem->execute([$zeramento_id, $it['id'], $prev]);
        if ($prev > 0) {
            // inserir movimentação de saída para histórico/auditoria
            $insMov->execute([$it['id'], $prev, $responsavel, "Zeramento batch {$batch_id}"]);
            $affected += 1;
        }
    }

    // Atualizar todos os itens para quantidade 0
    $pdo->exec("UPDATE itens SET quantidade = 0");

    // Invalidar token (single-use) para forçar reload/renovação no frontend
    unset($_SESSION['zerar_token']);
    unset($_SESSION['desfazer_token']); // opcional: forçar renovação de ambos

    $pdo->commit();

    echo json_encode(['status'=>'ok','affected'=>$affected,'batch_id'=>$batch_id]);
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>$e->getMessage()]);
    exit;
}
?>