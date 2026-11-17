<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
boot_session();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please log in.']);
    exit;
}

$booking_id = filter_var($_REQUEST['booking_id'] ?? 0, FILTER_VALIDATE_INT);
if (!$booking_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid booking ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND (user_id = ? OR customer_id = ?)");
    $stmt->execute([$booking_id, $_SESSION['user_id'], $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['success' => false, 'error' => 'Booking record not found']);
        exit;
    }

    $amount = (float)($booking['payment_type'] === 'downpayment' ? ($booking['deposit_amount'] > 0 ? $booking['deposit_amount'] : round($booking['total_price'] * 0.10, 2)) : $booking['total_price']);
    if ($amount < 100) {
        $amount = 100.00; 
    }

    require_once __DIR__ . '/../includes/PayMongo.php';
    $paymongo = new PayMongo($pdo);

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/capstone%20project/api/create_paymongo_checkout.php');
    $script_dir = str_replace(' ', '%20', dirname(dirname($script_name)));
    $base_url = rtrim($protocol . $host . $script_dir, '/');

    $success_url = $base_url . "/customer/paymongo_callback.php?type=success&booking_id=" . $booking_id;
    $failed_url  = $base_url . "/customer/paymongo_callback.php?type=failed&booking_id=" . $booking_id;

    $desc = "RG CARTINTWORKS Booking #" . ($booking['booking_ref'] ?: $booking_id);
    $res = $paymongo->createGcashSource($amount, $desc, $success_url, $failed_url, [
        'booking_id' => (string)$booking_id,
        'user_id' => (string)$_SESSION['user_id']
    ]);

    if (isset($res['status']) && $res['status'] === 'success' && !empty($res['checkout_url'])) {
        
        $upd = $pdo->prepare("UPDATE bookings SET payment_reference = ?, payment_details = ? WHERE id = ?");
        $upd->execute([$res['source_id'], json_encode($res), $booking_id]);

        if (isset($_GET['redirect']) && $_GET['redirect'] == 1) {
            header("Location: " . $res['checkout_url']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'checkout_url' => $res['checkout_url'],
            'source_id' => $res['source_id']
        ]);
        exit;
    } else {
        $errMsg = $res['msg'] ?? 'Failed to create PayMongo GCash checkout source.';
        if (isset($_GET['redirect']) && $_GET['redirect'] == 1) {
            header("Location: ../customer/my_bookings.php?payment_error=" . urlencode($errMsg) . "&booking_id=" . $booking_id);
            exit;
        }
        echo json_encode([
            'success' => false,
            'error' => $errMsg
        ]);
        exit;
    }
} catch (Exception $e) {
    $errMsg = 'PayMongo Checkout Error: ' . $e->getMessage();
    if (isset($_GET['redirect']) && $_GET['redirect'] == 1) {
        header("Location: ../customer/my_bookings.php?payment_error=" . urlencode($errMsg) . "&booking_id=" . $booking_id);
        exit;
    }
    echo json_encode([
        'success' => false,
        'error' => $errMsg
    ]);
    exit;
}
