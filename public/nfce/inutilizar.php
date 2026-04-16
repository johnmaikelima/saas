<?php
$pageTitle = 'Inutilizar NFC-e';
require_once __DIR__ . '/../../app/includes/auth.php';
if (!temPerfil('admin')) { flashError('Sem permissão.'); redirect('nfce/'); }

$tid = tenantId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { flashError('Token inválido.'); redirect('nfce/inutilizar.php'); }

    $inicio = (int)($_POST['numero_inicio'] ?? 0);
    $fim = (int)($_POST['numero_fim'] ?? 0);
    $motivo = sanitize($_POST['motivo'] ?? '');

    if ($inicio <= 0 || $fim <= 0 || $fim < $inicio) {
        flashError('Faixa de numeração inválida.');
        redirect('nfce/inutilizar.php');
    }

    if (strlen($motivo) < 15) {
        flashError('Motivo deve ter no mínimo 15 caracteres.');
        redirect('nfce/inutilizar.php');
    }

    $nfceHelper = new NfceHelper($tid);
    $result = $nfceHelper->inutilizar($inicio, $fim, $motivo);

    if ($result['ok']) {
        auditLog('nfce_inutilizar', "Números {$inicio} a {$fim} inutilizados");
        flashSuccess($result['msg']);
    } else {
        flashError($result['msg']);
    }

    redirect('nfce/inutilizar.php');
}

// Listar inutilizações
$stmt = db()->prepare("SELECT * FROM nfce_inutilizadas WHERE tenant_id = ? ORDER BY criado_em DESC LIMIT 50");
$stmt->execute([$tid]);
$inutilizadas = $stmt->fetchAll();

require __DIR__ . '/../../app/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="page-title mb-0"><i class="fas fa-ban me-2"></i>Inutilizar Numeração NFC-e</h5>
    <a href="<?= baseUrl('nfce/') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header"><h6 class="mb-0">Nova Inutilização</h6></div>
            <div class="card-body">
                <div class="alert alert-info small">
                    <i class="fas fa-info-circle me-1"></i>
                    Inutilize números que foram pulados e não serão utilizados. A SEFAZ exige que não haja lacunas na numeração.
                </div>

                <form method="POST">
                    <?= csrfField() ?>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Número Início</label>
                            <input type="number" name="numero_inicio" class="form-control" required min="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Número Fim</label>
                            <input type="number" name="numero_fim" class="form-control" required min="1">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Motivo</label>
                            <textarea name="motivo" class="form-control" rows="3" required minlength="15"
                                      placeholder="Mínimo 15 caracteres"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-warning mt-3" onclick="return confirm('Confirma a inutilização? Esta ação é irreversível.')">
                        <i class="fas fa-ban me-1"></i>Inutilizar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header"><h6 class="mb-0">Histórico de Inutilizações</h6></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Faixa</th>
                            <th>Série</th>
                            <th>Protocolo</th>
                            <th>Motivo</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inutilizadas)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Nenhuma inutilização registrada</td></tr>
                        <?php else: ?>
                            <?php foreach ($inutilizadas as $inut): ?>
                            <tr>
                                <td><?= $inut['numero_inicio'] ?> - <?= $inut['numero_fim'] ?></td>
                                <td><?= $inut['serie'] ?></td>
                                <td><small><?= e($inut['protocolo']) ?></small></td>
                                <td><small><?= e(mb_substr($inut['motivo'], 0, 50)) ?></small></td>
                                <td><small><?= formatDateTime($inut['criado_em']) ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
