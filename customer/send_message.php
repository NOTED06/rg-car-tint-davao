<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$user_id = (int)$_SESSION['user_id'];
$message = trim($_POST['message'] ?? '');

if ($message === '') {
    http_response_code(400);
    exit("Message required");
}

$stmt = $pdo->prepare("
    INSERT INTO chat_messages (customer_id, sender_type, message, created_at)
    VALUES (?, 'customer', ?, NOW())
");
$stmt->execute([$user_id, $message]);

$title = "New customer message";
$msg = "Customer #{$user_id}: " . mb_strimwidth($message, 0, 80, "…");

$stmt = $pdo->prepare("
    INSERT INTO notifications (user_type, title, message, type, related_id, related_type, is_read, created_at)
    VALUES ('admin', ?, ?, 'info', ?, 'user', 0, NOW())
");
$stmt->execute([$title, $msg, $user_id]);

echo "OK";

