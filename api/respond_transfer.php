<?php

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id    = (int)$_SESSION['user_id'];
$action     = trim($_POST['action'] ?? '');
$booking_id = (int)($_POST['booking_id'] ?? 0);

if (!$booking_id || !in_array($action, ['accept', 'pick_own'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    
    $stmt = $pdo->prepare("
        SELECT id, appointment_date, appointment_time, transfer_status, admin_transfer_reason
        FROM bookings
        WHERE id = ? AND user_id = ? AND transfer_status = 'pending_customer_approval'
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found or already responded']);
        exit;
    }

    $pdo->beginTransaction();

    if ($action === 'accept') {
        
        $pdo->prepare("
            UPDATE bookings
            SET transfer_status = 'accepted',
                updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ")->execute([$booking_id, $user_id]);

        
        $date_label = date('F j, Y', strtotime($booking['appointment_date']));
        $time_label = $booking['appointment_time'];
        $pdo->prepare("
            INSERT INTO notifications (user_type, type, title, message, is_read, related_id, related_type, created_at)
            VALUES ('admin', 'success', '✅ Customer Accepted Transfer',
                ?, 0, ?, 'booking', NOW())
        ")->execute([
            "Customer accepted the rescheduled slot for booking #{$booking_id}: {$date_label} at {$time_label}.",
            $booking_id
        ]);

        
        $pdo->prepare("
            INSERT INTO notifications (user_id, user_type, type, title, message, is_read, created_at)
            VALUES (?, 'customer', 'success', '✅ Schedule Confirmed', ?, 0, NOW())
        ")->execute([
            $user_id,
            "You confirmed the new schedule for booking #{$booking_id}: {$date_label} at {$time_label}. See you then!"
        ]);

        $pdo->commit();
        echo json_encode([
            'success'    => true,
            'message'    => 'Schedule accepted',
            'new_date'   => date('F j, Y', strtotime($booking['appointment_date'])),
            'new_time'   => $booking['appointment_time'],
        ]);

    } else {
        
        $pdo->prepare("
            UPDATE bookings
            SET transfer_status = 'customer_reschedule',
                updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ")->execute([$booking_id, $user_id]);

        
        $pdo->prepare("
            INSERT INTO notifications (user_type, type, title, message, is_read, related_id, related_type, created_at)
            VALUES ('admin', 'warning', '📅 Customer Picking Own Date',
                ?, 0, ?, 'booking', NOW())
        ")->execute([
            "Customer declined the admin-transferred slot for booking #{$booking_id} and is choosing their own date.",
            $booking_id
        ]);

        $pdo->commit();
        echo json_encode([
            'success'     => true,
            'action'      => 'pick_own',
            'redirect'    => "book.php?action=reschedule&booking_id={$booking_id}",
        ]);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("respond_transfer error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
