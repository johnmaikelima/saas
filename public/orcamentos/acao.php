<?php
/**
 * Ações sobre orçamento: aprovar, recusar, enviar, duplicar, cancelar
 */
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$user = usuario();
$tid = tenantId();

$ajax = !empty($_POST['ajax']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($ajax) { echo json_encode(['ok' => false]); exit; }
    flashError('Método inválido.');
    redirect('orcamentos/');
}

if (!verifyCsrf()) {
    if ($ajax) { echo json_encode(['ok' => false, 'msg' => 'CSRF']); exit; }
    flashError('Token inválido.');
    redirect('orcamentos/');
}

$id = (int)($_POST['id'] ?? 0);
$acao = sanitize($_POST['acao'] ?? '');
$motivo = sanitize($_POST['motivo'] ?? '');
$canal = sanitize($_POST['canal'] ?? '');

$stmt = $pdo->prepare("SELECT * FROM orcamentos WHERE id = ? AND tenant_id = ?");
$stmt->execute([$id, $tid]);
$orc = $stmt->fetch();
if (!$orc) {
    if ($ajax) { echo json_encode(['ok' => false, 'msg' => 'Não encontrado']); exit; }
    flashError('Orçamento não encontrado.');
    redirect('orcamentos/');
}

function registrarHistorico($pdo, $tid, $orcId, $userId, $acao, $descricao) {
    $pdo->prepare("INSERT INTO orcamento_historico (tenant_id, orcamento_id, usuario_id, acao, descricao) VALUES (?,?,?,?,?)")
        ->execute([$tid, $orcId, $userId, $acao, $descricao]);
}

try {
    switch ($acao) {
        case 'aprovar':
            if ($orc['status'] !== 'pendente') throw new Exception('Apenas pendentes podem ser aprovados');
            $pdo->prepare("UPDATE orcamentos SET status='aprovado', aprovado_em=NOW() WHERE id=? AND tenant_id=?")
                ->execute([$id, $tid]);
            registrarHistorico($pdo, $tid, $id, $user['id'], 'aprovado', 'Aprovado internamente');
            flashSuccess('Orçamento aprovado.');
            redirect('orcamentos/view.php?id=' . $id);
            break;

        case 'recusar':
            if ($orc['status'] !== 'pendente') throw new Exception('Apenas pendentes podem ser recusados');
            $pdo->prepare("UPDATE orcamentos SET status='recusado', recusado_em=NOW(), recusa_motivo=? WHERE id=? AND tenant_id=?")
                ->execute([$motivo, $id, $tid]);
            registrarHistorico($pdo, $tid, $id, $user['id'], 'recusado', $motivo ?: 'Recusado');
            flashSuccess('Orçamento recusado.');
            redirect('orcamentos/view.php?id=' . $id);
            break;

        case 'cancelar':
            if (in_array($orc['status'], ['convertido','cancelado'])) throw new Exception('Não pode ser cancelado');
            $pdo->prepare("UPDATE orcamentos SET status='cancelado' WHERE id=? AND tenant_id=?")
                ->execute([$id, $tid]);
            registrarHistorico($pdo, $tid, $id, $user['id'], 'cancelado', $motivo ?: 'Cancelado');
            flashSuccess('Orçamento cancelado.');
            redirect('orcamentos/view.php?id=' . $id);
            break;

        case 'enviar':
            registrarHistorico($pdo, $tid, $id, $user['id'], 'enviado', 'Enviado via ' . ($canal ?: 'manual'));
            if ($ajax) { echo json_encode(['ok' => true]); exit; }
            flashSuccess('Envio registrado.');
            redirect('orcamentos/view.php?id=' . $id);
            break;

        case 'duplicar':
            // Buscar proximo numero
            $stmtN = $pdo->prepare("SELECT COALESCE(MAX(numero),0)+1 FROM orcamentos WHERE tenant_id = ?");
            $stmtN->execute([$tid]);
            $novoNum = (int)$stmtN->fetchColumn();
            $token = bin2hex(random_bytes(24));

            $validadeDias = (int) getConfig('orcamento_validade_dias', '15');
            $novaValidade = date('Y-m-d', strtotime("+{$validadeDias} days"));

            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO orcamentos
                (tenant_id, numero, cliente_id, usuario_id, data_orcamento, validade, status,
                 subtotal, desconto_tipo, desconto_valor, total,
                 condicoes_pagamento, prazo_entrega, observacoes, observacoes_internas, token_publico)
                VALUES (?, ?, ?, ?, CURDATE(), ?, 'pendente', ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$tid, $novoNum, $orc['cliente_id'], $user['id'], $novaValidade,
                    $orc['subtotal'], $orc['desconto_tipo'], $orc['desconto_valor'], $orc['total'],
                    $orc['condicoes_pagamento'], $orc['prazo_entrega'], $orc['observacoes'], $orc['observacoes_internas'], $token]);
            $novoId = (int)$pdo->lastInsertId();

            $pdo->prepare("INSERT INTO orcamento_itens
                (tenant_id, orcamento_id, produto_id, descricao, quantidade, valor_unitario, desconto, subtotal, ordem)
                SELECT tenant_id, ?, produto_id, descricao, quantidade, valor_unitario, desconto, subtotal, ordem
                FROM orcamento_itens WHERE orcamento_id = ? AND tenant_id = ?")
                ->execute([$novoId, $id, $tid]);

            registrarHistorico($pdo, $tid, $novoId, $user['id'], 'duplicado', "Duplicado do orçamento #" . str_pad($orc['numero'],4,'0',STR_PAD_LEFT));
            registrarHistorico($pdo, $tid, $id, $user['id'], 'duplicado', "Duplicado para orçamento #" . str_pad($novoNum,4,'0',STR_PAD_LEFT));
            $pdo->commit();

            flashSuccess('Orçamento duplicado. Editando cópia.');
            redirect('orcamentos/form.php?id=' . $novoId);
            break;

        default:
            throw new Exception('Ação inválida');
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($ajax) { echo json_encode(['ok' => false, 'msg' => $e->getMessage()]); exit; }
    flashError('Erro: ' . $e->getMessage());
    redirect('orcamentos/view.php?id=' . $id);
}
