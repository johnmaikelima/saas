<?php
/**
 * API - Criar categoria via AJAX
 */
require_once __DIR__ . '/../../app/includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensagem' => 'Método não permitido']);
    exit;
}

if (!verifyCsrf()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensagem' => 'Token inválido']);
    exit;
}

$nome = sanitize($_POST['nome'] ?? '');
$cor = sanitize($_POST['cor'] ?? '#6c757d');
$icone = sanitize($_POST['icone'] ?? '');

if (empty($nome)) {
    echo json_encode(['ok' => false, 'mensagem' => 'O nome da categoria é obrigatório.']);
    exit;
}

try {
    $id = tenantInsert('categorias', [
        'nome'  => $nome,
        'cor'   => $cor,
        'icone' => $icone,
        'ativo' => 1,
    ]);

    echo json_encode([
        'ok'   => true,
        'id'   => $id,
        'nome' => $nome,
        'cor'  => $cor,
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensagem' => 'Erro ao salvar categoria.']);
}
