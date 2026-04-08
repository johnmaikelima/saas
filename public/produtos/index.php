<?php
$pageTitle = 'Produtos';
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$tid = tenantId();

// Filtros
$busca = sanitize($_GET['busca'] ?? '');
$categoria = (int)($_GET['categoria'] ?? 0);
$estoque = sanitize($_GET['estoque'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

// Categorias para dropdown
$stmtCat = $pdo->prepare("SELECT * FROM categorias WHERE tenant_id = ? AND ativo = 1 ORDER BY nome");
$stmtCat->execute([$tid]);
$categorias = $stmtCat->fetchAll();

// Montar query com filtros
$where = "p.tenant_id = ?";
$params = [$tid];

if ($busca !== '') {
    $where .= " AND (p.descricao LIKE ? OR p.codigo_barras LIKE ? OR CAST(p.id AS CHAR) = ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $params[] = $busca;
}

if ($categoria > 0) {
    $where .= " AND p.categoria_id = ?";
    $params[] = $categoria;
}

if ($estoque === 'baixo') {
    $where .= " AND p.estoque_atual <= p.estoque_minimo AND p.estoque_minimo > 0";
} elseif ($estoque === 'zerado') {
    $where .= " AND p.estoque_atual <= 0";
} elseif ($estoque === 'normal') {
    $where .= " AND (p.estoque_atual > p.estoque_minimo OR p.estoque_minimo = 0)";
}

// Contagem total
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM produtos p WHERE {$where}");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();

$perPage = 20;
$totalPages = max(1, ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Query principal com JOIN
$sql = "SELECT p.*, c.nome as categoria_nome, c.cor as categoria_cor
        FROM produtos p
        LEFT JOIN categorias c ON c.id = p.categoria_id
        WHERE {$where}
        ORDER BY p.descricao ASC
        LIMIT {$perPage} OFFSET {$offset}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll();

$pag = [
    'data' => $produtos,
    'total' => $total,
    'page' => $page,
    'perPage' => $perPage,
    'totalPages' => $totalPages,
];

// Montar URL base para paginação
$queryParams = $_GET;
unset($queryParams['page']);
$baseUrl = '?' . http_build_query($queryParams);

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-barcode me-2"></i>Produtos</h4>
    <a href="<?= baseUrl('produtos/form.php') ?>" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Novo Produto
    </a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-0 small">Buscar</label>
                <input type="text" name="busca" class="form-control form-control-sm" placeholder="Nome, código de barras ou ID..." value="<?= e($busca) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-0 small">Categoria</label>
                <select name="categoria" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoria === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-0 small">Estoque</label>
                <select name="estoque" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="normal" <?= $estoque === 'normal' ? 'selected' : '' ?>>Normal</option>
                    <option value="baixo" <?= $estoque === 'baixo' ? 'selected' : '' ?>>Baixo</option>
                    <option value="zerado" <?= $estoque === 'zerado' ? 'selected' : '' ?>>Zerado</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Cód</th>
                    <th>EAN</th>
                    <th>Descrição</th>
                    <th>Categoria</th>
                    <th class="text-end">Custo</th>
                    <th class="text-end">Venda</th>
                    <th class="text-end">Margem</th>
                    <th class="text-center">Estoque</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($produtos as $p): ?>
                <?php
                    $margem = $p['preco_custo'] > 0 ? (($p['preco_venda'] - $p['preco_custo']) / $p['preco_custo']) * 100 : 0;
                    $estoqueBaixo = $p['estoque_minimo'] > 0 && $p['estoque_atual'] <= $p['estoque_minimo'];
                ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><small class="text-muted"><?= e($p['codigo_barras']) ?></small></td>
                    <td><?= e($p['descricao']) ?></td>
                    <td>
                        <?php if ($p['categoria_nome']): ?>
                            <span class="badge" style="background-color: <?= e($p['categoria_cor'] ?: '#6c757d') ?>"><?= e($p['categoria_nome']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?= formatMoney((float)$p['preco_custo']) ?></td>
                    <td class="text-end"><?= formatMoney((float)$p['preco_venda']) ?></td>
                    <td class="text-end">
                        <span class="<?= $margem < 0 ? 'text-danger' : 'text-success' ?>">
                            <?= number_format($margem, 1, ',', '.') ?>%
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-<?= $estoqueBaixo ? 'danger' : 'secondary' ?>">
                            <?= (int)$p['estoque_atual'] ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <?php if ($p['ativo']): ?>
                            <span class="badge bg-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <a href="<?= baseUrl('produtos/form.php?id=' . $p['id']) ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="<?= baseUrl('produtos/delete.php?id=' . $p['id'] . '&csrf=' . csrfToken()) ?>" class="btn btn-sm btn-outline-danger" title="Excluir" onclick="return confirm('Excluir este produto?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($produtos)): ?>
                <tr><td colspan="10" class="text-center text-muted py-3">Nenhum produto encontrado</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pag['totalPages'] > 1): ?>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted"><?= $total ?> produto(s) encontrado(s)</small>
            <?= renderPagination($pag, $baseUrl) ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
