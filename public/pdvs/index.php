<?php
$pageTitle = 'PDVs / Terminais';
require_once __DIR__ . '/../../app/includes/auth.php';

if (!temPerfil('admin', 'gerente')) {
    flashError('Sem permissão para acessar PDVs.');
    redirect('dashboard/');
}

$pdo = db();
$tid = tenantId();

$stmt = $pdo->prepare("
    SELECT p.*,
           (SELECT COUNT(*) FROM caixas c WHERE c.pdv_id = p.id AND c.status = 'aberto') as caixa_aberto,
           (SELECT u.nome FROM caixas c JOIN usuarios u ON u.id = c.usuario_id WHERE c.pdv_id = p.id AND c.status = 'aberto' LIMIT 1) as operador_atual
    FROM pdvs p
    WHERE p.tenant_id = ?
    ORDER BY p.nome
");
$stmt->execute([$tid]);
$pdvs = $stmt->fetchAll();

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-cash-register me-2"></i>PDVs / Terminais</h4>
    <a href="<?= baseUrl('pdvs/form.php') ?>" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Novo PDV
    </a>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle me-1"></i>
    Cadastre os PDVs/Terminais físicos do seu estabelecimento. Ao abrir o caixa, o operador escolhe qual terminal vai usar.
</div>

<div class="card shadow">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Cód</th>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Em uso</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pdvs as $pdv): ?>
                <tr>
                    <td><?= $pdv['id'] ?></td>
                    <td>
                        <i class="fas fa-cash-register me-1 text-primary"></i>
                        <strong><?= e($pdv['nome']) ?></strong>
                    </td>
                    <td class="text-muted"><?= e($pdv['descricao'] ?: '-') ?></td>
                    <td class="text-center">
                        <?php if ($pdv['ativo']): ?>
                            <span class="badge bg-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($pdv['caixa_aberto']): ?>
                            <span class="badge bg-warning text-dark" title="Operador: <?= e($pdv['operador_atual']) ?>">
                                <i class="fas fa-user me-1"></i><?= e($pdv['operador_atual']) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark">Livre</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <a href="<?= baseUrl('pdvs/form.php?id=' . $pdv['id']) ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($pdvs)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">
                    Nenhum PDV cadastrado. <a href="<?= baseUrl('pdvs/form.php') ?>">Cadastrar o primeiro</a>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
