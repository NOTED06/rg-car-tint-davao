<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]); exit;
}

$user_id    = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$booking_id = (int)($_POST['booking_id'] ?? 0);

if ($user_id <= 0 || $booking_id <= 0) {
    echo json_encode(['success' => false]); exit;
}

try {
    
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET status = 'Cancelled',
            cancellation_reason = 'Hold expired — no payment proof submitted within 20 minutes',
            updated_at = NOW()
        WHERE id = ? AND user_id = ? AND status = 'On Hold'
          AND hold_expires_at IS NOT NULL AND hold_expires_at < NOW()
    ");
    $stmt->execute([$booking_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        
        try {
            $pdo->prepare("INSERT INTO notifications (user_id,user_type,title,message,type,is_read,related_id,related_type) VALUES (?,'customer','Booking Expired','Your booking reservation has been cancelled — the 20-minute payment window expired. Please book again.','warning',0,?,'booking')")->execute([$user_id, $booking_id]);
        } catch (PDOException $ne) {}
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("expire_hold error: " . $e->getMessage());
    echo json_encode(['success' => false]);
}
