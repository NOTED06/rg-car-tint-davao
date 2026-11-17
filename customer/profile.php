<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require_customer_auth('../login.php');

$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Customer';
$message = '';
$msg_type = '';

$notif_display_name = $user_name ?? 'Customer'; 
$notif_display_name = trim((string)$notif_display_name);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['notification_action'])) {
    if ($_POST['notification_action'] == 'mark_read') {
        $notif_id = filter_var($_POST['notification_id'], FILTER_VALIDATE_INT);
        if ($notif_id) {
            $stmt = $pdo->prepare(" UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ? AND user_type='customer' ");
            $stmt->execute([$notif_id, $user_id]);
        }
    } elseif ($_POST['notification_action'] == 'mark_all_read') {
        $stmt = $pdo->prepare(" UPDATE notifications SET is_read = 1 WHERE user_id = ? AND user_type = 'customer' AND (type IS NULL OR type <> 'deleted') ");
        $stmt->execute([$user_id]);
    } elseif ($_POST['notification_action'] == 'delete') {
        
        $notif_id = filter_var($_POST['notification_id'], FILTER_VALIDATE_INT);
        if ($notif_id) {
            $stmt = $pdo->prepare(" UPDATE notifications SET type = 'deleted', is_read = 1 WHERE id = ? AND user_id = ? AND user_type='customer' ");
            $stmt->execute([$notif_id, $user_id]);
            
            
            $admin_notif = "Customer {$notif_display_name} (ID: {$user_id}) deleted a notification.";
            $stmt = $pdo->prepare(" INSERT INTO notifications (user_type, title, message, type, related_id, related_type, is_read, created_at) VALUES ('admin', 'Notification Deleted', ?, 'info', ?, 'user', 0, NOW()) ");
            $stmt->execute([$admin_notif, $user_id]);
        }
    }

    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }
    header("Location: profile.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    $password_sql = "";
    $params = [$full_name, $phone, $email, $address];
    
    if (!empty($_POST['new_password'])) {
        $password_sql = ", password = ?";
        $params[] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    }
    
    $params[] = $user_id;
    
    try {
        $sql = "UPDATE users SET full_name = ?, phone = ?, email = ?, address = ? $password_sql WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $_SESSION['user_name'] = $full_name;
        $user_name = $full_name;
        
        
        $notif_msg = "Customer {$full_name} (ID: {$user_id}) updated their profile details.";
        $stmt = $pdo->prepare(" INSERT INTO notifications (user_type, title, message, type, related_id, related_type, is_read, created_at) VALUES ('admin', 'Profile Updated', ?, 'info', ?, 'user', 0, NOW()) ");
        $stmt->execute([$notif_msg, $user_id]);
        
        $message = "Profile updated successfully!";
        $msg_type = "success";
    } catch (PDOException $e) {
        $message = "Error updating profile: " . $e->getMessage();
        $msg_type = "danger";
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) die("User not found.");
    $is_profile_incomplete = empty(trim((string)($user['phone'] ?? ''))) || empty(trim((string)($user['address'] ?? '')));
} catch (Exception $e) {
    die("User not found.");
}

$notif_display_name = trim((string)($user['full_name'] ?? $user_name ?? 'Customer'));
if (strtolower($notif_display_name) === 'john dave wick21') {
    $notif_display_name = 'Customer';
}

try {
    $table = null;
    $checkTbl = $pdo->prepare("SHOW TABLES LIKE 'appointments'");
    $checkTbl->execute();
    if ($checkTbl->fetchColumn()) {
        $table = 'appointments';
        $relatedType = 'appointment';
    } else {
        $checkTbl = $pdo->prepare("SHOW TABLES LIKE 'bookings'");
        $checkTbl->execute();
        if ($checkTbl->fetchColumn()) {
            $table = 'bookings';
            $relatedType = 'booking';
        }
    }
    
    if ($table) {
        
        $stmt = $pdo->prepare(" SELECT id, status, rejection_reason, appointment_date FROM {$table} WHERE user_id = ? ORDER BY id DESC ");
        $stmt->execute([$user_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rows as $row) {
            $item_id = (int)$row['id'];
            $status = strtolower(trim((string)$row['status']));
            $rejection_reason = $row['rejection_reason'] ?? '';
            $appointment_date = $row['appointment_date'] ?? '';
            
            
            $date_display = '';
            if ($appointment_date) {
                $date_display = ' on ' . date('M d, Y', strtotime($appointment_date));
            }
            
            if ($status === 'approved') {
                $title = "✅ Appointment Approved";
                $msg = "Good news! Your appointment{$date_display} has been approved by our admin. You can proceed to your schedule and prepare your vehicle for installation.";
                $type = "success";
            } elseif ($status === 'declined') {
                $title = "❌ Appointment Declined";
                
                if (!empty($rejection_reason)) {
                    $msg = "We're sorry — your appointment{$date_display} has been declined by our admin. Reason: \"{$rejection_reason}\". Please review your details and try booking again or contact support for assistance.";
                } else {
                    $msg = "We're sorry — your appointment{$date_display} has been declined by our admin. This could be because the area is too far for service or due to scheduling conflicts. Please try booking again or contact support.";
                }
                $type = "danger";
            }
            
            elseif (in_array($status, ['admin_cancelled','admin_canceled','cancelled_by_admin','canceled_by_admin'], true)) {
                $title = "⚠️ Appointment Cancelled by Admin";
                if (!empty($rejection_reason)) {
                    $msg = "Your appointment{$date_display} has been cancelled by our admin. Reason: \"{$rejection_reason}\". This may be because the area is too far for service or due to scheduling constraints. Please try booking again or contact support.";
                } else {
                    $msg = "Your appointment{$date_display} has been cancelled by our admin. This may be because the area is too far for service or due to scheduling constraints. Please try booking again or contact support.";
                }
                $type = "warning";
            }
            
            elseif ($status === 'cancelled' || $status === 'canceled') {
                $title = "📅 Appointment Cancelled";
                $msg = "You have cancelled your appointment{$date_display}. If you'd like to reschedule, please create a new booking.";
                $type = "warning";
            } else {
                continue;
            }
            
            
            $check = $pdo->prepare(" SELECT COUNT(*) FROM notifications WHERE user_id = ? AND user_type = 'customer' AND related_type = ? AND related_id = ? AND title LIKE ? ");
            $search_title = '%' . substr($title, 2) . '%'; 
            $check->execute([$user_id, $relatedType, $item_id, $search_title]);
            $exists = (int)$check->fetchColumn();
            
            if ($exists === 0) {
                $ins = $pdo->prepare(" INSERT INTO notifications (user_id, user_type, title, message, type, related_id, related_type, is_read, created_at) VALUES (?, 'customer', ?, ?, ?, ?, ?, 0, NOW()) ");
                $ins->execute([$user_id, $title, $msg, $type, $item_id, $relatedType]);
                
                
                $admin_notif = "Customer {$notif_display_name} was notified about their {$status} appointment #{$item_id}.";
                $admin_type = ($status == 'declined') ? 'warning' : 'info';
                $stmt = $pdo->prepare(" INSERT INTO notifications (user_type, title, message, type, related_id, related_type, is_read, created_at) VALUES ('admin', 'Customer Notification Sent', ?, ?, ?, 'customer', 0, NOW()) ");
                $stmt->execute([$admin_notif, $admin_type, $user_id]);
            }
        }
    }
} catch (Exception $e) {
    error_log("Notification creation error: " . $e->getMessage());
}

$stmt = $pdo->prepare(" SELECT COUNT(*) FROM notifications WHERE user_id = ? AND user_type = 'customer' AND is_read = 0 AND (type IS NULL OR type <> 'deleted') ");
$stmt->execute([$user_id]);
$unread_count = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(" SELECT * FROM notifications WHERE user_id = ? AND user_type = 'customer' AND (type IS NULL OR type <> 'deleted') ORDER BY created_at DESC LIMIT 20 ");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user_bookings = [];
try {
    if ($table) {
        $stmt = $pdo->prepare(" SELECT b.*, v.type_name FROM {$table} b LEFT JOIN vehicle_types v ON b.vehicle_type_id = v.id WHERE b.user_id = ? ORDER BY b.created_at DESC LIMIT 5 ");
        $stmt->execute([$user_id]);
        $user_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error fetching user bookings: " . $e->getMessage());
}

$feedback_count = 0;
$avg_rating = 0;
try {
    $stmt = $pdo->prepare(" SELECT COUNT(*) as count, AVG(rating) as avg_rating FROM testimonials WHERE user_id = ? ");
    $stmt->execute([$user_id]);
    $feedback_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $feedback_count = (int)$feedback_data['count'];
    $avg_rating = round((float)$feedback_data['avg_rating'], 1);
} catch (Exception $e) {
    error_log("Error fetching feedback: " . $e->getMessage());
}

$all_packages_search = []; $pkg_min_prices_s = []; $pkg_single_models_s = []; $pkg_car_models_s = [];
try {
    $all_packages_search = $pdo->query("SELECT * FROM tint_packages WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $min_rows = $pdo->query("SELECT package_id, MIN(price) AS min_price FROM package_model_prices GROUP BY package_id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($min_rows as $mr) $pkg_min_prices_s[(int)$mr['package_id']] = (float)$mr['min_price'];
    $single_rows = $pdo->query("SELECT package_id, MIN(car_model) AS car_model, COUNT(DISTINCT car_model) AS car_count FROM package_model_prices GROUP BY package_id HAVING car_count = 1")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($single_rows as $sr) $pkg_single_models_s[(int)$sr['package_id']] = (string)$sr['car_model'];
    $cm_rows = $pdo->query("SELECT package_id, car_model FROM package_model_prices GROUP BY package_id, car_model ORDER BY car_model")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cm_rows as $cr) $pkg_car_models_s[(int)$cr['package_id']][] = $cr['car_model'];
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — RG Cartint Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .dashboard-header { background: linear-gradient(135deg, #1e1e1e 0%, #333 100%); color: white; padding: 24px 0; border-bottom: 4px solid #ffc107; }
        .notification-badge { position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.7rem; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .notif-dropdown { width: 380px; max-height: 480px; overflow-y: auto; border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
        .notif-item { padding: 12px 15px; border-bottom: 1px solid #eee; transition: background 0.2s; }
        .notif-item:hover { background: #f9f9f9; }
        .notif-item.unread { background: #f0f7ff; border-left: 3px solid #ffc107; }
        .notif-icon { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px; flex-shrink: 0; }
        .notif-time { font-size: 0.75rem; color: #999; }
        .mini-card { border: none; border-radius: 14px; box-shadow: 0 5px 18px rgba(0,0,0,0.06); overflow: hidden; margin-bottom: 20px; }
        .mini-head { display:flex; align-items:center; justify-content:space-between; padding: 14px 16px; background: #fff; border-bottom: 1px solid #eee; }
        .mini-user { display:flex; align-items:center; gap:10px; min-width: 0; }
        .mini-avatar { width: 44px; height: 44px; border-radius: 50%; background: #333; color: #fff; display:flex; align-items:center; justify-content:center; font-weight: 800; font-size: 1.2rem; }
        .mini-meta { min-width:0; }
        .mini-meta .name { font-weight: 800; margin: 0; font-size: 1rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width: 260px; }
        .mini-meta .role { margin: 0; font-size: 0.85rem; color: #6c757d; }
        .mini-body { padding: 16px; background: #fff; }
        .mini-actions { display:flex; justify-content:flex-end; gap:10px; margin-top: 12px; }
        .profile-dd-btn{ position: relative; color: #fff; border-radius: 10px; padding: 8px 12px; background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.18); transition: all .25s; }
        .profile-dd-btn:hover{ background: rgba(255,255,255,0.18); color:#ffc107; }
        .profile-dd-badge-wrap { position: absolute; top: 0; right: 0; transform: translate(25%,-25%); }
        
        
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-completed { background: #cffafe; color: #155e75; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-declined { background: #fee2e2; color: #991b1b; border-left: 3px solid #dc2626; }
        
        
        .booking-item { padding: 12px; border: 1px solid #eee; border-radius: 10px; margin-bottom: 10px; transition: all 0.2s; }
        .booking-item:hover { background: #f8fafc; border-color: #ffc107; }
        
        
        .rejection-tooltip { cursor: help; border-bottom: 1px dashed #dc2626; }
    
        .pkg-hero-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
            border-radius: 18px !important;
            padding: 22px 28px !important;
            color: #ffffff !important;
            margin-bottom: 20px !important;
            box-shadow: 0 8px 24px rgba(15,23,42,0.12) !important;
            position: relative !important;
            overflow: hidden !important;
            width: 100% !important;
        }
        .pkg-hero-banner::after {
            content: '' !important;
            position: absolute !important;
            top: -50% !important;
            right: -10% !important;
            width: 300px !important;
            height: 300px !important;
            background: radial-gradient(circle, rgba(212,160,23,0.15) 0%, transparent 70%) !important;
            pointer-events: none !important;
        }
</style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/customer.css?v=<?= time() ?> time() ?>">

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
        <?php if (!$is_profile_incomplete): ?>
        <ul class="cs-nav">
            <li class="cs-nav-item">
                <a href="dashboard.php" class="cs-nav-link">
                    <span class="cs-nav-icon"><i class="fas fa-gauge-high"></i></span>Dashboard
                </a>
            </li>
            <li class="cs-nav-item">
                <a href="support.php" class="cs-nav-link">
                    <span class="cs-nav-icon"><i class="fas fa-headset"></i></span>Customer Support
                </a>
            </li>
            <li class="cs-nav-item">
                <a href="my_bookings.php" class="cs-nav-link">
                    <span class="cs-nav-icon"><i class="fas fa-calendar-alt"></i></span>My Bookings
                </a>
            </li>
        </ul>
        <?php else: ?>
        <div style="padding: 10px 16px; font-size: 11px; color: #a1a1aa; line-height: 1.4;"><i class="fas fa-lock me-1"></i> Please complete your profile to unlock other pages.</div>
        <?php endif; ?>
    </div>
    <div class="cs-section">
        <div class="cs-section-label">Account</div>
        <ul class="cs-nav">
            <?php if (!$is_profile_incomplete): ?>
            
            <?php endif; ?>
            <li class="cs-nav-item">
                <a href="profile.php" class="cs-nav-link active">
                    <span class="cs-nav-icon"><i class="fas fa-user-circle"></i></span>My Profile
                </a>
            </li>
            <li class="cs-nav-item">
                <a href="../logout.php" class="cs-nav-link">
                    <span class="cs-nav-icon"><i class="fas fa-right-from-bracket"></i></span>Logout
                </a>
            </li>
        </ul>
    </div>
    <div class="cs-footer">
        <div class="cs-user">
            <div class="cs-user-avatar"><?= strtoupper(substr($user['full_name'] ?? 'C', 0, 1)) ?></div>
            <div class="cs-user-info">
                <div class="cs-user-name"><?= htmlspecialchars($user['full_name'] ?? 'Customer') ?></div>
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
                    <?php if($unread_count > 0): ?>
                    <span class="topbar-notif-badge" id="notifBadgeWrap"><?= $unread_count > 9 ? '9+' : $unread_count ?></span>
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
                                    <strong style="font-size:12px;"><?= htmlspecialchars($notif['title']) ?></strong>
                                    <form method="POST" class="ms-2 notifActionForm" style="flex-shrink:0;">
                                        <input type="hidden" name="notification_id" value="<?= (int)$notif['id'] ?>">
                                        <input type="hidden" name="notification_action" value="delete">
                                        <button type="submit" class="btn-close" style="width:.45em;height:.45em;" aria-label="Delete"></button>
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
                    <div class="topbar-user-avatar"><?= strtoupper(substr($user['full_name'] ?? 'C', 0, 1)) ?></div>
                    <span class="topbar-user-name d-none d-md-inline"><?= htmlspecialchars($user['full_name'] ?? 'Customer') ?></span>
                    <i class="fas fa-chevron-down" style="font-size:10px;color:#94a3b8;"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width:170px;border-radius:10px;border:1px solid #E6E2DA;box-shadow:0 8px 24px rgba(0,0,0,.1);">
                    <?php if (!$is_profile_incomplete): ?>
                    <li><a class="dropdown-item" href="dashboard.php" style="font-size:13px;"><i class="fas fa-gauge-high me-2" style="color:#D4A017;"></i>Dashboard</a></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="profile.php" style="font-size:13px;"><i class="fas fa-user-circle me-2" style="color:#D4A017;"></i>My Profile</a></li>
                    <?php if (!$is_profile_incomplete): ?>
                    <li><a class="dropdown-item" href="my_bookings.php" style="font-size:13px;"><i class="fas fa-calendar-alt me-2" style="color:#D4A017;"></i>My Bookings</a></li>
                    
                    
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../logout.php" style="font-size:13px;"><i class="fas fa-right-from-bracket me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="cust-content" style="max-width: 1200px; margin: 0 auto; padding: 24px 24px;">
        
        <div class="pkg-hero-banner d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <span class="badge mb-2" style="background:rgba(212,160,23,0.2);color:#F5C842;border:1px solid rgba(212,160,23,0.4);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;letter-spacing:.05em;">
                    <i class="fas fa-user-circle me-1"></i>ACCOUNT PROFILE
                </span>
                <h3 style="font-weight:800;margin:0;font-size:1.6rem;letter-spacing:-0.01em;color:#ffffff;">My Profile</h3>
                <p style="color:#94a3b8;font-size:13px;margin:6px 0 0;max-width:640px;line-height:1.5;">
                    Manage your account information, contact details, and security preferences
                </p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-sm" style="background:rgba(255,255,255,0.08);color:#ffffff;border:1px solid rgba(255,255,255,0.2);padding:8px 16px;border-radius:10px;font-weight:700;font-size:12px;">
                    <i class="fas fa-gauge-high me-2" style="color:#D4A017;"></i>Dashboard
                </a>
            </div>
        </div>

        <?php if($message): ?>
        <div class="alert alert-<?= htmlspecialchars($msg_type) ?> alert-dismissible fade show mb-4" role="alert" style="border-radius:10px;font-size:13px;">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($is_profile_incomplete): ?>
        <div class="alert d-flex align-items-center gap-3 p-3 mb-4" 
             style="background:#fffbeb; border:1px solid #fef3c7; border-radius:12px; color:#b45309; font-size:13.5px; font-weight:500; line-height:1.4;">
            <i class="fas fa-exclamation-triangle" style="color:#d97706; font-size:18px; flex-shrink:0;"></i>
            <div>
                <strong>Action Required: Profile Incomplete!</strong><br>
                Please add your <strong>Phone Number</strong> and <strong>Address</strong> below to unlock access to the booking page and other customer portal features.
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <div class="col-lg-4">
                
                <div class="profile-card mb-4">
                    <div class="profile-avatar"><?= strtoupper(substr($user['full_name'] ?? 'C', 0, 1)) ?></div>
                    <div class="profile-name"><?= htmlspecialchars($user['full_name'] ?? '') ?></div>
                    <div class="profile-since">Member since <?= date('M Y', strtotime($user['created_at'] ?? 'now')) ?></div>
                    <div class="profile-stats-row">
                        <div class="text-center">
                            <div style="font-size:1.3rem;font-weight:800;"><?= count($user_bookings) ?></div>
                            <div style="font-size:11px;color:#94a3b8;margin-top:2px;">Bookings</div>
                        </div>
                        <div class="text-center">
                            <div style="font-size:1.3rem;font-weight:800;"><?= $unread_count ?></div>
                            <div style="font-size:11px;color:#94a3b8;margin-top:2px;">Unread</div>
                        </div>
                        <div class="text-center">
                            <div style="font-size:1.3rem;font-weight:800;"><?= $feedback_count ?></div>
                            <div style="font-size:11px;color:#94a3b8;margin-top:2px;">Reviews</div>
                        </div>
                    </div>
                </div>
                
                <div class="panel-card">
                    <div class="panel-head"><span class="panel-head-title"><i class="fas fa-id-card me-2"></i>Account Info</span></div>
                    <div style="padding:16px;">
                        <div class="profile-info-row">
                            <span><i class="fas fa-envelope me-2" style="color:#D4A017;"></i>Email</span>
                            <span style="font-size:12px;"><?= htmlspecialchars($user['email'] ?? '') ?></span>
                        </div>
                        <div class="profile-info-row">
                            <span><i class="fas fa-phone me-2" style="color:#D4A017;"></i>Phone</span>
                            <span style="font-size:12px;"><?= htmlspecialchars($user['phone'] ?? 'Not set') ?></span>
                        </div>
                        <div class="profile-info-row">
                            <span><i class="fas fa-map-marker-alt me-2" style="color:#D4A017;"></i>Address</span>
                            <span style="font-size:12px;"><?= htmlspecialchars($user['address'] ?? 'Not set') ?></span>
                        </div>
                        <div class="profile-info-row">
                            <span><i class="fas fa-star me-2" style="color:#D4A017;"></i>Rating</span>
                            <span style="font-size:12px;">
                                <?php if($feedback_count > 0): ?><?= $avg_rating ?> ★ (<?= $feedback_count ?> review<?= $feedback_count != 1 ? 's' : '' ?>)<?php else: ?>No reviews yet<?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
        </div>
        
            
            <div class="col-lg-8">
                
                <div class="panel-card mb-4">
                    <div class="panel-head"><span class="panel-head-title"><i class="fas fa-user-edit me-2"></i>Edit Profile</span></div>
                    <div style="padding:20px;">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Full Name</label>
                                    <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required style="border:1.5px solid #E6E2DA;border-radius:8px;font-size:14px;">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Email Address</label>
                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required style="border:1.5px solid #E6E2DA;border-radius:8px;font-size:14px;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Phone Number</label>
                                    <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required style="border:1.5px solid <?= (empty(trim((string)($user['phone'] ?? '')))) ? '#f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.15)' : '#E6E2DA' ?>;border-radius:8px;font-size:14px;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Address</label>
                                    <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>" required style="border:1.5px solid <?= (empty(trim((string)($user['address'] ?? '')))) ? '#f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.15)' : '#E6E2DA' ?>;border-radius:8px;font-size:14px;">
                                </div>
                                <div class="col-12">
                                    <div class="accordion" id="pwAccordion">
                                        <div class="accordion-item" style="border:1.5px solid #E6E2DA;border-radius:8px;overflow:hidden;">
                                            <h2 class="accordion-header" id="pwHead">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pwCollapse" aria-expanded="false" aria-controls="pwCollapse" style="font-size:13px;">
                                                    <i class="fas fa-lock me-2" style="color:#D4A017;"></i> Change Password (optional)
                                                </button>
                                            </h2>
                                            <div id="pwCollapse" class="accordion-collapse collapse" aria-labelledby="pwHead" data-bs-parent="#pwAccordion">
                                                <div class="accordion-body">
                                                    <label class="form-label text-muted small">New Password (leave blank to keep current)</label>
                                                    <input type="password" class="form-control" name="new_password" placeholder="Enter new password" style="border:1.5px solid #E6E2DA;border-radius:8px;font-size:14px;">
                                                    <small class="text-muted">Min. 6 characters</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn-gold"><i class="fas fa-save me-2"></i>Save Changes</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                
                <?php if(!empty($user_bookings)): ?>
                <div class="panel-card">
                    <div class="panel-head">
                        <span class="panel-head-title"><i class="fas fa-calendar-alt me-2"></i>Recent Bookings</span>
                        <a href="my_bookings.php" class="btn-outline-gold" style="font-size:12px;padding:6px 14px;">View All</a>
                    </div>
                    <?php foreach($user_bookings as $booking):
                        $bst = strtolower($booking['status']);
                        $bsc = 's-pending'; $bsi = 'fa-clock';
                        if($bst=='approved') { $bsc='s-approved'; $bsi='fa-check-circle'; }
                        if($bst=='completed') { $bsc='s-completed'; $bsi='fa-circle-check'; }
                        if(in_array($bst,['cancelled','canceled'])) { $bsc='s-cancelled'; $bsi='fa-ban'; }
                        if($bst=='declined') { $bsc='s-declined'; $bsi='fa-times-circle'; }
                    ?>
                    <div style="padding:12px 16px;border-bottom:1px solid #F1EDE4;display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
                        <div>
                            <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($booking['vehicle_model'] ?? 'Vehicle') ?></div>
                            <small style="color:#94a3b8;"><?= htmlspecialchars($booking['type_name'] ?? 'Car') ?> · ₱<?= number_format($booking['total_price'] ?? 0, 0) ?></small>
                            <div style="font-size:12px;color:#94a3b8;margin-top:3px;"><i class="far fa-calendar me-1"></i><?= date('M d, Y', strtotime($booking['appointment_date'] ?? $booking['created_at'])) ?></div>
                            <?php if($bst=='declined' && !empty($booking['rejection_reason'])): ?>
                            <div class="rejection-banner mt-1" style="font-size:11px;padding:4px 8px;"><i class="fas fa-info-circle me-1"></i><?= htmlspecialchars(substr($booking['rejection_reason'], 0, 70)) ?>...</div>
                            <?php endif; ?>
                        </div>
                        <span class="status-pill <?= $bsc ?>" style="white-space:nowrap;"><i class="fas <?= $bsi ?> me-1"></i><?= ucfirst($booking['status']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

const _searchData = <?= json_encode(array_map(function($pkg) use ($pkg_min_prices_s, $pkg_single_models_s, $pkg_car_models_s) {
    $pid = (int)$pkg['id'];
    return [
        'id' => $pid,
        'name' => $pkg['name'],
        'desc' => $pkg['description'] ?? '',
        'price' => $pkg_min_prices_s[$pid] ?? min((float)($pkg['sedan_price']??0),(float)($pkg['suv_price']??0),(float)($pkg['van_price']??0)),
        'cars' => $pkg_car_models_s[$pid] ?? [],
        'single' => $pkg_single_models_s[$pid] ?? null,
        'tint' => $pkg['tint_type'] ?? 'carbon',
    ];
}, $all_packages_search)) ?>;

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
            if (nameMatch || descMatch || tintMatch) results.push({ type: 'package', pkg, car: null, score: nameMatch ? 2 : 1 });
            matchedCars.forEach(car => results.push({ type: 'car', pkg, car, score: 1 }));
        });
        results.sort((a,b) => b.score - a.score);
        const limited = results.slice(0, 8);
        if (limited.length === 0) {
            dropdown.innerHTML = '<div class="search-empty"><i class="fas fa-search" style="font-size:1.4rem;color:#555;margin-bottom:8px;"></i><div>No results for "<strong>' + escHtml(q) + '</strong>"</div></div>';
            dropdown.style.display = 'block'; return;
        }
        dropdown.innerHTML = limited.map((r, i) => {
            const url = 'book.php?package_id=' + r.pkg.id + (r.car ? '&car_model=' + encodeURIComponent(r.car) : (r.pkg.single ? '&car_model=' + encodeURIComponent(r.pkg.single) : ''));
            if (r.type === 'car') {
                return `<a href="${url}" class="search-result-item${i===0?' active':''}" data-idx="${i}"><div class="search-result-icon car"><i class="fas fa-car-side"></i></div><div class="search-result-info"><div class="search-result-title">${escHtml(r.car)}</div><div class="search-result-sub"><i class="fas fa-film me-1"></i>${escHtml(r.pkg.name)} &mdash; ${fmtPrice(r.pkg.price)}</div></div><i class="fas fa-arrow-right search-result-arrow"></i></a>`;
            }
            return `<a href="${url}" class="search-result-item${i===0?' active':''}" data-idx="${i}"><div class="search-result-icon pkg"><i class="fas fa-film"></i></div><div class="search-result-info"><div class="search-result-title">${escHtml(r.pkg.name)}</div><div class="search-result-sub">${r.pkg.cars.length ? r.pkg.cars.length + ' car' + (r.pkg.cars.length>1?'s':'') + ' &mdash; ' : ''}Starts at ${fmtPrice(r.pkg.price)}</div></div><i class="fas fa-arrow-right search-result-arrow"></i></a>`;
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

// Sidebar toggle
const sidebar = document.getElementById('custSidebar');
const overlay = document.getElementById('sidebarOverlay');
const toggle  = document.getElementById('sidebarToggle');
function openCustSidebar()  { if(sidebar) sidebar.classList.add('open'); if(overlay) overlay.classList.add('show'); document.body.style.overflow='hidden'; }
function closeCustSidebar() { if(sidebar) sidebar.classList.remove('open'); if(overlay) overlay.classList.remove('show'); document.body.style.overflow=''; }
if(toggle)  toggle.addEventListener('click', openCustSidebar);
if(overlay) overlay.addEventListener('click', closeCustSidebar);
document.querySelectorAll('.cs-nav-link').forEach(l => l.addEventListener('click', () => { if(window.innerWidth < 992) closeCustSidebar(); }));

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, s => ({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;" }[s]));
}
function notifIcon(type) {
    if (type === "success") return {bg:"bg-success", icon:"fa-check-circle"};
    if (type === "warning") return {bg:"bg-warning", icon:"fa-exclamation-triangle"};
    if (type === "danger") return {bg:"bg-danger", icon:"fa-times-circle"};
    return {bg:"bg-primary", icon:"fa-info-circle"};
}
async function submitNotifFormAjax(form) {
    try {
        const fd = new FormData(form);
        const res = await fetch("profile.php", { method:"POST", body:fd, headers:{"X-Requested-With":"XMLHttpRequest"}, cache:"no-store" });
        if (!res.ok) return false;
        await pollNotifications();
        return true;
    } catch (e) { return false; }
}
document.addEventListener("submit", function(e) {
    if (e.target.classList && e.target.classList.contains("notifActionForm")) { e.preventDefault(); e.stopPropagation(); submitNotifFormAjax(e.target); }
}, true);
document.addEventListener("click", function(e) {
    const btn = e.target.closest(".notifActionForm .btn-close");
    if (btn) e.stopPropagation();
}, true);
async function pollNotifications() {
    try {
        const res = await fetch("../api/notifications.php", {cache:"no-store"});
        if (!res.ok) return;
        const data = await res.json();
        const bw = document.getElementById("notifBadgeWrap");
        if (bw) { if(data.unread>0){bw.style.display='flex';bw.textContent=data.unread>9?'9+':data.unread;}else{bw.style.display='none';} }
        const nl = document.getElementById("notifList");
        if (!nl) return;
        if (!data.notifications || !data.notifications.length) { nl.innerHTML='<div class="p-4 text-center text-muted small">No notifications yet</div>'; return; }
        nl.innerHTML = data.notifications.map(n => {
            const m = notifIcon(n.type);
            const u = parseInt(n.is_read)===0?'unread':'';
            const dt = new Date(String(n.created_at).replace(' ','T'));
            return `<div class="notif-row ${u}">
                <div class="notif-icon ${m.bg}"><i class="fas ${m.icon} fa-xs"></i></div>
                <div class="flex-grow-1" style="min-width:0;">
                    <div class="d-flex justify-content-between align-items-start">
                        <strong style="font-size:12px;">${escapeHtml(n.title)}</strong>
                        <form method="POST" class="ms-2 notifActionForm" style="flex-shrink:0;">
                            <input type="hidden" name="notification_id" value="${n.id}">
                            <input type="hidden" name="notification_action" value="delete">
                            <button type="submit" class="btn-close" style="width:.45em;height:.45em;" aria-label="Delete"></button>
                        </form>
                    </div>
                    <p style="font-size:11px;color:#64748b;margin:2px 0;">${escapeHtml(n.message)}</p>
                    <div class="notif-time">${dt.toLocaleString()}</div>
                </div>
            </div>`;
        }).join('');
    } catch(e) {}
}
pollNotifications();
setInterval(pollNotifications, 5000);
</script>
<?php include __DIR__ . '/../includes/chatbot.php'; ?>
<?php include __DIR__ . '/../includes/customer_modals.php'; ?>

</body>
</html>
