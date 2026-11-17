<?php
require_once __DIR__ . '/HttpSmsGateway.php';

function sendBookingSMS($pdo, $booking_id, $status, $reason = '') {
    try {
        
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();
        if (!$booking) return false;

        
        $phone = $booking['phone'] ?? '';
        $name = $booking['customer_name'] ?? '';
        
        if (empty($phone) && !empty($booking['customer_id'])) {
            $cust_stmt = $pdo->prepare("SELECT phone, customer_name FROM customers WHERE id = ?");
            $cust_stmt->execute([$booking['customer_id']]);
            $cust = $cust_stmt->fetch();
            if ($cust) {
                $phone = $cust['phone'] ?? '';
                if (empty($name)) {
                    $name = $cust['customer_name'] ?? '';
                }
            }
        }

        if (empty($phone)) {
            return false;
        }

        $car = $booking['vehicle_model'] ?? 'your car';
        $date = isset($booking['appointment_date']) ? date('M d, Y', strtotime($booking['appointment_date'])) : '';
        $time = $booking['appointment_time'] ?? '';
        
        $text = "";
        if ($status === 'Pending') {
            $text = "RG Cartint Works: Hi {$name}! Your booking (Ref: #{$booking_id}) for {$car} on {$date} at {$time} has been received and is currently PENDING review. Thank you!";
        } else if ($status === 'Approved') {
            $text = "RG Cartint Works: Hi {$name}! Good news! Your booking (Ref: #{$booking_id}) for {$car} on {$date} at {$time} has been APPROVED. See you soon!";
        } else if ($status === 'Completed') {
            $text = "RG Cartint Works: Hi {$name}! Your booking (Ref: #{$booking_id}) for {$car} on {$date} at {$time} is now COMPLETED. Thank you for choosing RG Cartint Works!";
        } else if ($status === 'Cancelled') {
            $text = "RG Cartint Works: Hi {$name}! Your booking (Ref: #{$booking_id}) for {$car} on {$date} at {$time} has been CANCELLED.";
        } else if ($status === 'Declined') {
            $text = "RG Cartint Works: Hi {$name}! Unfortunately, we could not accept your booking (Ref: #{$booking_id}) for {$car} on {$date} at {$time}. Reason: {$reason}.";
        }

        if (empty($text)) return false;

        $gateway = new HttpSmsGateway($pdo);
        return $gateway->sendSMS($phone, $text);
    } catch (Exception $e) {
        error_log("Failed to send automatic SMS: " . $e->getMessage());
        return false;
    }
}
