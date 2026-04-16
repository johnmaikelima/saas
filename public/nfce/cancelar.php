<?php
$pageTitle = 'Cancelar NFC-e';
require_once __DIR__ . '/../../app/includes/auth.php';
if (!temPerfil('admin')) { flashError('Sem permissão.'); redirect('nfce/'); }

$id = (int)($_GET['id'] ?? 0);
$nfce = tenantFind('nfce', $id);

if (!$nfce || $nfce['status'] !== 'autorizada') {
    flashError('NFC-e não encontrada ou não pode ser cancelada.');
    redirect('nfce/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { flashError('Token inválido.'); redirect('nfce/cancelar.php?id=' . $id); }

    $justificativa = sanitize($_POST['justificativa'] ?? '');

    if (strlen($justificativa) < 15) {
        flashError('Justificativa deve ter no mínimo 15 caracteres.');
        redirect('nfce/cancelar.php?id=' . $id);
    }

    $nfceHelper = new NfceHelper(tenantId());
    $result = $nfceHelper->cancelar($id, $justificativa);

    if ($result['ok']) {
        auditLog('nfce_cancelar', "NFC-e #{$nfce['numero']} cancelada. Chave: {$nfce['chave_acesso']}");
        flashSuccess($result['msg']);
    } else {
        flashError($result['msg']);
    }

    redirect('nfce/');
}

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-ban me-2"></i>Cancelar NFC-e</h5>

<div class="card shadow-sm" style="max-width: 600px;">
    <div class="card-body">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Atenção:</strong> O cancelamento é irreversível. A NFC-e deve ser cancelada em até 24 horas após a emissão.
        </div>

        <table class="table table-sm mb-4">
            <tr><th>Número:</th><td><?= $nfce['numero'] ?> / Série <?= $nfce['serie'] ?></td></tr>
            <tr><th>Chave:</th><td><small><?= e($nfce['chave_acesso']) ?></small></td></tr>
            <tr><th>Protocolo:</th><td><?= e($nfce['protocolo']) ?></td></tr>
            <tr><th>Data Emissão:</th><td><?= formatDateTime($nfce['criado_em']) ?></td></tr>
        </table>

        <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Justificativa do Cancelamento</label>
                <textarea name="justificativa" class="form-control" rows="3" required minlength="15"
                          placeholder="Mínimo 15 caracteres. Ex: Cancelamento solicitado pelo cliente"></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Confirma o cancelamento desta NFC-e?')">
                    <i class="fas fa-ban me-1"></i>Cancelar NFC-e
                </button>
                <a href="<?= baseUrl('nfce/') ?>" class="btn btn-secondary">Voltar</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
