<?php
$pageTitle = 'Configurações';
require_once __DIR__ . '/../../app/includes/auth.php';
if (!temPerfil('admin')) { flashError('Sem permissão.'); redirect('dashboard/'); }

$pdo = db();
$tid = tenantId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { flashError('Token inválido.'); redirect('configuracao/'); }

    $secao = $_POST['secao'] ?? '';

    if ($secao === 'empresa') {
        $campos = ['razao_social','nome_fantasia','cnpj','ie','im','cep','endereco','numero','complemento','bairro','cidade','estado','telefone','email','regime_tributario'];
        $set = implode(', ', array_map(fn($c) => "{$c} = ?", $campos));
        $vals = array_map(fn($c) => sanitize($_POST[$c] ?? ''), $campos);
        $vals[] = $tid;

        $stmt = $pdo->prepare("UPDATE tenants SET {$set} WHERE id = ?");
        $stmt->execute($vals);

        // Logo
        if (!empty($_FILES['logo']['name'])) {
            $uploaded = uploadFile($_FILES['logo'], 'logos');
            if ($uploaded) {
                $pdo->prepare("UPDATE tenants SET logo = ? WHERE id = ?")->execute([$uploaded, $tid]);
            }
        }

        auditLog('config_empresa', 'Dados da empresa atualizados');
        flashSuccess('Dados da empresa atualizados.');
    } elseif ($secao === 'sistema') {
        foreach (['nome_loja','casas_decimais_qtd'] as $k) {
            setConfig($k, sanitize($_POST[$k] ?? ''), 'sistema');
        }
        foreach (['vender_sem_estoque','alerta_estoque_minimo'] as $k) {
            setConfig($k, isset($_POST[$k]) ? '1' : '0', 'sistema');
        }
        auditLog('config_sistema', 'Configurações do sistema atualizadas');
        flashSuccess('Configurações do sistema atualizadas.');
    }

    redirect('configuracao/');
}

$empresa = getEmpresa();
$estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-sliders me-2"></i>Configurações</h5>

<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#empresa">Empresa</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sistema">Sistema</a></li>
</ul>

<div class="tab-content mt-3">
    <div class="tab-pane fade show active" id="empresa">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="secao" value="empresa">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Razão Social</label><input type="text" class="form-control" name="razao_social" value="<?= e($empresa['razao_social'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Nome Fantasia</label><input type="text" class="form-control" name="nome_fantasia" value="<?= e($empresa['nome_fantasia'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label">CNPJ</label><input type="text" class="form-control" name="cnpj" data-mask="cpfcnpj" value="<?= e($empresa['cnpj'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label">IE</label><input type="text" class="form-control" name="ie" value="<?= e($empresa['ie'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label">IM</label><input type="text" class="form-control" name="im" value="<?= e($empresa['im'] ?? '') ?>"></div>
                        <div class="col-md-3">
                            <label class="form-label">Regime Tributário</label>
                            <select class="form-select" name="regime_tributario">
                                <option value="1" <?= ($empresa['regime_tributario'] ?? 1) == 1 ? 'selected' : '' ?>>1 - Simples Nacional</option>
                                <option value="2" <?= ($empresa['regime_tributario'] ?? 1) == 2 ? 'selected' : '' ?>>2 - Simples Excesso</option>
                                <option value="3" <?= ($empresa['regime_tributario'] ?? 1) == 3 ? 'selected' : '' ?>>3 - Regime Normal</option>
                            </select>
                        </div>
                        <div class="col-md-2"><label class="form-label">CEP</label><input type="text" class="form-control" name="cep" data-mask="cep" value="<?= e($empresa['cep'] ?? '') ?>"></div>
                        <div class="col-md-5"><label class="form-label">Endereço</label><input type="text" class="form-control" name="endereco" value="<?= e($empresa['endereco'] ?? '') ?>"></div>
                        <div class="col-md-2"><label class="form-label">Número</label><input type="text" class="form-control" name="numero" value="<?= e($empresa['numero'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label">Complemento</label><input type="text" class="form-control" name="complemento" value="<?= e($empresa['complemento'] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Bairro</label><input type="text" class="form-control" name="bairro" value="<?= e($empresa['bairro'] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Cidade</label><input type="text" class="form-control" name="cidade" value="<?= e($empresa['cidade'] ?? '') ?>"></div>
                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="">UF</option>
                                <?php foreach ($estados as $uf): ?>
                                    <option value="<?= $uf ?>" <?= ($empresa['estado'] ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Telefone</label><input type="text" class="form-control" name="telefone" data-mask="telefone" value="<?= e($empresa['telefone'] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?= e($empresa['email'] ?? '') ?>"></div>
                        <div class="col-md-5">
                            <label class="form-label">Logo</label>
                            <input type="file" class="form-control" name="logo" accept="image/*">
                            <?php if (!empty($empresa['logo'])): ?>
                                <small class="text-muted">Atual: <?= e($empresa['logo']) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save me-1"></i>Salvar</button>
                </form>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="sistema">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="secao" value="sistema">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Nome da Loja</label><input type="text" class="form-control" name="nome_loja" value="<?= e(getConfig('nome_loja', $empresa['nome_fantasia'] ?? 'Kaixa')) ?>"></div>
                        <div class="col-md-2"><label class="form-label">Casas Decimais Qtd</label><input type="number" class="form-control" name="casas_decimais_qtd" value="<?= e(getConfig('casas_decimais_qtd', '2')) ?>" min="0" max="4"></div>
                        <div class="col-12">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="vender_sem_estoque" <?= getConfig('vender_sem_estoque') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label">Permitir venda sem estoque</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="alerta_estoque_minimo" <?= getConfig('alerta_estoque_minimo', '1') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label">Alerta de estoque mínimo</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save me-1"></i>Salvar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
