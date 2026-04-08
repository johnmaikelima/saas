-- Migration 004: Vincular tenant ao sistema de licenciamento do Painel
ALTER TABLE tenants ADD COLUMN licenca_chave VARCHAR(19) DEFAULT NULL AFTER plano;
ALTER TABLE tenants ADD COLUMN api_token VARCHAR(64) DEFAULT NULL AFTER licenca_chave;
ALTER TABLE tenants ADD COLUMN data_vencimento DATETIME DEFAULT NULL AFTER api_token;
ALTER TABLE tenants ADD COLUMN painel_cliente_id INT DEFAULT NULL AFTER data_vencimento;
