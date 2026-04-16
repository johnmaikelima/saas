<?php
/**
 * Endpoint para limpar cache de planos.
 * Chamado pelo Painel Admin ao editar planos.
 * Protegido por PAINEL_API_SECRET.
 */
require_once __DIR__ . '/../../app/config.php';

header('Content-Type: application/json; charset=utf-8');

// Validar secret
$secret = $_SERVER['HTTP_X_API_SECRET'] ?? $_GET['secret'] ?? '';
if (empty(PAINEL_API_SECRET) || empty($secret) || !hash_equals(PAINEL_API_SECRET, $secret)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Acesso negado']);
    exit;
}

$removidos = [];
$cacheFiles = glob(STORAGE_PATH . '/cache_planos*.json');

foreach ($cacheFiles as $file) {
    if (file_exists($file)) {
        unlink($file);
        $removidos[] = basename($file);
    }
}

echo json_encode([
    'ok' => true,
    'msg' => 'Cache limpo com sucesso',
    'arquivos' => $removidos,
]);
