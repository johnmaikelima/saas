<?php
/**
 * Endpoint para alterar senha de usuário SaaS.
 * Chamado pelo Painel Admin.
 * Protegido por PAINEL_API_SECRET.
 */
require_once __DIR__ . '/../../app/config.php';

header('Content-Type: application/json; charset=utf-8');

// Validar secret
$secret = $_SERVER['HTTP_X_API_SECRET'] ?? '';
if (empty(PAINEL_API_SECRET) || empty($secret) || !hash_equals(PAINEL_API_SECRET, $secret)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Acesso negado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Metodo nao permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$licencaChave = trim($input['licenca_chave'] ?? '');
$novaSenha = $input['nova_senha'] ?? '';

if (empty($licencaChave) || empty($novaSenha)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'licenca_chave e nova_senha sao obrigatorios']);
    exit;
}

if (strlen($novaSenha) < 8) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Senha deve ter no minimo 8 caracteres']);
    exit;
}

try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Buscar tenant pela licença
    $stmt = $pdo->prepare("SELECT id FROM tenants WHERE licenca_chave = ? LIMIT 1");
    $stmt->execute([$licencaChave]);
    $tenant = $stmt->fetch();

    if (!$tenant) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Tenant nao encontrado para esta licenca']);
        exit;
    }

    // Buscar usuário admin do tenant
    $stmt = $pdo->prepare("SELECT id, login FROM usuarios WHERE tenant_id = ? AND perfil = 'admin' ORDER BY id ASC LIMIT 1");
    $stmt->execute([$tenant['id']]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Usuario admin nao encontrado']);
        exit;
    }

    // Alterar senha
    $senhaHash = password_hash($novaSenha, PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare("UPDATE usuarios SET senha_hash = ? WHERE id = ?")->execute([$senhaHash, $usuario['id']]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Senha alterada com sucesso',
        'login' => $usuario['login'],
    ]);

} catch (\PDOException $e) {
    error_log('Alterar senha API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Erro interno']);
}
