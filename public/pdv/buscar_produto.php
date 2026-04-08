<?php
/**
 * AJAX - Busca de produto por codigo de barras, codigo interno ou nome
 * SaaS - Filtrado por tenant_id
 */
require_once __DIR__ . '/../../app/includes/auth.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Não autenticado']);
    exit;
}

header('Content-Type: application/json');

$termo = sanitize($_GET['q'] ?? '');
if (strlen($termo) < 1) {
    echo json_encode([]);
    exit;
}

$pdo = db();
$tid = tenantId();

// Buscar por codigo de barras exato primeiro
$stmt = $pdo->prepare("SELECT p.*, c.nome as categoria_nome, c.cor as categoria_cor
    FROM produtos p LEFT JOIN categorias c ON c.id = p.categoria_id AND c.tenant_id = ?
    WHERE p.tenant_id = ? AND p.ativo = 1 AND p.codigo_barras = ?");
$stmt->execute([$tid, $tid, $termo]);
$exato = $stmt->fetchAll();

if (!empty($exato)) {
    echo json_encode($exato);
    exit;
}

// Busca por ID, codigo de barras parcial ou nome
$like = "%{$termo}%";
$stmt = $pdo->prepare("SELECT p.*, c.nome as categoria_nome, c.cor as categoria_cor
    FROM produtos p LEFT JOIN categorias c ON c.id = p.categoria_id AND c.tenant_id = ?
    WHERE p.tenant_id = ? AND p.ativo = 1 AND (
        CAST(p.id AS CHAR) = ? OR p.codigo_barras LIKE ? OR p.descricao LIKE ?
    ) ORDER BY p.descricao ASC LIMIT 20");
$stmt->execute([$tid, $tid, $termo, $like, $like]);
echo json_encode($stmt->fetchAll());
