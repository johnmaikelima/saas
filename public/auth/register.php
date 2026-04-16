<?php
$pageTitle = 'Cadastro';
require_once __DIR__ . '/../../app/bootstrap.php';
autoMigrate();

// Já logado?
if (isset($_SESSION['usuario']) && isset($_SESSION['tenant_id'])) {
    redirect('dashboard/');
}

$erro = '';
$erros = [];
$bloqueado = false;
$dados = [
    'cnpj' => '',
    'nome_empresa' => '',
    'nome_fantasia' => '',
    'email' => '',
    'whatsapp' => '',
    'cidade' => '',
    'uf' => '',
    'nome_responsavel' => '',
    'login' => '',
];

// Buscar planos do Painel (com cache)
$planosApi = [];
$cacheFile = STORAGE_PATH . '/cache_planos.json';
$cacheValido = file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 600;

if ($cacheValido) {
    $planosApi = json_decode(file_get_contents($cacheFile), true) ?: [];
} elseif (!empty(PAINEL_API_URL)) {
    try {
        $ch = curl_init(PAINEL_API_URL . '?action=planos&tipo=saas');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_SSL_VERIFYPEER => true]);
        $result = curl_exec($ch);
        curl_close($ch);
        if ($result) {
            $data = json_decode($result, true);
            if (!empty($data['ok']) && !empty($data['planos'])) {
                $planosApi = $data['planos'];
                @file_put_contents($cacheFile, json_encode($planosApi));
            }
        }
    } catch (\Throwable $e) {}
}

// Montar array de planos indexado pelo slug curto
$cores = ['#10b981', '#4f46e5', '#f59e0b', '#ef4444', '#8b5cf6'];
$planosInfo = [];
foreach ($planosApi as $i => $p) {
    $slugCurto = str_replace(['saas-', '-mensal', '-trimestral', '-anual'], '', $p['slug']);
    $preco = (float)$p['preco'];
    $recursos = is_array($p['recursos']) ? $p['recursos'] : (json_decode($p['recursos'] ?? '{}', true) ?: []);
    $planosInfo[$slugCurto] = [
        'nome'       => $p['nome'],
        'preco'      => $preco > 0 ? 'R$ ' . number_format($preco, 2, ',', '.') . '/mês' : 'Grátis',
        'valor'      => $preco,
        'cor'        => $cores[$i % count($cores)],
        'slug'       => $p['slug'],
        'beneficios' => $recursos['beneficios'] ?? [],
    ];
}

// Fallback se API falhar
if (empty($planosInfo)) {
    $planosInfo = [
        'starter'    => ['nome' => 'Starter',    'preco' => 'R$ 99,90/mês',  'valor' => 99.90,  'cor' => '#10b981', 'slug' => 'saas-starter-mensal', 'beneficios' => ['Até 500 produtos','2 usuários','PDV completo','Controle de estoque','Relatórios básicos']],
        'business'   => ['nome' => 'Business',   'preco' => 'R$ 199,90/mês', 'valor' => 199.90, 'cor' => '#4f46e5', 'slug' => 'saas-business-mensal', 'beneficios' => ['Até 2.000 produtos','5 usuários','PDV completo','Controle de estoque','Gestão de clientes','Relatórios avançados']],
        'enterprise' => ['nome' => 'Enterprise', 'preco' => 'R$ 399,90/mês', 'valor' => 399.90, 'cor' => '#f59e0b', 'slug' => 'saas-enterprise-mensal', 'beneficios' => ['Produtos ilimitados','Usuários ilimitados','PDV completo','Controle de estoque','Gestão de clientes','Relatórios avançados','Suporte prioritário 24/7']],
    ];
}

// Plano selecionado
$planoSelecionado = sanitize($_GET['plano'] ?? $_POST['plano'] ?? array_key_first($planosInfo));
if (!isset($planosInfo[$planoSelecionado])) $planoSelecionado = array_key_first($planosInfo);
$planoAtual = $planosInfo[$planoSelecionado];

// Rate limit: 3 tentativas por 10 minutos
if (rateLimited('register', 3, 600)) {
    $bloqueado = true;
    $erro = 'Muitas tentativas de cadastro. Aguarde 10 minutos antes de tentar novamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$bloqueado) {
    if (!verifyCsrf()) {
        $erro = 'Token de segurança inválido. Recarregue a página.';
    } else {
        // Coletar dados
        $dados['cnpj'] = limparCpfCnpj($_POST['cnpj'] ?? '');
        $dados['nome_empresa'] = sanitize($_POST['nome_empresa'] ?? '');
        $dados['nome_fantasia'] = sanitize($_POST['nome_fantasia'] ?? '');
        $dados['email'] = trim($_POST['email'] ?? '');
        $dados['whatsapp'] = sanitize($_POST['whatsapp'] ?? '');
        $dados['cidade'] = sanitize($_POST['cidade'] ?? '');
        $dados['uf'] = strtoupper(sanitize($_POST['uf'] ?? ''));
        $dados['nome_responsavel'] = sanitize($_POST['nome_responsavel'] ?? '');
        $dados['login'] = sanitize($_POST['login'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';

        // Validações
        if (empty($dados['cnpj']) || !validaCnpj($dados['cnpj'])) {
            $erros[] = 'CNPJ inválido.';
        }
        if (empty($dados['nome_empresa'])) {
            $erros[] = 'Razão social é obrigatória.';
        }
        if (empty($dados['nome_fantasia'])) {
            $erros[] = 'Nome fantasia é obrigatório.';
        }
        if (!validateEmail($dados['email'])) {
            $erros[] = 'E-mail inválido.';
        }
        if (empty($dados['whatsapp'])) {
            $erros[] = 'WhatsApp é obrigatório.';
        }
        if (empty($dados['cidade'])) {
            $erros[] = 'Cidade é obrigatória.';
        }
        if (empty($dados['uf']) || strlen($dados['uf']) !== 2) {
            $erros[] = 'UF inválida.';
        }
        if (empty($dados['nome_responsavel'])) {
            $erros[] = 'Nome do responsável é obrigatório.';
        }
        if (empty($dados['login']) || strlen($dados['login']) < 3) {
            $erros[] = 'Login deve ter pelo menos 3 caracteres.';
        }
        if (empty($senha)) {
            $erros[] = 'Senha é obrigatória.';
        } elseif (!validatePassword($senha)) {
            $erros[] = 'Senha deve ter no mínimo 8 caracteres, com pelo menos 1 letra e 1 número.';
        }
        if ($senha !== $confirmar_senha) {
            $erros[] = 'As senhas não conferem.';
        }

        // Se não há erros de validação, verificar duplicidade
        if (empty($erros)) {
            // Verificar CNPJ duplicado
            $stmt = db()->prepare("SELECT id FROM tenants WHERE cnpj = ?");
            $stmt->execute([$dados['cnpj']]);
            if ($stmt->fetch()) {
                $erros[] = 'Este CNPJ já está cadastrado no sistema.';
            }

            // Verificar login duplicado
            $stmt = db()->prepare("SELECT id FROM usuarios WHERE login = ?");
            $stmt->execute([$dados['login']]);
            if ($stmt->fetch()) {
                $erros[] = 'Este login já está em uso. Escolha outro.';
            }
        }

        // Se tudo ok, criar tenant e usuário
        if (empty($erros)) {
            try {
                $pdo = db();
                $pdo->beginTransaction();

                // 1. Registrar no Painel (licenciamento centralizado)
                $painelResponse = null;
                $licencaChave = null;
                $painelApiToken = null;
                try {
                    $painelData = json_encode([
                        'api_secret'     => PAINEL_API_SECRET,
                        'razao_social'   => $dados['nome_empresa'],
                        'nome_fantasia'  => $dados['nome_fantasia'],
                        'cnpj'           => $dados['cnpj'],
                        'email'          => $dados['email'],
                        'whatsapp'       => $dados['whatsapp'],
                        'contato_nome'   => $dados['nome_responsavel'],
                        'cidade'         => $dados['cidade'],
                        'uf'             => $dados['uf'],
                        'plano_slug'     => $planoAtual['slug'],
                        'login_saas'     => $dados['login'],
                    ]);

                    $ch = curl_init(PAINEL_API_URL . '?action=registrar_saas');
                    curl_setopt_array($ch, [
                        CURLOPT_POST           => true,
                        CURLOPT_POSTFIELDS     => $painelData,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                        CURLOPT_TIMEOUT        => 15,
                        CURLOPT_SSL_VERIFYPEER => true,
                    ]);
                    $painelResult = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($painelResult) {
                        $painelResponse = json_decode($painelResult, true);
                    }

                    if ($httpCode === 409) {
                        $erros[] = $painelResponse['mensagem'] ?? 'CNPJ já cadastrado no sistema de licenciamento.';
                        throw new \Exception('CNPJ duplicado no Painel');
                    }

                    if ($painelResponse && !empty($painelResponse['ok'])) {
                        $licencaChave = $painelResponse['chave'] ?? null;
                        $painelApiToken = $painelResponse['api_token'] ?? null;
                    }
                } catch (\Exception $apiEx) {
                    if (!empty($erros)) {
                        throw $apiEx; // Propagar erro de CNPJ duplicado
                    }
                    // Se API do Painel falhar, continuar sem licença (registrar localmente)
                    error_log('Painel API error: ' . $apiEx->getMessage());
                }

                // 2. Criar tenant
                $stmt = $pdo->prepare("INSERT INTO tenants (razao_social, nome_fantasia, cnpj, email, telefone, cidade, estado, plano, licenca_chave, api_token, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativo')");
                $stmt->execute([
                    $dados['nome_empresa'],
                    $dados['nome_fantasia'],
                    $dados['cnpj'],
                    $dados['email'],
                    $dados['whatsapp'],
                    $dados['cidade'],
                    $dados['uf'],
                    $planoSelecionado,
                    $licencaChave,
                    $painelApiToken,
                ]);
                $tenantId = (int) $pdo->lastInsertId();

                // 3. Criar usuário admin
                $senhaHash = hashPassword($senha);
                $stmt = $pdo->prepare("INSERT INTO usuarios (tenant_id, nome, login, email, senha_hash, perfil, ativo, trocar_senha) VALUES (?, ?, ?, ?, ?, 'admin', 1, 0)");
                $stmt->execute([
                    $tenantId,
                    $dados['nome_responsavel'],
                    $dados['login'],
                    $dados['email'],
                    $senhaHash,
                ]);
                $usuarioId = (int) $pdo->lastInsertId();

                // 4. Criar configurações padrão
                $configsPadrao = [
                    ['nome_loja', $dados['nome_fantasia'], 'sistema'],
                    ['cnpj', $dados['cnpj'], 'empresa'],
                    ['cidade', $dados['cidade'], 'empresa'],
                    ['uf', $dados['uf'], 'empresa'],
                ];
                $stmtConfig = $pdo->prepare("INSERT INTO configuracoes (tenant_id, chave, valor, grupo) VALUES (?, ?, ?, ?)");
                foreach ($configsPadrao as $cfg) {
                    $stmtConfig->execute([$tenantId, $cfg[0], $cfg[1], $cfg[2]]);
                }

                $pdo->commit();

                // 5. Auto-login
                regenerateSession();

                $_SESSION['usuario'] = [
                    'id' => $usuarioId,
                    'nome' => $dados['nome_responsavel'],
                    'login' => $dados['login'],
                    'perfil' => 'admin',
                    'trocar_senha' => false,
                ];
                $_SESSION['tenant_id'] = $tenantId;

                validateSession();

                auditLog('registro', 'Nova empresa cadastrada: ' . $dados['cnpj'] . ($licencaChave ? ' | Licença: ' . $licencaChave : ''), $tenantId, $usuarioId);

                rateLimitClear('register');

                // Se plano pago e tem URL de pagamento, redirecionar
                if ($painelResponse && !empty($painelResponse['payment_url'])) {
                    flashSuccess('Cadastro realizado! Você será redirecionado para o pagamento.');
                    redirect($painelResponse['payment_url']);
                }

                flashSuccess('Cadastro realizado com sucesso! Bem-vindo ao ' . APP_NAME . '. Seu período de teste é de 15 dias.');
                redirect('dashboard/');

            } catch (\Exception $ex) {
                if (isset($pdo) && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                rateLimitHit('register', 600);
                if (empty($erros)) {
                    $erro = 'Erro ao criar conta. Tente novamente.';
                }
                error_log('Register error: ' . $ex->getMessage());
            }
        } else {
            rateLimitHit('register', 600);
        }
    }
}

$ufs = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            padding: 30px 0;
        }
        .register-card {
            background: rgba(255,255,255,0.97);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 100%;
            margin: 0 auto;
            padding: 40px;
        }
        .register-card .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .register-card .logo i {
            font-size: 42px;
            color: #0f3460;
        }
        .register-card .logo h4 {
            color: #1a1a2e;
            margin-top: 8px;
            font-weight: 700;
        }
        .form-floating > .form-control:focus,
        .form-floating > .form-select:focus {
            border-color: #0f3460;
            box-shadow: 0 0 0 0.2rem rgba(15,52,96,0.25);
        }
        .btn-register {
            background: #0f3460;
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
        }
        .btn-register:hover {
            background: #1a1a2e;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 25px;
        }
        .step-indicator .step {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        .step.active {
            background: #0f3460;
            color: #fff;
        }
        .step.inactive {
            background: #e9ecef;
            color: #6c757d;
        }
        .step-label {
            font-size: 11px;
            text-align: center;
            color: #6c757d;
            margin-top: 4px;
        }
        .step-label.active {
            color: #0f3460;
            font-weight: 600;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f3460;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 8px;
        }
        .benefits-box {
            background: #f0f4ff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .benefits-box h6 {
            color: #0f3460;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .benefits-box .benefit-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            font-size: 14px;
            color: #333;
        }
        .benefits-box .benefit-item i {
            color: #28a745;
            width: 16px;
        }
        .btn-cnpj-search {
            background: #0f3460;
            border: none;
            color: #fff;
        }
        .btn-cnpj-search:hover {
            background: #1a1a2e;
            color: #fff;
        }
        .password-rules {
            font-size: 12px;
            color: #6c757d;
        }
        .password-rules .rule {
            margin-bottom: 2px;
        }
        .password-rules .rule.valid {
            color: #28a745;
        }
        .password-rules .rule.invalid {
            color: #dc3545;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="register-card">
        <div class="logo">
            <i class="fas fa-cash-register"></i>
            <h4><?= e(APP_NAME) ?></h4>
            <small class="text-muted">Cadastre sua empresa gratuitamente</small>
        </div>

        <!-- Indicador de etapas -->
        <div class="step-indicator">
            <div class="text-center">
                <div class="step active">1</div>
                <div class="step-label active">Empresa</div>
            </div>
            <div class="d-flex align-items-center" style="margin-top:-12px;color:#ccc;">---</div>
            <div class="text-center">
                <div class="step active">2</div>
                <div class="step-label active">Acesso</div>
            </div>
            <div class="d-flex align-items-center" style="margin-top:-12px;color:#ccc;">---</div>
            <div class="text-center">
                <div class="step inactive">3</div>
                <div class="step-label">Pronto!</div>
            </div>
        </div>

        <!-- Plano selecionado -->
        <div class="benefits-box" style="border-left: 4px solid <?= $planoAtual['cor'] ?>;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0"><i class="fas fa-tag me-2"></i>Plano <?= e($planoAtual['nome']) ?></h6>
                <span class="fw-bold" style="color: <?= $planoAtual['cor'] ?>; font-size: 1.2rem;"><?= e($planoAtual['preco']) ?></span>
            </div>
            <?php if (!empty($planoAtual['beneficios'])): ?>
                <?php foreach ($planoAtual['beneficios'] as $beneficio): ?>
                    <div class="benefit-item"><i class="fas fa-check"></i> <?= e($beneficio) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            <div class="mt-2"><small class="text-muted"><i class="fas fa-gift me-1"></i>15 dias de teste grátis. O pagamento será configurado após o período de teste.</small></div>
            <div class="mt-2">
                <a href="/" class="text-decoration-none" style="font-size: 0.8rem; color: <?= $planoAtual['cor'] ?>;">
                    <i class="fas fa-arrow-left me-1"></i>Trocar plano
                </a>
            </div>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger py-2">
                <i class="fas fa-exclamation-circle me-1"></i><?= e($erro) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($erros)): ?>
            <div class="alert alert-danger py-2">
                <i class="fas fa-exclamation-circle me-1"></i>
                <strong>Corrija os seguintes erros:</strong>
                <ul class="mb-0 mt-1">
                    <?php foreach ($erros as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off" id="formRegister">
            <?= csrfField() ?>
            <input type="hidden" name="plano" value="<?= e($planoSelecionado) ?>">

            <!-- Dados da Empresa -->
            <div class="section-title"><i class="fas fa-building me-2"></i>Dados da Empresa</div>

            <div class="mb-3">
                <label for="cnpj" class="form-label fw-semibold">CNPJ <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="text" class="form-control" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00"
                           value="<?= e($dados['cnpj'] ? formatCnpj($dados['cnpj']) : '') ?>" required maxlength="18"
                           <?= $bloqueado ? 'disabled' : '' ?>>
                    <button type="button" class="btn btn-cnpj-search" id="btnBuscaCnpj" title="Buscar dados do CNPJ"
                            <?= $bloqueado ? 'disabled' : '' ?>>
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <small class="text-muted">Digite o CNPJ e clique na lupa para preencher automaticamente</small>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="nome_empresa" name="nome_empresa" placeholder="Razão Social"
                               value="<?= e($dados['nome_empresa']) ?>" required <?= $bloqueado ? 'disabled' : '' ?>>
                        <label for="nome_empresa">Razão Social <span class="text-danger">*</span></label>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="nome_fantasia" name="nome_fantasia" placeholder="Nome Fantasia"
                               value="<?= e($dados['nome_fantasia']) ?>" required <?= $bloqueado ? 'disabled' : '' ?>>
                        <label for="nome_fantasia">Nome Fantasia <span class="text-danger">*</span></label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email" placeholder="E-mail"
                               value="<?= e($dados['email']) ?>" required <?= $bloqueado ? 'disabled' : '' ?>>
                        <label for="email">E-mail <span class="text-danger">*</span></label>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="whatsapp" name="whatsapp" placeholder="WhatsApp"
                               value="<?= e($dados['whatsapp']) ?>" required maxlength="15"
                               <?= $bloqueado ? 'disabled' : '' ?>>
                        <label for="whatsapp">WhatsApp <span class="text-danger">*</span></label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8 mb-3">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="cidade" name="cidade" placeholder="Cidade"
                               value="<?= e($dados['cidade']) ?>" required <?= $bloqueado ? 'disabled' : '' ?>>
                        <label for="cidade">Cidade <span class="text-danger">*</span></label>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="form-floating">
                        <select class="form-select" id="uf" name="uf" required <?= $bloqueado ? 'disabled' : '' ?>>
                            <option value="">Selecione</option>
                            <?php foreach ($ufs as $uf): ?>
                                <option value="<?= $uf ?>" <?= $dados['uf'] === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="uf">UF <span class="text-danger">*</span></label>
                    </div>
                </div>
            </div>

            <!-- Dados de Acesso -->
            <div class="section-title mt-3"><i class="fas fa-key me-2"></i>Dados de Acesso</div>

            <div class="mb-3">
                <div class="form-floating">
                    <input type="text" class="form-control" id="nome_responsavel" name="nome_responsavel" placeholder="Nome do Responsável"
                           value="<?= e($dados['nome_responsavel']) ?>" required <?= $bloqueado ? 'disabled' : '' ?>>
                    <label for="nome_responsavel">Nome do Responsável <span class="text-danger">*</span></label>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-floating">
                    <input type="text" class="form-control" id="login" name="login" placeholder="Login"
                           value="<?= e($dados['login']) ?>" required minlength="3"
                           <?= $bloqueado ? 'disabled' : '' ?>>
                    <label for="login">Login <span class="text-danger">*</span></label>
                </div>
                <small class="text-muted">Mínimo 3 caracteres. Será usado para acessar o sistema.</small>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-floating">
                        <input type="password" class="form-control" id="senha" name="senha" placeholder="Senha"
                               required minlength="8" <?= $bloqueado ? 'disabled' : '' ?>>
                        <label for="senha">Senha <span class="text-danger">*</span></label>
                    </div>
                    <div class="password-rules mt-1">
                        <div class="rule" id="rule-length"><i class="fas fa-circle fa-xs me-1"></i>Mínimo 8 caracteres</div>
                        <div class="rule" id="rule-letter"><i class="fas fa-circle fa-xs me-1"></i>Pelo menos 1 letra</div>
                        <div class="rule" id="rule-number"><i class="fas fa-circle fa-xs me-1"></i>Pelo menos 1 número</div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-floating">
                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" placeholder="Confirmar Senha"
                               required <?= $bloqueado ? 'disabled' : '' ?>>
                        <label for="confirmar_senha">Confirmar Senha <span class="text-danger">*</span></label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-register w-100 mb-3" <?= $bloqueado ? 'disabled' : '' ?>>
                <i class="fas fa-rocket me-2"></i>Começar Teste Grátis
            </button>
        </form>

        <div class="text-center">
            <span class="text-muted">Já tem uma conta?</span>
            <a href="login.php" class="text-decoration-none" style="color: #0f3460;">
                <i class="fas fa-sign-in-alt me-1"></i>Fazer login
            </a>
        </div>

        <div class="text-center mt-3">
            <small class="text-muted"><?= e(APP_NAME) ?> v<?= APP_VERSION ?></small>
        </div>
    </div>
</div>

<script>
// Máscara de CNPJ
document.getElementById('cnpj').addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '');
    if (v.length > 14) v = v.substring(0, 14);
    if (v.length > 12) v = v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{1,2})/, '$1.$2.$3/$4-$5');
    else if (v.length > 8) v = v.replace(/^(\d{2})(\d{3})(\d{3})(\d{1,4})/, '$1.$2.$3/$4');
    else if (v.length > 5) v = v.replace(/^(\d{2})(\d{3})(\d{1,3})/, '$1.$2.$3');
    else if (v.length > 2) v = v.replace(/^(\d{2})(\d{1,3})/, '$1.$2');
    this.value = v;
});

// Máscara de WhatsApp
document.getElementById('whatsapp').addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '');
    if (v.length > 11) v = v.substring(0, 11);
    if (v.length > 6) v = v.replace(/^(\d{2})(\d{5})(\d{1,4})/, '($1) $2-$3');
    else if (v.length > 2) v = v.replace(/^(\d{2})(\d{1,5})/, '($1) $2');
    this.value = v;
});

// Busca CNPJ na BrasilAPI
document.getElementById('btnBuscaCnpj').addEventListener('click', function() {
    const cnpjField = document.getElementById('cnpj');
    const cnpj = cnpjField.value.replace(/\D/g, '');

    if (cnpj.length !== 14) {
        alert('Digite um CNPJ válido com 14 dígitos.');
        cnpjField.focus();
        return;
    }

    const btn = this;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    fetch('https://brasilapi.com.br/api/cnpj/v1/' + cnpj)
        .then(r => {
            if (!r.ok) throw new Error('CNPJ não encontrado');
            return r.json();
        })
        .then(data => {
            if (data.razao_social) document.getElementById('nome_empresa').value = data.razao_social;
            if (data.nome_fantasia) document.getElementById('nome_fantasia').value = data.nome_fantasia || data.razao_social;
            if (data.email) document.getElementById('email').value = data.email;
            if (data.ddd_telefone_1) {
                let tel = data.ddd_telefone_1.replace(/\D/g, '');
                document.getElementById('whatsapp').value = tel;
                document.getElementById('whatsapp').dispatchEvent(new Event('input'));
            }
            if (data.municipio) document.getElementById('cidade').value = data.municipio;
            if (data.uf) document.getElementById('uf').value = data.uf;
        })
        .catch(err => {
            alert('Não foi possível buscar os dados do CNPJ. Verifique o número e tente novamente.');
        })
        .finally(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
});

// Validação de senha em tempo real
document.getElementById('senha').addEventListener('input', function() {
    const val = this.value;
    const ruleLength = document.getElementById('rule-length');
    const ruleLetter = document.getElementById('rule-letter');
    const ruleNumber = document.getElementById('rule-number');

    ruleLength.className = 'rule ' + (val.length >= 8 ? 'valid' : 'invalid');
    ruleLetter.className = 'rule ' + (/[A-Za-z]/.test(val) ? 'valid' : 'invalid');
    ruleNumber.className = 'rule ' + (/[0-9]/.test(val) ? 'valid' : 'invalid');
});
</script>
</body>
</html>
