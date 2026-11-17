<?php

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in to submit a review']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$booking_id = filter_var($input['booking_id'] ?? 0, FILTER_VALIDATE_INT);
$rating = filter_var($input['rating'] ?? 5.0, FILTER_VALIDATE_FLOAT);
$vehicle = trim($input['vehicle'] ?? '');
$tint_type = trim($input['tint_type'] ?? '');
$comment = trim($input['comment'] ?? '');
$would_recommend = !empty($input['would_recommend']) ? 1 : 0;

if (empty($comment)) {
    echo json_encode(['success' => false, 'error' => 'Please provide a short comment sharing your experience.']);
    exit;
}

try {
    
    $stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    
    if ($booking_id > 0) {
        $stmt = $pdo->prepare("SELECT vehicle_model, tint_type FROM bookings WHERE id = ? AND user_id = ?");
        $stmt->execute([$booking_id, $user_id]);
        $b = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($b) {
            if (empty($vehicle)) $vehicle = $b['vehicle_model'];
            if (empty($tint_type)) $tint_type = $b['tint_type'];
        }
    }

    
    $stmt = $pdo->prepare("
        INSERT INTO testimonials 
        (user_id, booking_id, name, email, vehicle, tint_type, rating, comment, would_recommend, is_approved, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
    ");
    $stmt->execute([
        $user_id,
        $booking_id > 0 ? $booking_id : null,
        $user['full_name'] ?? 'Customer',
        $user['email'] ?? null,
        $vehicle ?: null,
        $tint_type ?: null,
        $rating,
        $comment,
        $would_recommend
    ]);

    
    $admin_msg = "Customer " . ($user['full_name'] ?? 'Customer') . " submitted a " . number_format($rating, 1) . "★ review for Booking #" . ($booking_id ?: 'N/A') . ".";
    $pdo->prepare("
        INSERT INTO notifications (user_type, title, message, type, related_id, related_type, is_read, created_at) 
        VALUES ('admin', 'New Customer Review', ?, 'info', ?, 'review', 0, NOW())
    ")->execute([$admin_msg, $user_id]);

    echo json_encode([
        'success' => true, 
        'message' => 'Thank you for your feedback! Your review has been submitted successfully.'
    ]);

} catch (PDOException $e) {
    error_log("Submit review error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error submitting review']);
}
