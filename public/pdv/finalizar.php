<?php
/**
 * AJAX - Processar/finalizar venda
 * SaaS - Multi-tenant, sem NFC-e
 */
require_once __DIR__ . '/../../app/includes/auth.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Não autenticado']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Método inválido']);
    exit;
}

// Verificar CSRF via header
$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($csrfHeader) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfHeader)) {
    echo json_encode(['ok' => false, 'msg' => 'Token CSRF inválido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['ok' => false, 'msg' => 'Dados inválidos']);
    exit;
}

$user = usuario();
$pdo = db();
$tid = tenantId();

$itens = $input['itens'] ?? [];
$pagamentos = $input['pagamentos'] ?? [];
$descontoTipo = $input['desconto_tipo'] ?? 'valor';
$descontoValor = (float)($input['desconto_valor'] ?? 0);
$cpfCnpj = sanitize($input['cpf_cnpj'] ?? '');
$caixaId = (int)($input['caixa_id'] ?? 0);

// Validações
if (empty($itens)) {
    echo json_encode(['ok' => false, 'msg' => 'Nenhum item na venda']);
    exit;
}

if (empty($pagamentos)) {
    echo json_encode(['ok' => false, 'msg' => 'Nenhuma forma de pagamento']);
    exit;
}

// Verificar caixa aberto (pelo tenant, nao confiar no caixa_id do input)
$caixa = getCaixaAberto($user['id']);
if (!$caixa) {
    echo json_encode(['ok' => false, 'msg' => 'Caixa não está aberto']);
    exit;
}
$caixaId = $caixa['id'];

// Verificar configuração de estoque
$permitirSemEstoque = getConfig('vender_sem_estoque', '0') === '1';

try {
    $pdo->beginTransaction();

    // Calcular subtotal
    $subtotal = 0;
    foreach ($itens as &$item) {
        $item['subtotal'] = ($item['quantidade'] * $item['valor_unitario']) - ($item['desconto'] ?? 0);
        $subtotal += $item['subtotal'];
    }
    unset($item);

    // Calcular desconto geral
    $descontoReal = $descontoTipo === 'percentual' ? ($subtotal * $descontoValor / 100) : $descontoValor;
    $total = max(0, $subtotal - $descontoReal);

    // Verificar pagamentos
    $totalPago = array_sum(array_column($pagamentos, 'valor'));
    if ($totalPago < $total - 0.01) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'msg' => 'Valor pago insuficiente']);
        exit;
    }

    // Calcular troco
    $troco = max(0, $totalPago - $total);

    // Inserir venda (com tenant_id)
    $stmt = $pdo->prepare("INSERT INTO vendas (tenant_id, caixa_id, cliente_id, usuario_id, subtotal, desconto_tipo, desconto_valor, total, cpf_cnpj_nota, status, criado_em)
        VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, 'concluida', NOW())");
    $stmt->execute([$tid, $caixaId, $user['id'], $subtotal, $descontoTipo, $descontoReal, $total, $cpfCnpj]);
    $vendaId = (int)$pdo->lastInsertId();

    // Verificar se tem cliente pelo CPF/CNPJ
    if (!empty($cpfCnpj)) {
        $docLimpo = limparCpfCnpj($cpfCnpj);
        $stmtCli = $pdo->prepare("SELECT id FROM clientes WHERE tenant_id = ? AND REPLACE(REPLACE(REPLACE(cpf_cnpj,'.',''),'-',''),'/','') = ?");
        $stmtCli->execute([$tid, $docLimpo]);
        $cli = $stmtCli->fetch();
        if ($cli) {
            $pdo->prepare("UPDATE vendas SET cliente_id = ? WHERE id = ? AND tenant_id = ?")->execute([$cli['id'], $vendaId, $tid]);
        }
    }

    // Inserir itens
    $stmtItem = $pdo->prepare("INSERT INTO venda_itens (tenant_id, venda_id, produto_id, descricao, quantidade, valor_unitario, desconto, subtotal) VALUES (?,?,?,?,?,?,?,?)");
    $stmtEstoque = $pdo->prepare("UPDATE produtos SET estoque_atual = estoque_atual - ? WHERE id = ? AND tenant_id = ?");
    $stmtMov = $pdo->prepare("INSERT INTO estoque_movimentacoes (tenant_id, produto_id, tipo, quantidade, motivo, referencia_tipo, referencia_id, usuario_id, criado_em) VALUES (?,?,?,?,?,?,?,?,NOW())");

    foreach ($itens as $item) {
        // Verificar estoque
        if (!$permitirSemEstoque) {
            $stmtProd = $pdo->prepare("SELECT estoque_atual FROM produtos WHERE id = ? AND tenant_id = ?");
            $stmtProd->execute([$item['produto_id'], $tid]);
            $prod = $stmtProd->fetch();
            if ($prod && $prod['estoque_atual'] < $item['quantidade']) {
                $pdo->rollBack();
                echo json_encode(['ok' => false, 'msg' => "Estoque insuficiente para: {$item['descricao']}"]);
                exit;
            }
        }

        $stmtItem->execute([
            $tid,
            $vendaId,
            $item['produto_id'],
            $item['descricao'],
            $item['quantidade'],
            $item['valor_unitario'],
            $item['desconto'] ?? 0,
            $item['subtotal']
        ]);

        // Baixar estoque
        $stmtEstoque->execute([$item['quantidade'], $item['produto_id'], $tid]);

        // Registrar movimentação
        $stmtMov->execute([
            $tid,
            $item['produto_id'],
            'saida',
            $item['quantidade'],
            'Venda #' . $vendaId,
            'venda',
            $vendaId,
            $user['id']
        ]);
    }

    // Inserir pagamentos
    $stmtPag = $pdo->prepare("INSERT INTO venda_pagamentos (tenant_id, venda_id, forma, valor, troco) VALUES (?,?,?,?,?)");
    foreach ($pagamentos as $i => $pag) {
        $trocoItem = ($i === count($pagamentos) - 1 && $pag['forma'] === 'dinheiro') ? $troco : 0;
        $stmtPag->execute([$tid, $vendaId, $pag['forma'], $pag['valor'], $trocoItem]);
    }

    // SaaS: sem emissão de NFC-e (não há ACBr local)

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'msg' => 'Venda #' . $vendaId . ' finalizada com sucesso!',
        'venda_id' => $vendaId,
        'total' => $total,
        'troco' => $troco,
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Erro finalizar venda: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Erro ao finalizar venda. Tente novamente.']);
}
