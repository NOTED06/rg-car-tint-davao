<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require_customer_auth('../login.php');

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$receipt_data = [];

if ($booking_id <= 0) {
    header("Location: my_bookings.php");
    exit;
}

try {
    
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            vt.type_name as vehicle_type_name,
            vt.price_category,
            tp.name as package_name,
            tp.description as package_description,
            tp.sedan_price,
            tp.suv_price,
            tp.van_price,
            u.email,
            u.created_at as member_since
        FROM bookings b
        LEFT JOIN vehicle_types vt ON b.vehicle_type_id = vt.id
        LEFT JOIN tint_packages tp ON b.package_id = tp.id
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        header("Location: my_bookings.php");
        exit;
    }
    
    
    $customizations = [];
    if (!empty($booking['customization_data'])) {
        $customizations = json_decode($booking['customization_data'], true);
    }
    
    
    $base_price = 0;
    $vehicle_category = $booking['price_category'] ?? 'sedan';
    
    switch($vehicle_category) {
        case 'suv':
            $base_price = $booking['suv_price'] ?? $booking['sedan_price'] ?? 0;
            break;
        case 'van':
            $base_price = $booking['van_price'] ?? $booking['sedan_price'] ?? 0;
            break;
        default:
            $base_price = $booking['sedan_price'] ?? 0;
            break;
    }
    
    
    $customization_total = 0;
    if (isset($customizations['windows']) && is_array($customizations['windows'])) {
        foreach ($customizations['windows'] as $window) {
            if (isset($window['price'])) {
                $customization_total += floatval($window['price']);
            }
        }
    }
    
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base_url = $protocol . '://' . $host;
    $verify_url = $base_url . '/verify_booking.php?ref=' . urlencode($booking['booking_ref']);
    
    $receipt_data = [
        'id' => $booking['id'],
        'ref' => $booking['booking_ref'],
        'name' => $booking['customer_name'],
        'phone' => $booking['phone'],
        'email' => $booking['email'] ?? 'N/A',
        'address' => $booking['address'],
        'vehicle_model' => $booking['vehicle_model'],
        'vehicle_type' => $booking['vehicle_type_name'] ?? 'Standard Vehicle',
        'package_name' => $booking['package_name'] ?? 'Standard Package',
        'package_description' => $booking['package_description'] ?? 'Professional tint service',
        'appointment_date' => $booking['appointment_date'],
        'appointment_formatted' => date('F j, Y', strtotime($booking['appointment_date'])),
        'appointment_time' => $booking['appointment_time'] ?: '—',
        'notes' => $booking['notes'] ?? '',
        'status' => $booking['status'] ?? 'Pending',
        'created_at' => date('F j, Y h:i A', strtotime($booking['created_at'])),
        'member_since' => date('Y', strtotime($booking['member_since'] ?? $booking['created_at'])),
        'total' => floatval($booking['total_price']),
        'base_price' => $base_price,
        'customization_total' => $customization_total,
        'customizations' => $customizations,
        'vehicle_category' => $vehicle_category,
        'verify_url' => $verify_url
    ];
    
} catch (PDOException $e) {
    error_log("Receipt fetch error: " . $e->getMessage());
    header("Location: my_bookings.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Receipt #<?= htmlspecialchars($receipt_data['ref']) ?> | RG CARTINTWORKS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    
    <style>
        
        :root {
            --primary: #FFC107;
            --primary-dark: #FF9800;
            --secondary: #2c3e50;
            --accent: #3498db;
            --success: #28a745;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #1a1a1a;
            
            --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --gradient-dark: linear-gradient(135deg, var(--dark) 0%, var(--secondary) 100%);
            --gradient-success: linear-gradient(135deg, var(--success) 0%, #20c997 100%);
            --gradient-blue: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-ocean: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
            
            --shadow-sm: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-md: 0 8px 30px rgba(0,0,0,0.12);
            --shadow-lg: 0 15px 50px rgba(0,0,0,0.2);
            --shadow-xl: 0 25px 80px rgba(0,0,0,0.3);
        }

        @media print {
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
            body {
                background: white !important;
                font-size: 10pt;
                padding: 0.2in;
            }
            .receipt-container {
                box-shadow: none;
                border: 1px solid #e9ecef;
            }
            .page-break {
                page-break-after: always;
            }
            a {
                text-decoration: none !important;
                color: black !important;
            }
            .badge {
                border: 1px solid #000;
                color: #000 !important;
                background: transparent !important;
            }
            .receipt-header {
                background: #ffffff !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .warranty-card {
                background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .status-pending { background: #fff3cd !important; color: #856404 !important; }
            .status-confirmed { background: #d4edda !important; color: #155724 !important; }
            .status-processing { background: #cce5ff !important; color: #004085 !important; }
            .status-completed { background: #d1e7dd !important; color: #0a3622 !important; }
            .status-cancelled { background: #f8d7da !important; color: #721c24 !important; }
            #qrcode canvas {
                width: 120px !important;
                height: 120px !important;
            }
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .receipt-container {
            max-width: 720px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .receipt-header {
            background: #ffffff;
            color: #0A0A0A;
            padding: 15px 0;
            text-align: center;
            position: relative;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .receipt-logo {
            display: none !important;
        }
        
        .receipt-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 4px;
            color: #0A0A0A;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .receipt-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f1f5f9;
            padding: 5px 12px;
            border-radius: 6px;
            font-weight: 700;
            border: 1px solid #e2e8f0;
            font-size: 0.78rem;
            color: #0A0A0A;
        }
        
        .receipt-body {
            padding: 20px 25px;
        }
        
        .receipt-section {
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 12px;
            padding-left: 12px;
            border-left: 4px solid var(--primary);
            position: relative;
        }

        .section-title::before {
            content: '';
            position: absolute;
            left: -4px;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px 18px;
        }
        
        .detail-item {
            padding: 10px 12px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .detail-item:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-sm);
        }
        
        .detail-label {
            font-size: 0.75rem;
            color: #6c757d;
            margin-bottom: 2px;
        }
        
        .detail-value {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--secondary);
        }
        
        .price-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .price-table tr {
            border-bottom: 1px solid #dee2e6;
        }
        
        .price-table td {
            padding: 8px 0;
        }
        
        .price-table .label {
            color: #6c757d;
        }
        
        .price-table .amount {
            font-weight: 600;
            text-align: right;
        }
        
        .total-row {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--success);
        }
        
        .total-row td {
            padding-top: 10px;
            border-top: 2px solid var(--success);
        }
        
        .warranty-card {
            background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
            border-left: 4px solid var(--primary);
            padding: 20px;
            border-radius: 12px;
            margin-top: 30px;
            box-shadow: var(--shadow-sm);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn-print {
            background: var(--gradient-dark);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }
        
        .btn-download {
            background: white;
            color: var(--secondary);
            border: 2px solid var(--secondary);
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-download:hover {
            background: var(--secondary);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-back {
            background: white;
            color: var(--secondary);
            border: 2px solid #e9ecef;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-back:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #6c757d;
            font-size: 0.9rem;
            border-top: 1px solid #e9ecef;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 18px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        
        .status-pending { 
            background: #fff3cd; 
            color: #856404; 
            border: 1px solid #ffeeba;
        }
        .status-confirmed { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }
        .status-processing { 
            background: #cce5ff; 
            color: #004085; 
            border: 1px solid #b8daff;
        }
        .status-completed { 
            background: #d1e7dd; 
            color: #0a3622; 
            border: 1px solid #a3cfbb;
        }
        .status-cancelled { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
        }

        .custom-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }

        .custom-table thead {
            background: var(--gradient-primary);
            color: var(--secondary);
        }

        .custom-table th {
            padding: 12px;
            font-weight: 600;
        }

        .custom-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }

        .custom-table tr:last-child td {
            border-bottom: none;
        }

        .qr-container {
            display: inline-block;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 15px;
            border: 1px solid #e9ecef;
            text-align: center;
        }

        #qrcode {
            background: white;
            padding: 12px;
            border-radius: 10px;
            display: inline-block;
            margin-bottom: 10px;
            box-shadow: var(--shadow-sm);
        }

        #qrcode canvas {
            width: 130px;
            height: 130px;
            display: block;
        }

        .qr-text {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 8px;
            word-break: break-all;
            max-width: 200px;
        }

        .qr-verify-badge {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 12px;
            background: rgba(40, 167, 69, 0.1);
            color: var(--success);
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .qr-verify-badge i {
            color: var(--success);
        }
        
        @media (max-width: 768px) {
            .receipt-body {
                padding: 20px;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
            }
            
            .receipt-badge {
                position: static;
                margin-top: 15px;
                display: inline-block;
            }
            
            .action-buttons {
                flex-direction: column;
            }

            .btn-print, .btn-download, .btn-back {
                width: 100%;
                text-align: center;
            }

            #qrcode canvas {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>
<body>

<div class="receipt-container">
    
    <div class="receipt-header">
        <div class="receipt-logo">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div class="receipt-title">RG CARTINTWORKS</div>
        <p style="opacity: 0.9; margin-bottom: 0;">Professional Window Tinting Specialists</p>
        <div class="receipt-badge">
            <i class="fas fa-check-circle me-1" style="color: var(--primary);"></i> Receipt #<?= htmlspecialchars($receipt_data['ref']) ?>
        </div>
    </div>
    
    
    <div class="receipt-body">
        
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <span class="status-badge status-<?= strtolower($receipt_data['status']) ?>">
                    <i class="fas fa-circle me-1"></i> <?= $receipt_data['status'] ?>
                </span>
                <span class="ms-2 text-muted">
                    <i class="fas fa-calendar-alt me-1" style="color: var(--primary);"></i> Booked on <?= $receipt_data['created_at'] ?>
                </span>
            </div>
            <div class="text-end">
                <small class="text-muted d-block">
                    <i class="fas fa-star me-1" style="color: var(--primary);"></i> Member since <?= $receipt_data['member_since'] ?>
                </small>
            </div>
        </div>
        
        
        <div class="receipt-section">
            <h3 class="section-title">
                <i class="fas fa-calendar-check me-2" style="color: var(--primary);"></i> Appointment Details
            </h3>
            
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Appointment Date</div>
                    <div class="detail-value">
                        <i class="fas fa-calendar me-1" style="color: var(--primary);"></i>
                        <?= $receipt_data['appointment_formatted'] ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Appointment Time</div>
                    <div class="detail-value">
                        <i class="fas fa-clock me-1" style="color: var(--primary);"></i>
                        <?= $receipt_data['appointment_time'] ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Estimated Duration</div>
                    <div class="detail-value">
                        <i class="fas fa-hourglass-half me-1" style="color: var(--primary);"></i>
                        <?php 
                        switch($receipt_data['package_name']) {
                            case strpos($receipt_data['package_name'], 'HD Carbon') !== false:
                                echo '2-3 hours';
                                break;
                            case strpos($receipt_data['package_name'], 'Magic Chrome') !== false:
                                echo '3-4 hours';
                                break;
                            case strpos($receipt_data['package_name'], 'Nano Ceramic') !== false:
                                echo '4-5 hours';
                                break;
                            default:
                                echo '2-4 hours';
                        }
                        ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Service Location</div>
                    <div class="detail-value">
                        <i class="fas fa-map-marker-alt me-1" style="color: var(--primary);"></i>
                        Our Shop
                    </div>
                </div>
            </div>
            
            <?php if(!empty($receipt_data['notes'])): ?>
            <div class="mt-3 p-3" style="background: #fff9e6; border-radius: 12px; border-left: 4px solid var(--primary);">
                <div class="detail-label mb-2">
                    <i class="fas fa-sticky-note me-1" style="color: var(--primary);"></i> Special Instructions:
                </div>
                <div class="detail-value"><?= nl2br(htmlspecialchars($receipt_data['notes'])) ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        
        <div class="receipt-section">
            <h3 class="section-title">
                <i class="fas fa-user me-2" style="color: var(--primary);"></i> Customer Information
            </h3>
            
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Full Name</div>
                    <div class="detail-value">
                        <i class="fas fa-user-circle me-1" style="color: var(--primary);"></i>
                        <?= htmlspecialchars($receipt_data['name']) ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Contact Number</div>
                    <div class="detail-value">
                        <i class="fas fa-phone me-1" style="color: var(--primary);"></i>
                        +63<?= htmlspecialchars($receipt_data['phone']) ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Email Address</div>
                    <div class="detail-value">
                        <i class="fas fa-envelope me-1" style="color: var(--primary);"></i>
                        <?= htmlspecialchars($receipt_data['email']) ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Service Address</div>
                    <div class="detail-value">
                        <i class="fas fa-home me-1" style="color: var(--primary);"></i>
                        <?= htmlspecialchars($receipt_data['address']) ?>
                    </div>
                </div>
            </div>
        </div>
        
        
        <div class="receipt-section">
            <h3 class="section-title">
                <i class="fas fa-car me-2" style="color: var(--primary);"></i> Vehicle Information
            </h3>
            
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Car Model</div>
                    <div class="detail-value">
                        <i class="fas fa-car-side me-1" style="color: var(--primary);"></i>
                        <?= htmlspecialchars($receipt_data['vehicle_model']) ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Vehicle Type</div>
                    <div class="detail-value">
                        <i class="fas fa-truck me-1" style="color: var(--primary);"></i>
                        <?= htmlspecialchars($receipt_data['vehicle_type']) ?>
                    </div>
                </div>
            </div>
        </div>
        
        
        <div class="receipt-section">
            <h3 class="section-title">
                <i class="fas fa-box me-2" style="color: var(--primary);"></i> Package Details
            </h3>
            
            <div class="mb-4 p-3" style="background: #f8f9fa; border-radius: 12px; border: 1px solid #e9ecef;">
                <h5 class="fw-bold mb-2" style="color: var(--secondary);">
                    <i class="fas fa-tint me-2" style="color: var(--primary);"></i>
                    <?= htmlspecialchars($receipt_data['package_name']) ?>
                </h5>
                <p class="text-muted mb-0"><?= htmlspecialchars($receipt_data['package_description']) ?></p>
            </div>
            
            
            <?php if(!empty($receipt_data['customizations']) && isset($receipt_data['customizations']['windows'])): ?>
            <div class="mb-4">
                <h6 class="fw-bold mb-3" style="color: var(--secondary);">
                    <i class="fas fa-cog me-2" style="color: var(--primary);"></i> Tint Customization
                </h6>
                <div class="table-responsive">
                    <table class="custom-table table">
                        <thead>
                            <tr>
                                <th>Window</th>
                                <th>Tint %</th>
                                <th>Material</th>
                                <th class="text-end">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($receipt_data['customizations']['windows'] as $window): ?>
                            <tr>
                                <td>
                                    <i class="fas fa-window-maximize me-1" style="color: var(--primary);"></i>
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $window['type']))) ?>
                                </td>
                                <td><span class="badge bg-dark"><?= $window['tint_percentage'] ?>% VLT</span></td>
                                <td><?= htmlspecialchars(ucfirst($window['material'])) ?></td>
                                <td class="text-end fw-bold" style="color: var(--success);">+₱<?= number_format($window['price'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            
            <h6 class="fw-bold mb-3" style="color: var(--secondary);">
                <i class="fas fa-receipt me-2" style="color: var(--primary);"></i> Price Breakdown
            </h6>
            <table class="price-table">
                <tr>
                    <td class="label">
                        <i class="fas fa-box me-1" style="color: var(--primary);"></i>
                        Base Package (<?= ucfirst($receipt_data['vehicle_category']) ?>)
                    </td>
                    <td class="amount">₱<?= number_format($receipt_data['base_price'], 2) ?></td>
                </tr>
                
                <?php if($receipt_data['customization_total'] > 0): ?>
                <tr>
                    <td class="label">
                        <i class="fas fa-cog me-1" style="color: var(--primary);"></i>
                        Tint Customization
                    </td>
                    <td class="amount text-danger">+₱<?= number_format($receipt_data['customization_total'], 2) ?></td>
                </tr>
                <?php endif; ?>
                
                <tr class="total-row">
                    <td class="label fw-bold">Total Amount</td>
                    <td class="amount fw-bold" style="color: var(--success);">₱<?= number_format($receipt_data['total'], 2) ?></td>
                </tr>
            </table>
            

        </div>
        
        
        <div class="footer no-print">
            <div class="row">
                <div class="col-md-6 text-md-start mb-3 mb-md-0">
                    <i class="fas fa-map-marker-alt me-2" style="color: var(--primary);"></i> 123 Tint Avenue, Manila<br>
                    <i class="fas fa-phone me-2" style="color: var(--primary);"></i> +63 (2) 8123-4567<br>
                    <i class="fas fa-envelope me-2" style="color: var(--primary);"></i> info@rgcartintworks.com
                </div>
                <div class="col-md-6 text-md-end">
                    <i class="fas fa-clock me-2" style="color: var(--primary);"></i> Monday-Friday: 8:00 AM - 5:00 PM<br>
                    <i class="fas fa-clock me-2" style="color: var(--primary);"></i> Saturday-Sunday: Closed
                </div>
            </div>
            <hr class="my-3" style="border-color: #e9ecef;">
            <p class="mb-0">
                <i class="fas fa-check-circle me-1" style="color: var(--primary);"></i>
                This is an official receipt from RG CARTINTWORKS. 
                Please present this receipt on the day of your appointment.
            </p>
            <p class="mb-0 mt-2">
                <small>Generated on <?= date('F j, Y h:i A') ?> | Booking Reference: <?= htmlspecialchars($receipt_data['ref']) ?></small>
            </p>
        </div>
        
        
        <div class="action-buttons no-print">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print me-2"></i> Print Receipt
            </button>
            <a href="feedback.php?booking_id=<?= $receipt_data['id'] ?>" class="btn-download" style="background:#fff9e6;color:#92400e;border-color:#fde68a;">
                <i class="fas fa-star me-2" style="color:#D4A017;"></i> Rate & Leave Feedback
            </a>
            <a href="my_bookings.php" class="btn-back">
                <i class="fas fa-arrow-left me-2"></i> Back to Bookings
            </a>
            <a href="book.php" class="btn-download">
                <i class="fas fa-plus me-2"></i> New Booking
            </a>
        </div>
    </div>
</div>

<div class="print-only" style="display: none; text-align: center; margin-top: 30px; font-size: 10pt; color: #6c757d;">
    <hr style="border-color: #e9ecef;">
    <p>
        <i class="fas fa-shield-alt me-1" style="color: var(--primary);"></i>
        This is a computer-generated receipt. No signature required.
    </p>
    <p>© <?= date('Y') ?> RG CARTINTWORKS. All rights reserved.</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const verifyUrl = <?= json_encode($receipt_data['verify_url']) ?>;
    
    
    document.getElementById('qrcode').innerHTML = '';
    
    
    new QRCode(document.getElementById("qrcode"), {
        text: verifyUrl,
        width: 130,
        height: 130,
        colorDark: "#2c3e50",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
    
    
    document.getElementById('qrcode').addEventListener('click', function() {
        
        window.open(verifyUrl, '_blank');
    });
    
    
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('print') === '1') {
        setTimeout(() => {
            window.print();
        }, 500); 
    }
    
    
    window.addEventListener('beforeprint', function() {
        document.body.style.background = 'white';
    });
    
    
    window.addEventListener('afterprint', function() {
        document.body.style.background = 'linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%)';
    });
});

function downloadPDF() {
    window.print();
}

function verifyBooking() {
    window.open(<?= json_encode($receipt_data['verify_url']) ?>, '_blank');
}

function emailReceipt() {
    const subject = encodeURIComponent('Your RG CARTINTWORKS Booking Receipt #<?= $receipt_data['ref'] ?>');
    const body = encodeURIComponent(
        'Thank you for choosing RG CARTINTWORKS.\n\n' +
        'Your booking receipt is attached. Please find your appointment details below:\n\n' +
        'Booking Reference: <?= $receipt_data['ref'] ?>\n' +
        'Date: <?= $receipt_data['appointment_formatted'] ?>\n' +
        'Time: <?= $receipt_data['appointment_time'] ?>\n' +
        'Vehicle: <?= $receipt_data['vehicle_model'] ?>\n' +
        'Package: <?= $receipt_data['package_name'] ?>\n' +
        'Total Amount: ₱<?= number_format($receipt_data['total'], 2) ?>\n\n' +
        'Verify your booking online: <?= $receipt_data['verify_url'] ?>\n\n' +
        'Thank you for your business!'
    );
    
    window.location.href = `mailto:${encodeURIComponent('<?= $receipt_data['email'] ?>')}?subject=${subject}&body=${body}`;
}

window.addEventListener('DOMContentLoaded', () => {
    
    setTimeout(() => {
        window.print();
    }, 400);
});
</script>
</body>
</html>
