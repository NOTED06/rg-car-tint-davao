<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require_customer_auth('../login.php');

$user_id = $_SESSION['user_id'];
$raw_user_name = $_SESSION['user_name'] ?? 'Customer';

$display_name = trim((string)($raw_user_name));
if (strtolower($display_name) === 'customer' || strtolower($display_name) === 'john dave wick21') {
    $display_name = '';
}
$greeting_name = !empty($display_name) ? $display_name : ($_SESSION['display_name'] ?? 'Valued Customer');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['notification_action'])) {
    if ($_POST['notification_action'] == 'mark_read') {
        $notif_id = filter_var($_POST['notification_id'], FILTER_VALIDATE_INT);
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ? AND user_type='customer'");
        $stmt->execute([$notif_id, $user_id]);
    }
    elseif ($_POST['notification_action'] == 'mark_all_read') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND user_type = 'customer'");
        $stmt->execute([$user_id]);
    }
    elseif ($_POST['notification_action'] == 'delete') {
        $notif_id = filter_var($_POST['notification_id'], FILTER_VALIDATE_INT);
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ? AND user_type='customer'");
        $stmt->execute([$notif_id, $user_id]);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND user_type = 'customer' AND is_read = 0");
$stmt->execute([$user_id]);
$unread_count = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND user_type = 'customer' ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

try {
    $sql = "SELECT b.*, v.type_name, p.name as package_name
            FROM bookings b
            LEFT JOIN vehicle_types v ON b.vehicle_type_id = v.id
            LEFT JOIN tint_packages p ON b.package_id = p.id
            WHERE b.customer_id = ? OR b.user_id = ?
            ORDER BY b.appointment_date DESC, b.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id]);
    $my_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_bookings = count($my_bookings);
    $pending_bookings = 0;
    $completed_bookings = 0;
    $active_warranties = 0;
    $total_spent = 0;

    foreach ($my_bookings as $b) {
        $st = $b['status'] ?? 'Pending';
        if (in_array($st, ['Pending', 'Confirmed', 'In Progress', 'On Hold'])) {
            $pending_bookings++;
        }
        if ($st === 'Completed') {
            $completed_bookings++;
            $total_spent += (float)($b['total_price'] ?? 0);
            if (!empty($b['warranty_years'])) {
                $active_warranties++;
            }
        }
    }

} catch (Exception $e) {
    $my_bookings = [];
    $total_bookings = 0;
    $pending_bookings = 0;
    $completed_bookings = 0;
    $active_warranties = 0;
    $total_spent = 0;
}

try {
    $pkg_stmt = $pdo->query("SELECT * FROM tint_packages WHERE is_active = 1 ORDER BY is_popular DESC, display_order ASC, id ASC");
    $all_packages = $pkg_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $all_packages = [];
}

$pkg_min_prices = [];
try {
    $min_rows = $pdo->query("SELECT package_id, MIN(price) as min_price FROM package_model_prices WHERE price > 0 GROUP BY package_id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($min_rows as $mr) $pkg_min_prices[(int)$mr['package_id']] = (float)$mr['min_price'];
} catch (Exception $e) {}

$pkg_single_models = [];
$pkg_car_models = [];
try {
    $single_rows = $pdo->query("SELECT package_id, MIN(car_model) AS car_model, COUNT(DISTINCT car_model) AS car_count FROM package_model_prices GROUP BY package_id HAVING car_count = 1")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($single_rows as $sr) {
        $pkg_single_models[(int)$sr['package_id']] = (string)$sr['car_model'];
    }
    $cm_rows = $pdo->query("SELECT package_id, car_model FROM package_model_prices GROUP BY package_id, car_model ORDER BY car_model")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cm_rows as $cr) {
        $pkg_car_models[(int)$cr['package_id']][] = $cr['car_model'];
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard — RG CARTINTWORKS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,600;9..40,700;9..40,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/customer.css?v=<?= time() ?> time() ?>">
    <style>
        .dash-hero-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #334155 100%);
            border-radius: 16px;
            padding: 28px 32px;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(212, 160, 23, 0.3);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
        }
        .dash-hero-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(212,160,23,0.15) 0%, transparent 70%);
            pointer-events: none;
        }
        .stat-metric-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 14px rgba(0,0,0,0.03);
            transition: all 0.25s ease;
            height: 100%;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-metric-card:hover {
            transform: translateY(-3px);
            border-color: #d4a017;
            box-shadow: 0 8px 22px rgba(212,160,23,0.12);
        }
        .stat-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .recent-booking-item {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            transition: border-color 0.2s;
            margin-bottom: 12px;
        }
        .recent-booking-item:hover {
            border-color: #d4a017;
        }
        .status-badge-custom {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
    </style>
<style>

.pkg-card-custom {
    background: #ffffff !important;
    border: 1.5px solid rgba(212, 160, 23, 0.25) !important;
    border-radius: 16px !important;
    padding: 0 !important;
    position: relative !important;
    transition: all 0.3s ease !important;
    height: 100% !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05) !important;
}
.pkg-card-custom:hover {
    border-color: #d4a017 !important;
    box-shadow: 0 12px 30px rgba(212, 160, 23, 0.2) !important;
    transform: translateY(-5px) !important;
}
.pkg-card-custom.pkg-popular {
    border-color: #d4a017 !important;
    background: linear-gradient(180deg, #ffffff 75%, #FFFDF5 100%) !important;
}
.pkg-badge-popular {
    position: absolute !important;
    top: 12px !important;
    left: 12px !important;
    z-index: 3 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 4px !important;
    background: linear-gradient(135deg, #d4a017 0%, #b45309 100%) !important;
    color: #ffffff !important;
    font-size: 10px !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: .06em !important;
    padding: 4px 12px !important;
    border-radius: 30px !important;
    box-shadow: 0 4px 12px rgba(180,83,9,0.35) !important;
}
.pkg-card-img-wrap {
    position: relative !important;
    width: 100% !important;
    height: 185px !important;
    min-height: 185px !important;
    max-height: 185px !important;
    background: #0f172a !important;
    overflow: hidden !important;
    border-top-left-radius: 15px !important;
    border-top-right-radius: 15px !important;
}
.pkg-card-img-wrap img {
    width: 100% !important;
    height: 185px !important;
    min-height: 185px !important;
    max-height: 185px !important;
    object-fit: cover !important;
    object-position: center !important;
    display: block !important;
    transition: transform 0.4s ease !important;
}
.pkg-card-custom:hover .pkg-card-img-wrap img {
    transform: scale(1.06) !important;
}
.pkg-floating-price {
    position: absolute !important;
    top: 12px !important;
    right: 12px !important;
    z-index: 3 !important;
    background: rgba(15, 23, 42, 0.88) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
    color: #F5C842 !important;
    font-size: 15px !important;
    font-weight: 800 !important;
    padding: 5px 14px !important;
    border-radius: 30px !important;
    border: 1.5px solid rgba(245, 200, 66, 0.4) !important;
    box-shadow: 0 4px 14px rgba(0,0,0,0.25) !important;
}
.pkg-card-title {
    margin: 16px 18px 6px !important;
    font-size: 18px !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    line-height: 1.3 !important;
    letter-spacing: -0.01em !important;
}
.pkg-card-description {
    margin: 0 18px 12px !important;
    font-size: 12.5px !important;
    color: #64748b !important;
    line-height: 1.5 !important;
}
.pkg-card-specs {
    margin: 0 18px 14px !important;
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 6px !important;
}
.pkg-spec-pill {
    font-size: 11px !important;
    font-weight: 600 !important;
    background: #f8fafc !important;
    color: #334155 !important;
    padding: 4px 10px !important;
    border-radius: 20px !important;
    border: 1px solid #e2e8f0 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 5px !important;
}
.pkg-spec-pill i {
    color: #d4a017 !important;
}
.pkg-feature-list {
    margin: 0 18px 14px !important;
    padding: 10px 0 0 0 !important;
    border-top: 1px dashed #e2e8f0 !important;
    list-style: none !important;
    flex: 1 !important;
}
.pkg-feature-item {
    padding: 3px 0 !important;
    font-size: 12px !important;
    color: #475569 !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
}
.pkg-feature-item i {
    color: #d4a017 !important;
    font-size: 11px !important;
    flex-shrink: 0 !important;
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
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(245, 200, 66, 0.25) !important;
}
.btn-book-car-custom {
    background: linear-gradient(135deg, #F5C842 0%, #D4A017 100%) !important;
    color: #121214 !important;
    box-shadow: 0 4px 14px rgba(212, 160, 23, 0.35) !important;
}
.btn-book-car-custom:hover {
    background: linear-gradient(135deg, #FFD700 0%, #E6B800 100%) !important;
    color: #000000 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(245, 200, 66, 0.45) !important;
}
.btn-pkg-action-custom:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(180,83,9,0.4) !important;
    color: #ffffff !important;
}
</style>
<style>
.notif-row.active-notif {
    background: #fef3c7 !important;
    border-left: 4px solid #b45309 !important;
    box-shadow: inset 0 0 8px rgba(212, 160, 23, 0.25) !important;
}
.notif-row.active-notif strong {
    color: #78350f !important;
}
</style>
<script>
let activeCustNotifId = null;

window.handleCustNotifClick = async function(e, id, type, relId) {
    if (e && e.target && e.target.closest(".btn-close, .notifActionForm")) return;
    
    activeCustNotifId = id;
    const row = e ? e.target.closest(".notif-row") : null;
    if (row) {
        document.querySelectorAll(".notif-row").forEach(r => r.classList.remove("active-notif"));
        row.classList.add("active-notif");
    }
    
    try {
        await fetch("../api/notifications.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({ action: "mark_read", id: id })
        });
    } catch (err) {}
    
    let redirectUrl = "my_bookings.php";
    const typeStr = (type || "").toLowerCase();
    
    if (typeStr.includes("support") || typeStr.includes("ticket")) {
        redirectUrl = "support.php";
    } else if (typeStr.includes("feedback") || typeStr.includes("review")) {
        redirectUrl = "feedback.php";
    } else if (relId && relId > 0) {
        redirectUrl = "my_bookings.php?id=" + relId + "#booking-" + relId;
    }
    
    if (window.location.pathname.endsWith("my_bookings.php") && relId && relId > 0) {
        const card = document.getElementById("booking-" + relId);
        if (card) {
            card.scrollIntoView({ behavior: "smooth", block: "center" });
            card.classList.add("notif-jump-target");
            setTimeout(() => card.classList.remove("notif-jump-target"), 3500);
            return;
        }
    }
    
    setTimeout(() => {
        window.location.href = redirectUrl;
    }, 200);
};
</script>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="cust-sidebar" id="custSidebar">
    <a class="cs-brand d-flex align-items-center" href="<?= BASE_URL ?>/index.php" style="text-decoration:none;gap:10px;padding:24px 16px;">
        <div class="cs-brand-icon" style="background:none;box-shadow:none;width:auto;height:auto;display:flex;align-items:center;justify-content:center;">
            <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M16 2L20 8L26 10L24 16L26 22L20 24L16 30L12 24L6 22L8 16L6 10L12 8L16 2Z" fill="#D4A017"/>
                <path d="M16 8L19 12L24 14L22 18L24 22L19 24L16 28L13 24L8 22L10 18L8 14L13 12L16 8Z" fill="#0A0A0A"/>
                <path d="M16 12L18 15L22 16L20 19L22 22L18 23L16 26L14 23L10 22L12 19L10 16L14 15L16 12Z" fill="#F5C842"/>
            </svg>
        </div>
        <div class="cs-brand-text">
            <strong style="font-family:'Bebas Neue', sans-serif;font-size:1.35rem;letter-spacing:.05em;color:#FFFFFF;line-height:1;font-weight:700;">RG CARTINTWORKS</strong>
            <span style="font-size:9px;color:rgba(255,255,255,.5);font-weight:500;text-transform:uppercase;letter-spacing:.05em;margin-top:2px;display:block;">Customer Portal</span>
        </div>
    </a>

    <div class="cs-section">
        <div class="cs-section-label">Menu</div>
        <ul class="cs-nav">
            <li class="cs-nav-item">
                <a href="dashboard.php" class="cs-nav-link active">
                    <span class="cs-nav-icon"><i class="fas fa-gauge-high"></i></span>Dashboard
                </a>
            </li>
            
            <li class="cs-nav-item">
                <a href="my_bookings.php" class="cs-nav-link">
                    <span class="cs-nav-icon"><i class="fas fa-calendar-alt"></i></span>My Bookings
                    <?php if((int)$pending_bookings > 0): ?><span class="cs-nav-badge"><?= (int)$pending_bookings ?></span><?php endif; ?>
                </a>
            </li>
        </ul>
    </div>

    <div class="cs-section">
        <div class="cs-section-label">Account</div>
        <ul class="cs-nav">
            <li class="cs-nav-item">
                <a href="../logout.php" class="cs-nav-link">
                    <span class="cs-nav-icon"><i class="fas fa-right-from-bracket"></i></span>Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="cs-footer">
        <div class="cs-user">
            <div class="cs-user-avatar"><?= strtoupper(substr($greeting_name, 0, 1)) ?></div>
            <div class="cs-user-info">
                <div class="cs-user-name"><?= htmlspecialchars($greeting_name) ?></div>
                <div class="cs-user-role">Customer Account</div>
            </div>
        </div>
    </div>
</aside>

<div class="cust-main">

    
    <nav class="cust-topbar">
        <a class="cs-brand d-flex align-items-center" href="dashboard.php" style="text-decoration:none;gap:10px;">
            <div class="cs-brand-icon" style="background:none;box-shadow:none;width:auto;height:auto;display:flex;align-items:center;justify-content:center;">
                <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 2L20 8L26 10L24 16L26 22L20 24L16 30L12 24L6 22L8 16L6 10L12 8L16 2Z" fill="#D4A017"/>
                    <path d="M16 8L19 12L24 14L22 18L24 22L19 24L16 28L13 24L8 22L10 18L8 14L13 12L16 8Z" fill="#0A0A0A"/>
                    <path d="M16 12L18 15L22 16L20 19L22 22L18 23L16 26L14 23L10 22L12 19L10 16L14 15L16 12Z" fill="#F5C842"/>
                </svg>
            </div>
            <div class="cs-brand-text">
                <div class="d-flex align-items-center gap-2">
                    <strong style="font-family:'Bebas Neue', sans-serif;font-size:1.35rem;letter-spacing:.05em;color:#0A0A0A;line-height:1;font-weight:700;margin:0;">RG CARTINTWORKS</strong>
                    <span style="background:#D4A017;color:#0A0A0A;font-size:.6rem;font-weight:800;padding:.15rem .35rem;border-radius:4px;line-height:1;letter-spacing:.05em;">DAVAO</span>
                </div>
                <span style="font-size:7px;color:#a1a1aa;letter-spacing:1.5px;font-weight:800;display:block;margin-top:4px;text-transform:uppercase;line-height:1;">Bespoke Automotive Studio</span>
            </div>
        </a>

        
        <ul class="topbar-nav d-none d-lg-flex">
                    <li><a href="dashboard.php" class="topbar-nav-link"><i class="fas fa-gauge-high"></i>Dashboard</a></li>
                    <li><a href="my_bookings.php" class="topbar-nav-link"><i class="fas fa-calendar-alt"></i>My Bookings</a></li>
                </ul>

        
        <div class="topbar-actions-dock">
            
            <div class="topbar-search" id="topbarSearch">
                <div class="topbar-search-inner">
                    <i class="fas fa-search topbar-search-icon"></i>
                    <input type="text" id="globalSearchInput" class="topbar-search-input" placeholder="Search packages..." autocomplete="off">
                    <button type="button" class="topbar-search-clear" id="searchClear" style="display:none;"><i class="fas fa-xmark"></i></button>
                </div>
                <div class="topbar-search-dropdown" id="searchDropdown"></div>
            </div>

            
            <div class="dropdown">
                <button class="topbar-icon-btn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <?php if((int)$unread_count > 0): ?>
                    <span class="topbar-notif-badge" id="notifBadgeWrap"><?= (int)$unread_count > 9 ? '9+' : (int)$unread_count ?></span>
                    <?php else: ?>
                    <span class="topbar-notif-badge" id="notifBadgeWrap" style="display:none;"></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end notif-panel p-0">
                    <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                        <span style="font-size:13px;font-weight:700;"><i class="fas fa-bell me-2" style="color:#D4A017;"></i>Notifications</span>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="notification_action" value="mark_all_read">
                            <button type="submit" style="background:none;border:none;font-size:12px;color:#D4A017;font-weight:600;cursor:pointer;padding:0;">Mark all read</button>
                        </form>
                    </div>
                    <div id="notifList">
                        <?php if(empty($notifications)): ?>
                        <div class="p-4 text-center text-muted small">No notifications yet</div>
                        <?php else: foreach($notifications as $notif):
                            $ni='bg-primary'; $nicon='fa-info-circle';
                            if($notif['type']=='success'){$ni='bg-success';$nicon='fa-check-circle';}
                            if($notif['type']=='warning'){$ni='bg-warning';$nicon='fa-exclamation-triangle';}
                            if($notif['type']=='danger'){$ni='bg-danger';$nicon='fa-times-circle';}
                        ?>
                        <div class="notif-row <?= ((int)$notif['is_read']===0)?'unread':'' ?>" onclick="handleCustNotifClick(event, <?= (int)$notif['id'] ?>, '<?= htmlspecialchars($notif['related_type'] ?? $notif['type'] ?? '') ?>', <?= (int)($notif['related_id'] ?? 0) ?>)" style="cursor:pointer;">
                            <div class="notif-icon <?= $ni ?>"><i class="fas <?= $nicon ?> fa-xs"></i></div>
                            <div class="flex-grow-1" style="min-width:0;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <strong style="font-size:12px;line-height:1.3;"><?= htmlspecialchars($notif['title']) ?></strong>
                                    <form method="POST" class="ms-2 notifActionForm" style="flex-shrink:0;">
                                        <input type="hidden" name="notification_id" value="<?= (int)$notif['id'] ?>">
                                        <input type="hidden" name="notification_action" value="delete">
                                        <button type="submit" class="btn-close" style="width:.45em;height:.45em;"></button>
                                    </form>
                                </div>
                                <p style="font-size:11px;color:#64748b;margin:2px 0;"><?= htmlspecialchars($notif['message']) ?></p>
                                <div class="notif-time"><?= date('M d, h:i A', strtotime($notif['created_at'])) ?></div>
                            </div>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                    <div class="p-2 border-top text-center">
                        <a href="my_bookings.php" style="font-size:12px;color:#D4A017;text-decoration:none;font-weight:600;">View all notifications</a>
                    </div>
                </div>
            </div>

            
            <div class="dropdown">
                <a class="topbar-user" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="topbar-user-avatar"><?= strtoupper(substr($greeting_name, 0, 1)) ?></div>
                    <span class="topbar-user-name d-none d-md-inline"><?= htmlspecialchars($greeting_name) ?></span>
                    <i class="fas fa-chevron-down" style="font-size:10px;color:#94a3b8;"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width:170px;border-radius:10px;border:1px solid #E6E2DA;box-shadow:0 8px 24px rgba(0,0,0,.1);">
                    <li><a class="dropdown-item" href="dashboard.php" style="font-size:13px;"><i class="fas fa-gauge-high me-2" style="color:#D4A017;"></i>Dashboard</a></li>
                    <li><a class="dropdown-item" href="profile.php" style="font-size:13px;"><i class="fas fa-user-circle me-2" style="color:#D4A017;"></i>My Profile</a></li>
                    <li><a class="dropdown-item" href="my_bookings.php" style="font-size:13px;"><i class="fas fa-calendar-alt me-2" style="color:#D4A017;"></i>My Bookings</a></li>
                    
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../logout.php" style="font-size:13px;"><i class="fas fa-right-from-bracket me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    
    <div class="cust-content">

        
        <style>
        .store-video-showcase-container {
            position: relative;
            width: 100%;
            height: 450px;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.3);
            border: 1.5px solid rgba(212, 160, 23, 0.4);
            margin-bottom: 30px;
        }
        .video-showcase-content {
            position: relative;
            z-index: 3;
            padding: 50px 54px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #ffffff;
        }
        .store-showcase-title {
            font-size: 2.7rem !important;
            font-weight: 900 !important;
            line-height: 1.15 !important;
            margin: 8px 0 10px !important;
        }
        .store-hero-video {
            position: absolute;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            transform: translate(-50%, -50%);
            object-fit: cover;
            z-index: 1;
            filter: brightness(0.65) contrast(1.1);
            transition: transform 0.1s ease-out;
        }
        .video-overlay-gradient {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.88) 0%, rgba(15, 23, 42, 0.45) 50%, rgba(15, 23, 42, 0.88) 100%);
            z-index: 2;
        }
        .video-showcase-content {
            position: relative;
            z-index: 3;
            padding: 50px 54px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #ffffff;
        }
        .store-badge-pill {
            background: rgba(212, 160, 23, 0.25);
            color: #F5C842;
            border: 1px solid rgba(245, 200, 66, 0.4);
            font-size: 11px;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 30px;
            letter-spacing: 0.05em;
        }
        .store-showcase-title {
            font-size: 2.7rem;
            font-weight: 900;
            letter-spacing: -0.02em;
            margin: 6px 0 8px;
            text-shadow: 0 4px 12px rgba(0,0,0,0.5);
            color: #ffffff;
        }
        .store-showcase-title .gold-text {
            color: #F5C842;
        }
        .store-showcase-subtitle {
            font-size: 14px;
            color: #cbd5e1;
            max-width: 640px;
            line-height: 1.6;
            margin: 0;
        }
        .btn-video-hero-gold {
            padding: 12px 22px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 13px;
            text-transform: uppercase;
            background: linear-gradient(135deg, #d4a017 0%, #b45309 100%);
            color: #ffffff !important;
            text-decoration: none !important;
            box-shadow: 0 4px 16px rgba(180,83,9,0.35);
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-video-hero-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 22px rgba(180,83,9,0.5);
        }
        .btn-video-hero-outline {
            padding: 12px 22px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-decoration: none !important;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-video-hero-outline:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }
        </style>

        <div class="store-video-showcase-container">
            <video class="store-hero-video" id="storeHeroVideo" autoplay loop muted playsinline poster="../assets/images/hero_car.png">
                <source src="../6159288-hd_1920_1080_30fps.mp4" type="video/mp4">
            </video>
            <div class="video-overlay-gradient"></div>
            <div class="video-showcase-content">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="store-badge-pill">
                        <i class="fas fa-circle-play me-1"></i>BESPOKE STORE SHOWCASE • DAVAO CITY
                    </span>
                    <span class="text-white-50 small ms-2"><i class="fas fa-calendar-day me-1"></i><?= date('l, F j, Y') ?></span>
                </div>
                <h1 class="store-showcase-title">
                    Welcome Back, <span class="gold-text"><?= htmlspecialchars($greeting_name) ?></span>
                </h1>
                <p class="store-showcase-subtitle">
                    RG CARTINTWORKS Davao — Philippines' premier bespoke automotive window tinting studio. Experience engineered solar control, 99% UV radiation block, and optical privacy tailored for your luxury vehicle.
                </p>
                <div class="d-flex flex-wrap gap-3 mt-3">
                    <a href="packages.php" class="btn-video-hero-gold">
                        <i class="fas fa-film"></i>Explore Tint Catalog
                    </a>
                    <a href="my_bookings.php" class="btn-video-hero-outline">
                        <i class="fas fa-calendar-check"></i>My Appointments
                    </a>
                </div>
            </div>
        </div>

        <script>
        
        window.addEventListener('scroll', function() {
            const video = document.getElementById('storeHeroVideo');
            if (video) {
                let scrollPos = window.scrollY;
                if (scrollPos < 600) {
                    video.style.transform = `translate(-50%, calc(-50% + ${scrollPos * 0.22}px))`;
                }
            }
        });
        </script>

        
        <div class="panel-card">
            <div class="panel-head" style="padding:16px 20px;display:flex;align-items:center;justify-content:between;border-bottom:1px solid #e2e8f0;">
                <span class="panel-head-title" style="font-weight:800;font-size:15px;color:#0f172a;"><i class="fas fa-film me-2" style="color:#D4A017;"></i>Available Tint Packages</span>
                <a href="packages.php" class="btn-outline-gold" style="font-size:12px;padding:5px 14px;border-radius:6px;text-decoration:none;">See All Packages</a>
            </div>
            <div style="padding:20px;">
                <?php if(!empty($all_packages)): ?>
                <div class="row g-4">
                    <?php foreach($all_packages as $pkg):
                        
                        $raw_feat = trim((string)($pkg['features'] ?? ''));
                        $features = [];
                        if (!empty($raw_feat) && $raw_feat !== '[]' && $raw_feat !== 'null') {
                            $decoded = json_decode($raw_feat, true);
                            if (is_array($decoded)) {
                                foreach ($decoded as $item) {
                                    $item_str = is_array($item) ? implode(', ', $item) : (string)$item;
                                    $item_str = trim($item_str);
                                    if (!empty($item_str) && $item_str !== '[]' && $item_str !== 'null') {
                                        $features[] = $item_str;
                                    }
                                }
                            } else {
                                $lines = explode("\n", $raw_feat);
                                foreach ($lines as $line) {
                                    $line_str = trim($line);
                                    if (!empty($line_str) && $line_str !== '[]' && $line_str !== 'null') {
                                        $features[] = $line_str;
                                    }
                                }
                            }
                        }
                        

                        $pid = (int)$pkg['id'];
                        $start_price = $pkg_min_prices[$pid] ?? min(
                            array_filter([
                                (float)($pkg['sedan_price'] ?? 0),
                                (float)($pkg['suv_price'] ?? 0),
                                (float)($pkg['van_price'] ?? 0)
                            ]) ?: [0]
                        );
                        $pkg_img = !empty($pkg['image_url']) && $pkg['image_url'] !== '/images/tint-packages/default-tint.jpg' ? $pkg['image_url'] : '';

                        
                        $desc = trim((string)($pkg['description'] ?? ''));

                        
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
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="pkg-card-custom<?= $pkg['is_popular'] ? ' pkg-popular' : '' ?>">
                            <?php if ($pkg['is_popular']): ?>
                            <div class="pkg-badge-popular"><i class="fas fa-star me-1"></i>Popular</div>
                            <?php endif; ?>

                            
                            <div class="pkg-card-img-wrap">
                                <?php if($pkg_img): ?>
                                <img src="<?= htmlspecialchars($pkg_img) ?>" alt="<?= htmlspecialchars($pkg['name']) ?>">
                                <?php else: ?>
                                <img src="../assets/images/hero_car.png" alt="<?= htmlspecialchars($pkg['name']) ?>">
                                <?php endif; ?>
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
                            </div>

                            

                            
                            <?php if (isset($pkg_single_models[(int)$pkg['id']])): ?>
                            
                            <button type="button" class="btn-pkg-action-custom btn-book-car-custom open-book-modal-btn" data-pkg-id="<?= (int)$pkg['id'] ?>" data-car-model="<?= htmlspecialchars($pkg_single_models[(int)$pkg['id']] ?? '') ?>" data-mode="custom">
                                <i class="fas fa-car-side me-1.5"></i>Book This Car
                            </button>
                            <?php else: ?>
                            
                            <button type="button" class="btn-pkg-action-custom btn-book-package-custom open-book-modal-btn" data-pkg-id="<?= (int)$pkg['id'] ?>" data-car-model="" data-mode="package">
                                <i class="fas fa-layer-group me-1.5"></i>Book Package
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <div style="font-size:2.5rem;color:#E6E2DA;margin-bottom:10px;"><i class="fas fa-film"></i></div>
                    <p style="color:#94a3b8;">No tint packages available at the moment.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

const _searchData = <?= json_encode(array_map(function($pkg) use ($pkg_min_prices, $pkg_single_models, $pkg_car_models) {
    $pid = (int)$pkg['id'];
    return [
        'id' => $pid,
        'name' => $pkg['name'],
        'desc' => $pkg['description'] ?? '',
        'price' => $pkg_min_prices[$pid] ?? min((float)($pkg['sedan_price']??0),(float)($pkg['suv_price']??0),(float)($pkg['van_price']??0)),
        'cars' => $pkg_car_models[$pid] ?? [],
        'single' => $pkg_single_models[$pid] ?? null,
        'tint' => $pkg['tint_type'] ?? 'carbon',
    ];
}, $all_packages)) ?>;

(function(){
    const input = document.getElementById('globalSearchInput');
    const dropdown = document.getElementById('searchDropdown');
    const clearBtn = document.getElementById('searchClear');
    if (!input || !dropdown) return;

    function escHtml(s){ return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c])); }
    function fmtPrice(v){ return '\u20b1' + Number(v).toLocaleString('en-PH'); }

    function buildResults(q) {
        q = q.toLowerCase().trim();
        if (!q) { dropdown.style.display = 'none'; clearBtn.style.display = 'none'; return; }
        clearBtn.style.display = 'flex';
        const results = [];

        _searchData.forEach(pkg => {
            const nameMatch = pkg.name.toLowerCase().includes(q);
            const descMatch = (pkg.desc||'').toLowerCase().includes(q);
            const tintMatch = (pkg.tint||'').toLowerCase().includes(q);
            const matchedCars = (pkg.cars||[]).filter(c => c.toLowerCase().includes(q));

            if (nameMatch || descMatch || tintMatch) {
                results.push({ type: 'package', pkg, car: null, score: nameMatch ? 2 : 1 });
            }
            matchedCars.forEach(car => {
                results.push({ type: 'car', pkg, car, score: 1 });
            });
        });

        results.sort((a,b) => b.score - a.score);
        const limited = results.slice(0, 8);

        if (limited.length === 0) {
            dropdown.innerHTML = '<div class="search-empty"><i class="fas fa-search" style="font-size:1.4rem;color:#555;margin-bottom:8px;"></i><div>No results for "<strong>' + escHtml(q) + '</strong>"</div></div>';
            dropdown.style.display = 'block';
            return;
        }

        dropdown.innerHTML = limited.map((r, i) => {
            const car = r.car || r.pkg.single || null;
            const carArg = car ? ', ' + JSON.stringify(car).replace(/"/g, '&quot;') : '';
            const onclick = `openBookingModal(${r.pkg.id}${carArg});document.getElementById('searchDropdown').style.display='none';`;
            if (r.type === 'car') {
                return `<div class="search-result-item${i===0?' active':''}" data-idx="${i}" onclick="${onclick}" style="cursor:pointer;">
                    <div class="search-result-icon car"><i class="fas fa-car-side"></i></div>
                    <div class="search-result-info">
                        <div class="search-result-title">${escHtml(r.car)}</div>
                        <div class="search-result-sub"><i class="fas fa-film me-1"></i>${escHtml(r.pkg.name)} &mdash; ${fmtPrice(r.pkg.price)}</div>
                    </div>
                    <i class="fas fa-arrow-right search-result-arrow"></i>
                </div>`;
            }
            return `<div class="search-result-item${i===0?' active':''}" data-idx="${i}" onclick="${onclick}" style="cursor:pointer;">
                <div class="search-result-icon pkg"><i class="fas fa-film"></i></div>
                <div class="search-result-info">
                    <div class="search-result-title">${escHtml(r.pkg.name)}</div>
                    <div class="search-result-sub">${r.pkg.cars.length ? r.pkg.cars.length + ' car' + (r.pkg.cars.length>1?'s':'') + ' &mdash; ' : ''}Starts at ${fmtPrice(r.pkg.price)}</div>
                </div>
                <i class="fas fa-arrow-right search-result-arrow"></i>
            </div>`;
        }).join('');
        dropdown.style.display = 'block';
    }

    input.addEventListener('input', () => buildResults(input.value));
    input.addEventListener('focus', () => { if(input.value.trim()) buildResults(input.value); });
    clearBtn.addEventListener('click', () => { input.value = ''; dropdown.style.display = 'none'; clearBtn.style.display = 'none'; input.focus(); });

    
    input.addEventListener('keydown', e => {
        const items = dropdown.querySelectorAll('.search-result-item');
        if (!items.length) return;
        let cur = [...items].findIndex(el => el.classList.contains('active'));
        if (e.key === 'ArrowDown') { e.preventDefault(); items[cur]?.classList.remove('active'); cur = (cur+1) % items.length; items[cur].classList.add('active'); items[cur].scrollIntoView({block:'nearest'}); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); items[cur]?.classList.remove('active'); cur = (cur-1+items.length) % items.length; items[cur].classList.add('active'); items[cur].scrollIntoView({block:'nearest'}); }
        else if (e.key === 'Enter') { e.preventDefault(); const active = dropdown.querySelector('.search-result-item.active'); if(active) window.location.href = active.href; }
        else if (e.key === 'Escape') { dropdown.style.display = 'none'; input.blur(); }
    });

    
    document.addEventListener('click', e => { if (!e.target.closest('#topbarSearch')) dropdown.style.display = 'none'; });

    
    document.addEventListener('keydown', e => { if ((e.ctrlKey || e.metaKey) && e.key === 'k') { e.preventDefault(); input.focus(); input.select(); } });
})();

const sidebar = document.getElementById('custSidebar');
const overlay = document.getElementById('sidebarOverlay');
const toggle  = document.getElementById('sidebarToggle');
function openCustSidebar()  { if(sidebar) sidebar.classList.add('open'); if(overlay) overlay.classList.add('show'); document.body.style.overflow='hidden'; }
function closeCustSidebar() { if(sidebar) sidebar.classList.remove('open'); if(overlay) overlay.classList.remove('show'); document.body.style.overflow=''; }
if(toggle)  toggle.addEventListener('click', openCustSidebar);
if(overlay) overlay.addEventListener('click', closeCustSidebar);
document.querySelectorAll('.cs-nav-link').forEach(l => l.addEventListener('click', () => { if(window.innerWidth < 992) closeCustSidebar(); }));

function escapeHtml(s){ return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c])); }
function notifIcon(t){ if(t==='success')return{bg:'bg-success',ic:'fa-check-circle'}; if(t==='warning')return{bg:'bg-warning',ic:'fa-exclamation-triangle'}; if(t==='danger')return{bg:'bg-danger',ic:'fa-times-circle'}; return{bg:'bg-primary',ic:'fa-info-circle'}; }

async function submitNotifAjax(form){
    try{
        const fd=new FormData(form);
        const r=await fetch('dashboard.php',{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'},cache:'no-store'});
        if(!r.ok)return;
        await pollNotifications();
    }catch(e){}
}
document.addEventListener('submit',e=>{
    if(e.target.classList.contains('notifActionForm')){e.preventDefault();e.stopPropagation();submitNotifAjax(e.target);}
},true);

async function pollNotifications(){
    try{
        const r=await fetch('../api/notifications.php',{cache:'no-store'});
        if(!r.ok)return;
        const d=await r.json();
        const bw=document.getElementById('notifBadgeWrap');
        if(bw){if(d.unread>0){bw.style.display='flex';bw.textContent=d.unread>9?'9+':d.unread;}else{bw.style.display='none';}}
        const nl=document.getElementById('notifList');
        if(!nl)return;
        if(!d.notifications||!d.notifications.length){nl.innerHTML='<div class="p-4 text-center text-muted small">No notifications yet</div>';return;}
        nl.innerHTML=d.notifications.map(n=>{
            const m=notifIcon(n.type);
            const isAct = parseInt(n.id) === parseInt(activeCustNotifId) ? 'active-notif' : ''; return `<div class="notif-row ${parseInt(n.is_read)===0?'unread':''} ${isAct}" onclick="handleCustNotifClick(event, ${n.id}, '${n.related_type||n.type||''}', ${n.related_id||0})" style="cursor:pointer;">
                <div class="notif-icon ${m.bg}"><i class="fas ${m.ic} fa-xs"></i></div>
                <div class="flex-grow-1" style="min-width:0;">
                    <div class="d-flex justify-content-between align-items-start">
                        <strong style="font-size:12px;">${escapeHtml(n.title)}</strong>
                        <form method="POST" class="ms-2 notifActionForm" style="flex-shrink:0;">
                            <input type="hidden" name="notification_id" value="${n.id}">
                            <input type="hidden" name="notification_action" value="delete">
                            <button type="submit" class="btn-close" style="width:.45em;height:.45em;"></button>
                        </form>
                    </div>
                    <p style="font-size:11px;color:#64748b;margin:2px 0;">${escapeHtml(n.message)}</p>
                    <div class="notif-time">${new Date(String(n.created_at).replace(' ','T')).toLocaleString()}</div>
                </div>
            </div>`;
        }).join('');
    }catch(e){}
}
pollNotifications();
setInterval(pollNotifications,5000);

    // Global click listener for Book This Car / Book Package buttons
    document.addEventListener("click", function(e) {
        const btn = e.target.closest(".open-book-modal-btn");
        if (btn) {
            e.preventDefault();
            const pkgId = btn.dataset.pkgId;
            const model = btn.dataset.carModel || null;
            const mode  = btn.dataset.mode || "package";
            if (typeof window.openBookingModal === "function") {
                window.openBookingModal(pkgId, model, mode);
            } else {
                window.location.href = "dashboard.php?open_booking=1&package_id=" + pkgId + (model ? "&car_model=" + encodeURIComponent(model) : "");
            }
        }
    });

</script>

<?php include __DIR__ . '/../includes/chatbot.php'; ?>
<?php include __DIR__ . '/../includes/customer_modals.php'; ?>
<?php include __DIR__ . '/booking_modal.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get("open_booking") === "1") {
        const pkgId = params.get("package_id") || "";
        const model = params.get("car_model") || "";
        setTimeout(() => {
            if (typeof window.openBookingModal === "function") {
                window.openBookingModal(pkgId, model);
            }
        }, 300);
    }
});
</script>

</body>
</html>