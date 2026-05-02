<?php
/**
 * Login automático via token temporário (impersonate)
 * Chamado pelo Painel Admin para acessar o SaaS de um cliente
 */
require_once __DIR__ . '/../../app/bootstrap.php';

$token = $_GET['token'] ?? '';
if (empty($token) || strlen($token) !== 64) {
    http_response_code(403);
    die('Token inválido.');
}

$pdo = db();

// Buscar token válido (expira em 5 minutos)
$stmt = $pdo->prepare("SELECT * FROM impersonate_tokens WHERE token = ? AND usado = 0 AND expira_em > NOW()");
$stmt->execute([$token]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(403);
    die('Token inválido ou expirado.');
}

// Marcar como usado
$pdo->prepare("UPDATE impersonate_tokens SET usado = 1, usado_em = NOW(), ip_usado = ? WHERE id = ?")->execute([
    $_SERVER['REMOTE_ADDR'] ?? '', $row['id']
]);

// Buscar tenant e usuário admin
$tenant = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
$tenant->execute([$row['tenant_id']]);
$tenant = $tenant->fetch();

if (!$tenant) {
    die('Empresa não encontrada.');
}

$user = $pdo->prepare("SELECT * FROM usuarios WHERE tenant_id = ? AND perfil = 'admin' AND ativo = 1 ORDER BY id ASC LIMIT 1");
$user->execute([$row['tenant_id']]);
$user = $user->fetch();

if (!$user) {
    die('Nenhum usuário admin encontrado para esta empresa.');
}

// Definir dados da sessão
$_SESSION['usuario'] = [
    'id' => $user['id'],
    'nome' => $user['nome'],
    'login' => $user['login'],
    'perfil' => $user['perfil'],
    'trocar_senha' => false,
];
$_SESSION['tenant_id'] = $tenant['id'];
$_SESSION['impersonate'] = true; // Flag para indicar acesso administrativo

// Inicializar fingerprint da sessão para impersonate
$fingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
$_SESSION['_fingerprint'] = $fingerprint;
$_SESSION['_last_activity'] = time();

// Agora regenerar a sessão APÓS definir os dados
regenerateSession();

// Registrar auditlog (depois, fora da sessão pode falhar)
try {
    auditLog('impersonate', 'Acesso administrativo via Painel', $tenant['id'], $user['id']);
} catch (\Throwable $e) {
    // Falha silenciosa - não bloqueia o login
}

// Redirecionar com headers seguros
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Location: ' . APP_URL . '/dashboard/index.php', true, 302);
exit;
