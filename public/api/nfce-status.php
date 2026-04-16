<?php
/**
 * API - Verificar status do serviço SEFAZ
 */
require_once __DIR__ . '/../../app/includes/auth.php';

header('Content-Type: application/json');

if (!temPerfil('admin')) {
    http_response_code(403);
    echo json_encode(['online' => false, 'mensagem' => 'Sem permissao']);
    exit;
}

try {
    $nfce = new NfceHelper(tenantId());
    $result = $nfce->statusServico();
    echo json_encode($result);
} catch (\Exception $e) {
    echo json_encode(['online' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
}
