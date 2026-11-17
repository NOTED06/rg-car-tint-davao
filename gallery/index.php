<?php

require __DIR__ . '/../includes/auth.php';
boot_session();
require __DIR__ . '/../includes/db.php';

$current_page = basename($_SERVER['PHP_SELF']);

function displayCategory($category) {
    $display = [
        'sedan' => 'Sedan',
        'suv' => 'SUV',
        'pickup' => 'Pickup',
        'van' => 'Van',
        'luxury' => 'Luxury'
    ];
    return $display[$category] ?? ucfirst($category);
}

$gallery_items = [
    [
        'image' => BASE_URL . '/assets/images/sedan_tint.png',
        'category' => 'sedan',
        'title' => 'Toyota Vios - Ceramic Tint',
        'tint_type' => 'Ceramic Pro',
        'description' => 'Premium ceramic tint with 95% heat rejection',
        'year' => '2024'
    ],
    [
        'image' => BASE_URL . '/assets/images/suv_tint.png',
        'category' => 'pickup',
        'title' => 'Toyota Hilux - Carbon Tint',
        'tint_type' => 'Carbon Film',
        'description' => 'Durable carbon film for work vehicles',
        'year' => '2023'
    ],
    [
        'image' => BASE_URL . '/assets/images/van_tint.png',
        'category' => 'van',
        'title' => 'Toyota Hiace - Privacy Tint',
        'tint_type' => 'Privacy Film',
        'description' => 'Enhanced privacy for family vans',
        'year' => '2024'
    ],
    [
        'image' => BASE_URL . '/assets/images/hero_car.png',
        'category' => 'luxury',
        'title' => 'BMW X5 - Ultra Ceramic',
        'tint_type' => 'Ultra Ceramic',
        'description' => 'Ultra premium ceramic with 5-Year Warranty',
        'year' => '2024'
    ],
    [
        'image' => BASE_URL . '/assets/images/sedan_tint.png',
        'category' => 'sedan',
        'title' => 'Honda Civic - Light Tint',
        'tint_type' => 'Ceramic Lite',
        'description' => 'Light tint for maximum visibility',
        'year' => '2023'
    ],
    [
        'image' => BASE_URL . '/assets/images/suv_tint.png',
        'category' => 'suv',
        'title' => 'Mitsubishi Montero - Windshield Tint',
        'tint_type' => 'Windshield Ceramic',
        'description' => 'Clear ceramic for windshield protection',
        'year' => '2024'
    ],
    [
        'image' => BASE_URL . '/assets/images/hero_car.png',
        'category' => 'luxury',
        'title' => 'Mercedes-Benz - Elite Package',
        'tint_type' => 'Elite Ceramic',
        'description' => 'Full elite ceramic tint package',
        'year' => '2024'
    ],
    [
        'image' => BASE_URL . '/assets/images/sedan_tint.png',
        'category' => 'sedan',
        'title' => 'Mazda 3 - Crystal Clear Tint',
        'tint_type' => 'Crystal Clear',
        'description' => 'Almost invisible with high heat rejection',
        'year' => '2023'
    ],
    [
        'image' => BASE_URL . '/assets/images/hero_car.png',
        'category' => 'luxury',
        'title' => 'Audi Q7 - Premium Tint',
        'tint_type' => 'Premium Ceramic',
        'description' => 'Premium ceramic with 99% heat rejection',
        'year' => '2024'
    ]
];

$featured_albums = [
    'nissan_terra' => [
        'title' => 'NISSAN TERRA 2019 - FULL WRAPPED BLACK SERIES',
        'tint_name' => 'CROWN OPTICS HD NANO CERAMIC TINT',
        'description' => 'Professional installation showcase',
        'features' => [
            '99% heat rejection',
            '99% UV-BLOCKING',
            '12 years warranty',
            'Made in USA'
        ],
        'images' => [
            [
                'url' => BASE_URL . '/assets/images/suv_tint.png',
                'title' => 'Nissan Terra - Front View',
                'type' => 'Front Windshield Tint Installation'
            ],
            [
                'url' => BASE_URL . '/assets/images/suv_tint.png',
                'title' => 'Nissan Terra - Side Windows',
                'type' => 'Side Windows Tint Installation'
            ],
            [
                'url' => BASE_URL . '/assets/images/suv_tint.png',
                'title' => 'Nissan Terra - Rear View',
                'type' => 'Rear Window Tint Installation'
            ],
            [
                'url' => BASE_URL . '/assets/images/suv_tint.png',
                'title' => 'Nissan Terra - Interior View',
                'type' => 'Interior View with Tint'
            ]
        ]
    ],
    'ford_everest' => [
        'title' => 'FORD EVEREST NEXT GEN - FULL WRAPPED BLACK SERIES',
        'tint_name' => 'CROWN OPTICS HD NANO CERAMIC TINT',
        'description' => 'Another satisfied customer - Next Gen Ford Everest',
        'features' => [
            '99% HEAT REJECTION',
            '99% UV REJECTION',
            '10-12 YEARS LIFESPAN',
            'MADE IN USA'
        ],
        'images' => [
            [
                'url' => BASE_URL . '/assets/images/suv_tint.png',
                'title' => 'Ford Everest Next Gen - Front View',
                'type' => 'Front Windshield Tint Installation'
            ],
            [
                'url' => BASE_URL . '/assets/images/suv_tint.png',
                'title' => 'Ford Everest Next Gen - Side Profile',
                'type' => 'Side Windows Tint Installation'
            ],
            [
                'url' => BASE_URL . '/assets/images/suv_tint.png',
                'title' => 'Ford Everest Next Gen - Rear View',
                'type' => 'Rear Window Tint Installation'
            ],
            [
                'url' => BASE_URL . '/assets/images/suv_tint.png',
                'title' => 'Ford Everest Next Gen - Interior View',
                'type' => 'Interior View with Tint'
            ]
        ]
    ],
    'da64v_minivan' => [
        'title' => 'DA64V MINIVAN TRANSFORMER - FULL WRAPPED BLACK SERIES',
        'tint_name' => 'CERAMIC TINT',
        'location' => 'Inawayan, Davao City',
        'referral' => 'REFERRAL from previous customer',
        'testimonial' => '"TESTER MUNA BAGO INSTALL, PARA LEGIT CHECK"',
        'features' => [
            '80-85% heat rejection',
            '95-99% IR rejection',
            '99% UV-blocking protection',
            'Privacy / safety protection',
            'Skin care protection',
            'Clarity 100%',
            'Avoid dashboard fade',
            'Fast cooling',
            'Premium Nano ceramic',
            'Quality, affordable, durable'
        ],
        'images' => [
            [
                'url' => BASE_URL . '/assets/images/van_tint.png',
                'title' => 'DA64V Minivan Transformer - Front View',
                'type' => 'Front Windshield Tint Installation'
            ],
            [
                'url' => BASE_URL . '/assets/images/van_tint.png',
                'title' => 'DA64V Minivan Transformer - Side Profile',
                'type' => 'Side Windows Tint Installation'
            ],
            [
                'url' => BASE_URL . '/assets/images/van_tint.png',
                'title' => 'DA64V Minivan Transformer - Rear View',
                'type' => 'Rear Window Tint Installation'
            ],
            [
                'url' => BASE_URL . '/assets/images/van_tint.png',
                'title' => 'DA64V Minivan Transformer - Interior View',
                'type' => 'Interior View with Tint'
            ]
        ]
    ],
    'four_units_batch' => [
        'title' => '4 UNITS BATCH INSTALLATION - MULTIPLE VEHICLES',
        'badge' => 'CARTINT CARTINT CARTINT',
        'subtitle' => 'Done Cartint - 4 Units',
        'locations' => 'Dumoy / Inawayan Toril / Riverside, Calinan',
        'vehicles' => [
            ['name' => 'DA64 Transformer Minivan', 'tint' => 'Full wrapped around black series ceramic tint'],
            ['name' => 'DA17 Transformer Minivan', 'tint' => 'Full wrapped around black series ceramic tint'],
            ['name' => 'DA16 Pick Type', 'tint' => 'Full wrapped around black series ceramic tint'],
            ['name' => 'Multicab Scrum', 'tint' => 'Full wrapped around chameleon nano ceramic tint']
        ],
        'features' => [
            '80-85% heat rejection',
            '80-85% IR rejection',
            '99% UV-blocking protection',
            'Privacy / safety protection',
            'Skin care protection',
            'Clarity 100%',
            'Avoid dashboard fade',
            'Fast cooling',
            'Premium',
            'Nano ceramic',
            'Quality, affordable, durable'
        ],
        'images' => [
            [
                'url' => BASE_URL . '/assets/images/van_tint.png',
                'title' => 'DA64 Transformer Minivan',
                'type' => 'Black Series Ceramic Tint'
            ],
            [
                'url' => BASE_URL . '/assets/images/van_tint.png',
                'title' => 'DA17 Transformer Minivan',
                'type' => 'Black Series Ceramic Tint'
            ],
            [
                'url' => BASE_URL . '/assets/images/suv_tint.png',
                'title' => 'DA16 Pick Type',
                'type' => 'Black Series Ceramic Tint'
            ],
            [
                'url' => BASE_URL . '/assets/images/sedan_tint.png',
                'title' => 'Multicab Scrum',
                'type' => 'Chameleon Nano Ceramic Tint'
            ]
        ]
    ],
    'two_units_suv' => [
        'title' => '2 UNITS SUV INSTALLATION - FORD EVEREST & TOYOTA FORTUNER',
        'badge' => '⭐️ CARTINT ⭐️ CARTINT ⭐️ CARTINT ⭐️',
        'subtitle' => '2 Units Done Cartint 💯',
        'referral' => 'REFERRAL from previous customer',
        'thank_you' => 'thank you po bossing ❤️',
        'testimonial' => '"TESTER MUNA BAGO INSTALL, PARA LEGIT CHECK"',
        'locations' => 'Diversion Orange Grove / Maa, Davao City',
        'tint_type' => 'Full wrapped around black series nano ceramic tint',
        'vehicles' => [
            ['name' => 'Ford Everest Titanium', 'tint' => 'Full wrapped around black series nano ceramic tint'],
            ['name' => 'Toyota Fortuner', 'tint' => 'Full wrapped around black series nano ceramic tint']
        ],
        'features' => [
            '80-85% heat rejection',
            '95-99% IR rejection',
            '99% UV-blocking protection',
            'Privacy / safety protection',
            'Skin care protection',
            'Clarity 100%',
            'Avoid dashboard fade',
            'Fast cooling',
            'Premium',
            'Nano ceramic',
            'Quality, affordable, durable'
        ],
        'images' => [
            [
                'url' => BASE_URL . '/assets/images/suv_tint.png',
                'title' => 'Ford Everest Titanium - Front View',
                'type' => 'Black Series Nano Ceramic Tint'
            ],
            [
                'url' => BASE_URL . '/assets/images/suv_tint.png',
                'title' => 'Ford Everest Titanium - Side Profile',
                'type' => 'Black Series Nano Ceramic Tint'
            ],
            [
                'url' => BASE_URL . '/assets/images/suv_tint.png',
                'title' => 'Toyota Fortuner - Front View',
                'type' => 'Black Series Nano Ceramic Tint'
            ],
            [
                'url' => BASE_URL . '/assets/images/suv_tint.png',
                'title' => 'Toyota Fortuner - Side Profile',
                'type' => 'Black Series Nano Ceramic Tint'
            ]
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Gallery | RG CARTINTWORKS Davao</title>
    <meta name="description" content="View our gallery of professionally tinted vehicles in Davao. See before & after comparisons and book your tinting service.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,300&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">

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

        
        .navbar-custom {
            background: var(--ink) !important;
            border-bottom: 1px solid rgba(212,160,23,.15);
            padding: .9rem 0;
            transition: all .3s ease;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1030;
        }
        .navbar-custom.scrolled {
            padding: .55rem 0;
            background: rgba(10,10,10,.97) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom-color: rgba(212,160,23,.25);
            box-shadow: 0 4px 24px rgba(0,0,0,.35);
        }
        .navbar-custom.hide-nav {
            transform: translateY(-100%);
        }

        .navbar-brand-custom {
            display: flex;
            align-items: center;
            gap: .65rem;
            text-decoration: none;
            transition: transform .3s;
        }
        .navbar-brand-custom:hover { transform: translateY(-2px); }

        .nav-logo-icon {
            width: 36px; height: 36px;
            background: rgba(212,160,23,.12);
            border: 1px solid rgba(212,160,23,.3);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform .3s, background .3s;
        }
        .navbar-brand-custom:hover .nav-logo-icon {
            background: rgba(212,160,23,.22);
            transform: rotate(12deg);
        }

        .nav-brand-text {
            font-family: var(--font-display);
            font-size: 1.45rem;
            letter-spacing: .06em;
            color: var(--gold-light);
        }
        .nav-brand-badge {
            font-size: .65rem;
            font-weight: 700;
            background: var(--gold);
            color: var(--ink);
            padding: .2rem .55rem;
            border-radius: 50px;
            letter-spacing: .06em;
        }

        .nav-link-custom {
            font-weight: 500;
            font-size: .88rem;
            padding: .5rem 1rem !important;
            margin: 0 .15rem;
            border-radius: var(--radius-sm);
            color: rgba(255,255,255,.7) !important;
            transition: all .25s;
            position: relative;
            letter-spacing: .01em;
        }
        .nav-link-custom::after {
            content: '';
            position: absolute;
            bottom: 4px; left: 1rem; right: 1rem;
            height: 1.5px;
            background: var(--gold);
            transform: scaleX(0);
            border-radius: 2px;
            transition: transform .3s;
        }
        .nav-link-custom:hover {
            color: var(--gold-light) !important;
            background: rgba(212,160,23,.07);
        }
        .nav-link-custom:hover::after { transform: scaleX(1); }
        .nav-link-custom.active {
            color: var(--gold-light) !important;
            background: rgba(212,160,23,.1);
        }
        .nav-link-custom.active::after { transform: scaleX(1); }

        .navbar-toggler-custom {
            border: 1.5px solid rgba(212,160,23,.35);
            border-radius: var(--radius-sm);
            padding: .4rem .6rem;
            color: var(--gold-light);
            background: transparent;
            cursor: pointer;
            transition: all .25s;
        }
        .navbar-toggler-custom:hover {
            background: rgba(212,160,23,.12);
            border-color: var(--gold);
        }

        
        .nav-user-avatar {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ink);
            font-weight: 700;
            font-size: .82rem;
        }

        .btn-nav-book {
            background: var(--gold);
            color: var(--ink);
            border: none;
            padding: .5rem 1.2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: .85rem;
            transition: all .25s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            box-shadow: var(--shadow-gold);
        }
        .btn-nav-book:hover {
            background: var(--gold-light);
            color: var(--ink);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(212,160,23,.4);
        }
        .btn-nav-signin {
            background: transparent;
            color: rgba(255,255,255,.75);
            border: 1.5px solid rgba(255,255,255,.2);
            padding: .5rem 1.1rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: .85rem;
            transition: all .25s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
        }
        .btn-nav-signin:hover {
            border-color: rgba(255,255,255,.5);
            color: var(--white);
            background: rgba(255,255,255,.06);
        }

        @media (max-width: 992px) {
            .navbar-collapse-custom {
                background: var(--ink-soft);
                border-top: 1px solid rgba(212,160,23,.12);
                padding: 1rem;
                border-radius: 0 0 var(--radius-md) var(--radius-md);
                margin-top: .5rem;
            }
            .nav-link-custom { margin: .15rem 0; }
            .navbar-actions { margin-top: .75rem; padding-top: .75rem; border-top: 1px solid rgba(255,255,255,.07); }
        }

        
        .hero-section {
            background:
                linear-gradient(to right, rgba(10,10,10,.92) 42%, rgba(10,10,10,.55) 100%),
                url('https://images.unsplash.com/photo-1544636331-e26879cd4d9b?auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            min-height: 58vh;
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

        
        .album-card-new {
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
            cursor: pointer;
        }
        .album-card-new:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
            border-color: var(--gold);
        }
        .album-card-badge {
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
        .album-cover-new {
            position: relative;
            height: 220px;
            overflow: hidden;
        }
        .album-cover-new img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .6s cubic-bezier(.4,0,.2,1);
        }
        .album-card-new:hover .album-cover-new img { transform: scale(1.08); }
        .album-cover-overlay {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            background: linear-gradient(transparent, rgba(10,10,10,.85));
            color: var(--white);
            padding: 1.8rem 1.4rem 1rem;
            transform: translateY(100%);
            transition: transform .4s ease;
        }
        .album-card-new:hover .album-cover-overlay { transform: translateY(0); }
        .album-cover-overlay h5 { font-size: .9rem; color: var(--gold-light); margin-bottom: .2rem; }
        .album-cover-overlay p { font-size: .78rem; opacity: .8; margin: 0; }

        .album-info-new {
            padding: 1.6rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .album-info-new h3 {
            font-size: .95rem;
            font-weight: 600;
            color: var(--ink);
            line-height: 1.4;
            margin-bottom: .5rem;
            flex: 1;
        }
        .album-tint-label {
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: .5rem;
        }
        .album-photo-count {
            font-size: .8rem;
            color: var(--text-dim);
            display: flex;
            align-items: center;
            gap: .4rem;
        }
        .album-photo-count i { color: var(--gold); }

        
        .filter-wrap {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 1.8rem 2rem;
            box-shadow: var(--shadow-lg);
            border: 1.5px solid var(--smoke-dark);
            margin-bottom: 3rem;
        }
        .filter-wrap h4 {
            font-family: var(--font-display);
            font-size: 1.4rem;
            letter-spacing: .06em;
            color: var(--ink);
            margin-bottom: 1.2rem;
        }
        .filter-btn-new {
            border: 1.5px solid var(--smoke-dark);
            background: transparent;
            color: var(--text-dim);
            padding: .6rem 1.2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: .82rem;
            letter-spacing: .02em;
            transition: all .25s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
        }
        .filter-btn-new:hover,
        .filter-btn-new.active {
            background: var(--gold);
            color: var(--ink);
            border-color: var(--gold);
            transform: translateY(-2px);
            box-shadow: var(--shadow-gold);
        }

        
        .gallery-item {
            margin-bottom: 2rem;
            transition: all .4s cubic-bezier(.4,0,.2,1);
        }
        .gallery-item.hidden {
            opacity: 0;
            transform: translateY(20px);
            display: none !important;
        }
        .gallery-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1.5px solid var(--smoke-dark);
            transition: all .4s cubic-bezier(.4,0,.2,1);
            height: 100%;
        }
        .gallery-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--gold);
        }
        .gallery-img-wrap {
            position: relative;
            overflow: hidden;
        }
        .gallery-img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform .5s cubic-bezier(.4,0,.2,1);
            display: block;
        }
        .gallery-card:hover .gallery-img { transform: scale(1.07); }
        .gallery-img-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(10,10,10,.75), transparent 55%);
            opacity: 0;
            transition: opacity .3s;
            display: flex;
            align-items: flex-end;
            padding: 1.2rem;
        }
        .gallery-card:hover .gallery-img-overlay { opacity: 1; }
        .gallery-overlay-text { transform: translateY(12px); transition: transform .3s; color: var(--white); }
        .gallery-card:hover .gallery-overlay-text { transform: translateY(0); }
        .gallery-overlay-text span {
            font-size: .78rem;
            background: var(--gold);
            color: var(--ink);
            padding: .3rem .8rem;
            border-radius: 50px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }
        .gallery-caption-new {
            padding: 1.3rem 1.5rem;
        }
        .gallery-caption-new h5 {
            font-size: .9rem;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: .3rem;
            line-height: 1.4;
        }
        .gallery-caption-new p {
            font-size: .8rem;
            color: var(--text-dim);
            margin-bottom: .8rem;
            line-height: 1.5;
        }
        .gallery-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .gallery-cat-badge {
            background: var(--ink);
            color: var(--white);
            font-size: .72rem;
            font-weight: 600;
            padding: .25rem .75rem;
            border-radius: var(--radius-xs);
            letter-spacing: .04em;
        }
        .gallery-year-badge {
            background: var(--smoke);
            color: var(--text-dim);
            font-size: .72rem;
            font-weight: 500;
            padding: .25rem .65rem;
            border-radius: var(--radius-xs);
            border: 1px solid var(--smoke-dark);
        }
        .gallery-tint-tag {
            background: var(--gold);
            color: var(--ink);
            font-size: .72rem;
            font-weight: 700;
            padding: .25rem .75rem;
            border-radius: 50px;
            letter-spacing: .03em;
        }

        
        .stat-card {
            text-align: center;
            padding: 2.5rem 1.5rem;
            border: 1px solid rgba(255,255,255,.07);
            border-radius: var(--radius-lg);
            transition: border-color .3s, background .3s;
        }
        .stat-card:hover {
            border-color: rgba(212,160,23,.3);
            background: rgba(212,160,23,.04);
        }
        .stat-icon {
            width: 60px; height: 60px;
            background: var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.2rem;
            color: var(--ink);
        }
        .stat-number {
            font-family: var(--font-display);
            font-size: 4rem;
            color: var(--gold-light);
            line-height: 1;
            letter-spacing: .02em;
            margin-bottom: .3rem;
        }
        .stat-label { font-size: .95rem; font-weight: 600; color: var(--white); margin-bottom: .3rem; }
        .stat-unit { font-size: .75rem; color: rgba(255,255,255,.35); letter-spacing: .1em; text-transform: uppercase; }

        
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
            display: flex; align-items: center; justify-content: center;
            color: var(--gold);
            font-size: 1.1rem;
            margin-bottom: 1.2rem;
        }
        .benefit-cell h5 { font-size: .98rem; font-weight: 600; margin-bottom: .4rem; }
        .benefit-cell p { font-size: .86rem; color: var(--text-dim); line-height: 1.65; margin: 0; }

        
        .compare-card-new {
            background: rgba(255,255,255,.04);
            border-radius: var(--radius-lg);
            padding: 2rem;
            height: 100%;
            border: 1px solid rgba(255,255,255,.07);
            transition: all .3s;
        }
        .compare-card-new:hover {
            border-color: rgba(212,160,23,.3);
            background: rgba(212,160,23,.04);
        }
        .compare-img-new {
            width: 100%;
            height: 230px;
            object-fit: cover;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            border: 2px solid transparent;
            transition: border-color .3s;
        }
        .compare-card-new:hover .compare-img-new { border-color: var(--gold); }
        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .75rem 0;
            border-bottom: 1px solid rgba(255,255,255,.07);
            font-size: .88rem;
        }
        .stat-row:last-child { border: none; }
        .stat-row span { color: rgba(255,255,255,.65); }

        
        .cta-section { background: var(--gold); padding: 5.5rem 0; }
        .cta-section h2 {
            font-family: var(--font-display);
            font-size: clamp(2.2rem, 4vw, 3.5rem);
            color: var(--ink);
            letter-spacing: .04em;
        }
        .btn-cta-dark {
            display: inline-flex; align-items: center; gap: .5rem;
            background: var(--ink); color: var(--white);
            padding: .9rem 2rem; border-radius: 50px;
            font-weight: 600; text-decoration: none; font-size: 1rem;
            transition: all .25s;
        }
        .btn-cta-dark:hover { background: var(--ink-soft); color: var(--white); transform: translateY(-2px); }
        .btn-cta-outline {
            display: inline-flex; align-items: center; gap: .5rem;
            background: transparent; color: var(--ink);
            border: 2px solid var(--ink);
            padding: .9rem 2rem; border-radius: 50px;
            font-weight: 600; text-decoration: none; font-size: 1rem;
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
            display: flex; flex-direction: column; gap: 10px; align-items: flex-end;
            max-height: 0; overflow: hidden; opacity: 0; transform: translateY(16px);
            transition: all .4s cubic-bezier(.4,0,.2,1);
            order: 1;
        }
        .fab-secondary-container.show {
            max-height: 600px; opacity: 1; transform: translateY(0);
            margin-bottom: 14px;
        }
        .fab-button {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            padding: .75rem 1.4rem; border-radius: 50px;
            font-weight: 700; font-size: .88rem; text-decoration: none;
            box-shadow: var(--shadow-lg); border: none; cursor: pointer;
            transition: all .3s cubic-bezier(.4,0,.2,1);
            position: relative; overflow: hidden;
            min-width: 165px; z-index: 1; letter-spacing: .02em;
        }
        .fab-button:hover { transform: translateY(-4px) scale(1.04); box-shadow: var(--shadow-xl); text-decoration: none; }
        .fab-icon { width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,.18); border-radius: 50%; font-size: 1rem; flex-shrink: 0; }
        .fab-text { white-space: nowrap; font-size: .85rem; }
        .fab-badge {
            position: absolute; top: -7px; right: -7px;
            background: #e53e3e; color: var(--white);
            padding: 2px 8px; border-radius: 50px; font-size: .65rem; font-weight: 800;
            border: 2px solid var(--white); animation: badgePulse 1.5s infinite;
        }
        @keyframes badgePulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.12)} }

        .fab-toggle {
            width: 52px; height: 52px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; cursor: pointer; box-shadow: var(--shadow-lg);
            border: 1.5px solid rgba(212,160,23,.35);
            transition: all .3s; order: 3;
            background: var(--ink); color: var(--gold-light);
        }
        .fab-toggle:hover { transform: rotate(90deg) scale(1.1); background: var(--ink-soft); border-color: var(--gold); }
        .fab-toggle.rotate { transform: rotate(45deg); }

        .fab-button.btn-warning { background: var(--gold); color: var(--ink); border: 1.5px solid rgba(212,160,23,.5); }
        .fab-button.btn-warning:hover { background: var(--gold-light); color: var(--ink); }
        .fab-button.btn-success { background: linear-gradient(135deg,#2ecc71,#27ae60); color: var(--white); }
        .fab-button.btn-primary { background: linear-gradient(135deg,#006AFF,#0084FF); color: var(--white); }

        .fab-main::before {
            content: ''; position: absolute; top: -5px; left: -5px; right: -5px; bottom: -5px;
            background: var(--gold); border-radius: 50px; z-index: -1; opacity: .3;
            animation: pulseRing 2.2s ease-out infinite;
        }
        @keyframes pulseRing { 0%{transform:scale(.95);opacity:.5} 100%{transform:scale(1.18);opacity:0} }

        .animate-pulse { animation: pulseSoft 2s ease-in-out infinite; }
        @keyframes pulseSoft { 0%,100%{transform:scale(1)} 50%{transform:scale(1.04)} }

        
        .modal-content {
            background: var(--ink-mid);
            color: var(--white);
            border-radius: var(--radius-lg);
            border: 1.5px solid rgba(212,160,23,.25);
        }
        .modal-header {
            border-bottom: 1px solid rgba(212,160,23,.2);
            padding: 1.4rem 1.8rem;
        }
        .modal-title {
            font-family: var(--font-display);
            font-size: 1.6rem;
            letter-spacing: .06em;
            color: var(--gold-light);
        }
        .modal-footer {
            border-top: 1px solid rgba(212,160,23,.2);
        }
        .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }

        .album-modal-tint {
            font-size: 1.4rem; font-weight: 700;
            color: var(--white); text-transform: uppercase;
            letter-spacing: 2px; margin-bottom: 1rem;
        }
        .modal-feature-tag {
            background: rgba(212,160,23,.15);
            border: 1px solid rgba(212,160,23,.3);
            color: var(--gold-light);
            padding: .4rem 1rem; border-radius: 50px;
            font-size: .8rem; font-weight: 600;
            display: inline-flex; align-items: center; gap: .4rem;
            margin: 3px;
        }
        .modal-referral {
            background: var(--gold); color: var(--ink);
            font-weight: 700; padding: .5rem 1.2rem; border-radius: 50px;
            display: inline-block; font-size: .85rem; margin: .4rem 0;
        }
        .modal-location {
            background: rgba(255,255,255,.08);
            color: rgba(255,255,255,.7);
            padding: .4rem 1rem; border-radius: 50px;
            font-size: .82rem; display: inline-block; margin: .4rem 0;
        }
        .modal-testimonial {
            background: transparent;
            border: 1.5px solid rgba(212,160,23,.4);
            color: var(--gold-light);
            padding: .6rem 1.2rem; border-radius: 50px;
            font-size: .85rem; font-style: italic; font-weight: 600;
            display: inline-block; margin: .4rem 0;
        }

        .album-grid-modal {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1.2rem;
        }
        .album-grid-item {
            position: relative;
            border-radius: var(--radius-md);
            overflow: hidden;
            aspect-ratio: 4/3;
            cursor: pointer;
            border: 1.5px solid rgba(255,255,255,.07);
            transition: all .3s;
        }
        .album-grid-item:hover {
            border-color: var(--gold);
            transform: translateY(-4px) scale(1.02);
            box-shadow: var(--shadow-gold);
        }
        .album-grid-item img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s; }
        .album-grid-item:hover img { transform: scale(1.08); }
        .album-grid-item .album-item-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(10,10,10,.85), transparent 55%);
            color: var(--white); padding: 1rem;
            display: flex; flex-direction: column; justify-content: flex-end;
            opacity: 0; transition: opacity .3s;
        }
        .album-grid-item:hover .album-item-overlay { opacity: 1; }
        .album-grid-item .album-item-overlay h5 { font-size: .82rem; color: var(--gold-light); margin-bottom: .15rem; }
        .album-grid-item .album-item-overlay p { font-size: .73rem; opacity: .75; margin: 0; }

        
        footer.bg-dark { background: var(--ink) !important; border-top: 1px solid rgba(255,255,255,.06); }
        footer h4.text-warning { color: var(--gold-light) !important; font-family: var(--font-display); letter-spacing: .06em; font-size: 1.7rem; }
        footer .text-warning { color: var(--gold-light) !important; }
        footer a.hover-warning:hover { color: var(--gold-light) !important; }
        .social-icon {
            width: 36px; height: 36px;
            border: 1px solid rgba(255,255,255,.15); border-radius: var(--radius-xs);
            display: inline-flex; align-items: center; justify-content: center;
            color: rgba(255,255,255,.5); text-decoration: none; margin-right: .4rem;
            transition: all .2s; font-size: .85rem;
        }
        .social-icon:hover { border-color: var(--gold); color: var(--gold); }

        
        @keyframes fadeInUp {
            from { opacity:0; transform:translateY(32px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .animate-fadeInUp { animation: fadeInUp .75s ease-out; }

        
        #hidden-lightbox-links { display: none; }

        
        @media (max-width: 1100px) {
            .benefit-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .hero-section { padding-top: 120px; min-height: auto; padding-bottom: 4rem; }
            .hero-title { font-size: clamp(2.8rem, 12vw, 5rem); }
            .album-grid-modal { grid-template-columns: 1fr; }
            .floating-action-buttons { bottom: 15px; right: 15px; }
        }
        @media (max-width: 576px) {
            .section { padding: 3.5rem 0; }
            .benefit-grid { grid-template-columns: 1fr; }
            .btn-primary-custom, .btn-secondary-custom { width: 100%; justify-content: center; margin-bottom: .5rem; }
            .fab-button { min-width: 130px; padding: .65rem 1rem; font-size: .82rem; }
            .filter-wrap { padding: 1.2rem; }
        }
        @media (max-width: 400px) {
            .fab-text { display: none; }
            .fab-button { width: 48px !important; height: 48px !important; border-radius: 50% !important; padding: 0 !important; min-width: auto !important; }
        }
        @media (hover: none) and (pointer: coarse) {
            .album-card-new:hover { transform: none; }
            .gallery-card:hover { transform: none; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<section class="hero-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 col-xl-7">
                <div class="hero-content animate-fadeInUp">
                    <nav class="breadcrumb-wrap">
                        <a href="../index.php"><i class="fas fa-home"></i> Home</a>
                        <i class="fas fa-chevron-right"></i>
                        <span style="color:rgba(255,255,255,.5);">Gallery</span>
                    </nav>

                    <div class="hero-badge">
                        <div class="hero-badge-dot"></div>
                        <span>Satisfied Customers Gallery</span>
                    </div>

                    <h1 class="hero-title">
                        OUR WORK
                        <span class="gold-accent">speaks for itself</span>
                    </h1>

                    <p class="hero-subtitle">
                        Explore our collection of professionally tinted vehicles from Davao's premier window tinting specialist. Real results, real customers.
                    </p>

                    <div class="d-flex flex-wrap gap-3">
                        <a href="#featured-albums" class="btn-primary-custom">
                            <i class="fas fa-star"></i> View Featured
                        </a>
                        <a href="../book.php" class="btn-secondary-custom">
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
        <span class="ribbon-item"><i class="fas fa-camera"></i> <?= count($gallery_items) + 9 ?>+ SATISFIED CUSTOMERS FEATURED</span>
        <span class="ribbon-item"><i class="fas fa-shield-alt"></i> 5-Year Warranty INCLUDED</span>
        <span class="ribbon-item"><i class="fas fa-sun"></i> 99% HEAT & UV REJECTION</span>
        <span class="ribbon-item"><i class="fas fa-bolt"></i> BOOK NOW — 30% OFF CERAMIC TINTS</span>
        <span class="ribbon-item"><i class="fas fa-map-marker-alt"></i> DAVAO · TAGUM · DIGOS · PANABO · CARMEN</span>
        <span class="ribbon-item"><i class="fas fa-camera"></i> <?= count($gallery_items) + 9 ?>+ SATISFIED CUSTOMERS FEATURED</span>
        <span class="ribbon-item"><i class="fas fa-shield-alt"></i> 5-Year Warranty INCLUDED</span>
        <span class="ribbon-item"><i class="fas fa-sun"></i> 99% HEAT & UV REJECTION</span>
        <span class="ribbon-item"><i class="fas fa-bolt"></i> BOOK NOW — 30% OFF CERAMIC TINTS</span>
        <span class="ribbon-item"><i class="fas fa-map-marker-alt"></i> DAVAO · TAGUM · DIGOS · PANABO · CARMEN</span>
    </div>
</div>

<div id="hidden-lightbox-links"></div>

<section id="featured-albums" class="section" style="background:var(--smoke);">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6">
                <div class="section-eyebrow">Featured</div>
                <h2 class="section-title">Featured <em>Installations</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle">Click on any album card to view the full installation details and photos</p>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-md-6 col-lg-4">
                <div class="album-card-new" onclick="showAlbum('nissan_terra')">
                    <div class="album-card-badge">Premium</div>
                    <div class="album-cover-new">
                        <img src="<?= $featured_albums['nissan_terra']['images'][0]['url'] ?>" alt="Nissan Terra">
                        <div class="album-cover-overlay">
                            <h5><i class="fas fa-expand-alt me-1"></i> View Album</h5>
                            <p><?= count($featured_albums['nissan_terra']['images']) ?> photos</p>
                        </div>
                    </div>
                    <div class="album-info-new">
                        <div class="album-tint-label"><i class="fas fa-gem me-1"></i><?= $featured_albums['nissan_terra']['tint_name'] ?></div>
                        <h3><?= $featured_albums['nissan_terra']['title'] ?></h3>
                        <div class="album-photo-count"><i class="fas fa-images"></i> <?= count($featured_albums['nissan_terra']['images']) ?> photos in album</div>
                    </div>
                </div>
            </div>

            
            <div class="col-md-6 col-lg-4">
                <div class="album-card-new" onclick="showAlbum('ford_everest')">
                    <div class="album-card-badge">Premium</div>
                    <div class="album-cover-new">
                        <img src="<?= $featured_albums['ford_everest']['images'][0]['url'] ?>" alt="Ford Everest">
                        <div class="album-cover-overlay">
                            <h5><i class="fas fa-expand-alt me-1"></i> View Album</h5>
                            <p><?= count($featured_albums['ford_everest']['images']) ?> photos</p>
                        </div>
                    </div>
                    <div class="album-info-new">
                        <div class="album-tint-label"><i class="fas fa-gem me-1"></i><?= $featured_albums['ford_everest']['tint_name'] ?></div>
                        <h3><?= $featured_albums['ford_everest']['title'] ?></h3>
                        <div class="album-photo-count"><i class="fas fa-images"></i> <?= count($featured_albums['ford_everest']['images']) ?> photos in album</div>
                    </div>
                </div>
            </div>

            
            <div class="col-md-6 col-lg-4">
                <div class="album-card-new" onclick="showAlbum('da64v_minivan')">
                    <div class="album-card-badge">Featured</div>
                    <div class="album-cover-new">
                        <img src="<?= $featured_albums['da64v_minivan']['images'][0]['url'] ?>" alt="DA64V Minivan">
                        <div class="album-cover-overlay">
                            <h5><i class="fas fa-expand-alt me-1"></i> View Album</h5>
                            <p><?= count($featured_albums['da64v_minivan']['images']) ?> photos</p>
                        </div>
                    </div>
                    <div class="album-info-new">
                        <div class="album-tint-label"><i class="fas fa-gem me-1"></i><?= $featured_albums['da64v_minivan']['tint_name'] ?></div>
                        <h3><?= $featured_albums['da64v_minivan']['title'] ?></h3>
                        <div class="album-photo-count"><i class="fas fa-images"></i> <?= count($featured_albums['da64v_minivan']['images']) ?> photos in album</div>
                    </div>
                </div>
            </div>

            
            <div class="col-md-6 col-lg-6">
                <div class="album-card-new" onclick="showAlbum('four_units_batch')">
                    <div class="album-card-badge">Batch</div>
                    <div class="album-cover-new" style="height:260px;">
                        <img src="<?= $featured_albums['four_units_batch']['images'][0]['url'] ?>" alt="4 Units Batch">
                        <div class="album-cover-overlay">
                            <h5><i class="fas fa-expand-alt me-1"></i> View Album</h5>
                            <p><?= count($featured_albums['four_units_batch']['images']) ?> photos · 4 vehicles</p>
                        </div>
                    </div>
                    <div class="album-info-new">
                        <div class="album-tint-label"><i class="fas fa-layer-group me-1"></i>BATCH INSTALLATION</div>
                        <h3><?= $featured_albums['four_units_batch']['title'] ?></h3>
                        <div class="album-photo-count"><i class="fas fa-car"></i> 4 vehicles &nbsp;·&nbsp; <i class="fas fa-images"></i> <?= count($featured_albums['four_units_batch']['images']) ?> photos</div>
                    </div>
                </div>
            </div>

            
            <div class="col-md-6 col-lg-6">
                <div class="album-card-new" onclick="showAlbum('two_units_suv')">
                    <div class="album-card-badge">Premium</div>
                    <div class="album-cover-new" style="height:260px;">
                        <img src="<?= $featured_albums['two_units_suv']['images'][0]['url'] ?>" alt="2 Units SUV">
                        <div class="album-cover-overlay">
                            <h5><i class="fas fa-expand-alt me-1"></i> View Album</h5>
                            <p><?= count($featured_albums['two_units_suv']['images']) ?> photos · 2 SUVs</p>
                        </div>
                    </div>
                    <div class="album-info-new">
                        <div class="album-tint-label"><i class="fas fa-star me-1"></i>NANO CERAMIC — BLACK SERIES</div>
                        <h3><?= $featured_albums['two_units_suv']['title'] ?></h3>
                        <div class="album-photo-count"><i class="fas fa-car"></i> 2 SUVs &nbsp;·&nbsp; <i class="fas fa-images"></i> <?= count($featured_albums['two_units_suv']['images']) ?> photos</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="gallery" class="section" style="background:var(--ink);">
    <div class="container">
        <div class="row align-items-end mb-4">
            <div class="col-lg-6">
                <div class="section-eyebrow">Portfolio</div>
                <h2 class="section-title">More of <em>Our Work</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle">Browse through our collection of professionally tinted vehicles. Click any image to view larger.</p>
            </div>
        </div>

        
        <div class="filter-wrap">
            <h4><i class="fas fa-filter me-2" style="color:var(--gold);"></i>FILTER BY VEHICLE TYPE</h4>
            <div class="d-flex flex-wrap gap-2" id="filterButtons">
                <button class="filter-btn-new active" data-filter="all" onclick="filterGallery('all')">
                    <i class="fas fa-th-large"></i> All Vehicles
                </button>
                <button class="filter-btn-new" data-filter="sedan" onclick="filterGallery('sedan')">
                    <i class="fas fa-car"></i> Sedans
                </button>
                <button class="filter-btn-new" data-filter="suv" onclick="filterGallery('suv')">
                    <i class="fas fa-truck"></i> SUVs
                </button>
                <button class="filter-btn-new" data-filter="pickup" onclick="filterGallery('pickup')">
                    <i class="fas fa-truck-pickup"></i> Pickups
                </button>
                <button class="filter-btn-new" data-filter="van" onclick="filterGallery('van')">
                    <i class="fas fa-shuttle-van"></i> Vans
                </button>
                <button class="filter-btn-new" data-filter="luxury" onclick="filterGallery('luxury')">
                    <i class="fas fa-gem"></i> Luxury
                </button>
            </div>
        </div>

        
        <div class="row" id="galleryContainer">
            <?php foreach ($gallery_items as $index => $item): ?>
            <div class="col-md-6 col-lg-4 col-xl-3 gallery-item" data-category="<?= $item['category'] ?>">
                <div class="gallery-card">
                    <div class="gallery-img-wrap">
                        <a href="<?= $item['image'] ?>"
                           data-lightbox="gallery-set"
                           data-title="<?= htmlspecialchars($item['title']) ?>"
                           class="lightbox-link">
                            <img src="<?= $item['image'] ?>"
                                 alt="<?= htmlspecialchars($item['title']) ?>"
                                 class="gallery-img">
                            <div class="gallery-img-overlay">
                                <div class="gallery-overlay-text">
                                    <span><i class="fas fa-expand-alt"></i> View Larger</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="gallery-caption-new">
                        <h5><?= htmlspecialchars($item['title']) ?></h5>
                        <p><?= htmlspecialchars($item['description']) ?></p>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="gallery-cat-badge"><?= displayCategory($item['category']) ?></span>
                            <span class="gallery-year-badge"><?= htmlspecialchars($item['year']) ?></span>
                        </div>
                        <span class="gallery-tint-tag"><?= htmlspecialchars($item['tint_type']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-dark">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6">
                <div class="section-eyebrow" style="color:rgba(255,255,255,.4);">By the Numbers</div>
                <h2 class="section-title section-title-light">Our <em>Track Record</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle section-subtitle-light">Consistent quality results trusted by hundreds of Davao vehicle owners</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-images fa-lg" style="color:var(--ink);"></i></div>
                    <div class="stat-number"><?= count($gallery_items) + 9 ?>+</div>
                    <div class="stat-label">Featured Installs</div>
                    <div class="stat-unit">In Gallery</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-car fa-lg" style="color:var(--ink);"></i></div>
                    <div class="stat-number">5</div>
                    <div class="stat-label">Vehicle Categories</div>
                    <div class="stat-unit">All Types Covered</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-star fa-lg" style="color:var(--ink);"></i></div>
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Satisfied Clients</div>
                    <div class="stat-unit">Customer Rating</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-alt fa-lg" style="color:var(--ink);"></i></div>
                    <div class="stat-number">2023–<?= date('Y') ?></div>
                    <div class="stat-label">Gallery Years</div>
                    <div class="stat-unit">Active Since</div>
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
                <h2 class="section-title">See the <em>Difference</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle">Every vehicle in our gallery enjoys these proven benefits</p>
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
                <div class="section-eyebrow" style="color:rgba(255,255,255,.4);">Comparison</div>
                <h2 class="section-title section-title-light">Before <em>&</em> After</h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle section-subtitle-light">See the dramatic difference professional tinting makes</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="compare-card-new">
                    <img src="https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=800&q=80"
                         alt="Before Tinting" class="compare-img-new">
                    <h3 style="color:var(--gold-light);margin-bottom:1.2rem;font-family:var(--font-display);font-size:1.6rem;letter-spacing:.04em;">
                        <i class="fas fa-times-circle me-2" style="color:#e53e3e;"></i>BEFORE TINTING
                    </h3>
                    <div class="stat-row"><span><i class="fas fa-thermometer-half me-2"></i>Interior Temperature</span><strong style="color:#e53e3e;">45–50°C</strong></div>
                    <div class="stat-row"><span><i class="fas fa-sun me-2"></i>Glare Reduction</span><strong style="color:rgba(255,255,255,.4);">0%</strong></div>
                    <div class="stat-row"><span><i class="fas fa-shield me-2"></i>UV Protection</span><strong style="color:rgba(255,255,255,.4);">0%</strong></div>
                    <div class="stat-row"><span><i class="fas fa-eye me-2"></i>Privacy</span><strong style="color:rgba(255,255,255,.4);">None</strong></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="compare-card-new" style="border-color:rgba(212,160,23,.3);">
                    <img src="https://images.unsplash.com/photo-1544636331-e26879cd4d9b?auto=format&fit=crop&w=800&q=80"
                         alt="After Tinting" class="compare-img-new">
                    <h3 style="color:var(--gold-light);margin-bottom:1.2rem;font-family:var(--font-display);font-size:1.6rem;letter-spacing:.04em;">
                        <i class="fas fa-check-circle me-2" style="color:#22c55e;"></i>AFTER TINTING
                    </h3>
                    <div class="stat-row"><span><i class="fas fa-thermometer-half me-2"></i>Interior Temperature</span><strong style="color:#22c55e;">28–32°C</strong></div>
                    <div class="stat-row"><span><i class="fas fa-sun me-2"></i>Glare Reduction</span><strong style="color:#22c55e;">Up to 95%</strong></div>
                    <div class="stat-row"><span><i class="fas fa-shield me-2"></i>UV Protection</span><strong style="color:#22c55e;">Up to 99%</strong></div>
                    <div class="stat-row"><span><i class="fas fa-eye me-2"></i>Privacy</span><strong style="color:#22c55e;">Enhanced</strong></div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <div style="background:rgba(212,160,23,.08);border:1px solid rgba(212,160,23,.2);border-radius:var(--radius-md);padding:1.2rem 2rem;display:inline-block;">
                <i class="fas fa-lightbulb me-2" style="color:var(--gold);"></i>
                <span style="color:rgba(255,255,255,.7);font-size:.9rem;">
                    <strong style="color:var(--white);">Did You Know?</strong> Professional window tinting can reduce your car's interior temperature by up to 60% and block 99% of harmful UV rays.
                </span>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h2>WANT YOUR CAR IN OUR GALLERY?</h2>
                <p class="mb-0 mt-2" style="color:rgba(10,10,10,.65);">Professional installation · 5-Year Warranty · Mobile service available</p>
                <div class="row g-4 mt-2">
                    <div class="col-4 text-center">
                        <i class="fas fa-camera fa-2x mb-2" style="color:var(--ink);opacity:.7;"></i>
                        <div style="font-size:.85rem;font-weight:600;color:var(--ink);">Share #RGCartintworks</div>
                    </div>
                    <div class="col-4 text-center">
                        <i class="fas fa-award fa-2x mb-2" style="color:var(--ink);opacity:.7;"></i>
                        <div style="font-size:.85rem;font-weight:600;color:var(--ink);">5-Year Warranty</div>
                    </div>
                    <div class="col-4 text-center">
                        <i class="fas fa-home fa-2x mb-2" style="color:var(--ink);opacity:.7;"></i>
                        <div style="font-size:.85rem;font-weight:600;color:var(--ink);">Mobile Service</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 text-lg-end d-flex gap-3 flex-wrap justify-content-lg-end">
                <a href="../book.php" class="btn-cta-dark"><i class="fas fa-calendar-check"></i> Book Appointment Now</a>
                <a href="tel:+09970628687" class="btn-cta-outline"><i class="fas fa-phone"></i> Call: +0997 062 8687</a>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="albumModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="albumModalLabel">Album Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="albumModalBody" style="padding:1.8rem;"></div>
            <div class="modal-footer">
                <button type="button" class="btn-primary-custom" style="border-radius:var(--radius-md);padding:.6rem 1.4rem;font-size:.85rem;" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<footer class="bg-dark text-white py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h4 class="fw-bold mb-3 text-warning" style="font-family:var(--font-display);letter-spacing:.06em;font-size:1.7rem;">RG CARTINTWORKS</h4>
                <p class="mb-4" style="color:rgba(255,255,255,.5);font-size:.88rem;line-height:1.75;">
                    Davao's trusted mobile window tinting specialist established in 2020. Professional installation with up to 5-Year Warranty using premium HD Carbon, Magic Chrome, and Nano Ceramic films.
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
                    <li class="mb-2"><a href="../services.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Car Window Tinting</a></li>
                    <li class="mb-2"><a href="../services.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">SUV Tinting</a></li>
                    <li class="mb-2"><a href="../services.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Tint Removal</a></li>
                    <li class="mb-2"><a href="../services.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Tint Repair</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6">
                <h5 style="font-size:.75rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-bottom:.9rem;">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="../index.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Home</a></li>
                    <li class="mb-2"><a href="index.php" style="font-size:.86rem;color:rgba(255,255,255,.9);text-decoration:none;font-weight:600;">Gallery</a></li>
                    <li class="mb-2"><a href="../services.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Services</a></li>
                    <li class="mb-2"><a href="../book.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Book Now</a></li>
                    <li class="mb-2"><a href="../index.php#feedback" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Feedback</a></li>
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
<?php include __DIR__ . '/../includes/chatbot.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>

<script>

lightbox.option({
    'resizeDuration': 300,
    'wrapAround': true,
    'albumLabel': "Image %1 of %2",
    'fadeDuration': 400,
    'imageFadeDuration': 300,
    'positionFromTop': 100,
    'alwaysShowNavOnTouchDevices': true
});

var featuredAlbums = <?= json_encode($featured_albums) ?>;

function createLightboxImages() {
    const hiddenContainer = document.getElementById('hidden-lightbox-links');
    if (!hiddenContainer) return;
    hiddenContainer.innerHTML = '';
    const albumIds = {
        'nissan_terra': 'album-nissan-terra',
        'ford_everest': 'album-ford-everest',
        'da64v_minivan': 'album-da64v-minivan',
        'four_units_batch': 'album-four-units-batch',
        'two_units_suv': 'album-two-units-suv'
    };
    for (const [key, albumId] of Object.entries(albumIds)) {
        const album = featuredAlbums[key];
        if (!album || !album.images) continue;
        album.images.forEach((image) => {
            const link = document.createElement('a');
            link.href = image.url;
            link.setAttribute('data-lightbox', albumId);
            link.setAttribute('data-title', image.title);
            hiddenContainer.appendChild(link);
        });
    }
}

function openAlbumLightbox(albumKey, imageIndex = 0) {
    const albumIds = {
        'nissan_terra': 'album-nissan-terra',
        'ford_everest': 'album-ford-everest',
        'da64v_minivan': 'album-da64v-minivan',
        'four_units_batch': 'album-four-units-batch',
        'two_units_suv': 'album-two-units-suv'
    };
    const albumId = albumIds[albumKey];
    if (!albumId) return;
    const links = document.querySelectorAll(`[data-lightbox="${albumId}"]`);
    if (links && links.length > imageIndex) links[imageIndex].click();
}

function showAlbum(albumKey) {
    const album = featuredAlbums[albumKey];
    const modalLabel = document.getElementById('albumModalLabel');
    const modalBody = document.getElementById('albumModalBody');
    if (!album) return;

    modalLabel.textContent = album.title || 'Album Details';

    let infoHtml = '';
    if (album.tint_name) infoHtml += `<div style="font-family:var(--font-display);font-size:1.5rem;color:var(--white);letter-spacing:2px;text-transform:uppercase;margin-bottom:.8rem;">${album.tint_name}</div>`;
    if (album.description) infoHtml += `<p style="color:rgba(255,255,255,.6);margin-bottom:1rem;">${album.description}</p>`;
    if (album.location) infoHtml += `<span class="modal-location"><i class="fas fa-map-marker-alt me-1"></i>${album.location}</span> `;
    if (album.referral) infoHtml += `<span class="modal-referral"><i class="fas fa-star me-1"></i>${album.referral}</span> `;
    if (album.tint_type) infoHtml += `<p style="color:rgba(255,255,255,.65);font-size:.85rem;margin-top:.8rem;">${album.tint_type}</p>`;
    if (album.testimonial) infoHtml += `<div class="modal-testimonial mt-2">${album.testimonial}</div>`;

    let vehiclesHtml = '';
    if (album.vehicles && album.vehicles.length) {
        vehiclesHtml = `<div style="background:rgba(255,255,255,.04);border-radius:var(--radius-md);padding:1.2rem;margin:1rem 0;border:1px solid rgba(212,160,23,.15);">
            <h6 style="color:var(--gold-light);margin-bottom:.8rem;font-family:var(--font-display);letter-spacing:.06em;">VEHICLES IN THIS BATCH</h6>
            ${album.vehicles.map(v => `
                <div style="display:flex;align-items:flex-start;gap:.6rem;padding:.5rem 0;border-bottom:1px solid rgba(255,255,255,.06);">
                    <i class="fas fa-car" style="color:var(--gold);margin-top:.15rem;flex-shrink:0;"></i>
                    <div><strong style="color:var(--gold-light);font-size:.85rem;">${v.name}</strong><br><span style="color:rgba(255,255,255,.5);font-size:.78rem;font-style:italic;">${v.tint}</span></div>
                </div>`).join('')}
        </div>`;
    }

    let featuresHtml = '';
    if (album.features && album.features.length) {
        featuresHtml = `<div style="margin:1rem 0;">
            <h6 style="color:var(--gold-light);margin-bottom:.7rem;font-family:var(--font-display);letter-spacing:.06em;">KEY FEATURES</h6>
            <div class="d-flex flex-wrap">${album.features.map(f => `<span class="modal-feature-tag"><i class="fas fa-check-circle"></i>${f}</span>`).join('')}</div>
        </div>`;
    }

    const photosHtml = `
        <div style="margin-top:1.4rem;">
            <h6 style="color:var(--gold-light);margin-bottom:.8rem;font-family:var(--font-display);letter-spacing:.06em;">ALBUM PHOTOS <span style="color:rgba(255,255,255,.4);font-family:var(--font-body);font-size:.8rem;font-weight:400;">— click to enlarge</span></h6>
            <div class="album-grid-modal">
                ${album.images.map((image, index) => `
                    <div class="album-grid-item" onclick="openAlbumLightbox('${albumKey}', ${index})">
                        <img src="${image.url}" alt="${image.title}" loading="lazy">
                        <div class="album-item-overlay">
                            <h5>${image.title}</h5>
                            <p>${image.type}</p>
                        </div>
                    </div>`).join('')}
            </div>
            <div class="text-center mt-3">
                <button class="btn-primary-custom" style="border-radius:var(--radius-md);padding:.65rem 1.6rem;font-size:.88rem;" onclick="openAlbumLightbox('${albumKey}', 0)">
                    <i class="fas fa-play-circle"></i> Start Slideshow
                </button>
            </div>
        </div>`;

    modalBody.innerHTML = infoHtml + vehiclesHtml + featuresHtml + photosHtml;

    const modalEl = document.getElementById('albumModal');
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
}

function filterGallery(category) {
    document.querySelectorAll('.filter-btn-new').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-filter') === category) btn.classList.add('active');
    });
    document.querySelectorAll('.gallery-item').forEach((item, index) => {
        const shouldShow = category === 'all' || item.getAttribute('data-category') === category;
        setTimeout(() => {
            if (shouldShow) {
                item.classList.remove('hidden');
                item.style.display = 'block';
                item.style.animation = 'fadeInUp 0.6s ease forwards';
            } else {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                setTimeout(() => { item.classList.add('hidden'); }, 300);
            }
        }, index * 40);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    createLightboxImages();

    
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

    
    const navbar = document.getElementById('mainNavbar');
    let lastScrollTop = 0;

    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        
        if (scrollTop > 50) { navbar.classList.add('scrolled'); }
        else { navbar.classList.remove('scrolled'); }

        
        if (scrollTop > lastScrollTop && scrollTop > 120) {
            navbar.classList.add('hide-nav');
            
            fabContainer.style.opacity = '0.7';
            fabContainer.style.transform = 'translateY(18px)';
        } else {
            navbar.classList.remove('hide-nav');
            fabContainer.style.opacity = '1';
            fabContainer.style.transform = 'translateY(0)';
        }
        lastScrollTop = scrollTop;

        
        const scrollPosition = window.scrollY + window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        fabContainer.style.display = scrollPosition >= documentHeight - 50 ? 'none' : 'flex';
    });

    
    const mobileToggler = document.getElementById('mobileToggler');
    const mobileMenu = document.getElementById('mobileMenu');
    const togglerIcon = document.getElementById('togglerIcon');
    let menuOpen = false;

    mobileToggler.addEventListener('click', function() {
        menuOpen = !menuOpen;
        if (menuOpen) {
            mobileMenu.style.setProperty('display', 'block', 'important');
            requestAnimationFrame(() => {
                mobileMenu.style.maxHeight = mobileMenu.scrollHeight + 'px';
                mobileMenu.style.opacity = '1';
            });
            togglerIcon.classList.remove('fa-bars');
            togglerIcon.classList.add('fa-times');
            mobileToggler.setAttribute('aria-expanded', 'true');
        } else {
            mobileMenu.style.maxHeight = '0';
            mobileMenu.style.opacity = '0';
            setTimeout(() => { mobileMenu.style.setProperty('display', 'none', 'important'); }, 400);
            togglerIcon.classList.remove('fa-times');
            togglerIcon.classList.add('fa-bars');
            mobileToggler.setAttribute('aria-expanded', 'false');
        }
    });

    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fadeInUp');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.album-card-new, .gallery-card, .benefit-cell, .stat-card, .compare-card-new').forEach(el => {
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

    
    filterGallery('all');
});

window.filterGallery = filterGallery;
window.showAlbum = showAlbum;
window.openAlbumLightbox = openAlbumLightbox;
</script>

</body>
</html>