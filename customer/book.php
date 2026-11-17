<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_customer_auth('../login.php?open_signin=1');

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = trim($_POST['name'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $car_model      = trim($_POST['model'] ?? '');
    $vehicle_type   = trim($_POST['vehicle_type'] ?? 'sedan');
    $package_id     = (int)($_POST['package'] ?? 0);
    $date           = trim($_POST['date'] ?? '');
    $time           = trim($_POST['time'] ?? '');
    $notes          = trim($_POST['notes'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'GCash');
    $payment_type   = trim($_POST['payment_type'] ?? 'full');
    $tint_type      = trim($_POST['selected_tint_type'] ?? 'carbon');
    $shade          = (int)($_POST['selected_shade'] ?? 35);
    $custom_data    = $_POST['customization_data'] ?? '';

    
    if (empty($name) || empty($phone) || empty($car_model) || $package_id <= 0 || empty($date) || empty($time)) {
        header("Location: dashboard.php?error=missing_fields");
        exit;
    }

    
    $vehicle_type_id = 1; 
    try {
        $vt_stmt = $pdo->prepare("SELECT id FROM vehicle_types WHERE LOWER(type_name) = ? OR LOWER(price_category) = ? LIMIT 1");
        $vt_stmt->execute([strtolower($vehicle_type), strtolower($vehicle_type)]);
        $vt_id = $vt_stmt->fetchColumn();
        if ($vt_id) $vehicle_type_id = (int)$vt_id;
    } catch (Exception $e) {}

    
    $total_price = 4500.00;
    try {
        $combo = $tint_type . '|' . $shade;
        $pmp_stmt = $pdo->prepare("SELECT price FROM package_model_prices WHERE package_id = ? AND car_model = ? LIMIT 1");
        $pmp_stmt->execute([$package_id, $car_model]);
        $pmp_price = $pmp_stmt->fetchColumn();

        if ($pmp_price && (float)$pmp_price > 0) {
            $total_price = (float)$pmp_price;
        } else {
            $pkg_stmt = $pdo->prepare("SELECT sedan_price, suv_price, van_price, price FROM tint_packages WHERE id = ?");
            $pkg_stmt->execute([$package_id]);
            $pkg = $pkg_stmt->fetch(PDO::FETCH_ASSOC);
            if ($pkg) {
                if (strtolower($vehicle_type) === 'suv' && (float)($pkg['suv_price'] ?? 0) > 0) {
                    $total_price = (float)$pkg['suv_price'];
                } elseif (strtolower($vehicle_type) === 'van' && (float)($pkg['van_price'] ?? 0) > 0) {
                    $total_price = (float)$pkg['van_price'];
                } elseif ((float)($pkg['sedan_price'] ?? 0) > 0) {
                    $total_price = (float)$pkg['sedan_price'];
                } else {
                    $total_price = (float)($pkg['price'] ?? 4500);
                }
            }
        }
    } catch (Exception $e) {}

    
    if (!empty($custom_data)) {
        $cd = json_decode($custom_data, true);
        if (is_array($cd) && isset($cd['total']) && (float)$cd['total'] > 0) {
            $total_price = (float)$cd['total'];
        }
    }

    $deposit_amount = ($payment_type === 'downpayment') ? round($total_price * 0.10, 2) : $total_price;
    $booking_ref = 'RG-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 5));

    
    try {
        $ins = $pdo->prepare("
            INSERT INTO bookings (
                reference_code, booking_ref, user_id, customer_id, customer_name, phone, address,
                car_model, vehicle_model, vehicle_type_id, booking_type, package_id,
                total_price, deposit_amount, payment_type, payment_method, payment_status,
                appointment_date, appointment_time, notes, tint_type, shade_percentage,
                customization_data, status, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, 'full_package', ?,
                ?, ?, ?, ?, 'Pending',
                ?, ?, ?, ?, ?,
                ?, 'Pending', NOW()
            )
        ");
        $ins->execute([
            $booking_ref, $booking_ref, $user_id, $user_id, $name, $phone, $address,
            $car_model, $car_model, $vehicle_type_id, $package_id,
            $total_price, $deposit_amount, $payment_type, $payment_method,
            $date, $time, $notes, $tint_type, $shade,
            $custom_data
        ]);

        $new_booking_id = $pdo->lastInsertId();

        
        $upd_u = $pdo->prepare("UPDATE users SET phone = COALESCE(NULLIF(phone,''), ?), address = COALESCE(NULLIF(address,''), ?) WHERE id = ?");
        $upd_u->execute([$phone, $address, $user_id]);

        
        try {
            $notif = $pdo->prepare("INSERT INTO notifications (user_id, user_type, title, message, type, related_id, related_type, created_at) VALUES (?, 'customer', 'Booking Submitted', ?, 'info', ?, 'booking', NOW())");
            $notif->execute([$user_id, "Your appointment for {$car_model} on {$date} at {$time} was submitted successfully.", $new_booking_id]);

            $admin_notif = $pdo->prepare("INSERT INTO notifications (user_type, title, message, type, related_id, related_type, created_at) VALUES ('admin', 'New Online Booking', ?, 'info', ?, 'booking', NOW())");
            $admin_notif->execute(["New booking #{$booking_ref} by {$name} for {$car_model}.", $new_booking_id]);
        } catch (Exception $e) {}

        
        if (in_array(strtolower($payment_method), ['gcash', 'card', 'maya', 'paymongo', 'e-wallet'])) {
            try {
                require_once __DIR__ . '/../includes/PayMongo.php';
                $paymongo = new PayMongo($pdo);

                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/capstone%20project/customer/book.php');
                $script_dir = str_replace(' ', '%20', dirname(dirname($script_name)));
                $base_url = rtrim($protocol . $host . $script_dir, '/');

                $success_url = $base_url . "/customer/paymongo_callback.php?type=success&booking_id=" . $new_booking_id;
                $failed_url  = $base_url . "/customer/paymongo_callback.php?type=failed&booking_id=" . $new_booking_id;

                $desc = "RG CARTINTWORKS Booking #" . $booking_ref;
                $pay_res = $paymongo->createGcashSource($deposit_amount, $desc, $success_url, $failed_url, [
                    'booking_id' => (string)$new_booking_id,
                    'user_id' => (string)$user_id
                ]);

                if (isset($pay_res['status']) && $pay_res['status'] === 'success' && !empty($pay_res['checkout_url'])) {
                    
                    $upd_pm = $pdo->prepare("UPDATE bookings SET payment_reference = ?, payment_details = ? WHERE id = ?");
                    $upd_pm->execute([$pay_res['source_id'], json_encode($pay_res), $new_booking_id]);

                    
                    header("Location: " . $pay_res['checkout_url']);
                    exit;
                }
            } catch (Exception $e) {
                error_log("PayMongo instant checkout error: " . $e->getMessage());
            }

            
            header("Location: my_bookings.php?pay_modal=1&booking_id=" . $new_booking_id);
            exit;
        } else {
            header("Location: my_bookings.php?created=1&booking_id=" . $new_booking_id);
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error creating booking: " . $e->getMessage());
        header("Location: dashboard.php?error=booking_failed");
        exit;
    }
}

$pkg_id = $_GET['package_id'] ?? '';
$car_model = $_GET['car_model'] ?? '';
$shade = $_GET['shade'] ?? '';

$params = ['open_booking' => 1];
if (!empty($pkg_id))    $params['package_id'] = $pkg_id;
if (!empty($car_model)) $params['car_model']  = $car_model;
if (!empty($shade))     $params['shade']      = $shade;

header("Location: dashboard.php?" . http_build_query($params));
exit;
