<?php
/**
 * Gerar e exibir DANFCE (PDF) de uma NFC-e autorizada
 */
require_once __DIR__ . '/../../app/includes/auth.php';

$chave = sanitize($_GET['chave'] ?? '');
if (empty($chave) || strlen($chave) !== 44) {
    flashError('Chave de acesso inválida.');
    redirect('nfce/');
}

$tid = tenantId();

try {
    $nfceHelper = new NfceHelper($tid);
    $xml = $nfceHelper->getXmlAutorizado($chave);

    if (!$xml) {
        flashError('XML autorizado não encontrado para esta chave.');
        redirect('nfce/');
    }

    $pdf = $nfceHelper->gerarDanfce($xml);

    if (!$pdf) {
        flashError('Erro ao gerar DANFCE.');
        redirect('nfce/');
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="DANFCE_' . $chave . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;

} catch (\Exception $e) {
    error_log('Erro DANFCE: ' . $e->getMessage());
    flashError('Erro ao gerar DANFCE: ' . $e->getMessage());
    redirect('nfce/');
}
