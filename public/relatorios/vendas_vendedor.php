<?php
$pageTitle = 'Vendas por Vendedor';
require_once __DIR__ . '/../../app/includes/auth.php';
if (!temPerfil('admin', 'gerente')) { flashError('Sem permissão.'); redirect('dashboard/'); }

$pdo = db();
$tid = tenantId();
$dataInicio = sanitize($_GET['data_inicio'] ?? date('Y-m-01'));
$dataFim = sanitize($_GET['data_fim'] ?? date('Y-m-d'));

$stmt = $pdo->prepare("SELECT u.nome, COUNT(v.id) as qtd, SUM(v.total) as total
    FROM vendas v INNER JOIN usuarios u ON u.id = v.usuario_id
    WHERE v.tenant_id = ? AND v.status = 'concluida' AND DATE(v.criado_em) BETWEEN ? AND ?
    GROUP BY u.id, u.nome ORDER BY total DESC");
$stmt->execute([$tid, $dataInicio, $dataFim]);
$dados = $stmt->fetchAll();

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-user-tie me-2"></i>Vendas por Vendedor</h5>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small mb-0">De</label><input type="date" class="form-control form-control-sm" name="data_inicio" value="<?= e($dataInicio) ?>"></div>
            <div class="col-md-3"><label class="form-label small mb-0">Até</label><input type="date" class="form-control form-control-sm" name="data_fim" value="<?= e($dataFim) ?>"></div>
            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light"><tr><th>#</th><th>Vendedor</th><th>Qtd. Vendas</th><th>Total</th><th>Ticket Médio</th></tr></thead>
            <tbody>
            <?php foreach ($dados as $i => $d): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($d['nome']) ?></td>
                    <td><?= $d['qtd'] ?></td>
                    <td><?= formatMoney($d['total']) ?></td>
                    <td><?= formatMoney($d['total'] / $d['qtd']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
