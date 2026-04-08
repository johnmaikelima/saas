-- Migration 003: Adicionar campo observacao no caixa
ALTER TABLE caixas ADD COLUMN observacao TEXT DEFAULT NULL AFTER diferenca;
