<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

$is_json_req = (isset($_GET['format']) && $_GET['format'] === 'json') || isset($_GET['json']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

if (!isset($_SESSION['user_id'])) {
    if ($is_json_req) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Please log in to view booking details']);
        exit;
    }
    echo '<div class="alert alert-danger">Please log in to view booking details</div>';
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$booking_id) {
    if ($is_json_req) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid booking ID']);
        exit;
    }
    echo '<div class="alert alert-danger">Invalid booking ID</div>';
    exit;
}

try {
    
    $table = null;
    $checkTbl = $pdo->query("SHOW TABLES LIKE 'bookings'");
    if ($checkTbl->rowCount() > 0) {
        $table = 'bookings';
    } else {
        $checkTbl = $pdo->query("SHOW TABLES LIKE 'appointments'");
        if ($checkTbl->rowCount() > 0) {
            $table = 'appointments';
        }
    }
    
    if (!$table) {
        echo '<div class="alert alert-danger">No bookings table found</div>';
        exit;
    }
    
    
    $stmt = $pdo->prepare("
        SELECT b.*, v.type_name 
        FROM {$table} b 
        LEFT JOIN vehicle_types v ON b.vehicle_type_id = v.id 
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $is_json_req = (isset($_GET['format']) && $_GET['format'] === 'json') || isset($_GET['json']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if (!$booking) {
        if ($is_json_req) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Booking not found or permission denied']);
            exit;
        }
        echo '<div class="alert alert-danger">Booking not found or you don\'t have permission to view it</div>';
        exit;
    }
    
    if ($is_json_req) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'booking' => $booking]);
        exit;
    }
    
    
    $package_names = [
        1 => 'HD Carbon Ceramic Tint - Black Series',
        2 => 'Magic Chrome Ceramic Tint',
        3 => 'Nano Ceramic Tint - Black Series',
        4 => 'Nano Ceramic Tint - Clear Bluish',
        5 => 'Standard Carbon Tint',
        6 => 'Premium Security Tint'
    ];
    $package_name = $package_names[$booking['package_id']] ?? 'Custom Package';
    
    
    $status_class = '';
    $status_icon = '';
    switch(strtolower($booking['status'] ?? 'pending')) {
        case 'pending':
            $status_class = 'status-pending';
            $status_icon = 'fa-hourglass-half';
            break;
        case 'approved':
            $status_class = 'status-approved';
            $status_icon = 'fa-check-circle';
            break;
        case 'completed':
            $status_class = 'status-completed';
            $status_icon = 'fa-circle-check';
            break;
        case 'cancelled':
        case 'canceled':
            $status_class = 'status-cancelled';
            $status_icon = 'fa-ban';
            break;
        case 'declined':
            $status_class = 'status-declined';
            $status_icon = 'fa-times-circle';
            break;
        default:
            $status_class = 'status-pending';
            $status_icon = 'fa-clock';
    }
    ?>
    
    <style>
        .bd{padding:0;font-family:'DM Sans',sans-serif;}
        .bd-head{background:#0A0A0A;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;}
        .bd-head .bd-id{background:rgba(212,160,23,.15);color:#D4A017;font-weight:700;font-size:13px;padding:4px 12px;border-radius:99px;font-family:monospace;}
        .bd-head .bd-status{padding:4px 14px;border-radius:99px;font-size:12px;font-weight:600;}
        .bd-status.status-pending{background:#fef3c7;color:#92400e;}
        .bd-status.status-approved{background:#d1fae5;color:#065f46;}
        .bd-status.status-completed{background:#cffafe;color:#155e75;}
        .bd-status.status-cancelled,.bd-status.status-declined{background:#fee2e2;color:#991b1b;}
        .bd-body{padding:16px 20px;}
        .bd-sect{margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #f0ebe0;}
        .bd-sect:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0;}
        .bd-sect-title{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#0A0A0A;margin-bottom:10px;display:flex;align-items:center;gap:8px;}
        .bd-sect-title i{color:#D4A017;font-size:13px;}
        .bd-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 20px;}
        .bd-lbl{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#94a3b8;margin-bottom:2px;}
        .bd-val{font-size:13px;font-weight:600;color:#0A0A0A;margin-bottom:0;}
        .bd-val.bd-gold{color:#D4A017;}
        .bd-val.bd-price{font-size:16px;font-weight:800;color:#059669;}
        .bd-pill{display:inline-flex;align-items:center;gap:4px;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:600;}
        .bd-pill-warn{background:#fef3c7;color:#92400e;}
        .bd-pill-ok{background:#d1fae5;color:#065f46;}
        .bd-reject{background:#fee2e2;border-left:3px solid #dc2626;padding:10px 14px;border-radius:8px;margin-top:10px;font-size:12px;}
        .bd-foot{font-size:11px;color:#94a3b8;padding:10px 20px;border-top:1px solid #f0ebe0;background:#fafaf8;display:flex;justify-content:space-between;align-items:center;}
        .bd-foot i{color:#D4A017;margin-right:4px;}
        @media(max-width:576px){.bd-grid{grid-template-columns:1fr;}}
    </style>
    
    <div class="bd">
        
        <div class="bd-head">
            <span class="bd-id">#<?= $booking['id'] ?></span>
            <span class="bd-status <?= $status_class ?>"><i class="fas <?= $status_icon ?> me-1"></i><?= ucfirst($booking['status'] ?? 'Pending') ?></span>
        </div>

        <div class="bd-body">
            
            <div class="bd-sect">
                <div class="bd-sect-title"><i class="fas fa-user"></i>Customer</div>
                <div class="bd-grid">
                    <div><div class="bd-lbl">Name</div><div class="bd-val"><?= htmlspecialchars($booking['customer_name'] ?? 'N/A') ?></div></div>
                    <div><div class="bd-lbl">Phone</div><div class="bd-val"><?= htmlspecialchars($booking['phone'] ?? 'Not provided') ?></div></div>
                    <div><div class="bd-lbl">Email</div><div class="bd-val"><?= htmlspecialchars($booking['email'] ?? 'Not provided') ?></div></div>
                    <div><div class="bd-lbl">Address</div><div class="bd-val"><?= htmlspecialchars($booking['address'] ?? 'Not provided') ?></div></div>
                </div>
            </div>
            
            
            <div class="bd-sect">
                <div class="bd-sect-title"><i class="fas fa-car"></i>Vehicle</div>
                <div class="bd-grid">
                    <div><div class="bd-lbl">Model</div><div class="bd-val"><?= htmlspecialchars($booking['vehicle_model'] ?? 'N/A') ?></div></div>
                    <div><div class="bd-lbl">Type</div><div class="bd-val"><?= htmlspecialchars($booking['type_name'] ?? 'Car') ?></div></div>
                    <?php if(!empty($booking['vehicle_year'])): ?>
                    <div><div class="bd-lbl">Year</div><div class="bd-val"><?= $booking['vehicle_year'] ?></div></div>
                    <?php endif; ?>
                    <?php if(!empty($booking['vehicle_color'])): ?>
                    <div><div class="bd-lbl">Color</div><div class="bd-val"><?= htmlspecialchars($booking['vehicle_color']) ?></div></div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="bd-sect">
                <div class="bd-sect-title"><i class="fas fa-film"></i>Package</div>
                <div class="bd-grid">
                    <div style="grid-column:1/-1"><div class="bd-lbl">Package</div><div class="bd-val bd-gold"><?= htmlspecialchars($package_name) ?></div></div>
                    <?php if(!empty($booking['tint_type'])): ?>
                    <div><div class="bd-lbl">Tint Type</div><div class="bd-val"><?= ucfirst($booking['tint_type']) ?></div></div>
                    <?php endif; ?>
                    <?php if(!empty($booking['shade_percentage'])): ?>
                    <div><div class="bd-lbl">Shade</div><div class="bd-val"><?= $booking['shade_percentage'] ?>%</div></div>
                    <?php endif; ?>
                    <?php if(!empty($booking['warranty_years'])): ?>
                    <div><div class="bd-lbl">Warranty</div><div class="bd-val"><?= $booking['warranty_years'] ?> Years</div></div>
                    <?php endif; ?>
                    <?php if(!empty($booking['installation_time'])): ?>
                    <div><div class="bd-lbl">Installation</div><div class="bd-val"><?= $booking['installation_time'] ?></div></div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="bd-sect">
                <div class="bd-sect-title"><i class="fas fa-calendar-alt"></i>Appointment & Payment</div>
                <div class="bd-grid">
                    <div><div class="bd-lbl">Date</div><div class="bd-val"><?= date('M d, Y', strtotime($booking['appointment_date'] ?? $booking['created_at'])) ?></div></div>
                    <div><div class="bd-lbl">Time</div><div class="bd-val"><?= htmlspecialchars($booking['appointment_time'] ?: '—') ?></div></div>
                    <div><div class="bd-lbl">Method</div><div class="bd-val"><?= htmlspecialchars($booking['payment_method'] ?? 'Cash') ?></div></div>
                    <div>
                        <div class="bd-lbl">Payment</div>
                        <div class="bd-val">
                            <?php if(($booking['payment_status'] ?? 'Pending') == 'Paid'): ?>
                                <span class="bd-pill bd-pill-ok"><i class="fas fa-check-circle"></i>Paid</span>
                            <?php else: ?>
                                <span class="bd-pill bd-pill-warn"><i class="fas fa-clock"></i><?= htmlspecialchars($booking['payment_status'] ?? 'Pending') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div><div class="bd-lbl">Total</div><div class="bd-val bd-price">₱<?= number_format($booking['total_price'] ?? 0, 2) ?></div></div>
                    <?php if(!empty($booking['payment_reference'])): ?>
                    <div><div class="bd-lbl">Reference</div><div class="bd-val" style="font-family:monospace;font-size:12px;color:#D4A017;"><?= htmlspecialchars($booking['payment_reference']) ?></div></div>
                    <?php endif; ?>
                </div>
            </div>

            
            <?php if(!empty($booking['notes'])): ?>
            <div class="bd-sect">
                <div class="bd-sect-title"><i class="fas fa-sticky-note"></i>Notes</div>
                <div style="font-size:12px;color:#64748b;background:#fafaf8;padding:10px 14px;border-radius:8px;border:1px solid #f0ebe0;"><?= nl2br(htmlspecialchars($booking['notes'])) ?></div>
            </div>
            <?php endif; ?>

            
            <?php if(strtolower($booking['status'] ?? '') == 'declined' && !empty($booking['rejection_reason'])): ?>
            <div class="bd-reject">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;"><i class="fas fa-times-circle" style="color:#dc2626;"></i><strong style="color:#991b1b;">Declined</strong></div>
                <div style="color:#7f1d1d;"><?= htmlspecialchars($booking['rejection_reason']) ?></div>
                <?php if(!empty($booking['rejected_by'])): ?><div style="margin-top:6px;font-size:11px;color:#94a3b8;">Declined by: Admin</div><?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if(in_array(strtolower($booking['status'] ?? ''), ['cancelled', 'canceled']) && !empty($booking['rejection_reason'])): ?>
            <div class="bd-reject" style="background:#fef3c7;border-left-color:#f59e0b;">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;"><i class="fas fa-exclamation-triangle" style="color:#f59e0b;"></i><strong style="color:#92400e;">Cancelled</strong></div>
                <div style="color:#78350f;"><?= htmlspecialchars($booking['rejection_reason']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="bd-foot">
            <span><i class="far fa-clock"></i>Booked on <?= date('M d, Y \a\t h:i A', strtotime($booking['created_at'] ?? 'now')) ?></span>
            <a href="print_receipt.php?id=<?= $booking['id'] ?>" target="_blank" class="btn btn-sm btn-outline-warning fw-bold py-1 px-3" style="font-size: 11px; border-radius: 6px; text-decoration: none;">
                <i class="fas fa-print me-1"></i>Print Receipt
            </a>
        </div>
    </div>
    
    <?php
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error loading booking details: ' . htmlspecialchars($e->getMessage()) . '</div>';
    error_log("Booking details error: " . $e->getMessage());
}
?>
