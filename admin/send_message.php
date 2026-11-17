<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$customer_id = (int)($_POST['customer_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if ($customer_id <= 0 || $message === '') {
    http_response_code(400);
    exit("Invalid data");
}

$stmt = $pdo->prepare("
    INSERT INTO chat_messages (customer_id, sender_type, message, created_at)
    VALUES (?, 'admin', ?, NOW())
");
$stmt->execute([$customer_id, $message]);

$title = "New message from Admin";
$preview = mb_strimwidth($message, 0, 80, "…");

$stmt = $pdo->prepare("
    INSERT INTO notifications (user_id, user_type, type, title, message, is_read, created_at)
    VALUES (?, 'customer', 'info', ?, ?, 0, NOW())
");
$stmt->execute([$customer_id, $title, $preview]);

echo "OK";

