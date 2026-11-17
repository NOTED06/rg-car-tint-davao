<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_customer_auth('../login.php');

$booking_id = (int)($_GET['id'] ?? 0);
if ($booking_id > 0) {
    header("Location: ../api/create_paymongo_checkout.php?booking_id=" . $booking_id . "&redirect=1");
} else {
    header("Location: my_bookings.php");
}
exit;
