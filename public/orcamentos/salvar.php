<?php
/**
 * AJAX - Salvar orçamento (criar ou atualizar)
 */
require_once __DIR__ . '/../../app/includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Método inválido']);
    exit;
}

$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['ok' => false, 'msg' => 'Token CSRF inválido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['ok' => false, 'msg' => 'Dados inválidos']);
    exit;
}

$pdo = db();
$user = usuario();
$tid = tenantId();

$id = (int)($input['id'] ?? 0);
$itens = $input['itens'] ?? [];
$clienteId = !empty($input['cliente_id']) ? (int)$input['cliente_id'] : null;
$dataOrcamento = sanitize($input['data_orcamento'] ?? date('Y-m-d'));
$validade = sanitize($input['validade'] ?? '');
$descontoTipo = in_array($input['desconto_tipo'] ?? '', ['valor','percentual']) ? $input['desconto_tipo'] : 'valor';
$descontoValor = (float)($input['desconto_valor'] ?? 0);
$condicoes = sanitize($input['condicoes_pagamento'] ?? '');
$prazoEntrega = sanitize($input['prazo_entrega'] ?? '');
$observacoes = sanitize($input['observacoes'] ?? '');
$observacoesInt = sanitize($input['observacoes_internas'] ?? '');

if (empty($itens)) {
    echo json_encode(['ok' => false, 'msg' => 'Adicione ao menos um item']);
    exit;
}
if (empty($validade)) {
    echo json_encode(['ok' => false, 'msg' => 'Informe a validade']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Calcular subtotal
    $subtotal = 0;
    foreach ($itens as &$item) {
        $item['quantidade'] = (float)($item['quantidade'] ?? 1);
        $item['valor_unitario'] = (float)($item['valor_unitario'] ?? 0);
        $item['desconto'] = (float)($item['desconto'] ?? 0);
        $item['subtotal'] = max(0, ($item['quantidade'] * $item['valor_unitario']) - $item['desconto']);
        $subtotal += $item['subtotal'];
    }
    unset($item);

    $descontoReal = $descontoTipo === 'percentual' ? ($subtotal * $descontoValor / 100) : $descontoValor;
    $total = max(0, $subtotal - $descontoReal);

    if ($id > 0) {
        // Atualizar
        $stmt = $pdo->prepare("SELECT * FROM orcamentos WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tid]);
        $orcAtual = $stmt->fetch();
        if (!$orcAtual) throw new Exception('Orçamento não encontrado');
        if ($orcAtual['status'] === 'convertido') throw new Exception('Orçamento convertido não pode ser editado');

        $pdo->prepare("UPDATE orcamentos SET
            cliente_id = ?, data_orcamento = ?, validade = ?,
            subtotal = ?, desconto_tipo = ?, desconto_valor = ?, total = ?,
            condicoes_pagamento = ?, prazo_entrega = ?, observacoes = ?, observacoes_internas = ?
            WHERE id = ? AND tenant_id = ?")
            ->execute([$clienteId, $dataOrcamento, $validade,
                $subtotal, $descontoTipo, $descontoValor, $total,
                $condicoes, $prazoEntrega, $observacoes, $observacoesInt,
                $id, $tid]);

        $pdo->prepare("DELETE FROM orcamento_itens WHERE orcamento_id = ? AND tenant_id = ?")
            ->execute([$id, $tid]);

        $pdo->prepare("INSERT INTO orcamento_historico (tenant_id, orcamento_id, usuario_id, acao, descricao)
            VALUES (?, ?, ?, 'editado', 'Orçamento atualizado')")
            ->execute([$tid, $id, $user['id']]);

        $numero = (int)$orcAtual['numero'];
    } else {
        // Criar
        $stmtN = $pdo->prepare("SELECT COALESCE(MAX(numero),0)+1 FROM orcamentos WHERE tenant_id = ?");
        $stmtN->execute([$tid]);
        $numero = (int)$stmtN->fetchColumn();

        $token = bin2hex(random_bytes(24));

        $pdo->prepare("INSERT INTO orcamentos
            (tenant_id, numero, cliente_id, usuario_id, data_orcamento, validade, status,
             subtotal, desconto_tipo, desconto_valor, total,
             condicoes_pagamento, prazo_entrega, observacoes, observacoes_internas, token_publico)
            VALUES (?, ?, ?, ?, ?, ?, 'pendente', ?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$tid, $numero, $clienteId, $user['id'], $dataOrcamento, $validade,
                $subtotal, $descontoTipo, $descontoValor, $total,
                $condicoes, $prazoEntrega, $observacoes, $observacoesInt, $token]);
        $id = (int)$pdo->lastInsertId();

        $pdo->prepare("INSERT INTO orcamento_historico (tenant_id, orcamento_id, usuario_id, acao, descricao)
            VALUES (?, ?, ?, 'criado', 'Orçamento criado')")
            ->execute([$tid, $id, $user['id']]);
    }

    // Inserir itens
    $stmtItem = $pdo->prepare("INSERT INTO orcamento_itens
        (tenant_id, orcamento_id, produto_id, descricao, quantidade, valor_unitario, desconto, subtotal, ordem)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($itens as $idx => $item) {
        $produtoId = !empty($item['produto_id']) ? (int)$item['produto_id'] : null;
        $stmtItem->execute([
            $tid, $id, $produtoId,
            sanitize($item['descricao']),
            $item['quantidade'], $item['valor_unitario'],
            $item['desconto'], $item['subtotal'], $idx
        ]);
    }

    $pdo->commit();

    echo json_encode(['ok' => true, 'id' => $id, 'numero' => $numero, 'msg' => 'Orçamento salvo com sucesso']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Erro salvar orcamento: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
