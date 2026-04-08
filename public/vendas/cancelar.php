<?php
$pageTitle = 'Cancelar Venda';
require_once __DIR__ . '/../../app/includes/auth.php';

if (!temPerfil('admin', 'gerente')) {
    flashError('Sem permissão.');
    redirect('vendas/');
}

$pdo = db();
$tid = tenantId();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM vendas WHERE id = ? AND tenant_id = ? AND status = 'concluida'");
$stmt->execute([$id, $tid]);
$venda = $stmt->fetch();
if (!$venda) {
    flashError('Venda não encontrada ou já cancelada.');
    redirect('vendas/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flashError('Token inválido.');
        redirect('vendas/');
    }

    $motivo = sanitize($_POST['motivo'] ?? '');
    if (empty($motivo)) {
        flashError('Motivo é obrigatório.');
        redirect('vendas/cancelar.php?id=' . $id);
    }

    $pdo->beginTransaction();
    try {
        // Cancelar venda
        $pdo->prepare("UPDATE vendas SET status = 'cancelada', motivo_cancelamento = ?, cancelado_em = NOW() WHERE id = ? AND tenant_id = ?")
            ->execute([$motivo, $id, $tid]);

        // Devolver estoque
        $stmt = $pdo->prepare("SELECT * FROM venda_itens WHERE venda_id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tid]);
        $itens = $stmt->fetchAll();

        foreach ($itens as $item) {
            $pdo->prepare("UPDATE produtos SET estoque_atual = estoque_atual + ? WHERE id = ? AND tenant_id = ?")
                ->execute([$item['quantidade'], $item['produto_id'], $tid]);

            $pdo->prepare("INSERT INTO estoque_movimentacoes (tenant_id, produto_id, tipo, quantidade, motivo, referencia_tipo, referencia_id, usuario_id, criado_em) VALUES (?,?,?,?,?,?,?,?,NOW())")
                ->execute([$tid, $item['produto_id'], 'entrada', $item['quantidade'], 'Cancelamento Venda #' . $id, 'devolucao', $id, usuario()['id']]);
        }

        $pdo->commit();
        auditLog('venda_cancelada', "Venda #{$id} cancelada. Motivo: {$motivo}");
        flashSuccess('Venda #' . $id . ' cancelada com sucesso.');
    } catch (Exception $e) {
        $pdo->rollBack();
        flashError('Erro: ' . $e->getMessage());
    }
    redirect('vendas/');
}

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-ban me-2 text-danger"></i>Cancelar Venda #<?= $id ?></h5>

<div class="card shadow-sm" style="max-width:500px;">
    <div class="card-body">
        <div class="alert alert-warning">
            <strong>Atenção!</strong> Esta ação irá cancelar a venda e devolver o estoque dos produtos.
        </div>
        <p><strong>Total da venda:</strong> <?= formatMoney($venda['total']) ?></p>
        <p><strong>Data:</strong> <?= formatDateTime($venda['criado_em']) ?></p>

        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="mb-3">
                <label class="form-label">Motivo do cancelamento *</label>
                <textarea class="form-control" name="motivo" rows="3" required placeholder="Informe o motivo"></textarea>
            </div>
            <button type="submit" class="btn btn-danger" onclick="return confirm('Confirma o cancelamento?')">
                <i class="fas fa-ban me-1"></i>Confirmar Cancelamento
            </button>
            <a href="<?= baseUrl('vendas/view.php?id=' . $id) ?>" class="btn btn-secondary">Voltar</a>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
