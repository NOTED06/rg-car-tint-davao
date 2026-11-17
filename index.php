<?php

require __DIR__ . '/includes/auth.php';
boot_session();

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && !isset($_GET['preview'])) {
    header("Location: admin/index.php#dashboard");
    exit;
}

require __DIR__ . '/includes/db.php';

$page_title = "RG CARTINTWORKS | Premium Window Tinting Davao";

$is_logged_in = isset($_SESSION['user_id']) || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);

try {
    $packages_stmt = $pdo->query("
        SELECT p.*, v.type_name, 
               COALESCE((SELECT MIN(price) FROM package_model_prices WHERE package_id = p.id AND price > 0), p.price) as display_price 
        FROM tint_packages p 
        LEFT JOIN vehicle_types v ON p.vehicle_type_id = v.id 
        WHERE p.is_active = 1 
        ORDER BY p.is_popular DESC, p.is_featured DESC, p.display_order ASC, p.name ASC
    ");
    $packages = $packages_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $packages = [];
    error_log("Error fetching packages: " . $e->getMessage());
}

$pkg_min_prices = [];
try {
    $min_rows = $pdo->query("SELECT package_id, MIN(price) as min_price FROM package_model_prices WHERE price > 0 GROUP BY package_id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($min_rows as $mr) $pkg_min_prices[(int)$mr['package_id']] = (float)$mr['min_price'];
} catch (Exception $e) {}

$pkg_single_models = [];
try {
    $single_rows = $pdo->query("SELECT package_id, MIN(car_model) AS car_model, COUNT(DISTINCT car_model) AS car_count FROM package_model_prices GROUP BY package_id HAVING car_count = 1")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($single_rows as $sr) {
        $pkg_single_models[(int)$sr['package_id']] = (string)$sr['car_model'];
    }
} catch (Exception $e) {}

$customer_reviews = [];
try {
    $reviews_stmt = $pdo->query("
        SELECT t.*, u.full_name as user_full_name 
        FROM testimonials t 
        LEFT JOIN users u ON t.user_id = u.id 
        WHERE t.is_approved = 1 
        ORDER BY t.created_at DESC 
        LIMIT 6
    ");
    $customer_reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $customer_reviews = [];
    error_log("Error fetching customer reviews: " . $e->getMessage());
}

if (empty($customer_reviews)) {
    $customer_reviews = [
        [
            'name' => 'Mark Anthony Santos',
            'vehicle' => 'Toyota Fortuner 2023',
            'tint_type' => 'Nano Ceramic Shield',
            'rating' => 5.0,
            'comment' => 'The heat rejection is amazing! Riding around Davao under the hot sun feels so much cooler now. Very clean installation and fast home service!',
            'would_recommend' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'name' => 'Dr. Karen Flores',
            'vehicle' => 'Honda Civic RS',
            'tint_type' => 'Hybrid Ceramic Series',
            'rating' => 5.0,
            'comment' => 'Excellent service! They came directly to my home in Matina and tinted my car in under 3 hours. Premium quality film and zero mobile signal block.',
            'would_recommend' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ],
        [
            'name' => 'Engr. Jerome Dela Cruz',
            'vehicle' => 'Ford Ranger Wildtrak',
            'tint_type' => 'HD Carbon Series',
            'rating' => 5.0,
            'comment' => 'Great value for money. HD Carbon gives my truck privacy and serious style without blinding me at night. 5-year warranty included too!',
            'would_recommend' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 week'))
        ]
    ];
}

try {
    $vehicle_types_stmt = $pdo->query("SELECT * FROM vehicle_types WHERE is_active = 1 ORDER BY type_name");
    $vehicle_types = $vehicle_types_stmt->fetchAll();
} catch (PDOException $e) {
    $vehicle_types = [];
    error_log("Error fetching vehicle types: " . $e->getMessage());
}

$packages_by_vehicle = [];
foreach ($packages as $package) {
    $vehicle_type_id = $package['vehicle_type_id'] ?? 0;
    if (!isset($packages_by_vehicle[$vehicle_type_id])) {
        $packages_by_vehicle[$vehicle_type_id] = [];
    }
    $packages_by_vehicle[$vehicle_type_id][] = $package;
}

$price_column = 'display_price';

$featured_packages = $packages;
if (empty($featured_packages)) {
    $featured_packages = [
        ['id' => 1, 'name' => 'HD Carbon Ceramic Tint - Black Series', 'description' => 'Premium carbon ceramic tint with excellent heat rejection', 'price' => 4000, 'original_price' => 6500, 'vehicle_type' => 'Sedan/Hatchback/Pick Up', 'features' => json_encode(['HD Carbon Ceramic Film', 'High Heat Rejection', '99% UV Protection', 'Black Series Finish', 'Official Warranty'])],
        ['id' => 6, 'name' => 'Magic Chrome Ceramic Tint', 'description' => 'Chrome effect ceramic tint for vans', 'price' => 6500, 'original_price' => 9000, 'vehicle_type' => 'Van', 'features' => json_encode(['Chrome Ceramic Film', 'Mirror Chrome Effect', 'High Heat Rejection', 'UV Protection', '3-Year Warranty'])],
        ['id' => 12, 'name' => 'Nano Ceramic Tint - Clear Bluish', 'description' => 'Clear bluish nano ceramic tint for vans', 'price' => 8000, 'original_price' => 10500, 'vehicle_type' => 'Van', 'features' => json_encode(['Nano Ceramic Film', 'Clear Bluish Tone', 'High Transparency', 'Heat Rejection', 'UV Protection', 'Official Warranty'])],
    ];
}

try {
    $tint_types_stmt = $pdo->query("
        SELECT t.*, 
               COALESCE((SELECT MIN(price) FROM package_model_prices WHERE LOWER(tint_type) = LOWER(t.name) OR LOWER(tint_type) = REPLACE(LOWER(t.name), ' ', '-')), 0) as min_price 
        FROM tint_types t 
        WHERE t.is_active = 1 
        ORDER BY t.price_multiplier ASC
    ");
    $db_tint_types = $tint_types_stmt->fetchAll();
} catch (PDOException $e) {
    $db_tint_types = [];
    error_log("Error fetching tint types: " . $e->getMessage());
}

$real_bookings_count = 0;
$real_max_heat = '99%';
$real_max_uv = '99%';
$real_max_warranty = '5 Years';
$real_avg_rating = 5.0;
$real_testimonials_count = 0;

try {
    $real_bookings_count = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    
    
    $tint_stats_stmt = $pdo->query("SELECT heat_rejection, uv_protection, warranty_years FROM tint_types WHERE is_active = 1");
    $max_heat_val = 0;
    $max_uv_val = 0;
    $max_warranty_val = 0;
    
    while($row = $tint_stats_stmt->fetch(PDO::FETCH_ASSOC)) {
        $heat = intval(str_replace('%', '', $row['heat_rejection']));
        if($heat > $max_heat_val) $max_heat_val = $heat;
        
        $uv = floatval(str_replace('%', '', $row['uv_protection']));
        if($uv > $max_uv_val) $max_uv_val = $uv;
        
        $warranty = $row['warranty_years'];
        $w_years = intval($warranty);
        if($w_years > $max_warranty_val) $max_warranty_val = $w_years;
    }
    
    if($max_heat_val > 0) $real_max_heat = $max_heat_val . '%';
    if($max_uv_val > 0) $real_max_uv = min(99, $max_uv_val) . '%';
    $real_max_warranty = ($max_warranty_val > 0 ? $max_warranty_val . ' Years' : '5 Years');
    
    
    $real_testimonials_count = $pdo->query("SELECT COUNT(*) FROM testimonials")->fetchColumn();
    $avg_rating_val = $pdo->query("SELECT AVG(rating) FROM testimonials")->fetchColumn();
    if($avg_rating_val > 0) $real_avg_rating = floatval($avg_rating_val);
} catch (PDOException $e) {
    error_log("Error fetching dynamic stats: " . $e->getMessage());
}

$gallery_items = [
    ['image' => BASE_URL . '/assets/images/sedan_tint.png', 'category' => 'sedan', 'title' => 'Toyota Vios - Ceramic Tint', 'tint_type' => 'Ceramic Pro', 'description' => 'Premium ceramic tint with 95% heat rejection'],
    ['image' => BASE_URL . '/assets/images/suv_tint.png', 'category' => 'pickup', 'title' => 'Toyota Hilux - Carbon Tint', 'tint_type' => 'Carbon Film', 'description' => 'Durable carbon film for work vehicles'],
    ['image' => BASE_URL . '/assets/images/van_tint.png', 'category' => 'van', 'title' => 'Toyota Hiace - Privacy Tint', 'tint_type' => 'Privacy Film', 'description' => 'Enhanced privacy for family vans'],
    ['image' => BASE_URL . '/assets/images/hero_car.png', 'category' => 'luxury', 'title' => 'BMW X5 - Ultra Ceramic', 'tint_type' => 'Ultra Ceramic', 'description' => 'Ultra premium ceramic with official warranty'],
    ['image' => BASE_URL . '/assets/images/sedan_tint.png', 'category' => 'sedan', 'title' => 'Honda Civic - Light Tint', 'tint_type' => 'Ceramic Lite', 'description' => 'Light tint for maximum visibility'],
    ['image' => BASE_URL . '/assets/images/hero_car.png', 'category' => 'luxury', 'title' => 'Mercedes-Benz - Elite Package', 'tint_type' => 'Elite Ceramic', 'description' => 'Full elite ceramic tint package'],
    ['image' => BASE_URL . '/assets/images/sedan_tint.png', 'category' => 'sedan', 'title' => 'Mazda 3 - Crystal Clear Tint', 'tint_type' => 'Crystal Clear', 'description' => 'Almost invisible with high heat rejection'],
    ['image' => BASE_URL . '/assets/images/hero_car.png', 'category' => 'luxury', 'title' => 'Audi Q7 - Premium Tint', 'tint_type' => 'Premium Ceramic', 'description' => 'Premium ceramic with 99% heat rejection'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="Davao's #1 window tinting specialist. Professional installation of 3M, Solar Gard, V-KOOL, XPEL, and premium ceramic tints with Official Warranty. Beat the heat with our exclusive offers!">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,300&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/landing.css?v=<?= time() ?>">
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 col-xl-7">
                <div class="hero-content animate-fadeInUp">
                    <div class="hero-badge">
                        <div class="hero-badge-dot"></div>
                        <span>Davao's #1 Window Tinting</span>
                    </div>

                    <h1 class="hero-title">
                        PREMIUM<br>WINDOW<br>TINTING
                        <span class="gold-accent">for your car</span>
                    </h1>

                    <p class="hero-subtitle">
                        Beat the Davao heat with professional window tinting. Get up to 99% UV protection,
                        superior heat rejection, and enhanced privacy for your vehicle.
                    </p>

                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <?php if ($is_logged_in): ?>
                            <a href="customer/book.php" class="btn-primary-custom">
                                <i class="fas fa-calendar-check"></i> Get Free Quote
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#signInModal">
                                <i class="fas fa-calendar-check"></i> Get Free Quote
                            </button>
                        <?php endif; ?>
                        <a href="#services" class="btn-secondary-custom">
                            <i class="fas fa-car"></i> View Services
                        </a>
                    </div>

                    <div class="hero-stats">
                        <div class="hero-stat">
                            <strong><?= $real_bookings_count > 0 ? $real_bookings_count . '+' : '12+' ?></strong>
                            <small>Cars Tinted</small>
                        </div>
                        <div class="hero-stat">
                            <strong><?= htmlspecialchars($real_max_uv) ?></strong>
                            <small>UV Blocked</small>
                        </div>
                        <div class="hero-stat">
                            <strong><?= is_numeric($real_max_warranty) ? $real_max_warranty . ' Yrs' : ($real_max_warranty === '5-Year' ? '∞' : htmlspecialchars($real_max_warranty)) ?></strong>
                            <small>Warranty</small>
                        </div>
                        <div class="hero-stat">
                            <strong>3hr</strong>
                            <small>Service Time</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="promo-ribbon">
    <div class="ribbon-track">
        <span class="ribbon-item"><i class="fas fa-home"></i> FREE HOME SERVICE WITHIN DAVAO CITY</span>
        <span class="ribbon-item"><i class="fas fa-shield-alt"></i> <?= htmlspecialchars(strtoupper($real_max_warranty)) ?> WARRANTY INCLUDED</span>
        <span class="ribbon-item"><i class="fas fa-sun"></i> <?= htmlspecialchars(strtoupper($real_max_heat)) ?> HEAT REJECTION</span>
        <span class="ribbon-item"><i class="fas fa-bolt"></i> BOOK NOW — 30% OFF CERAMIC TINTS</span>
        <span class="ribbon-item"><i class="fas fa-map-marker-alt"></i> DAVAO · TAGUM · DIGOS · PANABO · CARMEN</span>
        
        <span class="ribbon-item"><i class="fas fa-home"></i> FREE HOME SERVICE WITHIN DAVAO CITY</span>
        <span class="ribbon-item"><i class="fas fa-shield-alt"></i> <?= htmlspecialchars(strtoupper($real_max_warranty)) ?> WARRANTY INCLUDED</span>
        <span class="ribbon-item"><i class="fas fa-sun"></i> <?= htmlspecialchars(strtoupper($real_max_heat)) ?> HEAT REJECTION</span>
        <span class="ribbon-item"><i class="fas fa-bolt"></i> BOOK NOW — 30% OFF CERAMIC TINTS</span>
        <span class="ribbon-item"><i class="fas fa-map-marker-alt"></i> DAVAO · TAGUM · DIGOS · PANABO · CARMEN</span>
    </div>
</div>

<section class="section" style="background:var(--ink);">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6">
                <div class="section-eyebrow">Why Choose RG Cartint</div>
                <h2 class="section-title">The RG Cartint <em>Advantage</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle">Every installation backed by proven expertise, premium films, and true mobile convenience</p>
            </div>
        </div>
        <div class="benefit-grid">
            <div class="benefit-cell">
                <div class="benefit-icon"><i class="fas fa-calendar-alt"></i></div>
                <h5>5+ Years of Experience</h5>
                <p>Established 2020 with 500+ vehicles completed. Your car is always in highly skilled hands.</p>
            </div>
            <div class="benefit-cell">
                <div class="benefit-icon"><i class="fas fa-truck"></i></div>
                <h5>Mobile Home Service</h5>
                <p>We drive to you — Davao City, Tagum, Digos, Panabo, and Carmen. No need to visit a shop.</p>
            </div>
            <div class="benefit-cell">
                <div class="benefit-icon"><i class="fas fa-gem"></i></div>
                <h5>Premium Film Only</h5>
                <p>HD Carbon, Magic Chrome Ceramic, and Nano Ceramic series — top-tier materials every time.</p>
            </div>
            <div class="benefit-cell">
                <div class="benefit-icon"><i class="fas fa-shield-alt"></i></div>
                <h5>Up to Official Warranty</h5>
                <p>Every job comes with a written warranty. We stand behind every installation we complete.</p>
            </div>
            <div class="benefit-cell">
                <div class="benefit-icon"><i class="fas fa-sun"></i></div>
                <h5>99% UV Protection</h5>
                <p>Block harmful UV rays and cut interior heat dramatically. Protect your passengers and cabin.</p>
            </div>
            <div class="benefit-cell">
                <div class="benefit-icon"><i class="fas fa-clock"></i></div>
                <h5>Same-Day Completion</h5>
                <p>Most vehicles tinted in 2–4 hours. Book in the morning and drive tinted by afternoon.</p>
            </div>
        </div>
    </div>
</section>

<style>

.pkg-card-custom {
    background: #ffffff;
    border: 1.5px solid rgba(212, 160, 23, 0.3);
    border-radius: 16px;
    padding: 0;
    position: relative;
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}
.pkg-card-custom:hover {
    border-color: #d4a017;
    box-shadow: 0 12px 30px rgba(212, 160, 23, 0.2);
    transform: translateY(-5px);
}
.pkg-card-custom.pkg-popular {
    border-color: #d4a017;
    background: linear-gradient(180deg, #ffffff 75%, #FFFDF5 100%);
}
.pkg-badge-popular {
    position: absolute;
    top: 12px;
    left: 12px;
    z-index: 3;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: linear-gradient(135deg, #d4a017 0%, #b45309 100%);
    color: #ffffff;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    padding: 4px 12px;
    border-radius: 30px;
    box-shadow: 0 4px 12px rgba(180,83,9,0.35);
}
.pkg-card-img-wrap {
    position: relative;
    width: 100%;
    height: 185px;
    min-height: 185px;
    max-height: 185px;
    background: #0f172a;
    overflow: hidden;
}
.pkg-card-img-wrap img {
    width: 100%;
    height: 185px;
    object-fit: cover;
    object-position: center;
    display: block;
    transition: transform 0.4s ease;
}
.pkg-card-custom:hover .pkg-card-img-wrap img {
    transform: scale(1.06);
}
.pkg-floating-price {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 3;
    background: rgba(15, 23, 42, 0.88);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: #F5C842;
    font-size: 15px;
    font-weight: 800;
    padding: 5px 14px;
    border-radius: 30px;
    border: 1.5px solid rgba(245, 200, 66, 0.4);
    box-shadow: 0 4px 14px rgba(0,0,0,0.25);
}
.pkg-card-title {
    margin: 16px 18px 6px;
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.3;
    letter-spacing: -0.01em;
}
.pkg-card-description {
    margin: 0 18px 12px;
    font-size: 12.5px;
    color: #64748b;
    line-height: 1.5;
    min-height: 38px;
}
.pkg-card-specs {
    margin: 0 18px 14px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.pkg-spec-pill {
    font-size: 11px;
    font-weight: 600;
    background: #f8fafc;
    color: #334155;
    padding: 4px 10px;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.pkg-spec-pill i {
    color: #d4a017;
}
.btn-pkg-action-custom {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    margin: 14px 18px 18px 18px !important;
    padding: 12px 18px !important;
    border-radius: 10px !important;
    font-weight: 800 !important;
    font-size: 13px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    text-decoration: none !important;
    border: none !important;
    cursor: pointer !important;
    width: calc(100% - 36px) !important;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    margin-top: auto !important;
}
.btn-book-package-custom {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
    color: #F5C842 !important;
    border: 1px solid rgba(245, 200, 66, 0.35) !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.25) !important;
}
.btn-book-package-custom:hover {
    background: linear-gradient(135deg, #334155 0%, #1e293b 100%) !important;
    color: #FFFFFF !important;
    border-color: rgba(245, 200, 66, 0.6) !important;
}
.btn-book-car-custom {
    background: linear-gradient(135deg, #d4a017 0%, #b45309 100%) !important;
    color: #0f172a !important;
    border: 1px solid rgba(212, 160, 23, 0.6) !important;
    box-shadow: 0 4px 14px rgba(212, 160, 23, 0.35) !important;
}
.btn-book-car-custom:hover {
    background: linear-gradient(135deg, #f5c842 0%, #d4a017 100%) !important;
    color: #0f172a !important;
}
</style>

<section id="services" class="offers-section" style="background:#F5F3EF; padding:5.5rem 0;">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6">
                <div class="section-eyebrow" style="color:#D4A017;">Pricing</div>
                <h2 class="section-title" style="color:#0A0A0A!important;">Car Window Tinting <em style="color:#D4A017; font-style:italic;">Offers</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle" style="color:#5A5A5A!important;">Specialized tinting solutions for every vehicle type</p>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <?php
            $display_packages = !empty($packages) ? $packages : [];
            foreach ($display_packages as $index => $pkg):
                $pid = (int)$pkg['id'];
                $start_price = $pkg_min_prices[$pid] ?? (float)($pkg['display_price'] ?? $pkg['price'] ?? 0);
                if ($start_price <= 0) {
                    $start_price = (float)($pkg['sedan_price'] ?? $pkg['suv_price'] ?? 4500);
                }
                
                $raw_img = trim((string)($pkg['image_url'] ?? ''));
                $pkg_img = 'assets/images/hero_car.png';
                if (!empty($raw_img) && $raw_img !== '/images/tint-packages/default-tint.jpg') {
                    $clean_url = ltrim(str_replace(['../', './'], '', $raw_img), '/');
                    if (file_exists(__DIR__ . '/' . $clean_url)) {
                        $pkg_img = $clean_url;
                    }
                }
                
                $desc = html_entity_decode(trim((string)($pkg['description'] ?? '')), ENT_QUOTES, 'UTF-8');

                
                $card_types = array_values(array_filter(array_map('trim', explode(',', (string)($pkg['tint_type'] ?? '')))));
                $card_shade_map = json_decode((string)($pkg['shade_percentage'] ?? ''), true);
                $card_shades = [];
                if (is_array($card_shade_map)) {
                    foreach ($card_shade_map as $st => $svs) {
                        foreach ((array)$svs as $sv) $card_shades[] = (int)$sv;
                    }
                    $card_shades = array_values(array_unique($card_shades));
                    rsort($card_shades);
                } elseif (!empty($pkg['shade_percentage'])) {
                    $card_shades = [(int)$pkg['shade_percentage']];
                }
                
                $is_pop = !empty($pkg['is_popular']) || !empty($pkg['is_featured']);
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="pkg-card-custom<?= $is_pop ? ' pkg-popular' : '' ?>">
                    <?php if ($is_pop): ?>
                    <div class="pkg-badge-popular"><i class="fas fa-star me-1"></i><?= !empty($pkg['badge']) ? htmlspecialchars($pkg['badge']) : 'Popular' ?></div>
                    <?php endif; ?>

                    
                    <div class="pkg-card-img-wrap">
                        <img src="<?= htmlspecialchars($pkg_img) ?>" alt="<?= htmlspecialchars($pkg['name']) ?>" onerror="this.onerror=null; this.src='assets/images/hero_car.png';">
                        <?php if($start_price > 0): ?>
                        <div class="pkg-floating-price">₱<?= number_format($start_price, 0) ?></div>
                        <?php endif; ?>
                    </div>

                    
                    <div class="pkg-card-title"><?= htmlspecialchars(ucwords($pkg['name'])) ?></div>
                    <?php if(!empty($desc)): ?>
                    <div class="pkg-card-description"><?= htmlspecialchars($desc) ?></div>
                    <?php endif; ?>

                    
                    <div class="pkg-card-specs">
                        <?php if (!empty($card_types)): ?>
                        <span class="pkg-spec-pill"><i class="fas fa-layer-group"></i><?= htmlspecialchars(implode(', ', array_map(function($v){ return ucwords(str_replace('-', ' ', $v)); }, array_slice($card_types, 0, 2))) . (count($card_types) > 2 ? ' +' : '')) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($card_shades)): ?>
                        <span class="pkg-spec-pill"><i class="fas fa-sun"></i><?= htmlspecialchars(implode(', ', array_map(function($v){ return $v . '%'; }, array_slice($card_shades, 0, 3))) . (count($card_shades) > 3 ? ' +' : '')) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($pkg['installation_time'])): ?>
                        <span class="pkg-spec-pill"><i class="fas fa-clock"></i><?= htmlspecialchars($pkg['installation_time']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($pkg['warranty_years'])): ?>
                        <span class="pkg-spec-pill"><i class="fas fa-shield-halved"></i><?= (int)$pkg['warranty_years'] ?>yr warranty</span>
                        <?php endif; ?>
                        <?php if (!empty($pkg['heat_rejection'])): ?>
                        <span class="pkg-spec-pill"><i class="fas fa-temperature-arrow-down"></i><?= (int)$pkg['heat_rejection'] ?>% heat block</span>
                        <?php endif; ?>
                    </div>

                    
                    <?php if ($is_logged_in): ?>
                        <?php if (isset($pkg_single_models[(int)$pkg['id']])): ?>
                            <a href="customer/book.php?package_id=<?= $pkg['id'] ?>" class="btn-pkg-action-custom btn-book-car-custom">
                                <i class="fas fa-car-side me-1.5"></i>Book This Car
                            </a>
                        <?php else: ?>
                            <a href="customer/book.php?package_id=<?= $pkg['id'] ?>" class="btn-pkg-action-custom btn-book-package-custom">
                                <i class="fas fa-layer-group me-1.5"></i>Book Package
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (isset($pkg_single_models[(int)$pkg['id']])): ?>
                            <button type="button" class="btn-pkg-action-custom btn-book-car-custom" data-bs-toggle="modal" data-bs-target="#signInModal">
                                <i class="fas fa-car-side me-1.5"></i>Book This Car
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn-pkg-action-custom btn-book-package-custom" data-bs-toggle="modal" data-bs-target="#signInModal">
                                <i class="fas fa-layer-group me-1.5"></i>Book Package
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" style="background:#FFFFFF; padding:5.5rem 0;">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6">
                <div class="section-eyebrow" style="color:#D4A017;">Customer Feedback</div>
                <h2 class="section-title" style="color:#0A0A0A!important;">Verified Customer <em style="color:#D4A017; font-style:italic;">Reviews</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle" style="color:#5A5A5A!important;">Real ratings & reviews fetched directly from our approved client feedback</p>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($customer_reviews as $review): 
                $client_name = !empty($review['name']) ? $review['name'] : (!empty($review['user_full_name']) ? $review['user_full_name'] : 'Satisfied Client');
                $rating_val = floatval($review['rating'] ?? 5.0);
                $vehicle_info = !empty($review['vehicle']) ? $review['vehicle'] : 'Vehicle Owner';
                $tint_name = !empty($review['tint_type']) ? $review['tint_type'] : 'Window Tint';
                $created_date = !empty($review['created_at']) ? date('M d, Y', strtotime($review['created_at'])) : 'Recent';
                $initials = strtoupper(substr($client_name, 0, 1));
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="testimonial-review-card" style="background:#F8F6F2!important; border:1.5px solid #E6E2DA; border-radius:22px; padding:2rem; height:100%; display:flex; flex-direction:column; justify-content:space-between; transition:all .3s ease; box-shadow:0 8px 30px rgba(0,0,0,0.06);">
                    <div>
                        
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="review-stars" style="color:#D4A017; font-size:1.05rem;">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <?php if ($s <= floor($rating_val)): ?>
                                        <i class="fas fa-star"></i>
                                    <?php elseif ($s - $rating_val <= 0.5): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php else: ?>
                                        <i class="far fa-star" style="color:#CBD5E1;"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <span class="ms-2 fw-bold" style="color:#0A0A0A!important; font-size:.9rem;"><?= number_format($rating_val, 1) ?></span>
                            </div>
                            <span class="badge" style="background:rgba(212,160,23,.12); color:#B8860B; border:1px solid rgba(212,160,23,.3); font-size:.72rem; padding:.35rem .65rem; border-radius:50px; font-weight:700;">
                                <i class="fas fa-check-circle me-1"></i> Verified Review
                            </span>
                        </div>

                        
                        <p class="review-comment" style="color:#2C3E50!important; font-size:.95rem; line-height:1.7; font-style:italic; margin-bottom:1.5rem;">
                            "<?= htmlspecialchars($review['comment']) ?>"
                        </p>
                    </div>

                    
                    <div class="pt-3" style="border-top:1px solid #E6E2DA;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="review-avatar" style="width:44px; height:44px; background:linear-gradient(135deg,#D4A017,#F5C842); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#0A0A0A!important; font-weight:800; font-size:1.1rem; flex-shrink:0;">
                                <?= $initials ?>
                            </div>
                            <div>
                                <h6 class="mb-0 font-weight-bold" style="color:#0A0A0A!important; font-size:.95rem;"><?= htmlspecialchars($client_name) ?></h6>
                                <div style="font-size:.82rem; color:#B8860B!important; font-weight:600;">
                                    <i class="fas fa-car me-1" style="font-size:.75rem;"></i> <?= htmlspecialchars($vehicle_info) ?>
                                </div>
                                <div style="font-size:.75rem; color:#64748B!important; margin-top:2px;">
                                    <span><?= htmlspecialchars($tint_name) ?></span> · <span><?= $created_date ?></span>
                                </div>
                            </div>
                        </div>
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
                <div class="section-eyebrow">How It Works</div>
                <h2 class="section-title">Our Installation <em>Process</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle">Professional tinting completed in just a few simple steps</p>
            </div>
        </div>
        <div class="process-grid">
            <div class="process-step"><div class="step-num">01</div><h5>Consultation</h5><p>Discuss your needs, vehicle type, and choose the perfect tint package</p></div>
            <div class="process-step"><div class="step-num">02</div><h5>Preparation</h5><p>Thorough window cleaning and preparation for flawless application</p></div>
            <div class="process-step"><div class="step-num">03</div><h5>Installation</h5><p>Precise application by our certified professional tinting technicians</p></div>
            <div class="process-step"><div class="step-num">04</div><h5>Quality Check</h5><p>Final inspection, warranty registration, and care instructions</p></div>
        </div>
    </div>
</section>

<section id="gallery" class="section" style="background:var(--ink);">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6">
                <div class="section-eyebrow">Portfolio</div>
                <h2 class="section-title">Our <em>Gallery</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle">Real cars, real results from RG Cartint Works Davao</p>
            </div>
        </div>

        
        <div class="gallery-filter-bar mb-4">
            <button class="gallery-filter-btn active" data-filter="all" onclick="filterGalleryItems('all')"><i class="fas fa-th-large"></i> All</button>
            <button class="gallery-filter-btn" data-filter="sedan" onclick="filterGalleryItems('sedan')"><i class="fas fa-car"></i> Sedans</button>
            <button class="gallery-filter-btn" data-filter="pickup" onclick="filterGalleryItems('pickup')"><i class="fas fa-truck-pickup"></i> Pickups</button>
            <button class="gallery-filter-btn" data-filter="van" onclick="filterGalleryItems('van')"><i class="fas fa-shuttle-van"></i> Vans</button>
            <button class="gallery-filter-btn" data-filter="luxury" onclick="filterGalleryItems('luxury')"><i class="fas fa-gem"></i> Luxury</button>
        </div>

        <div class="row g-3" id="galleryGrid">
            <?php foreach ($gallery_items as $item): ?>
            <div class="col-md-6 col-lg-4 col-xl-3 gallery-grid-item" data-category="<?= $item['category'] ?>">
                <div class="gallery-card-mini">
                    <div class="gallery-card-img">
                        <img src="<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
                        <div class="gallery-card-overlay">
                            <span class="gallery-tint-badge"><?= htmlspecialchars($item['tint_type']) ?></span>
                        </div>
                    </div>
                    <div class="gallery-card-info">
                        <h6><?= htmlspecialchars($item['title']) ?></h6>
                        <p><?= htmlspecialchars($item['description']) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="about" class="section" style="background:var(--ink-mid);">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6">
                <div class="section-eyebrow">Our Story</div>
                <h2 class="section-title">Who <em>We Are</em></h2>
            </div>
            <div class="col-lg-5 ms-auto">
                <p class="section-subtitle">Established in 2020, RG CARTINTWORKS is Davao's trusted mobile window tinting specialist</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="story-card">
                    <div class="year-badge"><i class="fas fa-flag me-2"></i>Established 2020</div>
                    <h3 class="mb-3" style="font-family:var(--font-display);letter-spacing:.04em;font-size:1.8rem;color:var(--text-body);">Our Beginning</h3>
                    <p style="color:var(--text-dim);line-height:1.75;font-size:.95rem;">RG CARTINTWORKS started with one goal: deliver professional window tinting that provides reliable privacy, protection, and comfort for every vehicle. From day one, we focused on precision installations and a customer-first approach that builds long-term trust.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="story-card">
                    <div class="year-badge"><i class="fas fa-rocket me-2"></i>Today</div>
                    <h3 class="mb-3" style="font-family:var(--font-display);letter-spacing:.04em;font-size:1.8rem;color:var(--text-body);">Trusted Mobile Service</h3>
                    <p style="color:var(--text-dim);line-height:1.75;font-size:.95rem;">Today we are a mobile-first freelancer-owned business offering professional home-service within Davao City and to clients outside Davao. We combine premium materials, expert workmanship, and a modern service system for smooth booking, accurate tracking, and reliable support.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section-dark">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6"><div class="section-eyebrow">By the Numbers</div><h2 class="section-title">Our <em>Track Record</em></h2></div>
            <div class="col-lg-5 ms-auto"><p class="section-subtitle">Years of experience and consistent quality trusted by hundreds of Davao vehicle owners</p></div>
        </div>
        <div class="row g-4">
            <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="fas fa-calendar-alt"></i></div><div class="stat-number">5+</div><div class="stat-label">Years Experience</div><div class="stat-unit">Since 2020</div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="fas fa-car"></i></div><div class="stat-number"><?= $real_bookings_count > 0 ? $real_bookings_count . '+' : '12+' ?></div><div class="stat-label">Vehicles Tinted</div><div class="stat-unit">And Counting</div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="fas fa-star"></i></div><div class="stat-number"><?= number_format($real_avg_rating, 1) ?></div><div class="stat-label">Average Rating</div><div class="stat-unit">From <?= $real_testimonials_count ?> Reviews</div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-icon"><i class="fas fa-shield-alt"></i></div><div class="stat-number"><?= is_numeric($real_max_warranty) ? $real_max_warranty . ' Yrs' : ($real_max_warranty === '5-Year' ? 'Lftm' : htmlspecialchars($real_max_warranty)) ?></div><div class="stat-label">Max Warranty</div><div class="stat-unit">On Premium Films</div></div></div>
        </div>
    </div>
</section>

<section class="section" style="background:var(--ink);">
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

<section class="section" style="background:var(--ink-mid);" id="faq">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-lg-6"><div class="section-eyebrow">FAQ</div><h2 class="section-title">Frequently Asked <em>Questions</em></h2></div>
            <div class="col-lg-5 ms-auto"><p class="section-subtitle">Quick answers to common questions about our tinting services</p></div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item faq-item"><h3 class="accordion-header"><button class="accordion-button faq-btn" type="button" data-bs-toggle="collapse" data-bs-target="#faq1"><i class="fas fa-clock me-2" style="color:var(--gold);"></i>How long have you been in business?</button></h3><div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion"><div class="accordion-body">We were established in 2020 and have 5 years of experience and counting, serving Davao City and clients outside Davao through our mobile-first home service.</div></div></div>
                    <div class="accordion-item faq-item"><h3 class="accordion-header"><button class="accordion-button faq-btn collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2"><i class="fas fa-film me-2" style="color:var(--gold);"></i>What tint series do you offer?</button></h3><div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body">We specialize in high-performance materials including HD Carbon, Magic Chrome, and Nano Ceramic series — designed for excellent heat rejection, privacy, and protection.</div></div></div>
                    <div class="accordion-item faq-item"><h3 class="accordion-header"><button class="accordion-button faq-btn collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3"><i class="fas fa-shield-alt me-2" style="color:var(--gold);"></i>Do you offer warranty?</button></h3><div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body">Yes! Our tinting solutions come with warranties ranging from 5 to 7 years depending on the film series chosen. We stand behind every installation we do.</div></div></div>
                    <div class="accordion-item faq-item"><h3 class="accordion-header"><button class="accordion-button faq-btn collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4"><i class="fas fa-truck me-2" style="color:var(--gold);"></i>Do you offer home service?</button></h3><div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body">Yes — we are a mobile-first business. We provide professional home-service tinting within Davao City and can also serve clients outside Davao upon arrangement.</div></div></div>
                    <div class="accordion-item faq-item"><h3 class="accordion-header"><button class="accordion-button faq-btn collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5"><i class="fas fa-tag me-2" style="color:var(--gold);"></i>How much does car tinting cost?</button></h3><div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body">Pricing depends on your vehicle type, window count, and the film series selected. HD Carbon is our most affordable option, while Nano Ceramic is our premium tier. Check our services section above or ask our chatbot for a quick estimate!</div></div></div>
                    <div class="accordion-item faq-item"><h3 class="accordion-header"><button class="accordion-button faq-btn collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6"><i class="fas fa-map-marker-alt me-2" style="color:var(--gold);"></i>Where are you located?</button></h3><div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion"><div class="accordion-body">We are mobile-based and cover Davao City, Tagum, Digos, Panabo, and Carmen. We come to your home or office — no need to drive to a shop!</div></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cta-section" style="background:linear-gradient(135deg, #D4A017 0%, #B8860B 100%); padding:5rem 0;">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h2 style="color:#0A0A0A!important; font-family:var(--font-display); font-weight:800; font-size:2.8rem; letter-spacing:.03em; margin-bottom:.5rem;">READY TO EXPERIENCE THE DIFFERENCE?</h2>
                <p class="mb-0 mt-2" style="color:rgba(10,10,10,.85); font-weight:500;">Professional installation · Up to official warranty · Mobile service available</p>
                <div class="row g-4 mt-2">
                    <div class="col-4 text-center"><i class="fas fa-truck fa-2x mb-2" style="color:#0A0A0A; opacity:.85;"></i><div style="font-size:.88rem; font-weight:700; color:#0A0A0A;">Mobile Service</div></div>
                    <div class="col-4 text-center"><i class="fas fa-award fa-2x mb-2" style="color:#0A0A0A; opacity:.85;"></i><div style="font-size:.88rem; font-weight:700; color:#0A0A0A;">Official Warranty</div></div>
                    <div class="col-4 text-center"><i class="fas fa-sun fa-2x mb-2" style="color:#0A0A0A; opacity:.85;"></i><div style="font-size:.88rem; font-weight:700; color:#0A0A0A;">99% UV Block</div></div>
                </div>
            </div>
            <div class="col-lg-5 text-lg-end d-flex gap-3 flex-wrap justify-content-lg-end align-items-center">
                <?php if ($is_logged_in): ?>
                    <a href="customer/book.php" class="btn-cta-dark" style="background:#0A0A0A!important; color:#ffffff!important; font-weight:700; border-radius:50px; padding:.9rem 2rem; border:1.5px solid #0A0A0A; box-shadow:0 8px 24px rgba(0,0,0,0.3); text-decoration:none; display:inline-flex; align-items:center; gap:.5rem;">
                        <i class="fas fa-calendar-check"></i> Book Appointment Now
                    </a>
                <?php else: ?>
                    <button type="button" class="btn-cta-dark" data-bs-toggle="modal" data-bs-target="#signInModal" style="background:#0A0A0A!important; color:#ffffff!important; font-weight:700; border-radius:50px; padding:.9rem 2rem; border:1.5px solid #0A0A0A; box-shadow:0 8px 24px rgba(0,0,0,0.3); display:inline-flex; align-items:center; gap:.5rem;">
                        <i class="fas fa-calendar-check"></i> Book Appointment Now
                    </button>
                <?php endif; ?>
                <a href="tel:+09970628687" class="btn-cta-outline" style="background:transparent!important; color:#0A0A0A!important; font-weight:700; border-radius:50px; padding:.9rem 2rem; border:2px solid #0A0A0A!important; text-decoration:none; display:inline-flex; align-items:center; gap:.5rem;">
                    <i class="fas fa-phone"></i> Call: +0997 062 8687
                </a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/chatbot.php'; ?>

<footer class="bg-dark text-white py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h4 class="fw-bold mb-3" style="font-family:var(--font-display);color:var(--gold-light);letter-spacing:.06em;font-size:1.7rem;">RG CARTINTWORKS</h4>
                <p class="mb-4" style="color:rgba(255,255,255,.5);font-size:.88rem;line-height:1.75;">
                    Davao's trusted mobile window tinting specialist established in 2020. Professional installation with up to official warranty using premium HD Carbon, Magic Chrome, and Nano Ceramic films.
                </p>
                <div class="d-flex gap-3">
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
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
                    <li class="mb-2"><a href="#services" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Services</a></li>
                    <li class="mb-2"><a href="#gallery" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Gallery</a></li>
                    <li class="mb-2"><a href="#about" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">About Us</a></li>
                    <li class="mb-2"><a href="customer/book.php" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">Book Now</a></li>
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
                        <a href="tel:0997 062 8687" style="font-size:.86rem;color:rgba(255,255,255,.5);text-decoration:none;" class="hover-warning">+0997 062 8687</a>
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
            <div class="col-md-6"><small style="color:rgba(255,255,255,.3);">&copy; 2025 RG CARTINTWORKS. All rights reserved.</small></div>
            <div class="col-md-6 text-md-end"><small style="color:rgba(255,255,255,.3);">Professional Car Window Tinting in Davao Region</small></div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/landing.js?v=<?= time() ?>"></script>

</body>
</html>
