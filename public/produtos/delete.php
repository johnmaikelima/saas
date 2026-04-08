<?php
require_once __DIR__ . '/../../app/includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$csrf = $_GET['csrf'] ?? '';

if ($id <= 0) {
    flashError('Produto inválido.');
    redirect('produtos/');
}

if (empty($csrf) || !hash_equals(csrfToken(), $csrf)) {
    flashError('Token inválido.');
    redirect('produtos/');
}

$produto = tenantFind('produtos', $id);
if (!$produto) {
    flashError('Produto não encontrado.');
    redirect('produtos/');
}

tenantDelete('produtos', $id);
flashSuccess('Produto excluído com sucesso!');
redirect('produtos/');
