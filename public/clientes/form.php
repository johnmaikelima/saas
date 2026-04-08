<?php
$pageTitle = 'Cliente';
require_once __DIR__ . '/../../app/includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$cliente = null;

if ($id > 0) {
    $cliente = tenantFind('clientes', $id);
    if (!$cliente) {
        flashError('Cliente não encontrado.');
        redirect('clientes/');
    }
    $pageTitle = 'Editar Cliente';
} else {
    $pageTitle = 'Novo Cliente';
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flashError('Token inválido.');
        redirect('clientes/form.php' . ($id ? "?id={$id}" : ''));
    }

    $data = [
        'nome'         => sanitize($_POST['nome'] ?? ''),
        'cpf_cnpj'     => limparCpfCnpj($_POST['cpf_cnpj'] ?? ''),
        'rg_ie'        => sanitize($_POST['rg_ie'] ?? ''),
        'telefone'     => sanitize($_POST['telefone'] ?? ''),
        'celular'      => sanitize($_POST['celular'] ?? ''),
        'email'        => sanitize($_POST['email'] ?? ''),
        'cep'          => sanitize($_POST['cep'] ?? ''),
        'endereco'     => sanitize($_POST['endereco'] ?? ''),
        'numero'       => sanitize($_POST['numero'] ?? ''),
        'complemento'  => sanitize($_POST['complemento'] ?? ''),
        'bairro'       => sanitize($_POST['bairro'] ?? ''),
        'cidade'       => sanitize($_POST['cidade'] ?? ''),
        'estado'       => sanitize($_POST['estado'] ?? ''),
        'observacoes'  => sanitize($_POST['observacoes'] ?? ''),
    ];

    if (empty($data['nome'])) {
        flashError('O nome do cliente é obrigatório.');
        redirect('clientes/form.php' . ($id ? "?id={$id}" : ''));
    }

    if ($id > 0) {
        tenantUpdate('clientes', $data, $id);
        flashSuccess('Cliente atualizado com sucesso!');
    } else {
        tenantInsert('clientes', $data);
        flashSuccess('Cliente cadastrado com sucesso!');
    }

    redirect('clientes/');
}

$estados = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="fas fa-user me-2"></i><?= $id ? 'Editar Cliente' : 'Novo Cliente' ?>
    </h4>
    <a href="<?= baseUrl('clientes/') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<form method="POST">
    <?= csrfField() ?>

    <div class="card shadow mb-3">
        <div class="card-header"><i class="fas fa-id-card me-2"></i>Dados Pessoais</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome <span class="text-danger">*</span></label>
                    <input type="text" name="nome" class="form-control" value="<?= e($cliente['nome'] ?? '') ?>" required maxlength="255">
                </div>
                <div class="col-md-3">
                    <label class="form-label">CPF/CNPJ</label>
                    <input type="text" name="cpf_cnpj" class="form-control" value="<?= e(!empty($cliente['cpf_cnpj']) ? formatDoc($cliente['cpf_cnpj']) : '') ?>" data-mask="cpfcnpj" maxlength="18">
                </div>
                <div class="col-md-3">
                    <label class="form-label">RG/IE</label>
                    <input type="text" name="rg_ie" class="form-control" value="<?= e($cliente['rg_ie'] ?? '') ?>" maxlength="20">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Telefone</label>
                    <input type="text" name="telefone" class="form-control" value="<?= e($cliente['telefone'] ?? '') ?>" data-mask="telefone" maxlength="15">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Celular</label>
                    <input type="text" name="celular" class="form-control" value="<?= e($cliente['celular'] ?? '') ?>" data-mask="telefone" maxlength="15">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= e($cliente['email'] ?? '') ?>" maxlength="255">
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-3">
        <div class="card-header"><i class="fas fa-map-marker-alt me-2"></i>Endereço</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">CEP</label>
                    <input type="text" name="cep" class="form-control" value="<?= e($cliente['cep'] ?? '') ?>" data-mask="cep" maxlength="9">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Endereço</label>
                    <input type="text" name="endereco" class="form-control" value="<?= e($cliente['endereco'] ?? '') ?>" maxlength="255">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Número</label>
                    <input type="text" name="numero" class="form-control" value="<?= e($cliente['numero'] ?? '') ?>" maxlength="10">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Complemento</label>
                    <input type="text" name="complemento" class="form-control" value="<?= e($cliente['complemento'] ?? '') ?>" maxlength="100">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Bairro</label>
                    <input type="text" name="bairro" class="form-control" value="<?= e($cliente['bairro'] ?? '') ?>" maxlength="100">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cidade</label>
                    <input type="text" name="cidade" class="form-control" value="<?= e($cliente['cidade'] ?? '') ?>" maxlength="100">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Selecione</option>
                        <?php foreach ($estados as $uf): ?>
                            <option value="<?= $uf ?>" <?= ($cliente['estado'] ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-3">
        <div class="card-header"><i class="fas fa-sticky-note me-2"></i>Observações</div>
        <div class="card-body">
            <textarea name="observacoes" class="form-control" rows="3" maxlength="1000"><?= e($cliente['observacoes'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
        <a href="<?= baseUrl('clientes/') ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
