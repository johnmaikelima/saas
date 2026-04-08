<?php
$pageTitle = 'Clientes';
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$tid = tenantId();

// Filtros
$busca = sanitize($_GET['busca'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

// Montar query
$where = "tenant_id = ?";
$params = [$tid];

if ($busca !== '') {
    $where .= " AND (nome LIKE ? OR cpf_cnpj LIKE ? OR telefone LIKE ? OR celular LIKE ? OR email LIKE ?)";
    $buscaLike = "%{$busca}%";
    $params[] = $buscaLike;
    $params[] = $buscaLike;
    $params[] = $buscaLike;
    $params[] = $buscaLike;
    $params[] = $buscaLike;
}

// Contagem
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE {$where}");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();

$perPage = 20;
$totalPages = max(1, ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT * FROM clientes WHERE {$where} ORDER BY nome ASC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$clientes = $stmt->fetchAll();

$pag = [
    'data' => $clientes,
    'total' => $total,
    'page' => $page,
    'perPage' => $perPage,
    'totalPages' => $totalPages,
];

$queryParams = $_GET;
unset($queryParams['page']);
$baseUrl = '?' . http_build_query($queryParams);

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-users me-2"></i>Clientes</h4>
    <a href="<?= baseUrl('clientes/form.php') ?>" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Novo Cliente
    </a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label mb-0 small">Buscar</label>
                <input type="text" name="busca" class="form-control form-control-sm" placeholder="Nome, CPF/CNPJ, telefone ou email..." value="<?= e($busca) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-search me-1"></i>Buscar</button>
            </div>
            <?php if ($busca): ?>
            <div class="col-md-2">
                <a href="<?= baseUrl('clientes/') ?>" class="btn btn-sm btn-outline-secondary w-100"><i class="fas fa-times me-1"></i>Limpar</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nome</th>
                    <th>CPF/CNPJ</th>
                    <th>Telefone</th>
                    <th>Email</th>
                    <th>Cidade/UF</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($clientes as $cli): ?>
                <tr>
                    <td><?= e($cli['nome']) ?></td>
                    <td><?= !empty($cli['cpf_cnpj']) ? e(formatDoc($cli['cpf_cnpj'])) : '<span class="text-muted">-</span>' ?></td>
                    <td><?= e($cli['telefone'] ?: $cli['celular'] ?: '-') ?></td>
                    <td><?= e($cli['email'] ?: '-') ?></td>
                    <td>
                        <?php if (!empty($cli['cidade'])): ?>
                            <?= e($cli['cidade']) ?><?= !empty($cli['estado']) ? '/' . e($cli['estado']) : '' ?>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <a href="<?= baseUrl('clientes/historico.php?id=' . $cli['id']) ?>" class="btn btn-sm btn-outline-info" title="Histórico">
                            <i class="fas fa-history"></i>
                        </a>
                        <a href="<?= baseUrl('clientes/form.php?id=' . $cli['id']) ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($clientes)): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">Nenhum cliente encontrado</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pag['totalPages'] > 1): ?>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted"><?= $total ?> cliente(s) encontrado(s)</small>
            <?= renderPagination($pag, $baseUrl) ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
