<?php
$pageTitle = 'Usuários';
require_once __DIR__ . '/../../app/includes/auth.php';

if (!temPerfil('admin')) {
    flashError('Sem permissão.');
    redirect('dashboard/');
}

$pdo = db();
$tid = tenantId();

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE tenant_id = ? ORDER BY nome ASC");
$stmt->execute([$tid]);
$usuarios = $stmt->fetchAll();

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-user-shield me-2"></i>Usuários</h4>
    <a href="<?= baseUrl('usuarios/form.php') ?>" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Novo Usuário
    </a>
</div>

<div class="card shadow">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nome</th>
                    <th>Login</th>
                    <th>Perfil</th>
                    <th class="text-center">Status</th>
                    <th>Último Acesso</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <?php
                    $perfilBadge = match($u['perfil']) {
                        'admin' => 'danger',
                        'gerente' => 'warning',
                        'caixa' => 'info',
                        default => 'secondary'
                    };
                ?>
                <tr>
                    <td><?= e($u['nome']) ?></td>
                    <td><code><?= e($u['login']) ?></code></td>
                    <td><span class="badge bg-<?= $perfilBadge ?>"><?= ucfirst(e($u['perfil'])) ?></span></td>
                    <td class="text-center">
                        <?php if ($u['ativo']): ?>
                            <span class="badge bg-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= !empty($u['ultimo_acesso']) ? formatDateTime($u['ultimo_acesso']) : '<span class="text-muted">Nunca</span>' ?>
                    </td>
                    <td class="text-center">
                        <a href="<?= baseUrl('usuarios/form.php?id=' . $u['id']) ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($usuarios)): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">Nenhum usuário cadastrado</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
