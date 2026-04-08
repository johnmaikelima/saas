<?php
// Landing page pública - sem autenticação
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PDV Pro - Sistema de vendas completo para o seu negócio. PDV, estoque, clientes, relatórios e muito mais. 100% online.">
    <title>PDV Pro - Sistema de Vendas Completo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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

        .navbar-landing.scrolled {
            padding: 0.6rem 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        .navbar-brand-custom {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-dark) !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand-custom i {
            color: var(--primary);
            font-size: 1.6rem;
        }

        .nav-link-custom {
            color: var(--text-body) !important;
            font-weight: 500;
            font-size: 0.9rem;
            padding: 0.5rem 1rem !important;
            transition: color 0.3s;
            text-decoration: none;
        }

        .nav-link-custom:hover { color: var(--primary) !important; }

        .btn-nav-login {
            border: 1px solid var(--border);
            color: var(--text-body) !important;
            border-radius: 8px;
            padding: 0.45rem 1.2rem !important;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-nav-login:hover {
            background: var(--bg-gray);
            border-color: var(--primary-light);
            color: var(--primary) !important;
        }

        .btn-nav-register {
            background: var(--primary);
            color: #fff !important;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.3rem !important;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-nav-register:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }

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

        .hero-section::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -15%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.06) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(79, 70, 229, 0.08);
            border: 1px solid rgba(79, 70, 229, 0.15);
            border-radius: 50px;
            padding: 0.4rem 1.2rem;
            font-size: 0.85rem;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1.5rem;
            animation: fadeInDown 0.8s ease;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            color: var(--text-dark);
            animation: fadeInUp 0.8s ease;
        }

        .hero-title .gradient-text {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: var(--text-body);
            line-height: 1.7;
            margin-bottom: 2.5rem;
            max-width: 520px;
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        .hero-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease 0.4s both;
        }

        .btn-hero-primary {
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 0.9rem 2rem;
            font-size: 1.05rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(79, 70, 229, 0.25);
        }

        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(79, 70, 229, 0.35);
            color: #fff;
            background: var(--primary-dark);
        }

        .btn-hero-secondary {
            background: #fff;
            color: var(--text-body);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0.9rem 2rem;
            font-size: 1.05rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            transition: all 0.3s;
        }

        .btn-hero-secondary:hover {
            border-color: var(--primary-light);
            color: var(--primary);
            background: #fff;
        }

        .hero-stats {
            display: flex;
            gap: 2.5rem;
            margin-top: 3rem;
            animation: fadeInUp 0.8s ease 0.6s both;
        }

        .hero-stat { text-align: center; }

        .hero-stat-number {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
        }

        .hero-stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.2rem;
        }

        .hero-image-area {
            position: relative;
            animation: fadeInRight 1s ease 0.3s both;
        }

        .hero-mockup {
            background: #fff;
            border-radius: 20px;
            border: 1px solid var(--border);
            padding: 2rem;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        .mockup-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .mockup-dot { width: 10px; height: 10px; border-radius: 50%; }
        .mockup-dot.red { background: #ef4444; }
        .mockup-dot.yellow { background: #f59e0b; }
        .mockup-dot.green { background: #10b981; }

        .mockup-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .mockup-card {
            background: var(--bg-gray);
            border-radius: 12px;
            padding: 1.2rem;
            border: 1px solid var(--border);
        }

        .mockup-card-icon { font-size: 1.4rem; margin-bottom: 0.5rem; }
        .mockup-card-value { font-size: 1.3rem; font-weight: 700; color: var(--text-dark); }
        .mockup-card-label { font-size: 0.75rem; color: var(--text-muted); }

        .mockup-card.highlight {
            border-color: rgba(79, 70, 229, 0.2);
            background: rgba(79, 70, 229, 0.04);
        }

        .floating-badge {
            position: absolute;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 0.8rem 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            animation: float 3s ease-in-out infinite;
        }

        .floating-badge.badge-1 { top: -15px; right: -20px; }
        .floating-badge.badge-2 { bottom: -15px; left: -20px; animation-delay: 1.5s; }
        .floating-badge i { font-size: 1.2rem; }
        .floating-badge .fb-text { font-size: 0.8rem; font-weight: 600; color: var(--text-dark); }
        .floating-badge .fb-sub { font-size: 0.7rem; color: var(--text-muted); }

        /* ===== SECTIONS ===== */
        section { padding: 6rem 0; }
        .section-white { background: var(--bg-light); }
        .section-gray { background: var(--bg-gray); }

        .section-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(79, 70, 229, 0.08);
            border: 1px solid rgba(79, 70, 229, 0.12);
            border-radius: 50px;
            padding: 0.35rem 1rem;
            font-size: 0.8rem;
            color: var(--primary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--text-dark);
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: var(--text-body);
            max-width: 600px;
            margin: 0 auto 3rem;
        }

        /* ===== FEATURES ===== */
        .feature-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2rem;
            height: 100%;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            opacity: 0;
            transition: opacity 0.4s;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            border-color: rgba(79, 70, 229, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
        }

        .feature-card:hover::before { opacity: 1; }

        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1.2rem;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(14, 165, 233, 0.08));
            color: var(--primary);
        }

        .feature-card h5 { font-size: 1.15rem; font-weight: 700; margin-bottom: 0.6rem; color: var(--text-dark); }
        .feature-card p { font-size: 0.9rem; color: var(--text-body); line-height: 1.6; margin: 0; }

        /* ===== PRICING ===== */
        .pricing-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2.5rem 2rem;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .pricing-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        }

        .pricing-card.featured {
            border-color: var(--primary);
            box-shadow: 0 0 0 1px var(--primary), 0 10px 40px rgba(79, 70, 229, 0.12);
            transform: scale(1.05);
        }

        .pricing-card.featured:hover { transform: scale(1.05) translateY(-8px); }

        .pricing-popular {
            position: absolute;
            top: -14px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.35rem 1.5rem;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pricing-name { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-dark); }
        .pricing-desc { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem; }

        .pricing-price {
            font-size: 3rem;
            font-weight: 900;
            color: var(--text-dark);
            line-height: 1;
            margin-bottom: 0.3rem;
        }

        .pricing-price .currency { font-size: 1.3rem; font-weight: 600; vertical-align: super; }
        .pricing-price .period { font-size: 1rem; font-weight: 400; color: var(--text-muted); }
        .pricing-from { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.5rem; }

        .pricing-features { list-style: none; padding: 0; margin: 1.5rem 0; text-align: left; flex-grow: 1; }

        .pricing-features li {
            padding: 0.5rem 0;
            font-size: 0.9rem;
            color: var(--text-body);
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }

        .pricing-features li i { font-size: 0.8rem; width: 20px; text-align: center; }
        .pricing-features li i.fa-check { color: var(--success); }
        .pricing-features li i.fa-xmark { color: #cbd5e1; }
        .pricing-features li.disabled { color: #cbd5e1; }

        .btn-pricing {
            display: block;
            width: 100%;
            padding: 0.85rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s;
            margin-top: auto;
        }

        .btn-pricing-outline {
            border: 1px solid var(--border);
            color: var(--primary);
            background: transparent;
        }

        .btn-pricing-outline:hover {
            background: var(--bg-gray);
            border-color: var(--primary-light);
            color: var(--primary-dark);
        }

        .btn-pricing-primary {
            background: var(--primary);
            color: #fff;
            border: none;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.25);
        }

        .btn-pricing-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.35);
            color: #fff;
            background: var(--primary-dark);
        }

        /* ===== FAQ ===== */
        .faq-item {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            margin-bottom: 1rem;
            overflow: hidden;
            transition: all 0.3s;
        }

        .faq-item:hover { border-color: rgba(79, 70, 229, 0.2); }

        .faq-question {
            width: 100%;
            background: none;
            border: none;
            color: var(--text-dark);
            padding: 1.3rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            text-align: left;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: color 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .faq-question:hover { color: var(--primary); }
        .faq-question i { transition: transform 0.3s; color: var(--primary); }
        .faq-question.active i { transform: rotate(180deg); }

        .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.4s ease, padding 0.4s ease; }
        .faq-answer.open { max-height: 300px; }
        .faq-answer p { padding: 0 1.5rem 1.3rem; color: var(--text-body); line-height: 1.7; font-size: 0.95rem; margin: 0; }

        /* ===== CTA ===== */
        .cta-section {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%; right: -20%;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .cta-title { font-size: 2.5rem; font-weight: 800; color: #fff; margin-bottom: 1rem; }
        .cta-text { font-size: 1.1rem; color: rgba(255,255,255,0.85); margin-bottom: 2rem; }

        .btn-cta {
            background: #fff;
            color: var(--primary-dark);
            border: none;
            border-radius: 12px;
            padding: 0.9rem 2.5rem;
            font-size: 1.05rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            transition: all 0.3s;
        }

        .btn-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            color: var(--primary-dark);
        }

        /* ===== FOOTER ===== */
        .footer {
            background: var(--text-dark);
            padding: 3rem 0 1.5rem;
        }

        .footer-brand {
            font-size: 1.3rem;
            font-weight: 800;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.8rem;
        }

        .footer-brand i { color: var(--primary-light); }
        .footer-desc { color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; max-width: 300px; }

        .footer-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #fff;
            margin-bottom: 1rem;
        }

        .footer-links { list-style: none; padding: 0; margin: 0; }
        .footer-links li { margin-bottom: 0.5rem; }
        .footer-links a { color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.3s; }
        .footer-links a:hover { color: var(--primary-light); }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.08);
            margin-top: 2rem;
            padding-top: 1.5rem;
            text-align: center;
            color: #64748b;
            font-size: 0.85rem;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInRight { from { opacity: 0; transform: translateX(40px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .animate-on-scroll.visible { opacity: 1; transform: translateY(0); }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 991px) {
            .hero-title { font-size: 2.5rem; }
            .hero-image-area { margin-top: 3rem; }
            .pricing-card.featured { transform: scale(1); }
            .pricing-card.featured:hover { transform: translateY(-8px); }
            .hero-stats { gap: 1.5rem; }
        }

        @media (max-width: 767px) {
            .hero-title { font-size: 2rem; }
            .hero-subtitle { font-size: 1rem; }
            .section-title { font-size: 1.8rem; }
            .hero-stats { flex-direction: column; gap: 1rem; align-items: flex-start; }
            .cta-title { font-size: 1.8rem; }
            .hero-actions { flex-direction: column; }
            .btn-hero-primary, .btn-hero-secondary { width: 100%; justify-content: center; }
        }

        .navbar-toggler-custom {
            border: 1px solid var(--border);
            padding: 0.4rem 0.7rem;
            border-radius: 8px;
            background: none;
            color: var(--text-dark);
            font-size: 1.2rem;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar-landing">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <a href="#" class="navbar-brand-custom">
                <i class="fas fa-bolt"></i>
                PDV Pro
            </a>

            <button class="navbar-toggler-custom d-lg-none" onclick="document.getElementById('navMenu').classList.toggle('show')" aria-label="Menu">
                <i class="fas fa-bars"></i>
            </button>

            <div class="d-none d-lg-flex align-items-center gap-3">
                <a href="#funcionalidades" class="nav-link-custom">Funcionalidades</a>
                <a href="#precos" class="nav-link-custom">Preços</a>
                <a href="#faq" class="nav-link-custom">FAQ</a>
                <a href="/auth/login.php" class="btn-nav-login">Entrar</a>
                <a href="/auth/register.php" class="btn-nav-register">Criar Conta</a>
            </div>
        </div>

        <div id="navMenu" class="d-lg-none mt-3" style="display:none;">
            <div class="d-flex flex-column gap-2 pb-3">
                <a href="#funcionalidades" class="nav-link-custom" onclick="document.getElementById('navMenu').style.display='none'">Funcionalidades</a>
                <a href="#precos" class="nav-link-custom" onclick="document.getElementById('navMenu').style.display='none'">Preços</a>
                <a href="#faq" class="nav-link-custom" onclick="document.getElementById('navMenu').style.display='none'">FAQ</a>
                <div class="d-flex gap-2 mt-2">
                    <a href="/auth/login.php" class="btn-nav-login flex-fill text-center">Entrar</a>
                    <a href="/auth/register.php" class="btn-nav-register flex-fill text-center">Criar Conta</a>
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
                    <i class="fas fa-sparkles"></i>
                    Novo - Agora 100% na nuvem
                </div>
                <h1 class="hero-title">
                    PDV Pro<br>
                    <span class="gradient-text">Sistema de vendas completo</span>
                </h1>
                <p class="hero-subtitle">
                    Gerencie vendas, estoque, clientes e finanças em um só lugar.
                    Simples, rápido e acessível de qualquer dispositivo.
                </p>
                <div class="hero-actions">
                    <a href="/auth/register.php?plano=free" class="btn-hero-primary">
                        <i class="fas fa-rocket"></i> Começar Grátis
                    </a>
                    <a href="/auth/login.php" class="btn-hero-secondary">
                        <i class="fas fa-sign-in-alt"></i> Já tem conta? Entrar
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-number">100%</div>
                        <div class="hero-stat-label">Online</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-number">0</div>
                        <div class="hero-stat-label">Instalação</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-number">24/7</div>
                        <div class="hero-stat-label">Disponível</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image-area">
                    <div class="hero-mockup">
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
                            <i class="fas fa-chart-line" style="color: var(--primary);"></i>
                            <div>
                                <div class="fb-text">+23% este mês</div>
                                <div class="fb-sub">Faturamento</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section id="funcionalidades" class="section-white">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label animate-on-scroll"><i class="fas fa-star"></i> Funcionalidades</div>
            <h2 class="section-title animate-on-scroll">Tudo que você precisa para vender mais</h2>
            <p class="section-subtitle animate-on-scroll">Ferramentas poderosas para gerenciar seu negócio de ponta a ponta, sem complicação.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-cash-register"></i></div>
                    <h5>PDV Completo</h5>
                    <p>Tela de vendas rápida e intuitiva. Registre vendas em segundos com atalhos de teclado, busca de produtos e múltiplas formas de pagamento.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-boxes-stacked"></i></div>
                    <h5>Controle de Estoque</h5>
                    <p>Entradas, saídas e alertas automáticos. Saiba exatamente o que tem em estoque e receba avisos quando um produto está acabando.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-users"></i></div>
                    <h5>Gestão de Clientes</h5>
                    <p>Cadastro completo com histórico de compras. Conheça seus clientes, fidelize e aumente suas vendas com dados inteligentes.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-chart-bar"></i></div>
                    <h5>Relatórios</h5>
                    <p>Vendas, lucratividade e muito mais. Dashboards visuais e relatórios detalhados para tomar decisões baseadas em dados reais.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-user-shield"></i></div>
                    <h5>Multi-usuário</h5>
                    <p>Perfis de admin, gerente e caixa. Controle quem acessa o quê com permissões granulares para cada tipo de usuário.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon"><i class="fas fa-cloud"></i></div>
                    <h5>100% Online</h5>
                    <p>Acesse de qualquer lugar, a qualquer hora. Basta um navegador e conexão com a internet. Sem instalação, sem complicação.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing -->
<section id="precos" class="section-gray">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label animate-on-scroll"><i class="fas fa-tags"></i> Planos e Preços</div>
            <h2 class="section-title animate-on-scroll">Escolha o plano ideal para o seu negócio</h2>
            <p class="section-subtitle animate-on-scroll">Comece grátis e evolua conforme sua necessidade. Sem fidelidade, cancele quando quiser.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-6 col-lg-4 animate-on-scroll">
                <div class="pricing-card">
                    <div class="pricing-name">Free</div>
                    <div class="pricing-desc">Para quem está começando</div>
                    <div class="pricing-price"><span class="currency">R$</span> 0 <span class="period">/mês</span></div>
                    <div class="pricing-from">&nbsp;</div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check"></i> Até 50 produtos</li>
                        <li><i class="fas fa-check"></i> 1 usuário</li>
                        <li><i class="fas fa-check"></i> PDV básico</li>
                        <li><i class="fas fa-check"></i> Relatórios simples</li>
                        <li class="disabled"><i class="fas fa-xmark"></i> Controle de estoque</li>
                        <li class="disabled"><i class="fas fa-xmark"></i> Gestão de clientes</li>
                        <li class="disabled"><i class="fas fa-xmark"></i> Suporte prioritário</li>
                    </ul>
                    <a href="/auth/register.php?plano=free" class="btn-pricing btn-pricing-outline">Começar Grátis</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 animate-on-scroll">
                <div class="pricing-card featured">
                    <div class="pricing-popular">Mais Popular</div>
                    <div class="pricing-name">Basic</div>
                    <div class="pricing-desc">Para pequenos negócios</div>
                    <div class="pricing-price"><span class="currency">R$</span> 49<span style="font-size:1.5rem">,90</span> <span class="period">/mês</span></div>
                    <div class="pricing-from">a partir de</div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check"></i> Até 500 produtos</li>
                        <li><i class="fas fa-check"></i> 3 usuários</li>
                        <li><i class="fas fa-check"></i> PDV completo</li>
                        <li><i class="fas fa-check"></i> Controle de estoque</li>
                        <li><i class="fas fa-check"></i> Gestão de clientes</li>
                        <li><i class="fas fa-check"></i> Relatórios avançados</li>
                        <li class="disabled"><i class="fas fa-xmark"></i> Suporte prioritário</li>
                    </ul>
                    <a href="/auth/register.php?plano=basic" class="btn-pricing btn-pricing-primary">Começar Agora</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 animate-on-scroll">
                <div class="pricing-card">
                    <div class="pricing-name">Pro</div>
                    <div class="pricing-desc">Para empresas em crescimento</div>
                    <div class="pricing-price"><span class="currency">R$</span> 99<span style="font-size:1.5rem">,90</span> <span class="period">/mês</span></div>
                    <div class="pricing-from">a partir de</div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check"></i> Produtos ilimitados</li>
                        <li><i class="fas fa-check"></i> Usuários ilimitados</li>
                        <li><i class="fas fa-check"></i> PDV completo</li>
                        <li><i class="fas fa-check"></i> Controle de estoque</li>
                        <li><i class="fas fa-check"></i> Gestão de clientes</li>
                        <li><i class="fas fa-check"></i> Relatórios avançados</li>
                        <li><i class="fas fa-check"></i> Suporte prioritário 24/7</li>
                    </ul>
                    <a href="/auth/register.php?plano=pro" class="btn-pricing btn-pricing-outline">Começar Agora</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section id="faq" class="section-white">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label animate-on-scroll"><i class="fas fa-circle-question"></i> Dúvidas Frequentes</div>
            <h2 class="section-title animate-on-scroll">Perguntas Frequentes</h2>
            <p class="section-subtitle animate-on-scroll">Tire suas dúvidas sobre o PDV Pro e descubra como podemos ajudar o seu negócio.</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Preciso instalar algum programa? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Não! O PDV Pro é 100% online. Basta acessar pelo navegador do seu computador, tablet ou celular. Não é necessário instalar nenhum programa ou aplicativo.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Posso testar gratuitamente? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Sim! Oferecemos um plano gratuito para você conhecer o sistema sem compromisso. Você pode usar o plano Free pelo tempo que quiser e fazer upgrade quando sentir necessidade.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Como funciona o controle de estoque? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>O sistema atualiza automaticamente o estoque a cada venda realizada. Você também pode registrar entradas manuais, fazer ajustes e configurar alertas para quando um produto atingir o estoque mínimo.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Quantos usuários posso cadastrar? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Depende do seu plano. O plano Free permite 1 usuário, o Basic até 3 usuários e o Pro oferece usuários ilimitados. Cada usuário pode ter um perfil diferente: administrador, gerente ou caixa.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Meus dados estão seguros? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Sim! Utilizamos criptografia de ponta a ponta, servidores seguros e backups automáticos diários. Seus dados ficam protegidos e acessíveis apenas por usuários autorizados da sua conta.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Posso cancelar a qualquer momento? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>Sim! Não exigimos fidelidade nem contratos longos. Você pode cancelar ou mudar de plano a qualquer momento diretamente pelo painel, sem burocracia.</p></div>
                </div>
                <div class="faq-item animate-on-scroll">
                    <button class="faq-question" onclick="toggleFaq(this)">Emite nota fiscal? <i class="fas fa-chevron-down"></i></button>
                    <div class="faq-answer"><p>O PDV Pro é um sistema de gestão de vendas e controle interno. A emissão de notas fiscais pode ser integrada com soluções parceiras. Entre em contato para mais informações sobre integrações disponíveis.</p></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container text-center">
        <h2 class="cta-title animate-on-scroll">Pronto para transformar suas vendas?</h2>
        <p class="cta-text animate-on-scroll">Junte-se a centenas de negócios que já usam o PDV Pro.<br>Crie sua conta gratuitamente em menos de 2 minutos.</p>
        <a href="/auth/register.php?plano=free" class="btn-cta animate-on-scroll"><i class="fas fa-rocket"></i> Começar Grátis Agora</a>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="footer-brand"><i class="fas fa-bolt"></i> PDV Pro</div>
                <p class="footer-desc">Sistema de vendas completo para o seu negócio. Simples, rápido e 100% online.</p>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title">Produto</div>
                <ul class="footer-links">
                    <li><a href="#funcionalidades">Funcionalidades</a></li>
                    <li><a href="#precos">Preços</a></li>
                    <li><a href="#faq">FAQ</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title">Acesso</div>
                <ul class="footer-links">
                    <li><a href="/auth/login.php">Entrar</a></li>
                    <li><a href="/auth/register.php">Criar Conta</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title">Legal</div>
                <ul class="footer-links">
                    <li><a href="#">Termos de Uso</a></li>
                    <li><a href="#">Privacidade</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title">Contato</div>
                <ul class="footer-links">
                    <li><a href="#">Suporte</a></li>
                    <li><a href="#">contato@pdvpro.com</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">&copy; 2026 PDV Pro. Todos os direitos reservados.</div>
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
