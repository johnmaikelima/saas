<?php
$pageTitle = 'Suprimento';
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
        redirect('caixa/suprimento.php');
    }

    $valor = (float)($_POST['valor'] ?? 0);
    $motivo = sanitize($_POST['motivo'] ?? '');

    if ($valor <= 0) {
        flashError('Valor deve ser maior que zero.');
    } elseif (empty($motivo)) {
        flashError('Motivo é obrigatório.');
    } else {
        db()->prepare("INSERT INTO caixa_movimentacoes (tenant_id, caixa_id, tipo, valor, motivo, usuario_id, criado_em) VALUES (?,?,?,?,?,?,NOW())")
            ->execute([$tid, $caixa['id'], 'suprimento', $valor, $motivo, usuario()['id']]);

        auditLog('suprimento', 'Suprimento de ' . formatMoney($valor) . ' no Caixa #' . $caixa['id']);
        flashSuccess('Suprimento de ' . formatMoney($valor) . ' registrado.');
        redirect('caixa/');
    }
}

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-arrow-down me-2 text-info"></i>Suprimento - Caixa #<?= $caixa['id'] ?></h5>

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
            <button type="submit" class="btn btn-info w-100"><i class="fas fa-arrow-down me-1"></i>Registrar Suprimento</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
