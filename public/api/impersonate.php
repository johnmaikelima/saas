<?php
/**
 * API - Gerar token de impersonate
 * Chamado pelo Painel Admin com API_SECRET
 */
require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensagem' => 'Método não permitido']);
    exit;
}

// Autenticar via API_SECRET
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$secret = $input['api_secret'] ?? ($_SERVER['HTTP_X_API_SECRET'] ?? '');

if (empty(PAINEL_API_SECRET) || empty($secret) || !hash_equals(PAINEL_API_SECRET, $secret)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensagem' => 'Acesso não autorizado']);
    exit;
}

$licencaChave = $input['licenca_chave'] ?? '';
if (empty($licencaChave)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensagem' => 'Licença obrigatória']);
    exit;
}

$pdo = db();

// Buscar tenant pela licença
$stmt = $pdo->prepare("SELECT id FROM tenants WHERE licenca_chave = ? AND status = 'ativo'");
$stmt->execute([$licencaChave]);
$tenant = $stmt->fetch();

if (!$tenant) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'mensagem' => 'Tenant não encontrado']);
    exit;
}

// Gerar token único
$token = bin2hex(random_bytes(32));

// Limpar tokens expirados
$pdo->prepare("DELETE FROM impersonate_tokens WHERE expira_em < NOW()")->execute();

// Criar token (expira em 5 minutos)
$pdo->prepare("INSERT INTO impersonate_tokens (tenant_id, token, expira_em) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))")
    ->execute([$tenant['id'], $token]);

$url = rtrim(APP_URL, '/') . '/auth/impersonate.php?token=' . $token;

echo json_encode([
    'ok' => true,
    'url' => $url,
]);
