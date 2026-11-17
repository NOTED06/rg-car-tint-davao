<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["count" => 0]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE user_id = ? AND status = 'Unread'
    ");
    $stmt->execute([$_SESSION["user_id"]]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(["count" => $result["count"] ?? 0]);
} catch (PDOException $e) {
    error_log("Notification count error: " . $e->getMessage());
    echo json_encode(["count" => 0]);
}

