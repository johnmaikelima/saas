<?php
$pageTitle = 'Formas de Pagamento';
require_once __DIR__ . '/../../app/includes/auth.php';
if (!temPerfil('admin', 'gerente')) { flashError('Sem permissão.'); redirect('dashboard/'); }

$pdo = db();
$tid = tenantId();
$dataInicio = sanitize($_GET['data_inicio'] ?? date('Y-m-01'));
$dataFim = sanitize($_GET['data_fim'] ?? date('Y-m-d'));

$stmt = $pdo->prepare("SELECT vp.forma, SUM(vp.valor) as total, COUNT(*) as qtd
    FROM venda_pagamentos vp INNER JOIN vendas v ON v.id = vp.venda_id
    WHERE v.tenant_id = ? AND v.status = 'concluida' AND DATE(v.criado_em) BETWEEN ? AND ?
    GROUP BY vp.forma ORDER BY total DESC");
$stmt->execute([$tid, $dataInicio, $dataFim]);
$dados = $stmt->fetchAll();

$nomes = ['dinheiro'=>'Dinheiro','pix'=>'PIX','debito'=>'Débito','credito'=>'Crédito'];
require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-credit-card me-2"></i>Formas de Pagamento</h5>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small mb-0">De</label><input type="date" class="form-control form-control-sm" name="data_inicio" value="<?= e($dataInicio) ?>"></div>
            <div class="col-md-3"><label class="form-label small mb-0">Até</label><input type="date" class="form-control form-control-sm" name="data_fim" value="<?= e($dataFim) ?>"></div>
            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button></div>
        </form>
    </div>
</div>

<div class="row">
    <?php foreach ($dados as $d): ?>
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <h6 class="text-muted"><?= e($nomes[$d['forma']] ?? $d['forma']) ?></h6>
                <h3><?= formatMoney($d['total']) ?></h3>
                <small class="text-muted"><?= $d['qtd'] ?> pagamento(s)</small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
