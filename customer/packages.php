<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require_customer_auth('../login.php');

$nav_pending = 0;
try {
    $s = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id=? AND status IN ('Pending','On Hold','Confirmed')");
    $s->execute([$_SESSION['user_id']]);
    $nav_pending = (int)$s->fetchColumn();
} catch (PDOException $e) {}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['notification_action'])) {
    if ($_POST['notification_action'] == 'mark_read') {
        $notif_id = filter_var($_POST['notification_id'], FILTER_VALIDATE_INT);
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ? AND user_type='customer'");
        $stmt->execute([$notif_id, $_SESSION['user_id']]);
    } elseif ($_POST['notification_action'] == 'mark_all_read') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND user_type = 'customer'");
        $stmt->execute([$_SESSION['user_id']]);
    } elseif ($_POST['notification_action'] == 'delete') {
        $notif_id = filter_var($_POST['notification_id'], FILTER_VALIDATE_INT);
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ? AND user_type='customer'");
        $stmt->execute([$notif_id, $_SESSION['user_id']]);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND user_type = 'customer' AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_count = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND user_type = 'customer' ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

try {
    $packages = $pdo->query("
        SELECT * FROM tint_packages
        WHERE is_active = 1
        ORDER BY is_popular DESC, display_order ASC, name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $packages = [];
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tint Packages — RG CARTINTWORKS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,600;9..40,700;9..40,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/customer.css?v=<?= time() ?> time() ?>">
<style>

.pkg-hero-banner {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 16px;
    padding: 26px 30px;
    color: #ffffff;
    margin-bottom: 24px;
    box-shadow: 0 8px 24px rgba(15,23,42,0.12);
    position: relative;
    overflow: hidden;
}
.pkg-hero-banner::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(212,160,23,0.15) 0%, transparent 70%);
    pointer-events: none;
}
.pkg-card-custom {
    background: #ffffff;
    border: 1.5px solid rgba(212, 160, 23, 0.25);
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
.pkg-feature-list {
    margin: 0 18px 14px;
    padding: 10px 0 0 0;
    border-top: 1px dashed #e2e8f0;
    list-style: none;
    flex: 1;
}
.pkg-feature-item {
    padding: 3px 0;
    font-size: 12px;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 8px;
}
.pkg-feature-item i {
    color: #d4a017;
    font-size: 11px;
    flex-shrink: 0;
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
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(180,83,9,0.4);
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
                <a href="dashboard.php" class="cs-nav-link">
                    <span class="cs-nav-icon"><i class="fas fa-gauge-high"></i></span>Dashboard
                </a>
            </li>
            
            <li class="cs-nav-item">
                <a href="my_bookings.php" class="cs-nav-link">
                    <span class="cs-nav-icon"><i class="fas fa-calendar-alt"></i></span>My Bookings
                    <?php if($nav_pending > 0): ?><span class="cs-nav-badge"><?= $nav_pending ?></span><?php endif; ?>
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
            <div class="cs-user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'C', 0, 1)) ?></div>
            <div class="cs-user-info">
                <div class="cs-user-name"><?= htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['user_name'] ?? 'Customer') ?></div>
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
                    <div class="topbar-user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'C', 0, 1)) ?></div>
                    <span class="topbar-user-name d-none d-md-inline"><?= htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['user_name'] ?? 'Customer') ?></span>
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

        
        <div class="pkg-hero-banner d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <span class="badge mb-2" style="background:rgba(212,160,23,0.2);color:#F5C842;border:1px solid rgba(212,160,23,0.4);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;letter-spacing:.05em;">
                    <i class="fas fa-film me-1"></i>STORE CATALOG
                </span>
                <h3 style="font-weight:800;margin:0;font-size:1.6rem;letter-spacing:-0.01em;color:#ffffff;">Automotive Tint Packages</h3>
                <p style="color:#94a3b8;font-size:13px;margin:6px 0 0;max-width:640px;line-height:1.5;">
                    Explore our engineered window tinting packages. Maximum solar heat rejection, 99% UV block, and lifetime durability tailored for your vehicle.
                </p>
            </div>
            <div>
                <a href="my_bookings.php" class="btn btn-sm" style="background:rgba(255,255,255,0.08);color:#ffffff;border:1px solid rgba(255,255,255,0.2);padding:8px 16px;border-radius:10px;font-weight:700;font-size:12px;">
                    <i class="fas fa-calendar-check me-2" style="color:#D4A017;"></i>My Bookings
                </a>
            </div>
        </div>

        <?php if (empty($packages)): ?>
        <div class="panel-card text-center py-5" style="background:#ffffff;border-radius:16px;border:1px solid #e2e8f0;padding:40px;">
            <i class="fas fa-film" style="font-size:2.5rem;color:#D4A017;display:block;margin-bottom:12px;"></i>
            <div style="font-weight:700;color:#1e293b;font-size:16px;">No packages available</div>
            <div style="font-size:13px;color:#64748b;margin-top:4px;">Check back soon — new tint packages are being updated.</div>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($packages as $pkg):
                
                $raw_feat = trim((string)($pkg['features'] ?? ''));
                $features = [];
                if (!empty($raw_feat)) {
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
                $raw_img = trim((string)($pkg['image_url'] ?? ''));
                $pkg_img = '../assets/images/hero_car.png';
                if (!empty($raw_img) && $raw_img !== '/images/tint-packages/default-tint.jpg') {
                    $clean_url = ltrim(str_replace(['../', './'], '', $raw_img), '/');
                    if (file_exists(__DIR__ . '/../' . $clean_url)) {
                        $pkg_img = '../' . $clean_url;
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
            ?>
            <div class="col-md-6 col-xl-4">
                <div class="pkg-card-custom<?= $pkg['is_popular'] ? ' pkg-popular' : '' ?>">
                    <?php if ($pkg['is_popular']): ?>
                    <div class="pkg-badge-popular"><i class="fas fa-star me-1"></i>Popular</div>
                    <?php endif; ?>

                    
                    <div class="pkg-card-img-wrap">
                        <img src="<?= htmlspecialchars($pkg_img) ?>" alt="<?= htmlspecialchars($pkg['name']) ?>" onerror="this.onerror=null; this.src='../assets/images/hero_car.png';">
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
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar  = document.getElementById('custSidebar');
const overlay  = document.getElementById('sidebarOverlay');
const toggle   = document.getElementById('sidebarToggle');
function openCustSidebar()  { if(sidebar) sidebar.classList.add('open'); if(overlay) overlay.classList.add('show'); document.body.style.overflow='hidden'; }
function closeCustSidebar() { if(sidebar) sidebar.classList.remove('open'); if(overlay) overlay.classList.remove('show'); document.body.style.overflow=''; }
if(toggle)  toggle.addEventListener('click', openCustSidebar);
if(overlay) overlay.addEventListener('click', closeCustSidebar);
document.querySelectorAll('.cs-nav-link').forEach(l => l.addEventListener('click', () => { if(window.innerWidth < 992) closeCustSidebar(); }));

    
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

</body>
</html>