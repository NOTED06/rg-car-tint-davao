<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit;
}

$user_id    = (int)$_SESSION['user_id'];
$booking_id = (int)($_POST['booking_id'] ?? 0);
$ref_number = trim($_POST['reference_number'] ?? '');
$pay_type   = in_array($_POST['payment_type'] ?? '', ['full','downpayment']) ? $_POST['payment_type'] : 'full';

if ($booking_id <= 0 || empty($ref_number)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']); exit;
}

if (!preg_match('/^[A-Za-z0-9\-_\. ]{4,50}$/', $ref_number)) {
    echo json_encode(['success' => false, 'message' => 'Invalid reference number format.']); exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, user_id, status, hold_expires_at, total_price, deposit_amount, payment_status FROM bookings WHERE id = ? AND user_id = ?");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']); exit;
}

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking not found.']); exit;
}

if ($booking['status'] !== 'On Hold') {
    echo json_encode(['success' => false, 'message' => 'This booking is no longer on hold.']); exit;
}

if (!empty($booking['hold_expires_at']) && strtotime($booking['hold_expires_at']) < time()) {
    
    try {
        $pdo->prepare("UPDATE bookings SET status='Cancelled', cancellation_reason='Hold expired', updated_at=NOW() WHERE id=? AND status='On Hold'")->execute([$booking_id]);
    } catch (PDOException $e) {}
    echo json_encode(['success' => false, 'message' => 'Hold time expired. Please book again.']); exit;
}

if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form limit.',
        UPLOAD_ERR_PARTIAL    => 'File only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file.',
    ];
    $err_code = $_FILES['payment_proof']['error'] ?? UPLOAD_ERR_NO_FILE;
    $err_msg  = $upload_errors[$err_code] ?? 'Upload error.';
    echo json_encode(['success' => false, 'message' => $err_msg]); exit;
}

$file      = $_FILES['payment_proof'];
$file_size = $file['size'];
$tmp_path  = $file['tmp_name'];

if ($file_size > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum allowed is 5MB.']); exit;
}

$finfo     = new finfo(FILEINFO_MIME_TYPE);
$mime_type = $finfo->file($tmp_path);
$allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mime_type, $allowed_mimes, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, WEBP images are allowed.']); exit;
}

$ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
$ext     = $ext_map[$mime_type];

$safe_filename = 'proof_' . $booking_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$upload_dir    = __DIR__ . '/../uploads/payment_proofs/';
$dest_path     = $upload_dir . $safe_filename;

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if (!move_uploaded_file($tmp_path, $dest_path)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file. Please try again.']); exit;
}

$total   = (float)$booking['total_price'];
$deposit = (float)($booking['deposit_amount'] ?: round($total * 0.10, 2));
$amount_paid = ($pay_type === 'downpayment') ? $deposit : $total;

try {
    $pdo->beginTransaction();

    $pdo->prepare("
        UPDATE bookings 
        SET payment_status   = 'Proof Submitted',
            payment_reference = ?,
            payment_type      = ?,
            payment_proof     = ?,
            updated_at        = NOW()
        WHERE id = ? AND user_id = ? AND status = 'On Hold'
    ")->execute([$ref_number, $pay_type, 'uploads/payment_proofs/' . $safe_filename, $booking_id, $user_id]);

    
    $pdo->prepare("
        UPDATE payment_transactions 
        SET reference_number = ?, status = 'Proof Submitted', amount = ?, updated_at = NOW()
        WHERE booking_id = ? AND user_id = ?
    ")->execute([$ref_number, $amount_paid, $booking_id, $user_id]);

    
    try {
        $pdo->prepare("INSERT INTO notifications (user_id,user_type,title,message,type,is_read,related_id,related_type) VALUES (?,'customer','Payment Proof Submitted','Your payment proof has been submitted and is awaiting admin verification. We will notify you once it is reviewed.','info',0,?,'booking')")->execute([$user_id, $booking_id]);
    } catch (PDOException $e) {}

    
    try {
        $bk_ref_row = $pdo->prepare("SELECT booking_ref FROM bookings WHERE id=?");
        $bk_ref_row->execute([$booking_id]);
        $bk_ref_data = $bk_ref_row->fetch(PDO::FETCH_ASSOC);
        $admin_msg = 'Payment proof submitted for Booking ' . ($bk_ref_data['booking_ref'] ?? '#'.$booking_id) . '. Please verify.';
        $pdo->prepare("INSERT INTO notifications (user_type,title,message,type,is_read,related_id,related_type) VALUES ('admin','New Payment Proof',?,'info',0,?,'booking')")->execute([$admin_msg, $booking_id]);
    } catch (PDOException $e) {}

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Proof submitted successfully.']);
} catch (PDOException $e) {
    $pdo->rollBack();
    
    if (file_exists($dest_path)) { unlink($dest_path); }
    error_log("Upload proof DB error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}
