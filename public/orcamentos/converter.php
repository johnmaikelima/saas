<?php
/**
 * Converter orcamento em venda concluida.
 * - Cria venda + itens + pagamento unico
 * - Baixa estoque
 * - Marca orcamento como 'convertido'
 */
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$user = usuario();
$tid = tenantId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf()) {
    flashError('Requisição inválida.');
    redirect('orcamentos/');
}

$id = (int)($_POST['id'] ?? 0);
$formaPagamento = sanitize($_POST['forma_pagamento'] ?? 'dinheiro');

$formasValidas = ['dinheiro','pix','debito','credito','boleto','outro'];
if (!in_array($formaPagamento, $formasValidas)) {
    flashError('Forma de pagamento inválida.');
    redirect('orcamentos/view.php?id=' . $id);
}

$stmt = $pdo->prepare("SELECT * FROM orcamentos WHERE id = ? AND tenant_id = ?");
$stmt->execute([$id, $tid]);
$orc = $stmt->fetch();
if (!$orc) {
    flashError('Orçamento não encontrado.');
    redirect('orcamentos/');
}
if (in_array($orc['status'], ['convertido','cancelado','recusado'])) {
    flashError('Este orçamento não pode mais ser convertido.');
    redirect('orcamentos/view.php?id=' . $id);
}

// Verificar caixa aberto
$caixa = getCaixaAberto($user['id']);
if (!$caixa) {
    flashError('Abra o caixa antes de converter orçamentos em venda.');
    redirect('caixa/abrir.php');
}

$stmtI = $pdo->prepare("SELECT * FROM orcamento_itens WHERE orcamento_id = ? AND tenant_id = ? ORDER BY ordem, id");
$stmtI->execute([$id, $tid]);
$itens = $stmtI->fetchAll();

if (empty($itens)) {
    flashError('Orçamento sem itens.');
    redirect('orcamentos/view.php?id=' . $id);
}

$permitirSemEstoque = getConfig('vender_sem_estoque', '0') === '1';

try {
    $pdo->beginTransaction();

    // Validar estoque
    if (!$permitirSemEstoque) {
        $stmtEst = $pdo->prepare("SELECT estoque_atual, descricao FROM produtos WHERE id = ? AND tenant_id = ?");
        foreach ($itens as $it) {
            if (!$it['produto_id']) continue;
            $stmtEst->execute([$it['produto_id'], $tid]);
            $p = $stmtEst->fetch();
            if ($p && $p['estoque_atual'] < $it['quantidade']) {
                throw new Exception("Estoque insuficiente para: {$p['descricao']}");
            }
        }
    }

    // Criar venda
    $descontoReal = $orc['desconto_tipo'] === 'percentual'
        ? ($orc['subtotal'] * $orc['desconto_valor'] / 100)
        : $orc['desconto_valor'];

    $pdo->prepare("INSERT INTO vendas (tenant_id, caixa_id, cliente_id, usuario_id,
        subtotal, desconto_tipo, desconto_valor, total, status, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'concluida', NOW())")
        ->execute([$tid, $caixa['id'], $orc['cliente_id'], $user['id'],
            $orc['subtotal'], $orc['desconto_tipo'], $descontoReal, $orc['total']]);
    $vendaId = (int)$pdo->lastInsertId();

    // Inserir itens da venda
    $stmtItem = $pdo->prepare("INSERT INTO venda_itens
        (tenant_id, venda_id, produto_id, descricao, quantidade, valor_unitario, desconto, subtotal)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtEst = $pdo->prepare("UPDATE produtos SET estoque_atual = estoque_atual - ? WHERE id = ? AND tenant_id = ?");
    $stmtMov = $pdo->prepare("INSERT INTO estoque_movimentacoes
        (tenant_id, produto_id, tipo, quantidade, motivo, referencia_tipo, referencia_id, usuario_id, criado_em)
        VALUES (?, ?, 'saida', ?, ?, 'venda', ?, ?, NOW())");

    foreach ($itens as $it) {
        $stmtItem->execute([$tid, $vendaId, $it['produto_id'], $it['descricao'],
            $it['quantidade'], $it['valor_unitario'], $it['desconto'], $it['subtotal']]);

        if ($it['produto_id']) {
            $stmtEst->execute([$it['quantidade'], $it['produto_id'], $tid]);
            $stmtMov->execute([$tid, $it['produto_id'], $it['quantidade'],
                "Venda #{$vendaId} (Orçamento #" . str_pad($orc['numero'],4,'0',STR_PAD_LEFT) . ")",
                $vendaId, $user['id']]);
        }
    }

    // Pagamento
    $pdo->prepare("INSERT INTO venda_pagamentos (tenant_id, venda_id, forma, valor, troco) VALUES (?, ?, ?, ?, 0)")
        ->execute([$tid, $vendaId, $formaPagamento, $orc['total']]);

    // Marcar orcamento como convertido
    $pdo->prepare("UPDATE orcamentos SET status='convertido', convertido_em=NOW(), venda_id=?, aprovado_em=COALESCE(aprovado_em, NOW())
        WHERE id=? AND tenant_id=?")
        ->execute([$vendaId, $id, $tid]);

    $pdo->prepare("INSERT INTO orcamento_historico (tenant_id, orcamento_id, usuario_id, acao, descricao)
        VALUES (?, ?, ?, 'convertido', ?)")
        ->execute([$tid, $id, $user['id'], "Convertido em Venda #{$vendaId}"]);

    $pdo->commit();

    flashSuccess("Orçamento convertido em Venda #{$vendaId} com sucesso!");
    redirect('vendas/view.php?id=' . $vendaId);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Erro converter orcamento: ' . $e->getMessage());
    flashError('Erro ao converter: ' . $e->getMessage());
    redirect('orcamentos/view.php?id=' . $id);
}
