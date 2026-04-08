<?php
/**
 * init_db.php
 *
 * Creates all application tables if they do not already exist.
 * Called automatically by conexao.php on first connection.
 * Can also be run directly in a browser for a one-time setup check.
 *
 * Safe to execute multiple times — all statements use IF NOT EXISTS.
 */

function initializeDatabase(PDO $pdo): void
{
    // Core inventory table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS itens (
            id          INT          NOT NULL AUTO_INCREMENT,
            nome        VARCHAR(255) NOT NULL,
            numero_item VARCHAR(64)  NOT NULL DEFAULT '',
            quantidade  INT          NOT NULL DEFAULT 0,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Stock movement history
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS movimentacoes (
            id           INT          NOT NULL AUTO_INCREMENT,
            item_id      INT          NOT NULL,
            tipo         VARCHAR(32)  NOT NULL,
            quantidade   INT          NOT NULL DEFAULT 0,
            validade     DATE         NULL,
            responsavel  VARCHAR(255) NULL,
            recebido_por VARCHAR(255) NULL,
            observacao   TEXT         NULL,
            data         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            CONSTRAINT fk_movimentacoes_item
                FOREIGN KEY (item_id) REFERENCES itens (id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Stock-zeroing audit log
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS zeramentos (
            id               INT          NOT NULL AUTO_INCREMENT,
            batch_id         VARCHAR(128) NOT NULL,
            created_at       DATETIME     NOT NULL,
            responsavel      VARCHAR(255) NOT NULL,
            comentario       TEXT         NULL,
            restored         TINYINT(1)   NOT NULL DEFAULT 0,
            restored_at      DATETIME     NULL,
            restored_by      VARCHAR(255) NULL,
            restored_comment TEXT         NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_zeramentos_batch (batch_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Per-item snapshot for each zeroing batch
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS zeramento_itens (
            id           INT NOT NULL AUTO_INCREMENT,
            zeramento_id INT NOT NULL,
            item_id      INT NOT NULL,
            previous_qty INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            CONSTRAINT fk_zeramento_itens_zeramento
                FOREIGN KEY (zeramento_id) REFERENCES zeramentos (id)
                ON DELETE CASCADE,
            CONSTRAINT fk_zeramento_itens_item
                FOREIGN KEY (item_id) REFERENCES itens (id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Backup table for history-clearing operations
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS backup_movimentacoes (
            id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            original_id    BIGINT          NULL,
            item_id        INT             NOT NULL,
            tipo           VARCHAR(32)     NOT NULL,
            quantidade     INT             NOT NULL DEFAULT 0,
            validade       DATETIME        NULL,
            responsavel    VARCHAR(255)    NULL,
            recebido_por   VARCHAR(255)    NULL,
            observacao     TEXT            NULL,
            data           DATETIME        NULL,
            batch_id       VARCHAR(64)     NOT NULL,
            restored       TINYINT(1)      NOT NULL DEFAULT 0,
            backup_created DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

// Allow running this file directly for a manual setup check
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    require_once __DIR__ . '/conexao.php';
    // $pdo is already available and tables were initialised by conexao.php,
    // but we call it explicitly here so the output is visible.
    try {
        initializeDatabase($pdo);
        echo "✅ Banco de dados inicializado com sucesso. Todas as tabelas foram verificadas/criadas.\n";
    } catch (PDOException $e) {
        http_response_code(500);
        echo "❌ Erro ao inicializar banco de dados: " . htmlspecialchars($e->getMessage()) . "\n";
    }
}
?>
