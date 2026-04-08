<?php
$pageTitle = 'Histórico de Estoque';
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$tid = tenantId();
$produtoId = (int)($_GET['produto_id'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));

$produto = null;
$where = ['em.tenant_id = ?'];
$params = [$tid];

if ($produtoId) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$produtoId, $tid]);
    $produto = $stmt->fetch();
    if ($produto) {
        $where[] = "em.produto_id = ?";
        $params[] = $produtoId;
    }
}

$whereStr = implode(' AND ', $where);

// Contagem
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM estoque_movimentacoes em WHERE {$whereStr}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$perPage = 30;
$totalPages = max(1, ceil($total / $perPage));
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT em.*, p.descricao as produto_nome, u.nome as usuario_nome
    FROM estoque_movimentacoes em
    LEFT JOIN produtos p ON p.id = em.produto_id
    LEFT JOIN usuarios u ON u.id = em.usuario_id
    WHERE {$whereStr}
    ORDER BY em.criado_em DESC
    LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$movimentacoes = $stmt->fetchAll();

$pag = [
    'data' => $movimentacoes,
    'total' => $total,
    'page' => $page,
    'perPage' => $perPage,
    'totalPages' => $totalPages,
];

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title">
    <i class="fas fa-history me-2"></i>Histórico de Movimentações
    <?php if ($produto): ?>
        - <?= e($produto['descricao']) ?> (Estoque: <?= $produto['estoque_atual'] ?>)
    <?php endif; ?>
</h5>

<?php if (!$produtoId): ?>
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-0">Filtrar por produto</label>
                <select class="form-select form-select-sm" name="produto_id">
                    <option value="">Todos os produtos</option>
                    <?php
                    $stmtProds = $pdo->prepare("SELECT id, descricao FROM produtos WHERE tenant_id = ? AND ativo = 1 ORDER BY descricao");
                    $stmtProds->execute([$tid]);
                    foreach ($stmtProds->fetchAll() as $pr): ?>
                        <option value="<?= $pr['id'] ?>"><?= e($pr['descricao']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th>Qtd</th>
                    <th>Motivo</th>
                    <th>Ref.</th>
                    <th>Usuário</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pag['data'] as $m): ?>
                <tr>
                    <td><?= formatDateTime($m['criado_em']) ?></td>
                    <td><?= e($m['produto_nome']) ?></td>
                    <td>
                        <?php
                        $badge = match($m['tipo']) { 'entrada'=>'success', 'saida'=>'danger', default=>'info' };
                        $icon = match($m['tipo']) { 'entrada'=>'fa-arrow-down', 'saida'=>'fa-arrow-up', default=>'fa-sync' };
                        ?>
                        <span class="badge bg-<?= $badge ?>"><i class="fas <?= $icon ?> me-1"></i><?= ucfirst(e($m['tipo'])) ?></span>
                    </td>
                    <td class="fw-bold"><?= $m['quantidade'] ?></td>
                    <td><?= e($m['motivo']) ?></td>
                    <td><small><?= e($m['referencia_tipo']) ?></small></td>
                    <td><?= e($m['usuario_nome']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($pag['data'])): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">Nenhuma movimentação encontrada</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">
    <?= renderPagination($pag, '?' . http_build_query(array_filter(['produto_id' => $produtoId]))) ?>
    <a href="<?= baseUrl('estoque/') ?>" class="btn btn-secondary btn-sm">Voltar</a>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
