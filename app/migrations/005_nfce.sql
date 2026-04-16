-- Migration 005: Tabelas para emissao de NFC-e

-- NFC-e emitidas
CREATE TABLE IF NOT EXISTS nfce (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    venda_id INT NULL,
    numero INT NOT NULL,
    serie INT NOT NULL DEFAULT 1,
    chave_acesso VARCHAR(44) NULL,
    protocolo VARCHAR(20) NULL,
    xml_autorizado LONGTEXT NULL,
    status ENUM('pendente','autorizada','cancelada','erro','inutilizada') DEFAULT 'pendente',
    ambiente TINYINT NOT NULL DEFAULT 2 COMMENT '1=producao, 2=homologacao',
    mensagem_erro TEXT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant_numero (tenant_id, numero, serie),
    INDEX idx_chave (chave_acesso),
    INDEX idx_venda (venda_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Logs de comunicacao NFC-e
CREATE TABLE IF NOT EXISTS nfce_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    nfce_id INT NULL,
    comando VARCHAR(50) NOT NULL,
    request_data TEXT NULL,
    response_data TEXT NULL,
    sucesso TINYINT(1) DEFAULT 0,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (nfce_id) REFERENCES nfce(id) ON DELETE SET NULL,
    INDEX idx_tenant_criado (tenant_id, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- NFC-e inutilizadas
CREATE TABLE IF NOT EXISTS nfce_inutilizadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    numero_inicio INT NOT NULL,
    numero_fim INT NOT NULL,
    serie INT NOT NULL DEFAULT 1,
    protocolo VARCHAR(20) NULL,
    motivo TEXT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Adicionar coluna nfce_id na tabela vendas (cpf_cnpj_nota ja existe na 001)
ALTER TABLE vendas ADD COLUMN nfce_id INT NULL AFTER status;
