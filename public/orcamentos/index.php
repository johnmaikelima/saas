<?php
$pageTitle = 'Orçamentos';
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$user = usuario();
$tid = tenantId();

// Expirar orcamentos vencidos (batch update na abertura da lista)
$pdo->prepare("UPDATE orcamentos SET status = 'expirado'
    WHERE tenant_id = ? AND status = 'pendente' AND validade < CURDATE()")->execute([$tid]);

$dataInicio = sanitize($_GET['data_inicio'] ?? date('Y-m-01'));
$dataFim = sanitize($_GET['data_fim'] ?? date('Y-m-d'));
$status = sanitize($_GET['status'] ?? '');
$clienteId = (int)($_GET['cliente_id'] ?? 0);
$vendedor = (int)($_GET['vendedor'] ?? 0);
$numero = sanitize($_GET['numero'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ["o.tenant_id = ?"];
$params = [$tid];

if ($numero !== '') {
    $where[] = "o.numero = ?";
    $params[] = (int)$numero;
} else {
    $where[] = "o.data_orcamento BETWEEN ? AND ?";
    $params[] = $dataInicio;
    $params[] = $dataFim;
}

if ($status) {
    $where[] = "o.status = ?";
    $params[] = $status;
}
if ($clienteId) {
    $where[] = "o.cliente_id = ?";
    $params[] = $clienteId;
}
if ($vendedor) {
    $where[] = "o.usuario_id = ?";
    $params[] = $vendedor;
}
if ($user['perfil'] === 'caixa') {
    $where[] = "o.usuario_id = ?";
    $params[] = $user['id'];
}

$whereStr = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orcamentos o WHERE {$whereStr}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$perPage = 20;
$totalPages = max(1, ceil($total / $perPage));
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT o.*, u.nome as vendedor_nome, c.nome as cliente_nome
    FROM orcamentos o
    LEFT JOIN usuarios u ON u.id = o.usuario_id
    LEFT JOIN clientes c ON c.id = o.cliente_id
    WHERE {$whereStr}
    ORDER BY o.criado_em DESC
    LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$orcamentos = $stmt->fetchAll();

// Resumo
$stmtResumo = $pdo->prepare("SELECT status, COUNT(*) qtd, COALESCE(SUM(total),0) total
    FROM orcamentos WHERE tenant_id = ? AND data_orcamento BETWEEN ? AND ?
    GROUP BY status");
$stmtResumo->execute([$tid, $dataInicio, $dataFim]);
$resumoRaw = $stmtResumo->fetchAll();
$resumo = ['pendente' => ['qtd' => 0, 'total' => 0], 'aprovado' => ['qtd' => 0, 'total' => 0],
           'convertido' => ['qtd' => 0, 'total' => 0], 'recusado' => ['qtd' => 0, 'total' => 0],
           'expirado' => ['qtd' => 0, 'total' => 0]];
foreach ($resumoRaw as $r) {
    if (isset($resumo[$r['status']])) {
        $resumo[$r['status']] = ['qtd' => (int)$r['qtd'], 'total' => (float)$r['total']];
    }
}
$totalPeriodo = array_sum(array_column($resumo, 'qtd'));
$convertidos = $resumo['convertido']['qtd'];
$taxaConv = $totalPeriodo > 0 ? ($convertidos / $totalPeriodo * 100) : 0;

$pag = [
    'data' => $orcamentos, 'total' => $total, 'page' => $page,
    'perPage' => $perPage, 'totalPages' => $totalPages,
];

// Vendedores e clientes
$stmtVend = $pdo->prepare("SELECT id, nome FROM usuarios WHERE tenant_id = ? AND ativo = 1 ORDER BY nome");
$stmtVend->execute([$tid]);
$vendedores = $stmtVend->fetchAll();

$stmtCli = $pdo->prepare("SELECT id, nome FROM clientes WHERE tenant_id = ? ORDER BY nome LIMIT 500");
$stmtCli->execute([$tid]);
$clientes = $stmtCli->fetchAll();

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="page-title mb-0"><i class="fas fa-file-signature me-2"></i>Orçamentos</h5>
    <a href="<?= baseUrl('orcamentos/form.php') ?>" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i>Novo Orçamento
    </a>
</div>

<div class="row g-2 mb-3">
    <div class="col-md">
        <div class="card shadow-sm h-100">
            <div class="card-body py-2">
                <div class="small text-muted">Pendentes</div>
                <div class="fs-5 fw-bold text-warning"><?= $resumo['pendente']['qtd'] ?></div>
                <div class="small"><?= formatMoney($resumo['pendente']['total']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md">
        <div class="card shadow-sm h-100">
            <div class="card-body py-2">
                <div class="small text-muted">Aprovados</div>
                <div class="fs-5 fw-bold text-info"><?= $resumo['aprovado']['qtd'] ?></div>
                <div class="small"><?= formatMoney($resumo['aprovado']['total']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md">
        <div class="card shadow-sm h-100">
            <div class="card-body py-2">
                <div class="small text-muted">Convertidos</div>
                <div class="fs-5 fw-bold text-success"><?= $resumo['convertido']['qtd'] ?></div>
                <div class="small"><?= formatMoney($resumo['convertido']['total']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md">
        <div class="card shadow-sm h-100">
            <div class="card-body py-2">
                <div class="small text-muted">Recusados</div>
                <div class="fs-5 fw-bold text-danger"><?= $resumo['recusado']['qtd'] ?></div>
                <div class="small"><?= formatMoney($resumo['recusado']['total']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md">
        <div class="card shadow-sm h-100">
            <div class="card-body py-2">
                <div class="small text-muted">Expirados</div>
                <div class="fs-5 fw-bold text-secondary"><?= $resumo['expirado']['qtd'] ?></div>
                <div class="small"><?= formatMoney($resumo['expirado']['total']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md">
        <div class="card shadow-sm h-100 border-primary">
            <div class="card-body py-2">
                <div class="small text-muted">Taxa de Conversão</div>
                <div class="fs-5 fw-bold text-primary"><?= number_format($taxaConv, 1, ',', '.') ?>%</div>
                <div class="small text-muted"><?= $convertidos ?> / <?= $totalPeriodo ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-0">De</label>
                <input type="date" class="form-control form-control-sm" name="data_inicio" value="<?= e($dataInicio) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Até</label>
                <input type="date" class="form-control form-control-sm" name="data_fim" value="<?= e($dataFim) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Status</label>
                <select class="form-select form-select-sm" name="status">
                    <option value="">Todos</option>
                    <option value="pendente" <?= $status === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                    <option value="aprovado" <?= $status === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                    <option value="convertido" <?= $status === 'convertido' ? 'selected' : '' ?>>Convertido</option>
                    <option value="recusado" <?= $status === 'recusado' ? 'selected' : '' ?>>Recusado</option>
                    <option value="expirado" <?= $status === 'expirado' ? 'selected' : '' ?>>Expirado</option>
                    <option value="cancelado" <?= $status === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Cliente</label>
                <select class="form-select form-select-sm" name="cliente_id">
                    <option value="">Todos</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $clienteId == $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($user['perfil'] !== 'caixa'): ?>
            <div class="col-md-2">
                <label class="form-label small mb-0">Vendedor</label>
                <select class="form-select form-select-sm" name="vendedor">
                    <option value="">Todos</option>
                    <?php foreach ($vendedores as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= $vendedor == $v['id'] ? 'selected' : '' ?>><?= e($v['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-1">
                <label class="form-label small mb-0">Nº</label>
                <input type="number" class="form-control form-control-sm" name="numero" value="<?= e($numero) ?>" placeholder="#">
            </div>
            <div class="col-md-1">
                <button class="btn btn-sm btn-primary w-100"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Nº</th>
                    <th>Data</th>
                    <th>Validade</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pag['data'] as $o):
                $statusColors = [
                    'pendente' => 'warning', 'aprovado' => 'info', 'convertido' => 'success',
                    'recusado' => 'danger', 'expirado' => 'secondary', 'cancelado' => 'dark'
                ];
                $cor = $statusColors[$o['status']] ?? 'secondary';
                $hoje = date('Y-m-d');
                $vencido = ($o['status'] === 'pendente' && $o['validade'] < $hoje);
            ?>
                <tr>
                    <td class="fw-bold">#<?= str_pad($o['numero'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td><?= formatDate($o['data_orcamento']) ?></td>
                    <td>
                        <?= formatDate($o['validade']) ?>
                        <?php if ($vencido): ?>
                            <i class="fas fa-exclamation-triangle text-danger ms-1" title="Vencido"></i>
                        <?php endif; ?>
                    </td>
                    <td><?= e($o['cliente_nome'] ?: '-') ?></td>
                    <td><?= e($o['vendedor_nome'] ?: '-') ?></td>
                    <td class="text-end fw-bold"><?= formatMoney($o['total']) ?></td>
                    <td>
                        <span class="badge bg-<?= $cor ?>"><?= ucfirst(e($o['status'])) ?></span>
                    </td>
                    <td class="text-end">
                        <a href="view.php?id=<?= $o['id'] ?>" class="btn btn-outline-info btn-action" title="Ver"><i class="fas fa-eye"></i></a>
                        <?php if (!in_array($o['status'], ['convertido'])): ?>
                            <a href="form.php?id=<?= $o['id'] ?>" class="btn btn-outline-primary btn-action" title="Editar"><i class="fas fa-edit"></i></a>
                        <?php endif; ?>
                        <a href="pdf.php?id=<?= $o['id'] ?>" class="btn btn-outline-secondary btn-action" title="PDF" target="_blank"><i class="fas fa-file-pdf"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($pag['data'])): ?>
                <tr><td colspan="8" class="text-center text-muted py-3">Nenhum orçamento encontrado</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= renderPagination($pag) ?></div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
