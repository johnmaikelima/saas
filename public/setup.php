<?php
/**
 * Setup inicial do SaaS PDV Pro
 * Execute uma vez para criar as tabelas e o primeiro tenant/admin.
 * Protegido por SETUP_KEY.
 */

require_once __DIR__ . '/../app/config.php';

// Verificar chave de setup
$key = $_GET['key'] ?? '';
if (empty($key) || $key !== SETUP_KEY) {
    http_response_code(403);
    echo '<h1>Acesso negado</h1><p>Informe a chave de setup: <code>setup.php?key=SUA_CHAVE</code></p>';
    exit;
}

$messages = [];
$error = false;

try {
    // Conectar ao banco
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Criar banco se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    $messages[] = "Banco de dados '{$_ENV['DB_NAME']}' verificado/criado.";

    // Executar migrations
    $migrationsPath = __DIR__ . '/../app/migrations/';
    $files = glob($migrationsPath . '*.sql');
    sort($files);

    foreach ($files as $file) {
        $sql = file_get_contents($file);
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $stmt) {
            if (empty($stmt)) continue;
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                // Ignorar erros de "já existe"
                if (!str_contains($e->getMessage(), 'already exists') && !str_contains($e->getMessage(), 'Duplicate column')) {
                    $messages[] = "Aviso: " . $e->getMessage();
                }
            }
        }
        $messages[] = "Migration " . basename($file) . " executada.";
    }

    // Criar tenant padrão se não existir
    $stmt = $pdo->query("SELECT COUNT(*) FROM tenants");
    $count = (int)$stmt->fetchColumn();

    if ($count === 0) {
        $pdo->exec("INSERT INTO tenants (nome_fantasia, razao_social, cnpj, status, plano, criado_em)
            VALUES ('Minha Loja', 'Minha Loja LTDA', '', 'ativo', 'trial', NOW())");
        $tenantId = (int)$pdo->lastInsertId();
        $messages[] = "Tenant padrão criado (ID: {$tenantId}).";

        // Criar usuário admin
        $senhaHash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare("INSERT INTO usuarios (tenant_id, nome, login, email, senha_hash, perfil, ativo, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())")
            ->execute([$tenantId, 'Administrador', 'admin', 'admin@pdvpro.com', $senhaHash, 'admin', 1]);
        $messages[] = "Usuário admin criado: login: admin / senha: admin123";
        $messages[] = "<strong class='text-danger'>IMPORTANTE: Troque a senha do admin no primeiro acesso!</strong>";
    } else {
        $messages[] = "Tenants já existem ({$count}). Nenhum tenant/usuário criado.";
    }

    // Criar diretórios
    $dirs = [UPLOAD_PATH . '/logos', STORAGE_PATH . '/logs'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            $messages[] = "Diretório criado: {$dir}";
        }
    }

    $messages[] = "<strong class='text-success'>Setup concluído com sucesso!</strong>";

} catch (PDOException $e) {
    $error = true;
    $messages[] = "<strong class='text-danger'>Erro: " . htmlspecialchars($e->getMessage()) . "</strong>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - PDV Pro SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5" style="max-width:600px;">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-cog me-2"></i>Setup - PDV Pro SaaS</h4>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php foreach ($messages as $msg): ?>
                        <li class="list-group-item"><?= $msg ?></li>
                    <?php endforeach; ?>
                </ul>

                <?php if (!$error): ?>
                    <div class="alert alert-info mt-3 mb-0">
                        <strong>Próximo passo:</strong> Acesse <a href="auth/login.php">o login</a> com as credenciais acima.
                        <br><small>Apague ou renomeie este arquivo após o setup.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
