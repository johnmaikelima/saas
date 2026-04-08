<?php
$pageTitle = 'Vendas';
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$user = usuario();
$tid = tenantId();

$dataInicio = sanitize($_GET['data_inicio'] ?? date('Y-m-d'));
$dataFim = sanitize($_GET['data_fim'] ?? date('Y-m-d'));
$status = sanitize($_GET['status'] ?? '');
$vendedor = (int)($_GET['vendedor'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ["v.tenant_id = ?", "DATE(v.criado_em) BETWEEN ? AND ?"];
$params = [$tid, $dataInicio, $dataFim];

if ($status) {
    $where[] = "v.status = ?";
    $params[] = $status;
}
if ($vendedor) {
    $where[] = "v.usuario_id = ?";
    $params[] = $vendedor;
}
if ($user['perfil'] === 'caixa') {
    $where[] = "v.usuario_id = ?";
    $params[] = $user['id'];
}

$whereStr = implode(' AND ', $where);

// Consulta manual com paginacao (nao usar paginate() pois ja temos tenant_id explicito)
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM vendas v WHERE {$whereStr}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$perPage = 20;
$totalPages = max(1, ceil($total / $perPage));
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT v.*, u.nome as vendedor, c.nome as cliente_nome
    FROM vendas v
    LEFT JOIN usuarios u ON u.id = v.usuario_id
    LEFT JOIN clientes c ON c.id = v.cliente_id
    WHERE {$whereStr}
    ORDER BY v.criado_em DESC
    LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$vendas = $stmt->fetchAll();

$pag = [
    'data' => $vendas,
    'total' => $total,
    'page' => $page,
    'perPage' => $perPage,
    'totalPages' => $totalPages,
];

// Vendedores do tenant
$stmtVend = $pdo->prepare("SELECT id, nome FROM usuarios WHERE tenant_id = ? AND ativo = 1 ORDER BY nome");
$stmtVend->execute([$tid]);
$vendedores = $stmtVend->fetchAll();

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="page-title mb-0"><i class="fas fa-receipt me-2"></i>Vendas</h5>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-0">De</label>
                <input type="date" class="form-control form-control-sm" name="data_inicio" value="<?= e($dataInicio) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Ate</label>
                <input type="date" class="form-control form-control-sm" name="data_fim" value="<?= e($dataFim) ?>">
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="status">
                    <option value="">Status</option>
                    <option value="concluida" <?= $status === 'concluida' ? 'selected' : '' ?>>Concluida</option>
                    <option value="cancelada" <?= $status === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                </select>
            </div>
            <?php if ($user['perfil'] !== 'caixa'): ?>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="vendedor">
                    <option value="">Vendedor</option>
                    <?php foreach ($vendedores as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= $vendedor == $v['id'] ? 'selected' : '' ?>><?= e($v['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Data/Hora</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th>Subtotal</th>
                    <th>Desconto</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pag['data'] as $v): ?>
                <tr>
                    <td><?= $v['id'] ?></td>
                    <td><?= formatDateTime($v['criado_em']) ?></td>
                    <td><?= e($v['cliente_nome'] ?: '-') ?></td>
                    <td><?= e($v['vendedor']) ?></td>
                    <td><?= formatMoney($v['subtotal']) ?></td>
                    <td><?= formatMoney($v['desconto_valor']) ?></td>
                    <td class="fw-bold"><?= formatMoney($v['total']) ?></td>
                    <td>
                        <span class="badge bg-<?= $v['status'] === 'concluida' ? 'success' : 'danger' ?>">
                            <?= ucfirst(e($v['status'])) ?>
                        </span>
                    </td>
                    <td>
                        <a href="view.php?id=<?= $v['id'] ?>" class="btn btn-outline-info btn-action" title="Detalhes"><i class="fas fa-eye"></i></a>
                        <?php if ($v['status'] === 'concluida'): ?>
                            <?php if (temPerfil('admin', 'gerente')): ?>
                                <a href="cancelar.php?id=<?= $v['id'] ?>" class="btn btn-outline-danger btn-action" title="Cancelar"><i class="fas fa-ban"></i></a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($pag['data'])): ?>
                <tr><td colspan="9" class="text-center text-muted py-3">Nenhuma venda encontrada</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= renderPagination($pag) ?></div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
