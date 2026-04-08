<?php
$pageTitle = 'Painel do Caixa';
require_once __DIR__ . '/../../app/includes/auth.php';

$pdo = db();
$user = usuario();
$tid = tenantId();
$caixa = getCaixaAberto($user['id']);

// Dados do caixa atual
$movimentacoes = [];
$resumo = [];
$vendas = ['qtd' => 0, 'total' => 0];
$totalSangrias = 0;
$totalSuprimentos = 0;

if ($caixa) {
    $stmt = $pdo->prepare("SELECT cm.*, u.nome as usuario_nome
        FROM caixa_movimentacoes cm
        LEFT JOIN usuarios u ON u.id = cm.usuario_id
        WHERE cm.caixa_id = ? AND cm.tenant_id = ?
        ORDER BY cm.criado_em DESC");
    $stmt->execute([$caixa['id'], $tid]);
    $movimentacoes = $stmt->fetchAll();

    // Resumo de vendas do caixa
    $stmt = $pdo->prepare("SELECT vp.forma, SUM(vp.valor) as total, SUM(vp.troco) as troco
        FROM venda_pagamentos vp
        INNER JOIN vendas v ON v.id = vp.venda_id
        WHERE v.caixa_id = ? AND v.tenant_id = ? AND v.status = 'concluida'
        GROUP BY vp.forma");
    $stmt->execute([$caixa['id'], $tid]);
    $resumo = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT COUNT(*) as qtd, COALESCE(SUM(total),0) as total
        FROM vendas WHERE caixa_id = ? AND tenant_id = ? AND status = 'concluida'");
    $stmt->execute([$caixa['id'], $tid]);
    $vendas = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(valor),0) FROM caixa_movimentacoes WHERE caixa_id = ? AND tenant_id = ? AND tipo = 'sangria'");
    $stmt->execute([$caixa['id'], $tid]);
    $totalSangrias = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(valor),0) FROM caixa_movimentacoes WHERE caixa_id = ? AND tenant_id = ? AND tipo = 'suprimento'");
    $stmt->execute([$caixa['id'], $tid]);
    $totalSuprimentos = (float)$stmt->fetchColumn();
}

// Histórico de caixas
$page = max(1, (int)($_GET['page'] ?? 1));
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM caixas WHERE tenant_id = ?");
$countStmt->execute([$tid]);
$total = (int)$countStmt->fetchColumn();

$perPage = 15;
$totalPages = max(1, ceil($total / $perPage));
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT cx.*, u.nome as operador
    FROM caixas cx
    LEFT JOIN usuarios u ON u.id = cx.usuario_id
    WHERE cx.tenant_id = ?
    ORDER BY cx.aberto_em DESC
    LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute([$tid]);
$historico = $stmt->fetchAll();

$pag = [
    'data' => $historico,
    'total' => $total,
    'page' => $page,
    'perPage' => $perPage,
    'totalPages' => $totalPages,
];

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-cash-register me-2"></i>Painel do Caixa</h5>

<?php if ($caixa): ?>
    <div class="alert alert-success d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-door-open me-2"></i>
            <strong>Caixa #<?= $caixa['id'] ?> aberto</strong> em <?= formatDateTime($caixa['aberto_em']) ?>
            | Valor abertura: <?= formatMoney($caixa['valor_abertura']) ?>
        </div>
        <div>
            <a href="sangria.php" class="btn btn-warning btn-sm me-1"><i class="fas fa-arrow-up me-1"></i>Sangria</a>
            <a href="suprimento.php" class="btn btn-info btn-sm me-1"><i class="fas fa-arrow-down me-1"></i>Suprimento</a>
            <a href="fechar.php" class="btn btn-danger btn-sm"><i class="fas fa-door-closed me-1"></i>Fechar Caixa</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body text-center">
                    <small>Vendas</small>
                    <h4><?= formatMoney($vendas['total'] ?? 0) ?></h4>
                    <small><?= $vendas['qtd'] ?? 0 ?> venda(s)</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark shadow-sm">
                <div class="card-body text-center">
                    <small>Sangrias</small>
                    <h4><?= formatMoney($totalSangrias) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body text-center">
                    <small>Suprimentos</small>
                    <h4><?= formatMoney($totalSuprimentos) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body text-center">
                    <small>Por Forma Pgto</small>
                    <?php foreach ($resumo as $r): ?>
                        <div class="small"><?= ucfirst(e($r['forma'])) ?>: <?= formatMoney($r['total'] - $r['troco']) ?></div>
                    <?php endforeach; ?>
                    <?php if (empty($resumo)): ?>
                        <div class="small text-white-50">Sem vendas</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($movimentacoes)): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header">Movimentações do Caixa Atual</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Data</th><th>Tipo</th><th>Valor</th><th>Motivo</th><th>Usuário</th></tr></thead>
                <tbody>
                <?php foreach ($movimentacoes as $m): ?>
                    <tr>
                        <td><?= formatDateTime($m['criado_em']) ?></td>
                        <td><span class="badge bg-<?= $m['tipo'] === 'sangria' ? 'warning' : 'info' ?>"><?= ucfirst(e($m['tipo'])) ?></span></td>
                        <td><?= formatMoney($m['valor']) ?></td>
                        <td><?= e($m['motivo']) ?></td>
                        <td><?= e($m['usuario_nome']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
<?php else: ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>Caixa fechado.
        <a href="abrir.php" class="btn btn-success btn-sm ms-3"><i class="fas fa-door-open me-1"></i>Abrir Caixa</a>
    </div>
<?php endif; ?>

<!-- Histórico de caixas -->
<div class="card shadow-sm">
    <div class="card-header">Histórico de Caixas</div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Operador</th><th>Abertura</th><th>Fechamento</th><th>Vlr.Abertura</th><th>Vlr.Fechamento</th><th>Diferença</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($pag['data'] as $cx): ?>
                <tr>
                    <td><?= $cx['id'] ?></td>
                    <td><?= e($cx['operador']) ?></td>
                    <td><?= formatDateTime($cx['aberto_em']) ?></td>
                    <td><?= $cx['fechado_em'] ? formatDateTime($cx['fechado_em']) : '-' ?></td>
                    <td><?= formatMoney($cx['valor_abertura']) ?></td>
                    <td><?= $cx['valor_fechamento'] !== null ? formatMoney($cx['valor_fechamento']) : '-' ?></td>
                    <td class="<?= ($cx['diferenca'] ?? 0) < 0 ? 'text-danger' : (($cx['diferenca'] ?? 0) > 0 ? 'text-warning' : '') ?>">
                        <?= $cx['diferenca'] !== null ? formatMoney($cx['diferenca']) : '-' ?>
                    </td>
                    <td><span class="badge bg-<?= $cx['status'] === 'aberto' ? 'success' : 'secondary' ?>"><?= ucfirst(e($cx['status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($pag['data'])): ?>
                <tr><td colspan="8" class="text-center text-muted py-3">Nenhum caixa registrado</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3"><?= renderPagination($pag) ?></div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
