-- Migration 007: Tokens de impersonate (acesso administrativo)

CREATE TABLE IF NOT EXISTS impersonate_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    usado TINYINT(1) DEFAULT 0,
    expira_em DATETIME NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    usado_em DATETIME NULL,
    ip_usado VARCHAR(45) NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
