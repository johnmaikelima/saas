<?php
$pageTitle = 'Vendas por Categoria';
require_once __DIR__ . '/../../app/includes/auth.php';
if (!temPerfil('admin', 'gerente')) { flashError('Sem permissão.'); redirect('dashboard/'); }

$pdo = db();
$tid = tenantId();
$dataInicio = sanitize($_GET['data_inicio'] ?? date('Y-m-01'));
$dataFim = sanitize($_GET['data_fim'] ?? date('Y-m-d'));

$stmt = $pdo->prepare("SELECT COALESCE(c.nome, 'Sem Categoria') as categoria, SUM(vi.subtotal) as total, COUNT(DISTINCT v.id) as qtd_vendas
    FROM venda_itens vi
    INNER JOIN vendas v ON v.id = vi.venda_id
    LEFT JOIN produtos p ON p.id = vi.produto_id
    LEFT JOIN categorias c ON c.id = p.categoria_id
    WHERE v.tenant_id = ? AND v.status = 'concluida' AND DATE(v.criado_em) BETWEEN ? AND ?
    GROUP BY c.nome ORDER BY total DESC");
$stmt->execute([$tid, $dataInicio, $dataFim]);
$dados = $stmt->fetchAll();

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-chart-pie me-2"></i>Vendas por Categoria</h5>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small mb-0">De</label><input type="date" class="form-control form-control-sm" name="data_inicio" value="<?= e($dataInicio) ?>"></div>
            <div class="col-md-3"><label class="form-label small mb-0">Até</label><input type="date" class="form-control form-control-sm" name="data_fim" value="<?= e($dataFim) ?>"></div>
            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button></div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm"><div class="card-body"><canvas id="chart" height="300"></canvas></div></div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Categoria</th><th>Vendas</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($dados as $d): ?>
                        <tr><td><?= e($d['categoria']) ?></td><td><?= $d['qtd_vendas'] ?></td><td><?= formatMoney($d['total']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($dados, 'categoria')) ?>,
        datasets: [{ data: <?= json_encode(array_map('floatval', array_column($dados, 'total'))) ?>,
            backgroundColor: ['#3b82f6','#22c55e','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#ec4899'] }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
