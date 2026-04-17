<?php
/**
 * AJAX - Salvar cliente rapidamente (usado em modais reutilizaveis).
 * Retorna JSON com id e nome do cliente criado/atualizado.
 */
require_once __DIR__ . '/../../app/includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Método inválido']);
    exit;
}

$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['ok' => false, 'msg' => 'Token CSRF inválido']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);

$data = [
    'nome'        => sanitize($_POST['nome'] ?? ''),
    'cpf_cnpj'    => limparCpfCnpj($_POST['cpf_cnpj'] ?? ''),
    'rg_ie'       => sanitize($_POST['rg_ie'] ?? ''),
    'telefone'    => sanitize($_POST['telefone'] ?? ''),
    'celular'     => sanitize($_POST['celular'] ?? ''),
    'email'       => sanitize($_POST['email'] ?? ''),
    'cep'         => sanitize($_POST['cep'] ?? ''),
    'endereco'    => sanitize($_POST['endereco'] ?? ''),
    'numero'      => sanitize($_POST['numero'] ?? ''),
    'complemento' => sanitize($_POST['complemento'] ?? ''),
    'bairro'      => sanitize($_POST['bairro'] ?? ''),
    'cidade'      => sanitize($_POST['cidade'] ?? ''),
    'estado'      => sanitize($_POST['estado'] ?? ''),
    'observacoes' => sanitize($_POST['observacoes'] ?? ''),
];

if (empty($data['nome'])) {
    echo json_encode(['ok' => false, 'msg' => 'O nome é obrigatório']);
    exit;
}

try {
    if ($id > 0) {
        $existing = tenantFind('clientes', $id);
        if (!$existing) {
            echo json_encode(['ok' => false, 'msg' => 'Cliente não encontrado']);
            exit;
        }
        tenantUpdate('clientes', $data, $id);
    } else {
        $id = tenantInsert('clientes', $data);
    }

    $label = $data['nome'];
    if ($data['cpf_cnpj']) $label .= ' - ' . formatDoc($data['cpf_cnpj']);

    echo json_encode(['ok' => true, 'id' => $id, 'nome' => $data['nome'], 'label' => $label]);
} catch (Exception $e) {
    error_log('Erro salvar cliente ajax: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Erro ao salvar cliente']);
}
