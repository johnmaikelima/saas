<?php
require_once __DIR__ . '/../../app/config.php';

$canonical = rtrim(APP_URL ?? '', '/') . '/artigos/';
$siteName = 'Balcão PDV';

$artigos = [
    [
        'slug' => 'sistema-pdv-para-mercado',
        'titulo' => 'Sistema PDV para Mercado e Mercadinho: Guia Completo 2026',
        'descricao' => 'Descubra como escolher o melhor sistema PDV para mercado pequeno e mercadinho. Controle de estoque, balança, NFC-e e gestão completa.',
        'imagem' => 'mercado',
        'icone' => 'fa-shopping-cart',
        'categoria' => 'Mercados',
        'data' => '2026-05-03',
    ],
    [
        'slug' => 'sistema-pdv-para-padaria',
        'titulo' => 'Sistema PDV para Padaria: Como Otimizar sua Operação',
        'descricao' => 'Sistema PDV completo para padarias com controle de produção, balança integrada, NFC-e e gestão de estoque para confeitaria.',
        'imagem' => 'padaria',
        'icone' => 'fa-bread-slice',
        'categoria' => 'Padarias',
        'data' => '2026-05-03',
    ],
    [
        'slug' => 'sistema-pdv-para-material-construcao',
        'titulo' => 'Sistema PDV para Loja de Material de Construção',
        'descricao' => 'Sistema completo para loja de material de construção: orçamentos, estoque amplo, NFC-e e controle de vendas a prazo.',
        'imagem' => 'construcao',
        'icone' => 'fa-hammer',
        'categoria' => 'Material de Construção',
        'data' => '2026-05-03',
    ],
    [
        'slug' => 'sistema-pdv-para-lanchonete',
        'titulo' => 'Sistema PDV para Lanchonete, Bar e Restaurante',
        'descricao' => 'Sistema PDV ideal para lanchonete, bar e restaurante: comandas, multi-PDV, agilidade e integração com NFC-e.',
        'imagem' => 'lanchonete',
        'icone' => 'fa-utensils',
        'categoria' => 'Alimentação',
        'data' => '2026-05-03',
    ],
    [
        'slug' => 'sistema-pdv-para-loja-de-roupas',
        'titulo' => 'Sistema PDV para Loja de Roupas e Boutique',
        'descricao' => 'Sistema PDV completo para loja de roupas: controle por tamanho/cor, fidelização de clientes, NFC-e e relatórios de vendas.',
        'imagem' => 'roupas',
        'icone' => 'fa-shirt',
        'categoria' => 'Vestuário',
        'data' => '2026-05-03',
    ],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow, max-image-preview:large">

    <title>Artigos sobre Sistema PDV | <?= e($siteName) ?></title>
    <meta name="description" content="Artigos completos sobre sistemas PDV para diferentes tipos de comércio. Aprenda como escolher e implementar o melhor sistema para seu negócio.">
    <meta name="keywords" content="sistema pdv, artigos sobre pdv, sistema para comercio, sistema para varejo, gestao comercial">
    <link rel="canonical" href="<?= e($canonical) ?>">

    <meta property="og:type" content="website">
    <meta property="og:title" content="Artigos sobre Sistema PDV | <?= e($siteName) ?>">
    <meta property="og:description" content="Conteúdo completo sobre sistemas PDV para todos os tipos de comércio. Mercado, padaria, lanchonete, lojas e muito mais.">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="<?= e($siteName) ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Blog",
      "name": "Blog do Balcão PDV",
      "description": "Artigos sobre sistemas PDV para diferentes tipos de comércio",
      "url": "<?= e($canonical) ?>",
      "publisher": {
        "@type": "Organization",
        "name": "Balcão PDV",
        "url": "<?= e(rtrim(APP_URL ?? '', '/')) ?>"
      },
      "blogPost": [
        <?php foreach ($artigos as $i => $a): ?>
        {
          "@type": "BlogPosting",
          "headline": "<?= e($a['titulo']) ?>",
          "description": "<?= e($a['descricao']) ?>",
          "datePublished": "<?= e($a['data']) ?>",
          "url": "<?= e($canonical . $a['slug']) ?>"
        }<?= $i < count($artigos) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
      ]
    }
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .navbar-brand { font-weight: 800; }
        .hero-blog {
            background: linear-gradient(135deg, #0f172a 0%, #1e40af 100%);
            color: #fff;
            padding: 100px 0 80px;
            text-align: center;
        }
        .hero-blog h1 { font-weight: 800; font-size: clamp(2rem, 5vw, 3rem); margin-bottom: 1rem; }
        .hero-blog p { font-size: 1.15rem; opacity: 0.9; max-width: 720px; margin: 0 auto; }
        .breadcrumb-blog { background: rgba(255,255,255,0.1); border-radius: 8px; padding: 8px 16px; display: inline-block; margin-top: 24px; }
        .breadcrumb-blog a { color: #93c5fd; text-decoration: none; }
        .breadcrumb-blog a:hover { color: #fff; }

        .article-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
        }
        .article-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.12);
            text-decoration: none;
            color: inherit;
        }
        .article-icon {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: #fff;
            padding: 50px 20px;
            text-align: center;
            font-size: 4rem;
        }
        .article-body { padding: 24px; flex: 1; display: flex; flex-direction: column; }
        .article-category {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 12px;
            align-self: flex-start;
        }
        .article-card h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        .article-card p { color: #64748b; font-size: 0.95rem; flex: 1; }
        .article-meta { font-size: 0.85rem; color: #94a3b8; margin-top: 16px; }
        .article-meta i { margin-right: 4px; }

        .footer-blog { background: #0f172a; color: #94a3b8; padding: 40px 0; text-align: center; margin-top: 80px; }
        .footer-blog a { color: #93c5fd; text-decoration: none; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="<?= e(rtrim(APP_URL ?? '', '/')) ?>/">
            <i class="fas fa-cash-register text-primary me-2"></i>Balcão PDV
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="<?= e(rtrim(APP_URL ?? '', '/')) ?>/">Início</a></li>
                <li class="nav-item"><a class="nav-link active fw-bold" href="<?= e($canonical) ?>">Artigos</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(rtrim(APP_URL ?? '', '/')) ?>/auth/register.php">Cadastrar</a></li>
                <li class="nav-item"><a class="btn btn-primary ms-lg-2" href="<?= e(rtrim(APP_URL ?? '', '/')) ?>/auth/login.php">Entrar</a></li>
            </ul>
        </div>
    </div>
</nav>

<header class="hero-blog">
    <div class="container">
        <h1>Artigos e Guias sobre Sistemas PDV</h1>
        <p>Conteúdo prático e completo sobre como escolher, implementar e usar sistemas PDV para o seu tipo de comércio.</p>
        <nav class="breadcrumb-blog" aria-label="breadcrumb">
            <a href="<?= e(rtrim(APP_URL ?? '', '/')) ?>/">Início</a> <i class="fas fa-chevron-right mx-2" style="font-size:0.7rem;"></i>
            <span>Artigos</span>
        </nav>
    </div>
</header>

<main class="py-5">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($artigos as $a): ?>
            <div class="col-md-6 col-lg-4">
                <a href="<?= e($canonical . $a['slug']) ?>" class="article-card" aria-label="Ler artigo: <?= e($a['titulo']) ?>">
                    <div class="article-icon">
                        <i class="fas <?= e($a['icone']) ?>"></i>
                    </div>
                    <div class="article-body">
                        <span class="article-category"><?= e($a['categoria']) ?></span>
                        <h2><?= e($a['titulo']) ?></h2>
                        <p><?= e($a['descricao']) ?></p>
                        <div class="article-meta">
                            <i class="fas fa-calendar"></i><?= date('d/m/Y', strtotime($a['data'])) ?>
                            <span class="ms-3"><i class="fas fa-clock"></i>Leitura ~8 min</span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<footer class="footer-blog">
    <div class="container">
        <p class="mb-1"><strong>Balcão PDV</strong> - Sistema de PDV gratuito e profissional</p>
        <p class="mb-0"><a href="<?= e(rtrim(APP_URL ?? '', '/')) ?>/">Voltar ao site</a></p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
