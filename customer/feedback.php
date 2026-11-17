<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require_customer_auth('../login.php');

$user_id = (int)$_SESSION['user_id'];
$message = '';
$msg_type = '';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $user = [];
}

try {
    $packages = $pdo->query("SELECT DISTINCT name FROM tint_packages ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $packages = ['Carbon', 'Ceramic', 'Nano Ceramic', 'Crystalline', 'Dyed', 'Metalized', 'Hybrid'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['notification_action'])) {
    if ($_POST['notification_action'] == 'mark_read') {
        $notif_id = filter_var($_POST['notification_id'], FILTER_VALIDATE_INT);
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ? AND user_type='customer'");
        $stmt->execute([$notif_id, $user_id]);
    } elseif ($_POST['notification_action'] == 'mark_all_read') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND user_type = 'customer'");
        $stmt->execute([$user_id]);
    } elseif ($_POST['notification_action'] == 'delete') {
        $notif_id = filter_var($_POST['notification_id'], FILTER_VALIDATE_INT);
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ? AND user_type='customer'");
        $stmt->execute([$notif_id, $user_id]);
    }
    header("Location: feedback.php");
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND user_type = 'customer' AND is_read = 0");
$stmt->execute([$user_id]);
$unread_count = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND user_type = 'customer' ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nav_pending = 0;
try {
    $s = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id=? AND status IN ('Pending','On Hold','Confirmed')");
    $s->execute([$user_id]);
    $nav_pending = (int)$s->fetchColumn();
} catch (PDOException $e) {}

$preset_booking_id = (int)($_GET['booking_id'] ?? ($_POST['booking_id'] ?? 0));
$preset_booking = null;

if ($preset_booking_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT b.*, p.name as package_name FROM bookings b LEFT JOIN tint_packages p ON b.package_id = p.id WHERE b.id = ? AND b.user_id = ?");
        $stmt->execute([$preset_booking_id, $user_id]);
        $preset_booking = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'submit_feedback') {
    $rating = filter_var($_POST['rating'] ?? 5.0, FILTER_VALIDATE_FLOAT);
    $vehicle = trim($_POST['vehicle'] ?? '');
    $tint_type = trim($_POST['tint_type'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $would_recommend = isset($_POST['would_recommend']) ? 1 : 0;
    $booking_id_to_save = (int)($_POST['booking_id'] ?? 0);

    if (empty($comment)) {
        $message = "Please provide your comments.";
        $msg_type = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO testimonials 
                (user_id, booking_id, name, email, vehicle, tint_type, rating, comment, would_recommend, is_approved, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
            ");
            $stmt->execute([
                $user_id,
                $booking_id_to_save > 0 ? $booking_id_to_save : null,
                $user['full_name'] ?? 'Customer',
                $user['email'] ?? null,
                $vehicle ?: null,
                $tint_type ?: null,
                $rating,
                $comment,
                $would_recommend
            ]);

            
            $admin_notif_msg = "Customer " . ($user['full_name'] ?? 'Customer') . " submitted a review rating of $rating ★.";
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_type, title, message, type, related_id, related_type, is_read, created_at) 
                VALUES ('admin', 'New Feedback Submitted', ?, 'info', ?, 'review', 0, NOW())
            ");
            $stmt->execute([$admin_notif_msg, $user_id]);

            $message = "Thank you for your feedback! Your review has been submitted and is pending administrator approval.";
            $msg_type = "success";
        } catch (PDOException $e) {
            $message = "Error submitting feedback: " . $e->getMessage();
            $msg_type = "danger";
        }
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $testimonials = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Feedback — RG CARTINTWORKS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,600;9..40,700;9..40,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/customer.css?v=<?= time() ?> time() ?>">
<style>
    .star-rating {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 8px;
    }
    .star-rating input {
        display: none;
    }
    .star-rating label {
        font-size: 1.8rem;
        color: #e2e8f0;
        cursor: pointer;
        transition: color 0.15s ease-in-out;
    }
    .star-rating input:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label {
        color: var(--gold);
    }
    .feedback-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 20px;
        margin-bottom: 16px;
        transition: all 0.2s ease;
    }
    .feedback-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    .star-gold { color: var(--gold); }
    .star-gray { color: #e2e8f0; }
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
                <a href="feedback.php" class="cs-nav-link active">
                    <span class="cs-nav-icon"><i class="fas fa-star"></i></span>Feedback
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
                    <div class="topbar-user-avatar"><?= strtoupper(substr($user_name ?? 'C', 0, 1)) ?></div>
                    <span class="topbar-user-name d-none d-md-inline"><?= htmlspecialchars($user_name ?? 'Customer') ?></span>
                    <i class="fas fa-chevron-down" style="font-size:10px;color:#94a3b8;"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width:170px;border-radius:10px;border:1px solid #E6E2DA;box-shadow:0 8px 24px rgba(0,0,0,.1);">
                    <li><a class="dropdown-item" href="dashboard.php" style="font-size:13px;"><i class="fas fa-gauge-high me-2" style="color:#D4A017;"></i>Dashboard</a></li>
                    <li><a class="dropdown-item" href="profile.php" style="font-size:13px;"><i class="fas fa-user-circle me-2" style="color:#D4A017;"></i>My Profile</a></li>
                    <li><a class="dropdown-item" href="my_bookings.php" style="font-size:13px;"><i class="fas fa-calendar-alt me-2" style="color:#D4A017;"></i>My Bookings</a></li>
                    
                    <li><a class="dropdown-item" href="feedback.php" style="font-size:13px;"><i class="fas fa-star me-2" style="color:#D4A017;"></i>Feedback</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../logout.php" style="font-size:13px;"><i class="fas fa-right-from-bracket me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="cust-content">
        <div class="page-hdr">
            <div>
                <h1 class="page-hdr-title">Feedback & Reviews</h1>
                <p class="page-hdr-sub">Share your experience with RG CARTINTWORKS and see your submitted feedback</p>
            </div>
        </div>

        <?php if($message): ?>
        <div class="alert alert-<?= htmlspecialchars($msg_type) ?> alert-dismissible fade show mb-4" role="alert" style="border-radius:10px;font-size:13px;">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <div class="col-lg-5">
                <div class="panel-card" style="padding:24px;">
                    <h5 style="font-weight:800;font-size:1.1rem;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-pen-nib" style="color:var(--gold);"></i> Write a Review
                    </h5>
                    <form method="POST" action="feedback.php">
                        <input type="hidden" name="action" value="submit_feedback">
                        
                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600;font-size:12px;display:block;">Your Rating</label>
                            <div class="star-rating">
                                <input type="radio" id="star5" name="rating" value="5.0" checked required /><label for="star5" title="5 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star4" name="rating" value="4.0" /><label for="star4" title="4 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star3" name="rating" value="3.0" /><label for="star3" title="3 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star2" name="rating" value="2.0" /><label for="star2" title="2 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star1" name="rating" value="1.0" /><label for="star1" title="1 star"><i class="fas fa-star"></i></label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600;font-size:12px;">Vehicle Model</label>
                            <input type="text" name="vehicle" class="form-control" placeholder="e.g. Toyota Fortuner 2023" style="border-radius:8px;font-size:13px;padding:10px;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600;font-size:12px;">Tint Package Used</label>
                            <select name="tint_type" class="form-select" style="border-radius:8px;font-size:13px;padding:10px;">
                                <option value="">-- Select package --</option>
                                <?php foreach($packages as $pkg_name): ?>
                                <option value="<?= htmlspecialchars($pkg_name) ?>"><?= htmlspecialchars($pkg_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600;font-size:12px;">Your Review / Comments <span class="text-danger">*</span></label>
                            <textarea name="comment" class="form-control" rows="4" placeholder="Tell us about the tint quality and service..." style="border-radius:8px;font-size:13px;padding:12px;" required></textarea>
                        </div>

                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="would_recommend" name="would_recommend" value="1" checked>
                            <label class="form-check-label" for="would_recommend" style="font-size:12px;font-weight:600;user-select:none;">I would recommend RG CARTINTWORKS to others</label>
                        </div>

                        <button type="submit" class="btn-gold w-100" style="padding:12px;border-radius:8px;font-weight:700;font-size:13px;background:var(--gold);color:var(--ink);border:none;transition:all 0.2s;">
                            <i class="fas fa-paper-plane me-2"></i>Submit Feedback
                        </button>
                    </form>
                </div>
            </div>

            
            <div class="col-lg-7">
                <div class="panel-card" style="padding:24px;">
                    <h5 style="font-weight:800;font-size:1.1rem;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-clock-rotate-left" style="color:var(--gold);"></i> Submitted Reviews
                    </h5>
                    
                    <?php if(empty($testimonials)): ?>
                    <div style="padding:60px 20px;text-align:center;color:#94a3b8;">
                        <i class="fas fa-star" style="font-size:3rem;margin-bottom:16px;display:block;opacity:0.3;"></i>
                        <p style="font-size:13px;margin:0;">You haven't submitted any feedback yet.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach($testimonials as $t): ?>
                        <div class="feedback-card">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
                                <div>
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= (int)$t['rating'] ? 'star-gold' : 'star-gray' ?>" style="font-size:13px;"></i>
                                        <?php endfor; ?>
                                        <span style="font-size:12px;font-weight:700;color:#0f172a;margin-left:4px;"><?= number_format($t['rating'], 1) ?></span>
                                    </div>
                                    <?php if(!empty($t['vehicle']) || !empty($t['tint_type'])): ?>
                                    <div style="font-size:11px;color:var(--text-secondary);margin-top:4px;">
                                        <?php if(!empty($t['vehicle'])): ?>
                                        <span><i class="fas fa-car me-1"></i><?= htmlspecialchars($t['vehicle']) ?></span>
                                        <?php endif; ?>
                                        <?php if(!empty($t['tint_type'])): ?>
                                        <span class="ms-2"><i class="fas fa-film me-1"></i><?= htmlspecialchars($t['tint_type']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div style="display:flex;gap:6px;">
                                    <?php if($t['is_approved']): ?>
                                    <span class="badge bg-success-subtle text-success px-2 py-1" style="font-size:10px;border-radius:4px;font-weight:600;text-transform:uppercase;">Approved</span>
                                    <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning px-2 py-1" style="font-size:10px;border-radius:4px;font-weight:600;text-transform:uppercase;">Pending Approval</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <p style="font-size:13px;color:#475569;margin-bottom:12px;line-height:1.5;white-space:pre-line;"><?= htmlspecialchars($t['comment']) ?></p>
                            
                            <div style="display:flex;justify-content:space-between;align-items:center;font-size:10px;color:#94a3b8;margin-top:12px;">
                                <span>
                                    <i class="fas <?= $t['would_recommend'] ? 'fa-thumbs-up text-success' : 'fa-thumbs-down text-danger' ?> me-1"></i>
                                    <?= $t['would_recommend'] ? 'Recommends this studio' : 'Does not recommend' ?>
                                </span>
                                <span>Submitted on <?= date('F j, Y, g:i A', strtotime($t['created_at'])) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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
</script>
<?php include __DIR__ . '/../includes/chatbot.php'; ?>
<?php include __DIR__ . '/../includes/customer_modals.php'; ?>

</body>
</html>
