<?php

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require_customer_auth('../login.php');

$type = $_GET['type'] ?? '';
$booking_id = intval($_GET['booking_id'] ?? 0);
$current_user_id = $_SESSION['user_id'];

if ($booking_id <= 0) {
    header("Location: my_bookings.php");
    exit;
}

try {
    
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
    $stmt->execute([$booking_id, $current_user_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        header("Location: my_bookings.php");
        exit;
    }

    $source_id = $booking['payment_reference'] ?? '';

    if (empty($source_id)) {
        error_log("PayMongo callback error: source_id empty for booking ID " . $booking_id);
        header("Location: my_bookings.php?payment_failed=true&booking_id=" . $booking_id);
        exit;
    }

    
    require_once __DIR__ . '/../includes/PayMongo.php';
    $paymongo = new PayMongo($pdo);

    if ($type === 'success') {
        $new_payment_status = ($booking['payment_type'] === 'downpayment') ? 'Paid (Deposit)' : 'Paid';
        $charge_amount = (float)($booking['payment_type'] === 'downpayment' ? ($booking['deposit_amount'] > 0 ? $booking['deposit_amount'] : round($booking['total_price'] * 0.10, 2)) : $booking['total_price']);
        
        
        if (!empty($source_id) && strpos($source_id, 'src_') === 0) {
            try {
                $paymongo->createPayment($source_id, $charge_amount, 'Booking #' . $booking['booking_ref']);
            } catch (Exception $e) {}
        }

        
        $upd = $pdo->prepare("UPDATE bookings SET payment_status = ?, status = 'Pending', hold_expires_at = NULL WHERE id = ?");
        $upd->execute([$new_payment_status, $booking_id]);

        
        try {
            $ins_tx = $pdo->prepare("INSERT INTO payment_transactions (booking_id, user_id, payment_method, amount, reference_number, transaction_id, status, payment_data) VALUES (?, ?, 'GCash', ?, ?, ?, 'Paid', ?)");
            $ins_tx->execute([
                $booking_id,
                $current_user_id,
                $charge_amount,
                $booking['booking_ref'],
                $source_id ?: ('TX-' . time()),
                json_encode(['status' => 'paid', 'source_id' => $source_id])
            ]);
        } catch (Exception $e) {}

        
        try {
            $pdo->prepare("INSERT INTO notifications (user_id, user_type, title, message, type, related_id, related_type, created_at) VALUES (?, 'customer', 'Payment Received', ?, 'success', ?, 'booking', NOW())")
                ->execute([$current_user_id, "Your online payment of ₱" . number_format($charge_amount, 2) . " for booking #" . $booking['booking_ref'] . " was verified successfully!", $booking_id]);
        } catch (Exception $e) {}

        
        header("Location: my_bookings.php?payment_success=true&booking_id=" . $booking_id);
        exit;
    } else {
        
        $pdo->prepare("DELETE FROM bookings WHERE id = ? AND (payment_status = 'Pending' OR payment_status IS NULL OR payment_status = 'Failed')")->execute([$booking_id]);
        header("Location: dashboard.php?open_booking=1&payment_cancelled=1");
        exit;
    }

} catch (Exception $e) {
    error_log("PayMongo callback exception: " . $e->getMessage());
    header("Location: dashboard.php?open_booking=1&payment_failed=1");
    exit;
}
