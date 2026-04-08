-- SCE - Sistema de Controle de Estoque EPI
-- Database schema: creates all required tables if they do not already exist.
-- Safe to run multiple times (uses IF NOT EXISTS).

CREATE TABLE IF NOT EXISTS itens (
    id           INT          NOT NULL AUTO_INCREMENT,
    nome         VARCHAR(255) NOT NULL,
    numero_item  VARCHAR(64)  NOT NULL DEFAULT '',
    quantidade   INT          NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS zeramento_itens (
    id            INT NOT NULL AUTO_INCREMENT,
    zeramento_id  INT NOT NULL,
    item_id       INT NOT NULL,
    previous_qty  INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    CONSTRAINT fk_zeramento_itens_zeramento
        FOREIGN KEY (zeramento_id) REFERENCES zeramentos (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_zeramento_itens_item
        FOREIGN KEY (item_id) REFERENCES itens (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
