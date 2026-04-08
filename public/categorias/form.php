<?php
$pageTitle = 'Categoria';
require_once __DIR__ . '/../../app/includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$categoria = null;

if ($id > 0) {
    $categoria = tenantFind('categorias', $id);
    if (!$categoria) {
        flashError('Categoria não encontrada.');
        redirect('categorias/');
    }
    $pageTitle = 'Editar Categoria';
} else {
    $pageTitle = 'Nova Categoria';
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flashError('Token inválido.');
        redirect('categorias/form.php' . ($id ? "?id={$id}" : ''));
    }

    $data = [
        'nome'   => sanitize($_POST['nome'] ?? ''),
        'cor'    => sanitize($_POST['cor'] ?? '#6c757d'),
        'icone'  => sanitize($_POST['icone'] ?? ''),
        'ativo'  => isset($_POST['ativo']) ? 1 : 0,
    ];

    if (empty($data['nome'])) {
        flashError('O nome da categoria é obrigatório.');
        redirect('categorias/form.php' . ($id ? "?id={$id}" : ''));
    }

    if ($id > 0) {
        tenantUpdate('categorias', $data, $id);
        flashSuccess('Categoria atualizada com sucesso!');
    } else {
        tenantInsert('categorias', $data);
        flashSuccess('Categoria cadastrada com sucesso!');
    }

    redirect('categorias/');
}

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="fas fa-tags me-2"></i><?= $id ? 'Editar Categoria' : 'Nova Categoria' ?>
    </h4>
    <a href="<?= baseUrl('categorias/') ?>" class="btn btn-outline-secondary btn-sm">
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
                        <input type="text" name="nome" class="form-control" value="<?= e($categoria['nome'] ?? '') ?>" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cor</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" name="cor" class="form-control form-control-color" value="<?= e($categoria['cor'] ?? '#6c757d') ?>" id="corPicker">
                            <span class="badge" id="corPreview" style="background-color: <?= e($categoria['cor'] ?? '#6c757d') ?>">Exemplo</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ícone (classe Font Awesome)</label>
                        <div class="input-group">
                            <span class="input-group-text" id="iconePreview">
                                <i class="<?= e($categoria['icone'] ?? 'fas fa-tag') ?>"></i>
                            </span>
                            <input type="text" name="icone" class="form-control" value="<?= e($categoria['icone'] ?? '') ?>" placeholder="fas fa-tag" id="iconeInput">
                        </div>
                        <small class="text-muted">Ex: fas fa-pizza-slice, fas fa-glass-water, fas fa-shirt</small>
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="ativo" class="form-check-input" id="ativo" <?= ($categoria['ativo'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="ativo">Categoria Ativa</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
                <a href="<?= baseUrl('categorias/') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('corPicker').addEventListener('input', function() {
    document.getElementById('corPreview').style.backgroundColor = this.value;
});
document.getElementById('iconeInput').addEventListener('input', function() {
    document.getElementById('iconePreview').innerHTML = '<i class="' + this.value + '"></i>';
});
</script>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
