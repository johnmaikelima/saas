<?php
$pageTitle = 'Trocar Senha';
require_once __DIR__ . '/../../app/bootstrap.php';
autoMigrate();

// Deve estar logado
if (!isset($_SESSION['usuario']) || !isset($_SESSION['tenant_id'])) {
    redirect('auth/login.php');
}

// Validar sessão
if (!validateSession()) {
    redirect('auth/login.php');
}

$user = $_SESSION['usuario'];
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $erro = 'Token de segurança inválido. Recarregue a página.';
    } else {
        $senhaAtual = $_POST['senha_atual'] ?? '';
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';

        if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
            $erro = 'Preencha todos os campos.';
        } elseif (!validatePassword($novaSenha)) {
            $erro = 'A nova senha deve ter no mínimo 8 caracteres, com pelo menos 1 letra e 1 número.';
        } elseif ($novaSenha !== $confirmarSenha) {
            $erro = 'A confirmação da senha não confere.';
        } else {
            // Buscar senha atual do banco
            $stmt = db()->prepare("SELECT senha_hash FROM usuarios WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$user['id'], tenantId()]);
            $row = $stmt->fetch();

            if (!$row || !verifyPassword($senhaAtual, $row['senha_hash'])) {
                $erro = 'Senha atual incorreta.';
            } else {
                // Atualizar senha
                $novaHash = hashPassword($novaSenha);
                $stmt = db()->prepare("UPDATE usuarios SET senha_hash = ?, trocar_senha = 0 WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$novaHash, $user['id'], tenantId()]);

                // Atualizar sessão
                $_SESSION['usuario']['trocar_senha'] = false;

                auditLog('trocar_senha', 'Senha alterada');

                flashSuccess('Senha alterada com sucesso!');
                redirect('dashboard/');
            }
        }
    }
}

$obrigatoria = $user['trocar_senha'] ?? false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trocar Senha - <?= e(APP_NAME) ?></title>
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
        .password-card {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 460px;
            width: 100%;
            padding: 40px;
        }
        .password-card .logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .password-card .logo i {
            font-size: 42px;
            color: #0f3460;
        }
        .password-card .logo h4 {
            color: #1a1a2e;
            margin-top: 10px;
            font-weight: 700;
        }
        .form-floating > .form-control:focus {
            border-color: #0f3460;
            box-shadow: 0 0 0 0.2rem rgba(15,52,96,0.25);
        }
        .btn-save {
            background: #0f3460;
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
        }
        .btn-save:hover {
            background: #1a1a2e;
        }
        .password-rules {
            font-size: 12px;
            color: #6c757d;
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
<div class="password-card">
    <div class="logo">
        <i class="fas fa-key"></i>
        <h4>Trocar Senha</h4>
        <?php if ($obrigatoria): ?>
            <div class="alert alert-warning py-2 mt-3 mb-0">
                <i class="fas fa-exclamation-triangle me-1"></i>
                Você precisa trocar sua senha antes de continuar.
            </div>
        <?php else: ?>
            <small class="text-muted">Altere sua senha de acesso</small>
        <?php endif; ?>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-danger py-2">
            <i class="fas fa-exclamation-circle me-1"></i><?= e($erro) ?>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <?= csrfField() ?>

        <div class="form-floating mb-3">
            <input type="password" class="form-control" id="senha_atual" name="senha_atual"
                   placeholder="Senha Atual" required autofocus>
            <label for="senha_atual"><i class="fas fa-lock me-1"></i>Senha Atual</label>
        </div>

        <div class="form-floating mb-2">
            <input type="password" class="form-control" id="nova_senha" name="nova_senha"
                   placeholder="Nova Senha" required minlength="8">
            <label for="nova_senha"><i class="fas fa-lock me-1"></i>Nova Senha</label>
        </div>
        <div class="password-rules mb-3">
            <div class="rule" id="rule-length"><i class="fas fa-circle fa-xs me-1"></i>Mínimo 8 caracteres</div>
            <div class="rule" id="rule-letter"><i class="fas fa-circle fa-xs me-1"></i>Pelo menos 1 letra</div>
            <div class="rule" id="rule-number"><i class="fas fa-circle fa-xs me-1"></i>Pelo menos 1 número</div>
        </div>

        <div class="form-floating mb-3">
            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha"
                   placeholder="Confirmar Nova Senha" required>
            <label for="confirmar_senha"><i class="fas fa-lock me-1"></i>Confirmar Nova Senha</label>
        </div>

        <button type="submit" class="btn btn-primary btn-save w-100 mb-3">
            <i class="fas fa-check me-2"></i>Alterar Senha
        </button>

        <?php if (!$obrigatoria): ?>
            <div class="text-center">
                <a href="<?= e(APP_URL) ?>/dashboard/" class="text-decoration-none" style="color: #0f3460;">
                    <i class="fas fa-arrow-left me-1"></i>Voltar ao sistema
                </a>
            </div>
        <?php else: ?>
            <div class="text-center">
                <a href="logout.php" class="text-decoration-none text-muted">
                    <i class="fas fa-sign-out-alt me-1"></i>Sair
                </a>
            </div>
        <?php endif; ?>
    </form>

    <div class="text-center mt-3">
        <small class="text-muted"><?= e(APP_NAME) ?> v<?= APP_VERSION ?></small>
    </div>
</div>

<script>
document.getElementById('nova_senha').addEventListener('input', function() {
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
