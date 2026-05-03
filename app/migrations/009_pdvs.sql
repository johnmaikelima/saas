-- Migration 009: Adicionar suporte a múltiplos PDVs (terminais)

CREATE TABLE IF NOT EXISTS pdvs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao VARCHAR(255) DEFAULT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant (tenant_id),
    INDEX idx_ativo (ativo),
    UNIQUE KEY unq_tenant_nome (tenant_id, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE caixas ADD COLUMN pdv_id INT DEFAULT NULL AFTER usuario_id;
ALTER TABLE caixas ADD INDEX idx_pdv (pdv_id);
