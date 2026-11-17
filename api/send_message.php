<?php

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401); 
    echo json_encode([
        'status' => 'error',
        'message' => 'Your session has expired. Please log in again.'
    ]);
    exit;
}

$customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
$message     = trim($_POST['message'] ?? '');

if ($customer_id <= 0 || empty($message)) {
    http_response_code(400); 
    echo json_encode([
        'status' => 'error',
        'message' => 'Please provide a valid customer ID and message.'
    ]);
    exit;
}

try {
    
    $pdo->beginTransaction();

    
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (customer_id, sender_type, message, created_at)
        VALUES (?, 'admin', ?, NOW())
    ");
    $stmt->execute([$customer_id, $message]);

    
    $preview = mb_strimwidth($message, 0, 80, "…");
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, user_type, type, title, message, is_read, created_at)
        VALUES (?, 'customer', 'info', 'New message from Admin', ?, 0, NOW())
    ");
    $stmt->execute([$customer_id, $preview]);

    
    $pdo->commit();

    
    echo json_encode([
        'status' => 'success',
        'time' => date('H:i'),
        'text' => $message
    ]);

} catch (Exception $e) {
    
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
