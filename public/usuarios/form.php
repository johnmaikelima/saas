<?php
$pageTitle = 'Usuário';
require_once __DIR__ . '/../../app/includes/auth.php';

if (!temPerfil('admin')) {
    flashError('Sem permissão.');
    redirect('dashboard/');
}

$pdo = db();
$tid = tenantId();
$id = (int)($_GET['id'] ?? 0);
$user = null;

if ($id > 0) {
    $user = tenantFind('usuarios', $id);
    if (!$user) {
        flashError('Usuário não encontrado.');
        redirect('usuarios/');
    }
    $pageTitle = 'Editar Usuário';
} else {
    $pageTitle = 'Novo Usuário';
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flashError('Token inválido.');
        redirect('usuarios/form.php' . ($id ? "?id={$id}" : ''));
    }

    $nome = sanitize($_POST['nome'] ?? '');
    $login = sanitize($_POST['login'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $perfil = sanitize($_POST['perfil'] ?? 'caixa');
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $senha = $_POST['senha'] ?? '';

    // Validações
    if (empty($nome) || empty($login)) {
        flashError('Nome e login são obrigatórios.');
        redirect('usuarios/form.php' . ($id ? "?id={$id}" : ''));
    }

    if (!in_array($perfil, ['admin', 'gerente', 'caixa'])) {
        flashError('Perfil inválido.');
        redirect('usuarios/form.php' . ($id ? "?id={$id}" : ''));
    }

    // Verificar login duplicado
    $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE tenant_id = ? AND login = ? AND id != ?");
    $stmtCheck->execute([$tid, $login, $id]);
    if ($stmtCheck->fetch()) {
        flashError('Já existe um usuário com este login.');
        redirect('usuarios/form.php' . ($id ? "?id={$id}" : ''));
    }

    $data = [
        'nome'   => $nome,
        'login'  => $login,
        'email'  => $email,
        'perfil' => $perfil,
        'ativo'  => $ativo,
    ];

    if ($id > 0) {
        // Atualizar senha apenas se preenchida
        if (!empty($senha)) {
            if (!validatePassword($senha)) {
                flashError('A senha deve ter no mínimo 8 caracteres, com letras e números.');
                redirect('usuarios/form.php?id=' . $id);
            }
            $data['senha'] = hashPassword($senha);
        }
        tenantUpdate('usuarios', $data, $id);
        flashSuccess('Usuário atualizado com sucesso!');
    } else {
        // Senha obrigatória na criação
        if (empty($senha)) {
            flashError('A senha é obrigatória para novos usuários.');
            redirect('usuarios/form.php');
        }
        if (!validatePassword($senha)) {
            flashError('A senha deve ter no mínimo 8 caracteres, com letras e números.');
            redirect('usuarios/form.php');
        }
        $data['senha'] = hashPassword($senha);
        tenantInsert('usuarios', $data);
        flashSuccess('Usuário cadastrado com sucesso!');
    }

    redirect('usuarios/');
}

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="fas fa-user-shield me-2"></i><?= $id ? 'Editar Usuário' : 'Novo Usuário' ?>
    </h4>
    <a href="<?= baseUrl('usuarios/') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <form method="POST">
            <?= csrfField() ?>

            <div class="card shadow mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome <span class="text-danger">*</span></label>
                            <input type="text" name="nome" class="form-control" value="<?= e($user['nome'] ?? '') ?>" required maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Login <span class="text-danger">*</span></label>
                            <input type="text" name="login" class="form-control" value="<?= e($user['login'] ?? '') ?>" required maxlength="100" autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Senha <?= $id ? '' : '<span class="text-danger">*</span>' ?></label>
                            <input type="password" name="senha" class="form-control" autocomplete="new-password" <?= $id ? '' : 'required' ?>>
                            <?php if ($id): ?>
                                <small class="text-muted">Deixe em branco para manter a senha atual</small>
                            <?php else: ?>
                                <small class="text-muted">Mínimo 8 caracteres, com letras e números</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Perfil <span class="text-danger">*</span></label>
                            <select name="perfil" class="form-select" required>
                                <option value="caixa" <?= ($user['perfil'] ?? 'caixa') === 'caixa' ? 'selected' : '' ?>>Caixa</option>
                                <option value="gerente" <?= ($user['perfil'] ?? '') === 'gerente' ? 'selected' : '' ?>>Gerente</option>
                                <option value="admin" <?= ($user['perfil'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="ativo" class="form-check-input" id="ativo" <?= ($user['ativo'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ativo">Usuário Ativo</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
                <a href="<?= baseUrl('usuarios/') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

    <?php if ($id && $user): ?>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header"><i class="fas fa-info-circle me-2"></i>Informações</div>
            <div class="card-body">
                <p class="mb-1"><strong>ID:</strong> <?= $user['id'] ?></p>
                <p class="mb-1"><strong>Perfil:</strong>
                    <span class="badge bg-<?= match($user['perfil']) { 'admin'=>'danger', 'gerente'=>'warning', default=>'info' } ?>">
                        <?= ucfirst(e($user['perfil'])) ?>
                    </span>
                </p>
                <p class="mb-1"><strong>Último Acesso:</strong><br>
                    <?= !empty($user['ultimo_acesso']) ? formatDateTime($user['ultimo_acesso']) : 'Nunca' ?>
                </p>
                <?php if (!empty($user['criado_em'])): ?>
                <p class="mb-0"><strong>Cadastrado em:</strong><br>
                    <?= formatDateTime($user['criado_em']) ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
