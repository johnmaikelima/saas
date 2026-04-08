<?php
$pageTitle = 'Relatórios';
require_once __DIR__ . '/../../app/includes/auth.php';
if (!temPerfil('admin', 'gerente')) { flashError('Sem permissão.'); redirect('dashboard/'); }

require __DIR__ . '/../../app/includes/header.php';
?>

<h5 class="page-title"><i class="fas fa-chart-bar me-2"></i>Relatórios</h5>

<div class="row g-3">
    <div class="col-md-4">
        <a href="vendas_periodo.php" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body text-center">
                <i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i>
                <h6>Vendas por Período</h6>
                <small class="text-muted">Diário, semanal, mensal</small>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="vendas_produto.php" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body text-center">
                <i class="fas fa-trophy fa-3x text-success mb-3"></i>
                <h6>Ranking de Produtos</h6>
                <small class="text-muted">Mais vendidos por quantidade e valor</small>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="vendas_categoria.php" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body text-center">
                <i class="fas fa-chart-pie fa-3x text-warning mb-3"></i>
                <h6>Vendas por Categoria</h6>
                <small class="text-muted">Distribuição por categoria</small>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="vendas_pagamento.php" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body text-center">
                <i class="fas fa-credit-card fa-3x text-info mb-3"></i>
                <h6>Formas de Pagamento</h6>
                <small class="text-muted">Distribuição por forma de pagamento</small>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="vendas_vendedor.php" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body text-center">
                <i class="fas fa-user-tie fa-3x text-secondary mb-3"></i>
                <h6>Vendas por Vendedor</h6>
                <small class="text-muted">Performance por operador</small>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="lucratividade.php" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body text-center">
                <i class="fas fa-dollar-sign fa-3x text-success mb-3"></i>
                <h6>Lucratividade</h6>
                <small class="text-muted">Margem de lucro por produto</small>
            </div>
        </a>
    </div>
</div>

<?php require __DIR__ . '/../../app/includes/footer.php'; ?>
