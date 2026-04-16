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
    } elseif ($secao === 'fiscal') {
        // Configurações fiscais NFC-e
        setConfig('nfce_ambiente', $_POST['nfce_ambiente'] === '1' ? '1' : '2', 'fiscal');
        setConfig('nfce_serie', max(1, (int)($_POST['nfce_serie'] ?? 1)) . '', 'fiscal');
        setConfig('nfce_numero', max(1, (int)($_POST['nfce_numero'] ?? 1)) . '', 'fiscal');
        setConfig('nfce_csc', sanitize($_POST['nfce_csc'] ?? ''), 'fiscal');
        setConfig('nfce_csc_id', sanitize($_POST['nfce_csc_id'] ?? ''), 'fiscal');
        setConfig('codigo_ibge_municipio', sanitize($_POST['codigo_ibge_municipio'] ?? ''), 'fiscal');
        setConfig('impressora_largura', in_array($_POST['impressora_largura'] ?? '80', ['58','80']) ? $_POST['impressora_largura'] : '80', 'fiscal');

        // Upload certificado A1
        if (!empty($_FILES['certificado']['name'])) {
            $certFile = $_FILES['certificado'];
            if ($certFile['error'] === UPLOAD_ERR_OK && $certFile['size'] <= 10 * 1024 * 1024) {
                $certDir = STORAGE_PATH . '/certificados/' . $tid;
                if (!is_dir($certDir)) mkdir($certDir, 0755, true);

                $certPath = $certDir . '/certificado.pfx';
                $senhaCert = $_POST['certificado_senha'] ?? '';

                // Validar certificado antes de salvar
                try {
                    $certContent = file_get_contents($certFile['tmp_name']);
                    \NFePHP\Common\Certificate::readPfx($certContent, $senhaCert);
                    file_put_contents($certPath, $certContent);
                    chmod($certPath, 0600);
                    setConfig('certificado_senha', encryptValue($senhaCert), 'fiscal');

                    // Ler validade do certificado
                    $certData = [];
                    if (openssl_pkcs12_read($certContent, $certData, $senhaCert)) {
                        $certInfo = openssl_x509_parse($certData['cert']);
                        if ($certInfo && isset($certInfo['validTo_time_t'])) {
                            setConfig('certificado_validade', date('Y-m-d', $certInfo['validTo_time_t']), 'fiscal');
                        }
                    }

                    flashSuccess('Certificado digital salvo com sucesso!');
                } catch (\Exception $e) {
                    flashError('Erro ao ler certificado: Verifique a senha e o arquivo .pfx');
                }
            } else {
                flashError('Erro no upload do certificado.');
            }
        } elseif (!empty(trim($_POST['certificado_senha'] ?? ''))) {
            // Atualizar só a senha (encriptada) - só se preencheu
            setConfig('certificado_senha', encryptValue(trim($_POST['certificado_senha'])), 'fiscal');
        }

        auditLog('config_fiscal', 'Configurações fiscais atualizadas');
        flashSuccess('Configurações fiscais atualizadas.');
    }

    redirect('configuracao/' . ($secao === 'fiscal' ? '#fiscal' : ''));
}

$empresa = getEmpresa();
$estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-sliders me-2"></i>Configurações</h5>

<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#empresa">Empresa</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sistema">Sistema</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#fiscal" id="tab-fiscal">Fiscal (NFC-e)</a></li>
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

    <div class="tab-pane fade" id="fiscal">
        <div class="card shadow-sm mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Configurações NFC-e</h6></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="secao" value="fiscal">

                    <?php
                    $certPath = STORAGE_PATH . '/certificados/' . $tid . '/certificado.pfx';
                    $certExiste = file_exists($certPath);
                    $certValidade = getConfig('certificado_validade', '');
                    $certVencido = !empty($certValidade) && strtotime($certValidade) < time();
                    ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <h6 class="text-muted border-bottom pb-2 mb-3">Certificado Digital A1</h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Certificado (.pfx)</label>
                            <input type="file" class="form-control" name="certificado" accept=".pfx,.p12">
                            <?php if ($certExiste): ?>
                                <small class="text-success"><i class="fas fa-check-circle me-1"></i>Certificado instalado</small>
                                <?php if (!empty($certValidade)): ?>
                                    <small class="<?= $certVencido ? 'text-danger' : 'text-muted' ?> ms-2">
                                        Validade: <?= formatDate($certValidade) ?>
                                        <?= $certVencido ? ' (VENCIDO!)' : '' ?>
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <small class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Nenhum certificado instalado</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Senha do Certificado</label>
                            <input type="password" class="form-control" name="certificado_senha" placeholder="<?= !empty(getConfig('certificado_senha', '')) ? '••••••••' : 'Senha do arquivo .pfx' ?>">
                            <?php if (!empty(getConfig('certificado_senha', ''))): ?>
                                <small class="text-muted">Senha já cadastrada. Deixe em branco para manter.</small>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 mt-4">
                            <h6 class="text-muted border-bottom pb-2 mb-3">Dados SEFAZ</h6>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Ambiente</label>
                            <select class="form-select" name="nfce_ambiente">
                                <option value="2" <?= getConfig('nfce_ambiente', '2') === '2' ? 'selected' : '' ?>>Homologação (testes)</option>
                                <option value="1" <?= getConfig('nfce_ambiente', '2') === '1' ? 'selected' : '' ?>>Produção</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Série NFC-e</label>
                            <input type="number" class="form-control" name="nfce_serie" value="<?= e(getConfig('nfce_serie', '1')) ?>" min="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Próximo Número</label>
                            <input type="number" class="form-control" name="nfce_numero" value="<?= e(getConfig('nfce_numero', '1')) ?>" min="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cód. IBGE Município</label>
                            <input type="text" class="form-control" name="codigo_ibge_municipio" value="<?= e(getConfig('codigo_ibge_municipio', '')) ?>" placeholder="Ex: 3550308" maxlength="7">
                            <small class="text-muted">7 dígitos. Consulte no IBGE.</small>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">CSC (Token)</label>
                            <input type="text" class="form-control" name="nfce_csc" value="<?= e(getConfig('nfce_csc', '')) ?>" placeholder="Código de Segurança do Contribuinte">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CSC ID (ID do Token)</label>
                            <input type="text" class="form-control" name="nfce_csc_id" value="<?= e(getConfig('nfce_csc_id', '')) ?>" placeholder="Ex: 000001">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Largura Impressão</label>
                            <select class="form-select" name="impressora_largura">
                                <option value="80" <?= getConfig('impressora_largura', '80') === '80' ? 'selected' : '' ?>>80mm (padrão)</option>
                                <option value="58" <?= getConfig('impressora_largura', '80') === '58' ? 'selected' : '' ?>>58mm</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar Configurações Fiscais</button>
                        <button type="button" class="btn btn-outline-info" id="btnTestarSefaz">
                            <i class="fas fa-satellite-dish me-1"></i>Testar Conexão SEFAZ
                        </button>
                        <span id="sefazStatus"></span>
                    </div>
                </form>
            </div>
        </div>

        <?php
        // Verificação rápida da configuração
        $nfceHelper = new NfceHelper($tid);
        $configCheck = $nfceHelper->verificarConfiguracao();
        ?>
        <?php if (!$configCheck['ok']): ?>
        <div class="alert alert-warning">
            <h6><i class="fas fa-exclamation-triangle me-2"></i>Configuração Incompleta</h6>
            <ul class="mb-0">
                <?php foreach ($configCheck['erros'] as $erro): ?>
                    <li><?= e($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php else: ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>Configuração fiscal completa! Pronto para emitir NFC-e.
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('btnTestarSefaz')?.addEventListener('click', function() {
    const btn = this;
    const status = document.getElementById('sefazStatus');
    btn.disabled = true;
    status.innerHTML = '<span class="text-muted"><i class="fas fa-spinner fa-spin me-1"></i>Consultando...</span>';

    fetch('<?= APP_URL ?>/api/nfce-status.php', {
        headers: {'X-CSRF-Token': '<?= csrfToken() ?>'}
    })
    .then(r => r.json())
    .then(data => {
        if (data.online) {
            status.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>SEFAZ Online - ' + data.mensagem + '</span>';
        } else {
            status.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>' + data.mensagem + '</span>';
        }
    })
    .catch(() => {
        status.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Erro ao consultar</span>';
    })
    .finally(() => btn.disabled = false);
});

// Ativar aba fiscal se URL tem #fiscal
if (window.location.hash === '#fiscal') {
    document.getElementById('tab-fiscal')?.click();
}
</script>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
