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

$booking_id = (int)$_POST['booking_id'];
$customer_id = (int)$_POST['customer_id'];
$action = $_POST['action']; 

$templates = [
    'approve' => ['status' => 'approved', 'type' => 'success', 'title' => '✅ Booking Approved!', 'msg' => 'Great news! Your booking has been confirmed.'],
    'decline' => ['status' => 'declined', 'type' => 'danger',  'title' => '❌ Booking Declined', 'msg' => 'Sorry, we are unable to accept your booking at this time.'],
    'cancel'  => ['status' => 'cancelled', 'type' => 'warning', 'title' => '⚠️ Booking Cancelled', 'msg' => 'Your booking request was successfully cancelled.']
];

if (!isset($templates[$action])) {
    echo json_encode(["status" => "error", "message" => "Invalid Action"]);
    exit;
}

$tpl = $templates[$action];

try {
    $pdo->beginTransaction();

    
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$tpl['status'], $booking_id]);

    
    if ($customer_id <= 0) {
        $stmt = $pdo->prepare("SELECT user_id FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $customer_id = (int)$stmt->fetchColumn();
    }

    
    if ($customer_id > 0) {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, user_type, type, title, message, is_read, related_id, related_type, created_at) VALUES (?, 'customer', ?, ?, ?, 0, ?, 'booking', NOW())");
        $stmt->execute([$customer_id, $tpl['type'], $tpl['title'], $tpl['msg'], $booking_id]);
    }

    $pdo->commit();
    echo json_encode(["status" => "success", "new_status" => $tpl['status']]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
