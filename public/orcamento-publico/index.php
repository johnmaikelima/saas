<?php
/**
 * Pagina PUBLICA de aprovacao de orcamento.
 * Acessivel apenas via token. NAO requer login.
 */
require_once __DIR__ . '/../../app/bootstrap.php';

$pdo = db();
$token = $_GET['t'] ?? '';
$token = preg_replace('/[^a-f0-9]/', '', $token);

if (strlen($token) < 32) {
    http_response_code(404);
    exit('Link inválido');
}

$stmt = $pdo->prepare("SELECT o.*, t.nome_fantasia, t.razao_social, t.cnpj, t.telefone, t.endereco, t.cidade, t.estado, t.logo,
    u.nome as vendedor_nome, c.nome as cliente_nome
    FROM orcamentos o
    LEFT JOIN tenants t ON t.id = o.tenant_id
    LEFT JOIN usuarios u ON u.id = o.usuario_id
    LEFT JOIN clientes c ON c.id = o.cliente_id
    WHERE o.token_publico = ?");
$stmt->execute([$token]);
$orc = $stmt->fetch();

if (!$orc) {
    http_response_code(404);
    exit('Orçamento não encontrado');
}

$tid = (int)$orc['tenant_id'];

// Expirar se vencido
if ($orc['status'] === 'pendente' && $orc['validade'] < date('Y-m-d')) {
    $pdo->prepare("UPDATE orcamentos SET status='expirado' WHERE id=?")->execute([$orc['id']]);
    $orc['status'] = 'expirado';
}

// Processar acao
$msg = null;
$msgTipo = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $nomeQuem = substr(trim($_POST['nome'] ?? ''), 0, 100);

    if ($orc['status'] !== 'pendente') {
        $msg = 'Este orçamento não está mais disponível para aprovação.';
        $msgTipo = 'warning';
    } elseif ($acao === 'aprovar') {
        $pdo->prepare("UPDATE orcamentos SET status='aprovado', aprovado_em=NOW() WHERE id=?")
            ->execute([$orc['id']]);
        $pdo->prepare("INSERT INTO orcamento_historico (tenant_id, orcamento_id, usuario_id, acao, descricao) VALUES (?,?,?,?,?)")
            ->execute([$tid, $orc['id'], null, 'aprovado', 'Aprovado pelo cliente' . ($nomeQuem ? " ({$nomeQuem})" : '') . ' via link público']);
        $orc['status'] = 'aprovado';
        $msg = 'Orçamento aprovado com sucesso! Em breve entraremos em contato.';
        $msgTipo = 'success';
    } elseif ($acao === 'recusar') {
        $motivo = substr(trim($_POST['motivo'] ?? ''), 0, 500);
        $pdo->prepare("UPDATE orcamentos SET status='recusado', recusado_em=NOW(), recusa_motivo=? WHERE id=?")
            ->execute([$motivo, $orc['id']]);
        $pdo->prepare("INSERT INTO orcamento_historico (tenant_id, orcamento_id, usuario_id, acao, descricao) VALUES (?,?,?,?,?)")
            ->execute([$tid, $orc['id'], null, 'recusado', 'Recusado pelo cliente' . ($nomeQuem ? " ({$nomeQuem})" : '') . ($motivo ? ': ' . $motivo : '') . ' via link público']);
        $orc['status'] = 'recusado';
        $msg = 'Recusa registrada. Obrigado pelo retorno.';
        $msgTipo = 'info';
    }
}

$stmtI = $pdo->prepare("SELECT * FROM orcamento_itens WHERE orcamento_id = ? AND tenant_id = ? ORDER BY ordem, id");
$stmtI->execute([$orc['id'], $tid]);
$itens = $stmtI->fetchAll();

$numero = str_pad($orc['numero'], 4, '0', STR_PAD_LEFT);
$descontoReal = $orc['desconto_tipo'] === 'percentual' ? ($orc['subtotal'] * $orc['desconto_valor'] / 100) : $orc['desconto_valor'];

// Carregar helper de escape simples
function pe(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'); }
function pMoney(float $v): string { return 'R$ ' . number_format($v, 2, ',', '.'); }
function pDate(string $d): string { return $d ? date('d/m/Y', strtotime($d)) : ''; }

$logoUrl = !empty($orc['logo']) ? baseUrl('uploads/' . $orc['logo']) : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento #<?= $numero ?> - <?= pe($orc['nome_fantasia'] ?? $orc['razao_social']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fb; }
        .wrapper { max-width: 880px; margin: 30px auto; background: #fff; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); overflow: hidden; }
        .cabecalho { background: linear-gradient(135deg, #002C87 0%, #0044bb 100%); color: #fff; padding: 24px 30px; }
        .cabecalho h1 { font-size: 22px; margin: 0; font-weight: 700; }
        .cabecalho .numero { font-size: 15px; opacity: 0.9; margin-top: 4px; }
        .cabecalho .empresa { text-align: right; }
        .cabecalho .logo-empresa { max-height: 60px; max-width: 180px; background: #fff; padding: 6px 10px; border-radius: 6px; margin-bottom: 6px; }
        .section-title { color: #002C87; font-weight: 700; margin: 20px 0 10px; border-bottom: 2px solid #002C87; padding-bottom: 6px; }
        .status-pill { padding: 6px 14px; border-radius: 50px; font-weight: 600; display: inline-block; font-size: 13px; }
        .status-pendente { background: #fff3cd; color: #856404; }
        .status-aprovado { background: #d4edda; color: #155724; }
        .status-recusado { background: #f8d7da; color: #721c24; }
        .status-convertido { background: #cce5ff; color: #004085; }
        .status-expirado, .status-cancelado { background: #e2e3e5; color: #383d41; }
        .total-final { background: #002C87; color: #fff; font-size: 22px; font-weight: 700; padding: 14px 20px; border-radius: 8px; text-align: right; }
        .btn-aprovar { background: #28a745; border: 0; color: #fff; font-weight: 700; padding: 14px; font-size: 16px; }
        .btn-aprovar:hover { background: #218838; color: #fff; }
        .btn-recusar { background: #fff; border: 1px solid #dc3545; color: #dc3545; font-weight: 600; padding: 14px; font-size: 16px; }
        .btn-recusar:hover { background: #dc3545; color: #fff; }
        table.tbl-itens th { background: #002C87; color: #fff; font-weight: 600; font-size: 13px; }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="cabecalho d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1><i class="fas fa-file-signature me-2"></i>ORÇAMENTO</h1>
            <div class="numero">Nº <?= $numero ?> · Validade: <?= pDate($orc['validade']) ?></div>
        </div>
        <div class="empresa">
            <?php if ($logoUrl): ?>
                <div><img src="<?= pe($logoUrl) ?>" alt="Logo" class="logo-empresa"></div>
            <?php endif; ?>
            <div class="fw-bold fs-5"><?= pe($orc['nome_fantasia'] ?? $orc['razao_social']) ?></div>
            <?php if ($orc['cnpj']): ?><div class="small">CNPJ: <?= pe($orc['cnpj']) ?></div><?php endif; ?>
            <?php if ($orc['telefone']): ?><div class="small">Tel: <?= pe($orc['telefone']) ?></div><?php endif; ?>
        </div>
    </div>

    <div class="p-4">
        <?php if ($msg): ?>
            <div class="alert alert-<?= pe($msgTipo) ?>"><?= pe($msg) ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="small text-muted">Cliente</div>
                <div class="fw-bold"><?= pe($orc['cliente_nome'] ?: 'Cliente') ?></div>
            </div>
            <span class="status-pill status-<?= pe($orc['status']) ?>"><?= pe(ucfirst($orc['status'])) ?></span>
        </div>

        <h5 class="section-title">Itens</h5>
        <div class="table-responsive">
            <table class="table tbl-itens align-middle">
                <thead>
                    <tr>
                        <th>Descrição</th>
                        <th class="text-end">Qtd</th>
                        <th class="text-end">Vlr Unit.</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($itens as $it): ?>
                    <tr>
                        <td><?= pe($it['descricao']) ?></td>
                        <td class="text-end"><?= rtrim(rtrim(number_format($it['quantidade'], 3, ',', '.'), '0'), ',') ?></td>
                        <td class="text-end"><?= pMoney((float)$it['valor_unitario']) ?></td>
                        <td class="text-end fw-bold"><?= pMoney((float)$it['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="row mt-3">
            <div class="col-md-7">
                <?php if ($orc['condicoes_pagamento']): ?>
                    <h6 class="section-title">Condições de Pagamento</h6>
                    <p class="small"><?= nl2br(pe($orc['condicoes_pagamento'])) ?></p>
                <?php endif; ?>
                <?php if ($orc['prazo_entrega']): ?>
                    <h6 class="section-title">Prazo de Entrega</h6>
                    <p class="small"><?= nl2br(pe($orc['prazo_entrega'])) ?></p>
                <?php endif; ?>
                <?php if ($orc['observacoes']): ?>
                    <h6 class="section-title">Observações</h6>
                    <p class="small"><?= nl2br(pe($orc['observacoes'])) ?></p>
                <?php endif; ?>
            </div>
            <div class="col-md-5">
                <div class="mb-2 d-flex justify-content-between">
                    <span>Subtotal:</span><span><?= pMoney((float)$orc['subtotal']) ?></span>
                </div>
                <?php if ($orc['desconto_valor'] > 0): ?>
                <div class="mb-2 d-flex justify-content-between text-danger">
                    <span>Desconto:</span><span>-<?= pMoney((float)$descontoReal) ?></span>
                </div>
                <?php endif; ?>
                <div class="total-final d-flex justify-content-between">
                    <span>TOTAL</span><span><?= pMoney((float)$orc['total']) ?></span>
                </div>
            </div>
        </div>

        <?php if ($orc['status'] === 'pendente'): ?>
            <hr class="my-4">
            <h5 class="section-title">Sua Resposta</h5>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <form method="POST" onsubmit="return confirm('Confirmar aprovação do orçamento?')">
                        <input type="hidden" name="acao" value="aprovar">
                        <input type="text" class="form-control mb-2" name="nome" placeholder="Seu nome (opcional)" maxlength="100">
                        <button type="submit" class="btn btn-aprovar w-100">
                            <i class="fas fa-check-circle me-2"></i>APROVAR ORÇAMENTO
                        </button>
                    </form>
                </div>
                <div class="col-md-6">
                    <form method="POST" onsubmit="return confirm('Confirmar a recusa do orçamento?')">
                        <input type="hidden" name="acao" value="recusar">
                        <input type="text" class="form-control mb-2" name="nome" placeholder="Seu nome (opcional)" maxlength="100">
                        <textarea name="motivo" class="form-control mb-2" placeholder="Motivo (opcional)" rows="1" maxlength="500"></textarea>
                        <button type="submit" class="btn btn-recusar w-100">
                            <i class="fas fa-times-circle me-2"></i>RECUSAR
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4 small text-muted">
            Emitido em <?= pDate($orc['data_orcamento']) ?>
            · Vendedor: <?= pe($orc['vendedor_nome'] ?: '-') ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
