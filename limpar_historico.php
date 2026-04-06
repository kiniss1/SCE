<?php
// limpar_historico.php
// - Faz backup de todas as linhas de movimentacoes para tabela backup_movimentacoes (cria se não existir)
// - Deleta as linhas da tabela movimentacoes
// - Retorna JSON { status:'ok', affected: N, batch_id: '...' } ou error
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status'=>'error','error'=>'Método não permitido']);
    exit;
}

$token = $_POST['token'] ?? '';
$responsavel = trim($_POST['responsavel'] ?? '');
$comentario = trim($_POST['comentario'] ?? '');

// valida token de sessão
if (empty($token) || !isset($_SESSION['limpar_historico_token']) || !hash_equals($_SESSION['limpar_historico_token'], $token)) {
    http_response_code(403);
    echo json_encode(['status'=>'error','error'=>'Token inválido ou expirado','code'=>'invalid_token']);
    exit;
}
if ($responsavel === '') {
    http_response_code(400);
    echo json_encode(['status'=>'error','error'=>'Responsável é obrigatório']);
    exit;
}

try {
    // gerar batch id
    $batch_id = bin2hex(random_bytes(8));
    // Criar tabela de backup se não existir (estrutura compatível com movimentacoes)
    $createSql = <<<SQL
CREATE TABLE IF NOT EXISTS backup_movimentacoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    original_id BIGINT NULL,
    item_id INT NOT NULL,
    tipo VARCHAR(32) NOT NULL,
    quantidade INT NOT NULL,
    validade DATETIME NULL,
    responsavel VARCHAR(255) NULL,
    recebido_por VARCHAR(255) NULL,
    observacao TEXT NULL,
    data DATETIME NULL,
    batch_id VARCHAR(64) NOT NULL,
    restored TINYINT(1) NOT NULL DEFAULT 0,
    backup_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
    $pdo->exec($createSql);

    $pdo->beginTransaction();

    // Selecionar todas as movimentacoes
    $stmt = $pdo->prepare("SELECT * FROM movimentacoes FOR UPDATE");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $affected = 0;
    if ($rows && count($rows) > 0) {
        // Inserir no backup
        $insertBackup = $pdo->prepare("INSERT INTO backup_movimentacoes (original_id, item_id, tipo, quantidade, validade, responsavel, recebido_por, observacao, data, batch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $deleteStmt = $pdo->prepare("DELETE FROM movimentacoes WHERE id = ?");
        foreach ($rows as $r) {
            $insertBackup->execute([
                $r['id'] ?? null,
                $r['item_id'] ?? 0,
                $r['tipo'] ?? '',
                $r['quantidade'] ?? 0,
                $r['validade'] ?? null,
                $r['responsavel'] ?? null,
                $r['recebido_por'] ?? null,
                // adiciona batch info na observação também para maior rastreabilidade
                (isset($r['observacao']) ? ($r['observacao'] . " ") : "") . "BATCH:HIST:{$batch_id}; Limpeza por {$responsavel}" . ($comentario ? " - {$comentario}" : ''),
                $r['data'] ?? null,
                $batch_id
            ]);
            // apagar original
            $deleteStmt->execute([$r['id']]);
            $affected++;
        }
    }

    $pdo->commit();

    // invalidar token para evitar reuso
    unset($_SESSION['limpar_historico_token']);

    echo json_encode(['status'=>'ok','affected'=>$affected,'batch_id'=>$batch_id]);
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status'=>'error','error'=>$e->getMessage()]);
    exit;
}
?>