<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require_customer_auth('../login.php');

$user_id    = (int)$_SESSION['user_id'];
$booking_id = (int)($_GET['id'] ?? 0);

try {
    $stmt = $pdo->prepare("
        SELECT b.*, vt.type_name, tp.name AS package_name
        FROM bookings b
        LEFT JOIN vehicle_types vt ON b.vehicle_type_id = vt.id
        LEFT JOIN tint_packages tp ON b.package_id = tp.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $booking = null; }

if (!$booking) { header("Location: my_bookings.php"); exit; }

$ps = $booking['payment_status'] ?? '';
$st = $booking['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Status — <?= htmlspecialchars($booking['booking_ref']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:opsz,wght@9..40,400;9..40,600;9..40,700;9..40,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/customer.css?v=<?= time() ?>">
<style>
body{background:#F5F3EF;font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;}
.status-wrap{max-width:520px;width:100%;margin:32px auto;padding:0 16px;}
.status-card{background:#fff;border:1.5px solid #E6E2DA;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.07);}
.status-head{padding:40px 32px;text-align:center;}
.status-head.submitted{background:linear-gradient(135deg,#FFF8DC,#FEF3C7);}
.status-head.confirmed{background:linear-gradient(135deg,#F0FDF4,#DCFCE7);}
.status-head.rejected {background:linear-gradient(135deg,#FEF2F2,#FEE2E2);}
.status-head.cancelled{background:linear-gradient(135deg,#F8FAFC,#F1F5F9);}
.status-icon{font-size:3.5rem;margin-bottom:16px;}
.status-head h2{font-family:'Bebas Neue',sans-serif;font-size:1.8rem;letter-spacing:.04em;margin-bottom:6px;}
.status-head p{font-size:14px;color:#475569;margin:0;}
.status-body{padding:28px 32px;}
.info-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #E6E2DA;font-size:13.5px;}
.info-row:last-child{border:none;}
.info-lbl{color:#64748b;}
.info-val{font-weight:600;color:#0A0A0A;}
.btn-primary-dark{display:flex;align-items:center;justify-content:center;gap:8px;background:#0A0A0A;color:#fff;padding:14px;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px;transition:all .2s;margin-top:20px;}
.btn-primary-dark:hover{background:#1a1a1a;color:#fff;}
.btn-outline-dark{display:flex;align-items:center;justify-content:center;gap:8px;background:transparent;color:#0A0A0A;border:1.5px solid #E6E2DA;padding:12px;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;transition:all .2s;margin-top:10px;}
.btn-outline-dark:hover{border-color:#D4A017;color:#D4A017;}
</style>
</head>
<body>
<div class="status-wrap">
<?php
$head_class = 'submitted';
$icon_html  = '<span style="color:#D4A017;"><i class="fas fa-hourglass-half"></i></span>';
$title      = 'Proof Submitted';
$subtitle   = 'Your payment proof is under review. We\'ll notify you once verified (usually within 30 minutes).';

if ($st === 'Confirmed' || $ps === 'Verified') {
    $head_class = 'confirmed';
    $icon_html  = '<span style="color:#16a34a;"><i class="fas fa-circle-check"></i></span>';
    $title      = 'Booking Confirmed!';
    $subtitle   = 'Your payment has been verified and your appointment is locked in.';
} elseif ($ps === 'Rejected') {
    $head_class = 'rejected';
    $icon_html  = '<span style="color:#dc2626;"><i class="fas fa-circle-xmark"></i></span>';
    $title      = 'Payment Rejected';
    $subtitle   = 'Your proof was rejected. Please check the reason and re-submit.';
} elseif ($st === 'Cancelled') {
    $head_class = 'cancelled';
    $icon_html  = '<span style="color:#94a3b8;"><i class="fas fa-ban"></i></span>';
    $title      = 'Booking Cancelled';
    $subtitle   = 'This booking has been cancelled.';
}
?>
    <div class="status-card">
        <div class="status-head <?= $head_class ?>">
            <div class="status-icon"><?= $icon_html ?></div>
            <h2><?= $title ?></h2>
            <p><?= $subtitle ?></p>
        </div>
        <div class="status-body">
            <div class="info-row"><span class="info-lbl">Reference #</span><span class="info-val"><?= htmlspecialchars($booking['booking_ref']) ?></span></div>
            <div class="info-row"><span class="info-lbl">Package</span><span class="info-val"><?= htmlspecialchars($booking['package_name'] ?? '') ?></span></div>
            <div class="info-row"><span class="info-lbl">Appointment</span><span class="info-val"><?= date('M d, Y', strtotime($booking['appointment_date'])) . ' ' . htmlspecialchars($booking['appointment_time'] ?: '') ?></span></div>
            <div class="info-row"><span class="info-lbl">Total Amount</span><span class="info-val">₱<?= number_format((float)$booking['total_price'], 0) ?></span></div>
            <div class="info-row"><span class="info-lbl">Payment Method</span><span class="info-val"><?= htmlspecialchars(ucfirst($booking['payment_method'] ?? '')) ?></span></div>
            <div class="info-row"><span class="info-lbl">Payment Type</span><span class="info-val"><?= ($booking['payment_type'] === 'downpayment') ? 'Deposit (10%)' : 'Full Payment' ?></span></div>
            <?php if(!empty($booking['payment_reference'])): ?>
            <div class="info-row"><span class="info-lbl">Ref Number</span><span class="info-val"><?= htmlspecialchars($booking['payment_reference']) ?></span></div>
            <?php endif; ?>
            <div class="info-row"><span class="info-lbl">Payment Status</span><span class="info-val"><?= htmlspecialchars($ps) ?></span></div>
            <?php if($ps === 'Rejected' && !empty($booking['rejection_reason'])): ?>
            <div style="background:#FEF2F2;border:1px solid #FCA5A5;border-radius:8px;padding:12px 14px;margin-top:12px;font-size:13px;color:#991b1b;">
                <strong>Rejection Reason:</strong> <?= htmlspecialchars($booking['rejection_reason']) ?>
            </div>
            <?php endif; ?>

            <?php if($ps === 'Rejected' || $st === 'Cancelled'): ?>
            <a href="book.php" class="btn-primary-dark"><i class="fas fa-calendar-plus"></i>Book Again</a>
            <?php else: ?>
            <a href="my_bookings.php" class="btn-primary-dark"><i class="fas fa-calendar-alt"></i>View My Bookings</a>
            <?php endif; ?>
            <a href="dashboard.php" class="btn-outline-dark"><i class="fas fa-gauge-high"></i>Back to Dashboard</a>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/chatbot.php'; ?>
</body>
</html>
