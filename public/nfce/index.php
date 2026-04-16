<?php
$pageTitle = 'NFC-e Emitidas';
require_once __DIR__ . '/../../app/includes/auth.php';
if (!temPerfil('admin', 'gerente')) { flashError('Sem permissão.'); redirect('dashboard/'); }

$pdo = db();
$tid = tenantId();

// Filtros
$filtroStatus = $_GET['status'] ?? '';
$filtroPeriodo = $_GET['periodo'] ?? 'mes';
$filtroBusca = sanitize($_GET['busca'] ?? '');

$where = "WHERE n.tenant_id = ?";
$params = [$tid];

if ($filtroStatus && in_array($filtroStatus, ['autorizada', 'cancelada', 'erro', 'pendente', 'inutilizada'])) {
    $where .= " AND n.status = ?";
    $params[] = $filtroStatus;
}

if ($filtroPeriodo === 'hoje') {
    $where .= " AND DATE(n.criado_em) = CURDATE()";
} elseif ($filtroPeriodo === 'semana') {
    $where .= " AND n.criado_em >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($filtroPeriodo === 'mes') {
    $where .= " AND n.criado_em >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
}

if (!empty($filtroBusca)) {
    $where .= " AND (n.chave_acesso LIKE ? OR n.numero LIKE ? OR v.id LIKE ?)";
    $busca = "%{$filtroBusca}%";
    $params[] = $busca;
    $params[] = $busca;
    $params[] = $busca;
}

// Contagem total
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM nfce n LEFT JOIN vendas v ON v.id = n.venda_id {$where}");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$totalPages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT n.*, v.total as venda_total, v.id as venda_num
    FROM nfce n
    LEFT JOIN vendas v ON v.id = n.venda_id
    {$where}
    ORDER BY n.criado_em DESC
    LIMIT ? OFFSET ?
");
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$notas = $stmt->fetchAll();

// Totais por status
$stmtTotais = $pdo->prepare("SELECT status, COUNT(*) as qtd FROM nfce WHERE tenant_id = ? AND criado_em >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY status");
$stmtTotais->execute([$tid]);
$totais = [];
while ($row = $stmtTotais->fetch()) {
    $totais[$row['status']] = $row['qtd'];
}

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="page-title mb-0"><i class="fas fa-file-invoice me-2"></i>NFC-e Emitidas</h5>
    <div class="d-flex gap-2">
        <a href="<?= baseUrl('nfce/inutilizar.php') ?>" class="btn btn-sm btn-outline-warning">
            <i class="fas fa-ban me-1"></i>Inutilizar
        </a>
        <a href="<?= baseUrl('configuracao/#fiscal') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-cog me-1"></i>Configuração
        </a>
    </div>
</div>

<!-- Cards resumo -->
<div class="row g-2 mb-3">
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-success border-3">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Autorizadas (30d)</small>
                <h5 class="mb-0 text-success"><?= $totais['autorizada'] ?? 0 ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-danger border-3">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Canceladas (30d)</small>
                <h5 class="mb-0 text-danger"><?= $totais['cancelada'] ?? 0 ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-warning border-3">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Erros (30d)</small>
                <h5 class="mb-0 text-warning"><?= $totais['erro'] ?? 0 ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-info border-3">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Total (30d)</small>
                <h5 class="mb-0"><?= array_sum($totais) ?></h5>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-auto">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Todos status</option>
                    <option value="autorizada" <?= $filtroStatus === 'autorizada' ? 'selected' : '' ?>>Autorizada</option>
                    <option value="cancelada" <?= $filtroStatus === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                    <option value="erro" <?= $filtroStatus === 'erro' ? 'selected' : '' ?>>Erro</option>
                    <option value="pendente" <?= $filtroStatus === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                </select>
            </div>
            <div class="col-auto">
                <select name="periodo" class="form-select form-select-sm">
                    <option value="hoje" <?= $filtroPeriodo === 'hoje' ? 'selected' : '' ?>>Hoje</option>
                    <option value="semana" <?= $filtroPeriodo === 'semana' ? 'selected' : '' ?>>Últimos 7 dias</option>
                    <option value="mes" <?= $filtroPeriodo === 'mes' ? 'selected' : '' ?>>Últimos 30 dias</option>
                    <option value="todos" <?= $filtroPeriodo === 'todos' ? 'selected' : '' ?>>Todos</option>
                </select>
            </div>
            <div class="col-auto">
                <input type="text" name="busca" class="form-control form-control-sm" placeholder="Chave, número ou venda..." value="<?= e($filtroBusca) ?>">
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i>Filtrar</button>
                <a href="?" class="btn btn-sm btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabela -->
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Série</th>
                    <th>Venda</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Ambiente</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($notas)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma NFC-e encontrada</td></tr>
                <?php else: ?>
                    <?php foreach ($notas as $n): ?>
                    <tr>
                        <td><strong><?= $n['numero'] ?></strong></td>
                        <td><?= $n['serie'] ?></td>
                        <td>
                            <?php if ($n['venda_num']): ?>
                                <a href="<?= baseUrl('vendas/view.php?id=' . $n['venda_num']) ?>">#<?= $n['venda_num'] ?></a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= $n['venda_total'] ? formatMoney((float)$n['venda_total']) : '-' ?></td>
                        <td>
                            <?php
                            $badgeClass = match($n['status']) {
                                'autorizada' => 'bg-success',
                                'cancelada' => 'bg-secondary',
                                'erro' => 'bg-danger',
                                'pendente' => 'bg-warning text-dark',
                                'inutilizada' => 'bg-dark',
                                default => 'bg-secondary',
                            };
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($n['status']) ?></span>
                            <?php if ($n['status'] === 'erro' && $n['mensagem_erro']): ?>
                                <i class="fas fa-info-circle text-danger ms-1" title="<?= e($n['mensagem_erro']) ?>" data-bs-toggle="tooltip"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $n['ambiente'] == 1 ? 'bg-success' : 'bg-warning text-dark' ?>">
                                <?= $n['ambiente'] == 1 ? 'Prod' : 'Homol' ?>
                            </span>
                        </td>
                        <td><small><?= formatDateTime($n['criado_em']) ?></small></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <?php if ($n['status'] === 'autorizada'): ?>
                                    <a href="<?= baseUrl('nfce/danfce.php?chave=' . e($n['chave_acesso'])) ?>"
                                       class="btn btn-outline-primary" title="DANFCE" target="_blank">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    <a href="<?= baseUrl('nfce/cancelar.php?id=' . $n['id']) ?>"
                                       class="btn btn-outline-danger" title="Cancelar">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($n['chave_acesso']): ?>
                                    <button class="btn btn-outline-secondary" title="Copiar chave"
                                            onclick="navigator.clipboard.writeText('<?= e($n['chave_acesso']) ?>'); this.innerHTML='<i class=\'fas fa-check\'></i>';">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&status=<?= e($filtroStatus) ?>&periodo=<?= e($filtroPeriodo) ?>&busca=<?= e($filtroBusca) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<script>
// Tooltips
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
