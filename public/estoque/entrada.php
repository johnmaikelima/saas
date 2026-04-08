<?php
$pageTitle = 'Entrada de Estoque';
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$tid = tenantId();
$produtoId = (int)($_GET['produto_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        flashError('Token inválido.');
        redirect('estoque/entrada.php');
    }

    $pid = (int)($_POST['produto_id'] ?? 0);
    $tipo = $_POST['tipo'] ?? 'entrada';
    $quantidade = (float)($_POST['quantidade'] ?? 0);
    $motivo = sanitize($_POST['motivo'] ?? '');
    $refTipo = sanitize($_POST['referencia_tipo'] ?? 'ajuste');

    // Validar tipo
    if (!in_array($tipo, ['entrada', 'saida', 'ajuste'])) {
        $tipo = 'entrada';
    }
    if (!in_array($refTipo, ['compra', 'ajuste', 'devolucao'])) {
        $refTipo = 'ajuste';
    }

    if ($pid <= 0 || $quantidade <= 0 || empty($motivo)) {
        flashError('Preencha todos os campos obrigatórios.');
    } else {
        // Verificar se o produto pertence ao tenant
        $stmtCheck = $pdo->prepare("SELECT id FROM produtos WHERE id = ? AND tenant_id = ?");
        $stmtCheck->execute([$pid, $tid]);
        if (!$stmtCheck->fetch()) {
            flashError('Produto não encontrado.');
            redirect('estoque/entrada.php');
        }

        $pdo->beginTransaction();
        try {
            if ($tipo === 'entrada') {
                $pdo->prepare("UPDATE produtos SET estoque_atual = estoque_atual + ? WHERE id = ? AND tenant_id = ?")
                    ->execute([$quantidade, $pid, $tid]);
            } else {
                $pdo->prepare("UPDATE produtos SET estoque_atual = estoque_atual - ? WHERE id = ? AND tenant_id = ?")
                    ->execute([$quantidade, $pid, $tid]);
            }

            $pdo->prepare("INSERT INTO estoque_movimentacoes (tenant_id, produto_id, tipo, quantidade, motivo, referencia_tipo, referencia_id, usuario_id, criado_em) VALUES (?,?,?,?,?,?,0,?,NOW())")
                ->execute([$tid, $pid, $tipo, $quantidade, $motivo, $refTipo, usuario()['id']]);

            $pdo->commit();
            auditLog('estoque_movimentacao', "Movimentação {$tipo} de {$quantidade} unidades no produto #{$pid}");
            flashSuccess('Movimentação registrada com sucesso!');
            redirect('estoque/');
        } catch (Exception $e) {
            $pdo->rollBack();
            flashError('Erro: ' . $e->getMessage());
        }
    }
}

$stmt = $pdo->prepare("SELECT id, descricao, codigo_barras, estoque_atual FROM produtos WHERE tenant_id = ? AND ativo = 1 ORDER BY descricao");
$stmt->execute([$tid]);
$produtos = $stmt->fetchAll();

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-arrow-down me-2"></i>Movimentação de Estoque</h5>

<div class="card shadow-sm" style="max-width:700px;">
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Produto *</label>
                    <select class="form-select" name="produto_id" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($produtos as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $produtoId == $p['id'] ? 'selected' : '' ?>>
                                <?= e($p['descricao']) ?> (Est: <?= $p['estoque_atual'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tipo *</label>
                    <select class="form-select" name="tipo">
                        <option value="entrada">Entrada</option>
                        <option value="saida">Saída</option>
                        <option value="ajuste">Ajuste</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Quantidade *</label>
                    <input type="number" class="form-control" name="quantidade" step="0.01" min="0.01" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Referência</label>
                    <select class="form-select" name="referencia_tipo">
                        <option value="compra">Compra</option>
                        <option value="ajuste">Ajuste</option>
                        <option value="devolucao">Devolução</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Motivo *</label>
                    <textarea class="form-control" name="motivo" rows="2" required placeholder="Descreva o motivo da movimentação"></textarea>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Registrar</button>
                <a href="<?= baseUrl('estoque/') ?>" class="btn btn-secondary">Voltar</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
