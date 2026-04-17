<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../../app/includes/auth.php';

$user = usuario();
$pdo = db();
$tid = tenantId();

// Resumo do dia
$hoje = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) as qtd, COALESCE(SUM(total),0) as total FROM vendas WHERE tenant_id = ? AND DATE(criado_em) = ? AND status = 'concluida'");
$stmt->execute([$tid, $hoje]);
$resumoDia = $stmt->fetch();
$ticketMedio = $resumoDia['qtd'] > 0 ? $resumoDia['total'] / $resumoDia['qtd'] : 0;

// Formas de pagamento do dia
$stmt = $pdo->prepare("SELECT vp.forma, SUM(vp.valor) as total FROM venda_pagamentos vp
    INNER JOIN vendas v ON v.id = vp.venda_id
    WHERE v.tenant_id = ? AND DATE(v.criado_em) = ? AND v.status = 'concluida'
    GROUP BY vp.forma");
$stmt->execute([$tid, $hoje]);
$pagamentosDia = $stmt->fetchAll();

// Vendas últimos 7 dias para gráfico
$vendasSemana = [];
for ($i = 6; $i >= 0; $i--) {
    $data = date('Y-m-d', strtotime("-{$i} days"));
    $label = date('d/m', strtotime($data));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) as total FROM vendas WHERE tenant_id = ? AND DATE(criado_em) = ? AND status = 'concluida'");
    $stmt->execute([$tid, $data]);
    $row = $stmt->fetch();
    $vendasSemana[] = ['label' => $label, 'total' => (float)$row['total']];
}

// Últimas 10 vendas
$stmt = $pdo->prepare("SELECT v.*, u.nome as vendedor FROM vendas v LEFT JOIN usuarios u ON u.id = v.usuario_id WHERE v.tenant_id = ? ORDER BY v.criado_em DESC LIMIT 10");
$stmt->execute([$tid]);
$ultimasVendas = $stmt->fetchAll();

// Produtos com estoque baixo
$stmt = $pdo->prepare("SELECT * FROM produtos WHERE tenant_id = ? AND ativo = 1 AND estoque_atual <= estoque_minimo AND estoque_minimo > 0 ORDER BY estoque_atual ASC LIMIT 10");
$stmt->execute([$tid]);
$estoqueBaixo = $stmt->fetchAll();

// Orcamentos - ultimos 30 dias
$orc30Ini = date('Y-m-d', strtotime('-30 days'));
$stmt = $pdo->prepare("SELECT status, COUNT(*) qtd, COALESCE(SUM(total),0) total
    FROM orcamentos WHERE tenant_id = ? AND data_orcamento >= ? GROUP BY status");
$stmt->execute([$tid, $orc30Ini]);
$orcResumo = ['pendente'=>['qtd'=>0,'total'=>0],'aprovado'=>['qtd'=>0,'total'=>0],
              'convertido'=>['qtd'=>0,'total'=>0],'recusado'=>['qtd'=>0,'total'=>0],
              'expirado'=>['qtd'=>0,'total'=>0]];
foreach ($stmt->fetchAll() as $r) {
    if (isset($orcResumo[$r['status']])) $orcResumo[$r['status']] = ['qtd'=>(int)$r['qtd'],'total'=>(float)$r['total']];
}
$orcTotalQtd = array_sum(array_column($orcResumo, 'qtd'));
$orcTaxaConv = $orcTotalQtd > 0 ? ($orcResumo['convertido']['qtd'] / $orcTotalQtd * 100) : 0;

$caixaAberto = getCaixaAberto();

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title mb-1">Vendas Hoje</h6>
                        <h3 class="mb-0"><?= formatMoney($resumoDia['total']) ?></h3>
                    </div>
                    <div class="align-self-center"><i class="fas fa-dollar-sign fa-2x opacity-50"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title mb-1">Qtd. Vendas</h6>
                        <h3 class="mb-0"><?= $resumoDia['qtd'] ?></h3>
                    </div>
                    <div class="align-self-center"><i class="fas fa-shopping-bag fa-2x opacity-50"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title mb-1">Ticket Médio</h6>
                        <h3 class="mb-0"><?= formatMoney($ticketMedio) ?></h3>
                    </div>
                    <div class="align-self-center"><i class="fas fa-chart-line fa-2x opacity-50"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title mb-1">Status</h6>
                        <span class="badge bg-<?= $caixaAberto ? 'success' : 'danger' ?>">
                            <i class="fas fa-cash-register me-1"></i>Caixa <?= $caixaAberto ? 'Aberto' : 'Fechado' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($pagamentosDia)): ?>
<div class="row mb-4">
    <?php foreach ($pagamentosDia as $pag): ?>
    <div class="col-md-3 mb-2">
        <div class="card shadow-sm">
            <div class="card-body py-2">
                <small class="text-muted">
                    <?= match($pag['forma']) { 'dinheiro'=>'Dinheiro','pix'=>'PIX','debito'=>'Débito','credito'=>'Crédito',default=>$pag['forma'] } ?>
                </small>
                <h5 class="mb-0"><?= formatMoney($pag['total']) ?></h5>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card shadow">
            <div class="card-header"><i class="fas fa-chart-bar me-2"></i>Vendas - Últimos 7 dias</div>
            <div class="card-body">
                <canvas id="chartVendas" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card shadow">
            <div class="card-header"><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Estoque Baixo</div>
            <div class="card-body p-0">
                <?php if (empty($estoqueBaixo)): ?>
                    <p class="text-muted text-center p-3">Nenhum produto com estoque baixo</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                    <?php foreach ($estoqueBaixo as $prod): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <small><?= e($prod['descricao']) ?></small>
                            <span class="badge bg-danger"><?= $prod['estoque_atual'] ?>/<?= $prod['estoque_minimo'] ?></span>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-file-signature me-2"></i>Orçamentos (últimos 30 dias)</span>
        <a href="<?= baseUrl('orcamentos/') ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-list me-1"></i>Ver todos
        </a>
    </div>
    <div class="card-body">
        <div class="row text-center g-2">
            <div class="col">
                <div class="small text-muted">Pendentes</div>
                <div class="fs-5 fw-bold text-warning"><?= $orcResumo['pendente']['qtd'] ?></div>
            </div>
            <div class="col">
                <div class="small text-muted">Aprovados</div>
                <div class="fs-5 fw-bold text-info"><?= $orcResumo['aprovado']['qtd'] ?></div>
            </div>
            <div class="col">
                <div class="small text-muted">Convertidos</div>
                <div class="fs-5 fw-bold text-success"><?= $orcResumo['convertido']['qtd'] ?></div>
            </div>
            <div class="col">
                <div class="small text-muted">Recusados</div>
                <div class="fs-5 fw-bold text-danger"><?= $orcResumo['recusado']['qtd'] ?></div>
            </div>
            <div class="col">
                <div class="small text-muted">Expirados</div>
                <div class="fs-5 fw-bold text-secondary"><?= $orcResumo['expirado']['qtd'] ?></div>
            </div>
            <div class="col border-start">
                <div class="small text-muted">Taxa de Conversão</div>
                <div class="fs-4 fw-bold text-primary"><?= number_format($orcTaxaConv, 1, ',', '.') ?>%</div>
                <div class="small text-muted"><?= $orcResumo['convertido']['qtd'] ?> de <?= $orcTotalQtd ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header"><i class="fas fa-receipt me-2"></i>Últimas Vendas</div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Data/Hora</th><th>Vendedor</th><th>Total</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($ultimasVendas as $v): ?>
                <tr>
                    <td><?= $v['id'] ?></td>
                    <td><?= formatDateTime($v['criado_em']) ?></td>
                    <td><?= e($v['vendedor']) ?></td>
                    <td><?= formatMoney($v['total']) ?></td>
                    <td>
                        <span class="badge bg-<?= $v['status'] === 'concluida' ? 'success' : 'danger' ?>">
                            <?= $v['status'] === 'concluida' ? 'Concluída' : 'Cancelada' ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($ultimasVendas)): ?>
                <tr><td colspan="5" class="text-center text-muted">Nenhuma venda registrada</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('chartVendas').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($vendasSemana, 'label')) ?>,
        datasets: [{
            label: 'Vendas (R$)',
            data: <?= json_encode(array_column($vendasSemana, 'total')) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.7)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, ticks: { callback: v => 'R$ ' + v.toFixed(2) } } },
        plugins: { legend: { display: false } }
    }
});
</script>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
