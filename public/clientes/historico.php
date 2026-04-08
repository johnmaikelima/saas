<?php
$pageTitle = 'Histórico do Cliente';
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$tid = tenantId();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flashError('Cliente inválido.');
    redirect('clientes/');
}

$cliente = tenantFind('clientes', $id);
if (!$cliente) {
    flashError('Cliente não encontrado.');
    redirect('clientes/');
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

// Contar vendas do cliente
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM vendas WHERE tenant_id = ? AND cliente_id = ?");
$stmtCount->execute([$tid, $id]);
$total = (int)$stmtCount->fetchColumn();

$totalPages = max(1, ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Buscar vendas com vendedor
$stmt = $pdo->prepare("
    SELECT v.*, u.nome as vendedor
    FROM vendas v
    LEFT JOIN usuarios u ON u.id = v.usuario_id
    WHERE v.tenant_id = ? AND v.cliente_id = ?
    ORDER BY v.criado_em DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute([$tid, $id]);
$vendas = $stmt->fetchAll();

$pag = [
    'data' => $vendas,
    'total' => $total,
    'page' => $page,
    'perPage' => $perPage,
    'totalPages' => $totalPages,
];

// Totais do cliente
$stmtTotais = $pdo->prepare("SELECT COUNT(*) as qtd, COALESCE(SUM(total),0) as total FROM vendas WHERE tenant_id = ? AND cliente_id = ? AND status = 'concluida'");
$stmtTotais->execute([$tid, $id]);
$totais = $stmtTotais->fetch();

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="fas fa-history me-2"></i>Histórico - <?= e($cliente['nome']) ?>
    </h4>
    <a href="<?= baseUrl('clientes/') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card bg-primary text-white shadow-sm">
            <div class="card-body py-2">
                <small>Total em Compras</small>
                <h4 class="mb-0"><?= formatMoney((float)$totais['total']) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white shadow-sm">
            <div class="card-body py-2">
                <small>Qtd. de Compras</small>
                <h4 class="mb-0"><?= (int)$totais['qtd'] ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white shadow-sm">
            <div class="card-body py-2">
                <small>Ticket Médio</small>
                <h4 class="mb-0"><?= $totais['qtd'] > 0 ? formatMoney($totais['total'] / $totais['qtd']) : formatMoney(0) ?></h4>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-3">
    <div class="card-header">
        <i class="fas fa-id-card me-2"></i>Dados do Cliente
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4"><strong>CPF/CNPJ:</strong> <?= !empty($cliente['cpf_cnpj']) ? e(formatDoc($cliente['cpf_cnpj'])) : '-' ?></div>
            <div class="col-md-4"><strong>Telefone:</strong> <?= e($cliente['telefone'] ?: $cliente['celular'] ?: '-') ?></div>
            <div class="col-md-4"><strong>Email:</strong> <?= e($cliente['email'] ?: '-') ?></div>
        </div>
    </div>
</div>

<div class="card shadow">
    <div class="card-header"><i class="fas fa-receipt me-2"></i>Vendas</div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Data/Hora</th>
                    <th>Vendedor</th>
                    <th class="text-end">Total</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($vendas as $v): ?>
                <tr>
                    <td><?= $v['id'] ?></td>
                    <td><?= formatDateTime($v['criado_em']) ?></td>
                    <td><?= e($v['vendedor'] ?? '-') ?></td>
                    <td class="text-end"><?= formatMoney((float)$v['total']) ?></td>
                    <td class="text-center">
                        <?php if ($v['status'] === 'concluida'): ?>
                            <span class="badge bg-success">Concluída</span>
                        <?php elseif ($v['status'] === 'cancelada'): ?>
                            <span class="badge bg-danger">Cancelada</span>
                        <?php else: ?>
                            <span class="badge bg-warning"><?= e(ucfirst($v['status'])) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($vendas)): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">Nenhuma venda registrada para este cliente</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pag['totalPages'] > 1): ?>
    <div class="card-footer">
        <?= renderPagination($pag, '?id=' . $id) ?>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
