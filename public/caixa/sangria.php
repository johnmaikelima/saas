<?php
$pageTitle = 'Sangria';
require_once __DIR__ . '/../../app/includes/auth.php';

$tid = tenantId();
$caixa = getCaixaAberto();
if (!$caixa) {
    flashWarning('Abra o caixa primeiro.');
    redirect('caixa/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flashError('Token inválido.');
        redirect('caixa/sangria.php');
    }

    $valor = (float)($_POST['valor'] ?? 0);
    $motivo = sanitize($_POST['motivo'] ?? '');

    if ($valor <= 0) {
        flashError('Valor deve ser maior que zero.');
    } elseif (empty($motivo)) {
        flashError('Motivo é obrigatório.');
    } else {
        db()->prepare("INSERT INTO caixa_movimentacoes (tenant_id, caixa_id, tipo, valor, motivo, usuario_id, criado_em) VALUES (?,?,?,?,?,?,NOW())")
            ->execute([$tid, $caixa['id'], 'sangria', $valor, $motivo, usuario()['id']]);

        auditLog('sangria', 'Sangria de ' . formatMoney($valor) . ' no Caixa #' . $caixa['id']);
        flashSuccess('Sangria de ' . formatMoney($valor) . ' registrada.');
        redirect('caixa/');
    }
}

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-arrow-up me-2 text-warning"></i>Sangria - Caixa #<?= $caixa['id'] ?></h5>

<div class="card shadow-sm" style="max-width:450px;">
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Valor *</label>
                <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="number" class="form-control" name="valor" step="0.01" min="0.01" required autofocus>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Motivo *</label>
                <textarea class="form-control" name="motivo" rows="2" required></textarea>
            </div>
            <button type="submit" class="btn btn-warning w-100"><i class="fas fa-arrow-up me-1"></i>Registrar Sangria</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
