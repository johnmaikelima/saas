<?php
/**
 * Template HTML do PDF de orcamento (imprimivel).
 * Variaveis esperadas: $orc, $itens, $empresa, $logoUrl
 */
$numero = str_pad($orc['numero'], 4, '0', STR_PAD_LEFT);
$descontoBR = $orc['desconto_tipo'] === 'percentual' ? number_format($orc['desconto_valor'], 2, ',', '.') . '%' : 'R$';
$descontoValor = $orc['desconto_tipo'] === 'percentual' ? ($orc['subtotal'] * $orc['desconto_valor'] / 100) : $orc['desconto_valor'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Orçamento #<?= $numero ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #333; padding: 15mm; max-width: 210mm; margin: 0 auto; background: #fff; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #002C87; padding-bottom: 12px; margin-bottom: 18px; }
        .header-left { flex: 1; }
        .logo { max-height: 60px; max-width: 180px; margin-bottom: 6px; }
        .empresa-nome { font-size: 14pt; font-weight: bold; color: #002C87; }
        .empresa-info { font-size: 9pt; color: #666; margin-top: 3px; }
        .header-right { text-align: right; }
        .doc-titulo { font-size: 20pt; font-weight: bold; color: #002C87; }
        .doc-numero { font-size: 13pt; color: #444; margin-top: 2px; }
        .doc-data { font-size: 9pt; color: #666; margin-top: 2px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 9pt; font-weight: bold; text-transform: uppercase; margin-top: 5px; }
        .status-pendente { background: #fff3cd; color: #856404; }
        .status-aprovado { background: #d1ecf1; color: #0c5460; }
        .status-convertido { background: #d4edda; color: #155724; }
        .status-recusado { background: #f8d7da; color: #721c24; }
        .status-expirado { background: #e2e3e5; color: #383d41; }
        .status-cancelado { background: #343a40; color: #fff; }

        .section { margin-bottom: 14px; }
        .section-titulo { font-size: 9pt; color: #fff; background: #002C87; padding: 4px 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .section-body { border: 1px solid #ddd; border-top: 0; padding: 10px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .info-item strong { color: #002C87; }

        table.itens { width: 100%; border-collapse: collapse; margin-top: 0; }
        table.itens th { background: #002C87; color: #fff; padding: 7px 6px; text-align: left; font-size: 9pt; font-weight: bold; }
        table.itens td { padding: 7px 6px; border-bottom: 1px solid #eee; font-size: 10pt; }
        table.itens tr:nth-child(even) td { background: #f9f9fb; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }

        .totais { margin-top: 12px; width: 100%; display: flex; justify-content: flex-end; }
        .totais table { width: 320px; border-collapse: collapse; }
        .totais td { padding: 5px 10px; font-size: 10pt; }
        .totais .label { text-align: right; color: #555; }
        .totais .valor { text-align: right; font-weight: bold; }
        .totais .total-final { background: #002C87; color: #fff; font-size: 13pt; }

        .obs-bloco { border-left: 3px solid #002C87; padding: 8px 12px; background: #f4f7fb; margin-top: 10px; font-size: 10pt; white-space: pre-wrap; }
        .obs-titulo { color: #002C87; font-weight: bold; margin-bottom: 4px; }

        .footer { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; font-size: 8pt; color: #888; text-align: center; }
        .assinatura { margin-top: 50px; display: flex; justify-content: space-around; }
        .assinatura div { text-align: center; width: 45%; border-top: 1px solid #333; padding-top: 4px; font-size: 9pt; }

        .print-btn { position: fixed; top: 15px; right: 15px; background: #002C87; color: #fff; padding: 10px 18px; border: 0; border-radius: 6px; cursor: pointer; font-size: 11pt; font-weight: bold; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
        .print-btn:hover { background: #001d5c; }

        @media print {
            body { padding: 10mm; }
            .print-btn { display: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<button class="print-btn no-print" onclick="window.print()">🖨 Imprimir / Salvar PDF</button>

<div class="header">
    <div class="header-left">
        <?php if ($logoUrl): ?>
            <img src="<?= e($logoUrl) ?>" class="logo" alt="Logo">
        <?php endif; ?>
        <div class="empresa-nome"><?= e($empresa['razao_social'] ?? $empresa['nome_fantasia'] ?? 'Empresa') ?></div>
        <div class="empresa-info">
            <?php if (!empty($empresa['cnpj'])): ?>CNPJ: <?= e($empresa['cnpj']) ?><br><?php endif; ?>
            <?php if (!empty($empresa['endereco'])): ?><?= e($empresa['endereco']) ?><br><?php endif; ?>
            <?php if (!empty($empresa['cidade'])): ?><?= e($empresa['cidade']) ?><?= !empty($empresa['estado']) ? '/' . e($empresa['estado']) : '' ?><br><?php endif; ?>
            <?php if (!empty($empresa['telefone'])): ?>Tel: <?= e($empresa['telefone']) ?><?php endif; ?>
            <?php if (!empty($empresa['email'])): ?> · <?= e($empresa['email']) ?><?php endif; ?>
        </div>
    </div>
    <div class="header-right">
        <div class="doc-titulo">ORÇAMENTO</div>
        <div class="doc-numero">Nº <?= $numero ?></div>
        <div class="doc-data">Emitido em <?= formatDate($orc['data_orcamento']) ?></div>
        <div class="doc-data"><strong>Validade:</strong> <?= formatDate($orc['validade']) ?></div>
        <span class="status-badge status-<?= e($orc['status']) ?>"><?= e($orc['status']) ?></span>
    </div>
</div>

<?php if ($orc['cliente_nome']): ?>
<div class="section">
    <div class="section-titulo">Cliente</div>
    <div class="section-body">
        <div class="info-grid">
            <div class="info-item"><strong>Nome:</strong> <?= e($orc['cliente_nome']) ?></div>
            <?php if ($orc['cliente_doc']): ?><div class="info-item"><strong>CPF/CNPJ:</strong> <?= e($orc['cliente_doc']) ?></div><?php endif; ?>
            <?php if ($orc['cliente_cel'] || $orc['cliente_tel']): ?><div class="info-item"><strong>Telefone:</strong> <?= e($orc['cliente_cel'] ?: $orc['cliente_tel']) ?></div><?php endif; ?>
            <?php if ($orc['cliente_email']): ?><div class="info-item"><strong>Email:</strong> <?= e($orc['cliente_email']) ?></div><?php endif; ?>
            <?php if ($orc['cliente_endereco']): ?><div class="info-item" style="grid-column:1/-1;"><strong>Endereço:</strong> <?= e($orc['cliente_endereco']) ?><?= $orc['cliente_cidade'] ? ' - ' . e($orc['cliente_cidade']) : '' ?><?= $orc['cliente_estado'] ? '/' . e($orc['cliente_estado']) : '' ?></div><?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="section">
    <div class="section-titulo">Itens do Orçamento</div>
    <table class="itens">
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th>Descrição</th>
                <th class="text-end" style="width:10%">Qtd</th>
                <th class="text-end" style="width:15%">Vlr Unit.</th>
                <th class="text-end" style="width:10%">Desc.</th>
                <th class="text-end" style="width:15%">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($itens as $i => $item): ?>
            <tr>
                <td class="text-center"><?= $i + 1 ?></td>
                <td><?= e($item['descricao']) ?></td>
                <td class="text-end"><?= rtrim(rtrim(number_format($item['quantidade'], 3, ',', '.'), '0'), ',') ?></td>
                <td class="text-end"><?= formatMoney($item['valor_unitario']) ?></td>
                <td class="text-end"><?= formatMoney($item['desconto']) ?></td>
                <td class="text-end"><strong><?= formatMoney($item['subtotal']) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="totais">
    <table>
        <tr><td class="label">Subtotal:</td><td class="valor"><?= formatMoney($orc['subtotal']) ?></td></tr>
        <?php if ($orc['desconto_valor'] > 0): ?>
        <tr><td class="label">Desconto (<?= $descontoBR ?>):</td><td class="valor" style="color:#c0392b;">-<?= formatMoney($descontoValor) ?></td></tr>
        <?php endif; ?>
        <tr class="total-final"><td class="label" style="color:#fff;">TOTAL:</td><td class="valor"><?= formatMoney($orc['total']) ?></td></tr>
    </table>
</div>

<?php if ($orc['condicoes_pagamento']): ?>
    <div class="obs-bloco">
        <div class="obs-titulo">Condições de Pagamento</div>
        <?= e($orc['condicoes_pagamento']) ?>
    </div>
<?php endif; ?>

<?php if ($orc['prazo_entrega']): ?>
    <div class="obs-bloco">
        <div class="obs-titulo">Prazo de Entrega</div>
        <?= e($orc['prazo_entrega']) ?>
    </div>
<?php endif; ?>

<?php if ($orc['observacoes']): ?>
    <div class="obs-bloco">
        <div class="obs-titulo">Observações</div>
        <?= e($orc['observacoes']) ?>
    </div>
<?php endif; ?>

<div class="assinatura">
    <div>Vendedor: <?= e($orc['vendedor_nome'] ?? '') ?></div>
    <div>Cliente: <?= e($orc['cliente_nome'] ?? '') ?></div>
</div>

<div class="footer">
    Orçamento gerado em <?= date('d/m/Y H:i') ?> ·
    Válido até <?= formatDate($orc['validade']) ?> ·
    <?= e($empresa['nome_fantasia'] ?? $empresa['razao_social'] ?? '') ?>
</div>

<script>
    // Auto focus impressao se pediu pelo query
    if (new URLSearchParams(location.search).get('print') === '1') {
        setTimeout(() => window.print(), 300);
    }
</script>

</body>
</html>
