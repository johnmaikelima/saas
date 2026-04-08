<?php
$pageTitle = 'Ranking de Produtos';
require_once __DIR__ . '/../../app/includes/auth.php';
if (!temPerfil('admin', 'gerente')) { flashError('Sem permissão.'); redirect('dashboard/'); }

$pdo = db();
$tid = tenantId();
$dataInicio = sanitize($_GET['data_inicio'] ?? date('Y-m-01'));
$dataFim = sanitize($_GET['data_fim'] ?? date('Y-m-d'));

$stmt = $pdo->prepare("SELECT vi.produto_id, vi.descricao, SUM(vi.quantidade) as qtd_vendida, SUM(vi.subtotal) as total_vendido
    FROM venda_itens vi INNER JOIN vendas v ON v.id = vi.venda_id
    WHERE v.tenant_id = ? AND v.status = 'concluida' AND DATE(v.criado_em) BETWEEN ? AND ?
    GROUP BY vi.produto_id, vi.descricao ORDER BY total_vendido DESC LIMIT 50");
$stmt->execute([$tid, $dataInicio, $dataFim]);
$dados = $stmt->fetchAll();

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-trophy me-2"></i>Ranking de Produtos</h5>

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
            <thead class="table-light"><tr><th>#</th><th>Produto</th><th>Qtd. Vendida</th><th>Total Vendido</th></tr></thead>
            <tbody>
            <?php foreach ($dados as $i => $d): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($d['descricao']) ?></td>
                    <td><?= number_format($d['qtd_vendida'], 2) ?></td>
                    <td><?= formatMoney($d['total_vendido']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
