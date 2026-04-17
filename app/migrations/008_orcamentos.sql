-- Migration 008: Tabelas de Orcamentos

CREATE TABLE IF NOT EXISTS orcamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    numero INT NOT NULL,
    cliente_id INT NULL,
    usuario_id INT NULL,
    data_orcamento DATE NOT NULL,
    validade DATE NOT NULL,
    status ENUM('pendente','aprovado','recusado','expirado','convertido','cancelado') NOT NULL DEFAULT 'pendente',
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    desconto_tipo ENUM('valor','percentual') NOT NULL DEFAULT 'valor',
    desconto_valor DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    condicoes_pagamento TEXT NULL,
    prazo_entrega VARCHAR(255) NULL,
    observacoes TEXT NULL COMMENT 'Visiveis ao cliente',
    observacoes_internas TEXT NULL COMMENT 'Visiveis apenas internamente',
    token_publico VARCHAR(64) NULL,
    venda_id INT NULL COMMENT 'Preenchido quando convertido em venda',
    aprovado_em DATETIME NULL,
    recusado_em DATETIME NULL,
    recusa_motivo TEXT NULL,
    convertido_em DATETIME NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE SET NULL,
    UNIQUE KEY uq_tenant_numero (tenant_id, numero),
    UNIQUE KEY uq_token (token_publico),
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_tenant_data (tenant_id, data_orcamento),
    INDEX idx_tenant_cliente (tenant_id, cliente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orcamento_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    orcamento_id INT NOT NULL,
    produto_id INT NULL,
    descricao VARCHAR(255) NOT NULL,
    quantidade DECIMAL(12,3) NOT NULL DEFAULT 1,
    valor_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
    desconto DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    ordem INT NOT NULL DEFAULT 0,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (orcamento_id) REFERENCES orcamentos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE SET NULL,
    INDEX idx_orcamento (orcamento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orcamento_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    orcamento_id INT NOT NULL,
    usuario_id INT NULL,
    acao VARCHAR(50) NOT NULL COMMENT 'criado, editado, enviado, aprovado, recusado, convertido, duplicado, cancelado',
    descricao TEXT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (orcamento_id) REFERENCES orcamentos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_orcamento (orcamento_id, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
