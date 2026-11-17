<?php 
require __DIR__ . '/includes/auth.php';
boot_session();
require __DIR__ . '/includes/db.php'; 

$is_logged_in = isset($_SESSION['user_id']) || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);
$page_title = "RG CARTINTWORKS | Car Window Tinting Services";

try {
    $packages_stmt = $pdo->query("
        SELECT p.*, v.type_name 
        FROM tint_packages p 
        LEFT JOIN vehicle_types v ON p.vehicle_type_id = v.id 
        WHERE p.is_active = 1 
        ORDER BY p.price ASC
    ");
    $packages = $packages_stmt->fetchAll();
} catch (PDOException $e) {
    $packages = [];
}

try {
    $vehicle_types_stmt = $pdo->query("SELECT * FROM vehicle_types WHERE is_active = 1 ORDER BY type_name");
    $vehicle_types = $vehicle_types_stmt->fetchAll();
} catch (PDOException $e) {
    $vehicle_types = [];
}

$price_column = 'price';
if (isset($packages[0])) {
    if (array_key_exists('package_price', $packages[0])) $price_column = 'package_price';
    elseif (array_key_exists('total_price', $packages[0])) $price_column = 'total_price';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="Professional window tinting services in Davao. Nano Ceramic, Carbon, Hybrid tints for all vehicles. Official warranty, 99% UV protection.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,300&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">

    <style>
        
        :root {
            --gold:          #D4A017;
            --gold-light:    #F5C842;
            --gold-pale:     #FFF8DC;
            --ink:           #0A0A0A;
            --ink-mid:       #161616;
            --ink-soft:      #2A2A2A;
            --smoke:         #F5F3EF;
            --smoke-dark:    #E6E2DA;
            --white:         #FFFFFF;
            --text-body:     #333333;
            --text-dim:      #6E6862;
            --text-muted:    #9E9890;

            --font-display:  'Bebas Neue', sans-serif;
            --font-serif:    'DM Serif Display', serif;
            --font-body:     'DM Sans', sans-serif;

            --radius-xs: 4px;
            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 22px;
            --radius-xl: 36px;

            --shadow-sm:   0 2px 8px rgba(0,0,0,.07);
            --shadow-md:   0 8px 30px rgba(0,0,0,.11);
            --shadow-lg:   0 20px 60px rgba(0,0,0,.17);
            --shadow-xl:   0 28px 80px rgba(0,0,0,.25);
            --shadow-gold: 0 8px 32px rgba(212,160,23,.28);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font-body);
            background: var(--white);
            color: var(--text-body);
            overflow-x: hidden;
            line-height: 1.65;
        }

        h1, h2, h3, h4, h5, h6 { font-family: var(--font-body); font-weight: 700; }

        
        .hero-section {
            background:
                linear-gradient(to right, rgba(10,10,10,.92) 42%, rgba(10,10,10,.55) 100%),
                url('https://images.unsplash.com/photo-1544636331-e26879cd4d9b?auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            min-height: 60vh;
            display: flex;
            align-items: center;
            position: relative;
            color: white;
            padding-top: 100px;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0; right: 30%;
            width: 1px; height: 100%;
            background: linear-gradient(to bottom, transparent 0%, rgba(212,160,23,.4) 50%, transparent 100%);
            pointer-events: none;
        }
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 140px;
            background: linear-gradient(transparent, var(--white));
            pointer-events: none;
        }
        .hero-content { position: relative; z-index: 2; max-width: 680px; }

        .hero-badge {
            background: rgba(212,160,23,.15);
            border: 1px solid rgba(212,160,23,.4);
            color: var(--gold-light);
            padding: .55rem 1.3rem;
            border-radius: 50px;
            font-size: .8rem;
            font-weight: 600;
            letter-spacing: .14em;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: .6rem;
            margin-bottom: 1.8rem;
        }
        .hero-badge-dot {
            width: 7px; height: 7px;
            background: var(--gold-light);
            border-radius: 50%;
            animation: blink 1.8s ease infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.25} }

        .hero-title {
            font-family: var(--font-display);
            font-size: clamp(3rem, 7vw, 6rem);
            line-height: .92;
            letter-spacing: .03em;
            color: var(--white);
            margin-bottom: 1.6rem;
        }
        .hero-title .gold-accent {
            color: var(--gold-light);
            display: block;
            font-family: var(--font-serif);
            font-style: italic;
            font-size: clamp(1.4rem, 3vw, 2.5rem);
            letter-spacing: .01em;
            line-height: 1.3;
        }
        .hero-subtitle {
            font-size: 1.05rem;
            color: rgba(255,255,255,.65);
            max-width: 440px;
            line-height: 1.8;
            margin-bottom: 2.4rem;
            font-weight: 300;
        }

        
        .breadcrumb-wrap {
            background: rgba(212,160,23,.12);
            border: 1px solid rgba(212,160,23,.25);
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .4rem 1.1rem;
            margin-bottom: 1.4rem;
            font-size: .8rem;
            color: rgba(255,255,255,.6);
        }
        .breadcrumb-wrap a { color: var(--gold-light); text-decoration: none; }
        .breadcrumb-wrap a:hover { color: var(--white); }
        .breadcrumb-wrap i { font-size: .65rem; }

        
        .btn-primary-custom {
            background: var(--gold);
            border: none;
            padding: .85rem 2rem;
            font-weight: 600;
            font-size: .95rem;
            border-radius: 50px;
            color: var(--ink);
            transition: all .3s;
            box-shadow: var(--shadow-gold);
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            text-decoration: none;
            letter-spacing: .02em;
        }
        .btn-primary-custom:hover {
            background: var(--gold-light);
            color: var(--ink);
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(212,160,23,.45);
        }
        .btn-secondary-custom {
            background: transparent;
            border: 1.5px solid rgba(255,255,255,.3);
            color: rgba(255,255,255,.85);
            padding: .85rem 2rem;
            font-weight: 500;
            font-size: .95rem;
            border-radius: 50px;
            transition: all .3s;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            text-decoration: none;
        }
        .btn-secondary-custom:hover {
            border-color: rgba(255,255,255,.7);
            color: var(--white);
            background: rgba(255,255,255,.07);
        }

        
        .promo-ribbon {
            background: var(--ink);
            border-top: 1px solid rgba(212,160,23,.2);
            border-bottom: 1px solid rgba(212,160,23,.2);
            padding: .9rem 0;
            overflow: hidden;
        }
        .ribbon-track {
            display: flex;
            gap: 3.5rem;
            animation: marquee 24s linear infinite;
            white-space: nowrap;
            width: max-content;
        }
        .ribbon-item {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            font-family: var(--font-display);
            font-size: .95rem;
            letter-spacing: .1em;
            color: var(--white);
        }
        .ribbon-item i { color: var(--gold); }
        @keyframes marquee { from{transform:translateX(0)} to{transform:translateX(-50%)} }

        
        .section { padding: 5.5rem 0; }
        .section-dark { background: var(--ink-mid); }
        .section-smoke { background: var(--smoke); }

        .section-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--text-dim);
            margin-bottom: .9rem;
        }
        .section-eyebrow::before {
            content: '';
            display: inline-block;
            width: 22px; height: 2px;
            background: var(--gold);
            border-radius: 2px;
        }
        .section-title {
            font-family: var(--font-display);
            font-size: clamp(2.2rem, 4vw, 3.5rem);
            letter-spacing: .04em;
            line-height: 1;
            margin-bottom: .9rem;
            color: var(--ink);
        }
        .section-title-light { color: var(--white); }
        .section-title em {
            font-family: var(--font-serif);
            font-style: italic;
            color: var(--gold-light);
        }
        .section-subtitle {
            font-size: 1.02rem;
            color: var(--text-dim);
            max-width: 540px;
            font-weight: 300;
            line-height: 1.75;
        }
        .section-subtitle-light { color: rgba(255,255,255,.5); }

        
        .offer-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            height: 100%;
            box-shadow: var(--shadow-md);
            transition: all .4s cubic-bezier(.4,0,.2,1);
            position: relative;
            border: 1.5px solid var(--smoke-dark);
            display: flex;
            flex-direction: column;
        }
        .offer-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
            border-color: var(--gold);
        }
        .offer-card.offer-featured { border-color: var(--gold); box-shadow: var(--shadow-gold); }

        .offer-badge {
            position: absolute;
            top: 18px; right: -34px;
            background: var(--gold);
            color: var(--ink);
            padding: .55rem 3rem;
            font-weight: 700;
            font-size: .75rem;
            letter-spacing: .1em;
            text-transform: uppercase;
            transform: rotate(45deg);
            box-shadow: var(--shadow-sm);
            z-index: 2;
        }
        .offer-header {
            background: var(--ink);
            color: var(--white);
            padding: 2rem 1.8rem 1.6rem;
            text-align: center;
        }
        .offer-header h4 {
            font-family: var(--font-body);
            font-weight: 600;
            font-size: 1rem;
            line-height: 1.4;
            min-height: 2.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: .75rem;
        }
        .offer-price {
            font-family: var(--font-display);
            font-size: 3.2rem;
            letter-spacing: .02em;
            color: var(--gold-light);
            line-height: 1;
            margin-bottom: .4rem;
        }
        .original-price {
            font-size: 1rem;
            text-decoration: line-through;
            opacity: .55;
            color: rgba(255,255,255,.7);
        }
        .offer-savings {
            display: inline-block;
            background: #22c55e;
            color: #fff;
            padding: .2rem .7rem;
            border-radius: 50px;
            font-size: .72rem;
            font-weight: 700;
            margin-top: .4rem;
        }
        .offer-body {
            padding: 1.6rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .offer-body h5 {
            font-size: .82rem;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--text-dim);
            margin-bottom: 1rem;
            text-align: center;
            min-height: 2.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .offer-features {
            list-style: none;
            padding: 0; margin: 0;
            flex: 1;
        }
        .offer-features li {
            padding: .55rem 0;
            border-bottom: 1px solid var(--smoke);
            display: flex;
            align-items: flex-start;
            gap: .6rem;
            font-size: .86rem;
            color: var(--text-body);
            line-height: 1.45;
        }
        .offer-features li:last-child { border: none; }
        .offer-features li i { color: var(--gold); flex-shrink: 0; margin-top: .15rem; }

        
        .pricing-table-wrap {
            background: var(--white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1.5px solid var(--smoke-dark);
        }
        .pricing-table-head {
            background: var(--ink);
            color: var(--white);
            padding: 1.4rem 2rem;
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .pricing-table-head h4 {
            font-family: var(--font-display);
            font-size: 1.6rem;
            letter-spacing: .06em;
            color: var(--gold-light);
            margin: 0;
        }
        .pricing-table-head i { color: var(--gold); font-size: 1.2rem; }
        .table-custom { margin: 0; }
        .table-custom thead th {
            background: var(--smoke);
            color: var(--ink);
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            border: none;
            padding: 1rem 1.2rem;
        }
        .table-custom tbody td {
            padding: .95rem 1.2rem;
            border-color: var(--smoke);
            font-size: .88rem;
            vertical-align: middle;
        }
        .table-custom tbody tr:hover { background: var(--gold-pale); }
        .table-custom tbody tr.popular-row { background: rgba(212,160,23,.06); }
        .table-custom tbody tr.popular-row td:first-child { border-left: 3px solid var(--gold); }

        .book-btn {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .35rem .85rem;
            border-radius: 50px;
            font-size: .78rem;
            font-weight: 600;
            text-decoration: none;
            transition: all .25s;
            white-space: nowrap;
        }
        .book-btn-gold { background: var(--gold); color: var(--ink); border: none; }
        .book-btn-gold:hover { background: var(--gold-light); color: var(--ink); transform: translateY(-2px); }
        .book-btn-outline { background: transparent; color: var(--ink); border: 1.5px solid var(--smoke-dark); }
        .book-btn-outline:hover { border-color: var(--gold); color: var(--gold); background: var(--gold-pale); }

        
        .tint-compare-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            height: 100%;
            box-shadow: var(--shadow-md);
            border: 1.5px solid var(--smoke-dark);
            transition: all .4s cubic-bezier(.4,0,.2,1);
            position: relative;
        }
        .tint-compare-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--gold);
        }
        .tint-compare-card.featured {
            border-color: var(--gold);
            box-shadow: var(--shadow-gold);
        }
        .tint-compare-header {
            padding: 2rem 1.8rem 1.4rem;
            text-align: center;
            position: relative;
        }
        .tint-icon-wrap {
            width: 72px; height: 72px;
            border-radius: 50%;
            margin: 0 auto 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: var(--white);
            box-shadow: var(--shadow-md);
        }
        .tint-compare-body { padding: 1.4rem 1.8rem 1.8rem; }
        .heat-bar-wrap { margin-bottom: 1rem; }
        .heat-bar-label { font-size: .75rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--text-dim); margin-bottom: .35rem; display: flex; justify-content: space-between; }
        .heat-bar-track { height: 6px; background: var(--smoke-dark); border-radius: 99px; overflow: hidden; }
        .heat-bar-fill { height: 100%; background: linear-gradient(90deg, var(--gold), var(--gold-light)); border-radius: 99px; transition: width .8s cubic-bezier(.4,0,.2,1); }

        
        .car-type-offer {
            background: var(--white);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all .3s;
            height: 100%;
            border: 1.5px solid var(--smoke-dark);
        }
        .car-type-offer:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--gold);
        }
        .car-image {
            height: 190px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .car-overlay {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            background: linear-gradient(transparent, rgba(10,10,10,.8));
            color: var(--white);
            padding: 1rem;
        }
        .car-overlay h5 { font-size: .95rem; font-weight: 600; margin-bottom: .1rem; }
        .car-overlay small { color: var(--gold-light); font-size: .78rem; }

        
        .benefit-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5px;
            background: var(--smoke-dark);
            border: 1.5px solid var(--smoke-dark);
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-top: 3rem;
        }
        .benefit-cell {
            background: var(--white);
            padding: 2.4rem 2rem;
            transition: background .25s;
        }
        .benefit-cell:hover { background: var(--gold-pale); }
        .benefit-icon {
            width: 46px; height: 46px;
            background: var(--ink);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-size: 1.1rem;
            margin-bottom: 1.2rem;
        }
        .benefit-cell h5 { font-size: .98rem; font-weight: 600; margin-bottom: .4rem; }
        .benefit-cell p { font-size: .86rem; color: var(--text-dim); line-height: 1.65; margin: 0; }

        
        .process-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
            position: relative;
        }
        .process-grid::before {
            content: '';
            position: absolute;
            top: 36px;
            left: 12.5%;
            right: 12.5%;
            height: 2px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light), var(--gold));
            opacity: .3;
        }
        .process-step {
            text-align: center;
            padding: 2rem 1.5rem;
        }
        .step-num {
            width: 72px; height: 72px;
            background: var(--ink);
            border: 2px solid rgba(212,160,23,.4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            font-family: var(--font-display);
            font-size: 1.8rem;
            color: var(--gold-light);
            letter-spacing: .05em;
            transition: all .3s;
            position: relative;
            z-index: 1;
        }
        .process-step:hover .step-num {
            background: var(--gold);
            color: var(--ink);
            border-color: var(--gold);
            transform: scale(1.1);
        }
        .process-step h5 { font-size: .98rem; font-weight: 600; margin-bottom: .4rem; color: var(--white); }
        .process-step p { font-size: .82rem; color: rgba(255,255,255,.45); line-height: 1.65; margin: 0; }

        
        .capacity-card {
            text-align: center;
            padding: 2.5rem 1.5rem;
            border: 1px solid rgba(255,255,255,.07);
            border-radius: var(--radius-lg);
            transition: border-color .3s, background .3s;
        }
        .capacity-card:hover {
            border-color: rgba(212,160,23,.3);
            background: rgba(212,160,23,.04);
        }
        .capacity-icon {
            width: 64px; height: 64px;
            background: var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
        }
        .capacity-number {
            font-family: var(--font-display);
            font-size: 4.5rem;
            color: var(--gold-light);
            line-height: 1;
            letter-spacing: .02em;
            margin-bottom: .3rem;
        }
        .capacity-label { font-size: .98rem; font-weight: 600; color: var(--white); margin-bottom: .3rem; }
        .capacity-unit { font-size: .75rem; color: rgba(255,255,255,.35); letter-spacing: .1em; text-transform: uppercase; }

        
        .cta-section { background: var(--gold); padding: 5.5rem 0; }
        .cta-section h2 {
            font-family: var(--font-display);
            font-size: clamp(2.2rem, 4vw, 3.5rem);
            color: var(--ink);
            letter-spacing: .04em;
        }
        .btn-cta-dark {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: var(--ink);
            color: var(--white);
            padding: .9rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            font-size: 1rem;
            transition: all .25s;
        }
        .btn-cta-dark:hover { background: var(--ink-soft); color: var(--white); transform: translateY(-2px); }
        .btn-cta-outline {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: transparent;
            color: var(--ink);
            border: 2px solid var(--ink);
            padding: .9rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            font-size: 1rem;
            transition: all .25s;
        }
        .btn-cta-outline:hover { background: var(--ink); color: var(--white); }

        
        .floating-action-buttons {
            position: fixed;
            bottom: 30px; right: 30px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 14px;
        }
        .fab-main { animation: fabFloat 2.5s ease-in-out infinite; order: 2; }
        @keyframes fabFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }

        .fab-secondary-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-end;
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transform: translateY(16px);
            transition: all .4s cubic-bezier(.4,0,.2,1);
            order: 1;
        }
        .fab-secondary-container.show {
            max-height: 600px;
            opacity: 1;
            transform: translateY(0);
            margin-bottom: 14px;
        }
        .fab-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: .75rem 1.4rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: .88rem;
            text-decoration: none;
            box-shadow: var(--shadow-lg);
            border: none;
            cursor: pointer;
            transition: all .3s cubic-bezier(.4,0,.2,1);
            position: relative;
            overflow: hidden;
            min-width: 165px;
            z-index: 1;
            letter-spacing: .02em;
        }
        .fab-button:hover { transform: translateY(-4px) scale(1.04); box-shadow: var(--shadow-xl); text-decoration: none; }
        .fab-icon { width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,.18); border-radius: 50%; font-size: 1rem; flex-shrink: 0; }
        .fab-text { white-space: nowrap; font-size: .85rem; }
        .fab-badge {
            position: absolute;
            top: -7px; right: -7px;
            background: #e53e3e;
            color: var(--white);
            padding: 2px 8px;
            border-radius: 50px;
            font-size: .65rem;
            font-weight: 800;
            border: 2px solid var(--white);
            animation: badgePulse 1.5s infinite;
        }
        @keyframes badgePulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.12)} }

        .fab-toggle {
            width: 52px; height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            border: 1.5px solid rgba(212,160,23,.35);
            transition: all .3s;
            order: 3;
            background: var(--ink);
            color: var(--gold-light);
        }
        .fab-toggle:hover { transform: rotate(90deg) scale(1.1); background: var(--ink-soft); border-color: var(--gold); }
        .fab-toggle.rotate { transform: rotate(45deg); }

        .fab-button.btn-warning { background: var(--gold); color: var(--ink); border: 1.5px solid rgba(212,160,23,.5); }
        .fab-button.btn-warning:hover { background: var(--gold-light); color: var(--ink); }
        .fab-button.btn-success { background: linear-gradient(135deg,#2ecc71,#27ae60); color: var(--white); }
        .fab-button.btn-info { background: linear-gradient(135deg,#3498db,#2980b9); color: var(--white); }
        .fab-button.btn-primary { background: linear-gradient(135deg,#006AFF,#0084FF); color: var(--white); }

        .fab-main::before {
            content: '';
            position: absolute;
            top: -5px; left: -5px; right: -5px; bottom: -5px;
            background: var(--gold);
            border-radius: 50px;
            z-index: -1;
            opacity: .3;
            animation: pulseRing 2.2s ease-out infinite;
        }
        @keyframes pulseRing { 0%{transform:scale(.95);opacity:.5} 100%{transform:scale(1.18);opacity:0} }

        .animate-pulse { animation: pulseSoft 2s ease-in-out infinite; }
        @keyframes pulseSoft { 0%,100%{transform:scale(1)} 50%{transform:scale(1.04)} }

        
        footer.bg-dark { background: var(--ink) !important; border-top: 1px solid rgba(255,255,255,.06); }
        footer h4.text-warning { color: var(--gold-light) !important; font-family: var(--font-display); letter-spacing: .06em; font-size: 1.7rem; }
        footer .text-warning { color: var(--gold-light) !important; }
        footer a.hover-warning:hover { color: var(--gold-light) !important; }
        .social-icon {
            width: 36px; height: 36px;
            border: 1px solid rgba(255,255,255,.15);
            border-radius: var(--radius-xs);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,.5);
            text-decoration: none;
            margin-right: .4rem;
            transition: all .2s;
            font-size: .85rem;
        }
        .social-icon:hover { border-color: var(--gold); color: var(--gold); }

        
        @keyframes fadeInUp {
            from { opacity:0; transform:translateY(32px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .animate-fadeInUp { animation: fadeInUp .75s ease-out; }

        
        @media (max-width: 1100px) {
            .process-grid { grid-template-columns: repeat(2, 1fr); }
            .process-grid::before { display: none; }
            .benefit-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .hero-section { padding-top: 120px; min-height: auto; padding-bottom: 4rem; }
            .hero-title { font-size: clamp(2.8rem, 12vw, 5rem); }
            .process-grid { grid-template-columns: 1fr 1fr; }
            .floating-action-buttons { bottom: 15px; right: 15px; }
        }
        @media (max-width: 576px) {
            .section { padding: 3.5rem 0; }
            .benefit-grid { grid-template-columns: 1fr; }
            .process-grid { grid-template-columns: 1fr 1fr; }
            .btn-primary-custom, .btn-secondary-custom { width: 100%; justify-content: center; margin-bottom: .5rem; }
            .fab-button { min-width: 130px; padding: .65rem 1rem; font-size: .82rem; }
        }
        @media (max-width: 400px) {
            .fab-text { display: none; }
            .fab-button { width: 48px !important; height: 48px !important; border-radius: 50% !important; padding: 0 !important; min-width: auto !important; }
        }
        @media (hover: none) and (pointer: coarse) {
            .offer-card:hover, .tint-compare-card:hover { transform: none; }
        }

        
        .featured-customer-section {
            background: var(--ink-mid);
            padding: 5.5rem 0;
            position: relative;
            overflow: hidden;
        }
        .featured-customer-section::before {
            content: 'SHOWCASE';
            position: absolute;
            right: -1.5rem; top: 50%;
            transform: translateY(-50%) rotate(90deg);
            font-family: var(--font-display);
            font-size: 9rem;
            color: rgba(255,255,255,.025);
            pointer-events: none;
            white-space: nowrap;
        }
        .customer-showcase {
            background: rgba(255,255,255,.03);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(212,160,23,.18);
            border-radius: var(--radius-xl);
            padding: 3rem;
        }
        .video-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-top: 2rem;
        }
        .video-item {
            background: rgba(0,0,0,.4);
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 1px solid rgba(212,160,23,.2);
            transition: transform .3s, border-color .3s;
        }
        .video-item:hover {
            transform: translateY(-6px);
            border-color: var(--gold);
        }
        .video-container {
            position: relative;
            width: 100%;
            padding-top: 177.78%;
        }
        .video-container iframe {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            border: none;
        }
        .video-caption {
            padding: .9rem 1rem;
            border-top: 1px solid rgba(212,160,23,.15);
        }
        .video-caption h4 {
            font-family: var(--font-body);
            font-weight: 600;
            color: var(--gold-light);
            font-size: .88rem;
            margin-bottom: .3rem;
        }
        .video-caption p {
            font-size: .76rem;
            color: rgba(255,255,255,.5);
            margin: 0;
            line-height: 1.5;
        }
        .video-caption i { color: var(--gold); margin-right: .3rem; }

        @media (max-width: 992px) {
            .video-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 576px) {
            .video-grid { grid-template-columns: 1fr; }
            .customer-showcase { padding: 1.5rem; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<section class="hero-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 col-xl-7">
                <div class="hero-content animate-fadeInUp">
                    <nav class="breadcrumb-wrap">
                        <a href="index.php"><i class="fas fa-home"></i> Home</a>
                        <i class="fas fa-chevron-right"></i>
                        <span style="color:rgba(255,255,255,.5);">Services</span>
                    </nav>

                    <div class="hero-badge">
                        <div class="hero-badge-dot"></div>
                        <span>Professional Tinting Services</span>
                    </div>

                    <h1 class="hero-title">
                        TINTING<br>SERVICES
                        <span class="gold-accent">for every vehicle</span>
                    </h1>

                    <p class="hero-subtitle">
                        Premium window tinting solutions for sedans, SUVs, vans, and trucks. 
                        Beat Davao's heat with nano-ceramic technology and official warranty.
                    </p>

                    <div class="d-flex flex-wrap gap-3">
                        <a href="#packages" class="btn-primary-custom">
                            <i class="fas fa-layer-group"></i> View All Packages
                        </a>
                        <a href="customer/book.php" class="btn-secondary-custom">
                            <i class="fas fa-calendar-check"></i> Book Appointment
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="promo-ribbon">
    <div class="ribbon-track">
        <span class="ribbon-item"><i class="fas fa-home"></i> FREE HOME SERVICE WITHIN DAVAO CITY</span>
        <span class="ribbon-item"><i class="fas fa-shield-alt"></i> OFFICIAL WARRANTY INCLUDED</span>
        <span class="ribbon-item"><i class="fas fa-sun"></i> 99% HEAT & UV REJECTION</span>
        <span class="ribbon-item"><i class="fas fa-bolt"></i> BOOK NOW — 30% OFF CERAMIC TINTS</span>
        <span class="ribbon-item"><i class="fas fa-map-marker-alt"></i> DAVAO · TAGUM · DIGOS · PANABO · CARMEN</span>
        <span class="ribbon-item"><i class="fas fa-home"></i> FREE HOME SERVICE WITHIN DAVAO CITY</span>
        <span class="ribbon-item"><i class="fas fa-shield-alt"></i> OFFICIAL WARRANTY INCLUDED</span>
        <span class="ribbon-item"><i class="fas fa-sun"></i> 99% HEAT & UV REJECTION</span>
        <span class="ribbon-item"><i class="fas fa-bolt"></i> BOOK NOW — 30% OFF CERAMIC TINTS</span>
        <span class="ribbon-item"><i class="fas fa-map-marker-alt"></i> DAVAO · TAGUM · DIGOS · PANABO · CARMEN</span>
    </div>
</div>

<section id="packages" class="section" style="background:var(--smoke);">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6">
                <div class="section-eyebrow">Pricing</div>
                <h2 class="section-title">Our Tinting <em>Packages</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle">Choose from our range of professional window tinting solutions</p>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-3 col-md-6">
                <div class="offer-card">
                    <div class="offer-badge">BUDGET</div>
                    <div class="offer-header">
                        <h4>HD Carbon Ceramic Tint – Black Series</h4>
                        <div class="offer-price">₱4,000</div>
                        <div class="original-price"><del>₱6,500</del></div>
                        <span class="offer-savings">Save 38%</span>
                    </div>
                    <div class="offer-body">
                        <h5>Sedan / Hatchback / Pick Up</h5>
                        <ul class="offer-features">
                            <li><i class="fas fa-check-circle"></i> HD Carbon Ceramic Film</li>
                            <li><i class="fas fa-check-circle"></i> High Heat Rejection</li>
                            <li><i class="fas fa-check-circle"></i> 99% UV Protection</li>
                            <li><i class="fas fa-check-circle"></i> Black Series Finish</li>
                            <li><i class="fas fa-check-circle"></i> Official Warranty</li>
                        </ul>
                        <div class="mt-auto pt-3" style="border-top:1px solid var(--smoke);">
                            <a href="customer/book.php?package=hd-carbon&type=sedan" class="btn-primary-custom w-100 justify-content-center" style="border-radius:var(--radius-md);">
                                <i class="fas fa-shopping-cart"></i> Choose Package
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="col-lg-3 col-md-6">
                <div class="offer-card">
                    <div class="offer-badge">STYLISH</div>
                    <div class="offer-header">
                        <h4>Magic Chrome Ceramic Tint</h4>
                        <div class="offer-price">₱4,500</div>
                        <div class="original-price"><del>₱7,000</del></div>
                        <span class="offer-savings">Save 36%</span>
                    </div>
                    <div class="offer-body">
                        <h5>Sedan / Hatchback / Pick Up</h5>
                        <ul class="offer-features">
                            <li><i class="fas fa-check-circle"></i> Chrome Ceramic Film</li>
                            <li><i class="fas fa-check-circle"></i> Mirror Chrome Effect</li>
                            <li><i class="fas fa-check-circle"></i> High Heat Rejection</li>
                            <li><i class="fas fa-check-circle"></i> UV Protection</li>
                            <li><i class="fas fa-check-circle"></i> Official Warranty</li>
                        </ul>
                        <div class="mt-auto pt-3" style="border-top:1px solid var(--smoke);">
                            <a href="customer/book.php?package=magic-chrome&type=sedan" class="btn-primary-custom w-100 justify-content-center" style="border-radius:var(--radius-md);">
                                <i class="fas fa-shopping-cart"></i> Choose Package
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="col-lg-3 col-md-6">
                <div class="offer-card offer-featured">
                    <div class="offer-badge">BEST VALUE</div>
                    <div class="offer-header">
                        <h4>Nano Ceramic Tint – Black Series</h4>
                        <div class="offer-price">₱6,000</div>
                        <div class="original-price"><del>₱9,000</del></div>
                        <span class="offer-savings">Save 33%</span>
                    </div>
                    <div class="offer-body">
                        <h5>Sedan / Hatchback / Pick Up</h5>
                        <ul class="offer-features">
                            <li><i class="fas fa-check-circle"></i> Nano Ceramic Technology</li>
                            <li><i class="fas fa-check-circle"></i> All Medium Mixed Darkness</li>
                            <li><i class="fas fa-check-circle"></i> 99% UV Protection</li>
                            <li><i class="fas fa-check-circle"></i> Official Warranty</li>
                            <li><i class="fas fa-check-circle"></i> FREE Windshield Tint</li>
                        </ul>
                        <div class="mt-auto pt-3" style="border-top:1px solid var(--smoke);">
                            <a href="customer/book.php?package=nano-black&type=sedan" class="btn-primary-custom w-100 justify-content-center" style="border-radius:var(--radius-md);">
                                <i class="fas fa-star"></i> Choose Package
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="col-lg-3 col-md-6">
                <div class="offer-card">
                    <div class="offer-badge">PREMIUM</div>
                    <div class="offer-header">
                        <h4>Nano Ceramic Tint – Clear Bluish</h4>
                        <div class="offer-price">₱6,000</div>
                        <div class="original-price"><del>₱9,500</del></div>
                        <span class="offer-savings">Save 37%</span>
                    </div>
                    <div class="offer-body">
                        <h5>Sedan / Hatchback / Pick Up</h5>
                        <ul class="offer-features">
                            <li><i class="fas fa-check-circle"></i> Nano Ceramic Film</li>
                            <li><i class="fas fa-check-circle"></i> Clear Bluish Tone</li>
                            <li><i class="fas fa-check-circle"></i> High Transparency</li>
                            <li><i class="fas fa-check-circle"></i> Heat & UV Rejection</li>
                            <li><i class="fas fa-check-circle"></i> Official Warranty</li>
                        </ul>
                        <div class="mt-auto pt-3" style="border-top:1px solid var(--smoke);">
                            <a href="customer/book.php?package=nano-clear&type=sedan" class="btn-primary-custom w-100 justify-content-center" style="border-radius:var(--radius-md);">
                                <i class="fas fa-shopping-cart"></i> Choose Package
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" style="background:var(--white);">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6">
                <div class="section-eyebrow">All Vehicles</div>
                <h2 class="section-title">Complete Pricing <em>Table</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle">Prices for all package types across every vehicle category</p>
            </div>
        </div>

        <div class="pricing-table-wrap">
            <div class="pricing-table-head">
                <i class="fas fa-list-alt"></i>
                <h4>TINTING PACKAGES — FULL PRICE LIST</h4>
            </div>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Package</th>
                            <th>Sedan / Hatchback / Pick Up</th>
                            <th>SUV</th>
                            <th>Van</th>
                            <th>Book</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>HD Carbon Ceramic Tint</strong><br>
                                <small style="color:var(--text-muted);">Official Warranty · Black Series</small>
                            </td>
                            <td><strong>₱4,000</strong></td>
                            <td><strong>₱5,000</strong></td>
                            <td><strong>₱6,000</strong></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="customer/book.php?package=hd-carbon&type=sedan" class="book-btn book-btn-outline"><i class="fas fa-car"></i> Sedan</a>
                                    <a href="customer/book.php?package=hd-carbon&type=suv" class="book-btn book-btn-outline"><i class="fas fa-truck-pickup"></i> SUV</a>
                                    <a href="customer/book.php?package=hd-carbon&type=van" class="book-btn book-btn-outline"><i class="fas fa-shuttle-van"></i> Van</a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Magic Chrome Ceramic Tint</strong><br>
                                <small style="color:var(--text-muted);">Official Warranty · Mirror Effect</small>
                            </td>
                            <td><strong>₱4,500</strong></td>
                            <td><strong>₱5,500</strong></td>
                            <td><strong>₱6,500</strong></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="customer/book.php?package=magic-chrome&type=sedan" class="book-btn book-btn-outline"><i class="fas fa-car"></i> Sedan</a>
                                    <a href="customer/book.php?package=magic-chrome&type=suv" class="book-btn book-btn-outline"><i class="fas fa-truck-pickup"></i> SUV</a>
                                    <a href="customer/book.php?package=magic-chrome&type=van" class="book-btn book-btn-outline"><i class="fas fa-shuttle-van"></i> Van</a>
                                </div>
                            </td>
                        </tr>
                        <tr class="popular-row">
                            <td>
                                <strong>Nano Ceramic – Black Series</strong>
                                <span class="ms-2" style="background:var(--gold);color:var(--ink);padding:.15rem .6rem;border-radius:50px;font-size:.72rem;font-weight:700;">POPULAR</span><br>
                                <small style="color:var(--text-muted);">Official Warranty · FREE Windshield Tint</small>
                            </td>
                            <td><strong style="color:var(--gold);">₱6,000</strong></td>
                            <td><strong style="color:var(--gold);">₱7,000</strong></td>
                            <td><strong style="color:var(--gold);">₱8,000</strong></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="customer/book.php?package=nano-black&type=sedan" class="book-btn book-btn-gold"><i class="fas fa-star"></i> Sedan</a>
                                    <a href="customer/book.php?package=nano-black&type=suv" class="book-btn book-btn-gold"><i class="fas fa-star"></i> SUV</a>
                                    <a href="customer/book.php?package=nano-black&type=van" class="book-btn book-btn-gold"><i class="fas fa-star"></i> Van</a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Nano Ceramic – Clear Bluish</strong><br>
                                <small style="color:var(--text-muted);">Official Warranty · High Transparency</small>
                            </td>
                            <td><strong>₱6,000</strong></td>
                            <td><strong>₱7,000</strong></td>
                            <td><strong>₱8,000</strong></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="customer/book.php?package=nano-clear&type=sedan" class="book-btn book-btn-outline"><i class="fas fa-car"></i> Sedan</a>
                                    <a href="customer/book.php?package=nano-clear&type=suv" class="book-btn book-btn-outline"><i class="fas fa-truck-pickup"></i> SUV</a>
                                    <a href="customer/book.php?package=nano-clear&type=van" class="book-btn book-btn-outline"><i class="fas fa-shuttle-van"></i> Van</a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        
        <div class="row g-4 mt-5">
            <div class="col-lg-6">
                <div style="background:var(--ink);border-radius:var(--radius-lg);padding:2rem;border:1px solid rgba(212,160,23,.2);">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:48px;height:48px;background:var(--gold);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;color:var(--ink);font-size:1.2rem;flex-shrink:0;"><i class="fas fa-car"></i></div>
                        <div>
                            <h5 class="text-white mb-0">Windshield Tinting</h5>
                            <small style="color:var(--gold-light);">70% VLT — LTO Compliant</small>
                        </div>
                    </div>
                    <p style="color:rgba(255,255,255,.5);font-size:.86rem;margin-bottom:1.2rem;">Clear ceramic tint for windshield protection. Reduces glare while maintaining full road visibility.</p>
                    <div class="d-flex align-items-center justify-content-between">
                        <span style="font-family:var(--font-display);font-size:2.2rem;color:var(--gold-light);letter-spacing:.04em;">₱3,000</span>
                        <a href="customer/book.php?service=windshield" class="btn-primary-custom" style="padding:.6rem 1.4rem;font-size:.85rem;">
                            <i class="fas fa-calendar-check"></i> Book
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div style="background:var(--ink);border-radius:var(--radius-lg);padding:2rem;border:1px solid rgba(212,160,23,.2);">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:48px;height:48px;background:var(--gold);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;color:var(--ink);font-size:1.2rem;flex-shrink:0;"><i class="fas fa-sun"></i></div>
                        <div>
                            <h5 class="text-white mb-0">Sunroof / Moonroof Tinting</h5>
                            <small style="color:var(--gold-light);">Heat-Resistant Film · Custom Fit</small>
                        </div>
                    </div>
                    <p style="color:rgba(255,255,255,.5);font-size:.86rem;margin-bottom:1.2rem;">Specialized tint for panoramic and moonroof panels. Reduces overhead heat dramatically.</p>
                    <div class="d-flex align-items-center justify-content-between">
                        <span style="font-family:var(--font-display);font-size:2.2rem;color:var(--gold-light);letter-spacing:.04em;">₱2,000</span>
                        <a href="customer/book.php?service=sunroof" class="btn-primary-custom" style="padding:.6rem 1.4rem;font-size:.85rem;">
                            <i class="fas fa-calendar-check"></i> Book
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" style="background:var(--smoke);">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6">
                <div class="section-eyebrow">By Vehicle</div>
                <h2 class="section-title">Vehicle-Specific <em>Services</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle">Tailored tinting for every type of vehicle in Davao Region</p>
            </div>
        </div>

        <div class="row g-4">
            <?php
            $car_data = [
                ['label' => 'Sedans', 'from' => '₱4,000', 'features' => ['All Windows + Rear', '3 Tint Options', 'Official Warranty'], 'type' => 'sedan', 'img' => 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=800&q=80'],
                ['label' => 'SUVs / MPVs', 'from' => '₱5,000', 'features' => ['7+ Windows', 'Extra Dark Options', 'Free Sunroof Tint'], 'type' => 'suv', 'img' => 'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?auto=format&fit=crop&w=800&q=80'],
                ['label' => 'Pickup Trucks', 'from' => '₱4,500', 'features' => ['Cab + Rear Windows', 'Heavy-Duty Film', 'Anti-Scratch Coating'], 'type' => 'pickup', 'img' => 'https://ultimatewindowtinting.com/wp-content/uploads/2024/03/2.28.24-UWT-Blog-Window-Tint-for-Trucks-1.jpg'],
                ['label' => 'Vans / AUVs', 'from' => '₱6,000', 'features' => ['8+ Windows', 'Privacy Enhancement', 'Child-Safe Film'], 'type' => 'van', 'img' => 'https://lirp.cdn-website.com/a4bbb161/dms3rep/multi/opt/94423781_1071999943200084_6709058826991042560_n-1920w.jpg'],
            ];
            foreach ($car_data as $car): ?>
            <div class="col-md-6 col-lg-3">
                <div class="car-type-offer">
                    <div class="car-image" style="background-image:url('<?= $car['img'] ?>');">
                        <div class="car-overlay">
                            <h5><?= $car['label'] ?></h5>
                            <small>From <?= $car['from'] ?></small>
                        </div>
                    </div>
                    <div class="p-3">
                        <h6 style="font-size:.92rem;margin-bottom:.6rem;"><?= $car['label'] ?> Tinting</h6>
                        <ul class="list-unstyled small mb-3" style="color:var(--text-dim);">
                            <?php foreach ($car['features'] as $f): ?>
                            <li class="mb-1"><i class="fas fa-check text-success me-2"></i><?= $f ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="customer/book.php?vehicle_type=<?= $car['type'] ?>" class="btn btn-outline-dark btn-sm w-100" style="border-radius:50px;font-weight:600;font-size:.8rem;">
                            View <?= $car['label'] ?> Offers
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" style="background:var(--white);">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6">
                <div class="section-eyebrow">Film Technology</div>
                <h2 class="section-title">Compare Tint <em>Technologies</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle">Choose the right film technology for your climate and budget</p>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-md-6 col-lg-3">
                <div class="tint-compare-card">
                    <div class="tint-compare-header" style="background:linear-gradient(135deg,var(--ink) 0%,var(--ink-soft) 100%);">
                        <div class="tint-icon-wrap" style="background:linear-gradient(135deg,#444,#666);"><i class="fas fa-car"></i></div>
                        <h5 style="color:var(--white);margin-bottom:.25rem;">Carbon Tint</h5>
                        <span style="font-family:var(--font-display);font-size:2rem;color:var(--gold-light);letter-spacing:.04em;">₱4,000+</span><br>
                        <small style="color:rgba(255,255,255,.4);">3-Year Warranty</small>
                    </div>
                    <div class="tint-compare-body">
                        <div class="heat-bar-wrap">
                            <div class="heat-bar-label"><span>Heat Rejection</span><span>70%</span></div>
                            <div class="heat-bar-track"><div class="heat-bar-fill" style="width:70%"></div></div>
                        </div>
                        <div class="heat-bar-wrap">
                            <div class="heat-bar-label"><span>UV Protection</span><span>95%</span></div>
                            <div class="heat-bar-track"><div class="heat-bar-fill" style="width:95%"></div></div>
                        </div>
                        <div class="heat-bar-wrap">
                            <div class="heat-bar-label"><span>Clarity</span><span>80%</span></div>
                            <div class="heat-bar-track"><div class="heat-bar-fill" style="width:80%"></div></div>
                        </div>
                        <ul class="offer-features mt-3">
                            <li><i class="fas fa-check-circle"></i>No Signal Interference</li>
                            <li><i class="fas fa-check-circle"></i>Non-Reflective</li>
                            <li><i class="fas fa-check-circle"></i>Good Privacy</li>
                        </ul>
                        <a href="customer/book.php?tint=carbon" class="btn-primary-custom w-100 justify-content-center mt-3" style="border-radius:var(--radius-md);">
                            Choose Carbon
                        </a>
                    </div>
                </div>
            </div>

            
            <div class="col-md-6 col-lg-3">
                <div class="tint-compare-card">
                    <div class="tint-compare-header" style="background:linear-gradient(135deg,#1a2a1a 0%,#2e4a2e 100%);">
                        <div class="tint-icon-wrap" style="background:linear-gradient(135deg,#2ecc71,#27ae60);"><i class="fas fa-layer-group"></i></div>
                        <h5 style="color:var(--white);margin-bottom:.25rem;">Hybrid Ceramic-Carbon</h5>
                        <span style="font-family:var(--font-display);font-size:2rem;color:var(--gold-light);letter-spacing:.04em;">₱5,000+</span><br>
                        <small style="color:rgba(255,255,255,.4);">Official Warranty</small>
                    </div>
                    <div class="tint-compare-body">
                        <div class="heat-bar-wrap">
                            <div class="heat-bar-label"><span>Heat Rejection</span><span>90%</span></div>
                            <div class="heat-bar-track"><div class="heat-bar-fill" style="width:90%"></div></div>
                        </div>
                        <div class="heat-bar-wrap">
                            <div class="heat-bar-label"><span>UV Protection</span><span>98%</span></div>
                            <div class="heat-bar-track"><div class="heat-bar-fill" style="width:98%"></div></div>
                        </div>
                        <div class="heat-bar-wrap">
                            <div class="heat-bar-label"><span>Clarity</span><span>88%</span></div>
                            <div class="heat-bar-track"><div class="heat-bar-fill" style="width:88%"></div></div>
                        </div>
                        <ul class="offer-features mt-3">
                            <li><i class="fas fa-check-circle"></i>Best Value Option</li>
                            <li><i class="fas fa-check-circle"></i>Balanced Performance</li>
                            <li><i class="fas fa-check-circle"></i>Signal Friendly</li>
                        </ul>
                        <a href="customer/book.php?tint=hybrid" class="btn-primary-custom w-100 justify-content-center mt-3" style="border-radius:var(--radius-md);">
                            Choose Hybrid
                        </a>
                    </div>
                </div>
            </div>

            
            <div class="col-md-6 col-lg-3">
                <div class="tint-compare-card featured">
                    <div class="offer-badge">MOST POPULAR</div>
                    <div class="tint-compare-header" style="background:linear-gradient(135deg,var(--ink) 0%,#2a1a00 100%);">
                        <div class="tint-icon-wrap" style="background:linear-gradient(135deg,var(--gold),var(--gold-light));"><i class="fas fa-gem"></i></div>
                        <h5 style="color:var(--white);margin-bottom:.25rem;">Ceramic Tint</h5>
                        <span style="font-family:var(--font-display);font-size:2rem;color:var(--gold-light);letter-spacing:.04em;">₱6,000+</span><br>
                        <small style="color:rgba(255,255,255,.4);">Official Warranty</small>
                    </div>
                    <div class="tint-compare-body">
                        <div class="heat-bar-wrap">
                            <div class="heat-bar-label"><span>Heat Rejection</span><span>95%</span></div>
                            <div class="heat-bar-track"><div class="heat-bar-fill" style="width:95%"></div></div>
                        </div>
                        <div class="heat-bar-wrap">
                            <div class="heat-bar-label"><span>UV Protection</span><span>99%</span></div>
                            <div class="heat-bar-track"><div class="heat-bar-fill" style="width:99%"></div></div>
                        </div>
                        <div class="heat-bar-wrap">
                            <div class="heat-bar-label"><span>Clarity</span><span>95%</span></div>
                            <div class="heat-bar-track"><div class="heat-bar-fill" style="width:95%"></div></div>
                        </div>
                        <ul class="offer-features mt-3">
                            <li><i class="fas fa-check-circle"></i>Premium Performance</li>
                            <li><i class="fas fa-check-circle"></i>No Interference</li>
                            <li><i class="fas fa-check-circle"></i>Anti-Scratch Coating</li>
                        </ul>
                        <a href="customer/book.php?tint=ceramic" class="btn-primary-custom w-100 justify-content-center mt-3" style="border-radius:var(--radius-md);">
                            Choose Ceramic
                        </a>
                    </div>
                </div>
            </div>

            
            <div class="col-md-6 col-lg-3">
                <div class="tint-compare-card">
                    <div class="tint-compare-header" style="background:linear-gradient(135deg,#0a0a2a 0%,#1a1a4a 100%);">
                        <div class="tint-icon-wrap" style="background:linear-gradient(135deg,#3498db,#2980b9);"><i class="fas fa-crown"></i></div>
                        <h5 style="color:var(--white);margin-bottom:.25rem;">Nano Ceramic</h5>
                        <span style="font-family:var(--font-display);font-size:2rem;color:var(--gold-light);letter-spacing:.04em;">₱8,000+</span><br>
                        <small style="color:rgba(255,255,255,.4);">Official Warranty</small>
                    </div>
                    <div class="tint-compare-body">
                        <div class="heat-bar-wrap">
                            <div class="heat-bar-label"><span>Heat Rejection</span><span>99%</span></div>
                            <div class="heat-bar-track"><div class="heat-bar-fill" style="width:99%"></div></div>
                        </div>
                        <div class="heat-bar-wrap">
                            <div class="heat-bar-label"><span>UV Protection</span><span>99%</span></div>
                            <div class="heat-bar-track"><div class="heat-bar-fill" style="width:99%"></div></div>
                        </div>
                        <div class="heat-bar-wrap">
                            <div class="heat-bar-label"><span>Clarity</span><span>99%</span></div>
                            <div class="heat-bar-track"><div class="heat-bar-fill" style="width:99%"></div></div>
                        </div>
                        <ul class="offer-features mt-3">
                            <li><i class="fas fa-check-circle"></i>Highest Technology</li>
                            <li><i class="fas fa-check-circle"></i>Maximum Protection</li>
                            <li><i class="fas fa-check-circle"></i>US-Made Film</li>
                        </ul>
                        <a href="customer/book.php?tint=nano" class="btn-primary-custom w-100 justify-content-center mt-3" style="border-radius:var(--radius-md);">
                            Choose Nano
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" style="background:var(--smoke);">
    <div class="container">
        <div class="row align-items-end mb-2">
            <div class="col-lg-6">
                <div class="section-eyebrow">Why Tint?</div>
                <h2 class="section-title">Benefits of Window <em>Tinting</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle">Why thousands of Davao car owners trust our tinting services</p>
            </div>
        </div>

        <div class="benefit-grid">
            <div class="benefit-cell">
                <div class="benefit-icon"><i class="fas fa-thermometer-half"></i></div>
                <h5>Heat Reduction</h5>
                <p>Block up to 99% of infrared heat — up to 20°C cooler inside your car</p>
            </div>
            <div class="benefit-cell">
                <div class="benefit-icon"><i class="fas fa-shield-alt"></i></div>
                <h5>UV Protection</h5>
                <p>99% UV ray blockage — protect skin and prevent interior fading</p>
            </div>
            <div class="benefit-cell">
                <div class="benefit-icon"><i class="fas fa-eye-slash"></i></div>
                <h5>Glare Reduction</h5>
                <p>Reduce dangerous glare for safer and more comfortable driving</p>
            </div>
            <div class="benefit-cell">
                <div class="benefit-icon"><i class="fas fa-lock"></i></div>
                <h5>Enhanced Privacy</h5>
                <p>Keep your belongings safe with increased window privacy</p>
            </div>
            <div class="benefit-cell">
                <div class="benefit-icon"><i class="fas fa-snowflake"></i></div>
                <h5>AC Efficiency</h5>
                <p>Cooler interior means less AC use and better fuel economy</p>
            </div>
            <div class="benefit-cell">
                <div class="benefit-icon"><i class="fas fa-layer-group"></i></div>
                <h5>Shatter Protection</h5>
                <p>Film holds broken glass together during accidents for added safety</p>
            </div>
        </div>
    </div>
</section>

<section class="section section-dark">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6">
                <div class="section-eyebrow" style="color:rgba(255,255,255,.4);">How It Works</div>
                <h2 class="section-title section-title-light">Our Installation <em>Process</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle section-subtitle-light">Professional tinting completed in just a few simple steps</p>
            </div>
        </div>

        <div class="process-grid">
            <div class="process-step">
                <div class="step-num">01</div>
                <h5>Consultation</h5>
                <p>Discuss your needs, vehicle type, and choose the perfect tint package for you</p>
            </div>
            <div class="process-step">
                <div class="step-num">02</div>
                <h5>Preparation</h5>
                <p>Thorough window cleaning and preparation for flawless tint application</p>
            </div>
            <div class="process-step">
                <div class="step-num">03</div>
                <h5>Installation</h5>
                <p>Precise application by our certified professional tinting technicians</p>
            </div>
            <div class="process-step">
                <div class="step-num">04</div>
                <h5>Quality Check</h5>
                <p>Final inspection, warranty registration, and care instructions</p>
            </div>
        </div>

        
        <div class="row g-4 mt-5">
            <div class="col-md-4">
                <div class="capacity-card">
                    <div class="capacity-icon"><i class="fas fa-sun fa-lg" style="color:var(--ink);"></i></div>
                    <div class="capacity-number">1–3</div>
                    <div class="capacity-label">Cars Per Day</div>
                    <div class="capacity-unit">Daily Capacity</div>
                    <p class="mt-2 small" style="color:rgba(255,255,255,.4);">We professionally tint 1–3 vehicles daily with precision and care</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="capacity-card">
                    <div class="capacity-icon"><i class="fas fa-calendar-week fa-lg" style="color:var(--ink);"></i></div>
                    <div class="capacity-number">5–7</div>
                    <div class="capacity-label">Cars Per Week</div>
                    <div class="capacity-unit">Weekly Capacity</div>
                    <p class="mt-2 small" style="color:rgba(255,255,255,.4);">Consistently serving 5–7 vehicles weekly across Davao Region</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="capacity-card">
                    <div class="capacity-icon"><i class="fas fa-calendar-alt fa-lg" style="color:var(--ink);"></i></div>
                    <div class="capacity-number">20+</div>
                    <div class="capacity-label">Cars Per Month</div>
                    <div class="capacity-unit">Monthly Capacity</div>
                    <p class="mt-2 small" style="color:rgba(255,255,255,.4);">Successfully tinting 20–28 vehicles monthly with official warranty</p>
                </div>
            </div>
        </div>

        
        <div class="row mt-5">
            <div class="col-12">
                <div style="background:rgba(212,160,23,.08);border:1px solid rgba(212,160,23,.2);border-radius:var(--radius-lg);padding:2rem 2.5rem;">
                    <div class="row align-items-center g-3">
                        <div class="col-md-8">
                            <h4 style="color:var(--gold-light);font-weight:700;margin-bottom:.4rem;font-family:var(--font-display);letter-spacing:.04em;">
                                <i class="fas fa-motorcycle me-2"></i>MOBILE SERVICE IN DAVAO REGION
                            </h4>
                            <p style="color:rgba(255,255,255,.55);margin:0;font-size:.9rem;">We come to your location! Serving Davao, Tagum, Digos, Panabo, Carmen and surrounding areas.</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="customer/book.php" class="btn-primary-custom" style="display:inline-flex;">
                                <i class="fas fa-calendar-check"></i> Book Mobile Service
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" style="background:var(--white);">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6">
                <div class="section-eyebrow">Philippines Law</div>
                <h2 class="section-title">Legal Tint <em>Requirements</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle">We ensure 100% LTO compliance on all our tinting installations</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div style="background:var(--ink);border-radius:var(--radius-lg);padding:2rem;text-align:center;border:1.5px solid rgba(212,160,23,.2);">
                    <div style="width:60px;height:60px;background:var(--gold);border-radius:50%;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-car fa-lg" style="color:var(--ink);"></i>
                    </div>
                    <h5 style="color:var(--white);margin-bottom:.5rem;">Front Windshield</h5>
                    <div style="font-family:var(--font-display);font-size:3.5rem;color:var(--gold-light);line-height:1;letter-spacing:.04em;">70%</div>
                    <small style="color:rgba(255,255,255,.4);">Minimum VLT (Visible Light Transmission)</small>
                    <p style="color:rgba(255,255,255,.5);font-size:.82rem;margin-top:.8rem;margin-bottom:0;">Maximum 30% darkness allowed by LTO</p>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background:var(--ink);border-radius:var(--radius-lg);padding:2rem;text-align:center;border:1.5px solid rgba(212,160,23,.2);">
                    <div style="width:60px;height:60px;background:var(--gold);border-radius:50%;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-window-restore fa-lg" style="color:var(--ink);"></i>
                    </div>
                    <h5 style="color:var(--white);margin-bottom:.5rem;">Front Side Windows</h5>
                    <div style="font-family:var(--font-display);font-size:3.5rem;color:var(--gold-light);line-height:1;letter-spacing:.04em;">50%</div>
                    <small style="color:rgba(255,255,255,.4);">Minimum VLT Required</small>
                    <p style="color:rgba(255,255,255,.5);font-size:.82rem;margin-top:.8rem;margin-bottom:0;">Maximum 50% darkness on front side glass</p>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background:var(--ink);border-radius:var(--radius-lg);padding:2rem;text-align:center;border:1.5px solid rgba(212,160,23,.35);">
                    <div style="width:60px;height:60px;background:var(--gold);border-radius:50%;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-eye-slash fa-lg" style="color:var(--ink);"></i>
                    </div>
                    <h5 style="color:var(--white);margin-bottom:.5rem;">Rear Windows</h5>
                    <div style="font-family:var(--font-display);font-size:3.5rem;color:var(--gold-light);line-height:1;letter-spacing:.04em;">ANY</div>
                    <small style="color:rgba(255,255,255,.4);">Darkness Level Permitted</small>
                    <p style="color:rgba(255,255,255,.5);font-size:.82rem;margin-top:.8rem;margin-bottom:0;">Full darkness allowed on rear and rear side glass</p>
                </div>
            </div>
        </div>

        <div class="mt-4 text-center">
            <div style="background:var(--gold-pale);border:1.5px solid rgba(212,160,23,.35);border-radius:var(--radius-md);padding:1.2rem 2rem;display:inline-block;">
                <i class="fas fa-shield-check me-2" style="color:var(--gold);"></i>
                <strong>All our tinting installations are 100% LTO compliant.</strong> We will never install tinting that violates Philippine regulations.
            </div>
        </div>
    </div>
</section>

<section class="featured-customer-section">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6">
                <div class="section-eyebrow" style="color:rgba(255,255,255,.4);">Real Results</div>
                <h2 class="section-title section-title-light">Featured Customer <em>Showcase</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle section-subtitle-light">Real cars, real results from RG Cartint Works Davao. Watch the transformation.</p>
            </div>
        </div>

        <div class="customer-showcase">
            <div class="video-grid">
                <div class="video-item">
                    <div class="video-container">
                        <iframe src="https://www.facebook.com/plugins/video.php?height=476&href=https%3A%2F%2Fwww.facebook.com%2Freel%2F863326256665419%2F&show_text=false&width=267&t=0&autoplay=1&mute=1"
                                scrolling="no" frameborder="0" allowfullscreen="true"
                                allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe>
                    </div>
                    <div class="video-caption">
                        <h4><i class="fas fa-crown me-1"></i>Toyota Innova</h4>
                        <p><i class="fas fa-circle"></i> Front: Chameleon Nano Ceramic</p>
                        <p><i class="fas fa-circle"></i> Sides: Superblack Ceramic</p>
                    </div>
                </div>
                <div class="video-item">
                    <div class="video-container">
                        <iframe src="https://www.facebook.com/plugins/video.php?height=476&href=https%3A%2F%2Fwww.facebook.com%2Freel%2F4029576464001158%2F&show_text=false&width=267&t=0&autoplay=1&mute=1"
                                scrolling="no" frameborder="0" allowfullscreen="true"
                                allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe>
                    </div>
                    <div class="video-caption">
                        <h4><i class="fas fa-crown me-1"></i>Suzuki Ertiga 2019</h4>
                        <p><i class="fas fa-tint"></i> Crown Optics HD Nano Ceramic</p>
                        <p><i class="fas fa-check-circle"></i> 99% Heat · 12-Year Warranty · USA</p>
                    </div>
                </div>
                <div class="video-item">
                    <div class="video-container">
                        <iframe src="https://www.facebook.com/plugins/video.php?height=476&href=https%3A%2F%2Fwww.facebook.com%2Freel%2F2063298521119888%2F&show_text=false&width=267&t=0&autoplay=1&mute=1"
                                scrolling="no" frameborder="0" allowfullscreen="true"
                                allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe>
                    </div>
                    <div class="video-caption">
                        <h4><i class="fas fa-crown me-1"></i>2 Units Done</h4>
                        <p><i class="fas fa-car"></i> Land Cruiser 2025</p>
                        <p><i class="fas fa-car"></i> DA17 Minivan Transformer</p>
                    </div>
                </div>
                <div class="video-item">
                    <div class="video-container">
                        <iframe src="https://www.facebook.com/plugins/video.php?height=476&href=https%3A%2F%2Fwww.facebook.com%2Freel%2F1225022136396370%2F&show_text=false&width=267&t=0&autoplay=1&mute=1"
                                scrolling="no" frameborder="0" allowfullscreen="true"
                                allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe>
                    </div>
                    <div class="video-caption">
                        <h4><i class="fas fa-crown me-1"></i>Suzuki Dzire 2026</h4>
                        <p><i class="fas fa-tint"></i> Crown Optics HD Nano Ceramic</p>
                        <p><i class="fas fa-check-circle"></i> 99% Heat · 99% UV · Made in USA</p>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5 pt-3">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div style="background:rgba(212,160,23,.08);border:1px solid rgba(212,160,23,.25);border-radius:var(--radius-lg);padding:2rem;">
                            <p style="color:var(--gold-light);font-weight:700;margin-bottom:.5rem;letter-spacing:.04em;">✨ Thank you for choosing RG Cartint Works Davao!</p>
                            <h4 class="text-white mb-3">UPGRADE your tint now! Book now</h4>
                            <div class="d-flex gap-3 justify-content-center flex-wrap mt-3">
                                <a href="customer/book.php" class="btn-primary-custom">
                                    <i class="fas fa-calendar-check"></i> Book Now
                                </a>
                                <a href="#offers" class="btn-secondary-custom">
                                    <i class="fas fa-images"></i> View Packages
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h2>READY TO UPGRADE YOUR TINT?</h2>
                <p class="mb-0 mt-2" style="color:rgba(10,10,10,.65);">Professional installation · Official warranty · Mobile service available</p>
                <div class="row g-4 mt-2">
                    <div class="col-4 text-center">
                        <i class="fas fa-clock fa-2x mb-2" style="color:var(--ink);opacity:.7;"></i>
                        <div style="font-size:.85rem;font-weight:600;color:var(--ink);">3-Hour Service</div>
                    </div>
                    <div class="col-4 text-center">
                        <i class="fas fa-award fa-2x mb-2" style="color:var(--ink);opacity:.7;"></i>
                        <div style="font-size:.85rem;font-weight:600;color:var(--ink);">Official Warranty</div>
                    </div>
                    <div class="col-4 text-center">
                        <i class="fas fa-home fa-2x mb-2" style="color:var(--ink);opacity:.7;"></i>
                        <div style="font-size:.85rem;font-weight:600;color:var(--ink);">Mobile Service</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 text-lg-end d-flex gap-3 flex-wrap justify-content-lg-end">
                <a href="customer/book.php" class="btn-cta-dark"><i class="fas fa-calendar-check"></i> Book Appointment Now</a>
                <a href="tel:+09970628687" class="btn-cta-outline"><i class="fas fa-phone"></i> Call: +0997 062 8687</a>
            </div>
        </div>
    </div>
</section>

<footer class="bg-dark text-white py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h4 class="fw-bold mb-3 text-warning" style="font-family:var(--font-display);letter-spacing:.06em;font-size:1.7rem;">RG CARTINTWORKS</h4>
                <p class="mb-4" style="color:rgba(255,255,255,.5);font-size:.88rem;line-height:1.75;">
                    Davao's premier car window tinting specialist since 2008. Professional installation
                    with official warranty using only authentic films from global brands.
                </p>
                <div class="d-flex gap-3">
                    <a href="https://www.facebook.com/profile.php?id=61574013049501" target="_blank" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <h5 style="font-size:.75rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-bottom:.9rem;">Services</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="#" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Car Window Tinting</a></li>
                    <li class="mb-2"><a href="#" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">SUV Tinting</a></li>
                    <li class="mb-2"><a href="#" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Tint Removal</a></li>
                    <li class="mb-2"><a href="#" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Tint Repair</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6">
                <h5 style="font-size:.75rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-bottom:.9rem;">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="index.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Home</a></li>
                    <li class="mb-2"><a href="gallery/index.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Gallery</a></li>
                    <li class="mb-2"><a href="services.php" style="font-size:.86rem;color:rgba(255,255,255,.9);text-decoration:none;font-weight:600;">Services</a></li>
                    <li class="mb-2"><a href="customer/book.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Book Now</a></li>
                    <li class="mb-2"><a href="index.php#feedback" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Feedback</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h5 style="font-size:.75rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-bottom:.9rem;">Contact Us</h5>
                <ul class="list-unstyled">
                    <li class="mb-3 d-flex align-items-start gap-2">
                        <i class="fas fa-map-marker-alt mt-1" style="color:var(--gold);width:16px;"></i>
                        <span style="font-size:.86rem;color:rgba(255,255,255,.5);">Davao · Tagum · Digos · Panabo · Carmen<br><small style="color:rgba(255,255,255,.3);">Serving Davao Region</small></span>
                    </li>
                    <li class="mb-3 d-flex align-items-center gap-2">
                        <i class="fas fa-phone" style="color:var(--gold);width:16px;"></i>
                        <a href="tel:09970628687" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">+0997 062 8687</a>
                    </li>
                    <li class="mb-3 d-flex align-items-center gap-2">
                        <i class="fas fa-envelope" style="color:var(--gold);width:16px;"></i>
                        <a href="mailto:rminguillo12@gmail.com" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">rminguillo12@gmail.com</a>
                    </li>
                    <li class="d-flex align-items-center gap-2">
                        <i class="fas fa-clock" style="color:var(--gold);width:16px;"></i>
                        <span style="font-size:.86rem;color:rgba(255,255,255,.5);">Mon–Sat: 8AM–6PM | Sun: 9AM–4PM</span>
                    </li>
                </ul>
            </div>
        </div>
        <hr style="border-color:rgba(255,255,255,.07);margin:2rem 0 1.5rem;">
        <div class="row align-items-center">
            <div class="col-md-6"><small style="color:rgba(255,255,255,.3);">&copy; <?= date('Y') ?> RG CARTINTWORKS. All rights reserved.</small></div>
            <div class="col-md-6 text-md-end"><small style="color:rgba(255,255,255,.3);">Professional Car Window Tinting in Davao Region</small></div>
        </div>
    </div>
</footer>
<?php include __DIR__ . '/includes/chatbot.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    
    
    
    document.addEventListener('DOMContentLoaded', function() {
        const fabSecondary = document.getElementById('fabSecondary');
        const fabToggle = document.getElementById('fabToggle');
        const fabToggleIcon = document.getElementById('fabToggleIcon');
        const fabContainer = document.getElementById('floatingButtons');

        fabToggle.addEventListener('click', function() {
            fabSecondary.classList.toggle('show');
            fabToggle.classList.toggle('rotate');
            fabToggleIcon.classList.toggle('fa-plus');
            fabToggleIcon.classList.toggle('fa-times');
        });

        
        let lastScrollTop = 0;
        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            if (scrollTop > lastScrollTop && scrollTop > 100) {
                fabContainer.style.opacity = '0.7';
                fabContainer.style.transform = 'translateY(18px)';
            } else {
                fabContainer.style.opacity = '1';
                fabContainer.style.transform = 'translateY(0)';
            }
            lastScrollTop = scrollTop;

            const scrollPosition = window.scrollY + window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            fabContainer.style.display = scrollPosition >= documentHeight - 50 ? 'none' : 'flex';
        });

        
        document.querySelectorAll('.fab-button.fab-secondary').forEach(button => {
            button.addEventListener('click', function() {
                fabSecondary.classList.remove('show');
                fabToggle.classList.remove('rotate');
                fabToggleIcon.classList.remove('fa-times');
                fabToggleIcon.classList.add('fa-plus');
            });
        });

        
        if (!localStorage.getItem('fabWelcomeShown')) {
            setTimeout(() => {
                fabSecondary.classList.add('show');
                fabToggle.classList.add('rotate');
                fabToggleIcon.classList.remove('fa-plus');
                fabToggleIcon.classList.add('fa-times');
                setTimeout(() => {
                    fabSecondary.classList.remove('show');
                    fabToggle.classList.remove('rotate');
                    fabToggleIcon.classList.remove('fa-times');
                    fabToggleIcon.classList.add('fa-plus');
                }, 3000);
                localStorage.setItem('fabWelcomeShown', 'true');
            }, 2000);
        }
    });

    
    
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fadeInUp');
                
                entry.target.querySelectorAll('.heat-bar-fill').forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0%';
                    setTimeout(() => { bar.style.width = width; }, 100);
                });
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.offer-card, .tint-compare-card, .car-type-offer, .benefit-cell, .capacity-card, .process-step').forEach(el => {
        observer.observe(el);
    });

    
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                window.scrollTo({ top: targetElement.offsetTop - 100, behavior: 'smooth' });
            }
        });
    });

    
    document.querySelectorAll('.hover-warning').forEach(link => {
        link.addEventListener('mouseenter', function() { this.style.color = 'var(--gold-light)'; this.style.transform = 'translateX(4px)'; });
        link.addEventListener('mouseleave', function() { this.style.color = ''; this.style.transform = ''; });
    });
</script>

</body>
</html>

