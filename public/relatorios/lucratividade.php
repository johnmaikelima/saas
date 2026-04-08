<?php
$pageTitle = 'Lucratividade';
require_once __DIR__ . '/../../app/includes/auth.php';
if (!temPerfil('admin', 'gerente')) { flashError('Sem permissão.'); redirect('dashboard/'); }

$pdo = db();
$tid = tenantId();
$dataInicio = sanitize($_GET['data_inicio'] ?? date('Y-m-01'));
$dataFim = sanitize($_GET['data_fim'] ?? date('Y-m-d'));

$stmt = $pdo->prepare("SELECT p.descricao, p.preco_custo, p.preco_venda, SUM(vi.quantidade) as qtd,
    SUM(vi.subtotal) as receita, SUM(vi.quantidade * p.preco_custo) as custo_total
    FROM venda_itens vi
    INNER JOIN vendas v ON v.id = vi.venda_id
    INNER JOIN produtos p ON p.id = vi.produto_id
    WHERE v.tenant_id = ? AND v.status = 'concluida' AND DATE(v.criado_em) BETWEEN ? AND ?
    GROUP BY p.id, p.descricao, p.preco_custo, p.preco_venda ORDER BY (SUM(vi.subtotal) - SUM(vi.quantidade * p.preco_custo)) DESC LIMIT 50");
$stmt->execute([$tid, $dataInicio, $dataFim]);
$dados = $stmt->fetchAll();

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-dollar-sign me-2"></i>Lucratividade</h5>

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
            <thead class="table-light"><tr><th>Produto</th><th>Custo Unit.</th><th>Venda Unit.</th><th>Qtd.</th><th>Receita</th><th>Custo Total</th><th>Lucro</th><th>Margem</th></tr></thead>
            <tbody>
            <?php foreach ($dados as $d):
                $lucro = $d['receita'] - $d['custo_total'];
                $margem = $d['custo_total'] > 0 ? ($lucro / $d['custo_total']) * 100 : 0;
            ?>
                <tr>
                    <td><?= e($d['descricao']) ?></td>
                    <td><?= formatMoney($d['preco_custo']) ?></td>
                    <td><?= formatMoney($d['preco_venda']) ?></td>
                    <td><?= number_format($d['qtd'], 2) ?></td>
                    <td><?= formatMoney($d['receita']) ?></td>
                    <td><?= formatMoney($d['custo_total']) ?></td>
                    <td class="<?= $lucro >= 0 ? 'text-success' : 'text-danger' ?> fw-bold"><?= formatMoney($lucro) ?></td>
                    <td><?= number_format($margem, 1) ?>%</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
