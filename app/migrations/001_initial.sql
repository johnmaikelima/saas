-- Migration 001: Tabelas do SaaS PDV Pro (Multi-tenant MySQL)

-- Tenants (empresas)
CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    razao_social VARCHAR(255) NOT NULL DEFAULT '',
    nome_fantasia VARCHAR(255) NOT NULL DEFAULT '',
    cnpj VARCHAR(20) NOT NULL DEFAULT '',
    ie VARCHAR(20) DEFAULT '',
    im VARCHAR(20) DEFAULT '',
    cep VARCHAR(10) DEFAULT '',
    endereco VARCHAR(255) DEFAULT '',
    numero VARCHAR(20) DEFAULT '',
    complemento VARCHAR(100) DEFAULT '',
    bairro VARCHAR(100) DEFAULT '',
    cidade VARCHAR(100) DEFAULT '',
    estado VARCHAR(2) DEFAULT '',
    telefone VARCHAR(20) DEFAULT '',
    email VARCHAR(255) DEFAULT '',
    regime_tributario INT DEFAULT 1,
    logo VARCHAR(255) DEFAULT '',
    plano VARCHAR(50) DEFAULT 'free',
    status ENUM('ativo','suspenso','cancelado') DEFAULT 'ativo',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Configurações por tenant
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    chave VARCHAR(100) NOT NULL,
    valor TEXT DEFAULT '',
    grupo VARCHAR(50) DEFAULT 'sistema',
    UNIQUE KEY uk_tenant_chave (tenant_id, chave),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Usuários (multi-tenant)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    login VARCHAR(100) NOT NULL,
    email VARCHAR(255) DEFAULT '',
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('admin','gerente','caixa') DEFAULT 'caixa',
    ativo TINYINT(1) DEFAULT 1,
    ultimo_acesso DATETIME NULL,
    trocar_senha TINYINT(1) DEFAULT 0,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tenant_login (tenant_id, login),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Categorias
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cor VARCHAR(7) DEFAULT '#6c757d',
    icone VARCHAR(50) DEFAULT 'fa-tag',
    ativo TINYINT(1) DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Produtos
CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    codigo_barras VARCHAR(50) DEFAULT '',
    descricao VARCHAR(255) NOT NULL,
    descricao_curta VARCHAR(40) DEFAULT '',
    unidade VARCHAR(5) DEFAULT 'UN',
    ncm VARCHAR(10) DEFAULT '',
    cfop VARCHAR(5) DEFAULT '5102',
    cest VARCHAR(10) DEFAULT '',
    cst_csosn VARCHAR(5) DEFAULT '102',
    preco_custo DECIMAL(10,2) DEFAULT 0,
    preco_venda DECIMAL(10,2) NOT NULL DEFAULT 0,
    margem_lucro DECIMAL(10,2) DEFAULT 0,
    estoque_atual DECIMAL(10,3) DEFAULT 0,
    estoque_minimo DECIMAL(10,3) DEFAULT 0,
    categoria_id INT NULL,
    foto VARCHAR(255) DEFAULT '',
    ativo TINYINT(1) DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- Clientes
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    nome VARCHAR(200) NOT NULL,
    cpf_cnpj VARCHAR(20) DEFAULT '',
    rg_ie VARCHAR(30) DEFAULT '',
    telefone VARCHAR(20) DEFAULT '',
    celular VARCHAR(20) DEFAULT '',
    email VARCHAR(255) DEFAULT '',
    cep VARCHAR(10) DEFAULT '',
    endereco VARCHAR(255) DEFAULT '',
    numero VARCHAR(20) DEFAULT '',
    complemento VARCHAR(100) DEFAULT '',
    bairro VARCHAR(100) DEFAULT '',
    cidade VARCHAR(100) DEFAULT '',
    estado VARCHAR(2) DEFAULT '',
    observacoes TEXT DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Caixas
CREATE TABLE IF NOT EXISTS caixas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    usuario_id INT NOT NULL,
    valor_abertura DECIMAL(10,2) DEFAULT 0,
    valor_fechamento DECIMAL(10,2) DEFAULT 0,
    valor_informado DECIMAL(10,2) DEFAULT 0,
    diferenca DECIMAL(10,2) DEFAULT 0,
    status ENUM('aberto','fechado') DEFAULT 'aberto',
    aberto_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    fechado_em DATETIME NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Movimentações de caixa
CREATE TABLE IF NOT EXISTS caixa_movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    caixa_id INT NOT NULL,
    tipo ENUM('sangria','suprimento') NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    usuario_id INT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (caixa_id) REFERENCES caixas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Vendas
CREATE TABLE IF NOT EXISTS vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    caixa_id INT NULL,
    cliente_id INT NULL,
    usuario_id INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    desconto_tipo ENUM('valor','percentual') DEFAULT 'valor',
    desconto_valor DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    cpf_cnpj_nota VARCHAR(20) DEFAULT '',
    status ENUM('concluida','cancelada') DEFAULT 'concluida',
    motivo_cancelamento TEXT DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    cancelado_em DATETIME NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (caixa_id) REFERENCES caixas(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Itens da venda
CREATE TABLE IF NOT EXISTS venda_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    venda_id INT NOT NULL,
    produto_id INT NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    quantidade DECIMAL(10,3) NOT NULL,
    valor_unitario DECIMAL(10,2) NOT NULL,
    desconto DECIMAL(10,2) DEFAULT 0,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

-- Pagamentos da venda
CREATE TABLE IF NOT EXISTS venda_pagamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    venda_id INT NOT NULL,
    forma ENUM('dinheiro','pix','debito','credito') NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    troco DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE
);

-- Movimentações de estoque
CREATE TABLE IF NOT EXISTS estoque_movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    produto_id INT NOT NULL,
    tipo ENUM('entrada','saida','ajuste') NOT NULL,
    quantidade DECIMAL(10,3) NOT NULL,
    motivo VARCHAR(255) DEFAULT '',
    referencia_tipo VARCHAR(20) DEFAULT '',
    referencia_id INT DEFAULT 0,
    usuario_id INT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Índices para performance
CREATE INDEX idx_produtos_tenant ON produtos(tenant_id);
CREATE INDEX idx_produtos_barras ON produtos(tenant_id, codigo_barras);
CREATE INDEX idx_produtos_ativo ON produtos(tenant_id, ativo);
CREATE INDEX idx_clientes_tenant ON clientes(tenant_id);
CREATE INDEX idx_clientes_cpf ON clientes(tenant_id, cpf_cnpj);
CREATE INDEX idx_vendas_tenant ON vendas(tenant_id);
CREATE INDEX idx_vendas_caixa ON vendas(tenant_id, caixa_id);
CREATE INDEX idx_vendas_status ON vendas(tenant_id, status);
CREATE INDEX idx_vendas_criado ON vendas(tenant_id, criado_em);
CREATE INDEX idx_venda_itens_venda ON venda_itens(venda_id);
CREATE INDEX idx_venda_pag_venda ON venda_pagamentos(venda_id);
CREATE INDEX idx_caixas_status ON caixas(tenant_id, status);
CREATE INDEX idx_estoque_mov ON estoque_movimentacoes(tenant_id, produto_id);
CREATE INDEX idx_categorias_tenant ON categorias(tenant_id);
CREATE INDEX idx_usuarios_tenant ON usuarios(tenant_id);
