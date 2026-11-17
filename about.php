<?php
require __DIR__ . '/includes/auth.php';
boot_session();
require __DIR__ . '/includes/db.php';
$page_title = "About RG CARTINT WORKS DAVAO | Professional Car Tinting Services";
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="Learn about RG CARTINT WORKS DAVAO - Established in 2020, providing superior vehicle protection, privacy, and professional mobile tinting services in Davao.">

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
        body { font-family: var(--font-body); background: var(--white); color: var(--text-body); overflow-x: hidden; line-height: 1.65; }
        h1, h2, h3, h4, h5, h6 { font-family: var(--font-body); font-weight: 700; }

        
        .navbar-custom {
            background: var(--ink) !important;
            border-bottom: 1px solid rgba(212,160,23,.15);
            padding: .9rem 0; transition: all .3s ease;
            position: fixed; top: 0; left: 0; right: 0; z-index: 1030;
        }
        .navbar-custom.scrolled {
            padding: .55rem 0;
            background: rgba(10,10,10,.97) !important;
            backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            border-bottom-color: rgba(212,160,23,.25);
            box-shadow: 0 4px 24px rgba(0,0,0,.35);
        }
        .navbar-custom.hide-nav { transform: translateY(-100%); }
        .navbar-brand-custom { display: flex; align-items: center; gap: .65rem; text-decoration: none; transition: transform .3s; }
        .navbar-brand-custom:hover { transform: translateY(-2px); }
        .nav-logo-icon {
            width: 36px; height: 36px;
            background: rgba(212,160,23,.12); border: 1px solid rgba(212,160,23,.3);
            border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center;
            transition: transform .3s, background .3s;
        }
        .navbar-brand-custom:hover .nav-logo-icon { background: rgba(212,160,23,.22); transform: rotate(12deg); }
        .nav-brand-text { font-family: var(--font-display); font-size: 1.45rem; letter-spacing: .06em; color: var(--gold-light); }
        .nav-brand-badge { font-size: .65rem; font-weight: 700; background: var(--gold); color: var(--ink); padding: .2rem .55rem; border-radius: 50px; letter-spacing: .06em; }
        .nav-link-custom {
            font-weight: 500; font-size: .88rem; padding: .5rem 1rem !important; margin: 0 .15rem;
            border-radius: var(--radius-sm); color: rgba(255,255,255,.7) !important;
            transition: all .25s; position: relative; letter-spacing: .01em;
        }
        .nav-link-custom::after {
            content: ''; position: absolute; bottom: 4px; left: 1rem; right: 1rem;
            height: 1.5px; background: var(--gold); transform: scaleX(0); border-radius: 2px; transition: transform .3s;
        }
        .nav-link-custom:hover { color: var(--gold-light) !important; background: rgba(212,160,23,.07); }
        .nav-link-custom:hover::after { transform: scaleX(1); }
        .nav-link-custom.active { color: var(--gold-light) !important; background: rgba(212,160,23,.1); }
        .nav-link-custom.active::after { transform: scaleX(1); }
        .navbar-toggler-custom {
            border: 1.5px solid rgba(212,160,23,.35); border-radius: var(--radius-sm); padding: .4rem .6rem;
            color: var(--gold-light); background: transparent; cursor: pointer; transition: all .25s;
        }
        .navbar-toggler-custom:hover { background: rgba(212,160,23,.12); border-color: var(--gold); }
        .nav-user-avatar {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            color: var(--ink); font-weight: 700; font-size: .82rem;
        }
        .btn-nav-book {
            background: var(--gold); color: var(--ink); border: none; padding: .5rem 1.2rem;
            border-radius: 50px; font-weight: 600; font-size: .85rem; transition: all .25s;
            text-decoration: none; display: inline-flex; align-items: center; gap: .4rem; box-shadow: var(--shadow-gold);
        }
        .btn-nav-book:hover { background: var(--gold-light); color: var(--ink); transform: translateY(-2px); }
        .btn-nav-signin {
            background: transparent; color: rgba(255,255,255,.75); border: 1.5px solid rgba(255,255,255,.2);
            padding: .5rem 1.1rem; border-radius: 50px; font-weight: 500; font-size: .85rem;
            transition: all .25s; text-decoration: none; display: inline-flex; align-items: center; gap: .4rem;
        }
        .btn-nav-signin:hover { border-color: rgba(255,255,255,.5); color: var(--white); background: rgba(255,255,255,.06); }
        @media (max-width: 992px) {
            .navbar-collapse-custom {
                background: var(--ink-soft); border-top: 1px solid rgba(212,160,23,.12);
                padding: 1rem; border-radius: 0 0 var(--radius-md) var(--radius-md); margin-top: .5rem;
            }
            .nav-link-custom { margin: .15rem 0; }
            .navbar-actions { margin-top: .75rem; padding-top: .75rem; border-top: 1px solid rgba(255,255,255,.07); }
        }

        
        .hero-section {
            background: linear-gradient(to right, rgba(10,10,10,.92) 42%, rgba(10,10,10,.55) 100%),
                        url('https://images.unsplash.com/photo-1580273916550-e323be2ae537?auto=format&fit=crop&w=1920&q=80');
            background-size: cover; background-position: center;
            min-height: 58vh; display: flex; align-items: center;
            position: relative; color: white; padding-top: 100px; overflow: hidden;
        }
        .hero-section::before {
            content: ''; position: absolute; top: 0; right: 30%; width: 1px; height: 100%;
            background: linear-gradient(to bottom, transparent 0%, rgba(212,160,23,.4) 50%, transparent 100%); pointer-events: none;
        }
        .hero-section::after {
            content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 140px;
            background: linear-gradient(transparent, var(--white)); pointer-events: none;
        }
        .hero-content { position: relative; z-index: 2; max-width: 680px; }
        .hero-badge {
            background: rgba(212,160,23,.15); border: 1px solid rgba(212,160,23,.4); color: var(--gold-light);
            padding: .55rem 1.3rem; border-radius: 50px; font-size: .8rem; font-weight: 600;
            letter-spacing: .14em; text-transform: uppercase; display: inline-flex; align-items: center; gap: .6rem; margin-bottom: 1.8rem;
        }
        .hero-badge-dot { width: 7px; height: 7px; background: var(--gold-light); border-radius: 50%; animation: blink 1.8s ease infinite; }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.25} }
        .hero-title { font-family: var(--font-display); font-size: clamp(3rem, 7vw, 6rem); line-height: .92; letter-spacing: .03em; color: var(--white); margin-bottom: 1.6rem; }
        .hero-title .gold-accent { color: var(--gold-light); display: block; font-family: var(--font-serif); font-style: italic; font-size: clamp(1.4rem, 3vw, 2.5rem); letter-spacing: .01em; line-height: 1.3; }
        .hero-subtitle { font-size: 1.05rem; color: rgba(255,255,255,.65); max-width: 480px; line-height: 1.8; margin-bottom: 2.4rem; font-weight: 300; }
        .breadcrumb-wrap {
            background: rgba(212,160,23,.12); border: 1px solid rgba(212,160,23,.25); border-radius: 50px;
            display: inline-flex; align-items: center; gap: .5rem; padding: .4rem 1.1rem;
            margin-bottom: 1.4rem; font-size: .8rem; color: rgba(255,255,255,.6);
        }
        .breadcrumb-wrap a { color: var(--gold-light); text-decoration: none; }
        .breadcrumb-wrap a:hover { color: var(--white); }
        .breadcrumb-wrap i { font-size: .65rem; }

        
        .btn-primary-custom {
            background: var(--gold); border: none; padding: .85rem 2rem; font-weight: 600; font-size: .95rem;
            border-radius: 50px; color: var(--ink); transition: all .3s; box-shadow: var(--shadow-gold);
            display: inline-flex; align-items: center; gap: .5rem; text-decoration: none; letter-spacing: .02em;
        }
        .btn-primary-custom:hover { background: var(--gold-light); color: var(--ink); transform: translateY(-3px); box-shadow: 0 12px 40px rgba(212,160,23,.45); }
        .btn-secondary-custom {
            background: transparent; border: 1.5px solid rgba(255,255,255,.3); color: rgba(255,255,255,.85);
            padding: .85rem 2rem; font-weight: 500; font-size: .95rem; border-radius: 50px; transition: all .3s;
            display: inline-flex; align-items: center; gap: .5rem; text-decoration: none;
        }
        .btn-secondary-custom:hover { border-color: rgba(255,255,255,.7); color: var(--white); background: rgba(255,255,255,.07); }

        
        .promo-ribbon { background: var(--ink); border-top: 1px solid rgba(212,160,23,.2); border-bottom: 1px solid rgba(212,160,23,.2); padding: .9rem 0; overflow: hidden; }
        .ribbon-track { display: flex; gap: 3.5rem; animation: marquee 24s linear infinite; white-space: nowrap; width: max-content; }
        .ribbon-item { display: inline-flex; align-items: center; gap: .55rem; font-family: var(--font-display); font-size: .95rem; letter-spacing: .1em; color: var(--white); }
        .ribbon-item i { color: var(--gold); }
        @keyframes marquee { from{transform:translateX(0)} to{transform:translateX(-50%)} }

        
        .section { padding: 5.5rem 0; }
        .section-dark { background: var(--ink-mid); }
        .section-smoke { background: var(--smoke); }
        .section-eyebrow { display: inline-flex; align-items: center; gap: .5rem; font-size: .72rem; font-weight: 700; letter-spacing: .18em; text-transform: uppercase; color: var(--text-dim); margin-bottom: .9rem; }
        .section-eyebrow::before { content: ''; display: inline-block; width: 22px; height: 2px; background: var(--gold); border-radius: 2px; }
        .section-title { font-family: var(--font-display); font-size: clamp(2.2rem, 4vw, 3.5rem); letter-spacing: .04em; line-height: 1; margin-bottom: .9rem; color: var(--ink); }
        .section-title-light { color: var(--white); }
        .section-title em { font-family: var(--font-serif); font-style: italic; color: var(--gold-light); }
        .section-subtitle { font-size: 1.02rem; color: var(--text-dim); max-width: 580px; font-weight: 300; line-height: 1.75; }
        .section-subtitle-light { color: rgba(255,255,255,.5); }

        
        .story-card { background: var(--white); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-md); border: 1.5px solid var(--smoke-dark); transition: all .4s cubic-bezier(.4,0,.2,1); position: relative; padding: 2.5rem; height: 100%; }
        .story-card::before { content: ''; position: absolute; top: 0; left: 0; width: 5px; height: 100%; background: linear-gradient(to bottom, var(--gold), var(--gold-light)); border-radius: var(--radius-xs) 0 0 var(--radius-xs); }
        .story-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-xl); border-color: var(--gold); }
        .year-badge { background: var(--gold); color: var(--ink); padding: .45rem 1.2rem; border-radius: 50px; font-weight: 700; font-size: .85rem; display: inline-block; margin-bottom: 1.2rem; box-shadow: var(--shadow-sm); letter-spacing: .04em; }

        
        .value-card { background: var(--white); border-radius: var(--radius-lg); padding: 2.4rem 2rem; text-align: center; height: 100%; box-shadow: var(--shadow-md); border: 1.5px solid var(--smoke-dark); transition: all .4s cubic-bezier(.4,0,.2,1); }
        .value-card:hover { transform: translateY(-10px); box-shadow: var(--shadow-xl); border-color: var(--gold); }
        .value-icon { width: 64px; height: 64px; background: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.4rem; color: var(--ink); font-size: 1.4rem; box-shadow: var(--shadow-gold); }
        .value-card h5 { font-size: .98rem; font-weight: 700; margin-bottom: .6rem; color: var(--ink); }
        .value-card p { font-size: .86rem; color: var(--text-dim); line-height: 1.65; margin: 0; }

        
        .timeline { position: relative; max-width: 1000px; margin: 0 auto; }
        .timeline::before { content: ''; position: absolute; left: 50%; top: 0; bottom: 0; width: 3px; background: linear-gradient(to bottom, var(--gold), var(--gold-light)); transform: translateX(-50%); }
        .timeline-item { margin-bottom: 3rem; position: relative; }
        .timeline-content { background: var(--white); border-radius: var(--radius-lg); padding: 2rem 2rem 2rem 2.5rem; box-shadow: var(--shadow-md); border: 1.5px solid var(--smoke-dark); width: calc(50% - 50px); position: relative; transition: all .3s; }
        .timeline-content:hover { border-color: var(--gold); box-shadow: var(--shadow-xl); }
        .timeline-item:nth-child(odd) .timeline-content { margin-left: auto; }
        .timeline-item:nth-child(even) .timeline-content { margin-right: auto; }
        .timeline-year { position: absolute; top: 50%; width: 72px; height: 72px; background: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--ink); font-weight: 700; font-size: 1rem; box-shadow: var(--shadow-gold); font-family: var(--font-display); letter-spacing: .05em; z-index: 2; }
        .timeline-item:nth-child(odd) .timeline-year { right: calc(50% - 36px); transform: translateY(-50%) translateX(50%); }
        .timeline-item:nth-child(even) .timeline-year { left: calc(50% - 36px); transform: translateY(-50%) translateX(-50%); }
        .timeline-content h4 { font-size: 1rem; font-weight: 700; color: var(--ink); margin-bottom: .4rem; }
        .timeline-content p { font-size: .86rem; color: var(--text-dim); margin: 0; line-height: 1.6; }

        
        .benefit-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5px; background: var(--smoke-dark); border: 1.5px solid var(--smoke-dark); border-radius: var(--radius-lg); overflow: hidden; margin-top: 3rem; }
        .benefit-cell { background: var(--white); padding: 2.4rem 2rem; transition: background .25s; }
        .benefit-cell:hover { background: var(--gold-pale); }
        .benefit-icon { width: 46px; height: 46px; background: var(--ink); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; color: var(--gold); font-size: 1.1rem; margin-bottom: 1.2rem; }
        .benefit-cell h5 { font-size: .98rem; font-weight: 600; margin-bottom: .4rem; color: var(--ink); }
        .benefit-cell p { font-size: .86rem; color: var(--text-dim); line-height: 1.65; margin: 0; }

        
        .stat-card { text-align: center; padding: 2.5rem 1.5rem; border: 1px solid rgba(255,255,255,.07); border-radius: var(--radius-lg); transition: border-color .3s, background .3s; }
        .stat-card:hover { border-color: rgba(212,160,23,.3); background: rgba(212,160,23,.04); }
        .stat-icon { width: 60px; height: 60px; background: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.2rem; color: var(--ink); }
        .stat-number { font-family: var(--font-display); font-size: 3.5rem; color: var(--gold-light); line-height: 1; letter-spacing: .02em; margin-bottom: .3rem; }
        .stat-label { font-size: .95rem; font-weight: 600; color: var(--white); margin-bottom: .3rem; }
        .stat-unit { font-size: .75rem; color: rgba(255,255,255,.35); letter-spacing: .1em; text-transform: uppercase; }

        
        .accordion-item { border: 1.5px solid var(--smoke-dark) !important; border-radius: var(--radius-md) !important; margin-bottom: .75rem; overflow: hidden; }
        .accordion-button { font-family: var(--font-body); font-weight: 600; font-size: .95rem; color: var(--ink); background: var(--white); padding: 1.2rem 1.5rem; }
        .accordion-button:not(.collapsed) { background: var(--gold-pale); color: var(--ink); box-shadow: none; }
        .accordion-button:focus { box-shadow: none; }
        .accordion-body { font-size: .9rem; color: var(--text-dim); line-height: 1.7; padding: 1rem 1.5rem 1.4rem; }

        
        .chatbot-embed-wrap { background: var(--white); border-radius: var(--radius-lg); border: 1.5px solid var(--smoke-dark); box-shadow: var(--shadow-md); overflow: hidden; }
        .chatbot-embed-header { background: var(--ink); padding: 1rem 1.5rem; display: flex; align-items: center; gap: .8rem; border-bottom: 1px solid rgba(212,160,23,.2); }
        .chatbot-embed-avatar { width: 42px; height: 42px; background: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: var(--ink); flex-shrink: 0; animation: botPulse 2.5s ease-in-out infinite; }
        @keyframes botPulse { 0%,100%{box-shadow:0 0 0 0 rgba(212,160,23,.4)} 50%{box-shadow:0 0 0 8px rgba(212,160,23,0)} }
        .chatbot-embed-info h6 { color: var(--gold-light); font-family: var(--font-display); letter-spacing: .06em; font-size: 1.1rem; margin: 0; }
        .chatbot-embed-info span { font-size: .75rem; color: rgba(255,255,255,.5); display: flex; align-items: center; gap: .35rem; }
        .chatbot-embed-info span::before { content: ''; width: 7px; height: 7px; background: #22c55e; border-radius: 50%; display: inline-block; animation: blink 1.8s ease infinite; }

        .chatbot-embed-messages { height: 340px; overflow-y: auto; padding: 1.2rem 1.5rem; background: var(--smoke); display: flex; flex-direction: column; gap: .8rem; scroll-behavior: smooth; }
        .chatbot-embed-messages::-webkit-scrollbar { width: 4px; }
        .chatbot-embed-messages::-webkit-scrollbar-thumb { background: var(--smoke-dark); border-radius: 4px; }

        .chat-msg { display: flex; gap: .6rem; align-items: flex-end; max-width: 85%; animation: msgSlide .3s ease-out; }
        @keyframes msgSlide { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
        .chat-msg.bot { align-self: flex-start; }
        .chat-msg.user { align-self: flex-end; flex-direction: row-reverse; }
        .chat-msg-avatar { width: 30px; height: 30px; background: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .75rem; color: var(--ink); flex-shrink: 0; }
        .chat-msg.user .chat-msg-avatar { background: var(--ink-soft); color: var(--white); }
        .chat-bubble { padding: .75rem 1rem; border-radius: var(--radius-md); font-size: .86rem; line-height: 1.6; max-width: 100%; }
        .chat-msg.bot .chat-bubble { background: var(--white); color: var(--ink); border: 1.5px solid var(--smoke-dark); border-bottom-left-radius: var(--radius-xs); }
        .chat-msg.user .chat-bubble { background: var(--gold); color: var(--ink); font-weight: 500; border-bottom-right-radius: var(--radius-xs); }
        .chat-typing { display: flex; gap: .6rem; align-items: flex-end; align-self: flex-start; }
        .chat-typing .chat-bubble { background: var(--white); border: 1.5px solid var(--smoke-dark); display: flex; gap: 4px; align-items: center; padding: .75rem 1rem; border-bottom-left-radius: var(--radius-xs); }
        .typing-dot { width: 7px; height: 7px; background: var(--text-muted); border-radius: 50%; animation: typingBounce .8s ease-in-out infinite; }
        .typing-dot:nth-child(2) { animation-delay: .15s; }
        .typing-dot:nth-child(3) { animation-delay: .3s; }
        @keyframes typingBounce { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-5px)} }

        .chatbot-embed-suggestions { padding: .8rem 1.5rem; display: flex; flex-wrap: wrap; gap: .5rem; background: var(--white); border-top: 1px solid var(--smoke-dark); border-bottom: 1px solid var(--smoke-dark); }
        .chat-suggest-btn { background: transparent; border: 1.5px solid var(--smoke-dark); color: var(--text-dim); padding: .4rem .9rem; border-radius: 50px; font-size: .78rem; font-weight: 600; cursor: pointer; transition: all .2s; font-family: var(--font-body); display: inline-flex; align-items: center; gap: .35rem; }
        .chat-suggest-btn:hover { background: var(--gold); color: var(--ink); border-color: var(--gold); transform: translateY(-1px); }

        .chatbot-embed-input-row { display: flex; gap: .6rem; padding: 1rem 1.5rem; background: var(--white); }
        .chatbot-embed-input { flex: 1; border: 1.5px solid var(--smoke-dark); border-radius: 50px; padding: .6rem 1.1rem; font-family: var(--font-body); font-size: .88rem; color: var(--ink); outline: none; transition: border-color .2s; }
        .chatbot-embed-input:focus { border-color: var(--gold); }
        .chatbot-embed-send { width: 40px; height: 40px; background: var(--gold); border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--ink); font-size: .9rem; flex-shrink: 0; transition: all .2s; }
        .chatbot-embed-send:hover { background: var(--gold-light); transform: scale(1.08); }

        
        .cta-section { background: var(--gold); padding: 5.5rem 0; }
        .cta-section h2 { font-family: var(--font-display); font-size: clamp(2.2rem, 4vw, 3.5rem); color: var(--ink); letter-spacing: .04em; }
        .btn-cta-dark { display: inline-flex; align-items: center; gap: .5rem; background: var(--ink); color: var(--white); padding: .9rem 2rem; border-radius: 50px; font-weight: 600; text-decoration: none; font-size: 1rem; transition: all .25s; }
        .btn-cta-dark:hover { background: var(--ink-soft); color: var(--white); transform: translateY(-2px); }
        .btn-cta-outline { display: inline-flex; align-items: center; gap: .5rem; background: transparent; color: var(--ink); border: 2px solid var(--ink); padding: .9rem 2rem; border-radius: 50px; font-weight: 600; text-decoration: none; font-size: 1rem; transition: all .25s; }
        .btn-cta-outline:hover { background: var(--ink); color: var(--white); }

        
        .floating-action-buttons { position: fixed; bottom: 30px; right: 30px; z-index: 9999; display: flex; flex-direction: column; align-items: flex-end; gap: 14px; }
        .fab-secondary-container { display: flex; flex-direction: column; gap: 10px; align-items: flex-end; max-height: 0; overflow: hidden; opacity: 0; transform: translateY(16px); transition: all .4s cubic-bezier(.4,0,.2,1); order: 1; }
        .fab-secondary-container.show { max-height: 500px; opacity: 1; transform: translateY(0); margin-bottom: 14px; }
        .fab-button { display: flex; align-items: center; justify-content: center; gap: 10px; padding: .75rem 1.4rem; border-radius: 50px; font-weight: 700; font-size: .88rem; text-decoration: none; box-shadow: var(--shadow-lg); border: none; cursor: pointer; transition: all .3s cubic-bezier(.4,0,.2,1); position: relative; overflow: hidden; min-width: 165px; z-index: 1; letter-spacing: .02em; color: white; }
        .fab-button:hover { transform: translateY(-4px) scale(1.04); box-shadow: var(--shadow-xl); text-decoration: none; color: white; }
        .fab-icon { width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,.18); border-radius: 50%; font-size: 1rem; flex-shrink: 0; }
        .fab-text { white-space: nowrap; font-size: .85rem; }
        
        .fab-main-call { background: var(--gold); color: var(--ink) !important; border: 1.5px solid rgba(212,160,23,.5); order: 2; animation: fabFloat 2.5s ease-in-out infinite; position: relative; }
        .fab-main-call::before { content: ''; position: absolute; top: -5px; left: -5px; right: -5px; bottom: -5px; background: var(--gold); border-radius: 50px; z-index: -1; opacity: .3; animation: pulseRing 2.2s ease-out infinite; }
        .fab-main-call:hover { background: var(--gold-light) !important; }
        @keyframes fabFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
        @keyframes pulseRing { 0%{transform:scale(.95);opacity:.5} 100%{transform:scale(1.18);opacity:0} }
        .fab-toggle { width: 52px; height: 52px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; cursor: pointer; box-shadow: var(--shadow-lg); border: 1.5px solid rgba(212,160,23,.35); transition: all .3s; order: 3; background: var(--ink); color: var(--gold-light); }
        .fab-toggle:hover { background: var(--ink-soft); border-color: var(--gold); }
        .fab-toggle.rotate { transform: rotate(45deg); }

        
        footer.bg-dark { background: var(--ink) !important; border-top: 1px solid rgba(255,255,255,.06); }
        .social-icon { width: 36px; height: 36px; border: 1px solid rgba(255,255,255,.15); border-radius: var(--radius-xs); display: inline-flex; align-items: center; justify-content: center; color: rgba(255,255,255,.5); text-decoration: none; margin-right: .4rem; transition: all .2s; font-size: .85rem; }
        .social-icon:hover { border-color: var(--gold); color: var(--gold); }

        
        @keyframes fadeInUp { from{opacity:0;transform:translateY(32px)} to{opacity:1;transform:translateY(0)} }
        .animate-fadeInUp { animation: fadeInUp .75s ease-out; }

        
        @media (max-width: 1100px) { .benefit-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 992px) {
            .timeline::before { left: 30px; }
            .timeline-content { width: calc(100% - 80px); margin-left: 80px !important; margin-right: 0 !important; }
            .timeline-item:nth-child(odd) .timeline-year, .timeline-item:nth-child(even) .timeline-year { left: 0 !important; right: auto !important; transform: translateY(-50%) !important; }
        }
        @media (max-width: 768px) {
            .hero-section { padding-top: 120px; min-height: auto; padding-bottom: 4rem; }
            .hero-title { font-size: clamp(2.8rem, 12vw, 5rem); }
            .floating-action-buttons { bottom: 15px; right: 15px; }
            .chat-float-wrap { bottom: 15px; left: 15px; }
            .chat-float-window { width: 290px; }
            .section { padding: 3.5rem 0; }
        }
        @media (max-width: 576px) {
            .benefit-grid { grid-template-columns: 1fr; }
            .btn-primary-custom, .btn-secondary-custom { width: 100%; justify-content: center; margin-bottom: .5rem; }
            .fab-button { min-width: 130px; padding: .65rem 1rem; font-size: .82rem; }
        }
        @media (max-width: 400px) {
            .fab-text { display: none; }
            .fab-button { width: 48px !important; height: 48px !important; border-radius: 50% !important; padding: 0 !important; min-width: auto !important; }
        }
        @media (hover: none) and (pointer: coarse) { .story-card:hover, .value-card:hover { transform: none; } }
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
                        <span style="color:rgba(255,255,255,.5);">About Us</span>
                    </nav>
                    <div class="hero-badge"><div class="hero-badge-dot"></div><span>Established Since 2020</span></div>
                    <h1 class="hero-title">ABOUT<span class="gold-accent">RG CARTINTWORKS</span></h1>
                    <p class="hero-subtitle">Davao's trusted mobile window tinting specialist — delivering superior vehicle protection, privacy, and comfort since 2020.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="customer/book.php" class="btn-primary-custom"><i class="fas fa-calendar-check"></i> Book Appointment</a>
                        <a href="gallery/index.php" class="btn-secondary-custom"><i class="fas fa-images"></i> View Our Work</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="promo-ribbon">
    <div class="ribbon-track">
        <span class="ribbon-item"><i class="fas fa-award"></i> 5+ YEARS OF EXPERIENCE</span>
        <span class="ribbon-item"><i class="fas fa-shield-alt"></i> UP TO Official Warranty</span>
        <span class="ribbon-item"><i class="fas fa-sun"></i> 99% UV PROTECTION</span>
        <span class="ribbon-item"><i class="fas fa-truck"></i> MOBILE HOME SERVICE AVAILABLE</span>
        <span class="ribbon-item"><i class="fas fa-map-marker-alt"></i> DAVAO · TAGUM · DIGOS · PANABO · CARMEN</span>
        <span class="ribbon-item"><i class="fas fa-award"></i> 5+ YEARS OF EXPERIENCE</span>
        <span class="ribbon-item"><i class="fas fa-shield-alt"></i> UP TO Official Warranty</span>
        <span class="ribbon-item"><i class="fas fa-sun"></i> 99% UV PROTECTION</span>
        <span class="ribbon-item"><i class="fas fa-truck"></i> MOBILE HOME SERVICE AVAILABLE</span>
        <span class="ribbon-item"><i class="fas fa-map-marker-alt"></i> DAVAO · TAGUM · DIGOS · PANABO · CARMEN</span>
    </div>
</div>

<section class="section" style="background:var(--white);">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6"><div class="section-eyebrow">Our Story</div><h2 class="section-title">Who <em>We Are</em></h2></div>
            <div class="col-lg-5 ms-auto"><p class="section-subtitle">Established in 2020, RG CARTINTWORKS began with a mission to provide Davao car owners with superior vehicle protection and privacy. With 5 years of dedicated experience, we've evolved into a trusted freelancer-owned business known for precision and craftsmanship.</p></div>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="story-card">
                    <div class="year-badge"><i class="fas fa-flag me-2"></i>Established 2020</div>
                    <h3 class="mb-3" style="font-family:var(--font-display);letter-spacing:.04em;font-size:1.8rem;color:var(--ink);">Our Beginning</h3>
                    <p style="color:var(--text-dim);line-height:1.75;font-size:.95rem;">RG CARTINTWORKS started with one goal: deliver professional window tinting that provides reliable privacy, protection, and comfort for every vehicle. From day one, we focused on precision installations and a customer-first approach that builds long-term trust.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="story-card">
                    <div class="year-badge"><i class="fas fa-rocket me-2"></i>Today</div>
                    <h3 class="mb-3" style="font-family:var(--font-display);letter-spacing:.04em;font-size:1.8rem;color:var(--ink);">Trusted Mobile Service</h3>
                    <p style="color:var(--text-dim);line-height:1.75;font-size:.95rem;">Today we are a mobile-first freelancer-owned business offering professional home-service within Davao City and to clients outside Davao. We combine premium materials, expert workmanship, and a modern service system for smooth booking, accurate tracking, and reliable support.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section-dark">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6"><div class="section-eyebrow" style="color:rgba(255,255,255,.4);">By the Numbers</div><h2 class="section-title section-title-light">Our <em>Track Record</em></h2></div>
            <div class="col-lg-5 ms-auto"><p class="section-subtitle section-subtitle-light">Years of experience and consistent quality trusted by hundreds of Davao vehicle owners</p></div>
        </div>
        <div class="row g-4">
            <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="fas fa-calendar-alt" style="color:var(--ink);"></i></div><div class="stat-number">5+</div><div class="stat-label">Years Experience</div><div class="stat-unit">Since 2020</div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="fas fa-car" style="color:var(--ink);"></i></div><div class="stat-number">500+</div><div class="stat-label">Vehicles Tinted</div><div class="stat-unit">And Counting</div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="fas fa-star" style="color:var(--ink);"></i></div><div class="stat-number">100%</div><div class="stat-label">Satisfied Clients</div><div class="stat-unit">Customer Rating</div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="fas fa-shield-alt" style="color:var(--ink);"></i></div><div class="stat-number">7yr</div><div class="stat-label">Max Warranty</div><div class="stat-unit">On Premium Films</div></div></div>
        </div>
    </div>
</section>

<section class="section section-smoke">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6"><div class="section-eyebrow">Milestones</div><h2 class="section-title">Our <em>Journey</em></h2></div>
            <div class="col-lg-5 ms-auto"><p class="section-subtitle">Key milestones that shaped our growth and cemented our reputation in Davao</p></div>
        </div>
        <div class="timeline">
            <div class="timeline-item"><div class="timeline-year">2020</div><div class="timeline-content"><h4>Business Started</h4><p>Started tinting services focused on superior protection, privacy, and quality workmanship for Davao car owners</p></div></div>
            <div class="timeline-item"><div class="timeline-year">2021</div><div class="timeline-content"><h4>High-Performance Materials</h4><p>Specialized in premium film options including HD Carbon, Magic Chrome, and Nano Ceramic series</p></div></div>
            <div class="timeline-item"><div class="timeline-year">2022</div><div class="timeline-content"><h4>Mobile-First Expansion</h4><p>Expanded professional home-service tinting within Davao City and began serving clients outside Davao</p></div></div>
            <div class="timeline-item"><div class="timeline-year">2023</div><div class="timeline-content"><h4>Warranty & Performance</h4><p>Strengthened service quality with up to 99% UV protection, strong heat rejection, and 5–7 year warranties</p></div></div>
            <div class="timeline-item"><div class="timeline-year">2024</div><div class="timeline-content"><h4>Digitized Service System</h4><p>Implemented a fully digitized service management system for booking, inventory tracking, and customer support</p></div></div>
        </div>
    </div>
</section>

<section class="section" style="background:var(--white);">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6"><div class="section-eyebrow">Our Advantage</div><h2 class="section-title">Why <em>Choose Us?</em></h2></div>
            <div class="col-lg-5 ms-auto"><p class="section-subtitle">Delivering professional, convenient, and premium tinting services with every installation</p></div>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3"><div class="value-card"><div class="value-icon"><i class="fas fa-award"></i></div><h5>Expertise You Can Trust</h5><p>Half a decade of hands-on tinting experience applied with precision in every project.</p></div></div>
            <div class="col-md-6 col-lg-3"><div class="value-card"><div class="value-icon"><i class="fas fa-truck"></i></div><h5>Unmatched Convenience</h5><p>Mobile-first home-service within Davao City and to clients outside Davao — we go to you.</p></div></div>
            <div class="col-md-6 col-lg-3"><div class="value-card"><div class="value-icon"><i class="fas fa-shield-alt"></i></div><h5>Premium Protection</h5><p>HD Carbon, Magic Chrome, Nano Ceramic series — up to 99% UV protection and strong heat rejection.</p></div></div>
            <div class="col-md-6 col-lg-3"><div class="value-card"><div class="value-icon"><i class="fas fa-user-check"></i></div><h5>Personalized Care</h5><p>Freelancer-owned with full accountability and customized attention you can feel in every job.</p></div></div>
        </div>
    </div>
</section>

<section class="section section-smoke">
    <div class="container">
        <div class="row align-items-end mb-2">
            <div class="col-lg-6"><div class="section-eyebrow">Film Performance</div><h2 class="section-title">What Our <em>Tints Do</em></h2></div>
            <div class="col-lg-5 ms-auto"><p class="section-subtitle">Every film we install delivers these proven, measurable benefits for your vehicle</p></div>
        </div>
        <div class="benefit-grid">
            <div class="benefit-cell"><div class="benefit-icon"><i class="fas fa-thermometer-half"></i></div><h5>Heat Reduction</h5><p>Block up to 99% of infrared heat — up to 20°C cooler inside your car</p></div>
            <div class="benefit-cell"><div class="benefit-icon"><i class="fas fa-shield-alt"></i></div><h5>UV Protection</h5><p>99% UV ray blockage — protect skin and prevent interior fading</p></div>
            <div class="benefit-cell"><div class="benefit-icon"><i class="fas fa-eye-slash"></i></div><h5>Glare Reduction</h5><p>Reduce dangerous glare for safer and more comfortable driving</p></div>
            <div class="benefit-cell"><div class="benefit-icon"><i class="fas fa-lock"></i></div><h5>Enhanced Privacy</h5><p>Keep your belongings safe with increased window privacy</p></div>
            <div class="benefit-cell"><div class="benefit-icon"><i class="fas fa-snowflake"></i></div><h5>AC Efficiency</h5><p>Cooler interior means less AC use and better fuel economy</p></div>
            <div class="benefit-cell"><div class="benefit-icon"><i class="fas fa-layer-group"></i></div><h5>Shatter Protection</h5><p>Film holds broken glass together during accidents for added safety</p></div>
        </div>
    </div>
</section>

<section class="section section-dark">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6"><div class="section-eyebrow" style="color:rgba(255,255,255,.4);">Core Values</div><h2 class="section-title section-title-light">Mission & <em>Innovation</em></h2></div>
            <div class="col-lg-5 ms-auto"><p class="section-subtitle section-subtitle-light">Driven by purpose and powered by technology to serve you better</p></div>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div style="background:rgba(255,255,255,.04);border-radius:var(--radius-lg);padding:2.5rem;height:100%;border:1.5px solid rgba(212,160,23,.15);">
                    <div style="width:56px;height:56px;background:var(--gold);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--ink);margin-bottom:1.4rem;"><i class="fas fa-bullseye"></i></div>
                    <h3 style="font-family:var(--font-display);font-size:1.8rem;letter-spacing:.04em;color:var(--gold-light);margin-bottom:1rem;">OUR MISSION</h3>
                    <p style="color:rgba(255,255,255,.6);line-height:1.8;font-size:.95rem;">To provide Davao car owners with superior protection and privacy through professional window tinting using high-performance materials and precision workmanship. We deliver comfort, safety, and customer satisfaction in every project — without compromise.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div style="background:rgba(255,255,255,.04);border-radius:var(--radius-lg);padding:2.5rem;height:100%;border:1.5px solid rgba(212,160,23,.15);">
                    <div style="width:56px;height:56px;background:var(--gold);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--ink);margin-bottom:1.4rem;"><i class="fas fa-lightbulb"></i></div>
                    <h3 style="font-family:var(--font-display);font-size:1.8rem;letter-spacing:.04em;color:var(--gold-light);margin-bottom:1rem;">INNOVATION IN SERVICE</h3>
                    <p style="color:rgba(255,255,255,.6);line-height:1.8;font-size:.95rem;">We use technology to better serve our customers. With our AI Tint Assistant for instant technical comparisons and estimates, plus a comprehensive Service Management System for booking, inventory tracking, and support — we provide professional efficiency from start to finish.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section-smoke" id="faq">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6">
                <div class="section-eyebrow">FAQ</div>
                <h2 class="section-title">Frequently Asked <em>Questions</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle">Browse common questions — or chat with our <strong style="color:var(--gold);">Tint Assistant</strong> on the right for instant answers!</p>
            </div>
        </div>

        <div class="row g-5 align-items-start">

            
            <div class="col-lg-6">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                <i class="fas fa-clock me-2" style="color:var(--gold);"></i>How long have you been in business?
                            </button>
                        </h3>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">We were established in 2020 and have 5 years of experience and counting, serving Davao City and clients outside Davao through our mobile-first home service.</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                <i class="fas fa-film me-2" style="color:var(--gold);"></i>What tint series do you offer?
                            </button>
                        </h3>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">We specialize in high-performance materials including HD Carbon, Magic Chrome, and Nano Ceramic series — designed for excellent heat rejection, privacy, and protection.</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                <i class="fas fa-shield-alt me-2" style="color:var(--gold);"></i>Do you offer warranty?
                            </button>
                        </h3>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">Yes! Our tinting solutions come with warranties ranging from 5 to 7 years depending on the film series chosen. We stand behind every installation we do.</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                <i class="fas fa-truck me-2" style="color:var(--gold);"></i>Do you offer home service?
                            </button>
                        </h3>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">Yes — we are a mobile-first business. We provide professional home-service tinting within Davao City and can also serve clients outside Davao upon arrangement.</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                <i class="fas fa-star me-2" style="color:var(--gold);"></i>What makes you different?
                            </button>
                        </h3>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">We combine premium materials, hands-on expertise, and technology-driven service — including our AI Tint Assistant and digitized Service Management System — for smoother booking, better tracking, and reliable support.</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                <i class="fas fa-tag me-2" style="color:var(--gold);"></i>How much does car tinting cost?
                            </button>
                        </h3>
                        <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">Pricing depends on your vehicle type, window count, and the film series selected. HD Carbon is our most affordable option, while Nano Ceramic is our premium tier. Contact us or ask our chatbot for a quick estimate!</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                                <i class="fas fa-map-marker-alt me-2" style="color:var(--gold);"></i>Where are you located?
                            </button>
                        </h3>
                        <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">We are mobile-based and cover Davao City, Tagum, Digos, Panabo, and Carmen. We come to your home or office — no need to drive to a shop!</div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="col-lg-6">
                <div class="chatbot-embed-wrap" style="position:relative;">
                    <div id="embedContentWrapper" <?php if (!$is_logged_in) echo 'style="filter: blur(4px); pointer-events: none; opacity: 0.25;"'; ?>>
                        <div class="chatbot-embed-header">
                            <div class="chatbot-embed-avatar"><i class="fas fa-robot"></i></div>
                            <div class="chatbot-embed-info">
                                <h6>RG TINT ASSISTANT</h6>
                                <span>Online — Ask me anything!</span>
                            </div>
                        </div>
                        <div class="chatbot-embed-messages" id="embedChatMessages">
                            <div class="chat-msg bot">
                                <div class="chat-msg-avatar"><i class="fas fa-robot"></i></div>
                                <div class="chat-bubble">👋 Hi! I'm the <strong>RG Tint Assistant</strong>. I can answer questions about tint types, pricing, warranty, booking, and our service areas. What would you like to know? 😊</div>
                            </div>
                        </div>
                        <div class="chatbot-embed-suggestions">
                            <button class="chat-suggest-btn" onclick="embedSuggest('What tint types do you offer?')"><i class="fas fa-film"></i> Tint Types</button>
                            <button class="chat-suggest-btn" onclick="embedSuggest('How much does tinting cost?')"><i class="fas fa-tag"></i> Pricing</button>
                            <button class="chat-suggest-btn" onclick="embedSuggest('Do you have warranty?')"><i class="fas fa-shield-alt"></i> Warranty</button>
                            <button class="chat-suggest-btn" onclick="embedSuggest('How do I book an appointment?')"><i class="fas fa-calendar-check"></i> Booking</button>
                            <button class="chat-suggest-btn" onclick="embedSuggest('Where are you located?')"><i class="fas fa-map-marker-alt"></i> Location</button>
                            <button class="chat-suggest-btn" onclick="embedSuggest('Do you offer home service?')"><i class="fas fa-truck"></i> Home Service</button>
                        </div>
                        <div class="chatbot-embed-input-row">
                            <input type="text" class="chatbot-embed-input" id="embedChatInput" placeholder="Type your question here..." autocomplete="off">
                            <button class="chatbot-embed-send" onclick="embedSendMessage()"><i class="fas fa-paper-plane"></i></button>
                        </div>
                    </div>
                    <?php if (!$is_logged_in): ?>
                        
                        <div id="embedLockOverlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(10, 10, 10, 0.95); display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; text-align: center; z-index: 10; border-radius: var(--radius-lg);">
                            <div style="width: 50px; height: 50px; background: rgba(212,160,23,.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; border: 1.5px solid rgba(212,160,23,.3);">
                                <i class="fas fa-lock" style="color:var(--gold);"></i>
                            </div>
                            <h6 class="fw-bold mb-2 text-white">Sign In Required</h6>
                            <p class="text-muted small mb-3" style="max-width: 250px;">Please sign in to your account to chat with our AI Tint Assistant.</p>
                            <button type="button" data-bs-toggle="modal" data-bs-target="#signInModal" class="btn btn-sm fw-bold px-3 py-1.5" style="background:var(--gold);color:#0A0A0A;border-radius:50px;font-size:0.8rem;">
                                Sign In
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h2>READY TO EXPERIENCE THE DIFFERENCE?</h2>
                <p class="mb-0 mt-2" style="color:rgba(10,10,10,.65);">Professional installation · Up to Official Warranty · Mobile service available</p>
                <div class="row g-4 mt-2">
                    <div class="col-4 text-center"><i class="fas fa-truck fa-2x mb-2" style="color:var(--ink);opacity:.7;"></i><div style="font-size:.85rem;font-weight:600;color:var(--ink);">Mobile Service</div></div>
                    <div class="col-4 text-center"><i class="fas fa-award fa-2x mb-2" style="color:var(--ink);opacity:.7;"></i><div style="font-size:.85rem;font-weight:600;color:var(--ink);">Official Warranty</div></div>
                    <div class="col-4 text-center"><i class="fas fa-sun fa-2x mb-2" style="color:var(--ink);opacity:.7;"></i><div style="font-size:.85rem;font-weight:600;color:var(--ink);">99% UV Block</div></div>
                </div>
            </div>
            <div class="col-lg-5 text-lg-end d-flex gap-3 flex-wrap justify-content-lg-end">
                <a href="customer/book.php" class="btn-cta-dark"><i class="fas fa-calendar-check"></i> Book Appointment Now</a>
                <a href="tel:+09970628687" class="btn-cta-outline"><i class="fas fa-phone"></i> Call: +0997 062 8687</a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/chatbot.php'; ?>

<footer class="bg-dark text-white py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h4 class="fw-bold mb-3" style="color:var(--gold-light);font-family:var(--font-display);letter-spacing:.06em;font-size:1.7rem;">RG CARTINTWORKS</h4>
                <p class="mb-4" style="color:rgba(255,255,255,.5);font-size:.88rem;line-height:1.75;">Davao's premier car window tinting specialist since 2020. Professional installation with up to Official Warranty using only authentic high-performance films.</p>
                <div class="d-flex gap-3">
                    <a href="https://www.facebook.com/profile.php?id=61574013049501" target="_blank" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <h5 style="font-size:.75rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-bottom:.9rem;">Services</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="services.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Car Window Tinting</a></li>
                    <li class="mb-2"><a href="services.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">SUV Tinting</a></li>
                    <li class="mb-2"><a href="services.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Tint Removal</a></li>
                    <li class="mb-2"><a href="services.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Tint Repair</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6">
                <h5 style="font-size:.75rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-bottom:.9rem;">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="index.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Home</a></li>
                    <li class="mb-2"><a href="gallery/index.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Gallery</a></li>
                    <li class="mb-2"><a href="services.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Services</a></li>
                    <li class="mb-2"><a href="customer/book.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Book Now</a></li>
                    <li class="mb-2"><a href="about.php" style="font-size:.86rem;color:rgba(255,255,255,.9);text-decoration:none;font-weight:600;">About Us</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h5 style="font-size:.75rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-bottom:.9rem;">Contact Us</h5>
                <ul class="list-unstyled">
                    <li class="mb-3 d-flex align-items-start gap-2"><i class="fas fa-map-marker-alt mt-1" style="color:var(--gold);width:16px;"></i><span style="font-size:.86rem;color:rgba(255,255,255,.5);">Davao · Tagum · Digos · Panabo · Carmen<br><small style="color:rgba(255,255,255,.3);">Serving Davao Region</small></span></li>
                    <li class="mb-3 d-flex align-items-center gap-2"><i class="fas fa-phone" style="color:var(--gold);width:16px;"></i><a href="tel:09970628687" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">+0997 062 8687</a></li>
                    <li class="mb-3 d-flex align-items-center gap-2"><i class="fas fa-envelope" style="color:var(--gold);width:16px;"></i><a href="mailto:rminguillo12@gmail.com" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">rminguillo12@gmail.com</a></li>
                    <li class="d-flex align-items-center gap-2"><i class="fas fa-clock" style="color:var(--gold);width:16px;"></i><span style="font-size:.86rem;color:rgba(255,255,255,.5);">Mon–Sat: 8AM–6PM | Sun: 9AM–4PM</span></li>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>

const chatResponses = [
    {
        keywords: ['tint type','tint series','film','carbon','ceramic','chrome','nano','hd','what tint','type of tint','available','series'],
        answer: `🎨 We offer <strong>3 premium tint series</strong>:<br><br>
🔹 <strong>HD Carbon</strong> — Durable carbon film, great heat rejection, ideal for pickups & vans.<br>
🔹 <strong>Magic Chrome</strong> — Stunning chameleon finish with strong heat & UV rejection.<br>
🔹 <strong>Nano Ceramic</strong> — Our best! 99%+ UV & IR rejection, crystal clarity, up to Official Warranty.<br><br>
Want help choosing the right one? Just tell me your vehicle type! 😊`
    },
    {
        keywords: ['price','cost','how much','magkano','presyo','rate','fee','charge','quote','estimate','bayad'],
        answer: `💰 Pricing depends on your <strong>vehicle type</strong> and <strong>tint series</strong>:<br><br>
🚗 <strong>Sedan</strong> — Budget-friendly rates starting with HD Carbon<br>
🚙 <strong>SUV / Van</strong> — Mid-range pricing depending on window count<br>
✨ <strong>Nano Ceramic</strong> — Premium tier with top-of-the-line performance<br><br>
📲 For an exact quote, call us at <a href="tel:09970628687" style="color:var(--gold);font-weight:700;">+0997 062 8687</a> or message us on <a href="https://m.me/61574013049501" target="_blank" style="color:var(--gold);">Messenger</a>!`
    },
    {
        keywords: ['warranty','garantiya','guarantee','lifespan','how long','last','duration','year','matagal','expire'],
        answer: `🛡️ Yes! All tints come with <strong>warranty coverage</strong>:<br><br>
📌 <strong>HD Carbon</strong> — 3–5 year warranty<br>
📌 <strong>Magic Chrome</strong> — 5 year warranty<br>
📌 <strong>Nano Ceramic</strong> — Up to <strong>7 years</strong> warranty<br><br>
Warranty covers peeling, bubbling, and discoloration under normal use. ✅`
    },
    {
        keywords: ['book','appointment','schedule','reserve','pano mag book','how to book','set schedule','pa tint','mag pa'],
        answer: `📅 Booking is easy!<br><br>
1️⃣ Click <a href="customer/book.php" style="color:var(--gold);font-weight:700;">Book Appointment</a> on our website<br>
2️⃣ Fill in your vehicle details and preferred date<br>
3️⃣ We confirm your slot via call or message<br><br>
Or reach us on <a href="https://m.me/61574013049501" target="_blank" style="color:var(--gold);">Messenger</a> / <a href="https://wa.me/09970628687" target="_blank" style="color:var(--gold);">WhatsApp</a> anytime! 📲`
    },
    {
        keywords: ['location','address','where','nasaan','saan','area','coverage','map','find','lugar'],
        answer: `📍 We are a <strong>mobile tinting service</strong> — we come to YOU!<br><br>
✅ <strong>Areas covered:</strong><br>
• Davao City (all areas)<br>
• Tagum<br>
• Digos<br>
• Panabo<br>
• Carmen<br><br>
No need to drive anywhere — we go to your home or office! 🚐`
    },
    {
        keywords: ['home service','mobile','house','office','door to door','pupunta','aabot','lalabas','pwede sa bahay'],
        answer: `🚐 Yes! We offer <strong>mobile home-service tinting</strong>!<br><br>
We come to your:<br>
🏠 Home<br>
🏢 Office<br>
🅿️ Any parking area<br><br>
Just book and give us your address. We handle setup, installation, and cleanup — no mess, no hassle! 😊`
    },
    {
        keywords: ['uv','sun','heat','temperature','ir','infrared','protection','block','reject','cool','hot','init'],
        answer: `☀️ Our tints deliver <strong>industry-leading protection</strong>:<br><br>
🌡️ <strong>Heat Rejection</strong> — Up to 99% IR blocked = up to 20°C cooler inside<br>
🔆 <strong>UV Protection</strong> — 99% of harmful UV rays blocked<br>
😎 <strong>Glare Reduction</strong> — Safer, more comfortable driving<br>
❄️ <strong>AC Efficiency</strong> — Less AC = better fuel economy<br><br>
<strong>Nano Ceramic</strong> gives the best across all these! ✨`
    },
    {
        keywords: ['contact','phone','number','call','reach','facebook','messenger','whatsapp','social','message','email'],
        answer: `📞 Here's how to reach us:<br><br>
📱 <strong>Phone:</strong> <a href="tel:09970628687" style="color:var(--gold);">+0997 062 8687</a><br>
💬 <strong>Facebook:</strong> <a href="https://www.facebook.com/profile.php?id=61574013049501" target="_blank" style="color:var(--gold);">RG CARTINTWORKS</a><br>
📲 <strong>Messenger:</strong> <a href="https://m.me/61574013049501" target="_blank" style="color:var(--gold);">Message Us</a><br>
💚 <strong>WhatsApp:</strong> <a href="https://wa.me/09970628687" target="_blank" style="color:var(--gold);">Chat on WhatsApp</a><br>
📧 <strong>Email:</strong> rminguillo12@gmail.com<br><br>
🕐 Mon–Sat: 8AM–6PM | Sun: 9AM–4PM`
    },
    {
        keywords: ['hello','hi','hey','good morning','good afternoon','good evening','kumusta','musta','howdy','kamusta'],
        answer: `👋 Hello! Welcome to <strong>RG CARTINTWORKS Davao</strong>! 🚗✨<br><br>
I can help you with:<br>
🎨 Tint types & series<br>
💰 Pricing & quotes<br>
🛡️ Warranty info<br>
📅 How to book<br>
📍 Service areas & mobile service<br><br>
What would you like to know? 😊`
    },
    {
        keywords: ['thank','thanks','salamat','okay','ok','noted','perfect','great','awesome','nice','ganda','maganda'],
        answer: `😊 You're very welcome! Feel free to ask anything anytime.<br><br>
Ready to get started? 👉 <a href="customer/book.php" style="color:var(--gold);font-weight:700;">Book your appointment</a> or call <a href="tel:09970628687" style="color:var(--gold);">+0997 062 8687</a>. We'd love to serve you! 🚗✨`
    }
];

const defaultReply = `🤔 Thanks for your message! For the best answer, please contact us directly:<br><br>
📞 <a href="tel:09970628687" style="color:var(--gold);">+0997 062 8687</a><br>
💬 <a href="https://m.me/61574013049501" target="_blank" style="color:var(--gold);">Facebook Messenger</a><br>
💚 <a href="https://wa.me/09970628687" target="_blank" style="color:var(--gold);">WhatsApp</a><br><br>
Or try asking about: <em>tint types, pricing, warranty, booking, or location!</em>`;

function getBotResponse(msg) {
    const lower = msg.toLowerCase();
    for (const item of chatResponses) {
        if (item.keywords.some(k => lower.includes(k))) return item.answer;
    }
    return defaultReply;
}

function embedAddMsg(text, sender) {
    const c = document.getElementById('embedChatMessages');
    const w = document.createElement('div'); w.className = `chat-msg ${sender}`;
    const a = document.createElement('div'); a.className = 'chat-msg-avatar'; a.innerHTML = sender === 'bot' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';
    const b = document.createElement('div'); b.className = 'chat-bubble'; b.innerHTML = text;
    w.appendChild(a); w.appendChild(b); c.appendChild(w); c.scrollTop = c.scrollHeight;
}
function embedShowTyping() {
    const c = document.getElementById('embedChatMessages');
    const w = document.createElement('div'); w.className = 'chat-typing'; w.id = 'embedTyping';
    const a = document.createElement('div'); a.className = 'chat-msg-avatar'; a.innerHTML = '<i class="fas fa-robot"></i>';
    const b = document.createElement('div'); b.className = 'chat-bubble'; b.innerHTML = '<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';
    w.appendChild(a); w.appendChild(b); c.appendChild(w); c.scrollTop = c.scrollHeight;
}
function embedRemoveTyping() { const t = document.getElementById('embedTyping'); if(t) t.remove(); }
function embedSendMessage() {
    const input = document.getElementById('embedChatInput');
    const msg = input.value.trim(); if(!msg) return;
    embedAddMsg(msg, 'user'); input.value = ''; embedShowTyping();
    setTimeout(() => { embedRemoveTyping(); embedAddMsg(getBotResponse(msg), 'bot'); }, 800 + Math.random()*500);
}
function embedSuggest(text) { document.getElementById('embedChatInput').value = text; embedSendMessage(); }
document.getElementById('embedChatInput').addEventListener('keydown', e => { if(e.key === 'Enter') embedSendMessage(); });

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

    document.querySelectorAll('.fab-secondary-container .fab-button').forEach(btn => {
        btn.addEventListener('click', () => {
            fabSecondary.classList.remove('show');
            fabToggle.classList.remove('rotate');
            fabToggleIcon.classList.remove('fa-times');
            fabToggleIcon.classList.add('fa-plus');
        });
    });

    
    const navbar = document.getElementById('mainNavbar');
    let lastScrollTop = 0;
    window.addEventListener('scroll', function() {
        const st = window.pageYOffset || document.documentElement.scrollTop;
        navbar.classList.toggle('scrolled', st > 50);
        if (st > lastScrollTop && st > 120) {
            navbar.classList.add('hide-nav');
            fabContainer.style.opacity = '0.7';
            fabContainer.style.transform = 'translateY(18px)';
        } else {
            navbar.classList.remove('hide-nav');
            fabContainer.style.opacity = '1';
            fabContainer.style.transform = 'translateY(0)';
        }
        lastScrollTop = st;
        const atBottom = window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 50;
        fabContainer.style.display = atBottom ? 'none' : 'flex';
    });

    
    const mobileToggler = document.getElementById('mobileToggler');
    const mobileMenu = document.getElementById('mobileMenu');
    const togglerIcon = document.getElementById('togglerIcon');
    let menuOpen = false;
    mobileToggler.addEventListener('click', function() {
        menuOpen = !menuOpen;
        if (menuOpen) {
            mobileMenu.style.setProperty('display', 'block', 'important');
            requestAnimationFrame(() => { mobileMenu.style.maxHeight = mobileMenu.scrollHeight + 'px'; mobileMenu.style.opacity = '1'; });
            togglerIcon.classList.replace('fa-bars','fa-times');
            mobileToggler.setAttribute('aria-expanded','true');
        } else {
            mobileMenu.style.maxHeight = '0'; mobileMenu.style.opacity = '0';
            setTimeout(() => mobileMenu.style.setProperty('display','none','important'), 400);
            togglerIcon.classList.replace('fa-times','fa-bars');
            mobileToggler.setAttribute('aria-expanded','false');
        }
    });

    
    const obs = new IntersectionObserver(entries => {
        entries.forEach(e => { if(e.isIntersecting) e.target.classList.add('animate-fadeInUp'); });
    }, { threshold: 0.1 });
    document.querySelectorAll('.story-card,.value-card,.timeline-content,.stat-card,.benefit-cell').forEach(el => obs.observe(el));

    
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', function(e) {
            const id = this.getAttribute('href');
            if(id === '#') return;
            const target = document.querySelector(id);
            if(target) { e.preventDefault(); window.scrollTo({ top: target.offsetTop - 100, behavior: 'smooth' }); }
        });
    });

    
    document.querySelectorAll('.hover-warning').forEach(link => {
        link.addEventListener('mouseenter', function() { this.style.color = 'var(--gold-light)'; this.style.transform = 'translateX(4px)'; });
        link.addEventListener('mouseleave', function() { this.style.color = ''; this.style.transform = ''; });
    });
});
</script>
</body>
</html>

