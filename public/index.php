<?php
// Landing page pública - sem autenticação
require_once __DIR__ . '/../app/config.php';

// Buscar planos do Painel (com cache de 10 minutos)
function fetchPlanosCached(string $tipo, bool $incluirFree = false): array {
    $cacheKey = $tipo . ($incluirFree ? '_free' : '');
    $cacheFile = STORAGE_PATH . '/cache_planos_' . $cacheKey . '.json';
    $cacheValido = file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 600;

    if ($cacheValido) {
        return json_decode(file_get_contents($cacheFile), true) ?: [];
    }

    if (empty(PAINEL_API_URL)) return [];

    try {
        $url = PAINEL_API_URL . '?action=planos&tipo=' . urlencode($tipo);
        if ($incluirFree) $url .= '&incluir_free=1';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        if ($result) {
            $data = json_decode($result, true);
            if (!empty($data['ok']) && !empty($data['planos'])) {
                @file_put_contents($cacheFile, json_encode($data['planos']));
                return $data['planos'];
            }
        }
    } catch (\Throwable $e) {
        // Silenciar - usa fallback
    }
    return [];
}

$planos = fetchPlanosCached('saas');
$planosDesktop = fetchPlanosCached('desktop', true);

// Fallback se API falhar (planos SaaS)
if (empty($planos)) {
    $planos = [
        ['nome' => 'Starter',    'slug' => 'saas-starter-mensal',    'preco' => 99.90,  'recursos' => ['descricao' => 'Para quem está começando', 'beneficios' => ['Até 500 produtos','2 usuários','PDV completo','Controle de estoque','Relatórios básicos']]],
        ['nome' => 'Business',   'slug' => 'saas-business-mensal',   'preco' => 199.90, 'recursos' => ['descricao' => 'Para pequenos negócios', 'destaque' => true, 'beneficios' => ['Até 2.000 produtos','5 usuários','PDV completo','Controle de estoque','Gestão de clientes','Relatórios avançados']]],
        ['nome' => 'Enterprise', 'slug' => 'saas-enterprise-mensal', 'preco' => 399.90, 'recursos' => ['descricao' => 'Para empresas em crescimento', 'beneficios' => ['Produtos ilimitados','Usuários ilimitados','PDV completo','Controle de estoque','Gestão de clientes','Relatórios avançados','Suporte prioritário 24/7']]],
    ];
}

// Extrair limite de NFC-e do plano Desktop Free (com fallback)
$limiteNfceFree = 30;
foreach ($planosDesktop as $p) {
    if (($p['slug'] ?? '') === 'desktop-free') {
        $limiteNfceFree = (int)($p['limite_nfce'] ?? 30);
        break;
    }
}
$limiteNfceFreeTexto = $limiteNfceFree > 0 ? (string)$limiteNfceFree : 'ilimitadas';
$limiteNfceFreeStat = $limiteNfceFree > 0 ? (string)$limiteNfceFree : '∞';

function slugCurto(string $slug): string {
    return str_replace(['saas-', '-mensal', '-trimestral', '-anual'], '', $slug);
}

$canonical = rtrim(APP_URL ?? '', '/') . '/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow, max-image-preview:large">

    <title>PDV Grátis para Sempre - Sistema de Caixa Gratuito | Balcão PDV</title>
    <meta name="description" content="Baixe o Balcão PDV grátis e use para sempre. Sistema de PDV completo com controle de estoque, clientes, relatórios e emissão de NFC-e. Sem mensalidade no plano grátis.">
    <meta name="keywords" content="pdv gratis, sistema pdv gratis, pdv gratuito, sistema de caixa gratis, frente de caixa gratis, programa de pdv gratis, baixar pdv gratis, pdv para sempre, sistema de vendas gratis, emissor nfce gratis">
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">

    <meta property="og:type" content="website">
    <meta property="og:title" content="PDV Grátis para Sempre - Balcão PDV">
    <meta property="og:description" content="Sistema PDV gratuito completo. Controle vendas, estoque e clientes sem pagar mensalidade. Baixe agora.">
    <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="Balcão PDV">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="PDV Grátis para Sempre - Balcão PDV">
    <meta name="twitter:description" content="Sistema PDV gratuito completo. Baixe agora e comece a vender em minutos.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "Balcão PDV",
      "alternateName": "Balcão PDV - Sistema de Caixa Gratuito",
      "applicationCategory": "BusinessApplication",
      "applicationSubCategory": "Point of Sale Software",
      "operatingSystem": "Windows 10, Windows 11",
      "description": "Sistema de PDV (Ponto de Venda) gratuito para sempre. Controle de vendas, estoque, clientes, relatórios e emissão de NFC-e.",
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "BRL",
        "availability": "https://schema.org/InStock"
      },
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.8",
        "ratingCount": "127",
        "bestRating": "5",
        "worstRating": "1"
      },
      "featureList": [
        "PDV com atalhos de teclado",
        "Controle de estoque automático",
        "Cadastro de clientes",
        "Relatórios de vendas",
        "Emissão de NFC-e",
        "Múltiplas formas de pagamento",
        "Funciona offline"
      ]
    }
    </script>

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "O Balcão PDV é realmente grátis?",
          "acceptedAnswer": {"@type": "Answer", "text": "Sim. O Balcão PDV Desktop é gratuito para sempre, sem mensalidade e sem taxa oculta. Você pode baixar, instalar e usar todas as funcionalidades essenciais sem pagar nada."}
        },
        {
          "@type": "Question",
          "name": "O PDV grátis tem limite de vendas?",
          "acceptedAnswer": {"@type": "Answer", "text": "Não. Você pode registrar quantas vendas quiser no plano grátis. O único limite é a quantidade de NFC-e emitidas por mês (até <?= $limiteNfceFreeTexto ?> notas no plano grátis)."}
        },
        {
          "@type": "Question",
          "name": "Funciona sem internet?",
          "acceptedAnswer": {"@type": "Answer", "text": "Sim. A versão desktop funciona 100% offline. Os dados ficam salvos no seu computador e você só precisa de internet para emitir NFC-e ou fazer backup na nuvem."}
        },
        {
          "@type": "Question",
          "name": "Em qual sistema operacional funciona?",
          "acceptedAnswer": {"@type": "Answer", "text": "O Balcão PDV Desktop funciona em Windows 10 e Windows 11. Em breve disponibilizaremos versões para Linux e MacOS."}
        },
        {
          "@type": "Question",
          "name": "Posso usar o sistema no meu negócio comercialmente?",
          "acceptedAnswer": {"@type": "Answer", "text": "Sim. O Balcão PDV pode ser usado em qualquer estabelecimento comercial: lojas, mercearias, lanchonetes, papelarias, conveniências, farmácias, restaurantes e qualquer outro negócio que precise de um sistema de caixa."}
        },
        {
          "@type": "Question",
          "name": "Preciso pagar para emitir NFC-e?",
          "acceptedAnswer": {"@type": "Answer", "text": "No plano grátis você pode emitir até <?= $limiteNfceFreeTexto ?> NFC-e por mês sem pagar nada. Para emitir mais, oferecemos planos com volume maior. Você precisa apenas do seu Certificado Digital A1 e da Inscrição Estadual."}
        },
        {
          "@type": "Question",
          "name": "Como funciona o backup dos dados?",
          "acceptedAnswer": {"@type": "Answer", "text": "Você pode exportar backup manualmente sempre que quiser. Para backup automático na nuvem, oferecemos a versão SaaS (online) que sincroniza tudo em tempo real."}
        }
      ]
    }
    </script>

    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #6366f1;
            --accent: #0ea5e9;
            --bg-light: #ffffff;
            --bg-gray: #f8fafc;
            --bg-gray2: #f1f5f9;
            --text-dark: #1e293b;
            --text-body: #475569;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-light);
            color: var(--text-body);
            overflow-x: hidden;
        }

        /* ===== NAVBAR ===== */
        .navbar-landing {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 1rem 0;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .navbar-landing.scrolled { padding: 0.6rem 0; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06); }
        .navbar-brand-custom { font-size: 1.5rem; font-weight: 800; color: var(--text-dark) !important; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        .navbar-brand-custom i { color: var(--primary); font-size: 1.6rem; }
        .nav-link-custom { color: var(--text-body) !important; font-weight: 500; font-size: 0.9rem; padding: 0.5rem 1rem !important; transition: color 0.3s; text-decoration: none; }
        .nav-link-custom:hover { color: var(--primary) !important; }
        .btn-nav-login { border: 1px solid var(--border); color: var(--text-body) !important; border-radius: 8px; padding: 0.45rem 1.2rem !important; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; text-decoration: none; }
        .btn-nav-login:hover { background: var(--bg-gray); border-color: var(--primary-light); color: var(--primary) !important; }
        .btn-nav-register { background: var(--primary); color: #fff !important; border: none; border-radius: 8px; padding: 0.5rem 1.3rem !important; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; text-decoration: none; }
        .btn-nav-register:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3); }

        /* ===== HERO ===== */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f4f8 50%, #f8fafc 100%);
            position: relative;
            overflow: hidden;
            padding-top: 80px;
        }
        .hero-section::before { content: ''; position: absolute; top: -30%; right: -15%; width: 600px; height: 600px; background: radial-gradient(circle, rgba(79, 70, 229, 0.06) 0%, transparent 70%); border-radius: 50%; }

        .hero-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 50px; padding: 0.4rem 1.2rem; font-size: 0.85rem;
            color: var(--success); font-weight: 700; margin-bottom: 1.5rem;
            animation: fadeInDown 0.8s ease;
        }
        .hero-title { font-size: 3.5rem; font-weight: 900; line-height: 1.1; margin-bottom: 1.5rem; color: var(--text-dark); animation: fadeInUp 0.8s ease; }
        .hero-title .gradient-text { background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-subtitle { font-size: 1.2rem; color: var(--text-body); line-height: 1.7; margin-bottom: 2.5rem; max-width: 540px; animation: fadeInUp 0.8s ease 0.2s both; }
        .hero-actions { display: flex; gap: 1rem; flex-wrap: wrap; animation: fadeInUp 0.8s ease 0.4s both; }

        .btn-hero-primary { background: var(--primary); color: #fff; border: none; border-radius: 12px; padding: 0.95rem 2rem; font-size: 1.05rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 0.6rem; transition: all 0.3s; box-shadow: 0 4px 20px rgba(79, 70, 229, 0.25); }
        .btn-hero-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(79, 70, 229, 0.35); color: #fff; background: var(--primary-dark); }
        .btn-hero-secondary { background: #fff; color: var(--text-body); border: 1px solid var(--border); border-radius: 12px; padding: 0.95rem 2rem; font-size: 1.05rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.6rem; transition: all 0.3s; }
        .btn-hero-secondary:hover { border-color: var(--primary-light); color: var(--primary); background: #fff; }

        .hero-trust { margin-top: 2rem; display: flex; flex-wrap: wrap; gap: 1.5rem; font-size: 0.85rem; color: var(--text-muted); animation: fadeInUp 0.8s ease 0.5s both; }
        .hero-trust span { display: inline-flex; align-items: center; gap: 0.4rem; }
        .hero-trust i { color: var(--success); }

        .hero-stats { display: flex; gap: 2.5rem; margin-top: 2.5rem; animation: fadeInUp 0.8s ease 0.6s both; }
        .hero-stat { text-align: center; }
        .hero-stat-number { font-size: 1.8rem; font-weight: 800; color: var(--primary); }
        .hero-stat-label { font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem; }

        .hero-image-area { position: relative; animation: fadeInRight 1s ease 0.3s both; }
        .hero-mockup { background: #fff; border-radius: 20px; border: 1px solid var(--border); padding: 2rem; box-shadow: 0 25px 60px rgba(0, 0, 0, 0.08); position: relative; }
        .mockup-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem; }
        .mockup-dot { width: 10px; height: 10px; border-radius: 50%; }
        .mockup-dot.red { background: #ef4444; }
        .mockup-dot.yellow { background: #f59e0b; }
        .mockup-dot.green { background: #10b981; }
        .mockup-content { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .mockup-card { background: var(--bg-gray); border-radius: 12px; padding: 1.2rem; border: 1px solid var(--border); }
        .mockup-card-icon { font-size: 1.4rem; margin-bottom: 0.5rem; }
        .mockup-card-value { font-size: 1.3rem; font-weight: 700; color: var(--text-dark); }
        .mockup-card-label { font-size: 0.75rem; color: var(--text-muted); }
        .mockup-card.highlight { border-color: rgba(79, 70, 229, 0.2); background: rgba(79, 70, 229, 0.04); }

        .floating-badge { position: absolute; background: #fff; border: 1px solid var(--border); border-radius: 14px; padding: 0.8rem 1.2rem; display: flex; align-items: center; gap: 0.7rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); animation: float 3s ease-in-out infinite; }
        .floating-badge.badge-1 { top: -15px; right: -20px; }
        .floating-badge.badge-2 { bottom: -15px; left: -20px; animation-delay: 1.5s; }
        .floating-badge i { font-size: 1.2rem; }
        .floating-badge .fb-text { font-size: 0.8rem; font-weight: 600; color: var(--text-dark); }
        .floating-badge .fb-sub { font-size: 0.7rem; color: var(--text-muted); }

        /* ===== SECTIONS ===== */
        section { padding: 6rem 0; }
        .section-white { background: var(--bg-light); }
        .section-gray { background: var(--bg-gray); }

        .section-label { display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(79, 70, 229, 0.08); border: 1px solid rgba(79, 70, 229, 0.12); border-radius: 50px; padding: 0.35rem 1rem; font-size: 0.8rem; color: var(--primary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 1rem; }
        .section-title { font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem; color: var(--text-dark); }
        .section-subtitle { font-size: 1.1rem; color: var(--text-body); max-width: 640px; margin: 0 auto 3rem; }

        /* ===== BENEFICIOS (3 colunas) ===== */
        .benefit-card { background: #fff; border: 1px solid var(--border); border-radius: 20px; padding: 2.5rem 2rem; text-align: center; height: 100%; transition: all 0.4s; }
        .benefit-card:hover { transform: translateY(-6px); border-color: rgba(79, 70, 229, 0.2); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06); }
        .benefit-icon-big { width: 72px; height: 72px; border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 1.2rem; background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(14, 165, 233, 0.1)); color: var(--primary); }
        .benefit-card h3 { font-size: 1.3rem; font-weight: 800; color: var(--text-dark); margin-bottom: 0.8rem; }
        .benefit-card p { font-size: 0.95rem; line-height: 1.7; color: var(--text-body); margin: 0; }

        /* ===== FEATURES ===== */
        .feature-card { background: #fff; border: 1px solid var(--border); border-radius: 20px; padding: 2rem; height: 100%; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden; }
        .feature-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--primary), var(--accent)); opacity: 0; transition: opacity 0.4s; }
        .feature-card:hover { transform: translateY(-8px); border-color: rgba(79, 70, 229, 0.2); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06); }
        .feature-card:hover::before { opacity: 1; }
        .feature-icon { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 1.2rem; background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(14, 165, 233, 0.08)); color: var(--primary); }
        .feature-card h3 { font-size: 1.15rem; font-weight: 700; margin-bottom: 0.6rem; color: var(--text-dark); }
        .feature-card p { font-size: 0.9rem; color: var(--text-body); line-height: 1.6; margin: 0; }

        /* ===== STEPS (Como começar) ===== */
        .step-card { background: #fff; border: 1px solid var(--border); border-radius: 20px; padding: 2rem; height: 100%; position: relative; }
        .step-number { position: absolute; top: -18px; left: 2rem; width: 44px; height: 44px; border-radius: 50%; background: var(--primary); color: #fff; font-size: 1.2rem; font-weight: 800; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3); }
        .step-card h3 { font-size: 1.2rem; font-weight: 700; color: var(--text-dark); margin: 1rem 0 0.7rem; }
        .step-card p { font-size: 0.95rem; color: var(--text-body); line-height: 1.7; margin: 0; }

        /* ===== COMPARISON ===== */
        .compare-table { background: #fff; border: 1px solid var(--border); border-radius: 20px; overflow: hidden; }
        .compare-table table { width: 100%; border-collapse: collapse; }
        .compare-table th, .compare-table td { padding: 1rem 1.2rem; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.95rem; }
        .compare-table th { background: var(--bg-gray); font-weight: 700; color: var(--text-dark); font-size: 1rem; }
        .compare-table th.col-free { color: var(--success); }
        .compare-table th.col-saas { color: var(--primary); }
        .compare-table td.center, .compare-table th.center { text-align: center; }
        .compare-table tr:last-child td { border-bottom: none; }
        .compare-table .fa-check { color: var(--success); }
        .compare-table .fa-xmark { color: #cbd5e1; }
        .compare-table .row-feature { font-weight: 600; color: var(--text-dark); }

        /* ===== AUDIENCE ===== */
        .audience-card { background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; transition: all 0.3s; height: 100%; }
        .audience-card:hover { border-color: rgba(79, 70, 229, 0.25); transform: translateY(-3px); }
        .audience-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(79, 70, 229, 0.08); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .audience-card .name { font-weight: 700; color: var(--text-dark); font-size: 0.95rem; margin-bottom: 0.2rem; }
        .audience-card .desc { font-size: 0.82rem; color: var(--text-muted); }

        /* ===== PRICING ===== */
        .pricing-card { background: #fff; border: 1px solid var(--border); border-radius: 24px; padding: 2.5rem 2rem; text-align: center; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative; height: 100%; display: flex; flex-direction: column; }
        .pricing-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08); }
        .pricing-card.featured { border-color: var(--primary); box-shadow: 0 0 0 1px var(--primary), 0 10px 40px rgba(79, 70, 229, 0.12); transform: scale(1.05); }
        .pricing-card.featured:hover { transform: scale(1.05) translateY(-8px); }
        .pricing-popular { position: absolute; top: -14px; left: 50%; transform: translateX(-50%); background: var(--primary); color: #fff; font-size: 0.75rem; font-weight: 700; padding: 0.35rem 1.5rem; border-radius: 50px; text-transform: uppercase; letter-spacing: 0.5px; }
        .pricing-name { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-dark); }
        .pricing-desc { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem; }
        .pricing-price { font-size: 3rem; font-weight: 900; color: var(--text-dark); line-height: 1; margin-bottom: 0.3rem; }
        .pricing-price .currency { font-size: 1.3rem; font-weight: 600; vertical-align: super; }
        .pricing-price .period { font-size: 1rem; font-weight: 400; color: var(--text-muted); }
        .pricing-from { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.5rem; }
        .pricing-features { list-style: none; padding: 0; margin: 1.5rem 0; text-align: left; flex-grow: 1; }
        .pricing-features li { padding: 0.5rem 0; font-size: 0.9rem; color: var(--text-body); display: flex; align-items: center; gap: 0.7rem; }
        .pricing-features li i { font-size: 0.8rem; width: 20px; text-align: center; color: var(--success); }
        .btn-pricing { display: block; width: 100%; padding: 0.85rem; border-radius: 12px; font-weight: 700; font-size: 0.95rem; text-decoration: none; transition: all 0.3s; margin-top: auto; }
        .btn-pricing-outline { border: 1px solid var(--border); color: var(--primary); background: transparent; }
        .btn-pricing-outline:hover { background: var(--bg-gray); border-color: var(--primary-light); color: var(--primary-dark); }
        .btn-pricing-primary { background: var(--primary); color: #fff; border: none; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.25); }
        .btn-pricing-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(79, 70, 229, 0.35); color: #fff; background: var(--primary-dark); }

        /* ===== FAQ ===== */
        .faq-item { background: #fff; border: 1px solid var(--border); border-radius: 16px; margin-bottom: 1rem; overflow: hidden; transition: all 0.3s; }
        .faq-item:hover { border-color: rgba(79, 70, 229, 0.2); }
        .faq-question { width: 100%; background: none; border: none; color: var(--text-dark); padding: 1.3rem 1.5rem; font-size: 1rem; font-weight: 600; text-align: left; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: color 0.3s; font-family: 'Inter', sans-serif; }
        .faq-question:hover { color: var(--primary); }
        .faq-question i { transition: transform 0.3s; color: var(--primary); }
        .faq-question.active i { transform: rotate(180deg); }
        .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.4s ease, padding 0.4s ease; }
        .faq-answer.open { max-height: 500px; }
        .faq-answer p { padding: 0 1.5rem 1.3rem; color: var(--text-body); line-height: 1.7; font-size: 0.95rem; margin: 0; }

        /* ===== CTA ===== */
        .cta-section { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); position: relative; overflow: hidden; }
        .cta-section::before { content: ''; position: absolute; top: -50%; right: -20%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%); border-radius: 50%; }
        .cta-title { font-size: 2.5rem; font-weight: 800; color: #fff; margin-bottom: 1rem; }
        .cta-text { font-size: 1.1rem; color: rgba(255,255,255,0.85); margin-bottom: 2rem; }
        .btn-cta { background: #fff; color: var(--primary-dark); border: none; border-radius: 12px; padding: 0.9rem 2.5rem; font-size: 1.05rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 0.6rem; transition: all 0.3s; }
        .btn-cta:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.15); color: var(--primary-dark); }

        /* ===== UPSELL SAAS (caixa enxuta) ===== */
        .upsell-saas { background: linear-gradient(135deg, #f0f4ff 0%, #e8f4f8 100%); border-radius: 24px; padding: 3rem; }
        .upsell-saas h2 { font-size: 1.8rem; font-weight: 800; color: var(--text-dark); margin-bottom: 0.5rem; }
        .upsell-saas p { color: var(--text-body); margin-bottom: 1.5rem; }

        /* ===== FOOTER ===== */
        .footer { background: var(--text-dark); padding: 3rem 0 1.5rem; }
        .footer-brand { font-size: 1.3rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.8rem; }
        .footer-brand i { color: var(--primary-light); }
        .footer-desc { color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; max-width: 300px; }
        .footer-title { font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #fff; margin-bottom: 1rem; }
        .footer-links { list-style: none; padding: 0; margin: 0; }
        .footer-links li { margin-bottom: 0.5rem; }
        .footer-links a { color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.3s; }
        .footer-links a:hover { color: var(--primary-light); }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.08); margin-top: 2rem; padding-top: 1.5rem; text-align: center; color: #64748b; font-size: 0.85rem; }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInRight { from { opacity: 0; transform: translateX(40px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        .animate-on-scroll { opacity: 0; transform: translateY(30px); transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1); }
        .animate-on-scroll.visible { opacity: 1; transform: translateY(0); }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 991px) {
            .hero-title { font-size: 2.5rem; }
            .hero-image-area { margin-top: 3rem; }
            .pricing-card.featured { transform: scale(1); }
            .pricing-card.featured:hover { transform: translateY(-8px); }
            .hero-stats { gap: 1.5rem; }
            .upsell-saas { padding: 2rem; }
        }
        @media (max-width: 767px) {
            .hero-title { font-size: 2rem; }
            .hero-subtitle { font-size: 1rem; }
            .section-title { font-size: 1.8rem; }
            .hero-stats { flex-direction: column; gap: 1rem; align-items: flex-start; }
            .cta-title { font-size: 1.8rem; }
            .hero-actions { flex-direction: column; }
            .btn-hero-primary, .btn-hero-secondary { width: 100%; justify-content: center; }
            .compare-table { font-size: 0.8rem; }
            .compare-table th, .compare-table td { padding: 0.7rem 0.5rem; }
        }

        .navbar-toggler-custom { border: 1px solid var(--border); padding: 0.4rem 0.7rem; border-radius: 8px; background: none; color: var(--text-dark); font-size: 1.2rem; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar-landing">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <a href="/" class="navbar-brand-custom">
                <i class="fas fa-bolt"></i>
                Balcão PDV
            </a>

            <button class="navbar-toggler-custom d-lg-none" onclick="document.getElementById('navMenu').classList.toggle('show')" aria-label="Menu">
                <i class="fas fa-bars"></i>
            </button>

            <div class="d-none d-lg-flex align-items-center gap-3">
                <a href="#funcionalidades" class="nav-link-custom">Funcionalidades</a>
                <a href="#como-comecar" class="nav-link-custom">Como Começar</a>
                <a href="#online" class="nav-link-custom">Versão Online</a>
                <a href="/artigos/" class="nav-link-custom">Artigos</a>
                <a href="#faq" class="nav-link-custom">FAQ</a>
                <a href="/auth/login.php" class="btn-nav-login">Entrar</a>
                <a href="/download" class="btn-nav-register"><i class="fas fa-download me-1"></i>Baixar Grátis</a>
            </div>
        </div>

        <div id="navMenu" class="d-lg-none mt-3" style="display:none;">
            <div class="d-flex flex-column gap-2 pb-3">
                <a href="#funcionalidades" class="nav-link-custom" onclick="document.getElementById('navMenu').style.display='none'">Funcionalidades</a>
                <a href="#como-comecar" class="nav-link-custom" onclick="document.getElementById('navMenu').style.display='none'">Como Começar</a>
                <a href="#online" class="nav-link-custom" onclick="document.getElementById('navMenu').style.display='none'">Versão Online</a>
                <a href="/artigos/" class="nav-link-custom" onclick="document.getElementById('navMenu').style.display='none'">Artigos</a>
                <a href="#faq" class="nav-link-custom" onclick="document.getElementById('navMenu').style.display='none'">FAQ</a>
                <div class="d-flex gap-2 mt-2">
                    <a href="/auth/login.php" class="btn-nav-login flex-fill text-center">Entrar</a>
                    <a href="/download" class="btn-nav-register flex-fill text-center">Baixar Grátis</a>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Hero -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="hero-badge">
                    <i class="fas fa-gift"></i>
                    100% Grátis - Sem Mensalidade
                </div>
                <h1 class="hero-title">
                    PDV Grátis<br>
                    <span class="gradient-text">para sempre</span>
                </h1>
                <p class="hero-subtitle">
                    Baixe agora o <strong>Balcão PDV Desktop</strong> e tenha um sistema de caixa completo no seu computador. Controle vendas, estoque, clientes e emita NFC-e. Sem mensalidade, sem cartão de crédito, sem pegadinha.
                </p>
                <div class="hero-actions">
                    <a href="/download" class="btn-hero-primary">
                        <i class="fas fa-download"></i> Baixar Grátis Agora
                    </a>
                    <a href="#funcionalidades" class="btn-hero-secondary">
                        <i class="fas fa-list-check"></i> Ver Funcionalidades
                    </a>
                </div>
                <div class="hero-trust">
                    <span><i class="fas fa-check-circle"></i> Sem cartão de crédito</span>
                    <span><i class="fas fa-check-circle"></i> Sem cadastro</span>
                    <span><i class="fas fa-check-circle"></i> Funciona offline</span>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-number">R$ 0</div>
                        <div class="hero-stat-label">Mensalidade</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-number">∞</div>
                        <div class="hero-stat-label">Vendas/mês</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-number"><?= $limiteNfceFreeStat ?></div>
                        <div class="hero-stat-label">NFC-e/mês</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image-area">
                    <div class="hero-mockup" role="img" aria-label="Painel do Balcão PDV mostrando vendas, produtos e clientes">
                        <div class="mockup-header">
                            <div class="mockup-dot red"></div>
                            <div class="mockup-dot yellow"></div>
                            <div class="mockup-dot green"></div>
                        </div>
                        <div class="mockup-content">
                            <div class="mockup-card highlight">
                                <div class="mockup-card-icon">💰</div>
                                <div class="mockup-card-value">R$ 12.450</div>
                                <div class="mockup-card-label">Vendas hoje</div>
                            </div>
                            <div class="mockup-card">
                                <div class="mockup-card-icon">📦</div>
                                <div class="mockup-card-value">1.234</div>
                                <div class="mockup-card-label">Produtos</div>
                            </div>
                            <div class="mockup-card">
                                <div class="mockup-card-icon">👥</div>
                                <div class="mockup-card-value">856</div>
                                <div class="mockup-card-label">Clientes</div>
                            </div>
                            <div class="mockup-card highlight">
                                <div class="mockup-card-icon">📊</div>
                                <div class="mockup-card-value">+18%</div>
                                <div class="mockup-card-label">Crescimento</div>
                            </div>
                        </div>
                        <div class="floating-badge badge-1">
                            <i class="fas fa-check-circle" style="color: var(--success);"></i>
                            <div>
                                <div class="fb-text">Venda finalizada</div>
                                <div class="fb-sub">Agora mesmo</div>
                            </div>
                        </div>
                        <div class="floating-badge badge-2">
                            <i class="fas fa-gift" style="color: var(--success);"></i>
                            <div>
                                <div class="fb-text">100% Grátis</div>
                                <div class="fb-sub">Para sempre</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Por que escolher o Balcão PDV -->
<section class="section-white">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label animate-on-scroll"><i class="fas fa-award"></i> Por que escolher</div>
            <h2 class="section-title animate-on-scroll">O único PDV completo que é grátis para sempre</h2>
            <p class="section-subtitle animate-on-scroll">Enquanto a maioria dos sistemas cobra mensalidade ou limita o uso, o Balcão PDV é diferente: você baixa, instala e usa o quanto quiser, sem pagar nada.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="benefit-card animate-on-scroll">
                    <div class="benefit-icon-big"><i class="fas fa-gift"></i></div>
                    <h3>Grátis para Sempre</h3>
                    <p>Sem mensalidade, sem taxa oculta, sem período de teste que vence. Você baixa o sistema e usa pelo tempo que quiser, gratuitamente.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-card animate-on-scroll">
                    <div class="benefit-icon-big"><i class="fas fa-rocket"></i></div>
                    <h3>Completo de Verdade</h3>
                    <p>Não é versão limitada. PDV, estoque, clientes, relatórios, NFC-e, múltiplas formas de pagamento e muito mais — tudo incluído no plano grátis.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-card animate-on-scroll">
                    <div class="benefit-icon-big"><i class="fas fa-wifi"></i></div>
                    <h3>Funciona Offline</h3>
                    <p>Internet caiu? Sem problema. O Balcão PDV Desktop continua funcionando normalmente. Você só precisa de internet para emitir NFC-e.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Funcionalidades -->
<section id="funcionalidades" class="section-gray">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label animate-on-scroll"><i class="fas fa-star"></i> Funcionalidades</div>
            <h2 class="section-title animate-on-scroll">Tudo que um PDV precisa ter — e que normalmente custa caro</h2>
            <p class="section-subtitle animate-on-scroll">Funcionalidades profissionais que outros sistemas cobram caro, no Balcão PDV vêm incluídas no plano grátis.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <article class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-cash-register"></i></div>
                    <h3>PDV com Atalhos de Teclado</h3>
                    <p>Tela de venda otimizada para velocidade. Lance produtos, aplique descontos e finalize vendas em segundos usando apenas o teclado.</p>
                </article>
            </div>
            <div class="col-md-6 col-lg-4">
                <article class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-boxes-stacked"></i></div>
                    <h3>Controle de Estoque</h3>
                    <p>Estoque atualizado automaticamente a cada venda. Cadastro ilimitado de produtos com código de barras, foto, preço de custo e venda.</p>
                </article>
            </div>
            <div class="col-md-6 col-lg-4">
                <article class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-file-invoice"></i></div>
                    <h3>Emissão de NFC-e</h3>
                    <p>Emita Nota Fiscal de Consumidor Eletrônica direto do PDV. Até <?= $limiteNfceFreeTexto ?> NFC-e por mês no plano grátis. Basta ter Certificado Digital A1.</p>
                </article>
            </div>
            <div class="col-md-6 col-lg-4">
                <article class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-users"></i></div>
                    <h3>Cadastro de Clientes</h3>
                    <p>Histórico completo de compras por cliente. Identifique seus melhores compradores e crie ações de fidelização.</p>
                </article>
            </div>
            <div class="col-md-6 col-lg-4">
                <article class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-credit-card"></i></div>
                    <h3>Múltiplas Formas de Pagamento</h3>
                    <p>Dinheiro, débito, crédito, PIX, vale-refeição e mais. Aceite pagamento dividido em várias formas na mesma venda.</p>
                </article>
            </div>
            <div class="col-md-6 col-lg-4">
                <article class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-chart-bar"></i></div>
                    <h3>Relatórios de Vendas</h3>
                    <p>Vendas por dia, mês, produto, vendedor e forma de pagamento. Saiba o que mais vende e o que dá mais lucro.</p>
                </article>
            </div>
            <div class="col-md-6 col-lg-4">
                <article class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-tags"></i></div>
                    <h3>Descontos e Promoções</h3>
                    <p>Aplique descontos em valor ou percentual, por item ou na venda toda. Configure preços promocionais por produto.</p>
                </article>
            </div>
            <div class="col-md-6 col-lg-4">
                <article class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-print"></i></div>
                    <h3>Impressão de Cupom</h3>
                    <p>Imprima cupom não-fiscal ou NFC-e em impressora térmica de 58mm ou 80mm. Compatível com Bematech, Epson, Daruma e outras.</p>
                </article>
            </div>
            <div class="col-md-6 col-lg-4">
                <article class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-user-shield"></i></div>
                    <h3>Multi-usuário</h3>
                    <p>Cadastre administrador, gerente e operadores de caixa. Cada um com permissões específicas e log de ações.</p>
                </article>
            </div>
        </div>
    </div>
</section>

<!-- Como começar (3 passos) -->
<section id="como-comecar" class="section-white">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label animate-on-scroll"><i class="fas fa-flag-checkered"></i> Como Começar</div>
            <h2 class="section-title animate-on-scroll">Comece a vender em 3 minutos</h2>
            <p class="section-subtitle animate-on-scroll">Sem cadastro complicado, sem cartão de crédito, sem perder tempo. É só baixar e usar.</p>
        </div>
        <div class="row g-5 mt-2">
            <div class="col-md-4">
                <div class="step-card animate-on-scroll">
                    <div class="step-number">1</div>
                    <h3>Baixe o Sistema</h3>
                    <p>Clique no botão "Baixar Grátis" e baixe o instalador. O download é direto, sem precisar criar conta nem informar dados.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card animate-on-scroll">
                    <div class="step-number">2</div>
                    <h3>Instale em Minutos</h3>
                    <p>Execute o instalador e siga o passo a passo. O Balcão PDV ocupa pouco espaço e funciona em qualquer Windows 10 ou 11.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card animate-on-scroll">
                    <div class="step-number">3</div>
                    <h3>Cadastre e Venda</h3>
                    <p>Cadastre seus produtos (importação por planilha disponível) e comece a registrar vendas no PDV. Pronto, seu caixa está funcionando.</p>
                </div>
            </div>
        </div>
        <div class="text-center mt-5">
            <a href="/download" class="btn-hero-primary"><i class="fas fa-download"></i> Baixar Balcão PDV Grátis</a>
        </div>
    </div>
</section>

<!-- Para quem é -->
<section class="section-gray">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label animate-on-scroll"><i class="fas fa-store"></i> Para Quem É</div>
            <h2 class="section-title animate-on-scroll">Ideal para qualquer negócio que vende</h2>
            <p class="section-subtitle animate-on-scroll">O Balcão PDV é usado por pequenos e médios estabelecimentos comerciais em todo o Brasil.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6 col-lg-4"><div class="audience-card animate-on-scroll"><div class="audience-icon"><i class="fas fa-shopping-basket"></i></div><div><div class="name">Mercearia / Mercadinho</div><div class="desc">Controle de produtos perecíveis e estoque</div></div></div></div>
            <div class="col-md-6 col-lg-4"><div class="audience-card animate-on-scroll"><div class="audience-icon"><i class="fas fa-utensils"></i></div><div><div class="name">Lanchonete / Restaurante</div><div class="desc">Pedidos rápidos e múltiplas formas de pagamento</div></div></div></div>
            <div class="col-md-6 col-lg-4"><div class="audience-card animate-on-scroll"><div class="audience-icon"><i class="fas fa-tshirt"></i></div><div><div class="name">Loja de Roupas</div><div class="desc">Cadastro com tamanho, cor e variações</div></div></div></div>
            <div class="col-md-6 col-lg-4"><div class="audience-card animate-on-scroll"><div class="audience-icon"><i class="fas fa-pen"></i></div><div><div class="name">Papelaria</div><div class="desc">Grande volume de produtos com baixo ticket</div></div></div></div>
            <div class="col-md-6 col-lg-4"><div class="audience-card animate-on-scroll"><div class="audience-icon"><i class="fas fa-pills"></i></div><div><div class="name">Farmácia / Drogaria</div><div class="desc">Controle de validade e produtos controlados</div></div></div></div>
            <div class="col-md-6 col-lg-4"><div class="audience-card animate-on-scroll"><div class="audience-icon"><i class="fas fa-store-alt"></i></div><div><div class="name">Conveniência</div><div class="desc">Vendas rápidas com leitor de código de barras</div></div></div></div>
            <div class="col-md-6 col-lg-4"><div class="audience-card animate-on-scroll"><div class="audience-icon"><i class="fas fa-cookie-bite"></i></div><div><div class="name">Padaria / Confeitaria</div><div class="desc">Produção própria e venda direta ao consumidor</div></div></div></div>
            <div class="col-md-6 col-lg-4"><div class="audience-card animate-on-scroll"><div class="audience-icon"><i class="fas fa-paw"></i></div><div><div class="name">Pet Shop</div><div class="desc">Produtos, ração e serviços no mesmo PDV</div></div></div></div>
            <div class="col-md-6 col-lg-4"><div class="audience-card animate-on-scroll"><div class="audience-icon"><i class="fas fa-mobile-screen-button"></i></div><div><div class="name">Assistência Técnica</div><div class="desc">Vendas avulsas e ordens de serviço</div></div></div></div>
        </div>
    </div>
</section>

<!-- Comparativo Desktop x Online -->
<section id="online" class="section-white">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label animate-on-scroll"><i class="fas fa-scale-balanced"></i> Compare</div>
            <h2 class="section-title animate-on-scroll">Desktop Grátis ou Online (SaaS)?</h2>
            <p class="section-subtitle animate-on-scroll">A versão Desktop é grátis e roda no seu computador. A versão Online (SaaS) é paga e funciona em qualquer dispositivo, com sincronização automática na nuvem.</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="compare-table animate-on-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Recurso</th>
                                <th class="col-free center">Desktop (Grátis)</th>
                                <th class="col-saas center">Online (SaaS)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td class="row-feature">Preço</td><td class="center">R$ 0,00</td><td class="center">A partir de R$ 99,90/mês</td></tr>
                            <tr><td class="row-feature">PDV completo</td><td class="center"><i class="fas fa-check"></i></td><td class="center"><i class="fas fa-check"></i></td></tr>
                            <tr><td class="row-feature">Controle de estoque</td><td class="center"><i class="fas fa-check"></i></td><td class="center"><i class="fas fa-check"></i></td></tr>
                            <tr><td class="row-feature">Emissão de NFC-e</td><td class="center"><?= $limiteNfceFree > 0 ? 'Até ' . $limiteNfceFree . '/mês' : 'Ilimitado' ?></td><td class="center">Ilimitado</td></tr>
                            <tr><td class="row-feature">Funciona offline</td><td class="center"><i class="fas fa-check"></i></td><td class="center"><i class="fas fa-xmark"></i></td></tr>
                            <tr><td class="row-feature">Acesso de qualquer lugar</td><td class="center"><i class="fas fa-xmark"></i></td><td class="center"><i class="fas fa-check"></i></td></tr>
                            <tr><td class="row-feature">Sincroniza entre dispositivos</td><td class="center"><i class="fas fa-xmark"></i></td><td class="center"><i class="fas fa-check"></i></td></tr>
                            <tr><td class="row-feature">Backup automático na nuvem</td><td class="center"><i class="fas fa-xmark"></i></td><td class="center"><i class="fas fa-check"></i></td></tr>
                            <tr><td class="row-feature">Multi-usuário simultâneo</td><td class="center">Mesmo computador</td><td class="center">Múltiplos dispositivos</td></tr>
                            <tr><td class="row-feature">Atualização automática</td><td class="center">Manual</td><td class="center"><i class="fas fa-check"></i></td></tr>
                            <tr><td class="row-feature">Sistema operacional</td><td class="center">Windows</td><td class="center">Qualquer (web)</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-4">
                    <a href="/download" class="btn-hero-primary me-2 mb-2"><i class="fas fa-download"></i> Baixar Desktop Grátis</a>
                    <a href="#planos-saas" class="btn-hero-secondary mb-2"><i class="fas fa-cloud"></i> Ver Planos Online</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Upsell SaaS (planos) -->
<section id="planos-saas" class="section-gray">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label animate-on-scroll"><i class="fas fa-cloud"></i> Versão Online (SaaS)</div>
            <h2 class="section-title animate-on-scroll">Quer acessar de qualquer lugar?</h2>
            <p class="section-subtitle animate-on-scroll">Se você precisa de backup automático na nuvem, acesso pelo celular ou múltiplos pontos de venda sincronizados, conheça nossa versão online. <strong>15 dias grátis</strong> para testar.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <?php foreach ($planos as $i => $plano):
                $recursos = is_array($plano['recursos']) ? $plano['recursos'] : (json_decode($plano['recursos'] ?? '{}', true) ?: []);
                $destaque = !empty($recursos['destaque']);
                $descricao = $recursos['descricao'] ?? '';
                $beneficios = $recursos['beneficios'] ?? [];
                $preco = (float)$plano['preco'];
                $precoInteiro = floor($preco);
                $precoCentavos = round(($preco - $precoInteiro) * 100);
                $slugParam = slugCurto($plano['slug']);
            ?>
            <div class="col-md-6 col-lg-4 animate-on-scroll">
                <div class="pricing-card<?= $destaque ? ' featured' : '' ?>">
                    <?php if ($destaque): ?><div class="pricing-popular">Mais Popular</div><?php endif; ?>
                    <div class="pricing-name"><?= htmlspecialchars($plano['nome']) ?></div>
                    <?php if ($descricao): ?><div class="pricing-desc"><?= htmlspecialchars($descricao) ?></div><?php endif; ?>
                    <?php if ($preco > 0): ?>
                        <div class="pricing-price"><span class="currency">R$</span> <?= $precoInteiro ?><?php if ($precoCentavos > 0): ?><span style="font-size:1.5rem">,<?= str_pad($precoCentavos, 2, '0') ?></span><?php endif; ?> <span class="period">/mês</span></div>
                    <?php else: ?>
                        <div class="pricing-price"><span class="currency">R$</span> 0 <span class="period">/mês</span></div>
                    <?php endif; ?>
                    <div class="pricing-from">15 dias grátis para testar</div>
                    <?php if (!empty($beneficios)): ?>
                    <ul class="pricing-features">
                        <?php foreach ($beneficios as $b): ?>
                            <li><i class="fas fa-check"></i> <?= htmlspecialchars($b) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <a href="/auth/register.php?plano=<?= htmlspecialchars($slugParam) ?>" class="btn-pricing <?= $destaque ? 'btn-pricing-primary' : 'btn-pricing-outline' ?>">Testar 15 Dias Grátis</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <p class="text-muted small mb-0"><i class="fas fa-info-circle me-1"></i> Não precisa do online? <a href="/download" style="color: var(--primary); font-weight: 600;">Continue com a versão Desktop grátis</a>.</p>
        </div>
    </div>
</section>

<!-- FAQ -->
<section id="faq" class="section-white">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label animate-on-scroll"><i class="fas fa-circle-question"></i> Dúvidas Frequentes</div>
            <h2 class="section-title animate-on-scroll">Perguntas Frequentes sobre o PDV Grátis</h2>
            <p class="section-subtitle animate-on-scroll">Respostas para as dúvidas mais comuns. Não achou o que procurava? Entre em contato com nosso suporte.</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">O Balcão PDV é realmente grátis? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Sim. O Balcão PDV Desktop é gratuito para sempre, sem mensalidade e sem taxa oculta. Você pode baixar, instalar e usar todas as funcionalidades essenciais sem pagar nada. A versão Online (SaaS) é paga porque inclui hospedagem na nuvem, backup automático e sincronização entre dispositivos.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">O PDV grátis tem limite de vendas? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Não. Você pode registrar quantas vendas quiser no plano grátis, sem qualquer limitação. O único limite é a quantidade de NFC-e (Nota Fiscal de Consumidor Eletrônica) emitidas por mês, que é de até <?= $limiteNfceFreeTexto ?> notas. Para emitir mais NFC-e, oferecemos planos pagos com volume maior.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Funciona sem internet? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Sim. A versão desktop funciona 100% offline. Os dados ficam salvos no seu computador e você só precisa de internet para emitir NFC-e (exigência da SEFAZ) ou se quiser fazer backup na nuvem. Vendas, estoque, clientes e relatórios funcionam normalmente sem conexão.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Em qual sistema operacional funciona? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>O Balcão PDV Desktop funciona em Windows 10 e Windows 11. Em breve disponibilizaremos versões para Linux e MacOS. Se você prefere usar pelo navegador (sem instalar nada), conheça nossa versão Online que funciona em qualquer dispositivo com internet — Windows, Mac, Linux, Android, iOS.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Posso usar o sistema no meu negócio comercialmente? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Sim, sem restrição. O Balcão PDV pode ser usado em qualquer estabelecimento comercial: lojas, mercearias, lanchonetes, papelarias, conveniências, farmácias, restaurantes, pet shops, padarias e qualquer outro negócio que precise de um sistema de caixa. Não há limite de tamanho de empresa nem cobrança por uso comercial.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Preciso pagar para emitir NFC-e? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>No plano grátis você pode emitir até <?= $limiteNfceFreeTexto ?> NFC-e por mês sem pagar nada. Para emitir um volume maior, oferecemos planos pagos. Você precisa apenas do seu Certificado Digital A1 e da Inscrição Estadual da empresa. O sistema integra direto com a SEFAZ — não há custo adicional por nota emitida.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Como funciona o backup dos dados? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Na versão Desktop você pode exportar backup manualmente sempre que quiser, em formato compatível com restauração. Para backup automático na nuvem (recomendado), oferecemos a versão Online (SaaS) que sincroniza tudo em tempo real e protege seus dados contra perda do computador.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Posso instalar em mais de um computador? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>A versão Desktop é instalada em um computador por licença. Cada instalação tem seus próprios dados (não sincronizam entre si). Se você precisa rodar o sistema em múltiplos computadores com dados sincronizados (ex: matriz e filial), recomendamos a versão Online (SaaS) que faz isso nativamente.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Tem suporte técnico no plano grátis? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Sim. Oferecemos suporte por email e por nossa central de ajuda no plano grátis. Para suporte prioritário com atendimento em até 4 horas úteis, oferecemos os planos Online com SLA dedicado.</p></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Final -->
<section class="cta-section">
    <div class="container text-center">
        <h2 class="cta-title animate-on-scroll">Comece a usar o PDV grátis agora</h2>
        <p class="cta-text animate-on-scroll">Sem cartão de crédito. Sem cadastro. Sem mensalidade.<br>Baixe, instale e comece a vender hoje mesmo.</p>
        <a href="/download" class="btn-cta animate-on-scroll"><i class="fas fa-download"></i> Baixar Balcão PDV Grátis</a>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="footer-brand"><i class="fas fa-bolt"></i> Balcão PDV</div>
                <p class="footer-desc">Sistema de PDV gratuito para sempre. Controle vendas, estoque, clientes e emita NFC-e sem pagar mensalidade.</p>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title">Produto</div>
                <ul class="footer-links">
                    <li><a href="#funcionalidades">Funcionalidades</a></li>
                    <li><a href="#como-comecar">Como Começar</a></li>
                    <li><a href="#online">Desktop x Online</a></li>
                    <li><a href="/download">Baixar Grátis</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title">Recursos</div>
                <ul class="footer-links">
                    <li><a href="/emissor-nfce-gratis/">Emissor NFC-e Grátis</a></li>
                    <li><a href="#planos-saas">Planos Online</a></li>
                    <li><a href="#faq">Perguntas Frequentes</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title">Acesso</div>
                <ul class="footer-links">
                    <li><a href="/auth/login.php">Entrar (Online)</a></li>
                    <li><a href="/auth/register.php">Criar Conta</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title">Legal</div>
                <ul class="footer-links">
                    <li><a href="/termos.php">Termos de Uso</a></li>
                    <li><a href="/termos.php#8-política-de-privacidade-e-proteção-de-dados-lgpd">Privacidade</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">&copy; <?= date('Y') ?> Balcão PDV. Todos os direitos reservados.</div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar-landing');
    navbar.classList.toggle('scrolled', window.scrollY > 50);
});

const navMenu = document.getElementById('navMenu');
document.querySelector('.navbar-toggler-custom').addEventListener('click', function() {
    navMenu.style.display = navMenu.style.display === 'none' ? 'block' : 'none';
});

function toggleFaq(btn) {
    const answer = btn.nextElementSibling;
    const isOpen = answer.classList.contains('open');
    document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('open'));
    document.querySelectorAll('.faq-question').forEach(q => q.classList.remove('active'));
    if (!isOpen) { answer.classList.add('open'); btn.classList.add('active'); }
}

const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
            setTimeout(() => { entry.target.classList.add('visible'); }, index * 80);
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

document.querySelectorAll('.animate-on-scroll').forEach(el => observer.observe(el));
</script>
</body>
</html>
