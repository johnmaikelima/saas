<?php
/**
 * PDF / pagina de impressao de orcamento.
 * Sem lib externa: gera HTML otimizado para impressao (Ctrl+P -> Salvar como PDF).
 */
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$tid = tenantId();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT o.*, u.nome as vendedor_nome,
    c.nome as cliente_nome, c.cpf_cnpj as cliente_doc, c.telefone as cliente_tel,
    c.celular as cliente_cel, c.email as cliente_email, c.endereco as cliente_endereco,
    c.cidade as cliente_cidade, c.estado as cliente_estado
    FROM orcamentos o
    LEFT JOIN usuarios u ON u.id = o.usuario_id
    LEFT JOIN clientes c ON c.id = o.cliente_id
    WHERE o.id = ? AND o.tenant_id = ?");
$stmt->execute([$id, $tid]);
$orc = $stmt->fetch();
if (!$orc) {
    http_response_code(404);
    exit('Orçamento não encontrado');
}

$stmtI = $pdo->prepare("SELECT oi.* FROM orcamento_itens oi
    WHERE oi.orcamento_id = ? AND oi.tenant_id = ?
    ORDER BY oi.ordem, oi.id");
$stmtI->execute([$id, $tid]);
$itens = $stmtI->fetchAll();

$empresa = getEmpresa();
$logoUrl = !empty($empresa['logo']) ? baseUrl('uploads/' . $empresa['logo']) : '';

include __DIR__ . '/_template_pdf.php';
