<?php
$pageTitle = 'Login';
require_once __DIR__ . '/../../app/bootstrap.php';
autoMigrate();

// Já logado?
if (isset($_SESSION['usuario']) && isset($_SESSION['tenant_id'])) {
    if (($_SESSION['usuario']['perfil'] ?? '') === 'caixa') {
        redirect('pdv/');
    }
    redirect('dashboard/');
}

$erro = '';
$bloqueado = false;

// Verificar rate limit
if (rateLimited('login')) {
    $bloqueado = true;
    $erro = 'Muitas tentativas de login. Aguarde alguns minutos antes de tentar novamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$bloqueado) {
    if (!verifyCsrf()) {
        $erro = 'Token de segurança inválido. Recarregue a página.';
    } else {
        $login = sanitize($_POST['login'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if (empty($login) || empty($senha)) {
            $erro = 'Preencha login e senha.';
        } else {
            $stmt = db()->prepare("SELECT u.*, t.status as tenant_status FROM usuarios u JOIN tenants t ON t.id = u.tenant_id WHERE u.login = ? AND u.ativo = 1");
            $stmt->execute([$login]);
            $user = $stmt->fetch();

            if ($user && verifyPassword($senha, $user['senha_hash'])) {
                // Verificar se o tenant está ativo
                if ($user['tenant_status'] !== 'ativo') {
                    $erro = 'Sua empresa está com o acesso suspenso. Entre em contato com o suporte.';
                    rateLimitHit('login');
                } else {
                    // Verificar licença no Painel (se tenant tem chave)
                    $tenant = db()->prepare("SELECT licenca_chave, data_vencimento FROM tenants WHERE id = ?");
                    $tenant->execute([$user['tenant_id']]);
                    $tenantData = $tenant->fetch();

                    if (!empty($tenantData['licenca_chave'])) {
                        // Validar com o Painel (em background, não bloquear login se API falhar)
                        try {
                            $validaData = json_encode([
                                'api_secret' => PAINEL_API_SECRET,
                                'chave'      => $tenantData['licenca_chave'],
                            ]);
                            $ch = curl_init(PAINEL_API_URL . '?action=validar_saas');
                            curl_setopt_array($ch, [
                                CURLOPT_POST           => true,
                                CURLOPT_POSTFIELDS     => $validaData,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                                CURLOPT_TIMEOUT        => 5,
                                CURLOPT_SSL_VERIFYPEER => true,
                            ]);
                            $validaResult = curl_exec($ch);
                            curl_close($ch);

                            if ($validaResult) {
                                $validaResponse = json_decode($validaResult, true);
                                if (isset($validaResponse['ok']) && !$validaResponse['ok']) {
                                    $status = $validaResponse['status'] ?? '';
                                    if ($status === 'expirada') {
                                        $erro = 'Seu período de teste expirou. Entre em contato para ativar um plano.';
                                        rateLimitHit('login');
                                        // Atualizar status local
                                        db()->prepare("UPDATE tenants SET status = 'suspenso' WHERE id = ?")->execute([$user['tenant_id']]);
                                    } elseif ($status === 'bloqueada') {
                                        $erro = 'Sua licença foi bloqueada. Entre em contato com o suporte.';
                                        rateLimitHit('login');
                                    } elseif ($status === 'inadimplente') {
                                        $erro = 'Conta inadimplente. Regularize seu pagamento para continuar.';
                                        rateLimitHit('login');
                                    }
                                    if (!empty($erro)) {
                                        // Não prosseguir com login - ir direto para o else final
                                    }
                                } elseif (isset($validaResponse['ok']) && $validaResponse['ok']) {
                                    // Atualizar data de vencimento local
                                    if (!empty($validaResponse['data_vencimento'])) {
                                        db()->prepare("UPDATE tenants SET data_vencimento = ? WHERE id = ?")
                                            ->execute([$validaResponse['data_vencimento'], $user['tenant_id']]);
                                    }
                                }
                            }
                        } catch (\Throwable $e) {
                            // Se API falhar, verificar vencimento local
                            if (!empty($tenantData['data_vencimento']) && strtotime($tenantData['data_vencimento']) < time()) {
                                $erro = 'Seu período de teste expirou. Entre em contato para ativar um plano.';
                                rateLimitHit('login');
                            }
                        }
                    } elseif (!empty($tenantData['data_vencimento']) && strtotime($tenantData['data_vencimento']) < time()) {
                        // Sem chave mas com vencimento local
                        $erro = 'Seu período de teste expirou. Entre em contato para ativar um plano.';
                        rateLimitHit('login');
                    }

                    if (empty($erro)) {
                        // Login bem-sucedido
                        rateLimitClear('login');
                        regenerateSession();

                        $_SESSION['usuario'] = [
                            'id' => $user['id'],
                            'nome' => $user['nome'],
                            'login' => $user['login'],
                            'perfil' => $user['perfil'],
                            'trocar_senha' => (bool) $user['trocar_senha'],
                        ];
                        $_SESSION['tenant_id'] = (int) $user['tenant_id'];

                        // Fingerprint da sessão
                        validateSession();

                        // Atualizar último acesso
                        db()->prepare("UPDATE usuarios SET ultimo_acesso = ? WHERE id = ?")
                            ->execute([date('Y-m-d H:i:s'), $user['id']]);

                        auditLog('login', 'Login bem-sucedido');

                        if ($user['trocar_senha']) {
                            redirect('auth/trocar_senha.php');
                        } else {
                            // Operador caixa vai direto para PDV (abrir caixa se não estiver aberto)
                            if ($user['perfil'] === 'caixa') {
                                redirect('pdv/');
                            } else {
                                redirect('dashboard/');
                            }
                        }
                    }
                }
            } else {
                rateLimitHit('login');
                $erro = 'Login ou senha incorretos.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 420px;
            width: 100%;
            padding: 40px;
        }
        .login-card .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-card .logo i {
            font-size: 48px;
            color: #0f3460;
        }
        .login-card .logo h4 {
            color: #1a1a2e;
            margin-top: 10px;
            font-weight: 700;
        }
        .form-floating > .form-control:focus {
            border-color: #0f3460;
            box-shadow: 0 0 0 0.2rem rgba(15,52,96,0.25);
        }
        .btn-login {
            background: #0f3460;
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
        }
        .btn-login:hover {
            background: #1a1a2e;
        }
        .alert-rate-limit {
            background: #fff3cd;
            border-color: #ffecb5;
            color: #664d03;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="logo">
        <i class="fas fa-cash-register"></i>
        <h4><?= e(APP_NAME) ?></h4>
        <small class="text-muted">Sistema PDV - Acesso Seguro</small>
    </div>

    <?php if ($erro): ?>
        <div class="alert <?= $bloqueado ? 'alert-warning alert-rate-limit' : 'alert-danger' ?> py-2">
            <i class="fas <?= $bloqueado ? 'fa-clock' : 'fa-exclamation-circle' ?> me-1"></i><?= e($erro) ?>
        </div>
    <?php endif; ?>

    <?= renderFlash() ?>

    <form method="POST" autocomplete="off">
        <?= csrfField() ?>
        <div class="form-floating mb-3">
            <input type="text" class="form-control" id="login" name="login" placeholder="Login"
                   value="<?= e($_POST['login'] ?? '') ?>" autofocus required <?= $bloqueado ? 'disabled' : '' ?>>
            <label for="login"><i class="fas fa-user me-1"></i>Login</label>
        </div>
        <div class="form-floating mb-3">
            <input type="password" class="form-control" id="senha" name="senha" placeholder="Senha"
                   required <?= $bloqueado ? 'disabled' : '' ?>>
            <label for="senha"><i class="fas fa-lock me-1"></i>Senha</label>
        </div>
        <button type="submit" class="btn btn-primary btn-login w-100 mb-3" <?= $bloqueado ? 'disabled' : '' ?>>
            <i class="fas fa-sign-in-alt me-2"></i>Entrar
        </button>
    </form>

    <div class="text-center">
        <a href="register.php" class="text-decoration-none" style="color: #0f3460;">
            <i class="fas fa-building me-1"></i>Cadastrar nova empresa
        </a>
    </div>

    <div class="text-center mt-3">
        <small class="text-muted"><?= e(APP_NAME) ?> v<?= APP_VERSION ?></small>
    </div>
</div>
</body>
</html>
