<?php
$pageTitle = 'Estoque';
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$tid = tenantId();
$busca = sanitize($_GET['busca'] ?? '');
$filtro = $_GET['filtro'] ?? '';

$where = ['p.tenant_id = ?', 'p.ativo = 1'];
$params = [$tid];

if ($busca) {
    $where[] = "(p.descricao LIKE ? OR p.codigo_barras LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}
if ($filtro === 'baixo') {
    $where[] = "p.estoque_atual <= p.estoque_minimo AND p.estoque_minimo > 0";
} elseif ($filtro === 'zerado') {
    $where[] = "p.estoque_atual <= 0";
}

$whereStr = implode(' AND ', $where);
$page = max(1, (int)($_GET['page'] ?? 1));

// Contagem
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM produtos p WHERE {$whereStr}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$perPage = 25;
$totalPages = max(1, ceil($total / $perPage));
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT p.*, c.nome as categoria_nome
    FROM produtos p
    LEFT JOIN categorias c ON c.id = p.categoria_id AND c.tenant_id = ?
    WHERE {$whereStr}
    ORDER BY p.descricao
    LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute(array_merge([$tid], $params));
$produtos = $stmt->fetchAll();

$pag = [
    'data' => $produtos,
    'total' => $total,
    'page' => $page,
    'perPage' => $perPage,
    'totalPages' => $totalPages,
];

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="page-title mb-0"><i class="fas fa-boxes-stacked me-2"></i>Estoque</h5>
    <a href="entrada.php" class="btn btn-primary btn-sm"><i class="fas fa-arrow-down me-1"></i>Entrada de Estoque</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm" name="busca" placeholder="Buscar produto..." value="<?= e($busca) ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="filtro">
                    <option value="">Todos</option>
                    <option value="baixo" <?= $filtro === 'baixo' ? 'selected' : '' ?>>Estoque baixo</option>
                    <option value="zerado" <?= $filtro === 'zerado' ? 'selected' : '' ?>>Estoque zerado</option>
                </select>
            </div>
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
                    <th>Cod</th>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Unidade</th>
                    <th>Estoque Atual</th>
                    <th>Estoque Min.</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pag['data'] as $p): ?>
                <?php
                $status = 'OK';
                $badge = 'success';
                if ($p['estoque_minimo'] > 0 && $p['estoque_atual'] <= $p['estoque_minimo']) {
                    $status = 'Baixo';
                    $badge = 'warning';
                }
                if ($p['estoque_atual'] <= 0) {
                    $status = 'Zerado';
                    $badge = 'danger';
                }
                ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= e($p['descricao']) ?></td>
                    <td><?= e($p['categoria_nome'] ?? '-') ?></td>
                    <td><?= e($p['unidade']) ?></td>
                    <td class="fw-bold"><?= $p['estoque_atual'] ?></td>
                    <td><?= $p['estoque_minimo'] ?></td>
                    <td><span class="badge bg-<?= $badge ?>"><?= $status ?></span></td>
                    <td>
                        <a href="historico.php?produto_id=<?= $p['id'] ?>" class="btn btn-outline-info btn-action" title="Histórico">
                            <i class="fas fa-history"></i>
                        </a>
                        <a href="entrada.php?produto_id=<?= $p['id'] ?>" class="btn btn-outline-success btn-action" title="Entrada">
                            <i class="fas fa-plus"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($pag['data'])): ?>
                <tr><td colspan="8" class="text-center text-muted py-3">Nenhum produto encontrado</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= renderPagination($pag) ?></div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
