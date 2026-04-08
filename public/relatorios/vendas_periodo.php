<?php
$pageTitle = 'Vendas por Período';
require_once __DIR__ . '/../../app/includes/auth.php';
if (!temPerfil('admin', 'gerente')) { flashError('Sem permissão.'); redirect('dashboard/'); }

$pdo = db();
$tid = tenantId();

$dataInicio = sanitize($_GET['data_inicio'] ?? date('Y-m-01'));
$dataFim = sanitize($_GET['data_fim'] ?? date('Y-m-d'));

$stmt = $pdo->prepare("SELECT DATE(criado_em) as dia, COUNT(*) as qtd, SUM(total) as total
    FROM vendas WHERE tenant_id = ? AND status = 'concluida' AND DATE(criado_em) BETWEEN ? AND ?
    GROUP BY DATE(criado_em) ORDER BY dia");
$stmt->execute([$tid, $dataInicio, $dataFim]);
$dados = $stmt->fetchAll();

$totalGeral = array_sum(array_column($dados, 'total'));
$qtdGeral = array_sum(array_column($dados, 'qtd'));

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-calendar-alt me-2"></i>Vendas por Período</h5>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-0">De</label>
                <input type="date" class="form-control form-control-sm" name="data_inicio" value="<?= e($dataInicio) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-0">Até</label>
                <input type="date" class="form-control form-control-sm" name="data_fim" value="<?= e($dataFim) ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card bg-primary text-white"><div class="card-body text-center"><small>Total Vendido</small><h4><?= formatMoney($totalGeral) ?></h4></div></div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white"><div class="card-body text-center"><small>Qtd. Vendas</small><h4><?= $qtdGeral ?></h4></div></div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white"><div class="card-body text-center"><small>Ticket Médio</small><h4><?= $qtdGeral > 0 ? formatMoney($totalGeral / $qtdGeral) : 'R$ 0,00' ?></h4></div></div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <canvas id="chart" height="200"></canvas>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Data</th><th>Qtd. Vendas</th><th>Total</th><th>Ticket Médio</th></tr></thead>
            <tbody>
            <?php foreach ($dados as $d): ?>
                <tr>
                    <td><?= formatDate($d['dia']) ?></td>
                    <td><?= $d['qtd'] ?></td>
                    <td><?= formatMoney($d['total']) ?></td>
                    <td><?= formatMoney($d['total'] / $d['qtd']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($d) => date('d/m', strtotime($d['dia'])), $dados)) ?>,
        datasets: [{
            label: 'Vendas (R$)',
            data: <?= json_encode(array_column($dados, 'total')) ?>,
            borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', fill: true, tension: 0.3
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
});
</script>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
