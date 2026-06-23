<?php
include 'config.php';

$totalPatients  = $conn->query("SELECT COUNT(*) as t FROM Patients")->fetch_assoc()['t'];
$totalTests     = $conn->query("SELECT COUNT(*) as t FROM LabTests")->fetch_assoc()['t'];
$totalUsers     = $conn->query("SELECT COUNT(*) as t FROM Users WHERE IsActive=1")->fetch_assoc()['t'];
$completedTests = $conn->query("SELECT COUNT(*) as t FROM LabTests WHERE Status='Completed'")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIMS — Laboratory Information Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --accent: #6246ea; --accent2: #2563eb; --ink: #0f0e17; --paper: #fffffe; --surface: #f7f6fb; --muted: #72737d; --border: #e8e7f0; }
        html { scroll-behavior: smooth; }
        body { font-family: 'DM Sans', sans-serif; background: var(--paper); color: var(--ink); overflow-x: hidden; }

        /* NAVBAR */
        .navbar { position: fixed; top: 0; left: 0; right: 0; z-index: 100; background: rgba(255,255,255,0.92); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); height: 64px; display: flex; align-items: center; justify-content: space-between; padding: 0 3rem; transition: box-shadow 0.3s; }
        .navbar.scrolled { box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .nav-logo { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.3rem; color: var(--ink); text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-logo-mark { width: 38px; height: 38px; background: linear-gradient(145deg, #7c5cff, #5234d4); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 16px rgba(98,70,234,0.4), inset 0 1px 1px rgba(255,255,255,0.25); position: relative; }
        .nav-logo-mark svg { width: 21px; height: 21px; }
        .nav-logo-text { display: flex; flex-direction: column; line-height: 1.1; }
        .nav-logo-text .sub { font-family: 'DM Sans', sans-serif; font-size: 0.62rem; font-weight: 500; color: var(--muted); letter-spacing: 0.08em; text-transform: uppercase; }
        .nav-links { display: flex; align-items: center; gap: 2rem; }
        .nav-links a { color: var(--muted); text-decoration: none; font-size: 0.88rem; font-weight: 500; transition: color 0.2s; }
        .nav-links a:hover { color: var(--accent); }
        .nav-btns { display: flex; gap: 10px; }
        .btn-outline { padding: 8px 20px; border: 1.5px solid var(--accent); color: var(--accent); border-radius: 50px; font-size: 0.85rem; font-weight: 600; text-decoration: none; transition: all 0.2s; }
        .btn-outline:hover { background: var(--accent); color: #fff; }
        .btn-solid { padding: 8px 20px; background: var(--accent); color: #fff; border-radius: 50px; font-size: 0.85rem; font-weight: 600; text-decoration: none; transition: opacity 0.2s; }
        .btn-solid:hover { opacity: 0.88; }

        /* HERO */
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f0e17 0%, #1a1040 50%, #0f2050 100%);
            display: flex; align-items: center;
            padding: 8rem 3rem 4rem;
            position: relative; overflow: hidden;
        }
        .hero-bg-circle1 { position: absolute; width: 600px; height: 600px; border-radius: 50%; background: radial-gradient(circle, rgba(98,70,234,0.2) 0%, transparent 70%); top: 50%; left: 30%; transform: translate(-50%,-50%); }
        .hero-bg-circle2 { position: absolute; width: 300px; height: 300px; border-radius: 50%; border: 1px solid rgba(98,70,234,0.2); top: 10%; right: 5%; animation: float 6s ease-in-out infinite; }
        .hero-bg-circle3 { position: absolute; width: 150px; height: 150px; border-radius: 50%; border: 1px solid rgba(98,70,234,0.15); bottom: 15%; left: 8%; animation: float 8s ease-in-out infinite reverse; }
        @keyframes float { 0%,100%{transform:translateY(0);} 50%{transform:translateY(-20px);} }

        .hero-inner { max-width: 1200px; margin: 0 auto; width: 100%; display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center; position: relative; z-index: 2; }
        .hero-badge { display: inline-block; background: rgba(98,70,234,0.2); border: 1px solid rgba(98,70,234,0.4); color: #a78bfa; padding: 6px 18px; border-radius: 50px; font-size: 0.78rem; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 1.5rem; }
        .hero h1 { font-family: 'Syne', sans-serif; font-size: clamp(2.2rem, 5vw, 3.8rem); font-weight: 800; color: #fff; line-height: 1.1; margin-bottom: 1.25rem; }
        .hero h1 span { color: #a78bfa; }
        .hero p { font-size: 1rem; color: rgba(255,255,255,0.65); line-height: 1.75; margin-bottom: 2.5rem; }
        .hero-btns { display: flex; gap: 1rem; flex-wrap: wrap; }
        .hero-btn-primary { padding: 14px 32px; background: var(--accent); color: #fff; border-radius: 50px; font-family: 'Syne', sans-serif; font-weight: 700; font-size: 0.95rem; text-decoration: none; transition: transform 0.2s, opacity 0.2s; box-shadow: 0 8px 30px rgba(98,70,234,0.4); }
        .hero-btn-primary:hover { transform: translateY(-2px); opacity: 0.9; }
        .hero-btn-secondary { padding: 14px 32px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff; border-radius: 50px; font-family: 'Syne', sans-serif; font-weight: 600; font-size: 0.95rem; text-decoration: none; }
        .hero-btn-secondary:hover { background: rgba(255,255,255,0.18); }

        /* Hero Image Side */
        .hero-visual { position: relative; display: flex; align-items: center; justify-content: center; }
        .hero-img-main { width: 100%; max-width: 420px; border-radius: 20px; box-shadow: 0 30px 60px rgba(0,0,0,0.5); animation: float 5s ease-in-out infinite; display: block; }
        .hero-float-card { position: absolute; background: rgba(255,255,255,0.95); border-radius: 14px; padding: 12px 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); display: flex; align-items: center; gap: 10px; font-size: 0.8rem; font-weight: 600; color: #0f0e17; }
        .hero-float-card.card1 { top: 10%; left: -10%; animation: float 4s ease-in-out infinite; }
        .hero-float-card.card2 { bottom: 15%; right: -8%; animation: float 5s ease-in-out infinite reverse; }
        .card-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }

        /* STATS */
        .stats-bar { background: var(--ink); padding: 3rem; border-top: 1px solid rgba(255,255,255,0.05); }
        .stats-inner { max-width: 1000px; margin: 0 auto; display: grid; grid-template-columns: repeat(4,1fr); gap: 2rem; text-align: center; }
        .stat-item .num { font-family: 'Syne', sans-serif; font-size: 2.5rem; font-weight: 800; color: #a78bfa; line-height: 1; margin-bottom: 6px; }
        .stat-item .lbl { font-size: 0.82rem; color: rgba(255,255,255,0.45); text-transform: uppercase; letter-spacing: 0.06em; }

        /* SECTIONS */
        section { padding: 6rem 3rem; }
        .section-inner { max-width: 1100px; margin: 0 auto; }
        .section-tag { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--accent); margin-bottom: 0.75rem; }
        .section-title { font-family: 'Syne', sans-serif; font-size: clamp(1.8rem, 4vw, 2.5rem); font-weight: 800; color: var(--ink); margin-bottom: 1rem; line-height: 1.15; }
        .section-sub { font-size: 1rem; color: var(--muted); line-height: 1.7; max-width: 520px; }

        /* FEATURES */
        .features-section { background: var(--surface); }
        .features-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center; margin-top: 3rem; }
        .features-list { display: flex; flex-direction: column; gap: 1.25rem; }
        .feature-item { display: flex; gap: 1rem; align-items: flex-start; background: var(--paper); border: 1px solid var(--border); border-radius: 14px; padding: 1.25rem; transition: transform 0.15s, box-shadow 0.15s; }
        .feature-item:hover { transform: translateX(4px); box-shadow: 0 4px 20px rgba(98,70,234,0.08); }
        .feature-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
        .feature-item h3 { font-family: 'Syne', sans-serif; font-size: 0.95rem; font-weight: 700; margin-bottom: 4px; }
        .feature-item p { font-size: 0.83rem; color: var(--muted); line-height: 1.5; }
        .features-img { display: flex; flex-direction: column; gap: 1rem; }
        .features-img-main { border-radius: 20px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.12); aspect-ratio: 4/3; }
        .features-img-main img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .features-img-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .features-img-row .small-img { border-radius: 14px; overflow: hidden; box-shadow: 0 10px 28px rgba(0,0,0,0.1); aspect-ratio: 4/3; }
        .features-img-row .small-img img { width: 100%; height: 100%; object-fit: cover; display: block; }

        /* ROLES */
        .roles-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 1.25rem; margin-top: 3rem; }
        .role-card { border-radius: 16px; padding: 1.75rem; display: flex; gap: 1.25rem; align-items: flex-start; transition: transform 0.15s; }
        .role-card:hover { transform: translateY(-3px); }
        .role-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; flex-shrink: 0; }
        .role-card h3 { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; margin-bottom: 0.5rem; }
        .role-card ul { padding-left: 1rem; }
        .role-card ul li { font-size: 0.83rem; color: var(--muted); margin-bottom: 4px; line-height: 1.5; }

        /* CAPABILITIES SECTION */
        .ai-section { background: linear-gradient(135deg, #0f0e17, #1a1040); color: #fff; }
        .ai-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center; }
        .ai-tag { display: inline-block; background: rgba(167,139,250,0.2); border: 1px solid rgba(167,139,250,0.3); color: #a78bfa; padding: 4px 14px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 1rem; }
        .ai-section h2 { font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800; margin-bottom: 1rem; }
        .ai-section p { color: rgba(255,255,255,0.65); line-height: 1.75; margin-bottom: 1.5rem; }
        .ai-features { display: flex; flex-direction: column; gap: 0.75rem; }
        .ai-feat { display: flex; align-items: center; gap: 10px; color: rgba(255,255,255,0.8); font-size: 0.88rem; }
        .ai-feat::before { content: '✓'; width: 22px; height: 22px; background: rgba(98,70,234,0.3); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; color: #a78bfa; flex-shrink: 0; }
        .ai-img { border-radius: 20px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.4); aspect-ratio: 4/3.6; }
        .ai-img img { width: 100%; height: 100%; object-fit: cover; display: block; }

        /* TEAM / ROLES VISUAL */
        .team-section { background: var(--surface); }
        .team-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1.5rem; margin-top: 3rem; }
        .team-card { background: var(--paper); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem; text-align: center; transition: transform 0.15s, box-shadow 0.15s; }
        .team-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.08); }
        .team-img { width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 1rem; object-fit: cover; border: 3px solid var(--border); }
        .team-card h3 { font-family: 'Syne', sans-serif; font-size: 0.95rem; font-weight: 700; margin-bottom: 4px; }
        .team-card p { font-size: 0.8rem; color: var(--muted); }
        .team-badge { display: inline-block; padding: 3px 12px; border-radius: 50px; font-size: 0.72rem; font-weight: 600; margin-top: 8px; }

        /* CTA */
        .cta-section { background: linear-gradient(135deg, var(--accent), var(--accent2)); text-align: center; color: #fff; }
        .cta-section h2 { font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 800; margin-bottom: 1rem; }
        .cta-section p { font-size: 1rem; opacity: 0.8; margin-bottom: 2rem; }
        .cta-btns { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
        .cta-btn-white { padding: 14px 32px; background: #fff; color: var(--accent); border-radius: 50px; font-family: 'Syne', sans-serif; font-weight: 700; font-size: 0.95rem; text-decoration: none; transition: transform 0.2s; }
        .cta-btn-white:hover { transform: translateY(-2px); }
        .cta-btn-ghost { padding: 14px 32px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); color: #fff; border-radius: 50px; font-family: 'Syne', sans-serif; font-weight: 600; font-size: 0.95rem; text-decoration: none; }

        /* FOOTER */
        footer { background: var(--ink); padding: 3rem; }
        .footer-inner { max-width: 1100px; margin: 0 auto; }
        .footer-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 2rem; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid rgba(255,255,255,0.08); flex-wrap: wrap; }
        .footer-brand .footer-logo { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.3rem; color: #fff; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 10px; }
        .footer-brand p { font-size: 0.83rem; color: rgba(255,255,255,0.4); max-width: 240px; line-height: 1.6; }
        .footer-col h4 { font-family: 'Syne', sans-serif; font-size: 0.8rem; font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.75rem; }
        .footer-col a { display: block; color: rgba(255,255,255,0.45); text-decoration: none; font-size: 0.83rem; margin-bottom: 0.4rem; transition: color 0.2s; }
        .footer-col a:hover { color: #fff; }
        .footer-bottom { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .footer-copy { font-size: 0.78rem; color: rgba(255,255,255,0.3); }
        .footer-dev { font-size: 0.82rem; color: rgba(255,255,255,0.5); }
        .footer-dev strong { color: #a78bfa; font-weight: 700; }

        @media (max-width: 900px) {
            .hero-inner, .features-layout, .ai-layout { grid-template-columns: 1fr; }
            .hero-visual { display: none; }
            .team-grid { grid-template-columns: repeat(2,1fr); }
            .roles-grid { grid-template-columns: 1fr; }
            .stats-inner { grid-template-columns: repeat(2,1fr); }
            section { padding: 4rem 1.5rem; }
            .navbar { padding: 0 1.5rem; }
            .nav-links { display: none; }
        }
        @media (max-width: 480px) { .features-img-row { grid-template-columns: 1fr; } }

        @keyframes fadeUp { from{opacity:0;transform:translateY(30px);} to{opacity:1;transform:translateY(0);} }
        .fade-up { animation: fadeUp 0.6s ease forwards; }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .count-up { display: inline-block; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <a href="/LIMS/welcome.php" class="nav-logo">
        <span class="nav-logo-mark">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 21H15" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/>
                <path d="M12 21V17" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/>
                <path d="M7 17H17C17 13.5 16 12.5 15 12C16 11.3 16.5 10.2 16.5 9C16.5 6.5 14.5 4.5 12 4.5C9.5 4.5 7.5 6.5 7.5 9C7.5 10.2 8 11.3 9 12C8 12.5 7 13.5 7 17Z" stroke="#fff" stroke-width="1.7" stroke-linejoin="round"/>
                <path d="M10.5 3V5" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/>
                <circle cx="12" cy="9" r="1.4" fill="#fff"/>
            </svg>
        </span>
        <span class="nav-logo-text">LIMS<span class="sub">Lab System</span></span>
    </a>
    <div class="nav-links">
        <a href="#features">Features</a>
        <a href="#roles">Roles</a>
        <a href="#ai">Highlights</a>
        <a href="#about">About</a>
    </div>
    <div class="nav-btns">
        <a href="/LIMS/login.php" class="btn-outline">Patient Portal</a>
        <a href="/LIMS/auth_login.php" class="btn-solid">Staff Login →</a>
    </div>
</nav>

<!-- HERO -->
<section class="hero" id="home">
    <div class="hero-bg-circle1"></div>
    <div class="hero-bg-circle2"></div>
    <div class="hero-bg-circle3"></div>
    <div class="hero-inner">
        <div>
            <div class="hero-badge fade-up">Lab Management Made Simple</div>
            <h1 class="fade-up delay-1">Smart Laboratory<br><span>Information System</span></h1>
            <p class="fade-up delay-2">A complete platform for managing lab samples, test results, patient reports, and multi-branch operations — all in one secure system.</p>
            <div class="hero-btns fade-up delay-3">
                <a href="/LIMS/auth_login.php" class="hero-btn-primary">Staff Login →</a>
                <a href="/LIMS/login.php" class="hero-btn-secondary">Patient Portal</a>
            </div>
        </div>
        <div class="hero-visual">
            <img src="https://images.unsplash.com/photo-1582719471384-894fbb16e074?w=500&h=620&fit=crop&q=80" alt="Doctor reviewing lab results" class="hero-img-main">
            <div class="hero-float-card card1">
                <div class="card-icon" style="background:#dcfce7;">🧪</div>
                <div><div style="font-size:0.72rem;color:#72737d;">Tests Logged</div><div style="font-size:1rem;font-weight:800;color:#15803d;"><?php echo $totalTests; ?>+</div></div>
            </div>
            <div class="hero-float-card card2">
                <div class="card-icon" style="background:#ede9fe;">👥</div>
                <div><div style="font-size:0.72rem;color:#72737d;">Patients</div><div style="font-size:1rem;font-weight:800;color:#6246ea;"><?php echo $totalPatients; ?>+</div></div>
            </div>
        </div>
    </div>
</section>

<!-- STATS -->
<div class="stats-bar">
    <div class="stats-inner">
        <div class="stat-item"><div class="num count-up" data-target="<?php echo $totalPatients; ?>"><?php echo $totalPatients; ?>+</div><div class="lbl">Patients Registered</div></div>
        <div class="stat-item"><div class="num count-up" data-target="<?php echo $totalTests; ?>"><?php echo $totalTests; ?>+</div><div class="lbl">Lab Tests Recorded</div></div>
        <div class="stat-item"><div class="num count-up" data-target="<?php echo $completedTests; ?>"><?php echo $completedTests; ?>+</div><div class="lbl">Tests Completed</div></div>
        <div class="stat-item"><div class="num count-up" data-target="<?php echo $totalUsers; ?>"><?php echo $totalUsers; ?></div><div class="lbl">Active Staff</div></div>
    </div>
</div>

<!-- FEATURES -->
<section class="features-section" id="features">
    <div class="section-inner">
        <div>
            <p class="section-tag">Core Features</p>
            <h2 class="section-title">Everything your lab needs</h2>
            <p class="section-sub">From sample registration to PDF report generation — one unified, secure platform.</p>
        </div>
        <div class="features-layout">
            <div class="features-list">
                <div class="feature-item">
                    <div class="feature-icon" style="background:#ede9fe;">🔐</div>
                    <div><h3>Role-Based Access Control</h3><p>Admin, Technician, Manager, Doctor — each with their own secure dashboard and permissions.</p></div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon" style="background:#dcfce7;">🧪</div>
                    <div><h3>Sample & Test Management</h3><p>Register, assign, and track lab tests from Registered → Testing → Completed with real-time status.</p></div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon" style="background:#dbeafe;">📄</div>
                    <div><h3>PDF Report Generation</h3><p>Professional patient reports with full test history. Print or save as PDF instantly.</p></div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon" style="background:#fef3c7;">📋</div>
                    <div><h3>Audit Logging</h3><p>Every action logged with timestamp, user ID, and IP — full compliance traceability.</p></div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon" style="background:#ffe4e6;">💰</div>
                    <div><h3>Billing & Invoicing</h3><p>Auto-generate invoices for every test. Track payments — Cash or Card.</p></div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon" style="background:#f0fdf4;">📅</div>
                    <div><h3>Appointment Booking</h3><p>Patients book appointments online. Staff confirm, complete, or cancel with one click.</p></div>
                </div>
            </div>
            <div class="features-img">
                <div class="features-img-main">
                    <img src="https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=600&q=80" alt="Lab equipment and samples">
                </div>
                <div class="features-img-row">
                    <div class="small-img">
                        <img src="https://images.unsplash.com/photo-1579154204601-01588f351e67?w=300&q=80" alt="Sample tubes">
                    </div>
                    <div class="small-img">
                        <img src="https://images.unsplash.com/photo-1631815589968-fdb09a223b1e?w=300&q=80" alt="Lab analysis">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ROLES / TEAM -->
<section class="team-section" id="roles">
    <div class="section-inner">
        <p class="section-tag">User Roles</p>
        <h2 class="section-title">Built for every stakeholder</h2>
        <p class="section-sub">Four distinct roles, each with tailored dashboards and access levels.</p>
        <div class="team-grid">
            <div class="team-card">
                <img src="https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=200&h=200&fit=crop&q=80" alt="Admin" class="team-img">
                <h3>System Admin</h3>
                <p>Full system control, user management, audit logs</p>
                <span class="team-badge" style="background:#ede9fe;color:#7c3aed;">⚙️ Admin</span>
            </div>
            <div class="team-card">
                <img src="https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=200&h=200&fit=crop&q=80" alt="Technician" class="team-img">
                <h3>Lab Technician</h3>
                <p>Register samples, record results, update tracking</p>
                <span class="team-badge" style="background:#e0f2fe;color:#0891b2;">🧪 Technician</span>
            </div>
            <div class="team-card">
                <img src="https://images.unsplash.com/photo-1582750433449-648ed127bb54?w=200&h=200&fit=crop&q=80" alt="Manager" class="team-img">
                <h3>Lab Manager</h3>
                <p>Monitor operations, analytics, billing, reports</p>
                <span class="team-badge" style="background:#dcfce7;color:#059669;">📊 Manager</span>
            </div>
            <div class="team-card">
                <img src="https://images.unsplash.com/photo-1651008376811-b90baee60c1f?w=200&h=200&fit=crop&q=80" alt="Doctor" class="team-img">
                <h3>Doctor / Client</h3>
                <p>View results, download reports, read-only access</p>
                <span class="team-badge" style="background:#fee2e2;color:#dc2626;">👨‍⚕️ Doctor</span>
            </div>
        </div>
    </div>
</section>

<!-- HIGHLIGHTS -->
<section class="ai-section" id="ai">
    <div class="section-inner">
        <div class="ai-layout">
            <div>
                <div class="ai-tag">Behind the Scenes</div>
                <h2>Handles the busy work for you</h2>
                <p>The system takes care of repetitive tasks automatically so your team can focus on patients instead of paperwork.</p>
                <div class="ai-features">
                    <div class="ai-feat">Auto-flags results as Normal, High, or Low</div>
                    <div class="ai-feat">Generates a scannable barcode for every sample</div>
                    <div class="ai-feat">Sends email notifications when results are ready</div>
                    <div class="ai-feat">Locks accounts and alerts admins after failed logins</div>
                    <div class="ai-feat">Live analytics dashboard with charts</div>
                    <div class="ai-feat">Auto-calculates billing for every test</div>
                </div>
            </div>
            <div class="ai-img">
                <img src="https://images.unsplash.com/photo-1631815589968-fdb09a223b1e?w=600&q=80" alt="Lab technician analyzing samples">
            </div>
        </div>
    </div>
</section>

<!-- ABOUT -->
<section id="about">
    <div class="section-inner">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:center;">
            <div>
                <p class="section-tag">About</p>
                <h2 class="section-title">Built for Modern Healthcare</h2>
                <p style="color:var(--muted);line-height:1.8;margin-bottom:1.25rem;">LIMS is a comprehensive, secure, web-based laboratory management platform designed to digitize and streamline all lab operations — replacing manual, paper-based workflows.</p>
                <p style="color:var(--muted);line-height:1.8;margin-bottom:2rem;">Built with industry-standard security practices — prepared statements, bcrypt hashing, CSRF protection, session management, and full audit logging.</p>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;">
                    <?php $tech=[['PHP 8.0','Backend','#ede9fe','#7c3aed'],['MariaDB','Database','#dcfce7','#059669'],['XAMPP','Server','#dbeafe','#1d4ed8'],['HTML/CSS','Frontend','#fef3c7','#b45309'],['JavaScript','Dynamic UI','#ffe4e6','#dc2626'],['PHPMailer','Emails','#f3e8ff','#9333ea']]; foreach($tech as $t): ?>
                    <div style="background:<?php echo $t[2]; ?>;border-radius:12px;padding:0.9rem;text-align:center;">
                        <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:0.9rem;color:<?php echo $t[3]; ?>;"><?php echo $t[0]; ?></div>
                        <div style="font-size:0.72rem;color:<?php echo $t[3]; ?>;opacity:0.7;"><?php echo $t[1]; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="border-radius:20px;overflow:hidden;box-shadow:0 20px 50px rgba(0,0,0,0.1);aspect-ratio:4/3.6;">
                <img src="https://images.pexels.com/photos/3825586/pexels-photo-3825586.jpeg?auto=compress&cs=tinysrgb&w=600" alt="Lab researcher reviewing samples" style="width:100%;height:100%;object-fit:cover;display:block;">
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="section-inner">
        <h2>Ready to get started?</h2>
        <p>Login as staff to manage the laboratory, or access the patient portal to view your reports.</p>
        <div class="cta-btns">
            <a href="/LIMS/auth_login.php" class="cta-btn-white">Staff Login →</a>
            <a href="/LIMS/login.php" class="cta-btn-ghost">Patient Portal</a>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="footer-inner">
        <div class="footer-top">
            <div class="footer-brand">
                <div class="footer-logo">
                    <span class="nav-logo-mark">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 21H15" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/>
                            <path d="M12 21V17" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/>
                            <path d="M7 17H17C17 13.5 16 12.5 15 12C16 11.3 16.5 10.2 16.5 9C16.5 6.5 14.5 4.5 12 4.5C9.5 4.5 7.5 6.5 7.5 9C7.5 10.2 8 11.3 9 12C8 12.5 7 13.5 7 17Z" stroke="#fff" stroke-width="1.7" stroke-linejoin="round"/>
                            <path d="M10.5 3V5" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/>
                            <circle cx="12" cy="9" r="1.4" fill="#fff"/>
                        </svg>
                    </span>
                    LIMS
                </div>
                <p>A secure, complete Laboratory Information Management System for modern healthcare.</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <a href="#features">Features</a>
                <a href="#roles">User Roles</a>
                <a href="#ai">Highlights</a>
                <a href="#about">About</a>
            </div>
            <div class="footer-col">
                <h4>Access</h4>
                <a href="/LIMS/auth_login.php">Staff Login</a>
                <a href="/LIMS/login.php">Patient Portal</a>
            </div>
            <div class="footer-col">
                <h4>System</h4>
                <a href="/LIMS/auth_login.php">Admin Panel</a>
                <a href="/LIMS/welcome.php">Home</a>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="footer-copy">© <?php echo date('Y'); ?> LIMS — All rights reserved.</div>
            <div class="footer-dev">Developed by <strong>Iqra Shahzadi</strong></div>
        </div>
    </div>
</footer>

<script>
window.addEventListener('scroll', function() {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 50);
});

function animateCounter(el, target) {
    let start = 0;
    const duration = 2000;
    const step = Math.max(1, Math.ceil(target / (duration / 16)));
    const timer = setInterval(() => {
        start += step;
        if (start >= target) { start = target; clearInterval(timer); }
        el.textContent = start + '+';
    }, 16);
}

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const target = parseInt(entry.target.dataset.target);
            if (target) animateCounter(entry.target, target);
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

document.querySelectorAll('.count-up').forEach(el => observer.observe(el));
</script>
</body>
</html>