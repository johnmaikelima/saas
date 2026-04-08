<?php
$pageTitle = 'Categorias';
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$tid = tenantId();

// Categorias com contagem de produtos
$stmt = $pdo->prepare("
    SELECT c.*,
           (SELECT COUNT(*) FROM produtos p WHERE p.categoria_id = c.id AND p.tenant_id = ?) as qtd_produtos
    FROM categorias c
    WHERE c.tenant_id = ?
    ORDER BY c.nome
");
$stmt->execute([$tid, $tid]);
$categorias = $stmt->fetchAll();

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-tags me-2"></i>Categorias</h4>
    <a href="<?= baseUrl('categorias/form.php') ?>" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nova Categoria
    </a>
</div>

<div class="card shadow">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Cód</th>
                    <th>Cor</th>
                    <th>Nome</th>
                    <th>Ícone</th>
                    <th class="text-center">Produtos</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($categorias as $cat): ?>
                <tr>
                    <td><?= $cat['id'] ?></td>
                    <td>
                        <span class="d-inline-block rounded-circle" style="width:20px;height:20px;background-color:<?= e($cat['cor'] ?: '#6c757d') ?>"></span>
                    </td>
                    <td>
                        <span class="badge" style="background-color: <?= e($cat['cor'] ?: '#6c757d') ?>"><?= e($cat['nome']) ?></span>
                    </td>
                    <td>
                        <?php if (!empty($cat['icone'])): ?>
                            <i class="<?= e($cat['icone']) ?>"></i> <small class="text-muted"><?= e($cat['icone']) ?></small>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-secondary"><?= (int)$cat['qtd_produtos'] ?></span>
                    </td>
                    <td class="text-center">
                        <?php if ($cat['ativo']): ?>
                            <span class="badge bg-success">Ativa</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inativa</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <a href="<?= baseUrl('categorias/form.php?id=' . $cat['id']) ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($categorias)): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">Nenhuma categoria cadastrada</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
