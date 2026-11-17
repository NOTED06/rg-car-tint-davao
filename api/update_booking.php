<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$booking_id = (int)($_POST['booking_id'] ?? 0);
$new_status = $_POST['status'] ?? ''; 
$customer_id = (int)($_POST['customer_id'] ?? 0);

if (!$booking_id || !$customer_id || !in_array($new_status, ['approved', 'declined', 'cancelled'])) {
    echo json_encode(["status" => "error", "message" => "Invalid Request"]);
    exit;
}

try {
    $pdo->beginTransaction();

    
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $booking_id]);

    
    $content = [
        'approved'  => ['title' => '✅ Booking Approved!', 'type' => 'success', 'msg' => 'Great news! Your booking has been confirmed.'],
        'declined'  => ['title' => '❌ Booking Declined', 'type' => 'danger',  'msg' => 'Unfortunately, we could not accept your booking at this time.'],
        'cancelled' => ['title' => '⚠️ Booking Cancelled', 'type' => 'warning', 'msg' => 'Your booking request has been cancelled.']
    ];

    $title = $content[$new_status]['title'];
    $type  = $content[$new_status]['type'];
    $message = $content[$new_status]['msg'];

    
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, user_type, type, title, message, is_read, created_at) 
                           VALUES (?, 'customer', ?, ?, ?, 0, NOW())");
    $stmt->execute([$customer_id, $type, $title, $message]);

    $pdo->commit();
    echo json_encode(["status" => "success", "message" => "Customer notified and status updated."]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
