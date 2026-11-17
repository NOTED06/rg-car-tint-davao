<?php

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve'])) {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("UPDATE testimonials SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Feedback approved successfully!'];
    } elseif (isset($_POST['reject'])) {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Feedback rejected and deleted.'];
    } elseif (isset($_POST['delete_approved'])) {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ? AND is_approved = 1");
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Approved feedback deleted successfully.'];
    } elseif (isset($_POST['delete_all_pending'])) {
        $stmt = $pdo->prepare("DELETE FROM testimonials WHERE is_approved = 0");
        $stmt->execute();
        $count = $stmt->rowCount();
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => "$count pending feedback entries deleted successfully."];
    } elseif (isset($_POST['delete_all_approved'])) {
        $stmt = $pdo->prepare("DELETE FROM testimonials WHERE is_approved = 1");
        $stmt->execute();
        $count = $stmt->rowCount();
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => "$count approved feedback entries deleted successfully."];
    }
    
    header('Location: feedback.php');
    exit();
}

$pending_stmt = $pdo->query("
    SELECT * FROM testimonials 
    WHERE is_approved = 0 
    ORDER BY created_at DESC
");
$pending_feedback = $pending_stmt->fetchAll();

$approved_stmt = $pdo->query("
    SELECT * FROM testimonials 
    WHERE is_approved = 1 
    ORDER BY created_at DESC
");
$approved_feedback = $approved_stmt->fetchAll();

$total_feedback = count($pending_feedback) + count($approved_feedback);
$avg_rating = 0;
if (count($approved_feedback) > 0) {
    $sum = 0;
    foreach ($approved_feedback as $fb) {
        $sum += floatval($fb['rating']);
    }
    $avg_rating = $sum / count($approved_feedback);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        
        :root {
            --primary:        #D4A017;
            --primary-light:  #F5C842;
            --primary-dark:   #B8860B;
            --primary-bg:     rgba(212,160,23,.08);

            --success:        #059669;
            --success-bg:     #d1fae5;
            --warning:        #d97706;
            --warning-bg:     #fef3c7;
            --danger:         #dc2626;
            --danger-bg:      #fee2e2;
            --info:           #0891b2;
            --info-bg:        #cffafe;

            --border:         #E6E2DA;
            --text-primary:   #0A0A0A;
            --text-secondary: #64748b;
            --text-muted:     #94a3b8;
            --surface:        #ffffff;
            --bg:             #F5F3EF;

            --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --shadow-md: 0 4px 6px rgba(0,0,0,.05), 0 2px 4px rgba(0,0,0,.04);
            --shadow-lg: 0 10px 15px rgba(0,0,0,.06), 0 4px 6px rgba(0,0,0,.04);

            --radius:    12px;
            --radius-sm: 8px;
            --radius-lg: 16px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.5;
        }

        
        .admin-header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }

        .header-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            flex-shrink: 0;
        }

        .brand-text {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .brand-sub {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 400;
        }

        .header-actions {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            transition: all .15s;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-secondary);
            text-decoration: none;
        }

        .header-btn:hover {
            background: var(--bg);
            color: var(--text-primary);
            border-color: #cbd5e1;
        }

        .header-btn.danger { color: var(--danger); border-color: #fca5a5; }
        .header-btn.danger:hover { background: var(--danger-bg); }

        
        .page-wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 28px 24px 48px;
        }

        
        .page-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        .page-sub {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 24px;
        }

        
        .flash {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 20px;
        }
        .flash.success { background: var(--success-bg); color: #065f46; }
        .flash.danger  { background: var(--danger-bg);  color: #991b1b; }
        .flash-icon {
            width: 28px; height: 28px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        }
        .flash.success .flash-icon { background: #a7f3d0; color: var(--success); }
        .flash.danger  .flash-icon { background: #fecaca; color: var(--danger); }
        .flash-close { margin-left: auto; background: none; border: none; cursor: pointer; color: inherit; opacity: .6; font-size: 15px; }

        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        @media (max-width: 900px) { .metrics-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 500px) { .metrics-grid { grid-template-columns: 1fr; } }

        .metric-card {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            padding: 18px 20px;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
            transition: transform .15s, box-shadow .15s;
        }
        .metric-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }

        .metric-accent {
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            border-radius: var(--radius) var(--radius) 0 0;
        }
        .accent-indigo  { background: linear-gradient(90deg, var(--primary), var(--primary-light)); }
        .accent-amber   { background: linear-gradient(90deg, var(--warning), #fbbf24); }
        .accent-green   { background: linear-gradient(90deg, var(--success), #34d399); }
        .accent-yellow  { background: linear-gradient(90deg, #eab308, #facc15); }

        .metric-icon {
            width: 36px; height: 36px;
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            font-size: 15px;
            margin-bottom: 12px;
        }
        .icon-indigo { background: var(--primary-bg); color: var(--primary); }
        .icon-amber  { background: var(--warning-bg); color: var(--warning); }
        .icon-green  { background: var(--success-bg); color: var(--success); }
        .icon-yellow { background: #fef9c3; color: #ca8a04; }

        .metric-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
            margin-bottom: 4px;
        }
        .metric-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .rating-circle {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--warning), #fbbf24);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            color: #78350f;
            font-weight: 700;
            flex-shrink: 0;
        }
        .rating-circle .rc-num { font-size: 18px; line-height: 1; }
        .rating-circle .rc-stars { font-size: 8px; }

        
        .bulk-bar {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px 20px;
            margin-bottom: 28px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .bulk-bar-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 7px;
            margin-right: 4px;
        }
        .bulk-bar-label i { color: var(--primary); }

        
        .section-hdr {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            margin-top: 8px;
        }
        .section-hdr-icon {
            width: 32px; height: 32px;
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
        }
        .section-hdr-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .section-hdr-count {
            font-size: 12px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 2px;
        }
        .section-hdr-line {
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        
        .fb-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 14px;
            overflow: hidden;
            transition: box-shadow .15s, transform .15s;
        }
        .fb-card:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); }

        .fb-card-accent {
            height: 4px;
        }
        .fb-card-accent.pending  { background: linear-gradient(90deg, var(--warning), #fbbf24); }
        .fb-card-accent.approved { background: linear-gradient(90deg, var(--success), #34d399); }

        .fb-card-body {
            padding: 18px 20px;
            display: flex;
            gap: 20px;
        }

        .fb-avatar {
            width: 44px; height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex; align-items: center; justify-content: center;
            color: white;
            font-size: 16px;
            font-weight: 700;
            flex-shrink: 0;
            text-transform: uppercase;
        }

        .fb-content { flex: 1; min-width: 0; }

        .fb-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 6px;
        }
        .fb-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            flex-shrink: 0;
        }
        .status-pending  { background: var(--warning-bg); color: #92400e; }
        .status-approved { background: var(--success-bg); color: #065f46; }

        
        .recommend-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: var(--success-bg);
            color: #065f46;
        }

        
        .stars {
            display: flex;
            align-items: center;
            gap: 2px;
            color: var(--warning);
            font-size: 13px;
            margin-bottom: 8px;
        }
        .stars .rating-val {
            font-size: 12px;
            color: var(--text-muted);
            margin-left: 5px;
            font-weight: 500;
        }

        
        .fb-comment {
            font-size: 13.5px;
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 10px;
            font-style: italic;
        }

        
        .fb-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 10px;
        }
        .fb-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            background: var(--primary-bg);
            color: var(--primary);
            border: 1px solid #c7d2fe;
        }

        
        .fb-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            font-size: 12px;
            color: var(--text-muted);
        }
        .fb-meta span { display: flex; align-items: center; gap: 4px; }

        
        .fb-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
            justify-content: center;
            flex-shrink: 0;
            min-width: 120px;
        }

        
        .act-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 600;
            font-family: inherit;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all .15s;
            white-space: nowrap;
            width: 100%;
            justify-content: center;
        }
        .act-approve {
            background: var(--success-bg);
            color: #065f46;
            border-color: #6ee7b7;
        }
        .act-approve:hover { background: var(--success); color: white; border-color: var(--success); }

        .act-reject {
            background: var(--warning-bg);
            color: #92400e;
            border-color: #fcd34d;
        }
        .act-reject:hover { background: var(--warning); color: white; border-color: var(--warning); }

        .act-delete {
            background: var(--danger-bg);
            color: #991b1b;
            border-color: #fca5a5;
        }
        .act-delete:hover { background: var(--danger); color: white; border-color: var(--danger); }

        
        .bulk-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 600;
            font-family: inherit;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all .15s;
        }
        .bulk-btn-warning {
            background: var(--warning-bg);
            color: #92400e;
            border-color: #fcd34d;
        }
        .bulk-btn-warning:hover { background: var(--warning); color: white; border-color: var(--warning); }
        .bulk-btn-danger {
            background: var(--danger-bg);
            color: #991b1b;
            border-color: #fca5a5;
        }
        .bulk-btn-danger:hover { background: var(--danger); color: white; border-color: var(--danger); }

        
        .empty-state {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 40px 20px;
            text-align: center;
            color: var(--text-muted);
            margin-bottom: 14px;
        }
        .empty-state i { font-size: 32px; margin-bottom: 10px; opacity: .4; display: block; }
        .empty-state p { font-size: 13px; }
    </style>
</head>
<body>

<header class="admin-header">
    <div class="header-inner">
        <a href="index.php" class="brand d-flex align-items-center text-decoration-none">
            <div class="brand-icon me-2" style="background:none;width:auto;height:auto;box-shadow:none;">
                <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 2L20 8L26 10L24 16L26 22L20 24L16 30L12 24L6 22L8 16L6 10L12 8L16 2Z" fill="#D4A017"/>
                    <path d="M16 8L19 12L24 14L22 18L24 22L19 24L16 28L13 24L8 22L10 18L8 14L13 12L16 8Z" fill="#0A0A0A"/>
                    <path d="M16 12L18 15L22 16L20 19L22 22L18 23L16 26L14 23L10 22L12 19L10 16L14 15L16 12Z" fill="#F5C842"/>
                </svg>
            </div>
            <div class="d-flex flex-column">
                <div class="d-flex align-items-center gap-2">
                    <span class="brand-text" style="font-family:'Bebas Neue', sans-serif;font-size:1.45rem;letter-spacing:.05em;color:#0A0A0A;line-height:1;font-weight:700;">RG CARTINTWORKS</span>
                    <span style="background:#D4A017;color:#0A0A0A;font-size:.62rem;font-weight:800;padding:.15rem .35rem;border-radius:4px;line-height:1;letter-spacing:.05em;">DAVAO</span>
                </div>
                <div class="brand-sub" style="font-size:10px;color:#64748b;font-weight:500;margin-top:1px;letter-spacing:.02em;">Feedback Management</div>
            </div>
        </a>

        <div class="header-actions">
            
            <a href="logout.php" class="header-btn danger">
                <i class="fa-solid fa-right-from-bracket" style="font-size:12px;"></i>
                Logout
            </a>
        </div>
    </div>
</header>

<div class="page-wrap">

    <div class="page-title"><i class="fa-solid fa-star-half-stroke me-2" style="color:var(--primary);"></i>Feedback Management</div>
    <div class="page-sub">Review, approve, and manage customer feedback</div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="flash <?= $_SESSION['flash_message']['type'] ?>">
            <div class="flash-icon">
                <i class="fa-solid fa-<?= $_SESSION['flash_message']['type'] == 'success' ? 'check' : 'exclamation' ?>"></i>
            </div>
            <?= htmlspecialchars($_SESSION['flash_message']['message']) ?>
            <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    
    <div class="metrics-grid">
        
        <div class="metric-card">
            <div class="metric-accent accent-indigo"></div>
            <div class="metric-icon icon-indigo"><i class="fa-solid fa-comments"></i></div>
            <div class="metric-value"><?= $total_feedback ?></div>
            <div class="metric-label">Total Feedback</div>
        </div>
        
        <div class="metric-card">
            <div class="metric-accent accent-amber"></div>
            <div class="metric-icon icon-amber"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="metric-value"><?= count($pending_feedback) ?></div>
            <div class="metric-label">Pending Approval</div>
        </div>
        
        <div class="metric-card">
            <div class="metric-accent accent-green"></div>
            <div class="metric-icon icon-green"><i class="fa-solid fa-circle-check"></i></div>
            <div class="metric-value"><?= count($approved_feedback) ?></div>
            <div class="metric-label">Approved Reviews</div>
        </div>
        
        <div class="metric-card">
            <div class="metric-accent accent-yellow"></div>
            <div class="metric-icon icon-yellow"><i class="fa-solid fa-star"></i></div>
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="rating-circle">
                    <div class="rc-num"><?= number_format($avg_rating, 1) ?></div>
                    <div class="rc-stars">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <?php if($i <= floor($avg_rating)): ?><i class="fas fa-star"></i>
                            <?php elseif($i == ceil($avg_rating) && ($avg_rating - floor($avg_rating)) >= 0.5): ?><i class="fas fa-star-half-alt"></i>
                            <?php else: ?><i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                </div>
                <div>
                    <div class="metric-value" style="font-size:14px;font-weight:700;">Average Rating</div>
                    <div class="metric-label">From <?= count($approved_feedback) ?> reviews</div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="bulk-bar">
        <div class="bulk-bar-label">
            <i class="fa-solid fa-layer-group"></i>
            Bulk Actions
        </div>
        <form method="POST" class="d-inline" data-rg-confirm="Delete ALL pending feedback? This cannot be undone." data-rg-type="danger" data-rg-title="Delete All Pending">
            <button type="submit" name="delete_all_pending" class="bulk-btn bulk-btn-warning">
                <i class="fa-solid fa-trash-can"></i>
                Delete All Pending (<?= count($pending_feedback) ?>)
            </button>
        </form>
        <form method="POST" class="d-inline" data-rg-confirm="Delete ALL approved feedback? This cannot be undone." data-rg-type="danger" data-rg-title="Delete All Approved">
            <button type="submit" name="delete_all_approved" class="bulk-btn bulk-btn-danger">
                <i class="fa-solid fa-trash-can"></i>
                Delete All Approved (<?= count($approved_feedback) ?>)
            </button>
        </form>
    </div>

    
    <div class="section-hdr">
        <div class="section-hdr-icon icon-amber"><i class="fa-solid fa-clock"></i></div>
        <div class="section-hdr-title">Pending Approval</div>
        <span class="section-hdr-count status-pending"><?= count($pending_feedback) ?></span>
        <div class="section-hdr-line"></div>
    </div>

    <?php if (empty($pending_feedback)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-inbox"></i>
            <p>No pending feedback to approve.</p>
        </div>
    <?php else: ?>
        <?php foreach ($pending_feedback as $feedback): ?>
            <div class="fb-card">
                <div class="fb-card-accent pending"></div>
                <div class="fb-card-body">
                    
                    <div class="fb-avatar"><?= mb_substr($feedback['name'], 0, 1) ?></div>

                    
                    <div class="fb-content">
                        <div class="fb-top">
                            <div class="fb-name">
                                <?= htmlspecialchars($feedback['name']) ?>
                                <?php if ($feedback['would_recommend'] == 1): ?>
                                    <span class="recommend-badge"><i class="fa-solid fa-thumbs-up" style="font-size:10px;"></i> Recommends</span>
                                <?php endif; ?>
                            </div>
                            <span class="status-badge status-pending"><i class="fa-solid fa-clock" style="font-size:10px;"></i> Pending</span>
                        </div>

                        <div class="stars">
                            <?php
                            $rating = floatval($feedback['rating']);
                            for ($i = 1; $i <= 5; $i++):
                                if ($i <= floor($rating)): ?><i class="fas fa-star"></i>
                                <?php elseif ($i == ceil($rating) && ($rating - floor($rating)) >= 0.5): ?><i class="fas fa-star-half-alt"></i>
                                <?php else: ?><i class="far fa-star"></i>
                                <?php endif;
                            endfor; ?>
                            <span class="rating-val"><?= $rating ?>/5</span>
                        </div>

                        <div class="fb-comment">"<?= htmlspecialchars($feedback['comment']) ?>"</div>

                        <?php if (!empty($feedback['vehicle']) || !empty($feedback['tint_type'])): ?>
                        <div class="fb-tags">
                            <?php if (!empty($feedback['vehicle'])): ?>
                                <span class="fb-tag"><i class="fa-solid fa-car" style="font-size:10px;"></i> <?= htmlspecialchars($feedback['vehicle']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($feedback['tint_type'])): ?>
                                <span class="fb-tag"><i class="fa-solid fa-droplet" style="font-size:10px;"></i> <?= htmlspecialchars($feedback['tint_type']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="fb-meta">
                            <span><i class="fa-regular fa-envelope"></i> <?= htmlspecialchars($feedback['email']) ?></span>
                            <span><i class="fa-regular fa-calendar"></i> <?= date('M j, Y g:i A', strtotime($feedback['created_at'])) ?></span>
                        </div>
                    </div>

                    
                    <div class="fb-actions">
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $feedback['id'] ?>">
                            <button type="button" name="approve" class="act-btn act-approve"
                                    onclick="var f=this.closest('form');rgConfirm({title:'Approve Feedback',message:'Approve this feedback? It will be shown publicly.',confirmText:'Approve',type:'info',onConfirm:function(){var h=document.createElement('input');h.type='hidden';h.name='approve';h.value='1';f.appendChild(h);f.submit();}})">
                                <i class="fa-solid fa-check" style="font-size:11px;"></i> Approve
                            </button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $feedback['id'] ?>">
                            <button type="button" name="reject" class="act-btn act-reject"
                                    onclick="var f=this.closest('form');rgConfirm({title:'Reject Feedback',message:'Reject and delete this feedback?',confirmText:'Reject',type:'danger',onConfirm:function(){var h=document.createElement('input');h.type='hidden';h.name='reject';h.value='1';f.appendChild(h);f.submit();}})">
                                <i class="fa-solid fa-xmark" style="font-size:11px;"></i> Reject
                            </button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $feedback['id'] ?>">
                            <button type="button" name="delete_approved" class="act-btn act-delete"
                                    onclick="var f=this.closest('form');rgConfirm({title:'Delete Feedback',message:'Permanently delete this feedback?',confirmText:'Delete',type:'danger',onConfirm:function(){var h=document.createElement('input');h.type='hidden';h.name='delete_approved';h.value='1';f.appendChild(h);f.submit();}})">
                                <i class="fa-solid fa-trash-can" style="font-size:11px;"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    
    <div class="section-hdr" style="margin-top:32px;">
        <div class="section-hdr-icon icon-green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="section-hdr-title">Approved Reviews</div>
        <span class="section-hdr-count status-approved"><?= count($approved_feedback) ?></span>
        <div class="section-hdr-line"></div>
    </div>

    <?php if (empty($approved_feedback)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-star"></i>
            <p>No approved feedback yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($approved_feedback as $feedback): ?>
            <div class="fb-card">
                <div class="fb-card-accent approved"></div>
                <div class="fb-card-body">
                    
                    <div class="fb-avatar" style="background:linear-gradient(135deg,var(--success),#34d399);">
                        <?= mb_substr($feedback['name'], 0, 1) ?>
                    </div>

                    
                    <div class="fb-content">
                        <div class="fb-top">
                            <div class="fb-name">
                                <?= htmlspecialchars($feedback['name']) ?>
                                <?php if ($feedback['would_recommend'] == 1): ?>
                                    <span class="recommend-badge"><i class="fa-solid fa-thumbs-up" style="font-size:10px;"></i> Recommends</span>
                                <?php endif; ?>
                            </div>
                            <span class="status-badge status-approved"><i class="fa-solid fa-check" style="font-size:10px;"></i> Approved</span>
                        </div>

                        <div class="stars">
                            <?php
                            $rating = floatval($feedback['rating']);
                            for ($i = 1; $i <= 5; $i++):
                                if ($i <= floor($rating)): ?><i class="fas fa-star"></i>
                                <?php elseif ($i == ceil($rating) && ($rating - floor($rating)) >= 0.5): ?><i class="fas fa-star-half-alt"></i>
                                <?php else: ?><i class="far fa-star"></i>
                                <?php endif;
                            endfor; ?>
                            <span class="rating-val"><?= $rating ?>/5</span>
                        </div>

                        <div class="fb-comment">"<?= htmlspecialchars($feedback['comment']) ?>"</div>

                        <?php if (!empty($feedback['vehicle']) || !empty($feedback['tint_type'])): ?>
                        <div class="fb-tags">
                            <?php if (!empty($feedback['vehicle'])): ?>
                                <span class="fb-tag"><i class="fa-solid fa-car" style="font-size:10px;"></i> <?= htmlspecialchars($feedback['vehicle']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($feedback['tint_type'])): ?>
                                <span class="fb-tag"><i class="fa-solid fa-droplet" style="font-size:10px;"></i> <?= htmlspecialchars($feedback['tint_type']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="fb-meta">
                            <span><i class="fa-regular fa-envelope"></i> <?= htmlspecialchars($feedback['email']) ?></span>
                            <span><i class="fa-regular fa-calendar"></i> <?= date('M j, Y g:i A', strtotime($feedback['created_at'])) ?></span>
                        </div>
                    </div>

                    
                    <div class="fb-actions">
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $feedback['id'] ?>">
                            <button type="button" name="delete_approved" class="act-btn act-delete"
                                    onclick="var f=this.closest('form');rgConfirm({title:'Delete Feedback',message:'Permanently delete this approved feedback? It will be removed from the public site.',confirmText:'Delete',type:'danger',onConfirm:function(){var h=document.createElement('input');h.type='hidden';h.name='delete_approved';h.value='1';f.appendChild(h);f.submit();}})">
                                <i class="fa-solid fa-trash-can" style="font-size:11px;"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<div class="rg-toast-wrap" id="rgToastWrap"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin.js?v=<?= filemtime(__DIR__.'/../assets/js/admin.js') ?>"></script>
</body>
</html>
