<?php

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in to submit a support ticket']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$booking_id = filter_var($input['booking_id'] ?? 0, FILTER_VALIDATE_INT);
$subject = trim($input['subject'] ?? 'General Support Inquiry');
$priority = trim($input['priority'] ?? 'medium');
$message = trim($input['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Please describe your inquiry or message.']);
    exit;
}

try {
    
    $stmt = $pdo->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    
    if ($booking_id > 0) {
        $stmt = $pdo->prepare("SELECT booking_ref, vehicle_model FROM bookings WHERE id = ? AND user_id = ?");
        $stmt->execute([$booking_id, $user_id]);
        $b = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($b) {
            $subject .= " (Booking #{$booking_id} - " . ($b['vehicle_model'] ?: 'Vehicle') . ")";
        }
    }

    
    $stmt = $pdo->prepare("
        INSERT INTO customer_services 
        (user_id, customer_id, customer_name, customer_email, customer_phone, subject, message, priority, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new', NOW(), NOW())
    ");
    $stmt->execute([
        $user_id,
        $user_id,
        $user['full_name'] ?? 'Customer',
        $user['email'] ?? null,
        $user['phone'] ?? null,
        $subject,
        $message,
        $priority
    ]);
    $ticket_id = $pdo->lastInsertId();

    
    $admin_msg = "New Support Ticket #" . $ticket_id . " from " . ($user['full_name'] ?? 'Customer') . ": " . mb_substr($subject, 0, 50);
    $pdo->prepare("
        INSERT INTO notifications (user_type, title, message, type, related_id, related_type, is_read, created_at) 
        VALUES ('admin', 'New Support Ticket', ?, 'warning', ?, 'support', 0, NOW())
    ")->execute([$admin_msg, $ticket_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Your support message has been sent to our customer service team!'
    ]);

} catch (PDOException $e) {
    error_log("Submit support error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error submitting support ticket']);
}
