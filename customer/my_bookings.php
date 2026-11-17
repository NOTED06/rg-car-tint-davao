<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require_customer_auth('../login.php');

$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Customer';
$message = '';
$msg_type = '';

$show_success_modal = false;
$success_booking = null;
if (isset($_GET['payment_success']) && $_GET['payment_success'] === 'true' && isset($_GET['booking_id'])) {
    $b_id = intval($_GET['booking_id']);
    try {
        $stmt = $pdo->prepare("SELECT b.*, p.name as package_name, vt.type_name as vehicle_type_name FROM bookings b LEFT JOIN tint_packages p ON b.package_id = p.id LEFT JOIN vehicle_types vt ON b.vehicle_type_id = vt.id WHERE b.id = ? AND b.user_id = ?");
        $stmt->execute([$b_id, $user_id]);
        $success_booking = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($success_booking) {
            $show_success_modal = true;
        }
    } catch (PDOException $e) {
        error_log("Failed to load successful booking: " . $e->getMessage());
    }
}

if (isset($_GET['payment_failed']) && $_GET['payment_failed'] === 'true') {
    $message = "Your GCash payment could not be processed. Please check your account or try paying again.";
    $msg_type = "danger";
}
if (isset($_GET['payment_cancelled']) && $_GET['payment_cancelled'] == '1') {
    $message = "Payment was cancelled. Your booking was not placed.";
    $msg_type = "warning";
}
if (!empty($_GET['payment_error'])) {
    $message = $_GET['payment_error'];
    $msg_type = "danger";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'cancel_booking') {
    $booking_id = filter_var($_POST['booking_id'], FILTER_VALIDATE_INT);
    
    if ($booking_id) {
        try {
            
            $check_stmt = $pdo->prepare("SELECT id, status, payment_status, payment_method, payment_reference, total_price FROM bookings WHERE id = ? AND user_id = ?");
            $check_stmt->execute([$booking_id, $user_id]);
            $booking = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($booking && in_array(strtolower($booking['status']), ['pending', 'on hold'])) {
                $is_paid = in_array(strtolower($booking['payment_status'] ?? ''), ['paid', 'deposit_paid', 'partially_paid', 'approved']);
                $new_pay_status = $is_paid ? 'Refund Required' : ($booking['payment_status'] ?? 'Pending');

                
                $update_stmt = $pdo->prepare("UPDATE bookings SET status = 'Cancelled', payment_status = ?, updated_at = NOW() WHERE id = ?");
                $update_stmt->execute([$new_pay_status, $booking_id]);
                
                
                $cust_msg = $is_paid 
                    ? "You have cancelled booking #{$booking_id}. Your online payment refund request (₱" . number_format($booking['total_price'] ?? 0, 2) . ") has been logged for admin review."
                    : "You have successfully cancelled your booking #{$booking_id}.";

                $notif_stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, user_type, title, message, type, related_id, related_type, is_read, created_at) 
                    VALUES (?, 'customer', 'Booking Cancelled', ?, 'warning', ?, 'booking', 0, NOW())
                ");
                $notif_stmt->execute([$user_id, $cust_msg, $booking_id]);
                
                
                $admin_msg = $is_paid 
                    ? "Customer {$user_name} cancelled paid booking #{$booking_id}. Refund required (₱" . number_format($booking['total_price'] ?? 0, 2) . " via " . ($booking['payment_method'] ?: 'Online') . ")."
                    : "Customer {$user_name} cancelled booking #{$booking_id}.";

                $admin_stmt = $pdo->prepare("
                    INSERT INTO notifications (user_type, title, message, type, related_id, related_type, is_read, created_at) 
                    VALUES ('admin', 'Booking Cancelled by Customer', ?, 'warning', ?, 'booking', 0, NOW())
                ");
                $admin_stmt->execute([$admin_msg, $booking_id]);
                
                $message = "Booking #{$booking_id} has been cancelled successfully." . ($is_paid ? " Refund request logged for admin processing." : "");
                $msg_type = "success";
            } else {
                $message = "This booking cannot be cancelled.";
                $msg_type = "danger";
            }
        } catch (Exception $e) {
            $message = "Error cancelling booking: " . $e->getMessage();
            $msg_type = "danger";
            error_log("Cancel booking error: " . $e->getMessage());
        }
    }
}

$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? '';
$search_term = $_GET['search'] ?? '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    
    $table = null;
    $checkTbl = $pdo->prepare("SHOW TABLES LIKE 'bookings'");
    $checkTbl->execute();
    if ($checkTbl->fetchColumn()) {
        $table = 'bookings';
    } else {
        $checkTbl = $pdo->prepare("SHOW TABLES LIKE 'appointments'");
        $checkTbl->execute();
        if ($checkTbl->fetchColumn()) {
            $table = 'appointments';
        }
    }
    
    if (!$table) {
        throw new Exception("No bookings table found");
    }
    
    
    $sql = "SELECT b.*, v.type_name 
            FROM {$table} b 
            LEFT JOIN vehicle_types v ON b.vehicle_type_id = v.id 
            WHERE b.user_id = ?";
    $params = [$user_id];
    
    if ($status_filter != 'all') {
        $sql .= " AND LOWER(b.status) = ?";
        $params[] = strtolower($status_filter);
    }
    
    if (!empty($date_filter)) {
        $sql .= " AND DATE(b.appointment_date) = ?";
        $params[] = $date_filter;
    }
    
    if (!empty($search_term)) {
        $sql .= " AND (b.id LIKE ? OR b.vehicle_model LIKE ? OR b.rejection_reason LIKE ?)";
        $search_param = '%' . $search_term . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    
    $count_sql = str_replace("b.*, v.type_name", "COUNT(*) as total", $sql);
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_bookings = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_bookings / $per_page);
    
    
    $sql .= " ORDER BY b.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $bookings = [];
    $total_bookings = 0;
    $total_pages = 1;
    error_log("Error fetching bookings: " . $e->getMessage());
}

try {
    $stats_sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN LOWER(status) = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN LOWER(status) = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN LOWER(status) = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN LOWER(status) = 'cancelled' OR LOWER(status) = 'canceled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN LOWER(status) = 'declined' THEN 1 ELSE 0 END) as declined
                  FROM {$table} WHERE user_id = ?";
    $stats_stmt = $pdo->prepare($stats_sql);
    $stats_stmt->execute([$user_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $stats = ['total'=>0, 'pending'=>0, 'approved'=>0, 'completed'=>0, 'cancelled'=>0, 'declined'=>0];
}

$auto_view_booking = isset($_GET['view_booking']) ? (int)$_GET['view_booking'] : 0;

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
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND user_type = 'customer' AND is_read = 0");
$stmt->execute([$user_id]);
$unread_count = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND user_type = 'customer' ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>My Bookings — RG Cartint Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { 
            background-color: #f8f9fa; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .page-header {
            background: linear-gradient(135deg, #1e1e1e 0%, #333 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-bottom: 4px solid #ffc107;
        }
        
        .page-title {
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .page-subtitle {
            color: rgba(255,255,255,0.7);
            margin-bottom: 0;
        }
        
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.2s;
            border: 1px solid #eee;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 1.2rem;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            min-width: 100px;
        }
        
        .status-pending { 
            background: #fef3c7; 
            color: #92400e; 
            border-left: 3px solid #f59e0b;
        }
        .status-pending i { color: #f59e0b; }
        
        .status-approved { 
            background: #d1fae5; 
            color: #065f46; 
            border-left: 3px solid #10b981;
        }
        .status-approved i { color: #10b981; }
        
        .status-completed { 
            background: #cffafe; 
            color: #155e75; 
            border-left: 3px solid #06b6d4;
        }
        .status-completed i { color: #06b6d4; }
        
        .status-cancelled { 
            background: #fee2e2; 
            color: #991b1b; 
            border-left: 3px solid #ef4444;
        }
        .status-cancelled i { color: #ef4444; }
        
        .status-declined { 
            background: #fee2e2; 
            color: #991b1b; 
            border-left: 3px solid #dc2626;
        }
        .status-declined i { color: #dc2626; }
        
        
        .booking-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.03);
            border: 1px solid #eee;
            transition: all 0.2s;
        }
        
        .booking-card:hover {
            border-color: #ffc107;
            box-shadow: 0 8px 20px rgba(255,193,7,0.1);
        }
        
        .booking-id {
            font-family: monospace;
            font-size: 1.1rem;
            font-weight: 700;
            color: #ffc107;
            background: #fff9e6;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .booking-date {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .booking-date i {
            margin-right: 5px;
            color: #ffc107;
        }
        
        .vehicle-info {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .vehicle-type {
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        .package-name {
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .price-tag {
            font-weight: 700;
            color: #10b981;
            font-size: 1.2rem;
        }
        
        .rejection-reason {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 0.9rem;
            color: #991b1b;
        }
        
        .rejection-reason i {
            margin-right: 8px;
            color: #dc2626;
        }
        
        
        .filter-bar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #eee;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
        }
        
        .search-box input {
            padding-left: 40px;
            border-radius: 50px;
            border: 1px solid #dee2e6;
            height: 45px;
        }
        
        .search-box input:focus {
            border-color: #ffc107;
            box-shadow: 0 0 0 0.2rem rgba(255,193,7,0.25);
        }
        
        .filter-select {
            height: 45px;
            border-radius: 50px;
            border: 1px solid #dee2e6;
            padding: 0 20px;
        }
        
        .filter-select:focus {
            border-color: #ffc107;
            box-shadow: 0 0 0 0.2rem rgba(255,193,7,0.25);
        }
        
        
        .pagination .page-link {
            color: #333;
            border-radius: 8px;
            margin: 0 3px;
        }
        
        .pagination .page-link:hover {
            background: #ffc107;
            color: #333;
            border-color: #ffc107;
        }
        
        .pagination .active .page-link {
            background: #ffc107;
            border-color: #ffc107;
            color: #333;
            font-weight: 600;
        }
        
        
        .btn-cancel {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: 50px;
            padding: 8px 16px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .btn-cancel:hover {
            background: #fecaca;
            color: #7f1d1d;
        }
        
        .btn-view {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #dee2e6;
            border-radius: 50px;
            padding: 8px 16px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .btn-view:hover {
            background: #e9ecef;
        }
        
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }
        
        .empty-icon {
            width: 80px;
            height: 80px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: #adb5bd;
        }
        
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
        }
        
        
        .modal-loading {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1060;
            border-radius: 8px;
        }

        
        .transfer-banner {
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
            border: 1.5px solid #f59e0b;
            border-radius: 12px;
            padding: 14px 16px;
            margin: 0 0 12px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .transfer-banner .tb-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 13px;
            color: #92400e;
        }
        .transfer-banner .tb-header i { font-size: 15px; color: #f59e0b; }
        .transfer-banner .tb-detail {
            font-size: 12px;
            color: #78350f;
            line-height: 1.5;
        }
        .transfer-banner .tb-reason {
            font-size: 11.5px;
            color: #92400e;
            background: rgba(245,158,11,.12);
            border-radius: 6px;
            padding: 5px 8px;
            font-style: italic;
        }
        .transfer-banner .tb-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .tb-btn-accept {
            background: linear-gradient(135deg,#10b981,#059669);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: opacity .15s;
        }
        .tb-btn-accept:hover { opacity: .88; }
        .tb-btn-accept:disabled { opacity: .5; cursor: not-allowed; }
        .tb-btn-own {
            background: #fff;
            color: #0A0A0A;
            border: 1.5px solid #e4e4e7;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background .15s;
        }
        .tb-btn-own:hover { background: #f4f4f5; }
    
        
        .status-pill-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }
        .status-pill-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.5rem 1.15rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            color: #475569;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.25s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        }
        .status-pill-btn:hover {
            border-color: #D4A017;
            color: #C89008;
            background: #fffdf5;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(212,160,23,0.15);
        }
        .status-pill-btn.active {
            background: linear-gradient(135deg, #F5C842 0%, #D4A017 100%);
            border-color: #D4A017;
            color: #121214;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(212,160,23,0.35);
        }
        .pill-badge {
            background: rgba(0,0,0,0.08);
            color: inherit;
            padding: 2px 7px;
            border-radius: 50px;
            font-size: 0.72rem;
            font-weight: 800;
        }
        .status-pill-btn.active .pill-badge {
            background: rgba(18,18,20,0.15);
            color: #121214;
        }

        
        .kpi-card-luxury {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.1rem 1.15rem;
            position: relative;
            overflow: hidden;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 3px 10px rgba(0,0,0,0.03);
            cursor: pointer;
        }
        .kpi-card-top-bar {
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3.5px;
        }
        .kpi-card-luxury:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(212,160,23,0.15);
            border-color: #D4A017;
        }
        .kpi-card-luxury.active-total,
        .kpi-card-luxury.active-pending,
        .kpi-card-luxury.active-approved,
        .kpi-card-luxury.active-completed,
        .kpi-card-luxury.active-cancelled {
            border: 2px solid var(--card-accent, #D4A017);
            background: #fffdf8;
            box-shadow: 0 6px 20px rgba(212,160,23,0.2);
        }
        .kpi-icon-luxury {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }
        .kpi-val-num {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.55rem;
            line-height: 1.1;
            color: #0f172a;
        }
        .kpi-lbl-title {
            font-size: 0.7rem;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-top: 2px;
        }

        
        .bk-card {
            background: #ffffff !important;
            border: 1.5px solid #E6E2DA !important;
            border-radius: 16px !important;
            margin-bottom: 20px !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02) !important;
            overflow: hidden !important;
            transition: all 0.25s ease !important;
        }
        .bk-card:hover {
            border-color: #D4A017 !important;
            box-shadow: 0 8px 25px rgba(212,160,23,0.12) !important;
        }
        .bk-card-top {
            padding: 12px 22px !important;
            background: #fafafa !important;
            border-bottom: 1px solid #f1f5f9 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            flex-wrap: wrap !important;
            gap: 10px !important;
            font-size: 0.8rem !important;
        }
        .bk-redesign-ref {
            color: #64748b !important;
            font-weight: 700 !important;
            letter-spacing: 0.04em !important;
            text-transform: uppercase !important;
        }
        .bk-redesign-ref strong {
            color: #0f172a !important;
            font-weight: 800 !important;
        }
        .badge-payment-paid {
            background: rgba(34, 197, 94, 0.12) !important;
            color: #166534 !important;
            padding: 4px 12px !important;
            border-radius: 50px !important;
            font-size: 0.72rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.04em !important;
            display: inline-flex !important;
            align-items: center !important;
        }
        .badge-payment-pending {
            background: rgba(245, 158, 11, 0.12) !important;
            color: #92400e !important;
            padding: 4px 12px !important;
            border-radius: 50px !important;
            font-size: 0.72rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.04em !important;
            display: inline-flex !important;
            align-items: center !important;
        }
        .badge-payment-rejected {
            background: rgba(239, 68, 68, 0.12) !important;
            color: #991b1b !important;
            padding: 4px 12px !important;
            border-radius: 50px !important;
            font-size: 0.72rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.04em !important;
            display: inline-flex !important;
            align-items: center !important;
        }

        .status-dot-indicator {
            display: inline-flex !important;
            align-items: center !important;
            gap: 7px !important;
            font-size: 0.75rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.05em !important;
            text-transform: uppercase !important;
        }
        .status-dot {
            width: 8px !important;
            height: 8px !important;
            border-radius: 50% !important;
            display: inline-block !important;
        }

        .bk-card-body {
            padding: 22px 28px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            flex-wrap: wrap !important;
            gap: 16px !important;
        }
        .bk-car-badge-icon {
            width: 48px !important;
            height: 48px !important;
            border-radius: 12px !important;
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: #D4A017 !important;
            font-size: 1.25rem !important;
            flex-shrink: 0 !important;
        }
        .bk-vehicle-title {
            font-family: 'DM Sans', sans-serif !important;
            font-weight: 700 !important;
            font-size: 1.05rem !important;
            color: #0f172a !important;
            margin-bottom: 2px !important;
        }
        .bk-vehicle-sub {
            font-size: 0.82rem !important;
            color: #64748b !important;
        }

        .bk-price-block {
            text-align: right !important;
        }
        .bk-price-lbl {
            font-size: 0.68rem !important;
            font-weight: 700 !important;
            color: #94a3b8 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.06em !important;
            margin-bottom: 2px !important;
        }
        .bk-price-val {
            font-family: 'DM Sans', sans-serif !important;
            font-weight: 800 !important;
            font-size: 1.55rem !important;
            color: #0f172a !important;
            line-height: 1.1 !important;
        }

        .bk-card-actions {
            padding: 14px 28px !important;
            background: #ffffff !important;
            border-top: 1px solid #f1f5f9 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            flex-wrap: wrap !important;
            gap: 10px !important;
        }

        .btn-card-action {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            padding: 8px 18px !important;
            border-radius: 10px !important;
            font-size: 0.82rem !important;
            font-weight: 700 !important;
            text-decoration: none !important;
            transition: all 0.2s ease !important;
            border: none !important;
            cursor: pointer !important;
            line-height: 1.2 !important;
        }
        .btn-card-action-soft {
            background: #f1f5f9 !important;
            color: #475569 !important;
            border: 1px solid #cbd5e1 !important;
        }
        .btn-card-action-soft:hover {
            background: #e2e8f0 !important;
            color: #0f172a !important;
        }
        .btn-card-action-primary {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(13,148,136,0.25) !important;
        }
        .btn-card-action-primary:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 16px rgba(13,148,136,0.35) !important;
            color: #ffffff !important;
        }
        .btn-card-action-gold {
            background: linear-gradient(135deg, #F5C842 0%, #D4A017 100%) !important;
            color: #121214 !important;
            box-shadow: 0 4px 12px rgba(212,160,23,0.25) !important;
        }
        .btn-card-action-gold:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 16px rgba(212,160,23,0.35) !important;
            color: #121214 !important;
        }

        
        .rg-booking-card {
            background: #ffffff !important;
            border: 1.5px solid #E6E2DA !important;
            border-radius: 16px !important;
            margin-bottom: 20px !important; width: 100% !important; border-radius: 18px !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02) !important;
            position: relative !important;
            overflow: hidden !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .rg-booking-card-top-bar {
            position: absolute !important;
            top: 0 !important; left: 0 !important; right: 0 !important;
            height: 3.5px !important;
            background: linear-gradient(90deg, #F5C842 0%, #D4A017 100%) !important;
        }
        .rg-booking-card:hover {
            transform: translateY(-3px) !important;
            border-color: #D4A017 !important;
            box-shadow: 0 10px 28px rgba(212,160,23,0.16) !important;
        }

        .rg-card-header {
            padding: 14px 28px !important;
            background: #fafafa !important;
            border-bottom: 1px solid #f1f5f9 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            flex-wrap: wrap !important;
            gap: 12px !important;
        }
        .rg-ref-badge {
            background: #FFFDF0 !important;
            border: 1px solid rgba(212,160,23,0.3) !important;
            color: #C89008 !important;
            font-weight: 800 !important;
            font-size: 0.8rem !important;
            padding: 4px 12px !important;
            border-radius: 8px !important;
            display: inline-flex !important;
            align-items: center !important;
            letter-spacing: 0.03em !important;
        }
        .rg-meta-date {
            color: #64748b !important;
            font-size: 0.82rem !important;
            font-weight: 600 !important;
        }
        .rg-badge-payment {
            padding: 4px 12px !important;
            border-radius: 50px !important;
            font-size: 0.7rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.05em !important;
            display: inline-flex !important;
            align-items: center !important;
        }
        .rg-badge-payment-paid {
            background: rgba(16, 185, 129, 0.12) !important;
            color: #059669 !important;
            border: 1px solid rgba(16, 185, 129, 0.2) !important;
        }
        .rg-badge-payment-pending {
            background: rgba(245, 158, 11, 0.12) !important;
            color: #d97706 !important;
            border: 1px solid rgba(245, 158, 11, 0.2) !important;
        }
        .rg-badge-payment-rejected {
            background: rgba(239, 68, 68, 0.12) !important;
            color: #dc2626 !important;
            border: 1px solid rgba(239, 68, 68, 0.2) !important;
        }

        .rg-status-dot-wrap {
            display: inline-flex !important;
            align-items: center !important;
            gap: 7px !important;
            font-size: 0.75rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.06em !important;
            text-transform: uppercase !important;
        }
        .rg-status-dot {
            width: 9px !important;
            height: 9px !important;
            border-radius: 50% !important;
            display: inline-block !important;
            box-shadow: 0 0 8px currentColor !important;
        }

        .rg-card-body {
            padding: 22px 28px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            flex-wrap: wrap !important;
            gap: 16px !important;
        }
        .rg-car-emblem {
            width: 52px !important;
            height: 52px !important;
            border-radius: 14px !important;
            background: #0A0A0A !important;
            color: #D4A017 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.3rem !important;
            box-shadow: 0 4px 14px rgba(0,0,0,0.12) !important;
            flex-shrink: 0 !important;
        }
        .rg-vehicle-name {
            font-weight: 800 !important;
            font-size: 1.1rem !important;
            color: #0f172a !important;
            margin-bottom: 2px !important;
        }
        .rg-vehicle-details {
            font-size: 0.83rem !important;
            color: #64748b !important;
            font-weight: 500 !important;
        }

        .rg-price-badge-card {
            background: #FFFDF5 !important;
            border: 1.5px solid rgba(212,160,23,0.22) !important;
            padding: 14px 28px !important;
            border-radius: 12px !important;
            text-align: right !important;
        }
        .rg-price-label {
            font-size: 0.65rem !important;
            font-weight: 800 !important;
            color: #C89008 !important;
            letter-spacing: 0.08em !important;
            text-transform: uppercase !important;
            margin-bottom: 2px !important;
        }
        .rg-price-amount {
            font-weight: 800 !important;
            font-size: 1.55rem !important;
            color: #0A0A0A !important;
            line-height: 1 !important;
        }

        .rg-card-footer {
            padding: 14px 28px !important;
            background: #ffffff !important;
            border-top: 1px solid #f1f5f9 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            flex-wrap: wrap !important;
            gap: 10px !important;
        }
        .rg-btn-action {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            padding: 8px 18px !important;
            border-radius: 50px !important;
            font-size: 0.82rem !important;
            font-weight: 700 !important;
            text-decoration: none !important;
            transition: all 0.2s ease !important;
            border: none !important;
            cursor: pointer !important;
        }
        .rg-btn-action-soft {
            background: #f8fafc !important;
            color: #334155 !important;
            border: 1.5px solid #cbd5e1 !important;
        }
        .rg-btn-action-soft:hover {
            background: #e2e8f0 !important;
            color: #0f172a !important;
            transform: translateY(-1px) !important;
        }
        .rg-btn-action-gold {
            background: linear-gradient(135deg, #F5C842 0%, #D4A017 100%) !important;
            color: #121214 !important;
            font-weight: 800 !important;
            box-shadow: 0 4px 14px rgba(212,160,23,0.3) !important;
        }
        .rg-btn-action-gold:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 18px rgba(212,160,23,0.4) !important;
            color: #121214 !important;
        }

        .pkg-hero-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
            border-radius: 16px !important;
            padding: 22px 28px !important; margin-bottom: 20px !important; width: 100% !important; border-radius: 18px !important;
            color: #ffffff !important;
            margin-bottom: 24px !important;
            box-shadow: 0 8px 24px rgba(15,23,42,0.12) !important;
            position: relative !important;
            overflow: hidden !important;
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

        
        .cust-content {
            padding: 24px !important;
            max-width: 100% !important;
            margin: 0 auto !important;
            width: 100% !important;
        }
        .pkg-hero-banner {
            width: 100% !important;
            margin-bottom: 24px !important;
        }
        .filter-toolbar-card {
            width: 100% !important;
            margin-bottom: 24px !important;
        }
        .rg-booking-card {
            width: 100% !important;
            margin-bottom: 24px !important;
        }

        .cust-content {
            max-width: 1400px !important;
            margin: 0 auto !important;
            padding: 24px 24px !important;
            width: 100% !important;
        }
</style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/customer.css?v=1785564750">

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
<style>
.notif-jump-target {
    animation: targetPulse 3.5s ease-in-out forwards !important;
    scroll-margin-top: 100px;
}
@keyframes targetPulse {
    0% {
        outline: 3px solid #D4A017 !important;
        box-shadow: 0 0 0 6px rgba(212, 160, 23, 0.35), 0 8px 24px rgba(212, 160, 23, 0.2) !important;
        background: #fef3c7 !important;
        transform: scale(1.01);
    }
    40% {
        outline: 3px solid #D4A017 !important;
        box-shadow: 0 0 0 6px rgba(212, 160, 23, 0.35), 0 8px 24px rgba(212, 160, 23, 0.2) !important;
        background: #fef3c7 !important;
        transform: scale(1.01);
    }
    100% {
        outline: 1.5px solid #E6E2DA !important;
        box-shadow: none !important;
        background: #ffffff !important;
        transform: scale(1);
    }
}
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const params = new URLSearchParams(window.location.search);
    const targetId = params.get("id") || (window.location.hash ? window.location.hash.replace("#booking-", "") : null);
    
    if (targetId) {
        const targetEl = document.getElementById("booking-" + targetId);
        if (targetEl) {
            setTimeout(() => {
                targetEl.scrollIntoView({ behavior: "smooth", block: "center" });
                targetEl.classList.add("notif-jump-target");
                setTimeout(() => targetEl.classList.remove("notif-jump-target"), 3500);
            }, 300);
        }
    }
});
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
                <a href="support.php" class="cs-nav-link">
                    <span class="cs-nav-icon"><i class="fas fa-headset"></i></span>Customer Support
                </a>
            </li>
            <li class="cs-nav-item">
                <a href="my_bookings.php" class="cs-nav-link active">
                    <span class="cs-nav-icon"><i class="fas fa-calendar-alt"></i></span>My Bookings
                    <?php if(($stats['pending'] ?? 0) > 0): ?><span class="cs-nav-badge"><?= (int)($stats['pending'] ?? 0) ?></span><?php endif; ?>
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

    <div class="cust-content" style="max-width: 1400px; margin: 0 auto; padding: 24px 24px;">
        
        <div class="pkg-hero-banner d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <span class="badge mb-2" style="background:rgba(212,160,23,0.2);color:#F5C842;border:1px solid rgba(212,160,23,0.4);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;letter-spacing:.05em;">
                    <i class="fas fa-calendar-alt me-1"></i>CUSTOMER PORTAL
                </span>
                <h3 style="font-weight:800;margin:0;font-size:1.6rem;letter-spacing:-0.01em;color:#ffffff;">My Bookings</h3>
                <p style="color:#94a3b8;font-size:13px;margin:6px 0 0;max-width:640px;line-height:1.5;">
                    View and manage your tinting appointments
                </p>
            </div>
            <div>
                <a href="packages.php" class="btn btn-sm" style="background:rgba(255,255,255,0.08);color:#ffffff;border:1px solid rgba(255,255,255,0.2);padding:8px 16px;border-radius:10px;font-weight:700;font-size:12px;">
                    <i class="fas fa-box-open me-2" style="color:#D4A017;"></i>Tint Packages
                </a>
            </div>
        </div>

        
        <div class="filter-toolbar-card mb-4" style="background:#ffffff;border:1.5px solid #E6E2DA;border-radius:16px;padding:12px 18px;margin-bottom:20px !important;width:100% !important;box-shadow:0 3px 10px rgba(0,0,0,0.02);">
            <form method="GET" action="my_bookings.php" id="bookingFilterForm" class="d-flex flex-wrap align-items-center justify-content-between gap-3 m-0">
                <input type="hidden" name="status" id="filterStatus" value="<?= htmlspecialchars($status_filter) ?>">
                
                
                <div class="status-pill-bar d-flex flex-wrap align-items-center gap-2 m-0">
                    <button type="button" class="status-pill-btn <?= $status_filter == 'all' ? 'active' : '' ?>" onclick="selectStatusFilter('all')">
                        <i class="fas fa-layer-group me-1"></i>All Bookings <span class="pill-badge"><?= $stats['total'] ?? 0 ?></span>
                    </button>
                    <button type="button" class="status-pill-btn <?= $status_filter == 'pending' ? 'active' : '' ?>" onclick="selectStatusFilter('pending')">
                        <i class="fas fa-hourglass-half me-1"></i>Pending <span class="pill-badge"><?= $stats['pending'] ?? 0 ?></span>
                    </button>
                    <button type="button" class="status-pill-btn <?= $status_filter == 'approved' ? 'active' : '' ?>" onclick="selectStatusFilter('approved')">
                        <i class="fas fa-circle-check me-1"></i>Approved <span class="pill-badge"><?= $stats['approved'] ?? 0 ?></span>
                    </button>
                    <button type="button" class="status-pill-btn <?= $status_filter == 'completed' ? 'active' : '' ?>" onclick="selectStatusFilter('completed')">
                        <i class="fas fa-shield-check me-1"></i>Completed <span class="pill-badge"><?= $stats['completed'] ?? 0 ?></span>
                    </button>
                    <button type="button" class="status-pill-btn <?= in_array($status_filter, ['cancelled','declined']) ? 'active' : '' ?>" onclick="selectStatusFilter('cancelled')">
                        <i class="fas fa-ban me-1"></i>Cancelled / Declined <span class="pill-badge"><?= ($stats['cancelled'] ?? 0) + ($stats['declined'] ?? 0) ?></span>
                    </button>
                </div>

                
                <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                    
                    <div style="position:relative;width:240px;">
                        <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px;pointer-events:none;"></i>
                        <input type="text" class="form-control ps-4" name="search" id="liveSearchInput" placeholder="Search..." value="<?= htmlspecialchars($search_term) ?>" style="height:36px;font-size:12px;border:1.5px solid #E6E2DA;border-radius:20px;background:#f8fafc;" oninput="onLiveSearchInput(this)">
                    </div>

                    
                    <div style="width:145px;">
                        <input type="date" class="form-control" name="date" id="filterDateInput" value="<?= htmlspecialchars($date_filter) ?>" style="height:36px;font-size:12px;border:1.5px solid #E6E2DA;border-radius:20px;background:#f8fafc;" onchange="this.form.submit()">
                    </div>

                    
                    <?php if(!empty($search_term) || $status_filter != 'all' || !empty($date_filter)): ?>
                    <a href="my_bookings.php" class="btn btn-sm btn-outline-secondary" style="height:36px;border-radius:20px;display:inline-flex;align-items:center;justify-content:center;padding:0 12px;font-size:12px;" title="Clear Filters">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <script>
        function selectStatusFilter(val) {
            document.getElementById('filterStatus').value = val;
            document.getElementById('bookingFilterForm').submit();
        }

        let searchTimer;
        function onLiveSearchInput(input) {
            const q = input.value.toLowerCase().trim();
            document.querySelectorAll('.bk-card').forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(q)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });

            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                document.getElementById('bookingFilterForm').submit();
            }, 600);
        }
        </script>

        
        <?php if($message): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show mb-3" role="alert" style="border-radius:10px;font-size:13px;">
            <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        
        <?php if(empty($bookings)): ?>
        <div class="panel-card text-center py-5">
            <div style="font-size:3rem;color:#E6E2DA;margin-bottom:16px;"><i class="fas fa-calendar-xmark"></i></div>
            <h5 style="font-weight:700;margin-bottom:8px;">No Bookings Found</h5>
            <p style="color:#94a3b8;margin-bottom:20px;">You haven't made any bookings yet or no bookings match your filters.</p>
        </div>
        <?php else: ?>
        <?php foreach($bookings as $booking):
            $status = strtolower($booking['status'] ?? 'pending');
            $status_display = ucfirst($booking['status'] ?? 'Pending');
            $dot_color = '#f59e0b'; $status_text_color = '#d97706';
            if ($status === 'approved' || $status === 'confirmed') { $dot_color = '#10b981'; $status_text_color = '#059669'; }
            elseif ($status === 'completed') { $dot_color = '#06b6d4'; $status_text_color = '#0891b2'; }
            elseif (in_array($status, ['cancelled','canceled','declined'])) { $dot_color = '#ef4444'; $status_text_color = '#dc2626'; }

            $appointment_date = $booking['appointment_date'] ?? $booking['created_at'];
            $package_names = [
                1=>'HD Carbon Ceramic Tint', 2=>'Magic Chrome Ceramic', 3=>'Nano Ceramic Black Series',
                4=>'Nano Ceramic Clear Bluish', 5=>'Standard Carbon', 6=>'Premium Security'
            ];
            $package_name = $package_names[$booking['package_id']] ?? ($booking['package_name'] ?? 'Custom Package');
            $can_cancel = ($status === 'pending');

            $pay_st = $booking['payment_status'] ?? '';
        ?>
        <div class="rg-booking-card bk-card" id="booking-<?= (int)$booking['id'] ?>" data-booking-id="<?= (int)$booking['id'] ?>">
            <div class="rg-booking-card-top-bar"></div>

            
            <div class="rg-card-header">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="rg-ref-badge"><i class="fas fa-hashtag me-1"></i><?= $booking['id'] ?></span>
                    <span style="color:#cbd5e1;">&bull;</span>
                    <span class="rg-meta-date"><i class="far fa-calendar-alt me-1" style="color:#D4A017;"></i><?= date('M d, Y', strtotime($appointment_date)) ?><?= !empty($booking['appointment_time']) ? ' at '.$booking['appointment_time'] : '' ?></span>
                    
                    
                    <?php if ($pay_st === 'Verified' || $status === 'completed'): ?>
                        <span class="rg-badge-payment rg-badge-payment-paid"><i class="fas fa-circle-check me-1"></i>GCASH PAID</span>
                    <?php elseif ($pay_st === 'Proof Submitted'): ?>
                        <span class="rg-badge-payment rg-badge-payment-pending"><i class="fas fa-clock me-1"></i>PROOF SUBMITTED</span>
                    <?php elseif ($pay_st === 'Rejected'): ?>
                        <span class="rg-badge-payment rg-badge-payment-rejected"><i class="fas fa-times-circle me-1"></i>PAYMENT REJECTED</span>
                    <?php elseif ($status === 'on hold'): ?>
                        <span class="rg-badge-payment rg-badge-payment-pending"><i class="fas fa-wallet me-1"></i>PAYMENT REQUIRED</span>
                    <?php endif; ?>
                </div>

                
                <div class="rg-status-dot-wrap" style="color:<?= $status_text_color ?>;">
                    <span class="rg-status-dot" style="background:<?= $dot_color ?>;color:<?= $dot_color ?>;"></span>
                    <?= $status_display ?>
                </div>
            </div>

            
            <div class="rg-card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rg-car-emblem">
                        <i class="fas fa-car-side"></i>
                    </div>
                    <div>
                        <div class="rg-vehicle-name"><?= htmlspecialchars($booking['vehicle_model'] ?: 'Vehicle') ?></div>
                        <div class="rg-vehicle-details">
                            <?= htmlspecialchars($booking['type_name'] ?: 'Car') ?> &bull; <?= htmlspecialchars($package_name) ?>
                            <?php if(!empty($booking['tint_type'])): ?>
                            (<?= ucfirst($booking['tint_type']) ?> &bull; <?= $booking['shade_percentage'] ?? 35 ?>% Shade)
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                
                <div class="rg-price-badge-card">
                    <div class="rg-price-label">ESTIMATED TOTAL</div>
                    <div class="rg-price-amount">₱<?= number_format($booking['total_price'] ?? 0, 0) ?></div>
                </div>
            </div>

            
            <?php if($status=='declined' && !empty($booking['rejection_reason'])): ?>
            <div class="rejection-banner mx-3 mb-3"><i class="fas fa-info-circle me-1"></i><strong>Declined:</strong> <?= htmlspecialchars($booking['rejection_reason']) ?></div>
            <?php endif; ?>
            <?php if(in_array($status,['cancelled','canceled']) && !empty($booking['rejection_reason'])): ?>
            <div class="cancellation-banner mx-3 mb-3"><i class="fas fa-exclamation-triangle me-1"></i><strong>Cancelled:</strong> <?= htmlspecialchars($booking['rejection_reason']) ?></div>
            <?php endif; ?>

            <?php
            $ts = $booking['transfer_status'] ?? null;
            if ($ts === 'pending_customer_approval'):
                $new_date_label = date('F j, Y', strtotime($booking['appointment_date']));
                $new_time_label = htmlspecialchars($booking['appointment_time']);
                $reason_txt = !empty($booking['admin_transfer_reason']) ? htmlspecialchars($booking['admin_transfer_reason']) : null;
                $transferred_at = !empty($booking['admin_transferred_at']) ? date('M d, Y g:i A', strtotime($booking['admin_transferred_at'])) : '';
            ?>
            <div class="transfer-banner mx-3 mb-3" id="tbanner-<?= $booking['id'] ?>">
                <div class="tb-header">
                    <i class="fas fa-calendar-xmark"></i>
                    Admin Rescheduled Your Appointment
                </div>
                <div class="tb-detail">
                    Your booking was moved to <strong><?= $new_date_label ?></strong> at <strong><?= $new_time_label ?></strong><?= $transferred_at ? " (moved on {$transferred_at})" : '' ?>.
                    Please accept this date or choose your own preferred slot.
                </div>
                <?php if ($reason_txt): ?>
                <div class="tb-reason"><i class="fas fa-comment-dots me-1"></i>Reason: <?= $reason_txt ?></div>
                <?php endif; ?>
                <div class="tb-actions">
                    <button class="tb-btn-accept" id="accept-<?= $booking['id'] ?>" onclick="respondTransfer(<?= $booking['id'] ?>, 'accept', this)">
                        <i class="fas fa-circle-check"></i>Accept New Date
                    </button>
                    <button class="tb-btn-own" onclick="respondTransfer(<?= $booking['id'] ?>, 'pick_own', this)">
                        <i class="fas fa-calendar-pen"></i>Pick My Own Date
                    </button>
                </div>
            </div>
            <?php endif; ?>

            
            <div class="rg-card-footer">
                <button type="button" class="rg-btn-action rg-btn-action-soft open-supp-btn" data-id="<?= $booking['id'] ?>" data-model="<?= htmlspecialchars($booking['vehicle_model'] ?: 'Vehicle') ?>">
                    <i class="fas fa-headset me-1" style="color:#d4a017;"></i>Support
                </button>
                <?php if($status=='on hold'): ?>
                <button type="button" class="rg-btn-action rg-btn-action-soft open-pay-btn" style="background:#FEF3C7;color:#92400e;border-color:#fde68a;" data-id="<?= $booking['id'] ?>" data-ref="<?= htmlspecialchars($booking['booking_ref'] ?? '') ?>" data-total="<?= (float)($booking['total_price'] ?? 0) ?>" data-deposit="<?= (float)($booking['deposit_amount'] ?: round($booking['total_price']*0.10, 2)) ?>" data-type="<?= htmlspecialchars($booking['payment_type'] ?? 'full') ?>"><i class="fas fa-wallet me-1"></i>Complete Payment</button>
                <?php endif; ?>
                <?php if($status=='pending' && ($booking['payment_status'] ?? '') === 'Proof Submitted'): ?>
                <a href="payment_status.php?id=<?= $booking['id'] ?>" class="rg-btn-action rg-btn-action-soft"><i class="fas fa-shield-check me-1"></i>View Status</a>
                <?php endif; ?>
                <?php if(($booking['payment_status'] ?? '') === 'Rejected'): ?>
                <button type="button" class="rg-btn-action rg-btn-action-soft open-pay-btn" style="background:#fef2f2;color:#991b1b;border-color:#fca5a5;" data-id="<?= $booking['id'] ?>" data-ref="<?= htmlspecialchars($booking['booking_ref'] ?? '') ?>" data-total="<?= (float)($booking['total_price'] ?? 0) ?>" data-deposit="<?= (float)($booking['deposit_amount'] ?: round($booking['total_price']*0.10, 2)) ?>" data-type="<?= htmlspecialchars($booking['payment_type'] ?? 'full') ?>"><i class="fas fa-upload me-1"></i>Re-upload Proof</button>
                <?php endif; ?>
                <?php if($can_cancel): ?>
                <form method="POST" class="d-inline" data-rg-confirm="Cancel this booking?" data-rg-type="warning" data-rg-title="Cancel Booking">
                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                    <input type="hidden" name="action" value="cancel_booking">
                    <button type="submit" class="rg-btn-action rg-btn-action-soft" style="color:#dc2626;border-color:#fca5a5;"><i class="fas fa-times me-1"></i>Cancel</button>
                </form>
                <?php endif; ?>
                <?php if($status=='completed'): ?>
                <button type="button" class="rg-btn-action rg-btn-action-soft open-rev-btn" style="background:#fff9e6;color:#92400e;border-color:#fde68a;" data-id="<?= $booking['id'] ?>" data-model="<?= htmlspecialchars($booking['vehicle_model'] ?: 'Vehicle') ?>" data-package="<?= htmlspecialchars($booking['package_name'] ?? 'Custom Package') ?>"><i class="fas fa-star me-1" style="color:#D4A017;"></i>Review</button>
                <a href="../download_warranty.php?id=<?= $booking['id'] ?>" target="_blank" class="rg-btn-action rg-btn-action-soft" style="background:#ecfdf5;color:#059669;border-color:#a7f3d0;"><i class="fas fa-file-pdf me-1"></i>Warranty</a>
                <?php endif; ?>

                
                <button class="rg-btn-action rg-btn-action-gold view-details-btn" data-booking-id="<?= $booking['id'] ?>">
                    <i class="fas fa-eye me-1"></i>View Details
                </button>
            </div>
        </div>
        <?php endforeach; ?>

        
        <?php if($total_pages > 1): ?>
        <nav aria-label="Bookings pagination" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page-1 ?>&status=<?= urlencode($status_filter) ?>&date=<?= urlencode($date_filter) ?>&search=<?= urlencode($search_term) ?>"><i class="fas fa-chevron-left"></i></a>
                </li>
                <?php for($i=1;$i<=$total_pages;$i++): ?>
                <li class="page-item <?= $i==$page?'active':'' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status_filter) ?>&date=<?= urlencode($date_filter) ?>&search=<?= urlencode($search_term) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= $page>=$total_pages?'disabled':'' ?>">
                    <a class="page-link" href="?page=<?= $page+1 ?>&status=<?= urlencode($status_filter) ?>&date=<?= urlencode($date_filter) ?>&search=<?= urlencode($search_term) ?>"><i class="fas fa-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
        <div class="text-muted text-center mt-3" style="font-size:12px;">Showing <?= count($bookings) ?> of <?= $total_bookings ?> booking<?= $total_bookings != 1 ? 's' : '' ?></div>
        <?php endif; ?>

    </div>
</div>

<div class="modal fade" id="bookingDetailsModal" tabindex="-1" aria-labelledby="bookingDetailsModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:560px;">
        <div class="modal-content" style="border:none;border-radius:14px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.15);">
            <div class="modal-header" style="background:#0A0A0A;padding:14px 20px;border:none;">
                <h6 class="modal-title" id="bookingDetailsModalLabel" style="margin:0;font-weight:700;font-size:14px;color:#fff;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-file-invoice" style="color:#D4A017;"></i>Booking Details
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="closeModalBtn" style="font-size:10px;"></button>
            </div>
            <div class="modal-body p-0" id="bookingDetailsContent" style="max-height:70vh;overflow-y:auto;">
                <div class="text-center py-5">
                    <div class="spinner-border text-warning spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>
                    <p class="mt-2 text-muted" style="font-size:12px;">Loading...</p>
                </div>
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

// Store modal instance
let bookingModal = null;

// Initialize modal when document is ready
document.addEventListener('DOMContentLoaded', function() {

    document.addEventListener("click", function(e) {
        const suppBtn = e.target.closest(".open-supp-btn");
        if (suppBtn) {
            e.preventDefault();
            if (typeof window.openCustomerSupportModal === "function") {
                window.openCustomerSupportModal({
                    id: suppBtn.dataset.id,
                    vehicle_model: suppBtn.dataset.model
                });
            }
        }

        const revBtn = e.target.closest(".open-rev-btn");
        if (revBtn) {
            e.preventDefault();
            if (typeof window.openCustomerReviewModal === "function") {
                window.openCustomerReviewModal({
                    id: revBtn.dataset.id,
                    vehicle_model: revBtn.dataset.model,
                    package_name: revBtn.dataset.package
                });
            }
        }
    });

    document.addEventListener("click", function(e) {
        const btn = e.target.closest(".open-pay-btn");
        if (btn) {
            e.preventDefault();
            const bId = btn.dataset.id;
            if (bId) {
                window.location.href = BASE_URL + "/api/create_paymongo_checkout.php?booking_id=" + bId + "&redirect=1";
            }
        }
    });

    // Auto-redirect to PayMongo Checkout if pay_modal parameter is present
    const payParams = new URLSearchParams(window.location.search);
    if (payParams.get("pay_modal") === "1") {
        const bId = payParams.get("id") || payParams.get("booking_id");
        if (bId) {
            window.location.href = BASE_URL + "/api/create_paymongo_checkout.php?booking_id=" + bId + "&redirect=1";
        }
    }

    const modalElement = document.getElementById('bookingDetailsModal');
    bookingModal = new bootstrap.Modal(modalElement);
    
    // Reset modal content when hidden
    modalElement.addEventListener('hidden.bs.modal', function() {
        resetModalContent();
    });
    
    // Add click handlers to all view details buttons
    document.querySelectorAll('.view-details-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const bookingId = this.dataset.bookingId;
            if (bookingId) {
                viewBookingDetails(bookingId);
            }
        });
    });
});

// Function to reset modal content to loading state
function resetModalContent() {
    const contentDiv = document.getElementById('bookingDetailsContent');
    if (contentDiv) {
        contentDiv.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-warning" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading booking details...</p>
            </div>
        `;
    }
}

// Function to load booking details via AJAX
function viewBookingDetails(bookingId) {
    const contentDiv = document.getElementById('bookingDetailsContent');
    
    // Reset content to loading state
    resetModalContent();
    
    // Show modal
    bookingModal.show();
    
    // Set a timeout to prevent infinite loading
    const timeoutId = setTimeout(() => {
        if (contentDiv.innerHTML.includes('spinner-border')) {
            contentDiv.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Request timed out. Please try again.
                    <button class="btn btn-sm btn-outline-danger mt-2" onclick="viewBookingDetails(${bookingId})">
                        <i class="fas fa-redo me-1"></i>Retry
                    </button>
                </div>
            `;
        }
    }, 10000); // 10 second timeout
    
    // Fetch booking details
    fetch('../api/get_booking_details.php?id=' + bookingId)
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            contentDiv.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            contentDiv.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error loading booking details: ${error.message}
                    <button class="btn btn-sm btn-outline-danger mt-2" onclick="viewBookingDetails(${bookingId})">
                        <i class="fas fa-redo me-1"></i>Retry
                    </button>
                    <div class="mt-2">
                        <a href="../api/get_booking_details.php?id=${bookingId}" class="text-danger" target="_blank">
                            <i class="fas fa-external-link-alt me-1"></i>Open in new window
                        </a>
                    </div>
                </div>
            `;
        });
}

// Auto-open modal if view_booking parameter is present
<?php if($auto_view_booking > 0): ?>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        viewBookingDetails(<?= $auto_view_booking ?>);
    }, 500);
});
<?php endif; ?>

// Manual close function (optional)
function closeBookingModal() {
    if (bookingModal) {
        bookingModal.hide();
    }
}

/* ═══════════════════════════════════════════════════
   RESPOND TO ADMIN TRANSFER
   ═══════════════════════════════════════════════════ */
async function respondTransfer(bookingId, action, btn) {
    const banner = document.getElementById('tbanner-' + bookingId);
    const allBtns = banner ? banner.querySelectorAll('button') : [];

    // Disable all buttons while working
    allBtns.forEach(b => { b.disabled = true; });

    const form = new FormData();
    form.append('booking_id', bookingId);
    form.append('action', action);

    try {
        const res  = await fetch('../api/respond_transfer.php', { method: 'POST', body: form, credentials: 'same-origin' });
        const data = await res.json();

        if (!data.success) {
            alert(data.message || 'Something went wrong. Please try again.');
            allBtns.forEach(b => { b.disabled = false; });
            return;
        }

        if (action === 'accept') {
            // Replace banner with a calm confirmation
            if (banner) {
                banner.style.background = 'linear-gradient(135deg,#ecfdf5,#d1fae5)';
                banner.style.borderColor = '#10b981';
                banner.innerHTML = `
                    <div class="tb-header" style="color:#065f46;">
                        <i class="fas fa-circle-check" style="color:#10b981;"></i>
                        Schedule Accepted
                    </div>
                    <div class="tb-detail" style="color:#064e3b;">
                        You confirmed your appointment for <strong>${data.new_date}</strong> at <strong>${data.new_time}</strong>. See you then!
                    </div>`;
            }
        } else {
            // pick_own — redirect to reschedule page
            window.location.href = data.redirect;
        }
    } catch (err) {
        console.error('respondTransfer error:', err);
        alert('Network error. Please try again.');
        allBtns.forEach(b => { b.disabled = false; });
    }
}
</script>

<?php if ($show_success_modal && $success_booking): ?>

<div class="modal fade show" id="paymentSuccessModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.65); z-index: 9999;" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content" style="background: #11111f; color: #ffffff; border: 1px solid rgba(212,160,23,0.25); border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.6);">
            <div class="modal-header text-center" style="background: #198754; border-bottom: none; padding: 1.75rem 1.25rem 1.25rem; flex-direction: column; align-items: center; position: relative;">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="closeSuccessModal()" style="position: absolute; right: 15px; top: 15px;"></button>
                <div style="width: 48px; height: 48px; border-radius: 50%; background: #ffffff; display: flex; align-items: center; justify-content: center; color: #198754; font-size: 1.5rem; margin-bottom: 0.75rem; box-shadow: 0 3px 8px rgba(0,0,0,0.12);">
                    <i class="fas fa-check"></i>
                </div>
                <h5 class="modal-title fw-bold" style="font-size: 1.35rem; margin-bottom: 0.15rem;">Booking Confirmed!</h5>
                <p style="margin: 0; font-size: 0.8rem; color: rgba(255,255,255,0.85);">Your professional tint appointment is scheduled.</p>
            </div>
            <div class="modal-body" style="padding: 1.25rem 1.5rem; font-size: 0.82rem; color: #ffffff;">
                
                
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; color: rgba(255,255,255,0.85);">
                        #<?= htmlspecialchars($success_booking['booking_ref']) ?>
                    </span>
                    <span style="background: rgba(25,135,84,0.15); border: 1px solid rgba(25,135,84,0.25); padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; color: #198754;">
                        Paid (Online)
                    </span>
                </div>

                
                <div style="border-bottom: 1px dashed rgba(255,255,255,0.08); padding-bottom: 0.75rem; margin-bottom: 0.75rem;">
                    <div style="color: #D4A017; font-weight: 700; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;"><i class="fas fa-calendar-check me-1"></i>Appointment</div>
                    <div class="d-flex justify-content-between py-1" style="color: rgba(255,255,255,0.8);">
                        <span style="color: rgba(255,255,255,0.5);">Date & Time:</span>
                        <span style="font-weight: 600; color: #ffffff;"><?= date('F j, Y \a\t h:i A', strtotime($success_booking['appointment_date'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1" style="color: rgba(255,255,255,0.8);">
                        <span style="color: rgba(255,255,255,0.5);">Package:</span>
                        <span style="font-weight: 600; color: #ffffff;"><?= htmlspecialchars($success_booking['package_name']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1" style="color: rgba(255,255,255,0.8);">
                        <span style="color: rgba(255,255,255,0.5);">Vehicle Model:</span>
                        <span style="font-weight: 600; color: #ffffff;"><?= htmlspecialchars($success_booking['vehicle_model']) ?></span>
                    </div>
                </div>

                
                <div style="border-bottom: 1px dashed rgba(255,255,255,0.08); padding-bottom: 0.75rem; margin-bottom: 0.75rem;">
                    <div style="color: #D4A017; font-weight: 700; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;"><i class="fas fa-credit-card me-1"></i>Payment Info</div>
                    <div class="d-flex justify-content-between py-1" style="color: rgba(255,255,255,0.8);">
                        <span style="color: rgba(255,255,255,0.45);">Method:</span>
                        <span style="font-weight: 600; color: #ffffff;"><?= htmlspecialchars($success_booking['payment_method']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1" style="color: rgba(255,255,255,0.8);">
                        <span style="color: rgba(255,255,255,0.45);">Payment Type:</span>
                        <span style="font-weight: 600; color: #ffffff;"><?= $success_booking['payment_type'] === 'full' ? 'Full Payment' : 'Down Payment (10%)' ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1" style="color: rgba(255,255,255,0.8);">
                        <span style="color: rgba(255,255,255,0.45);">Reference ID:</span>
                        <span style="font-size: 0.76rem; word-break: break-all; font-family: monospace; color: rgba(255,255,255,0.7);"><?= htmlspecialchars($success_booking['payment_reference']) ?></span>
                    </div>
                </div>

                
                <div style="background: rgba(212,160,23,0.06); border: 1px solid rgba(212,160,23,0.18); border-radius: 10px; padding: 0.75rem 1rem;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span style="font-weight: 700; color: #D4A017; font-size: 0.88rem;">Total Price</span>
                        <span style="font-size: 1.25rem; font-weight: 800; color: #D4A017;">₱<?= number_format($success_booking['total_price'], 2) ?></span>
                    </div>
                    <?php if($success_booking['payment_type'] === 'downpayment'): 
                        $dep_paid = (float)($success_booking['deposit_amount'] > 0 ? $success_booking['deposit_amount'] : round($success_booking['total_price'] * 0.10, 2));
                        $bal_due = max(0, (float)$success_booking['total_price'] - $dep_paid);
                    ?>
                        <div style="font-size: 0.7rem; color: rgba(255,255,255,0.4); text-align: right; margin-top: 2px;">10% deposit paid (₱<?= number_format($dep_paid, 2) ?>). Balance due on appointment: <strong>₱<?= number_format($bal_due, 2) ?></strong></div>
                    <?php else: ?>
                        <div style="font-size: 0.7rem; color: rgba(255,255,255,0.4); text-align: right; margin-top: 2px;">Fully paid online via GCash</div>
                    <?php endif; ?>
                </div>

            </div>
            <div class="modal-footer" style="border-top: none; padding: 0.5rem 1.5rem 1.5rem; justify-content: center;">
                <button type="button" class="btn btn-warning fw-bold px-4 py-2" onclick="closeSuccessModal()" style="border-radius: 8px; font-size: 0.85rem; box-shadow: 0 4px 12px rgba(212,160,23,0.25);">Got it</button>
            </div>
        </div>
    </div>
</div>

<div class="modal-backdrop fade show" id="paymentSuccessBackdrop" style="background-color: #000; opacity: 0.65; z-index: 9998;"></div>

<script>
function closeSuccessModal() {
    document.getElementById('paymentSuccessModal').style.display = 'none';
    const backdrop = document.getElementById('paymentSuccessBackdrop');
    if(backdrop) backdrop.remove();
    
    const url = new URL(window.location);
    url.searchParams.delete('payment_success');
    url.searchParams.delete('booking_id');
    window.history.replaceState({}, document.title, url);
}
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/toast.php'; ?>
<?php include __DIR__ . '/../includes/chatbot.php'; ?>
<?php include __DIR__ . '/../includes/customer_modals.php'; ?>

</body>
</html>
