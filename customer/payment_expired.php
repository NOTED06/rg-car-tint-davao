<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require_customer_auth('../login.php');

$user_id    = (int)$_SESSION['user_id'];
$booking_id = (int)($_GET['id'] ?? 0);
$booking    = null;

if ($booking_id > 0) {
    $stmt = $pdo->prepare("SELECT b.*, tp.name AS package_name, vt.type_name
        FROM bookings b
        LEFT JOIN tint_packages tp ON b.package_id = tp.id
        LEFT JOIN vehicle_types vt ON b.vehicle_type_id = vt.id
        WHERE b.id = ? AND b.user_id = ? AND b.status = 'Cancelled'");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Window Expired</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:opsz,wght@9..40,400;9..40,600;9..40,700;9..40,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/customer.css?v=<?= time() ?>">
<style>
.exp-page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:32px 16px;background:var(--smoke,#F5F3EF);}
.exp-card{background:#fff;border:1.5px solid var(--border,#E6E2DA);border-radius:16px;max-width:500px;width:100%;padding:48px 40px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.06);}
.exp-icon{width:80px;height:80px;background:#FEF3C7;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:2rem;color:#D97706;margin-bottom:24px;}
.exp-title{font-size:1.5rem;font-weight:800;color:#0A0A0A;margin-bottom:8px;}
.exp-subtitle{color:#64748b;font-size:14px;margin-bottom:28px;line-height:1.6;}
.info-pill{background:#F5F3EF;border-radius:10px;padding:14px 18px;text-align:left;margin-bottom:20px;font-size:13px;}
.info-pill .label{color:#94a3b8;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:2px;}
.info-pill .value{font-weight:700;color:#0A0A0A;}
.btn-rebook{display:inline-flex;align-items:center;gap:8px;background:#D4A017;color:#0A0A0A;padding:13px 28px;border-radius:10px;text-decoration:none;font-weight:800;font-size:14px;margin-bottom:12px;width:100%;justify-content:center;transition:all .2s;}
.btn-rebook:hover{background:#c49010;color:#0A0A0A;}
.btn-secondary-link{display:inline-flex;align-items:center;gap:8px;color:#64748b;padding:11px 28px;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;width:100%;justify-content:center;border:1.5px solid #E6E2DA;transition:all .2s;}
.btn-secondary-link:hover{border-color:#D4A017;color:#D4A017;}
</style>
</head>
<body>
<div class="exp-page">
    <div class="exp-card">
        <div class="exp-icon"><i class="fas fa-clock"></i></div>
        <div class="exp-title">Payment Window Expired</div>
        <div class="exp-subtitle">
            Your 20-minute payment window has passed without a proof upload.<br>
            Your reserved slot has been released.
        </div>

        <?php if ($booking): ?>
        <div class="info-pill">
            <div class="label">Cancelled Booking</div>
            <div class="value"><?= htmlspecialchars($booking['booking_ref']) ?></div>
        </div>
        <div class="info-pill" style="display:flex;gap:24px;">
            <div>
                <div class="label">Package</div>
                <div class="value"><?= htmlspecialchars($booking['package_name'] ?? '—') ?></div>
            </div>
            <div>
                <div class="label">Vehicle</div>
                <div class="value"><?= htmlspecialchars($booking['type_name'] ?? '—') ?> — <?= htmlspecialchars($booking['vehicle_model'] ?? '') ?></div>
            </div>
        </div>
        <?php endif; ?>

        <div style="background:#FEF9EC;border:1px solid #FDE68A;border-radius:10px;padding:14px 16px;font-size:13px;color:#92400e;margin-bottom:24px;text-align:left;">
            <i class="fas fa-lightbulb me-2"></i><strong>Tip:</strong> Next time, have your GCash / Maya app ready before booking so you can upload your screenshot within the 20-minute window.
        </div>

        <a href="dashboard.php?open_booking=1<?= $booking ? '&package_id=' . $booking['package_id'] . '&car_model=' . urlencode($booking['car_model']) : '' ?>" class="btn-rebook"><i class="fas fa-calendar-plus"></i>Book Again</a>
        <a href="my_bookings.php" class="btn-secondary-link"><i class="fas fa-list-ul"></i>View My Bookings</a>
    </div>
</div>
</body>
</html>
