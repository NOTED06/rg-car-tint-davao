<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND user_type='customer' AND is_read = 0");
$stmt->execute([$user_id]);
$unread = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT id, type, title, message, is_read, created_at FROM notifications 
                       WHERE user_id = ? AND user_type='customer' 
                       ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["unread" => $unread, "notifications" => $list]);
