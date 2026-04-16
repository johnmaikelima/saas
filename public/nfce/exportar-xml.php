<?php
require_once __DIR__ . '/../../app/includes/auth.php';
if (!temPerfil('admin', 'gerente')) { flashError('Sem permissão.'); redirect('dashboard/'); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('nfce/'); }
if (!verifyCsrf()) { flashError('Token inválido.'); redirect('nfce/'); }

$ids = sanitize($_POST['ids'] ?? '');
if (empty($ids)) { flashError('Nenhuma nota selecionada.'); redirect('nfce/'); }

// Validar e filtrar IDs (apenas inteiros positivos)
$idArray = array_filter(array_map('intval', explode(',', $ids)), fn($id) => $id > 0);
if (empty($idArray)) { flashError('IDs inválidos.'); redirect('nfce/'); }

$pdo = db();
$tid = tenantId();

// Buscar XMLs autorizados do tenant
$placeholders = implode(',', array_fill(0, count($idArray), '?'));
$stmt = $pdo->prepare("
    SELECT id, numero, serie, chave_acesso, xml_autorizado
    FROM nfce
    WHERE tenant_id = ? AND id IN ({$placeholders}) AND status = 'autorizada' AND xml_autorizado IS NOT NULL
");
$stmt->execute(array_merge([$tid], $idArray));
$notas = $stmt->fetchAll();

if (empty($notas)) {
    flashError('Nenhum XML autorizado encontrado para as notas selecionadas.');
    redirect('nfce/');
}

// Criar ZIP em memória
$tmpFile = tempnam(sys_get_temp_dir(), 'nfce_xml_');
$zip = new ZipArchive();
if ($zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
    flashError('Erro ao criar arquivo ZIP.');
    redirect('nfce/');
}

foreach ($notas as $nota) {
    $filename = "NFCe_{$nota['numero']}_{$nota['serie']}_{$nota['chave_acesso']}.xml";
    $zip->addFromString($filename, $nota['xml_autorizado']);
}

$zip->close();

// Enviar download
$dataExport = date('Y-m-d_His');
header('Content-Type: application/zip');
header("Content-Disposition: attachment; filename=\"nfce_xml_{$dataExport}.zip\"");
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: no-cache, must-revalidate');
readfile($tmpFile);
unlink($tmpFile);
exit;
