<?php
$pageTitle = 'PDV';
require_once __DIR__ . '/../../app/includes/auth.php';

if (!temPerfil('admin', 'gerente')) {
    flashError('Sem permissão para gerenciar PDVs.');
    redirect('dashboard/');
}

$id = (int)($_GET['id'] ?? 0);
$pdv = null;
$pdo = db();
$tid = tenantId();

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM pdvs WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tid]);
    $pdv = $stmt->fetch();
    if (!$pdv) {
        flashError('PDV não encontrado.');
        redirect('pdvs/');
    }
    $pageTitle = 'Editar PDV';
} else {
    $pageTitle = 'Novo PDV';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flashError('Token inválido.');
        redirect('pdvs/form.php' . ($id ? "?id={$id}" : ''));
    }

    $nome = sanitize($_POST['nome'] ?? '');
    $descricao = sanitize($_POST['descricao'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (empty($nome)) {
        flashError('O nome do PDV é obrigatório.');
        redirect('pdvs/form.php' . ($id ? "?id={$id}" : ''));
    }

    // Verificar nome duplicado
    $check = $pdo->prepare("SELECT id FROM pdvs WHERE tenant_id = ? AND nome = ? AND id != ?");
    $check->execute([$tid, $nome, $id]);
    if ($check->fetch()) {
        flashError('Já existe um PDV com este nome.');
        redirect('pdvs/form.php' . ($id ? "?id={$id}" : ''));
    }

    if ($id > 0) {
        $pdo->prepare("UPDATE pdvs SET nome = ?, descricao = ?, ativo = ? WHERE id = ? AND tenant_id = ?")
            ->execute([$nome, $descricao, $ativo, $id, $tid]);
        auditLog('pdv_atualizado', "PDV #{$id} '{$nome}' atualizado");
        flashSuccess('PDV atualizado com sucesso!');
    } else {
        $pdo->prepare("INSERT INTO pdvs (tenant_id, nome, descricao, ativo) VALUES (?, ?, ?, ?)")
            ->execute([$tid, $nome, $descricao, $ativo]);
        $newId = (int)$pdo->lastInsertId();
        auditLog('pdv_criado', "PDV #{$newId} '{$nome}' cadastrado");
        flashSuccess('PDV cadastrado com sucesso!');
    }

    redirect('pdvs/');
}

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="fas fa-cash-register me-2"></i><?= $id ? 'Editar PDV' : 'Novo PDV' ?>
    </h4>
    <a href="<?= baseUrl('pdvs/') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<div class="row">
    <div class="col-md-6">
        <form method="POST">
            <?= csrfField() ?>

            <div class="card shadow mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="nome" class="form-control" value="<?= e($pdv['nome'] ?? '') ?>" required maxlength="100" placeholder="Ex: Caixa Entrada 1" autofocus>
                        <small class="text-muted">Nome para identificar o terminal (ex: Caixa Entrada 1, Caixa Fundo)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="descricao" class="form-control" value="<?= e($pdv['descricao'] ?? '') ?>" maxlength="255" placeholder="Ex: Próximo à entrada principal">
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="ativo" class="form-check-input" id="ativo" <?= ($pdv['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="ativo">PDV Ativo</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
                <a href="<?= baseUrl('pdvs/') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
