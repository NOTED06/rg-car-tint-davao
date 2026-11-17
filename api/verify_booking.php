<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

$booking_ref = isset($_GET['ref']) ? trim($_GET['ref']) : '';

if (empty($booking_ref)) {
    header("Location: index.php");
    exit;
}

try {
    
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            vt.type_name as vehicle_type_name,
            tp.name as package_name,
            tp.description as package_description,
            u.full_name,
            u.email
        FROM bookings b
        LEFT JOIN vehicle_types vt ON b.vehicle_type_id = vt.id
        LEFT JOIN tint_packages tp ON b.package_id = tp.id
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.booking_ref = ?
    ");
    
    $stmt->execute([$booking_ref]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        $error = "Booking not found";
    }
    
} catch (PDOException $e) {
    error_log("Verification error: " . $e->getMessage());
    $error = "Database error occurred";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Booking | RG CARTINTWORKS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #FFC107;
            --primary-dark: #FF9800;
            --secondary: #2c3e50;
            --success: #28a745;
            --danger: #dc3545;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .verification-card {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: slideUp 0.6s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .verification-header {
            background: linear-gradient(135deg, #2c3e50 0%, #1a2634 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-bottom: 3px solid var(--primary);
        }
        
        .verification-body {
            padding: 30px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-verified {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .detail-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        
        .detail-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 3px;
        }
        
        .detail-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--secondary);
        }
        
        .btn-verify {
            background: var(--primary);
            color: var(--secondary);
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
            color: var(--secondary);
        }
    </style>
</head>
<body>
    <div class="verification-card">
        <div class="verification-header">
            <i class="fas fa-shield-alt fa-3x mb-3" style="color: var(--primary);"></i>
            <h2 class="fw-bold">Booking Verification</h2>
            <p class="mb-0">RG CARTINTWORKS Official Verification</p>
        </div>
        
        <div class="verification-body">
            <?php if (isset($error)): ?>
                <div class="text-center">
                    <div class="status-badge status-error mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i> Verification Failed
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Error</div>
                        <div class="detail-value"><?= htmlspecialchars($error) ?></div>
                    </div>
                    <p class="text-muted mt-3">
                        The booking reference you're trying to verify could not be found.
                    </p>
                    <a href="index.php" class="btn-verify mt-3">
                        <i class="fas fa-home me-2"></i> Return to Home
                    </a>
                </div>
            <?php else: ?>
                <div class="text-center mb-4">
                    <div class="status-badge status-verified">
                        <i class="fas fa-check-circle me-2"></i> Verified Booking
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Booking Reference</div>
                    <div class="detail-value">#<?= htmlspecialchars($booking['booking_ref']) ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Customer Name</div>
                    <div class="detail-value"><?= htmlspecialchars($booking['customer_name']) ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Appointment Date & Time</div>
                    <div class="detail-value">
                        <?= date('F j, Y', strtotime($booking['appointment_date'])) ?> at 
                        <?= htmlspecialchars($booking['appointment_time'] ?: '—') ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Vehicle</div>
                    <div class="detail-value">
                        <?= htmlspecialchars($booking['vehicle_model']) ?> 
                        (<?= htmlspecialchars($booking['vehicle_type_name'] ?? 'Standard') ?>)
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Package</div>
                    <div class="detail-value"><?= htmlspecialchars($booking['package_name'] ?? 'Standard Package') ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Booking Status</div>
                    <div class="detail-value">
                        <span class="badge" style="background: <?php 
                            switch($booking['status']) {
                                case 'Pending': echo '#fff3cd'; break;
                                case 'Confirmed': echo '#d4edda'; break;
                                case 'Processing': echo '#cce5ff'; break;
                                case 'Completed': echo '#d1e7dd'; break;
                                case 'Cancelled': echo '#f8d7da'; break;
                                default: echo '#e9ecef';
                            }
                        ?>; color: <?php 
                            switch($booking['status']) {
                                case 'Pending': echo '#856404'; break;
                                case 'Confirmed': echo '#155724'; break;
                                case 'Processing': echo '#004085'; break;
                                case 'Completed': echo '#0a3622'; break;
                                case 'Cancelled': echo '#721c24'; break;
                                default: echo '#6c757d';
                            }
                        ?>;">
                            <?= $booking['status'] ?>
                        </span>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Total Amount</div>
                    <div class="detail-value">₱<?= number_format($booking['total_price'], 2) ?></div>
                </div>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <p class="text-muted small mb-3">
                        <i class="fas fa-clock me-1" style="color: var(--primary);"></i>
                        Booking created on <?= date('F j, Y', strtotime($booking['created_at'])) ?>
                    </p>
                    
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $booking['user_id']): ?>
                        <a href="../customer/my_bookings.php" class="btn-verify me-2">
                            <i class="fas fa-calendar-alt me-2"></i> My Bookings
                        </a>
                    <?php endif; ?>
                    
                    <a href="index.php" class="btn-verify" style="background: var(--secondary); color: white;">
                        <i class="fas fa-home me-2"></i> Home
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
