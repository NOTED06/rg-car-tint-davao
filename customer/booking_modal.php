<?php

try {
    $bm_user_stmt = $pdo->prepare("SELECT full_name, email, phone, address FROM users WHERE id = ?");
    $bm_user_stmt->execute([$_SESSION['user_id']]);
    $bm_user_info = $bm_user_stmt->fetch(PDO::FETCH_ASSOC) ?: ['full_name'=>'','email'=>'','phone'=>'','address'=>''];
} catch (PDOException $e) {
    $bm_user_info = ['full_name'=>'','email'=>'','phone'=>'','address'=>''];
}

try {
    $bm_packages = $pdo->query("SELECT * FROM tint_packages ORDER BY display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $bm_packages = []; }

try {
    $bm_vehicles = $pdo->query("SELECT * FROM vehicle_types ORDER BY type_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($bm_vehicles)) {
        $bm_vehicles = [
            ['id'=>1,'type_name'=>'Sedan/Hatchback','price_category'=>'sedan','icon'=>'fa-car-side'],
            ['id'=>2,'type_name'=>'SUV/MPV','price_category'=>'suv','icon'=>'fa-truck-suv'],
            ['id'=>3,'type_name'=>'Pickup','price_category'=>'pickup','icon'=>'fa-truck-pickup'],
            ['id'=>4,'type_name'=>'Van/AUV','price_category'=>'van','icon'=>'fa-van-shuttle']
        ];
    }
} catch (PDOException $e) { $bm_vehicles = []; }

if (!function_exists('bmGetVehicleIcon')) {
    function bmGetVehicleIcon($v) {
        $icon = $v['icon'] ?? '';
        $name = strtolower(($v['type_name'] ?? '') . ' ' . ($v['price_category'] ?? '') . ' ' . $icon);
        if (strpos($name, 'pickup') !== false || strpos($name, 'truck-pickup') !== false) {
            return 'fa-truck-pickup';
        }
        if (strpos($name, 'suv') !== false || strpos($name, 'mpv') !== false) {
            return 'fa-truck-suv';
        }
        if (strpos($name, 'van') !== false || strpos($name, 'auv') !== false || strpos($name, 'shuttle') !== false) {
            return 'fa-van-shuttle';
        }
        if (strpos($name, 'sedan') !== false || strpos($name, 'hatchback') !== false) {
            return 'fa-car-side';
        }
        if (!empty($icon)) {
            return str_starts_with($icon, 'fa-') ? $icon : 'fa-' . $icon;
        }
        return 'fa-car-side';
    }
}

if (!function_exists('bmGetVehicleIconHtml')) {
    function bmGetVehicleIconHtml($v) {
        $name = strtolower(($v['type_name'] ?? '') . ' ' . ($v['price_category'] ?? '') . ' ' . ($v['icon'] ?? ''));
        if (strpos($name, 'pickup') !== false || strpos($name, 'truck-pickup') !== false) {
            return '<i class="fas fa-truck-pickup me-1"></i>';
        }
        if (strpos($name, 'suv') !== false || strpos($name, 'mpv') !== false || strpos($name, 'truck-suv') !== false) {
            return '<svg viewBox="0 0 122.88 61.11" width="1.25em" height="1em" style="vertical-align:-0.15em;fill:currentColor;display:inline-block;margin-right:0.35rem;" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M65.22,0h26.54h6.63c0.38,0,0.52,0.34,0.68,0.68l2.66,5.53c0.16,0.34-0.31,0.68-0.68,0.68H55.93 C52.98,6.9,58.02,0,65.22,0z M23.83,45.74v3.74h3.74C27.12,47.65,25.67,46.19,23.83,45.74z M27.57,51.93h-3.74 v3.74C25.67,55.22,27.12,53.77,27.57,51.93z M21.39,55.67v-3.74h-3.74C18.1,53.77,19.55,55.22,21.39,55.67z M17.64,49.49h3.74v-3.74C19.55,46.2,18.1,47.65,17.64,49.49z M90.05,45.74v3.74h3.74 C93.34,47.65,91.89,46.19,90.05,45.74z M93.79,51.93h-3.74v3.74C91.89,55.22,93.34,53.77,93.79,51.93z M87.61,55.67v-3.74h-3.74C84.31,53.77,85.76,55.22,87.61,55.67z M83.86,49.49h3.74v-3.74 C85.76,46.2,84.31,47.65,83.86,49.49z M89.66,12.77h3.52c5.08,0.23,7.17,0.05,9.19,1.59 c2.5,1.91,3.48,10.35,4.52,14.25c0.07,0.26-0.23,0.5-0.5,0.5H90.88c-0.27,0-0.47-0.22-0.5-0.5l-1.22-15.35 C89.14,13,89.39,12.77,89.66,12.77z M66.09,12.86h-11.5c-5.25,0-13.89,8.63-16.15,15.61 c-0.11,0.34,0.29,0.65,0.65,0.65h25.18c0.36,0,0.6-0.29,0.65-0.65l1.83-14.96C66.78,13.15,66.44,12.86,66.09,12.86z M71.07,12.8h14.07c0.27,0,0.48,0.23,0.5,0.5l1.07,15.32c0.02,0.27-0.23,0.5-0.5,0.5H69.24c-0.27,0-0.53-0.23-0.5-0.5l1.83-15.32 C70.6,13.02,70.79,12.8,71.07,12.8z M22.61,40.31c5.74,0,10.4,4.66,10.4,10.4c0,5.74-4.66,10.4-10.4,10.4 c-5.74,0-10.4-4.66-10.4-10.4C12.21,44.97,16.87,40.31,22.61,40.31z M1.81,40.78c0.53-0.24,1.11-0.35,1.74-0.34 c-0.03-3.28,0.19-6.14,0.95-8.17c0.28-1.17,0.79-2.02,1.47-2.64c2.14-1.91,21.25-3.59,25.28-4.11c4.95-4.84,10.46-9.27,16.33-13.46 c1.63-1.38,3.96-2.06,6.9-2.13l46.41-0.04c3.51-0.02,6.21,1.48,7.93,4.85l3.87,11.15l0.88-0.24v-4.34 c-0.16-1.45,0.41-2.17,1.52-2.35h5.2c1.43,0.06,2.44,0.78,2.6,2.85v15.42c0.01,1.59-0.57,2.63-2.04,2.85h-4.83 c-0.48,0.11-0.33,0.48-0.37,0.99v5.26c-0.35,3.88-1.41,6.88-4.52,7.27h-9.41c0.2-4.56-0.66-8.29-2.75-11.08 c-7.59-10.14-24.06-4.1-22.95,11.11H35.58c0.34-4.86-0.58-8.55-2.59-11.2c-7.57-9.99-25.18-4.6-23.1,11.51H3.64 C-0.13,53.94-1.36,42.19,1.81,40.78z M88.83,40.31c5.74,0,10.4,4.66,10.4,10.4c0,5.74-4.66,10.4-10.4,10.4 c-5.74,0-10.4-4.66-10.4-10.4C78.43,44.97,83.08,40.31,88.83,40.31z"/></svg>';
        }
        if (strpos($name, 'van') !== false || strpos($name, 'auv') !== false || strpos($name, 'shuttle') !== false) {
            return '<i class="fas fa-van-shuttle me-1"></i>';
        }
        return '<i class="fas fa-car-side me-1"></i>';
    }
}

$bm_car_list = [];
$bm_car_model_cats = [];
$suv_kw   = ['Fortuner','Everest','CR-V','CRV','Montero Sport','Tucson','Sportage','Terra','Trailblazer','MU-X','MUX','Prado','Land Cruiser'];
$van_kw   = ['HiAce','Hiace','Starex','Carnival','Staria','Urvan','NV350','Grandia','Ertiga','Traveller','Commuter'];
$pick_kw  = ['Hilux','Ranger','Navara','Strada','D-Max','Colorado','Revo'];
try {
    $cm_rows = $pdo->query("SELECT full_name, COALESCE(model,'') as model FROM car_models ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cm_rows as $cmr) {
        $bm_car_list[] = $cmr['full_name'];
        $fn = $cmr['full_name'];
        $cat = 'sedan';
        foreach ($suv_kw  as $kw) { if (stripos($fn, $kw) !== false) { $cat='suv'; break; } }
        if ($cat==='sedan') foreach ($van_kw  as $kw) { if (stripos($fn, $kw) !== false) { $cat='van'; break; } }
        if ($cat==='sedan') foreach ($pick_kw as $kw) { if (stripos($fn, $kw) !== false) { $cat='pickup'; break; } }
        $bm_car_model_cats[$fn] = $cat;
    }
    if (empty($bm_car_list)) $bm_car_list = ['Toyota Vios 1.5G CVT 2023','Toyota Fortuner 2.4G 4x2 2022'];
} catch (PDOException $e) {}

$bm_pkg_model_prices = [];
try {
    $pmp_rows = $pdo->query("SELECT package_id, car_model, tint_type, shade_percentage, price FROM package_model_prices")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($pmp_rows as $r) {
        $combo = $r['tint_type'].'|'.$r['shade_percentage'];
        $bm_pkg_model_prices[$r['package_id']][$combo][$r['car_model']] = (float)$r['price'];
    }
} catch (PDOException $e) {}

$bm_tint_labels = ['carbon'=>'Carbon','ceramic'=>'Ceramic','nano-ceramic'=>'Nano-Ceramic','dyed'=>'Dyed','metalized'=>'Metalized','hybrid'=>'Hybrid','crystalline'=>'Crystalline','infrared'=>'Infrared (IR)','security'=>'Security'];
$bm_shades = [70,50,35,20,5];

$bm_pkg_defaults = [];
foreach ($bm_packages as $p) {
    $types = array_values(array_filter(array_map('trim', explode(',', (string)($p['tint_type'] ?? 'carbon')))));
    if (empty($types)) $types = ['carbon'];
    $shadeMap = [];
    $decoded = json_decode((string)($p['shade_percentage'] ?? ''), true);
    if (is_array($decoded)) {
        foreach ($decoded as $tt => $shades) {
            $shadeMap[$tt] = array_values(array_map('intval', is_array($shades) ? $shades : []));
        }
    } else {
        $shadeMap[$types[0]] = [(int)($p['shade_percentage'] ?? 35)];
    }
    $basePrice = (float)($p['sedan_price'] ?: ($p['price'] ?: 4500));
    $bm_pkg_defaults[(string)$p['id']] = [
        'id'          => (string)$p['id'],
        'tint_types'  => $types,
        'shade_map'   => $shadeMap,
        'price'       => $basePrice,
        'sedan_price' => (float)($p['sedan_price'] ?: $basePrice),
        'suv_price'   => (float)($p['suv_price'] ?: $basePrice),
        'van_price'   => (float)($p['van_price'] ?: $basePrice),
    ];
}

$bm_tint_icons = ['carbon'=>'fa-atom','ceramic'=>'fa-shield-halved','nano-ceramic'=>'fa-microchip','dyed'=>'fa-droplet','metalized'=>'fa-layer-group','hybrid'=>'fa-code-merge','crystalline'=>'fa-gem','infrared'=>'fa-temperature-arrow-down','security'=>'fa-lock'];
?>

<div id="bookingModalOverlay" style="display:none;">
<style>

#bookingModalOverlay {
    position: fixed; inset: 0; z-index: 99999;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(8px);
    overflow-y: auto;
    display: flex; align-items: center; justify-content: center;
    padding: 24px 16px;
    animation: bmFadeIn 0.25s ease;
}
@keyframes bmFadeIn { from{opacity:0} to{opacity:1} }

#bookingModalBox {
    width: 100%;
    max-width: 900px;
    margin: auto;
    position: relative;
    box-shadow: 0 20px 50px rgba(0,0,0,0.25);
    border-radius: 14px;
    overflow: hidden;
}

#bookingModalOverlay {
    --admin-gold: #D4A017;
    --admin-gold-dark: #C89008;
    --admin-gold-light: #F5C842;
    --admin-bg-light: #FFFFFF;
    --admin-card-bg: #FFFFFF;
    --admin-card-border: #E2E8F0;
    --admin-text-dark: #0F172A;
    --admin-text-muted: #64748B;
    --admin-label-gold: #C89008;
    --success: #22C55E; --danger: #EF4444; --info: #3B82F6;
    --grad-header: linear-gradient(135deg, #d4a017 0%, #b8860b 100%);
    --grad-gold: linear-gradient(135deg, #F5C842 0%, #D4A017 100%);
    --radius: 14px; --radius-sm: 8px;
}

.bm-wizard-header {
    background: var(--grad-header);
    border-radius: var(--radius) var(--radius) 0 0;
    padding: 1.25rem 3rem 1rem 1.5rem;
    border: none;
    color: #ffffff;
    position: relative; overflow: hidden;
    box-shadow: 0 4px 15px rgba(184,134,11,0.2);
}
.bm-wizard-header h2 {
    font-family: 'Syne', sans-serif; font-size: 1.3rem; font-weight: 800;
    color: #ffffff; margin-bottom: 0.2rem; display: flex; align-items: center; gap: 8px;
}
.bm-wizard-header p { color: rgba(255,255,255,0.92); font-size: 0.85rem; margin-bottom: 0; }

.bm-close-btn {
    position: absolute;
    top: 14px; right: 16px;
    z-index: 10;
    width: 34px; height: 34px;
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.4);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #ffffff;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s;
}
.bm-close-btn:hover { background: #ffffff; color: var(--admin-gold-dark); transform: scale(1.08); }

.bm-wizard-steps {
    display:flex; justify-content:space-between; margin-top:1.1rem; position:relative;
}
.bm-wizard-steps::before {
    content:''; position:absolute; top:16px; left:25px; right:25px;
    height:2px; background:rgba(255,255,255,0.3); z-index:0;
}
.bm-wizard-steps::after {
    content:''; position:absolute; top:16px; left:25px; height:2px;
    background:#ffffff; z-index:1;
    width:var(--bm-progress-width,0%); transition:width 0.4s ease;
}
.bm-step { position:relative; z-index:2; text-align:center; flex:1; display:flex; flex-direction:column; align-items:center; }
.bm-step-bubble {
    width:32px; height:32px; background:rgba(255,255,255,0.25); border:2px solid #ffffff;
    border-radius:50%; display:flex; align-items:center; justify-content:center;
    margin-bottom:0.35rem; font-family:'Syne',sans-serif; font-weight:800; font-size:0.82rem;
    color:#ffffff; transition:all 0.3s ease;
}
.bm-step.active .bm-step-bubble { background:#ffffff; border-color:#ffffff; color:var(--admin-gold-dark); box-shadow:0 4px 12px rgba(0,0,0,0.2); transform:scale(1.1); }
.bm-step.completed .bm-step-bubble { background:#22C55E; border-color:#22C55E; color:white; }
.bm-step-label { font-size:0.7rem; font-weight:700; color:rgba(255,255,255,0.88); letter-spacing:0.04em; text-transform:uppercase; transition:color 0.3s; }
.bm-step.active .bm-step-label { color:#ffffff; font-weight:800; }
.bm-step.completed .bm-step-label { color:#e2e8f0; }

.bm-wizard-content {
    background:#ffffff;
    border-radius:0 0 var(--radius) var(--radius);
    padding:1.5rem 1.75rem; border:1px solid #e2e8f0; border-top:none;
    font-family:'DM Sans',sans-serif; color:var(--admin-text-dark);
}
.bm-panel { display:none; animation:bmSlide 0.25s ease; }
.bm-panel.active { display:block; }
@keyframes bmSlide { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }

.bm-section-title {
    font-family:'Syne',sans-serif; font-size:0.95rem; font-weight:800; color:var(--admin-label-gold);
    margin-bottom:0.75rem; display:flex; align-items:center; gap:0.5rem; text-transform:uppercase; letter-spacing:0.04em;
}
.bm-title-icon {
    width:28px; height:28px; background:rgba(212,160,23,0.12); border-radius:6px;
    display:flex; align-items:center; justify-content:center; color:var(--admin-gold-dark); font-size:0.85rem; flex-shrink:0;
}
.bm-divider { border:none; border-top:1px solid #e2e8f0; margin:1.25rem 0; }

.bm-label { color:var(--admin-label-gold); font-weight:700; font-size:0.82rem; margin-bottom:0.35rem; display:block; text-transform:uppercase; letter-spacing:0.03em; }
.bm-input, .bm-select, .bm-textarea {
    background:#ffffff !important; border:1px solid #cbd5e1 !important;
    border-radius:var(--radius-sm) !important; color:var(--admin-text-dark) !important;
    padding:8px 12px !important; font-size:0.86rem !important; font-family:'DM Sans',sans-serif !important;
    transition:all 0.2s !important; width:100%; box-sizing:border-box;
}
.bm-input:focus, .bm-select:focus, .bm-textarea:focus {
    border-color:var(--admin-gold) !important; box-shadow:0 0 0 3px rgba(212,160,23,0.18) !important;
    background:#ffffff !important; outline:none !important;
}
.bm-input::placeholder, .bm-textarea::placeholder { color:#94a3b8 !important; }
.bm-select option { background:#ffffff; color:var(--admin-text-dark); }
.bm-validation-error { color:#EF4444; font-size:0.78rem; margin-top:0.25rem; display:none; font-weight:600; }
.bm-input.bm-invalid, .bm-select.bm-invalid, .bm-textarea.bm-invalid { border-color:var(--danger) !important; }

.bm-pill-group { display:flex; flex-wrap:wrap; gap:0.45rem; }
.bm-pill {
    padding:0.35rem 0.9rem; border:1px solid #cbd5e1; border-radius:50px;
    background:#ffffff; color:#334155; font-size:0.8rem; font-weight:600;
    cursor:pointer; transition:all 0.2s ease; font-family:inherit;
}
.bm-pill:hover { border-color:var(--admin-gold); color:var(--admin-gold-dark); background:#fffdf5; }
.bm-pill.active { background:rgba(212,160,23,0.12); border-color:var(--admin-gold); color:var(--admin-gold-dark); font-weight:700; }

.bm-pkg-grid {
    display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:0.75rem; margin-bottom:1rem;
}
.bm-pkg-card {
    background:#ffffff; border:1px solid #e2e8f0;
    border-radius:10px; padding:0.85rem 1rem; cursor:pointer; transition:all 0.2s ease; position:relative;
    box-shadow:0 2px 5px rgba(0,0,0,0.02);
}
.bm-pkg-card:hover { border-color:var(--admin-gold); background:#fffdf5; transform:translateY(-1px); }
.bm-pkg-card.selected { border:2px solid var(--admin-gold); background:rgba(212,160,23,0.08); box-shadow:0 4px 12px rgba(212,160,23,0.15); }
.bm-pkg-check {
    position:absolute; top:8px; right:8px; width:18px; height:18px;
    background:var(--grad-gold); border-radius:50%; display:none;
    align-items:center; justify-content:center; font-size:8px; color:#121214; font-weight:700;
}
.bm-pkg-card.selected .bm-pkg-check { display:flex; }
.bm-pkg-card.popular-pkg::before { content:'⭐ Popular'; position:absolute; top:-1px; left:10px; background:var(--grad-gold); color:#121214; font-size:0.62rem; font-weight:800; padding:2px 7px; border-radius:0 0 6px 6px; }
.bm-pkg-name { font-family:'Syne',sans-serif; font-weight:700; font-size:0.88rem; color:var(--admin-text-dark); margin-bottom:0.25rem; }
.bm-pkg-price { font-family:'Syne',sans-serif; font-weight:800; font-size:1.02rem; color:var(--admin-gold-dark); margin-bottom:0.25rem; }
.bm-pkg-types { font-size:0.75rem; color:#64748b; }

.bm-tint-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(85px,1fr)); gap:0.45rem; }
.bm-tint-card {
    display:flex; flex-direction:column; align-items:center; gap:0.25rem;
    padding:0.6rem 0.4rem; background:#ffffff;
    border:1px solid #e2e8f0; border-radius:10px;
    cursor:pointer; transition:all 0.2s ease; color:#334155;
    font-size:0.72rem; font-weight:700; font-family:'DM Sans',sans-serif;
    box-shadow:0 2px 4px rgba(0,0,0,0.02);
}
.bm-tint-card i { font-size:0.95rem; color:#64748b; transition:color 0.2s; }
.bm-tint-card:hover { border-color:var(--admin-gold); background:#fffdf5; color:var(--admin-gold-dark); }
.bm-tint-card.selected { border:2px solid var(--admin-gold); background:rgba(212,160,23,0.1); color:var(--admin-gold-dark); }
.bm-tint-card.selected i { color:var(--admin-gold-dark); }
.bm-tint-card.disabled { opacity:0.35; cursor:not-allowed; pointer-events:none; }

.bm-shade-pills { display:flex; gap:0.5rem; flex-wrap:wrap; }
.bm-shade-pill {
    padding:0.45rem 0.85rem; background:#ffffff;
    border:1.5px solid #cbd5e1; border-radius:12px;
    color:#1e293b; font-family:'DM Sans',sans-serif;
    cursor:pointer; transition:all 0.2s ease;
    display:inline-flex; flex-direction:column; align-items:center; justify-content:center;
    min-width:80px; text-align:center;
}
.bm-shade-pill:hover { border-color:var(--admin-gold); color:var(--admin-gold-dark); background:#fffdf5; }
.bm-shade-pill:hover .bm-shade-tag { color:var(--admin-gold-dark); }
.bm-shade-pill.selected { background:var(--grad-header); border-color:var(--admin-gold-dark); color:#ffffff; box-shadow:0 3px 10px rgba(184,134,11,0.25); }
.bm-shade-pill.selected .bm-shade-tag { color:#fef08a; }
.bm-shade-pill.disabled { opacity:0.35; cursor:not-allowed; pointer-events:none; }
.bm-shade-num { font-weight:800; font-size:0.95rem; line-height:1.15; }
.bm-shade-tag { font-size:0.67rem; font-weight:600; color:#64748b; margin-top:2px; white-space:nowrap; }

.bm-window-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:0.5rem; }
.bm-window-card {
    background:#ffffff; border:1px solid #e2e8f0;
    border-radius:10px; padding:0.65rem 0.5rem; text-align:center; cursor:pointer;
    transition:all 0.2s ease; position:relative; box-shadow:0 2px 4px rgba(0,0,0,0.02);
}
.bm-window-card:hover { border-color:var(--admin-gold); background:#fffdf5; }
.bm-window-card.checked { border:2px solid var(--admin-gold); background:rgba(212,160,23,0.08); }
.bm-window-card.full-card { border-style:dashed; }
.bm-window-card.full-card.checked { border-style:solid; }
.bm-wc-check {
    position:absolute; top:5px; right:5px; width:16px; height:16px;
    background:var(--grad-gold); border-radius:50%; display:none;
    align-items:center; justify-content:center; font-size:7px; color:#121214; font-weight:700;
}
.bm-window-card.checked .bm-wc-check { display:flex; }
.bm-wc-icon { font-size:1.1rem; color:#64748b; margin-bottom:0.3rem; transition:color 0.2s; }
.bm-window-card.checked .bm-wc-icon { color:var(--admin-gold-dark); }
.fa-truck-suv {
    display: inline-block !important;
    width: 1.25em !important;
    height: 1em !important;
    vertical-align: -0.15em !important;
    background-color: currentColor !important;
    -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 122.88 61.11'%3E%3Cpath fill='black' d='M65.22,0h26.54h6.63c0.38,0,0.52,0.34,0.68,0.68l2.66,5.53c0.16,0.34-0.31,0.68-0.68,0.68H55.93 C52.98,6.9,58.02,0,65.22,0z M23.83,45.74v3.74h3.74C27.12,47.65,25.67,46.19,23.83,45.74z M27.57,51.93h-3.74 v3.74C25.67,55.22,27.12,53.77,27.57,51.93z M21.39,55.67v-3.74h-3.74C18.1,53.77,19.55,55.22,21.39,55.67z M17.64,49.49h3.74v-3.74C19.55,46.2,18.1,47.65,17.64,49.49z M90.05,45.74v3.74h3.74 C93.34,47.65,91.89,46.19,90.05,45.74z M93.79,51.93h-3.74v3.74C91.89,55.22,93.34,53.77,93.79,51.93z M87.61,55.67v-3.74h-3.74C84.31,53.77,85.76,55.22,87.61,55.67z M83.86,49.49h3.74v-3.74 C85.76,46.2,84.31,47.65,83.86,49.49z M89.66,12.77h3.52c5.08,0.23,7.17,0.05,9.19,1.59 c2.5,1.91,3.48,10.35,4.52,14.25c0.07,0.26-0.23,0.5-0.5,0.5H90.88c-0.27,0-0.47-0.22-0.5-0.5l-1.22-15.35 C89.14,13,89.39,12.77,89.66,12.77z M66.09,12.86h-11.5c-5.25,0-13.89,8.63-16.15,15.61 c-0.11,0.34,0.29,0.65,0.65,0.65h25.18c0.36,0,0.6-0.29,0.65-0.65l1.83-14.96C66.78,13.15,66.44,12.86,66.09,12.86z M71.07,12.8h14.07c0.27,0,0.48,0.23,0.5,0.5l1.07,15.32c0.02,0.27-0.23,0.5-0.5,0.5H69.24c-0.27,0-0.53-0.23-0.5-0.5l1.83-15.32 C70.6,13.02,70.79,12.8,71.07,12.8z M22.61,40.31c5.74,0,10.4,4.66,10.4,10.4c0,5.74-4.66,10.4-10.4,10.4 c-5.74,0-10.4-4.66-10.4-10.4C12.21,44.97,16.87,40.31,22.61,40.31z M1.81,40.78c0.53-0.24,1.11-0.35,1.74-0.34 c-0.03-3.28,0.19-6.14,0.95-8.17c0.28-1.17,0.79-2.02,1.47-2.64c2.14-1.91,21.25-3.59,25.28-4.11c4.95-4.84,10.46-9.27,16.33-13.46 c1.63-1.38,3.96-2.06,6.9-2.13l46.41-0.04c3.51-0.02,6.21,1.48,7.93,4.85l3.87,11.15l0.88-0.24v-4.34 c-0.16-1.45,0.41-2.17,1.52-2.35h5.2c1.43,0.06,2.44,0.78,2.6,2.85v15.42c0.01,1.59-0.57,2.63-2.04,2.85h-4.83 c-0.48,0.11-0.33,0.48-0.37,0.99v5.26c-0.35,3.88-1.41,6.88-4.52,7.27h-9.41c0.2-4.56-0.66-8.29-2.75-11.08 c-7.59-10.14-24.06-4.1-22.95,11.11H35.58c0.34-4.86-0.58-8.55-2.59-11.2c-7.57-9.99-25.18-4.6-23.1,11.51H3.64 C-0.13,53.94-1.36,42.19,1.81,40.78z M88.83,40.31c5.74,0,10.4,4.66,10.4,10.4c0,5.74-4.66,10.4-10.4,10.4 c-5.74,0-10.4-4.66-10.4-10.4C78.43,44.97,83.08,40.31,88.83,40.31z'/%3E%3C/svg%3E") no-repeat center / contain !important;
    mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 122.88 61.11'%3E%3Cpath fill='black' d='M65.22,0h26.54h6.63c0.38,0,0.52,0.34,0.68,0.68l2.66,5.53c0.16,0.34-0.31,0.68-0.68,0.68H55.93 C52.98,6.9,58.02,0,65.22,0z M23.83,45.74v3.74h3.74C27.12,47.65,25.67,46.19,23.83,45.74z M27.57,51.93h-3.74 v3.74C25.67,55.22,27.12,53.77,27.57,51.93z M21.39,55.67v-3.74h-3.74C18.1,53.77,19.55,55.22,21.39,55.67z M17.64,49.49h3.74v-3.74C19.55,46.2,18.1,47.65,17.64,49.49z M90.05,45.74v3.74h3.74 C93.34,47.65,91.89,46.19,90.05,45.74z M93.79,51.93h-3.74v3.74C91.89,55.22,93.34,53.77,93.79,51.93z M87.61,55.67v-3.74h-3.74C84.31,53.77,85.76,55.22,87.61,55.67z M83.86,49.49h3.74v-3.74 C85.76,46.2,84.31,47.65,83.86,49.49z M89.66,12.77h3.52c5.08,0.23,7.17,0.05,9.19,1.59 c2.5,1.91,3.48,10.35,4.52,14.25c0.07,0.26-0.23,0.5-0.5,0.5H90.88c-0.27,0-0.47-0.22-0.5-0.5l-1.22-15.35 C89.14,13,89.39,12.77,89.66,12.77z M66.09,12.86h-11.5c-5.25,0-13.89,8.63-16.15,15.61 c-0.11,0.34,0.29,0.65,0.65,0.65h25.18c0.36,0,0.6-0.29,0.65-0.65l1.83-14.96C66.78,13.15,66.44,12.86,66.09,12.86z M71.07,12.8h14.07c0.27,0,0.48,0.23,0.5,0.5l1.07,15.32c0.02,0.27-0.23,0.5-0.5,0.5H69.24c-0.27,0-0.53-0.23-0.5-0.5l1.83-15.32 C70.6,13.02,70.79,12.8,71.07,12.8z M22.61,40.31c5.74,0,10.4,4.66,10.4,10.4c0,5.74-4.66,10.4-10.4,10.4 c-5.74,0-10.4-4.66-10.4-10.4C12.21,44.97,16.87,40.31,22.61,40.31z M1.81,40.78c0.53-0.24,1.11-0.35,1.74-0.34 c-0.03-3.28,0.19-6.14,0.95-8.17c0.28-1.17,0.79-2.02,1.47-2.64c2.14-1.91,21.25-3.59,25.28-4.11c4.95-4.84,10.46-9.27,16.33-13.46 c1.63-1.38,3.96-2.06,6.9-2.13l46.41-0.04c3.51-0.02,6.21,1.48,7.93,4.85l3.87,11.15l0.88-0.24v-4.34 c-0.16-1.45,0.41-2.17,1.52-2.35h5.2c1.43,0.06,2.44,0.78,2.6,2.85v15.42c0.01,1.59-0.57,2.63-2.04,2.85h-4.83 c-0.48,0.11-0.33,0.48-0.37,0.99v5.26c-0.35,3.88-1.41,6.88-4.52,7.27h-9.41c0.2-4.56-0.66-8.29-2.75-11.08 c-7.59-10.14-24.06-4.1-22.95,11.11H35.58c0.34-4.86-0.58-8.55-2.59-11.2c-7.57-9.99-25.18-4.6-23.1,11.51H3.64 C-0.13,53.94-1.36,42.19,1.81,40.78z M88.83,40.31c5.74,0,10.4,4.66,10.4,10.4c0,5.74-4.66,10.4-10.4,10.4 c-5.74,0-10.4-4.66-10.4-10.4C78.43,44.97,83.08,40.31,88.83,40.31z'/%3E%3C/svg%3E") no-repeat center / contain !important;
}
.fa-truck-suv:before { content:"" !important; display:none !important; }
.bm-wc-label { font-size:0.72rem; font-weight:700; color:#1e293b; margin-bottom:0.15rem; }
.bm-wc-price { font-family:'DM Sans',sans-serif; font-weight:700; font-size:0.75rem; color:#64748b; }
.bm-window-card.checked .bm-wc-price { color:var(--admin-gold-dark); }

.bm-window-total {
    display:flex; align-items:center; justify-content:space-between;
    background:rgba(212,160,23,0.08); border:1px solid rgba(212,160,23,0.25);
    border-radius:8px; padding:0.6rem 0.95rem; margin-top:0.75rem;
}

.bm-schedule-grid { display:grid; grid-template-columns:1fr 270px; gap:1rem; }
@media(max-width:768px){ .bm-schedule-grid { grid-template-columns:1fr; } }
.bm-date-card { background:#f8fafc; border:1px solid #e2e8f0; border-radius:var(--radius); padding:1.1rem 1.35rem; }
.bm-date-card h5 { font-family:'Syne',sans-serif; font-weight:800; font-size:0.92rem; color:var(--admin-label-gold); display:flex; align-items:center; gap:0.4rem; margin-bottom:0.85rem; text-transform:uppercase; }
.bm-time-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(115px,1fr)); gap:0.4rem; margin-top:0.5rem; }
.bm-slot {
    padding:0.5rem 0.65rem; border-radius:8px; border:1px solid #cbd5e1;
    background:#ffffff; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; gap:0.45rem;
}
.bm-slot.available:hover { border-color:var(--admin-gold); background:#fffdf5; }
.bm-slot.selected { border:2px solid var(--admin-gold); background:rgba(212,160,23,0.1); }
.bm-slot.unavailable { opacity:0.35; cursor:not-allowed; pointer-events:none; }
.bm-slot-dot { width:7px; height:7px; border-radius:50%; background:#94a3b8; flex-shrink:0; }
.bm-slot.available .bm-slot-dot { background:var(--success); }
.bm-slot.selected .bm-slot-dot { background:var(--admin-gold-dark); }
.bm-slot-time { font-family:'DM Sans',sans-serif; font-weight:700; font-size:0.8rem; color:#1e293b; }
.bm-slot.selected .bm-slot-time { color:var(--admin-gold-dark); }

.bm-summary-card {
    background:#f8fafc; border:1px solid #e2e8f0;
    border-radius:var(--radius); padding:1.1rem; position:sticky; top:15px;
}
.bm-summary-card h5 { font-family:'Syne',sans-serif; font-weight:800; font-size:0.9rem; color:var(--admin-label-gold); display:flex; align-items:center; gap:0.4rem; margin-bottom:0.75rem; text-transform:uppercase; }
.bm-s-item { display:flex; justify-content:space-between; align-items:flex-start; padding:0.35rem 0; border-bottom:1px solid #e2e8f0; gap:0.4rem; }
.bm-s-label { font-size:0.75rem; color:#64748b; flex-shrink:0; font-weight:600; }
.bm-s-value { font-size:0.78rem; font-weight:700; color:#0f172a; text-align:right; word-break:break-word; }
.bm-total-box {
    background:var(--grad-gold); border-radius:8px; padding:0.75rem 0.95rem; margin-top:0.75rem; text-align:center;
    box-shadow:0 4px 12px rgba(212,160,23,0.2);
}
.bm-total-label { font-size:0.68rem; text-transform:uppercase; letter-spacing:0.05em; color:rgba(0,0,0,0.7); font-weight:800; margin-bottom:0.15rem; }
.bm-total-amt { font-family:'Syne',sans-serif; font-weight:800; font-size:1.25rem; color:#121214; }

.bm-order-grid {
    display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:0.75rem;
    background:#f8fafc; border:1px solid #e2e8f0;
    border-radius:var(--radius); padding:1.1rem; margin-bottom:1.25rem;
}
.bm-order-cell-label { font-size:0.68rem; text-transform:uppercase; letter-spacing:0.04em; color:#64748b; font-weight:700; margin-bottom:0.25rem; }
.bm-order-cell-val { font-weight:700; color:#0f172a; font-size:0.85rem; }
.bm-order-cell-val.gold { color:var(--admin-gold-dark); font-family:'Syne',sans-serif; font-weight:800; font-size:1.15rem; }

.bm-pay-toggle { display:flex; gap:0.4rem; margin-bottom:0.75rem; }
.bm-pay-btn {
    flex:1; padding:0.6rem; border:1px solid #cbd5e1; border-radius:8px;
    background:#ffffff; color:#334155; font-size:0.8rem; font-weight:600; cursor:pointer;
    transition:all 0.2s; display:flex; align-items:center; justify-content:center; gap:0.4rem; font-family:inherit;
}
.bm-pay-btn.active { border:2px solid var(--admin-gold); background:rgba(212,160,23,0.1); color:var(--admin-gold-dark); font-weight:700; }

.bm-footer {
    background:#f8fafc; border-top:1px solid #e2e8f0;
    border-radius:0 0 var(--radius) var(--radius);
    padding:0.75rem 1.35rem; margin:1.25rem -1.75rem -1.5rem -1.75rem;
    display:flex; align-items:center;
}
.bm-footer-inner { width:100%; display:flex; align-items:center; justify-content:space-between; gap:1rem; }
.bm-step-counter { font-size:0.78rem; color:#64748b; font-weight:600; }
.bm-step-counter strong { color:var(--admin-gold-dark); font-weight:800; }
.bm-mini-progress { height:4px; background:#e2e8f0; border-radius:2px; width:100px; margin-top:3px; }
.bm-mini-bar { height:100%; background:var(--grad-header); border-radius:2px; transition:width 0.4s ease; }

.bm-btn-group { display:flex; gap:0.5rem; }
.bm-btn-prev {
    padding:0.5rem 1.15rem; border:1px solid #cbd5e1; border-radius:8px;
    background:#ffffff; color:#475569; font-size:0.84rem; font-weight:600; cursor:pointer; transition:all 0.2s; font-family:inherit;
}
.bm-btn-prev:hover:not(:disabled) { border-color:var(--admin-gold); color:var(--admin-gold-dark); background:#fffdf5; }
.bm-btn-prev:disabled { opacity:0.35; cursor:not-allowed; }
.bm-btn-next, .bm-btn-submit {
    padding:0.5rem 1.35rem; background:var(--grad-gold); border:none; border-radius:8px;
    color:#121214; font-weight:700; font-size:0.84rem; cursor:pointer; transition:all 0.2s;
    display:flex; align-items:center; gap:0.4rem; font-family:inherit;
    box-shadow:0 3px 10px rgba(212,160,23,0.25);
}
.bm-btn-next:hover, .bm-btn-submit:hover { transform:translateY(-1px); box-shadow:0 4px 14px rgba(212,160,23,0.35); }
.bm-alert-info {
    background:rgba(59,130,246,0.08); border:1px solid rgba(59,130,246,0.25);
    border-radius:8px; padding:0.6rem 0.85rem; font-size:0.78rem; color:#1e293b; margin-top:0.75rem;
}
.bm-alert-success {
    background:rgba(34,197,94,0.08); border:1px solid rgba(34,197,94,0.25);
    border-radius:8px; padding:0.6rem 0.85rem; font-size:0.78rem; color:#1e293b; margin-top:0.75rem;
}
.bm-input-group { display:flex; }
.bm-input-group-text {
    background:rgba(212,160,23,0.12); border:1px solid #cbd5e1; border-right:none;
    border-radius:var(--radius-sm) 0 0 var(--radius-sm); padding:7px 11px; color:var(--admin-gold-dark); font-weight:700; font-size:0.85rem;
    display:flex; align-items:center; flex-shrink:0;
}
.bm-input-group .bm-input { border-radius:0 var(--radius-sm) var(--radius-sm) 0 !important; border-left:none !important; }
</style>

<div id="bookingModalBox">
    
    <div class="bm-wizard-header">
        <button class="bm-close-btn" onclick="closeBookingModal()" title="Close">
            <i class="fas fa-times"></i>
        </button>
        <h2><i class="fas fa-circle-half-stroke me-2"></i>Book a Tint Appointment</h2>
        <p>Complete the 3 steps below to secure your slot at RG CARTINTWORKS.</p>
        <div class="bm-wizard-steps" id="bmWizardSteps">
            <div class="bm-step active" data-step="1">
                <div class="bm-step-bubble">1</div>
                <div class="bm-step-label">Vehicle &amp; Tint</div>
            </div>
            <div class="bm-step" data-step="2">
                <div class="bm-step-bubble">2</div>
                <div class="bm-step-label">Schedule</div>
            </div>
            <div class="bm-step" data-step="3">
                <div class="bm-step-bubble">3</div>
                <div class="bm-step-label">Summary &amp; Pay</div>
            </div>
        </div>
    </div>

    <div class="bm-wizard-content">
        <?php if (!empty($bm_error_msg ?? '')): ?>
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;padding:1rem;margin-bottom:1.5rem;color:#FC8181;">
            <i class="fas fa-triangle-exclamation me-2"></i><?= $bm_error_msg ?>
        </div>
        <?php endif; ?>

        
        <div class="bm-panel active" id="bm-panel-1">
            <div class="bm-section-title">
                <div class="bm-title-icon"><i class="fas fa-car"></i></div>
                Your Car
            </div>
            <p style="color:#64748b;font-size:0.85rem;margin-bottom:1.25rem;">Select your car model — pricing updates automatically.</p>

            
            <div style="margin-bottom:1rem;">
                <label class="bm-label"><i class="fas fa-car me-1" style="color:var(--gold)"></i>Car Model <span style="color:var(--danger)">*</span></label>
                <div id="bmLockedBanner" style="display:none;margin-bottom:0.4rem;background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px;padding:0.4rem 0.75rem;align-items:center;gap:0.5rem;">
                    <i class="fas fa-lock" style="color:#D97706"></i>
                    <span id="bmLockedText" style="font-size:0.78rem;color:#78350F;font-weight:600;"></span>
                </div>
                <select id="bmCarModel" class="bm-select">
                    <option value="">— Select your car model —</option>
                    <?php foreach($bm_car_list as $cm): ?>
                    <option value="<?= htmlspecialchars($cm) ?>"><?= htmlspecialchars($cm) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="bm-validation-error" id="bm-model-error">Please select your car model</div>
            </div>

            
            <div class="bm-section-title" style="margin-top:1.25rem;">
                <div class="bm-title-icon"><i class="fas fa-car-side"></i></div>
                Vehicle Type
            </div>
            <div class="bm-pill-group mb-3" id="bmVehiclePills">
                <?php foreach($bm_vehicles as $vi): ?>
                <button type="button" class="bm-pill"
                        data-id="<?= $vi['id'] ?>"
                        data-category="<?= htmlspecialchars($vi['price_category'] ?? 'sedan') ?>"
                        data-name="<?= htmlspecialchars($vi['type_name']) ?>">
                    <?= bmGetVehicleIconHtml($vi) ?><?= htmlspecialchars($vi['type_name']) ?>
                </button>
                <?php endforeach; ?>
            </div>

            
            <div id="bmTintWrap" style="display:block;">
                <div class="bm-section-title">
                    <div class="bm-title-icon"><i class="fas fa-swatchbook"></i></div>
                    Tint Film Type &amp; Shade
                </div>
                <div class="bm-tint-grid mb-3" id="bmTintGrid">
                    <?php foreach($bm_tint_labels as $key=>$label):
                        $icon = $bm_tint_icons[$key] ?? 'fa-circle'; ?>
                    <button type="button" class="bm-tint-card" data-tint="<?= $key ?>" onclick="bmSelectTint('<?= $key ?>')">
                        <i class="fas <?= $icon ?>"></i><span><?= htmlspecialchars($label) ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
                <label class="bm-label mb-2"><i class="fas fa-circle-half-stroke me-1" style="color:var(--gold)"></i>Shade % (LTO Allowable)</label>
                <div class="bm-shade-pills mb-2" id="bmShadePills">
                    <?php 
                    $bm_shade_meta = [
                        70 => ['tag' => 'Light',        'hint' => 'Windshield (LTO Min)'],
                        50 => ['tag' => 'Medium-Light', 'hint' => 'Front Sides (LTO Min)'],
                        35 => ['tag' => 'Medium',       'hint' => 'Standard / Balanced'],
                        20 => ['tag' => 'Dark',         'hint' => 'Privacy / Rear Glass'],
                        5  => ['tag' => 'Super Dark',   'hint' => 'Limo Tint / Rear Glass']
                    ];
                    foreach($bm_shades as $sh): 
                        $sm = $bm_shade_meta[$sh] ?? ['tag'=>'', 'hint'=>''];
                    ?>
                    <button type="button" class="bm-shade-pill" data-shade="<?= $sh ?>" onclick="bmSelectShade(<?= $sh ?>)" title="<?= htmlspecialchars($sm['hint']) ?>">
                        <span class="bm-shade-num"><?= $sh ?>%</span>
                        <span class="bm-shade-tag"><?= htmlspecialchars($sm['tag']) ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>

                
                <div class="bm-shade-guide-bar" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:0.5rem 0.85rem;margin-top:0.4rem;font-size:0.75rem;color:#475569;display:flex;align-items:flex-start;gap:0.55rem;line-height:1.4;">
                    <i class="fas fa-shield-halved" style="color:#D97706;margin-top:2px;font-size:0.88rem;flex-shrink:0;"></i>
                    <div>
                        <strong style="color:#0F172A;">LTO Tint Compliance:</strong>
                        <span style="color:#0369A1;font-weight:700;">70%</span> is Light (minimum allowed on front windshield) &bull;
                        <span style="color:#0369A1;font-weight:700;">50%</span> is Medium-Light (minimum allowed on front side windows) &bull;
                        <span style="color:#64748B;">35%, 20%, 5% are darker shades for rear glass.</span>
                    </div>
                </div>

                <div id="bmComboInfo" style="display:none;font-size:0.82rem;color:#334155;padding:0.6rem 0.9rem;background:#FFFBEB;border:1.5px solid #FCD34D;border-radius:8px;margin-top:0.6rem;">
                    <div style="display:flex;align-items:center;gap:0.45rem;">
                        <i class="fas fa-circle-check" style="color:#D97706"></i>
                        <span style="color:#78350F;font-weight:600;">Selected:</span>
                        <strong id="bmComboLabel" style="color:#92400E;font-weight:700;"></strong>
                    </div>
                    <div id="bmComboDesc" style="font-size:0.75rem;color:#475569;margin-top:3px;margin-left:1.35rem;"></div>
                </div>
            </div>

            
            <div id="bmWindowWrap" style="display:none;">
                <hr class="bm-divider">
                <div class="bm-section-title">
                    <div class="bm-title-icon"><i class="fas fa-border-all"></i></div>
                    Select Windows to Tint
                </div>
                <p style="color:#64748b;font-size:0.84rem;margin-bottom:1rem;">
                    Choose <strong style="color:var(--gold)">Full Car</strong> for all windows, or select individual panes — price updates live.
                </p>
                <div class="bm-validation-error mb-2" id="bm-windows-error">Please select at least one window</div>
                <div class="bm-window-grid" id="bmWindowGrid"></div>
                <div id="bmWindowTotal" class="bm-window-total" style="display:none;">
                    <span style="color:#64748b;font-size:0.85rem;">Windows Total</span>
                    <span id="bmWindowTotalAmt" style="font-family:'Syne',sans-serif;font-weight:800;color:var(--gold);font-size:1.1rem;">₱0</span>
                </div>
            </div>
        </div>

        
        <div class="bm-panel" id="bm-panel-2">
            <div class="bm-schedule-grid">
                <div class="bm-date-card">
                    <h5><i class="fas fa-calendar-days" style="color:var(--gold)"></i>Date &amp; Time</h5>
                    <div style="margin-bottom:1rem;">
                        <label class="bm-label"><i class="fas fa-calendar me-1" style="color:var(--gold)"></i>Preferred Date <span style="color:var(--danger)">*</span></label>
                        <input type="date" id="bmDate" class="bm-input">
                        <div class="bm-validation-error" id="bm-date-error">Please pick a valid weekday</div>
                    </div>
                    <label class="bm-label"><i class="fas fa-clock me-1" style="color:var(--gold)"></i>Time Slot <span style="color:var(--danger)">*</span></label>
                    <div class="bm-time-grid" id="bmTimeSlots">
                        <div style="grid-column:1/-1;text-align:center;padding:1.5rem;color:#94a3b8;font-size:0.84rem;">
                            <i class="fas fa-calendar-day" style="font-size:1.8rem;display:block;margin-bottom:0.5rem;color:#cbd5e1;"></i>
                            Select a date to see available slots
                        </div>
                    </div>
                    <div class="bm-validation-error" id="bm-time-error">Please select a time slot</div>
                    <div style="margin-top:1.25rem;">
                        <label class="bm-label">Additional Notes <span style="color:#94a3b8;font-size:0.78rem;">(optional)</span></label>
                        <textarea id="bmNotes" class="bm-textarea" rows="3" placeholder="Special requirements, parking instructions..."></textarea>
                    </div>
                    <div class="bm-alert-info" style="margin-top:1rem;">
                        <i class="fas fa-circle-info me-1"></i>
                        Closed on weekends &bull; 2-hr service + 30-min buffer &bull; Up to 3 months advance booking
                    </div>
                </div>
                
                <div class="bm-summary-card">
                    <h5><i class="fas fa-file-invoice-dollar" style="color:var(--gold)"></i>Your Selection</h5>
                    <small style="font-size:0.68rem;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;font-weight:700;display:block;margin-bottom:0.65rem;">From Step 1</small>
                    <div class="bm-s-item"><span class="bm-s-label">Car</span><span class="bm-s-value" id="s2Car">—</span></div>
                    <div class="bm-s-item"><span class="bm-s-label">Package</span><span class="bm-s-value" id="s2Pkg">—</span></div>
                    <div class="bm-s-item" id="s2TintRow" style="display:none;"><span class="bm-s-label">Tint</span><span class="bm-s-value" id="s2Tint">—</span></div>
                    <div class="bm-s-item" id="s2ShadeRow" style="display:none;"><span class="bm-s-label">Shade</span><span class="bm-s-value" id="s2Shade">—</span></div>
                    <div class="bm-s-item" id="s2WinRow" style="display:none;"><span class="bm-s-label">Windows</span><span class="bm-s-value" id="s2Win">—</span></div>
                    <div class="bm-total-box" style="margin-top:0.75rem;">
                        <div class="bm-total-label">Estimated Total</div>
                        <div class="bm-total-amt" id="s2Total">₱0.00</div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="bm-panel" id="bm-panel-3">
            
            <div class="bm-section-title"><div class="bm-title-icon"><i class="fas fa-receipt"></i></div>Order Summary</div>
            <div class="bm-order-grid">
                <div><div class="bm-order-cell-label">Package</div><div class="bm-order-cell-val" id="s3Pkg">—</div></div>
                <div><div class="bm-order-cell-label">Car Model</div><div class="bm-order-cell-val" id="s3Car">—</div></div>
                <div><div class="bm-order-cell-label">Tint / Shade</div><div class="bm-order-cell-val" id="s3Tint" style="color:var(--gold);">—</div></div>
                <div><div class="bm-order-cell-label">Windows</div><div class="bm-order-cell-val" id="s3Win">—</div></div>
                <div><div class="bm-order-cell-label">Appointment</div><div class="bm-order-cell-val" id="s3Appt">—</div></div>
                <div><div class="bm-order-cell-label">Total Amount</div><div class="bm-order-cell-val gold" id="s3Total">₱0.00</div></div>
            </div>

            
            <div class="bm-section-title"><div class="bm-title-icon"><i class="fas fa-address-card"></i></div>Contact Information</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                <div>
                    <label class="bm-label"><i class="fas fa-user me-1" style="color:var(--gold)"></i>Full Name <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="bmName" class="bm-input" placeholder="Juan Dela Cruz" value="<?= htmlspecialchars($bm_user_info['full_name'] ?? '') ?>">
                    <div class="bm-validation-error" id="bm-name-error">Full name is required</div>
                </div>
                <div>
                    <label class="bm-label"><i class="fas fa-phone me-1" style="color:var(--gold)"></i>Phone <span style="color:var(--danger)">*</span></label>
                    <div class="bm-input-group">
                        <span class="bm-input-group-text">+63</span>
                        <input type="tel" id="bmPhone" class="bm-input" placeholder="9XXXXXXXXX" maxlength="10" value="<?= htmlspecialchars($bm_user_info['phone'] ?? '') ?>">
                    </div>
                    <div class="bm-validation-error" id="bm-phone-error">10 digits starting with 9</div>
                </div>
            </div>
            <div>
                <label class="bm-label"><i class="fas fa-location-dot me-1" style="color:var(--gold)"></i>Address <span style="color:var(--danger)">*</span></label>
                <textarea id="bmAddress" class="bm-textarea" rows="2" placeholder="Street, Barangay, City, Province"><?= htmlspecialchars($bm_user_info['address'] ?? '') ?></textarea>
                <div class="bm-validation-error" id="bm-address-error">Address is required</div>
            </div>

            <hr class="bm-divider">

            
            <div class="bm-section-title"><div class="bm-title-icon"><i class="fas fa-wallet"></i></div>Payment Method</div>
            <div style="display:flex;align-items:center;gap:12px;background:rgba(0,102,255,0.06);border:1.5px solid rgba(0,102,255,0.2);border-radius:12px;padding:1rem 1.25rem;margin-bottom:1.25rem;">
                <div style="width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,#0066ff,#0052cc);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(0,102,255,0.25);">
                    <i class="fas fa-mobile-screen-button" style="color:#ffffff;font-size:1.15rem;"></i>
                </div>
                <div>
                    <div style="font-weight:800;color:var(--admin-text-dark,#0F172A);font-size:0.95rem;line-height:1.2;">GCash Payment</div>
                    <div style="font-size:0.78rem;color:#64748B;margin-top:2px;">Secure mobile payment · Powered by PayMongo</div>
                </div>
            </div>

            <div class="bm-pay-toggle">
                <button type="button" class="bm-pay-btn active" data-type="full" onclick="bmPayType('full')">
                    <i class="fas fa-circle-check"></i>Full Payment
                </button>
                <button type="button" class="bm-pay-btn" data-type="downpayment" onclick="bmPayType('downpayment')">
                    <i class="fas fa-percent"></i>Down Payment (10%)
                </button>
            </div>
            <div id="bm-full-note">
                <div class="bm-alert-success"><i class="fas fa-circle-check me-1" style="color:var(--success)"></i><strong>Full Payment:</strong> Pay the full amount online. Booking confirmed after verification.</div>
            </div>
            <div id="bm-dp-note" style="display:none;">
                <div class="bm-alert-info"><i class="fas fa-info-circle me-1" style="color:var(--gold)"></i><strong>Down Payment (10%):</strong> <span id="bm-dp-note-text">Pay 10% now as a deposit. Remaining balance is due on appointment day.</span></div>
            </div>
        </div>
        
        <div class="bm-footer">
            <div class="bm-footer-inner">
                <div class="bm-step-counter">
                    Step <strong id="bmCurrentStep">1</strong> of 3
                    <div class="bm-mini-progress"><div class="bm-mini-bar" id="bmProgressBar" style="width:33%"></div></div>
                </div>
                <div class="bm-btn-group">
                    <button class="bm-btn-prev" id="bmPrevBtn" onclick="bmPrev()" disabled>
                        <i class="fas fa-arrow-left me-1"></i>Previous
                    </button>
                    <button class="bm-btn-next" id="bmNextBtn" onclick="bmNext()">
                        Next<i class="fas fa-arrow-right ms-1"></i>
                    </button>
                    <button class="bm-btn-submit" id="bmSubmitBtn" style="display:none" onclick="bmSubmit()">
                        <i class="fas fa-calendar-check me-1"></i>Confirm Booking
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="book.php" id="bmBookingForm" style="display:none;">
    <input type="hidden" name="name" id="bmF_name">
    <input type="hidden" name="phone" id="bmF_phone">
    <input type="hidden" name="address" id="bmF_address">
    <input type="hidden" name="model" id="bmF_model">
    <input type="hidden" name="vehicle_type" id="bmF_vtype">
    <input type="hidden" name="package" id="bmF_pkg">
    <input type="hidden" name="date" id="bmF_date">
    <input type="hidden" name="time" id="bmF_time">
    <input type="hidden" name="notes" id="bmF_notes">
    <input type="hidden" name="payment_method" id="bmF_paymethod" value="GCash">
    <input type="hidden" name="payment_type" id="bmF_paytype" value="full">
    <input type="hidden" name="payment_reference" value="">
    <input type="hidden" name="payment_details" value="">
    <input type="hidden" name="customization_data" id="bmF_custdata">
    <input type="hidden" name="selected_tint_type" id="bmF_tint">
    <input type="hidden" name="selected_shade" id="bmF_shade">
    <input type="hidden" name="car_quantity" value="1">
</form>

</div>

<script>
(function() {
'use strict';

const BM_CARS     = <?= json_encode($bm_car_list) ?>;
const BM_CAT_MAP  = <?= json_encode($bm_car_model_cats) ?>;
const BM_PRICES   = <?= json_encode($bm_pkg_model_prices) ?>;
const BM_PKG_DEF  = <?= json_encode($bm_pkg_defaults) ?>;
const BM_TINT_LBL = <?= json_encode($bm_tint_labels) ?>;

const WIN_CFG = {
    sedan:[
        {key:'full',label:'Full Car',icon:'fa-car',pct:1.00,full:true},
        {key:'windshield',label:'Windshield',icon:'fa-wind',pct:0.25},
        {key:'front_win',label:'Front Windows',icon:'fa-door-open',pct:0.20},
        {key:'rear_win',label:'Rear Windows',icon:'fa-car-rear',pct:0.20},
        {key:'rear_ws',label:'Rear Windshield',icon:'fa-window-restore',pct:0.20},
        {key:'sunstrip',label:'Sunstrip',icon:'fa-sun',pct:0.15},
    ],
    suv:[
        {key:'full',label:'Full Vehicle',icon:'fa-truck-suv',pct:1.00,full:true},
        {key:'windshield',label:'Windshield',icon:'fa-wind',pct:0.25},
        {key:'front_win',label:'Front Windows',icon:'fa-door-open',pct:0.20},
        {key:'side_win',label:'Side Windows',icon:'fa-car-side',pct:0.25},
        {key:'rear_ws',label:'Rear Windshield',icon:'fa-window-restore',pct:0.20},
        {key:'sunstrip',label:'Sunstrip',icon:'fa-sun',pct:0.10},
    ],
    van:[
        {key:'full',label:'Full Vehicle',icon:'fa-van-shuttle',pct:1.00,full:true},
        {key:'windshield',label:'Windshield',icon:'fa-wind',pct:0.20},
        {key:'front_win',label:'Front Windows',icon:'fa-door-open',pct:0.15},
        {key:'side_win',label:'Side Windows',icon:'fa-car-side',pct:0.40},
        {key:'rear_ws',label:'Rear Windshield',icon:'fa-window-restore',pct:0.15},
        {key:'sunstrip',label:'Sunstrip',icon:'fa-sun',pct:0.10},
    ],
    pickup:[
        {key:'full',label:'Full Truck',icon:'fa-truck-pickup',pct:1.00,full:true},
        {key:'windshield',label:'Windshield',icon:'fa-wind',pct:0.30},
        {key:'front_win',label:'Front Windows',icon:'fa-door-open',pct:0.25},
        {key:'rear_win',label:'Cab/Rear Windows',icon:'fa-car-side',pct:0.25},
        {key:'rear_ws',label:'Rear Glass',icon:'fa-window-restore',pct:0.20},
        {key:'sunstrip',label:'Sunstrip',icon:'fa-sun',pct:0.10},
    ]
};

const BM_PKGS_MAP = <?= json_encode(array_column($bm_packages, 'name', 'id')) ?>;

let BM = {
    step: 1,
    model:'', vehicle_type:'', vehicle_cat:'sedan', vehicle_name:'',
    package:'', pkg_name:'', pkg_price:0,
    tint_type:'', shade:0,
    windows:['full'],
    date:'', time:'', notes:'',
    name: <?= json_encode($bm_user_info['full_name'] ?? '') ?>,
    phone: <?= json_encode($bm_user_info['phone'] ?? '') ?>,
    address: <?= json_encode($bm_user_info['address'] ?? '') ?>,
    pay_type:'full',
};

window.openBookingModal = function(pkgId, carModel, mode) {
    document.getElementById('bookingModalOverlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    BM.step = 1;
    BM.mode = mode ? mode : (carModel ? 'custom' : 'package');

    let selectedPkgId = pkgId ? String(pkgId) : (Object.keys(BM_PKGS_MAP)[0] || '');
    BM.package = selectedPkgId;
    BM.pkg_name = BM_PKGS_MAP[selectedPkgId] || '';
    bmFilterCarsByPackage(BM.package);
    bmApplyTintAvailability();

    const carSel = document.getElementById('bmCarModel');
    if (!carModel) {
        if (carSel) carSel.value = '';
        BM.car_model = '';
    }

    bmUpdateWizard();

    if (carModel) {
        setTimeout(() => {
            const sel = document.getElementById('bmCarModel');
            if (sel) {
                sel.value = carModel;
                bmApplyCarModel(carModel);
                if (!sel.value) {
                    const opt = document.createElement('option');
                    opt.value = carModel; opt.textContent = carModel;
                    sel.appendChild(opt);
                    sel.value = carModel;
                    bmApplyCarModel(carModel);
                }
            }
        }, 100);
    }
    window.scrollTo(0,0);
};

window.closeBookingModal = function() {
    document.getElementById('bookingModalOverlay').style.display = 'none';
    document.body.style.overflow = '';
};

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeBookingModal(); });

function bmUpdateWizard() {
    
    document.querySelectorAll('.bm-step').forEach(s => {
        const n = parseInt(s.dataset.step);
        s.classList.toggle('active', n === BM.step);
        s.classList.toggle('completed', n < BM.step);
        const bubble = s.querySelector('.bm-step-bubble');
        bubble.innerHTML = n < BM.step ? '<i class="fas fa-check" style="font-size:0.75rem"></i>' : n;
    });
    
    document.querySelectorAll('.bm-panel').forEach(p => p.classList.remove('active'));
    const panel = document.getElementById(`bm-panel-${BM.step}`);
    if (panel) panel.classList.add('active');
    
    const pct = ((BM.step - 1) / 2) * 100;
    document.getElementById('bmWizardSteps').style.setProperty('--bm-progress-width', pct + '%');
    document.getElementById('bmCurrentStep').textContent = BM.step;
    document.getElementById('bmProgressBar').style.width = (BM.step / 3 * 100) + '%';
    
    document.getElementById('bmPrevBtn').disabled = BM.step === 1;
    document.getElementById('bmNextBtn').style.display = BM.step === 3 ? 'none' : 'flex';
    document.getElementById('bmSubmitBtn').style.display = BM.step === 3 ? 'flex' : 'none';
    
    if (BM.step === 2) bmInitSchedule();
    if (BM.step === 3) bmPopulateSummary();
}

window.bmNext = function() {
    if (!bmValidateStep()) { bmShowErrors(); return; }
    BM.step++;
    bmUpdateWizard();
    document.getElementById('bookingModalOverlay').scrollTo({top:0,behavior:'smooth'});
};
window.bmPrev = function() {
    if (BM.step > 1) { BM.step--; bmUpdateWizard(); }
};

function bmValidateStep() {
    if (BM.step === 1) return !!(BM.model && BM.package && BM.pkg_price > 0 && BM.windows.length > 0);
    if (BM.step === 2) return !!(BM.date && BM.time);
    if (BM.step === 3) {
        return !!(BM.name.trim() && /^9[0-9]{9}$/.test(BM.phone) && BM.address.trim());
    }
    return true;
}
function bmShowErrors() {
    if (BM.step === 1) {
        show('bm-model-error', !BM.model);
        show('bm-package-error', !BM.package);
        show('bm-windows-error', BM.windows.length === 0);
    }
    if (BM.step === 2) {
        show('bm-date-error', !BM.date);
        show('bm-time-error', !BM.time);
    }
    if (BM.step === 3) {
        show('bm-name-error', !BM.name.trim());
        show('bm-phone-error', !/^9[0-9]{9}$/.test(BM.phone));
        show('bm-address-error', !BM.address.trim());
    }
}
function show(id, condition) {
    const el = document.getElementById(id);
    if (el) el.style.display = condition ? 'block' : 'none';
}

function bmApplyCarModel(val) {
    BM.model = val;
    show('bm-model-error', !val);
    if (val) {
        bmAutoDetectVehicle(val);
        bmUpdateAllPrices();
    }
    bmUpdateSummary2();
}
document.getElementById('bmCarModel').addEventListener('change', function() { bmApplyCarModel(this.value); });

function bmAutoDetectVehicle(model) {
    let cat = BM_CAT_MAP[model] || null;
    if (!cat) {
        const m = model.toLowerCase();
        if (['fortuner','everest','cr-v','tucson','sportage','montero','terra','trailblazer','mux'].some(k=>m.includes(k))) cat='suv';
        else if (['hiace','starex','carnival','urvan','grandia','ertiga','commuter','staria'].some(k=>m.includes(k))) cat='van';
        else cat='sedan';
    }
    const pill = document.querySelector(`#bmVehiclePills .bm-pill[data-category="${cat}"]`);
    if (pill && !pill.classList.contains('active')) pill.click();
}

document.querySelectorAll('#bmVehiclePills .bm-pill').forEach(pill => {
    pill.addEventListener('click', function() {
        document.querySelectorAll('#bmVehiclePills .bm-pill').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        BM.vehicle_type = this.dataset.id;
        BM.vehicle_cat = this.dataset.category;
        BM.vehicle_name = this.dataset.name;
        if (BM.package && BM.model) {
            const bp = bmGetComboPrice(BM.package, BM.model);
            if (bp) { BM.windows = ['full']; bmRenderWindows(BM.vehicle_cat, bp); }
        }
        bmUpdateSummary2();
    });
});

window.bmSelectPackage = function(card) {
    document.querySelectorAll('.bm-pkg-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    BM.package = card.dataset.id;
    BM.pkg_name = card.querySelector('.bm-pkg-name').textContent;
    bmFilterCarsByPackage(BM.package);
    bmApplyTintAvailability();
    document.getElementById('bmTintWrap').style.display = BM.package ? 'block' : 'none';
    if (BM.model) {
        const bp = bmGetComboPrice(BM.package, BM.model);
        BM.pkg_price = bp;
        if (bp) bmRenderWindows(BM.vehicle_cat, bp);
    }
    bmUpdateAllPrices();
    bmUpdateSummary2();
    show('bm-package-error', false);
};

function bmFilterCarsByPackage(pkgId) {
    const sel = document.getElementById('bmCarModel');
    const pkgPrices = BM_PRICES[pkgId] || {};
    const pkgCars = new Set();
    Object.values(pkgPrices).forEach(m => Object.keys(m).forEach(c => pkgCars.add(c)));
    if (pkgCars.size === 0) return;
    const cur = sel.value;
    sel.innerHTML = '<option value="">— Select your car model —</option>';
    pkgCars.forEach(cm => {
        const o = document.createElement('option');
        o.value = cm; o.textContent = cm; sel.appendChild(o);
    });
    if (cur && pkgCars.has(cur)) { sel.value = cur; }
    else if (pkgCars.size === 1) {
        const only = [...pkgCars][0];
        sel.value = only; bmApplyCarModel(only);
    } else { sel.value = ''; }
}

function bmGetComboPrice(pkgId, model) {
    const pkgP = BM_PRICES[pkgId] || {};
    const key = (BM.tint_type || 'carbon') + '|' + (BM.shade || 35);
    if (pkgP[key] && pkgP[key][model]) return pkgP[key][model];
    for (const k in pkgP) { if (pkgP[k][model]) return pkgP[k][model]; }
    const card = document.querySelector(`.bm-pkg-card[data-id="${pkgId}"]`);
    if (card) {
        const fb = parseFloat(card.dataset.fallback) || 0;
        if (fb > 0) return fb;
    }
    const def = BM_PKG_DEF[pkgId] || {};
    if (BM.vehicle_cat === 'suv' && def.suv_price > 0) return def.suv_price;
    if (BM.vehicle_cat === 'van' && def.van_price > 0) return def.van_price;
    if (def.price > 0) return def.price;
    return 4500;
}

function bmUpdateAllPrices() {
    document.querySelectorAll('.bm-pkg-card').forEach(c => {
        const pid = c.dataset.id;
        const price = BM.model ? bmGetComboPrice(pid, BM.model) : 0;
        const el = document.getElementById(`bm-price-${pid}`);
        if (el) el.textContent = price ? '₱' + price.toLocaleString('en-PH',{minimumFractionDigits:0}) : 'Select car first';
    });
    if (BM.package && BM.model) {
        const bp = bmGetComboPrice(BM.package, BM.model);
        BM.pkg_price = bp;
        bmRenderWindows(BM.vehicle_cat, bp);
    }
    bmUpdateSummary2();
}

function bmApplyTintAvailability() {
    const def = BM_PKG_DEF[BM.package] || {};
    const allowed = def.tint_types && def.tint_types.length ? def.tint_types : null;
    document.querySelectorAll('.bm-tint-card').forEach(c => {
        if (allowed) {
            const ok = allowed.includes(c.dataset.tint);
            c.classList.toggle('disabled', !ok);
            c.style.display = ok ? '' : 'none';
        } else { c.classList.remove('disabled'); c.style.display = ''; }
    });
    if (allowed && !allowed.includes(BM.tint_type)) {
        BM.tint_type = allowed[0] || '';
    }
    bmUpdateTintUI();
}
window.bmSelectTint = function(val) {
    BM.tint_type = val;
    if (!BM.shade) BM.shade = 35;
    bmUpdateTintUI();
    bmUpdateAllPrices();
};
window.bmSelectShade = function(val) {
    if (!BM.tint_type) return;
    BM.shade = val;
    bmUpdateTintUI();
    bmUpdateAllPrices();
};
const BM_SHADE_DESCS = {
    70: '70% VLT (Light Tint) — High visibility & heat rejection. LTO legal minimum for front windshield.',
    50: '50% VLT (Medium-Light) — Clear road view with strong glare cut. LTO legal minimum for front side windows.',
    35: '35% VLT (Medium Tint) — Balanced privacy and day/night visibility.',
    20: '20% VLT (Dark Tint) — Deep privacy and high sun blocking (for rear windows).',
    5:  '5% VLT (Super Dark Tint) — Maximum limousine privacy (for rear windows).'
};

function bmUpdateTintUI() {
    document.querySelectorAll('.bm-tint-card').forEach(c => c.classList.toggle('selected', c.dataset.tint === BM.tint_type));
    document.querySelectorAll('.bm-shade-pill').forEach(p => p.classList.toggle('selected', parseInt(p.dataset.shade) === BM.shade));
    const info = document.getElementById('bmComboInfo');
    const lbl = document.getElementById('bmComboLabel');
    const desc = document.getElementById('bmComboDesc');
    if (info && BM.tint_type && BM.shade) {
        info.style.display = 'block';
        if (lbl) lbl.textContent = (BM_TINT_LBL[BM.tint_type] || BM.tint_type) + ' — ' + BM.shade + '%';
        if (desc) desc.textContent = BM_SHADE_DESCS[BM.shade] || '';
    } else if (info) { info.style.display = 'none'; }
}

function bmGetCardIconHtml(icon) {
    if (icon === 'fa-truck-suv') {
        return '<svg viewBox="0 0 122.88 61.11" width="1.25em" height="1.1rem" style="fill:currentColor;display:inline-block;margin-bottom:0.3rem;" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M65.22,0h26.54h6.63c0.38,0,0.52,0.34,0.68,0.68l2.66,5.53c0.16,0.34-0.31,0.68-0.68,0.68H55.93 C52.98,6.9,58.02,0,65.22,0z M23.83,45.74v3.74h3.74C27.12,47.65,25.67,46.19,23.83,45.74z M27.57,51.93h-3.74 v3.74C25.67,55.22,27.12,53.77,27.57,51.93z M21.39,55.67v-3.74h-3.74C18.1,53.77,19.55,55.22,21.39,55.67z M17.64,49.49h3.74v-3.74C19.55,46.2,18.1,47.65,17.64,49.49z M90.05,45.74v3.74h3.74 C93.34,47.65,91.89,46.19,90.05,45.74z M93.79,51.93h-3.74v3.74C91.89,55.22,93.34,53.77,93.79,51.93z M87.61,55.67v-3.74h-3.74C84.31,53.77,85.76,55.22,87.61,55.67z M83.86,49.49h3.74v-3.74 C85.76,46.2,84.31,47.65,83.86,49.49z M89.66,12.77h3.52c5.08,0.23,7.17,0.05,9.19,1.59 c2.5,1.91,3.48,10.35,4.52,14.25c0.07,0.26-0.23,0.5-0.5,0.5H90.88c-0.27,0-0.47-0.22-0.5-0.5l-1.22-15.35 C89.14,13,89.39,12.77,89.66,12.77z M66.09,12.86h-11.5c-5.25,0-13.89,8.63-16.15,15.61 c-0.11,0.34,0.29,0.65,0.65,0.65h25.18c0.36,0,0.6-0.29,0.65-0.65l1.83-14.96C66.78,13.15,66.44,12.86,66.09,12.86z M71.07,12.8h14.07c0.27,0,0.48,0.23,0.5,0.5l1.07,15.32c0.02,0.27-0.23,0.5-0.5,0.5H69.24c-0.27,0-0.53-0.23-0.5-0.5l1.83-15.32 C70.6,13.02,70.79,12.8,71.07,12.8z M22.61,40.31c5.74,0,10.4,4.66,10.4,10.4c0,5.74-4.66,10.4-10.4,10.4 c-5.74,0-10.4-4.66-10.4-10.4C12.21,44.97,16.87,40.31,22.61,40.31z M1.81,40.78c0.53-0.24,1.11-0.35,1.74-0.34 c-0.03-3.28,0.19-6.14,0.95-8.17c0.28-1.17,0.79-2.02,1.47-2.64c2.14-1.91,21.25-3.59,25.28-4.11c4.95-4.84,10.46-9.27,16.33-13.46 c1.63-1.38,3.96-2.06,6.9-2.13l46.41-0.04c3.51-0.02,6.21,1.48,7.93,4.85l3.87,11.15l0.88-0.24v-4.34 c-0.16-1.45,0.41-2.17,1.52-2.35h5.2c1.43,0.06,2.44,0.78,2.6,2.85v15.42c0.01,1.59-0.57,2.63-2.04,2.85h-4.83 c-0.48,0.11-0.33,0.48-0.37,0.99v5.26c-0.35,3.88-1.41,6.88-4.52,7.27h-9.41c0.2-4.56-0.66-8.29-2.75-11.08 c-7.59-10.14-24.06-4.1-22.95,11.11H35.58c0.34-4.86-0.58-8.55-2.59-11.2c-7.57-9.99-25.18-4.6-23.1,11.51H3.64 C-0.13,53.94-1.36,42.19,1.81,40.78z M88.83,40.31c5.74,0,10.4,4.66,10.4,10.4c0,5.74-4.66,10.4-10.4,10.4 c-5.74,0-10.4-4.66-10.4-10.4C78.43,44.97,83.08,40.31,88.83,40.31z"/></svg>';
    }
    return `<i class="fas ${icon} bm-wc-icon"></i>`;
}

function bmRenderWindows(cat, basePrice) {
    const wrap = document.getElementById('bmWindowWrap');
    const grid = document.getElementById('bmWindowGrid');
    if (!wrap || !grid || !basePrice || !BM.model) { if (wrap) wrap.style.display='none'; return; }

    const cfg = WIN_CFG[cat] || WIN_CFG.sedan;
    const fullKey = cfg.find(w=>w.full)?.key || 'full';

    let lockNotice = document.getElementById('bmWindowLockNotice');
    if (BM.mode === 'package') {
        BM.windows = [fullKey];
        if (!lockNotice) {
            lockNotice = document.createElement('div');
            lockNotice.id = 'bmWindowLockNotice';
            lockNotice.style.cssText = 'background:#FFFBEB;border:1.5px solid #FCD34D;border-radius:10px;padding:0.85rem 1.15rem;margin-bottom:1.1rem;color:#1E293B;font-size:0.85rem;display:flex;align-items:flex-start;gap:0.85rem;line-height:1.45;';
            lockNotice.innerHTML = '<i class="fas fa-lock" style="color:#D97706;font-size:1.2rem;margin-top:2px;flex-shrink:0;"></i><div><strong style="color:#92400E;display:block;margin-bottom:3px;font-size:0.88rem;">Fixed Package Coverage (Full Car)</strong><span style="font-size:0.82rem;color:#334155;display:block;">Package bookings include complete Full Car tinting. Customization is locked for package bookings. (To customize individual panes, click <strong style="color:#0F172A;text-decoration:underline;">"Book This Car"</strong>).</span></div>';
            const secTitle = wrap.querySelector('.bm-section-title');
            if (secTitle) secTitle.after(lockNotice);
        } else {
            lockNotice.style.display = 'flex';
        }

        grid.innerHTML = cfg.map(w => {
            const price = Math.round(basePrice * w.pct);
            const isFull = w.full;
            return `<div class="bm-window-card${isFull?' full-card checked':''}" data-key="${w.key}" style="opacity:${isFull?'1':'0.45'};cursor:not-allowed;position:relative;" onclick="bmToggleWin('${w.key}')">
                <div class="bm-wc-check">${isFull ? '<i class="fas fa-lock" style="font-size:9px;color:var(--gold)"></i>' : ''}</div>
                ${bmGetCardIconHtml(w.icon)}
                <div class="bm-wc-label">${w.label}</div>
                <div class="bm-wc-price">${isFull ? 'Included' : '₱'+price.toLocaleString('en-PH')}</div>
            </div>`;
        }).join('');

        wrap.style.display = 'block';
        bmUpdateWindowTotal(cfg, basePrice, fullKey);
        return;
    } else {
        if (lockNotice) lockNotice.style.display = 'none';
    }

    
    const valid = cfg.map(w=>w.key);
    BM.windows = BM.windows.filter(k=>valid.includes(k));
    if (!BM.windows.length) BM.windows = [fullKey];

    grid.innerHTML = cfg.map(w => {
        const price = Math.round(basePrice * w.pct);
        const checked = BM.windows.includes(w.key);
        return `<div class="bm-window-card${w.full?' full-card':''}${checked?' checked':''}" data-key="${w.key}" onclick="bmToggleWin('${w.key}')">
            <div class="bm-wc-check"><i class="fas fa-check" style="font-size:8px"></i></div>
            ${bmGetCardIconHtml(w.icon)}
            <div class="bm-wc-label">${w.label}</div>
            <div class="bm-wc-price">₱${price.toLocaleString('en-PH')}</div>
        </div>`;
    }).join('');

    wrap.style.display = 'block';
    bmUpdateWindowTotal(cfg, basePrice, fullKey);
}

window.bmToggleWin = function(key) {
    if (BM.mode === 'package') {
        const errEl = document.getElementById('bm-windows-error');
        if (errEl) {
            errEl.textContent = "Window customization is locked for package bookings. Click 'Book This Car' to customize individual window panes.";
            errEl.style.display = 'block';
            setTimeout(() => { errEl.style.display = 'none'; }, 4000);
        }
        return;
    }
    const cat = BM.vehicle_cat || 'sedan';
    const cfg = WIN_CFG[cat] || WIN_CFG.sedan;
    const fullKey = cfg.find(w=>w.full)?.key;
    const bp = bmGetComboPrice(BM.package, BM.model);
    if (key === fullKey) {
        BM.windows = [fullKey];
    } else {
        BM.windows = BM.windows.filter(k=>k!==fullKey);
        if (BM.windows.includes(key)) BM.windows = BM.windows.filter(k=>k!==key);
        else BM.windows.push(key);
        if (!BM.windows.length) BM.windows = [fullKey];
    }
    bmRenderWindows(cat, bp);
    bmUpdateSummary2();
    show('bm-windows-error', BM.windows.length === 0);
};

function bmUpdateWindowTotal(cfg, bp, fullKey) {
    const tot = BM.windows.includes(fullKey)
        ? bp
        : BM.windows.reduce((s,k)=>{ const w=cfg.find(c=>c.key===k); return s+(w?Math.round(bp*w.pct):0); }, 0);
    BM.pkg_price = tot;
    const el = document.getElementById('bmWindowTotalAmt');
    const box = document.getElementById('bmWindowTotal');
    if (el) el.textContent = '₱' + tot.toLocaleString('en-PH',{minimumFractionDigits:2});
    if (box) box.style.display = tot > 0 ? 'flex' : 'none';
    bmUpdateSummary2();
}

function bmBuildCustData() {
    const cat = BM.vehicle_cat || 'sedan';
    const cfg = WIN_CFG[cat] || WIN_CFG.sedan;
    const fullKey = cfg.find(w=>w.full)?.key;
    const bp = bmGetComboPrice(BM.package, BM.model);
    if (BM.windows.includes(fullKey) || !BM.windows.length) return {windows:[],mode:'full',total:bp};
    return {
        windows: BM.windows.map(k=>{ const w=cfg.find(c=>c.key===k); const p=w?Math.round(bp*w.pct):0; return{key:k,name:w?.label||k,price:p,pct:w?.pct||0}; }),
        mode:'partial', total:BM.pkg_price
    };
}

function bmInitSchedule() {
    bmUpdateSummary2();
    const dp = document.getElementById('bmDate');
    if (!dp || dp._bm_init) return;
    dp._bm_init = true;
    const today = new Date();
    const tom = new Date(today); tom.setDate(tom.getDate()+1);
    dp.min = bmLocalDate(today);
    dp.max = bmLocalDate(new Date(today.getFullYear(), today.getMonth()+3, today.getDate()));
    if (!BM.date || BM.date < dp.min) {
        let d = new Date(tom);
        if (d.getDay()===0) d.setDate(d.getDate()+1);
        if (d.getDay()===6) d.setDate(d.getDate()+2);
        dp.value = bmLocalDate(d); BM.date = dp.value;
    } else { dp.value = BM.date; }
    dp.addEventListener('change', function() {
        BM.date = this.value; BM.time = '';
        show('bm-date-error', !BM.date || bmIsWeekend(BM.date));
        bmLoadSlots(BM.date);
    });
    bmLoadSlots(BM.date);
}
function bmLocalDate(d) { return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0'); }
function bmIsWeekend(d) { const day=new Date(d+'T00:00:00').getDay(); return day===0||day===6; }

async function bmLoadSlots(date) {
    const con = document.getElementById('bmTimeSlots');
    if (!date) return;
    if (bmIsWeekend(date)) {
        con.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:1.5rem;color:#EF4444;font-size:0.82rem;"><i class="fas fa-calendar-xmark fa-2x" style="display:block;margin-bottom:0.5rem;"></i>Closed on weekends — pick a weekday</div>';
        return;
    }
    con.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:1.5rem;color:#64748b;"><span class="spinner-border spinner-border-sm text-warning me-2"></span>Checking availability...</div>';
    try {
        const res = await fetch(`../api/check_time.php?date=${date}&_=${Date.now()}`, {credentials:'same-origin'});
        const data = await res.json();
        const blocked = data.blocked_slots || [];
        const slots = ['06:00','08:20','10:40','13:00','15:20'];
        if (date === bmLocalDate(new Date())) {
            const now = new Date();
            const nowMins = now.getHours()*60+now.getMinutes();
            slots.forEach(t=>{ const [h,m]=t.split(':').map(Number); if(h*60+m<=nowMins&&!blocked.includes(t)) blocked.push(t); });
        }
        con.innerHTML = slots.map(t => {
            const unavail = blocked.includes(t);
            const sel = BM.time === t && !unavail;
            return `<div class="bm-slot ${unavail?'unavailable':sel?'selected':'available'}" data-time="${t}" ${unavail?'':`onclick="bmPickSlot('${t}')"`}>
                <span class="bm-slot-dot"></span>
                <div>
                    <div class="bm-slot-time">${bmFmt12(t)}</div>
                    <div class="bm-slot-pill">${unavail?'Not Available':sel?'Selected':'Available'}</div>
                </div>
            </div>`;
        }).join('');
        
        if (!BM.time) {
            const first = con.querySelector('.bm-slot.available');
            if (first) bmPickSlot(first.dataset.time);
        }
    } catch(e) {
        con.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:1rem;color:#EF4444;">Unable to load slots. Please refresh.</div>';
    }
}
window.bmPickSlot = function(time) {
    BM.time = time;
    document.querySelectorAll('#bmTimeSlots .bm-slot').forEach(s => {
        const t = s.dataset.time;
        s.className = 'bm-slot ' + (s.classList.contains('unavailable') ? 'unavailable' : t===time ? 'selected' : 'available');
        s.querySelector('.bm-slot-pill').textContent = s.classList.contains('unavailable') ? 'Not Available' : t===time ? 'Selected' : 'Available';
        if (!s.classList.contains('unavailable')) s.onclick = ()=>bmPickSlot(t);
    });
    show('bm-time-error', false);
};
function bmFmt12(t) {
    const [h,m]=t.split(':'); const hr=parseInt(h); const min=parseInt(m)||0;
    return `${hr%12||12}:${String(min).padStart(2,'0')} ${hr>=12?'PM':'AM'}`;
}

function bmUpdateSummary2() {
    const cat = BM.vehicle_cat || 'sedan';
    const cfg = WIN_CFG[cat] || WIN_CFG.sedan;
    const fullKey = cfg.find(w=>w.full)?.key;
    const set = (id,v) => { const e=document.getElementById(id); if(e) e.textContent=v; };
    const row = (id,show_it) => { const e=document.getElementById(id); if(e) e.style.display=show_it?'':'none'; };

    set('s2Car', BM.model || '—');
    set('s2Pkg', BM.pkg_name || '—');
    row('s2TintRow', !!BM.tint_type); set('s2Tint', BM_TINT_LBL[BM.tint_type]||BM.tint_type||'—');
    row('s2ShadeRow', !!BM.shade); set('s2Shade', BM.shade?BM.shade+'%':'—');
    if (BM.windows.length) {
        row('s2WinRow', true);
        const wlbl = BM.windows.includes(fullKey) ? (cfg.find(w=>w.full)?.label||'Full') : BM.windows.map(k=>cfg.find(c=>c.key===k)?.label||k).join(', ');
        set('s2Win', wlbl);
    } else { row('s2WinRow', false); }
    set('s2Total', bmFmt(BM.pkg_price||0));
}

function bmPopulateSummary() {
    const cat = BM.vehicle_cat || 'sedan';
    const cfg = WIN_CFG[cat] || WIN_CFG.sedan;
    const fullKey = cfg.find(w=>w.full)?.key;
    const set = (id,v) => { const e=document.getElementById(id); if(e) e.textContent=v; };

    set('s3Pkg', BM.pkg_name || '—');
    set('s3Car', BM.model || '—');
    const tLabel = BM_TINT_LBL[BM.tint_type]||BM.tint_type||'';
    const sLabel = BM.shade?BM.shade+'%':'';
    set('s3Tint', tLabel&&sLabel?`${tLabel} — ${sLabel}`:(tLabel||sLabel||'—'));
    const wlbl = BM.windows.includes(fullKey) ? (cfg.find(w=>w.full)?.label||'Full') : BM.windows.map(k=>cfg.find(c=>c.key===k)?.label||k).join(', ');
    set('s3Win', wlbl||'—');
    const dstr = BM.date ? new Date(BM.date+'T00:00:00').toLocaleDateString('en-PH',{weekday:'short',month:'short',day:'numeric',year:'numeric'}) : '—';
    set('s3Appt', dstr + (BM.time?' · '+bmFmt12(BM.time):''));
    set('s3Total', bmFmt(BM.pkg_price||0));

    
    ['bmName','bmPhone','bmAddress'].forEach(id => {
        const el = document.getElementById(id);
        if (el && !el.value) {
            if (id==='bmName') el.value = BM.name;
            if (id==='bmPhone') el.value = BM.phone;
            if (id==='bmAddress') el.value = BM.address;
        }
    });
    
    const nameEl=document.getElementById('bmName');
    const phEl=document.getElementById('bmPhone');
    const adEl=document.getElementById('bmAddress');
    if (nameEl) nameEl.oninput = ()=>{ BM.name=nameEl.value; show('bm-name-error',!BM.name.trim()); };
    if (phEl) phEl.oninput = ()=>{ BM.phone=phEl.value; show('bm-phone-error',!/^9[0-9]{9}$/.test(BM.phone)); };
    if (adEl) adEl.oninput = ()=>{ BM.address=adEl.value; show('bm-address-error',!BM.address.trim()); };
}

window.bmSelectPaymentMethod = function(method, cardEl) {
    BM.payment_method = method;
    document.querySelectorAll('.bm-pay-card').forEach(c => {
        c.style.border = '1.5px solid #E2E8F0';
        c.style.boxShadow = 'none';
        c.classList.remove('active');
    });
    if (cardEl) {
        cardEl.style.border = '2px solid #D4A017';
        cardEl.style.boxShadow = '0 4px 12px rgba(212,160,23,0.15)';
        cardEl.classList.add('active');
    }
    const input = document.getElementById('bmF_paymethod');
    if (input) input.value = method;
};

window.bmPayType = function(type) {
    BM.pay_type = type;
    document.querySelectorAll('.bm-pay-btn').forEach(b => b.classList.toggle('active', b.dataset.type===type));
    document.getElementById('bm-full-note').style.display = type==='full' ? 'block' : 'none';
    const dpEl = document.getElementById('bm-dp-note');
    if (dpEl) {
        dpEl.style.display = type==='downpayment' ? 'block' : 'none';
        const txtEl = document.getElementById('bm-dp-note-text');
        if (txtEl) {
            const tot = BM.pkg_price || 0;
            if (tot > 0) {
                const dp = tot * 0.10;
                const bal = tot - dp;
                txtEl.textContent = `Pay ${bmFmt(dp)} now (10% deposit). Remaining balance ${bmFmt(bal)} is due on appointment day.`;
            } else {
                txtEl.textContent = 'Pay 10% now as a deposit. Remaining balance is due on appointment day.';
            }
        }
    }
};

window.bmSubmit = function() {
    
    BM.name = (document.getElementById('bmName')?.value||'').trim();
    BM.phone = (document.getElementById('bmPhone')?.value||'').trim();
    BM.address = (document.getElementById('bmAddress')?.value||'').trim();
    BM.notes = (document.getElementById('bmNotes')?.value||'').trim();

    if (!bmValidateStep()) { bmShowErrors(); return; }

    document.getElementById('bmF_name').value = BM.name;
    document.getElementById('bmF_phone').value = BM.phone;
    document.getElementById('bmF_address').value = BM.address;
    document.getElementById('bmF_model').value = BM.model;
    document.getElementById('bmF_vtype').value = BM.vehicle_type;
    document.getElementById('bmF_pkg').value = BM.package;
    document.getElementById('bmF_date').value = BM.date;
    document.getElementById('bmF_time').value = BM.time;
    document.getElementById('bmF_notes').value = BM.notes;
    document.getElementById('bmF_paymethod').value = BM.payment_method || 'GCash';
    document.getElementById('bmF_paytype').value = BM.pay_type;
    document.getElementById('bmF_custdata').value = JSON.stringify(bmBuildCustData());
    document.getElementById('bmF_tint').value = BM.tint_type || 'carbon';
    document.getElementById('bmF_shade').value = BM.shade || 35;

    const btn = document.getElementById('bmSubmitBtn');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';
    btn.disabled = true;
    document.getElementById('bmBookingForm').submit();
};

function bmFmt(n) { return '₱' + n.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2}); }
})();
</script>
