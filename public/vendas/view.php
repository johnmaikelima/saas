<?php
$pageTitle = 'Detalhes da Venda';
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$tid = tenantId();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT v.*, u.nome as vendedor, c.nome as cliente_nome, c.cpf_cnpj as cliente_doc
    FROM vendas v
    LEFT JOIN usuarios u ON u.id = v.usuario_id
    LEFT JOIN clientes c ON c.id = v.cliente_id
    WHERE v.id = ? AND v.tenant_id = ?");
$stmt->execute([$id, $tid]);
$venda = $stmt->fetch();
if (!$venda) {
    flashError('Venda não encontrada.');
    redirect('vendas/');
}

$stmt = $pdo->prepare("SELECT vi.*, p.codigo_barras
    FROM venda_itens vi
    LEFT JOIN produtos p ON p.id = vi.produto_id
    WHERE vi.venda_id = ? AND vi.tenant_id = ?");
$stmt->execute([$id, $tid]);
$itens = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM venda_pagamentos WHERE venda_id = ? AND tenant_id = ?");
$stmt->execute([$id, $tid]);
$pagamentos = $stmt->fetchAll();

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="page-title mb-0"><i class="fas fa-receipt me-2"></i>Venda #<?= $id ?></h5>
    <div>
        <?php if ($venda['status'] === 'concluida'): ?>
            <?php if (temPerfil('admin', 'gerente')): ?>
                <a href="cancelar.php?id=<?= $id ?>" class="btn btn-outline-danger btn-sm"><i class="fas fa-ban me-1"></i>Cancelar</a>
            <?php endif; ?>
        <?php endif; ?>
        <a href="<?= baseUrl('vendas/') ?>" class="btn btn-secondary btn-sm">Voltar</a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Info da venda -->
        <div class="card shadow-sm mb-3">
            <div class="card-header">Informacoes</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4"><strong>Data:</strong> <?= formatDateTime($venda['criado_em']) ?></div>
                    <div class="col-md-4"><strong>Vendedor:</strong> <?= e($venda['vendedor']) ?></div>
                    <div class="col-md-4"><strong>Status:</strong>
                        <span class="badge bg-<?= $venda['status'] === 'concluida' ? 'success' : 'danger' ?>"><?= ucfirst(e($venda['status'])) ?></span>
                    </div>
                    <?php if (!empty($venda['cliente_nome'])): ?>
                        <div class="col-md-6 mt-2"><strong>Cliente:</strong> <?= e($venda['cliente_nome']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($venda['cpf_cnpj_nota'])): ?>
                        <div class="col-md-6 mt-2"><strong>CPF/CNPJ Nota:</strong> <?= e($venda['cpf_cnpj_nota']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($venda['motivo_cancelamento'])): ?>
                        <div class="col-12 mt-2"><strong>Motivo cancelamento:</strong> <?= e($venda['motivo_cancelamento']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Itens -->
        <div class="card shadow-sm mb-3">
            <div class="card-header">Itens</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Cod</th><th>Descrição</th><th>Qtd</th><th>Vlr.Unit</th><th>Desc.</th><th>Subtotal</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($itens as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($item['codigo_barras'] ?: $item['produto_id']) ?></td>
                            <td><?= e($item['descricao']) ?></td>
                            <td><?= $item['quantidade'] ?></td>
                            <td><?= formatMoney($item['valor_unitario']) ?></td>
                            <td><?= formatMoney($item['desconto']) ?></td>
                            <td class="fw-bold"><?= formatMoney($item['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Totais -->
        <div class="card shadow-sm mb-3">
            <div class="card-header">Totais</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span><span><?= formatMoney($venda['subtotal']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Desconto (<?= e($venda['desconto_tipo']) ?>):</span><span class="text-danger">-<?= formatMoney($venda['desconto_valor']) ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between fw-bold fs-5">
                    <span>Total:</span><span class="text-success"><?= formatMoney($venda['total']) ?></span>
                </div>
            </div>
        </div>

        <!-- Pagamentos -->
        <div class="card shadow-sm mb-3">
            <div class="card-header">Pagamentos</div>
            <div class="card-body">
                <?php foreach ($pagamentos as $pag): ?>
                    <div class="d-flex justify-content-between mb-1">
                        <span><?= ucfirst(e($pag['forma'])) ?></span>
                        <span><?= formatMoney($pag['valor']) ?></span>
                    </div>
                    <?php if ($pag['troco'] > 0): ?>
                        <div class="d-flex justify-content-between mb-1 text-info small">
                            <span>Troco:</span><span><?= formatMoney($pag['troco']) ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
