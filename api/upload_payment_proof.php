<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION["user_id"] ?? 0;
if ($user_id <= 0) {
    echo json_encode(["success" => false, "error" => "Unauthorized. Please log in."]);
    exit;
}

$booking_id = (int)($_POST["booking_id"] ?? 0);
$reference  = trim($_POST["reference_number"] ?? "");

if ($booking_id <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid booking ID."]);
    exit;
}

if (empty($reference)) {
    echo json_encode(["success" => false, "error" => "Please enter your GCash Reference Number."]);
    exit;
}

if (!isset($_FILES["proof_file"]) || $_FILES["proof_file"]["error"] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "error" => "Please upload a valid receipt image."]);
    exit;
}

$file = $_FILES["proof_file"];
$ext  = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

if (!in_array($ext, ["jpg", "jpeg", "png", "webp"])) {
    echo json_encode(["success" => false, "error" => "Invalid image format. Allowed: JPG, PNG, WEBP."]);
    exit;
}

$upload_dir = __DIR__ . "/../uploads/payments/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$filename = "proof_" . $booking_id . "_" . time() . "." . $ext;
$target_path = $upload_dir . $filename;
$relative_path = "uploads/payments/" . $filename;

if (!move_uploaded_file($file["tmp_name"], $target_path)) {
    echo json_encode(["success" => false, "error" => "Failed to save uploaded file."]);
    exit;
}

try {
    
    $stmt = $pdo->prepare("SELECT id, booking_ref, total_price, customer_name FROM bookings WHERE id = ? AND user_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(["success" => false, "error" => "Booking not found."]);
        exit;
    }

    
    $upd = $pdo->prepare("UPDATE bookings SET payment_status = 'Proof Submitted', payment_reference = ?, payment_proof = ?, updated_at = NOW() WHERE id = ?");
    $upd->execute([$reference, $relative_path, $booking_id]);

    
    try {
        $msg = "GCash Payment proof submitted for Booking #" . $booking["booking_ref"] . " (Ref: {$reference}). Awaiting admin verification.";
        $pdo->prepare("INSERT INTO notifications (user_id, user_type, title, message, type, is_read, related_id, related_type) VALUES (?, 'customer', 'Payment Proof Uploaded', ?, 'info', 0, ?, 'booking')")->execute([$user_id, $msg, $booking_id]);
        
        $admin_msg = "Customer {$booking['customer_name']} uploaded GCash proof for Booking #" . $booking["booking_ref"] . " (Ref: {$reference}).";
        $pdo->prepare("INSERT INTO notifications (user_type, title, message, type, is_read, related_id, related_type) VALUES ('admin', 'Payment Proof Uploaded', ?, 'info', 0, ?, 'booking')")->execute([$admin_msg, $booking_id]);
    } catch (PDOException $ne) {}

    echo json_encode([
        "success" => true,
        "message" => "Payment proof submitted successfully! Our team will verify your GCash transaction shortly."
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
