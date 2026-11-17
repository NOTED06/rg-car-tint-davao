<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$booking_id = (int)($_POST['booking_id'] ?? 0);
$new_status = trim($_POST['status'] ?? 'Approved');

if ($booking_id <= 0) {
    http_response_code(400);
    exit("Invalid booking_id");
}

$stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
$stmt->execute([$new_status, $booking_id]);

$stmt = $pdo->prepare("SELECT user_id FROM bookings WHERE id = ?");
$stmt->execute([$booking_id]);
$user_id = (int)$stmt->fetchColumn();

if ($user_id > 0) {
    $title = "Booking " . $new_status;
    $msg = "Your booking #{$booking_id} status has been updated to {$new_status}.";
    $notif_type = (in_array(strtolower($new_status), ['declined', 'cancelled'])) ? 'danger' : 'info';

    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, user_type, type, title, message, is_read, created_at)
        VALUES (?, 'customer', ?, ?, ?, 0, NOW())
    ");
    $stmt->execute([$user_id, $notif_type, $title, $msg]);
}

echo "OK";

