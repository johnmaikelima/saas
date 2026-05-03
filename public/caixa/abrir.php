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

// Buscar PDVs ativos do tenant
$stmtPdvs = $pdo->prepare("
    SELECT p.*,
           (SELECT u.nome FROM caixas c JOIN usuarios u ON u.id = c.usuario_id WHERE c.pdv_id = p.id AND c.status = 'aberto' LIMIT 1) as operador_atual,
           (SELECT c.id FROM caixas c WHERE c.pdv_id = p.id AND c.status = 'aberto' LIMIT 1) as caixa_aberto_id
    FROM pdvs p
    WHERE p.tenant_id = ? AND p.ativo = 1
    ORDER BY p.nome
");
$stmtPdvs->execute([$tid]);
$pdvs = $stmtPdvs->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flashError('Token inválido.');
        redirect('caixa/abrir.php');
    }

    $valor = (float)($_POST['valor_abertura'] ?? 0);
    $pdvId = (int)($_POST['pdv_id'] ?? 0) ?: null;
    $pdvSelecionado = null;

    // Se há PDVs cadastrados, é obrigatório selecionar um
    if (!empty($pdvs) && !$pdvId) {
        flashError('Selecione o PDV/Terminal que vai utilizar.');
        redirect('caixa/abrir.php');
    }

    // Validar PDV selecionado
    if ($pdvId) {
        $stmtPdv = $pdo->prepare("SELECT * FROM pdvs WHERE id = ? AND tenant_id = ? AND ativo = 1");
        $stmtPdv->execute([$pdvId, $tid]);
        $pdvSelecionado = $stmtPdv->fetch();

        if (!$pdvSelecionado) {
            flashError('PDV inválido ou inativo.');
            redirect('caixa/abrir.php');
        }

        // Verificar se PDV já está em uso
        $check = $pdo->prepare("
            SELECT c.id, u.nome as operador
            FROM caixas c
            JOIN usuarios u ON u.id = c.usuario_id
            WHERE c.pdv_id = ? AND c.status = 'aberto'
            LIMIT 1
        ");
        $check->execute([$pdvId]);
        $emUso = $check->fetch();

        if ($emUso) {
            flashError("O PDV '{$pdvSelecionado['nome']}' já está sendo utilizado por {$emUso['operador']} (Caixa #{$emUso['id']}).");
            redirect('caixa/abrir.php');
        }
    }

    $pdo->prepare("INSERT INTO caixas (tenant_id, usuario_id, pdv_id, valor_abertura, status, aberto_em) VALUES (?, ?, ?, ?, 'aberto', NOW())")
        ->execute([$tid, $user['id'], $pdvId, $valor]);

    $caixaId = (int)$pdo->lastInsertId();
    $pdvNome = $pdvSelecionado['nome'] ?? 'sem PDV';

    auditLog('caixa_aberto', "Caixa #{$caixaId} aberto no PDV '{$pdvNome}' com valor: " . formatMoney($valor));
    flashSuccess('Caixa aberto com sucesso!');
    redirect('pdv/');
}

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-door-open me-2"></i>Abrir Caixa</h5>

<?php if (empty($pdvs) && temPerfil('admin', 'gerente')): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-1"></i>
        Nenhum PDV cadastrado. Recomendamos <a href="<?= baseUrl('pdvs/form.php') ?>">cadastrar pelo menos um PDV</a> para identificar os terminais do estabelecimento.
    </div>
<?php endif; ?>

<div class="card shadow-sm" style="max-width:500px;">
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>

            <div class="mb-3">
                <label class="form-label">Operador</label>
                <input type="text" class="form-control" value="<?= e($user['nome']) ?>" readonly>
            </div>

            <?php if (!empty($pdvs)): ?>
            <div class="mb-3">
                <label class="form-label">PDV / Terminal <span class="text-danger">*</span></label>
                <select name="pdv_id" class="form-select" required>
                    <option value="">Selecione um PDV...</option>
                    <?php foreach ($pdvs as $p): ?>
                        <?php $emUso = !empty($p['caixa_aberto_id']); ?>
                        <option value="<?= $p['id'] ?>" <?= $emUso ? 'disabled' : '' ?>>
                            <?= e($p['nome']) ?>
                            <?php if ($emUso): ?>
                                (em uso por <?= e($p['operador_atual']) ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">PDVs em uso aparecem desabilitados</small>
            </div>
            <?php endif; ?>

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
