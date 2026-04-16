-- Migration 006: Registro de aceite dos termos de uso

ALTER TABLE tenants ADD COLUMN aceite_termos TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
ALTER TABLE tenants ADD COLUMN aceite_termos_em DATETIME NULL AFTER aceite_termos;
ALTER TABLE tenants ADD COLUMN aceite_termos_ip VARCHAR(45) NULL AFTER aceite_termos_em;
ALTER TABLE tenants ADD COLUMN aceite_termos_versao VARCHAR(20) DEFAULT '1.0' AFTER aceite_termos_ip;
