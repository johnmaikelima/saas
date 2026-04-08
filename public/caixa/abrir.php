<?php
$pageTitle = 'Abrir Caixa';
require_once __DIR__ . '/../../app/includes/auth.php';

$user = usuario();
$pdo = db();
$tid = tenantId();
$caixa = getCaixaAberto($user['id']);

if ($caixa) {
    flashWarning('Você já possui um caixa aberto (#' . $caixa['id'] . ').');
    redirect('caixa/');
}

// Verificar se outro caixa esta aberto (no tenant)
$stmt = $pdo->prepare("SELECT cx.*, u.nome as operador
    FROM caixas cx
    LEFT JOIN usuarios u ON u.id = cx.usuario_id
    WHERE cx.tenant_id = ? AND cx.status = 'aberto'");
$stmt->execute([$tid]);
$outroAberto = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flashError('Token inválido.');
        redirect('caixa/abrir.php');
    }

    if ($outroAberto && !temPerfil('admin', 'gerente')) {
        flashError('Já existe um caixa aberto por outro operador.');
        redirect('caixa/');
    }

    $valor = (float)($_POST['valor_abertura'] ?? 0);

    $pdo->prepare("INSERT INTO caixas (tenant_id, usuario_id, valor_abertura, status, aberto_em) VALUES (?, ?, ?, 'aberto', NOW())")
        ->execute([$tid, $user['id'], $valor]);

    auditLog('caixa_aberto', 'Caixa aberto com valor: ' . formatMoney($valor));
    flashSuccess('Caixa aberto com sucesso!');
    redirect('pdv/');
}

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-door-open me-2"></i>Abrir Caixa</h5>

<?php if ($outroAberto): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-1"></i>
        Caixa #<?= $outroAberto['id'] ?> aberto por <strong><?= e($outroAberto['operador']) ?></strong>
        em <?= formatDateTime($outroAberto['aberto_em']) ?>.
        <?php if (temPerfil('admin', 'gerente')): ?>
            Você pode abrir outro caixa mesmo assim.
        <?php else: ?>
            Feche o caixa anterior antes de abrir um novo.
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card shadow-sm" style="max-width:450px;">
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Operador</label>
                <input type="text" class="form-control" value="<?= e($user['nome']) ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Valor de Abertura (dinheiro em caixa)</label>
                <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="number" class="form-control" name="valor_abertura" step="0.01" min="0" value="0" autofocus>
                </div>
            </div>
            <button type="submit" class="btn btn-success w-100">
                <i class="fas fa-door-open me-1"></i>Abrir Caixa
            </button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
