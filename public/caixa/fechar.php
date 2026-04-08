<?php
$pageTitle = 'Fechar Caixa';
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$user = usuario();
$tid = tenantId();
$caixa = getCaixaAberto($user['id']);

if (!$caixa) {
    flashWarning('Nenhum caixa aberto.');
    redirect('caixa/');
}

// Calcular valores
$stmt = $pdo->prepare("SELECT vp.forma, COALESCE(SUM(vp.valor),0) as total, COALESCE(SUM(vp.troco),0) as troco
    FROM venda_pagamentos vp
    INNER JOIN vendas v ON v.id = vp.venda_id
    WHERE v.caixa_id = ? AND v.tenant_id = ? AND v.status = 'concluida'
    GROUP BY vp.forma");
$stmt->execute([$caixa['id'], $tid]);
$porForma = $stmt->fetchAll();

$totalVendas = 0;
$totalDinheiro = 0;
foreach ($porForma as $p) {
    $totalVendas += $p['total'] - $p['troco'];
    if ($p['forma'] === 'dinheiro') $totalDinheiro = $p['total'] - $p['troco'];
}

$stmt = $pdo->prepare("SELECT COALESCE(SUM(valor),0) FROM caixa_movimentacoes WHERE caixa_id = ? AND tenant_id = ? AND tipo = 'sangria'");
$stmt->execute([$caixa['id'], $tid]);
$sangrias = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(valor),0) FROM caixa_movimentacoes WHERE caixa_id = ? AND tenant_id = ? AND tipo = 'suprimento'");
$stmt->execute([$caixa['id'], $tid]);
$suprimentos = (float)$stmt->fetchColumn();

$esperado = $caixa['valor_abertura'] + $totalDinheiro - $sangrias + $suprimentos;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flashError('Token inválido.');
        redirect('caixa/');
    }

    $valorInformado = (float)($_POST['valor_informado'] ?? 0);
    $diferenca = $valorInformado - $esperado;

    $pdo->prepare("UPDATE caixas SET status = 'fechado', valor_fechamento = ?, valor_informado = ?, diferenca = ?, fechado_em = NOW() WHERE id = ? AND tenant_id = ?")
        ->execute([$esperado, $valorInformado, $diferenca, $caixa['id'], $tid]);

    auditLog('caixa_fechado', "Caixa #{$caixa['id']} fechado. Diferença: " . formatMoney($diferenca));
    flashSuccess('Caixa #' . $caixa['id'] . ' fechado. Diferença: ' . formatMoney($diferenca));
    redirect('caixa/');
}

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-door-closed me-2"></i>Fechar Caixa #<?= $caixa['id'] ?></h5>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm mb-3">
            <div class="card-header">Resumo do Caixa</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td>Abertura:</td><td class="text-end"><?= formatMoney($caixa['valor_abertura']) ?></td></tr>
                    <?php foreach ($porForma as $p): ?>
                        <tr>
                            <td>Vendas (<?= ucfirst(e($p['forma'])) ?>):</td>
                            <td class="text-end text-success">+ <?= formatMoney($p['total'] - $p['troco']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr><td>Sangrias:</td><td class="text-end text-danger">- <?= formatMoney($sangrias) ?></td></tr>
                    <tr><td>Suprimentos:</td><td class="text-end text-info">+ <?= formatMoney($suprimentos) ?></td></tr>
                    <tr class="table-dark fw-bold">
                        <td>Valor Esperado em Dinheiro:</td>
                        <td class="text-end"><?= formatMoney($esperado) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header">Conferência</div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">Valor informado (dinheiro contado)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" name="valor_informado" step="0.01" min="0"
                                   value="<?= number_format($esperado, 2, '.', '') ?>" autofocus>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Confirma o fechamento do caixa?')">
                        <i class="fas fa-door-closed me-1"></i>Fechar Caixa
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
