<?php
/**
 * Layout compartilhado dos artigos - SEO otimizado
 *
 * Variáveis esperadas:
 * $artigo = [
 *     'slug', 'titulo' (meta title), 'h1', 'descricao', 'keywords',
 *     'data_pub' (Y-m-d), 'data_mod' (opcional), 'categoria', 'tempo_leitura',
 *     'icone' (font-awesome), 'faq' (array de [pergunta, resposta])
 * ]
 * $conteudoHtml - HTML do conteúdo principal (entre intro e CTA final)
 * $intro - Parágrafo de introdução logo abaixo do H1
 */

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/_helpers.php';

$baseUrl = rtrim(APP_URL ?? '', '/');
$canonicalArtigo = $baseUrl . '/artigos/' . $artigo['slug'];
$canonicalListagem = $baseUrl . '/artigos/';
$dataPub = $artigo['data_pub'] ?? date('Y-m-d');
$dataMod = $artigo['data_mod'] ?? $dataPub;
$tempoLeitura = $artigo['tempo_leitura'] ?? 8;
$siteName = 'Balcão PDV';

// Outros artigos para sugestão (todos exceto o atual)
$outrosArtigos = [
    ['slug' => 'sistema-pdv-para-mercado', 'titulo' => 'Sistema PDV para Mercado', 'icone' => 'fa-shopping-cart'],
    ['slug' => 'sistema-pdv-para-padaria', 'titulo' => 'Sistema PDV para Padaria', 'icone' => 'fa-bread-slice'],
    ['slug' => 'sistema-pdv-para-material-construcao', 'titulo' => 'Sistema PDV para Material de Construção', 'icone' => 'fa-hammer'],
    ['slug' => 'sistema-pdv-para-lanchonete', 'titulo' => 'Sistema PDV para Lanchonete', 'icone' => 'fa-utensils'],
    ['slug' => 'sistema-pdv-para-loja-de-roupas', 'titulo' => 'Sistema PDV para Loja de Roupas', 'icone' => 'fa-shirt'],
];
$relacionados = array_filter($outrosArtigos, fn($o) => $o['slug'] !== $artigo['slug']);
$relacionados = array_slice($relacionados, 0, 3);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">

    <title><?= eAttr($artigo['titulo']) ?></title>
    <meta name="description" content="<?= eAttr($artigo['descricao']) ?>">
    <meta name="keywords" content="<?= eAttr($artigo['keywords']) ?>">
    <meta name="author" content="Equipe Balcão PDV">

    <link rel="canonical" href="<?= eAttr($canonicalArtigo) ?>">

    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= eAttr($artigo['titulo']) ?>">
    <meta property="og:description" content="<?= eAttr($artigo['descricao']) ?>">
    <meta property="og:url" content="<?= eAttr($canonicalArtigo) ?>">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="<?= eAttr($siteName) ?>">
    <meta property="article:published_time" content="<?= eAttr($dataPub) ?>">
    <meta property="article:modified_time" content="<?= eAttr($dataMod) ?>">
    <meta property="article:section" content="<?= eAttr($artigo['categoria']) ?>">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= eAttr($artigo['titulo']) ?>">
    <meta name="twitter:description" content="<?= eAttr($artigo['descricao']) ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Schema: Article -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Article",
      "headline": "<?= eAttr($artigo['h1']) ?>",
      "description": "<?= eAttr($artigo['descricao']) ?>",
      "image": "<?= eAttr($baseUrl) ?>/og-image.png",
      "datePublished": "<?= eAttr($dataPub) ?>T08:00:00-03:00",
      "dateModified": "<?= eAttr($dataMod) ?>T08:00:00-03:00",
      "author": {
        "@type": "Organization",
        "name": "Balcão PDV",
        "url": "<?= eAttr($baseUrl) ?>"
      },
      "publisher": {
        "@type": "Organization",
        "name": "Balcão PDV",
        "logo": {
          "@type": "ImageObject",
          "url": "<?= eAttr($baseUrl) ?>/logo.png"
        }
      },
      "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "<?= eAttr($canonicalArtigo) ?>"
      },
      "articleSection": "<?= eAttr($artigo['categoria']) ?>",
      "wordCount": <?= (int)($artigo['palavras'] ?? 1800) ?>
    }
    </script>

    <!-- Schema: Breadcrumb -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "Início", "item": "<?= eAttr($baseUrl) ?>/" },
        { "@type": "ListItem", "position": 2, "name": "Artigos", "item": "<?= eAttr($canonicalListagem) ?>" },
        { "@type": "ListItem", "position": 3, "name": "<?= eAttr($artigo['categoria']) ?>" }
      ]
    }
    </script>

    <?php if (!empty($artigo['faq'])): ?>
    <!-- Schema: FAQ -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        <?php foreach ($artigo['faq'] as $i => $f): ?>
        {
          "@type": "Question",
          "name": <?= json_encode($f['pergunta'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
          "acceptedAnswer": {
            "@type": "Answer",
            "text": <?= json_encode($f['resposta'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
          }
        }<?= $i < count($artigo['faq']) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
      ]
    }
    </script>
    <?php endif; ?>

    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #334155; line-height: 1.7; }
        .navbar-brand { font-weight: 800; }

        .article-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e40af 100%);
            color: #fff;
            padding: 80px 0 60px;
        }
        .article-hero h1 {
            font-weight: 800;
            font-size: clamp(1.8rem, 4vw, 2.75rem);
            line-height: 1.2;
            margin-bottom: 16px;
        }
        .article-hero .lead {
            font-size: 1.15rem;
            opacity: 0.9;
            max-width: 800px;
        }
        .article-meta-hero {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 24px;
        }
        .breadcrumb-blog {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 6px 14px;
            display: inline-block;
            margin-bottom: 20px;
            font-size: 0.85rem;
        }
        .breadcrumb-blog a { color: #93c5fd; text-decoration: none; }
        .breadcrumb-blog a:hover { color: #fff; }

        .article-content {
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            padding: 50px 40px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            margin-top: -30px;
            position: relative;
            z-index: 2;
        }
        .article-content h2 {
            font-weight: 700;
            color: #0f172a;
            font-size: 1.75rem;
            margin: 40px 0 16px;
            padding-bottom: 8px;
            border-bottom: 3px solid #1e40af;
        }
        .article-content h3 {
            font-weight: 700;
            color: #1e40af;
            font-size: 1.35rem;
            margin: 32px 0 12px;
        }
        .article-content p { margin-bottom: 16px; font-size: 1.05rem; }
        .article-content ul, .article-content ol { padding-left: 24px; margin-bottom: 20px; }
        .article-content li { margin-bottom: 8px; }
        .article-content strong { color: #0f172a; }
        .article-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
            font-size: 0.95rem;
        }
        .article-content table th {
            background: #1e40af;
            color: #fff;
            padding: 12px;
            text-align: left;
        }
        .article-content table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .article-content table tr:hover td { background: #f8fafc; }

        .info-box {
            background: #eff6ff;
            border-left: 4px solid #1e40af;
            padding: 20px 24px;
            border-radius: 8px;
            margin: 24px 0;
        }
        .info-box.success { background: #f0fdf4; border-color: #16a34a; }
        .info-box.warning { background: #fefce8; border-color: #ca8a04; }
        .info-box i { margin-right: 8px; }

        .cta-box {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: #fff;
            padding: 32px;
            border-radius: 12px;
            text-align: center;
            margin: 32px 0;
        }
        .cta-box h3 { color: #fff; margin-bottom: 12px; }
        .cta-box .btn {
            background: #fff;
            color: #1e40af;
            font-weight: 700;
            padding: 12px 32px;
            border-radius: 8px;
            display: inline-block;
            text-decoration: none;
            margin-top: 12px;
            transition: all 0.2s;
        }
        .cta-box .btn:hover { transform: translateY(-2px); }

        .faq-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 12px;
        }
        .faq-item h4 {
            font-weight: 700;
            color: #0f172a;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .related-posts {
            max-width: 1100px;
            margin: 60px auto 0;
            padding: 0 20px;
        }
        .related-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.2s;
            height: 100%;
        }
        .related-card:hover {
            transform: translateY(-2px);
            text-decoration: none;
            color: inherit;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .related-card .icon-circle {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: #fff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        .related-card h4 { font-size: 1rem; font-weight: 600; color: #0f172a; margin: 0; }

        .footer-blog { background: #0f172a; color: #94a3b8; padding: 40px 0; text-align: center; margin-top: 80px; }
        .footer-blog a { color: #93c5fd; text-decoration: none; }

        @media (max-width: 768px) {
            .article-content { padding: 30px 20px; border-radius: 0; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= eAttr($baseUrl) ?>/">
            <i class="fas fa-cash-register text-primary me-2"></i>Balcão PDV
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="<?= eAttr($baseUrl) ?>/">Início</a></li>
                <li class="nav-item"><a class="nav-link active fw-bold" href="<?= eAttr($canonicalListagem) ?>">Artigos</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= eAttr($baseUrl) ?>/auth/register.php">Cadastrar</a></li>
                <li class="nav-item"><a class="btn btn-primary ms-lg-2" href="<?= eAttr($baseUrl) ?>/auth/login.php">Entrar</a></li>
            </ul>
        </div>
    </div>
</nav>

<header class="article-hero">
    <div class="container">
        <nav class="breadcrumb-blog" aria-label="breadcrumb">
            <a href="<?= eAttr($baseUrl) ?>/">Início</a> <i class="fas fa-chevron-right mx-1" style="font-size:0.65rem;"></i>
            <a href="<?= eAttr($canonicalListagem) ?>">Artigos</a> <i class="fas fa-chevron-right mx-1" style="font-size:0.65rem;"></i>
            <span><?= eAttr($artigo['categoria']) ?></span>
        </nav>
        <h1><?= eAttr($artigo['h1']) ?></h1>
        <?php if (!empty($intro)): ?>
            <p class="lead"><?= $intro ?></p>
        <?php endif; ?>
        <div class="article-meta-hero">
            <span><i class="fas fa-calendar me-1"></i>Publicado em <?= date('d/m/Y', strtotime($dataPub)) ?></span>
            <span><i class="fas fa-clock me-1"></i>Leitura ~<?= $tempoLeitura ?> min</span>
            <span><i class="fas fa-folder me-1"></i><?= eAttr($artigo['categoria']) ?></span>
        </div>
    </div>
</header>

<main>
    <article class="article-content">
        <?= $conteudoHtml ?>

        <?php if (!empty($artigo['faq'])): ?>
        <h2>Perguntas Frequentes</h2>
        <?php foreach ($artigo['faq'] as $f): ?>
            <div class="faq-item">
                <h4><?= eAttr($f['pergunta']) ?></h4>
                <p class="mb-0"><?= $f['resposta'] ?></p>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <div class="cta-box">
            <h3>Comece grátis com o Balcão PDV</h3>
            <p class="mb-0">Teste todas as funcionalidades por 15 dias sem cartão de crédito.</p>
            <a href="<?= eAttr($baseUrl) ?>/auth/register.php" class="btn">
                <i class="fas fa-rocket me-2"></i>Começar agora grátis
            </a>
        </div>
    </article>

    <?php if (!empty($relacionados)): ?>
    <section class="related-posts">
        <h2 class="h4 fw-bold mb-4 text-center">Continue lendo</h2>
        <div class="row g-3">
            <?php foreach ($relacionados as $rel): ?>
            <div class="col-md-4">
                <a href="<?= eAttr($baseUrl . '/artigos/' . $rel['slug']) ?>" class="related-card">
                    <div class="icon-circle"><i class="fas <?= eAttr($rel['icone']) ?>"></i></div>
                    <h4><?= eAttr($rel['titulo']) ?></h4>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</main>

<footer class="footer-blog">
    <div class="container">
        <p class="mb-1"><strong>Balcão PDV</strong> - Sistema de PDV gratuito e profissional</p>
        <p class="mb-0">
            <a href="<?= eAttr($baseUrl) ?>/">Início</a> ·
            <a href="<?= eAttr($canonicalListagem) ?>">Artigos</a> ·
            <a href="<?= eAttr($baseUrl) ?>/auth/register.php">Criar conta grátis</a>
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
