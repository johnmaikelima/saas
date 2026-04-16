<?php
// Landing page SEO - Emissor NFC-e Grátis (Desktop)
require_once __DIR__ . '/../../app/config.php';

// Buscar planos desktop do Painel (com cache)
$planos = [];
$cacheFile = STORAGE_PATH . '/cache_planos_desktop.json';
$cacheValido = file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 600;

if ($cacheValido) {
    $planos = json_decode(file_get_contents($cacheFile), true) ?: [];
} elseif (!empty(PAINEL_API_URL)) {
    try {
        $ch = curl_init(PAINEL_API_URL . '?action=planos&tipo=desktop&incluir_free=1');
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
                $planos = $data['planos'];
                @file_put_contents($cacheFile, json_encode($planos));
            }
        }
    } catch (\Throwable $e) {}
}

// Fallback
if (empty($planos)) {
    $planos = [
        ['nome' => 'Free', 'slug' => 'desktop-free', 'preco' => 0, 'preco_mensal' => null, 'preco_anual' => null, 'limite_nfce' => 30, 'recursos' => ['descricao' => 'Para conhecer o sistema', 'destaque' => true, 'beneficios' => ['Até 30 NFC-e/mês','1 usuário','PDV completo','Controle de estoque','Sem compromisso']]],
        ['nome' => 'Pro', 'slug' => 'desktop-pro', 'preco' => 39.90, 'preco_mensal' => 39.90, 'preco_anual' => 29.90, 'limite_nfce' => 0, 'recursos' => ['descricao' => 'Para quem precisa de mais', 'beneficios' => ['NFC-e ilimitado','1 usuário','PDV completo','Controle de estoque','Suporte por WhatsApp']]],
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Emissor NFC-e grátis para seu negócio. Baixe o Kaixa e emita até 30 notas fiscais por mês sem pagar nada. Sistema completo com PDV, estoque e relatórios.">
    <meta name="keywords" content="emissor nfce gratis, emissor nfc-e gratis, nfce gratis, nota fiscal consumidor gratis, pdv gratis, sistema pdv gratis">
    <title>Emissor NFC-e Grátis - Kaixa | Sistema de Vendas Completo</title>
    <link rel="canonical" href="<?= APP_URL ?>/emissor-nfce-gratis/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "Kaixa - Emissor NFC-e",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Windows",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "BRL",
            "description": "Emissor NFC-e grátis com até 30 notas por mês"
        },
        "description": "Sistema emissor de NFC-e grátis com PDV completo, controle de estoque e relatórios."
    }
    </script>

    <style>
        :root {
            --primary: #059669;
            --primary-dark: #047857;
            --primary-light: #10b981;
            --accent: #0ea5e9;
            --bg-light: #ffffff;
            --bg-gray: #f8fafc;
            --text-dark: #1e293b;
            --text-body: #475569;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --success: #10b981;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-light); color: var(--text-body); overflow-x: hidden; }

        /* Navbar */
        .navbar-landing {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 1rem 0;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
        }
        .navbar-brand-custom {
            font-size: 1.5rem; font-weight: 800; color: var(--text-dark) !important;
            text-decoration: none; display: flex; align-items: center; gap: 0.5rem;
        }
        .navbar-brand-custom i { color: var(--primary); font-size: 1.6rem; }
        .nav-link-custom { color: var(--text-body) !important; font-weight: 500; font-size: 0.9rem; padding: 0.5rem 1rem !important; text-decoration: none; }
        .nav-link-custom:hover { color: var(--primary) !important; }

        /* Hero */
        .hero-section {
            min-height: 100vh;
            display: flex; align-items: center;
            background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 50%, #f8fafc 100%);
            padding-top: 80px;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute; top: -30%; right: -15%;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(5,150,105,0.06) 0%, transparent 70%);
            border-radius: 50%;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(5,150,105,0.08); border: 1px solid rgba(5,150,105,0.15);
            border-radius: 50px; padding: 0.4rem 1.2rem;
            font-size: 0.85rem; color: var(--primary); font-weight: 600; margin-bottom: 1.5rem;
        }
        .hero-title {
            font-size: 3.2rem; font-weight: 900; line-height: 1.1;
            margin-bottom: 1.5rem; color: var(--text-dark);
        }
        .hero-title .gradient-text {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .hero-subtitle {
            font-size: 1.15rem; color: var(--text-body); line-height: 1.7;
            margin-bottom: 2rem; max-width: 520px;
        }
        .hero-actions { display: flex; gap: 1rem; flex-wrap: wrap; }
        .btn-hero-primary {
            background: var(--primary); color: #fff; border: none; border-radius: 12px;
            padding: 0.9rem 2rem; font-size: 1.05rem; font-weight: 700;
            text-decoration: none; display: inline-flex; align-items: center; gap: 0.6rem;
            transition: all 0.3s; box-shadow: 0 4px 20px rgba(5,150,105,0.25);
        }
        .btn-hero-primary:hover {
            transform: translateY(-3px); box-shadow: 0 8px 30px rgba(5,150,105,0.35);
            color: #fff; background: var(--primary-dark);
        }
        .btn-hero-secondary {
            background: #fff; color: var(--text-body); border: 1px solid var(--border);
            border-radius: 12px; padding: 0.9rem 2rem; font-size: 1.05rem; font-weight: 600;
            text-decoration: none; display: inline-flex; align-items: center; gap: 0.6rem; transition: all 0.3s;
        }
        .btn-hero-secondary:hover { border-color: var(--primary-light); color: var(--primary); }

        .free-highlight {
            background: #ecfdf5; border: 2px solid var(--primary-light);
            border-radius: 16px; padding: 1.5rem 2rem; margin-top: 2rem;
        }
        .free-highlight h5 { color: var(--primary); font-weight: 800; margin-bottom: 0.5rem; }
        .free-highlight .items { display: flex; flex-wrap: wrap; gap: 1rem; }
        .free-highlight .item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; color: var(--text-dark); font-weight: 500; }
        .free-highlight .item i { color: var(--primary); }

        /* Sections */
        section { padding: 5rem 0; }
        .section-white { background: var(--bg-light); }
        .section-gray { background: var(--bg-gray); }
        .section-label {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(5,150,105,0.08); border: 1px solid rgba(5,150,105,0.12);
            border-radius: 50px; padding: 0.35rem 1rem; font-size: 0.8rem;
            color: var(--primary); font-weight: 600; text-transform: uppercase; margin-bottom: 1rem;
        }
        .section-title { font-size: 2.3rem; font-weight: 800; margin-bottom: 1rem; color: var(--text-dark); }
        .section-subtitle { font-size: 1.05rem; color: var(--text-body); max-width: 600px; margin: 0 auto 3rem; }

        /* Steps */
        .step-card {
            text-align: center; padding: 2rem;
        }
        .step-number {
            width: 60px; height: 60px; border-radius: 50%;
            background: var(--primary); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; font-weight: 800; margin: 0 auto 1rem;
        }
        .step-card h5 { font-weight: 700; color: var(--text-dark); margin-bottom: 0.5rem; }
        .step-card p { font-size: 0.9rem; color: var(--text-body); }

        /* Features */
        .feature-card {
            background: #fff; border: 1px solid var(--border); border-radius: 20px;
            padding: 2rem; height: 100%; transition: all 0.4s;
        }
        .feature-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.06); }
        .feature-icon {
            width: 56px; height: 56px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; margin-bottom: 1rem;
            background: linear-gradient(135deg, rgba(5,150,105,0.1), rgba(14,165,233,0.08));
            color: var(--primary);
        }
        .feature-card h5 { font-size: 1.1rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.5rem; }
        .feature-card p { font-size: 0.88rem; color: var(--text-body); margin: 0; }

        /* Pricing */
        .pricing-card {
            background: #fff; border: 1px solid var(--border); border-radius: 24px;
            padding: 2.5rem 2rem; text-align: center; transition: all 0.4s;
            position: relative; height: 100%; display: flex; flex-direction: column;
        }
        .pricing-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); }
        .pricing-card.featured {
            border-color: var(--primary);
            box-shadow: 0 0 0 1px var(--primary), 0 10px 40px rgba(5,150,105,0.12);
            transform: scale(1.05);
        }
        .pricing-card.featured:hover { transform: scale(1.05) translateY(-8px); }
        .pricing-popular {
            position: absolute; top: -14px; left: 50%; transform: translateX(-50%);
            background: var(--primary); color: #fff; font-size: 0.75rem;
            font-weight: 700; padding: 0.35rem 1.5rem; border-radius: 50px; text-transform: uppercase;
        }
        .pricing-name { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-dark); }
        .pricing-desc { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem; }
        .pricing-price { font-size: 3rem; font-weight: 900; color: var(--text-dark); line-height: 1; margin-bottom: 0.3rem; }
        .pricing-price .currency { font-size: 1.3rem; font-weight: 600; vertical-align: super; }
        .pricing-price .period { font-size: 1rem; font-weight: 400; color: var(--text-muted); }
        .pricing-from { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.5rem; }
        .pricing-features { list-style: none; padding: 0; margin: 1.5rem 0; text-align: left; flex-grow: 1; }
        .pricing-features li { padding: 0.5rem 0; font-size: 0.9rem; color: var(--text-body); display: flex; align-items: center; gap: 0.7rem; }
        .pricing-features li i.fa-check { color: var(--success); }

        .pricing-periods { margin-bottom: 1rem; }
        .pricing-periods small { display: block; color: var(--text-muted); font-size: 0.8rem; }
        .pricing-periods .period-option {
            display: inline-block; background: var(--bg-gray); border-radius: 8px;
            padding: 0.3rem 0.8rem; margin: 0.2rem; font-size: 0.8rem; color: var(--text-body);
        }
        .pricing-periods .period-option.best {
            background: rgba(5,150,105,0.1); color: var(--primary); font-weight: 600;
        }

        .btn-pricing {
            display: block; width: 100%; padding: 0.85rem; border-radius: 12px;
            font-weight: 700; font-size: 0.95rem; text-decoration: none; transition: all 0.3s; margin-top: auto;
        }
        .btn-pricing-primary {
            background: var(--primary); color: #fff; border: none;
            box-shadow: 0 4px 15px rgba(5,150,105,0.25);
        }
        .btn-pricing-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(5,150,105,0.35); color: #fff; }
        .btn-pricing-outline { border: 1px solid var(--border); color: var(--primary); background: transparent; }
        .btn-pricing-outline:hover { background: var(--bg-gray); border-color: var(--primary-light); }

        /* FAQ */
        .faq-item { background: #fff; border: 1px solid var(--border); border-radius: 16px; margin-bottom: 1rem; overflow: hidden; }
        .faq-question {
            width: 100%; background: none; border: none; color: var(--text-dark);
            padding: 1.3rem 1.5rem; font-size: 1rem; font-weight: 600;
            text-align: left; cursor: pointer; display: flex; justify-content: space-between;
            align-items: center; font-family: 'Inter', sans-serif;
        }
        .faq-question:hover { color: var(--primary); }
        .faq-question i { transition: transform 0.3s; color: var(--primary); }
        .faq-question.active i { transform: rotate(180deg); }
        .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.4s ease; }
        .faq-answer.open { max-height: 300px; }
        .faq-answer p { padding: 0 1.5rem 1.3rem; color: var(--text-body); line-height: 1.7; font-size: 0.95rem; margin: 0; }

        /* CTA */
        .cta-section { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); position: relative; overflow: hidden; }
        .cta-title { font-size: 2.3rem; font-weight: 800; color: #fff; margin-bottom: 1rem; }
        .cta-text { font-size: 1.05rem; color: rgba(255,255,255,0.85); margin-bottom: 2rem; }
        .btn-cta {
            background: #fff; color: var(--primary-dark); border: none; border-radius: 12px;
            padding: 0.9rem 2.5rem; font-size: 1.05rem; font-weight: 700;
            text-decoration: none; display: inline-flex; align-items: center; gap: 0.6rem; transition: all 0.3s;
        }
        .btn-cta:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.15); color: var(--primary-dark); }

        /* Footer */
        .footer { background: var(--text-dark); padding: 3rem 0 1.5rem; }
        .footer-brand { font-size: 1.3rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.8rem; }
        .footer-brand i { color: var(--primary-light); }
        .footer-desc { color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; }
        .footer-title { font-size: 0.85rem; font-weight: 700; text-transform: uppercase; color: #fff; margin-bottom: 1rem; }
        .footer-links { list-style: none; padding: 0; margin: 0; }
        .footer-links li { margin-bottom: 0.5rem; }
        .footer-links a { color: var(--text-muted); text-decoration: none; font-size: 0.9rem; }
        .footer-links a:hover { color: var(--primary-light); }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.08); margin-top: 2rem; padding-top: 1.5rem; text-align: center; color: #64748b; font-size: 0.85rem; }

        .animate-on-scroll { opacity: 0; transform: translateY(30px); transition: all 0.6s cubic-bezier(0.4,0,0.2,1); }
        .animate-on-scroll.visible { opacity: 1; transform: translateY(0); }

        @media (max-width: 991px) {
            .hero-title { font-size: 2.3rem; }
            .pricing-card.featured { transform: scale(1); }
            .pricing-card.featured:hover { transform: translateY(-8px); }
        }
        @media (max-width: 767px) {
            .hero-title { font-size: 1.9rem; }
            .section-title { font-size: 1.7rem; }
            .hero-actions { flex-direction: column; }
            .btn-hero-primary, .btn-hero-secondary { width: 100%; justify-content: center; }
            .free-highlight .items { flex-direction: column; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar-landing">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="navbar-brand-custom" href="/"><i class="fas fa-cash-register"></i> Kaixa</a>
        <div class="d-flex align-items-center gap-3">
            <a href="#precos" class="nav-link-custom d-none d-md-inline">Planos</a>
            <a href="#como-funciona" class="nav-link-custom d-none d-md-inline">Como Funciona</a>
            <a href="#faq" class="nav-link-custom d-none d-md-inline">FAQ</a>
            <a href="/" class="nav-link-custom d-none d-md-inline"><i class="fas fa-cloud me-1"></i>Versão Online</a>
        </div>
    </div>
</nav>

<!-- Hero -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="hero-badge"><i class="fas fa-gift"></i> 100% Grátis - Sem cartão de crédito</div>
                <h1 class="hero-title">
                    Emissor <span class="gradient-text">NFC-e Grátis</span><br>
                    para o seu negócio
                </h1>
                <p class="hero-subtitle">
                    Emita notas fiscais ao consumidor (NFC-e) sem pagar nada.
                    Sistema completo com PDV, controle de estoque e relatórios.
                    Até 30 NFC-e por mês no plano gratuito.
                </p>
                <div class="hero-actions">
                    <a href="#precos" class="btn-hero-primary">
                        <i class="fas fa-download"></i> Baixar Grátis
                    </a>
                    <a href="/" class="btn-hero-secondary">
                        <i class="fas fa-cloud"></i> Prefere online? Conheça o SaaS
                    </a>
                </div>

                <div class="free-highlight">
                    <h5><i class="fas fa-check-circle me-1"></i> Incluso no plano grátis:</h5>
                    <div class="items">
                        <div class="item"><i class="fas fa-check"></i> 30 NFC-e/mês</div>
                        <div class="item"><i class="fas fa-check"></i> PDV completo</div>
                        <div class="item"><i class="fas fa-check"></i> Controle de estoque</div>
                        <div class="item"><i class="fas fa-check"></i> Sem prazo de validade</div>
                        <div class="item"><i class="fas fa-check"></i> Sem compromisso</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center">
                <div style="background:#fff; border-radius:20px; border:1px solid var(--border); padding:2rem; box-shadow: 0 25px 60px rgba(0,0,0,0.08);">
                    <div style="display:flex;gap:6px;margin-bottom:1.5rem;">
                        <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;"></span>
                        <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;"></span>
                        <span style="width:10px;height:10px;border-radius:50%;background:#10b981;"></span>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div style="background:var(--bg-gray);border-radius:12px;padding:1.2rem;border:1px solid var(--border);">
                            <div style="font-size:1.4rem;margin-bottom:0.3rem;"><i class="fas fa-file-invoice" style="color:var(--primary);"></i></div>
                            <div style="font-size:1.3rem;font-weight:700;color:var(--text-dark);">NFC-e</div>
                            <div style="font-size:0.75rem;color:var(--text-muted);">Emissão rápida</div>
                        </div>
                        <div style="background:rgba(5,150,105,0.04);border-radius:12px;padding:1.2rem;border:1px solid rgba(5,150,105,0.2);">
                            <div style="font-size:1.4rem;margin-bottom:0.3rem;"><i class="fas fa-cash-register" style="color:var(--primary);"></i></div>
                            <div style="font-size:1.3rem;font-weight:700;color:var(--text-dark);">PDV</div>
                            <div style="font-size:0.75rem;color:var(--text-muted);">Ponto de venda</div>
                        </div>
                        <div style="background:var(--bg-gray);border-radius:12px;padding:1.2rem;border:1px solid var(--border);">
                            <div style="font-size:1.4rem;margin-bottom:0.3rem;"><i class="fas fa-boxes-stacked" style="color:var(--primary);"></i></div>
                            <div style="font-size:1.3rem;font-weight:700;color:var(--text-dark);">Estoque</div>
                            <div style="font-size:0.75rem;color:var(--text-muted);">Controle total</div>
                        </div>
                        <div style="background:var(--bg-gray);border-radius:12px;padding:1.2rem;border:1px solid var(--border);">
                            <div style="font-size:1.4rem;margin-bottom:0.3rem;"><i class="fas fa-chart-line" style="color:var(--primary);"></i></div>
                            <div style="font-size:1.3rem;font-weight:700;color:var(--text-dark);">Relatórios</div>
                            <div style="font-size:0.75rem;color:var(--text-muted);">Dados em tempo real</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Como Funciona -->
<section id="como-funciona" class="section-gray">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label animate-on-scroll"><i class="fas fa-play-circle"></i> Como Funciona</div>
            <h2 class="section-title animate-on-scroll">Comece a emitir NFC-e em 3 passos</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4 animate-on-scroll">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h5>Baixe o Kaixa</h5>
                    <p>Faça o download gratuito e instale no seu computador Windows. Processo rápido e simples.</p>
                </div>
            </div>
            <div class="col-md-4 animate-on-scroll">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h5>Configure sua empresa</h5>
                    <p>Cadastre os dados da sua empresa, certificado digital e comece a cadastrar seus produtos.</p>
                </div>
            </div>
            <div class="col-md-4 animate-on-scroll">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h5>Emita suas NFC-e</h5>
                    <p>Pronto! Use o PDV para vender e emitir NFC-e automaticamente. Até 30 notas por mês grátis.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Funcionalidades -->
<section class="section-white">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label animate-on-scroll"><i class="fas fa-star"></i> Funcionalidades</div>
            <h2 class="section-title animate-on-scroll">Tudo que você precisa, mesmo no plano grátis</h2>
            <p class="section-subtitle animate-on-scroll">O Kaixa oferece um sistema completo de vendas, não apenas um emissor de notas fiscais.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4 animate-on-scroll">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <h5>Emissão de NFC-e</h5>
                    <p>Emita notas fiscais ao consumidor de forma rápida e segura. Comunicação direta com a SEFAZ.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 animate-on-scroll">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-cash-register"></i></div>
                    <h5>PDV Completo</h5>
                    <p>Tela de vendas intuitiva com atalhos de teclado, busca de produtos e múltiplas formas de pagamento.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 animate-on-scroll">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-boxes-stacked"></i></div>
                    <h5>Controle de Estoque</h5>
                    <p>Entradas, saídas e alertas automáticos. Saiba exatamente o que tem em estoque.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 animate-on-scroll">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-chart-pie"></i></div>
                    <h5>Relatórios</h5>
                    <p>Acompanhe vendas, faturamento, produtos mais vendidos e fluxo de caixa em tempo real.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 animate-on-scroll">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-users"></i></div>
                    <h5>Cadastro de Clientes</h5>
                    <p>Mantenha o cadastro dos seus clientes para emissão de notas com CPF/CNPJ identificado.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 animate-on-scroll">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-shield-halved"></i></div>
                    <h5>Segurança</h5>
                    <p>Seus dados ficam protegidos no seu computador. Backup automático e criptografia dos dados.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing -->
<section id="precos" class="section-gray">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label animate-on-scroll"><i class="fas fa-tags"></i> Planos</div>
            <h2 class="section-title animate-on-scroll">Comece grátis, evolua quando precisar</h2>
            <p class="section-subtitle animate-on-scroll">O plano gratuito é completo e não tem prazo de validade. Use pelo tempo que quiser.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <?php foreach ($planos as $plano):
                $recursos = is_array($plano['recursos']) ? $plano['recursos'] : (json_decode($plano['recursos'] ?? '{}', true) ?: []);
                $destaque = !empty($recursos['destaque']);
                $descricao = $recursos['descricao'] ?? '';
                $beneficios = $recursos['beneficios'] ?? [];
                $preco = (float)$plano['preco'];
                $precoMensal = $plano['preco_mensal'] ?? null;
                $precoTrimestral = $plano['preco_trimestral'] ?? null;
                $precoSemestral = $plano['preco_semestral'] ?? null;
                $precoAnual = $plano['preco_anual'] ?? null;
            ?>
            <div class="col-md-6 col-lg-5 animate-on-scroll">
                <div class="pricing-card<?= $destaque ? ' featured' : '' ?>">
                    <?php if ($destaque): ?><div class="pricing-popular">Grátis para sempre</div><?php endif; ?>
                    <div class="pricing-name"><?= htmlspecialchars($plano['nome']) ?></div>
                    <?php if ($descricao): ?><div class="pricing-desc"><?= htmlspecialchars($descricao) ?></div><?php endif; ?>

                    <?php if ($preco == 0 && !$precoMensal): ?>
                        <div class="pricing-price"><span class="currency">R$</span> 0 <span class="period">/mês</span></div>
                        <div class="pricing-from">Para sempre grátis</div>
                    <?php else: ?>
                        <?php
                            $melhorPreco = $precoAnual ?: $precoSemestral ?: $precoTrimestral ?: $precoMensal ?: $preco;
                            $inteiro = floor($melhorPreco);
                            $centavos = round(($melhorPreco - $inteiro) * 100);
                        ?>
                        <div class="pricing-price">
                            <span class="currency">R$</span> <?= $inteiro ?><?php if ($centavos > 0): ?><span style="font-size:1.5rem">,<?= str_pad($centavos, 2, '0') ?></span><?php endif; ?> <span class="period">/mês</span>
                        </div>
                        <?php if ($precoAnual && $precoMensal && $precoAnual < $precoMensal): ?>
                            <div class="pricing-from">no plano anual (ou R$ <?= number_format($precoMensal, 2, ',', '.') ?>/mês)</div>
                        <?php else: ?>
                            <div class="pricing-from">&nbsp;</div>
                        <?php endif; ?>

                        <?php if ($precoMensal || $precoTrimestral || $precoSemestral || $precoAnual): ?>
                        <div class="pricing-periods">
                            <?php if ($precoMensal): ?><span class="period-option">Mensal: R$ <?= number_format($precoMensal, 2, ',', '.') ?></span><?php endif; ?>
                            <?php if ($precoTrimestral): ?><span class="period-option">Trim: R$ <?= number_format($precoTrimestral, 2, ',', '.') ?>/mês</span><?php endif; ?>
                            <?php if ($precoSemestral): ?><span class="period-option">Sem: R$ <?= number_format($precoSemestral, 2, ',', '.') ?>/mês</span><?php endif; ?>
                            <?php if ($precoAnual): ?><span class="period-option best">Anual: R$ <?= number_format($precoAnual, 2, ',', '.') ?>/mês</span><?php endif; ?>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($beneficios)): ?>
                    <ul class="pricing-features">
                        <?php foreach ($beneficios as $b): ?>
                            <li><i class="fas fa-check"></i> <?= htmlspecialchars($b) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <?php if ($preco == 0 && !$precoMensal): ?>
                        <a href="#" class="btn-pricing btn-pricing-primary"><i class="fas fa-download me-1"></i> Baixar Grátis</a>
                    <?php else: ?>
                        <a href="#" class="btn-pricing btn-pricing-outline">Assinar Agora</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4">
            <p class="text-muted">
                <i class="fas fa-cloud me-1"></i>Prefere acessar de qualquer lugar? Conheça a <a href="/" style="color:var(--primary);font-weight:600;">versão online (SaaS)</a> do Kaixa.
            </p>
        </div>
    </div>
</section>

<!-- FAQ -->
<section id="faq" class="section-white">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label animate-on-scroll"><i class="fas fa-circle-question"></i> Dúvidas Frequentes</div>
            <h2 class="section-title animate-on-scroll">Perguntas sobre o Emissor NFC-e Grátis</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">O emissor NFC-e é realmente grátis? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Sim! O plano Free permite emitir até 30 NFC-e por mês sem pagar nada, sem prazo de validade e sem precisar de cartão de crédito. Você pode usar pelo tempo que quiser.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Preciso de certificado digital? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Sim, para emitir NFC-e é necessário ter um certificado digital A1 (arquivo). Ele é exigido pela SEFAZ para garantir a autenticidade das notas fiscais.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Funciona em qual sistema operacional? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>O Kaixa Desktop funciona em Windows 10 ou superior. Se você prefere acessar pelo navegador (sem instalar), conheça nossa versão online (SaaS) que funciona em qualquer dispositivo.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">E se eu precisar de mais de 30 NFC-e por mês? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Basta fazer upgrade para o plano Pro, que oferece emissão ilimitada de NFC-e. O upgrade é feito dentro do próprio sistema, sem perder nenhum dado.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Qual a diferença entre a versão Desktop e a versão Online? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>A versão Desktop é instalada no seu computador e funciona offline. A versão Online (SaaS) funciona pelo navegador e pode ser acessada de qualquer dispositivo (PC, tablet, celular). Ambas emitem NFC-e.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Meus dados ficam seguros? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Na versão Desktop, seus dados ficam armazenados localmente no seu computador, com criptografia e backup automático. Você tem total controle sobre suas informações.</p></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section" style="padding: 5rem 0;">
    <div class="container text-center">
        <h2 class="cta-title animate-on-scroll">Comece a emitir NFC-e grátis agora</h2>
        <p class="cta-text animate-on-scroll">Sem compromisso, sem cartão de crédito. Baixe, instale e comece a usar.</p>
        <a href="#" class="btn-cta animate-on-scroll"><i class="fas fa-download"></i> Baixar Kaixa Grátis</a>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="footer-brand"><i class="fas fa-cash-register"></i> Kaixa</div>
                <p class="footer-desc">Emissor NFC-e grátis e sistema de vendas completo para o seu negócio.</p>
            </div>
            <div class="col-md-2">
                <div class="footer-title">Produto</div>
                <ul class="footer-links">
                    <li><a href="#precos">Planos Desktop</a></li>
                    <li><a href="/">Versão Online</a></li>
                    <li><a href="#como-funciona">Como Funciona</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <div class="footer-title">Recursos</div>
                <ul class="footer-links">
                    <li><a href="#faq">Perguntas Frequentes</a></li>
                    <li><a href="#">Suporte</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <div class="footer-title">Contato</div>
                <ul class="footer-links">
                    <li><a href="#">contato@pdvpro.com</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Kaixa. Todos os direitos reservados.</p>
        </div>
    </div>
</footer>

<script>
function toggleFaq(btn) {
    const answer = btn.nextElementSibling;
    const isOpen = answer.classList.contains('open');
    document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('open'));
    document.querySelectorAll('.faq-question').forEach(q => q.classList.remove('active'));
    if (!isOpen) { answer.classList.add('open'); btn.classList.add('active'); }
}

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('visible'); } });
}, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
document.querySelectorAll('.animate-on-scroll').forEach(el => observer.observe(el));
</script>
</body>
</html>
