<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$date = $input["date"] ?? "";
$type = $input["type"] ?? "";
$user_id = $_SESSION["user_id"];

if (empty($date) || empty($type)) {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
    exit;
}

try {
    if ($type === "fully_booked") {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, user_type, type, title, message, is_read, created_at) 
            VALUES (?, 'customer', 'warning', 'Fully Booked Notice', ?, 0, NOW())
        ");
        
        $formatted_date = date("F j, Y", strtotime($date));
        $message = "The appointment date {$formatted_date} you attempted to book is now fully booked. Please select another date.";
        
        $stmt->execute([$user_id, $message]);
        
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Invalid notification type"]);
    }
} catch (PDOException $e) {
    error_log("Send notification error: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => "Database error"]);
}

