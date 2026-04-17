<?php
$pageTitle = $pageTitle ?? APP_NAME;
$user = usuario();
$empresa = getEmpresa();
$nomeLoja = getConfig('nome_loja', $empresa['nome_fantasia'] ?? APP_NAME);
$logoNavbar = !empty($empresa['logo']) ? baseUrl('uploads/' . $empresa['logo']) : '';
$caixaAberto = $user ? getCaixaAberto() : null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e($nomeLoja) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= baseUrl('assets/css/app.css') ?>" rel="stylesheet">
    <?php if (isset($extraCss)): foreach($extraCss as $css): ?>
        <link href="<?= baseUrl($css) ?>" rel="stylesheet">
    <?php endforeach; endif; ?>
</head>
<body>

<?php if ($user): ?>
<nav class="navbar navbar-expand-lg shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= baseUrl('dashboard/') ?>">
            <?php if ($logoNavbar): ?>
                <img src="<?= e($logoNavbar) ?>" alt="<?= e($nomeLoja) ?>" class="navbar-logo">
            <?php else: ?>
                <i class="fas fa-cash-register me-2"></i><?= e($nomeLoja) ?>
            <?php endif; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= baseUrl('dashboard/') ?>">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= baseUrl('pdv/') ?>">
                        <i class="fas fa-shopping-cart me-1"></i>PDV
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-boxes-stacked me-1"></i>Cadastros
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= baseUrl('produtos/') ?>"><i class="fas fa-barcode me-2"></i>Produtos</a></li>
                        <li><a class="dropdown-item" href="<?= baseUrl('categorias/') ?>"><i class="fas fa-tags me-2"></i>Categorias</a></li>
                        <li><a class="dropdown-item" href="<?= baseUrl('clientes/') ?>"><i class="fas fa-users me-2"></i>Clientes</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-warehouse me-1"></i>Estoque
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= baseUrl('estoque/') ?>"><i class="fas fa-boxes-stacked me-2"></i>Movimentações</a></li>
                        <li><a class="dropdown-item" href="<?= baseUrl('estoque/entrada.php') ?>"><i class="fas fa-arrow-down me-2"></i>Entrada</a></li>
                        <li><a class="dropdown-item" href="<?= baseUrl('estoque/historico.php') ?>"><i class="fas fa-history me-2"></i>Histórico</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= baseUrl('vendas/') ?>">
                        <i class="fas fa-receipt me-1"></i>Vendas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= baseUrl('orcamentos/') ?>">
                        <i class="fas fa-file-signature me-1"></i>Orçamentos
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-cash-register me-1"></i>Caixa
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= baseUrl('caixa/') ?>"><i class="fas fa-desktop me-2"></i>Painel do Caixa</a></li>
                        <li><a class="dropdown-item" href="<?= baseUrl('caixa/abrir.php') ?>"><i class="fas fa-door-open me-2"></i>Abrir Caixa</a></li>
                        <li><a class="dropdown-item" href="<?= baseUrl('caixa/sangria.php') ?>"><i class="fas fa-arrow-up me-2"></i>Sangria</a></li>
                        <li><a class="dropdown-item" href="<?= baseUrl('caixa/suprimento.php') ?>"><i class="fas fa-arrow-down me-2"></i>Suprimento</a></li>
                    </ul>
                </li>

                <?php if (temPerfil('admin', 'gerente')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= baseUrl('nfce/') ?>">
                        <i class="fas fa-file-invoice me-1"></i>NFC-e
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= baseUrl('relatorios/') ?>">
                        <i class="fas fa-chart-bar me-1"></i>Relatórios
                    </a>
                </li>
                <?php endif; ?>

                <?php if (temPerfil('admin')): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-cog me-1"></i>Admin
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= baseUrl('usuarios/') ?>"><i class="fas fa-user-shield me-2"></i>Usuários</a></li>
                        <li><a class="dropdown-item" href="<?= baseUrl('configuracao/') ?>"><i class="fas fa-sliders me-2"></i>Configurações</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav">
                <?php if (!empty($_SESSION['impersonate'])): ?>
                <li class="nav-item me-3">
                    <span class="nav-link" style="background: rgba(250,204,21,0.2); color: #fbbf24; border-radius: 6px; font-size: 0.8rem; font-weight: 600;">
                        <i class="fas fa-eye me-1"></i>Acesso Admin
                    </span>
                </li>
                <?php endif; ?>
                <li class="nav-item me-3">
                    <?php if ($caixaAberto): ?>
                        <span class="nav-link text-success"><i class="fas fa-circle me-1"></i>Caixa Aberto</span>
                    <?php else: ?>
                        <span class="nav-link text-danger"><i class="fas fa-circle me-1"></i>Caixa Fechado</span>
                    <?php endif; ?>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?= e($user['nome']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small"><?= ucfirst(e($user['perfil'])) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= baseUrl('auth/logout.php') ?>"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>

<main class="<?= isset($fullWidth) ? '' : 'container-fluid' ?> py-3">
    <?= renderFlash() ?>
