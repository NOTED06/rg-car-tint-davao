<?php
require __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_start([
        'cookie_lifetime'  => 7200,
        'cookie_secure'    => $isSecure,
        'cookie_httponly'  => true,
        'cookie_samesite'  => 'Lax',
        'use_strict_mode'  => true,
        'use_only_cookies' => true,
    ]);
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
    session_unset();
    session_destroy();
    header("Location: login.php?expired=1");
    exit;
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

if (isset($_SESSION['ip']) && $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
    header("Location: login.php?hijack=1");
    exit;
}
$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];

$_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    
    unset($_SESSION['admin_logged_in']);
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verify_admin_csrf($redirect_section = ''): bool {
    if (!isset($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Security token invalid or expired. Please refresh the page and try again.'];
        $url = 'index.php' . ($redirect_section ? '#' . ltrim($redirect_section, '#') : '');
        header("Location: $url");
        exit;
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_sms_settings') {
    verify_admin_csrf('sms-gateway');
    try {
        $stmt = $pdo->prepare("INSERT INTO system_info (meta_field, meta_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)");
        
        
        $existing = [];
        $ext_stmt = $pdo->query("SELECT meta_field, meta_value FROM system_info");
        while ($row = $ext_stmt->fetch()) {
            $existing[$row['meta_field']] = $row['meta_value'];
        }

        $sensitive = [
            'semaphore_api_key', 
            'twilio_account_sid', 
            'twilio_auth_token', 
            'smsgate_local_password', 
            'smsgate_cloud_password'
        ];

        $fields = [
            'sms_gateway_type' => $_POST['sms_gateway_type'] ?? 'smsgate',
            'semaphore_api_key' => $_POST['semaphore_api_key'] ?? '',
            'semaphore_sender_name' => $_POST['semaphore_sender_name'] ?? '',
            'twilio_account_sid' => $_POST['twilio_account_sid'] ?? '',
            'twilio_auth_token' => $_POST['twilio_auth_token'] ?? '',
            'twilio_phone_number' => $_POST['twilio_phone_number'] ?? '',
            'smsgate_local_url' => $_POST['smsgate_local_url'] ?? '',
            'smsgate_local_username' => $_POST['smsgate_local_username'] ?? '',
            'smsgate_local_password' => $_POST['smsgate_local_password'] ?? '',
            'smsgate_public_url' => $_POST['smsgate_public_url'] ?? '',
            'smsgate_cloud_url' => $_POST['smsgate_cloud_url'] ?? '',
            'smsgate_device_id' => $_POST['smsgate_device_id'] ?? '',
            'smsgate_cloud_username' => $_POST['smsgate_cloud_username'] ?? '',
            'smsgate_cloud_password' => $_POST['smsgate_cloud_password'] ?? ''
        ];
        
        foreach ($fields as $meta_field => $meta_value) {
            if (in_array($meta_field, $sensitive) && (empty($meta_value) || strpos($meta_value, '***') !== false || $meta_value === '********')) {
                if (isset($existing[$meta_field]) && !empty($existing[$meta_field])) {
                    continue; 
                }
            }
            $stmt->execute([$meta_field, $meta_value]);
        }
        
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'SMS Gateway settings saved successfully!'];
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Error saving SMS settings: ' . $e->getMessage()];
    }
    header("Location: index.php#sms-gateway");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'send_test_sms') {
    verify_admin_csrf('sms-gateway');
    try {
        $to = trim($_POST['test_phone'] ?? '');
        $msg = trim($_POST['test_message'] ?? 'Test message from RG Cartint Works SMS gateway.');
        
        if (empty($to) || empty($msg)) {
            throw new Exception("Recipient and message are required.");
        }
        
        require_once __DIR__ . '/../includes/HttpSmsGateway.php';
        $gateway = new HttpSmsGateway($pdo);
        $res = $gateway->sendSMS($to, $msg);
        
        if ($res['success']) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Test SMS sent successfully! ' . ($res['message'] ?? '')];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Failed to send SMS: ' . ($res['message'] ?? 'Unknown error')];
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Error: ' . $e->getMessage()];
    }
    header("Location: index.php#sms-gateway");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['price_action'])) {
    verify_admin_csrf('packages');
    
    try {
        if ($_POST['price_action'] == 'update') {
            $package_id = filter_var($_POST['package_id'], FILTER_VALIDATE_INT);
            $new_price = filter_var($_POST['new_price'], FILTER_VALIDATE_FLOAT);
            $reason = htmlspecialchars(trim($_POST['reason'] ?? 'Price update'));
            
            if (!$package_id || !$new_price || $new_price <= 0) {
                throw new Exception("Invalid price or package ID");
            }
            
            
            $check_column = $pdo->query("SHOW COLUMNS FROM tint_packages LIKE 'current_price'");
            $has_current_price = $check_column->rowCount() > 0;
            
            if (!$has_current_price) {
                
                $pdo->exec("ALTER TABLE tint_packages ADD COLUMN current_price DECIMAL(10,2) AFTER price");
                $pdo->exec("ALTER TABLE tint_packages ADD COLUMN price_updated_by INT NULL AFTER current_price");
                $pdo->exec("ALTER TABLE tint_packages ADD COLUMN price_updated_at TIMESTAMP NULL AFTER price_updated_by");
                $pdo->exec("ALTER TABLE tint_packages ADD COLUMN can_edit_price BOOLEAN DEFAULT TRUE AFTER price_updated_at");
                $pdo->exec("ALTER TABLE tint_packages ADD COLUMN display_order INT DEFAULT 0 AFTER can_edit_price");
            }
            
            
            $old_price_stmt = $pdo->prepare("SELECT price, current_price FROM tint_packages WHERE id = ?");
            $old_price_stmt->execute([$package_id]);
            $old_prices = $old_price_stmt->fetch();
            $old_price = $old_prices['current_price'] ?? $old_prices['price'];
            
            
            $update_stmt = $pdo->prepare("UPDATE tint_packages SET current_price = ?, price_updated_by = ?, price_updated_at = NOW() WHERE id = ?");
            $update_stmt->execute([$new_price, $_SESSION['admin_id'], $package_id]);
            
            
            $history_stmt = $pdo->prepare("INSERT INTO price_history (package_id, old_price, new_price, changed_by, reason, changed_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $history_stmt->execute([$package_id, $old_price, $new_price, $_SESSION['admin_id'], $reason]);
            
            
            $pdo->prepare("INSERT INTO notifications (user_type, title, message, type, related_id, related_type, is_read, created_at) 
                          VALUES ('admin', 'Price Updated', 'Package price updated from ₱{$old_price} to ₱{$new_price}. Reason: {$reason}', 'info', ?, 'package', 0, NOW())")
                ->execute([$package_id]);
            
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => "Price updated successfully from ₱{$old_price} to ₱{$new_price}"];
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Error updating price: ' . $e->getMessage()];
        error_log("Price update error: " . $e->getMessage());
    }
    header("Location: index.php#packages");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_action']) && $_POST['booking_action'] == 'decline') {
    verify_admin_csrf('bookings');
    
    try {
        $booking_id = filter_var($_POST['booking_id'], FILTER_VALIDATE_INT);
        $rejection_reason = htmlspecialchars(trim($_POST['rejection_reason']));
        
        if (!$booking_id || empty($rejection_reason)) {
            throw new Exception("Booking ID and rejection reason are required");
        }
        
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'Declined', rejection_reason = ?, rejected_by = ?, rejected_at = NOW() WHERE id = ?");
        $stmt->execute([$rejection_reason, $_SESSION['admin_id'], $booking_id]);
        
        
        try {
            require_once __DIR__ . '/../includes/sms_helper.php';
            sendBookingSMS($pdo, $booking_id, 'Declined', $rejection_reason);
        } catch (Exception $e) {
            error_log("Failed sending declined booking SMS: " . $e->getMessage());
        }
        
        
        $booking_stmt = $pdo->prepare("SELECT user_id, customer_name, total_price FROM bookings WHERE id = ?");
        $booking_stmt->execute([$booking_id]);
        $booking = $booking_stmt->fetch(PDO::FETCH_ASSOC);
        
        
        $pdo->prepare("INSERT INTO notifications (user_type, title, message, type, related_id, related_type, is_read, created_at) 
                      VALUES ('admin', 'Booking Declined', 'Booking #{$booking_id} for {$booking['customer_name']} declined. Reason: {$rejection_reason}', 'warning', ?, 'booking', 0, NOW())")
            ->execute([$booking_id]);

        
        if (!empty($booking['user_id'])) {
            $pdo->prepare("INSERT INTO notifications (user_id, user_type, title, message, type, related_id, related_type, is_read, created_at) 
                          VALUES (?, 'customer', 'Booking Declined', 'Your booking #{$booking_id} has been declined. Reason: {$rejection_reason}', 'danger', ?, 'booking', 0, NOW())")
                ->execute([$booking['user_id'], $booking_id]);
        }
        
        
        $analytics_stmt = $pdo->prepare("INSERT INTO sales_analytics (sale_date, declined_count, declined_revenue_loss) 
                                        VALUES (CURDATE(), 1, ?) 
                                        ON DUPLICATE KEY UPDATE 
                                        declined_count = declined_count + 1, 
                                        declined_revenue_loss = declined_revenue_loss + ?");
        $analytics_stmt->execute([$booking['total_price'], $booking['total_price']]);
        
        $_SESSION['flash_message'] = ['type' => 'warning', 'message' => "Booking #{$booking_id} has been declined. Reason: {$rejection_reason}"];
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Error declining booking: ' . $e->getMessage()];
        error_log("Decline booking error: " . $e->getMessage());
    }
    header("Location: index.php#bookings");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_action']) && $_POST['booking_action'] == 'create') {
    verify_admin_csrf('bookings');
    
    try {
        $customer_name  = htmlspecialchars(trim($_POST['customer_name'] ?? 'Walk-in Customer'));
        $phone          = htmlspecialchars(trim($_POST['phone'] ?? ''));
        $email          = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $address        = htmlspecialchars(trim($_POST['address'] ?? 'Walk-in Store'));
        
        $vehicle_type_id= filter_var($_POST['vehicle_type_id'] ?? 0, FILTER_VALIDATE_INT);
        $vehicle_model  = htmlspecialchars(trim($_POST['vehicle_model'] ?? 'Standard Vehicle'));
        $vehicle_year   = filter_var($_POST['vehicle_year'] ?? date('Y'), FILTER_VALIDATE_INT);
        $vehicle_color  = htmlspecialchars(trim($_POST['vehicle_color'] ?? 'N/A'));
        
        $package_id     = (filter_var($_POST['package_id'] ?? 0, FILTER_VALIDATE_INT) > 0) ? (int)$_POST['package_id'] : null;
        $total_price    = filter_var($_POST['total_price'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0;
        
        $appointment_d  = htmlspecialchars($_POST['appointment_date'] ?? date('Y-m-d'));
        $appointment_t  = htmlspecialchars($_POST['appointment_time'] ?? '09:00:00');
        $appointment_datetime = $appointment_d . ' ' . $appointment_t;
        
        $notes          = htmlspecialchars(trim($_POST['notes'] ?? ''));
        $tint_type      = htmlspecialchars($_POST['tint_type'] ?? 'carbon');
        $shade          = filter_var($_POST['shade_percentage'] ?? 35, FILTER_VALIDATE_INT);
        $warranty_years = filter_var($_POST['warranty_years'] ?? 3, FILTER_VALIDATE_INT);
        $install_time   = htmlspecialchars($_POST['installation_time'] ?? '2-3 hours');
        
        $status         = htmlspecialchars($_POST['status'] ?? 'Approved');
        $payment_method = htmlspecialchars($_POST['payment_method'] ?? 'Cash');
        $payment_status = htmlspecialchars($_POST['payment_status'] ?? 'Paid');
        
        
        $user_id = 0;
        if ($email) {
            $u_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $u_stmt->execute([$email]);
            $user_id = (int)$u_stmt->fetchColumn();
        }
        if (!$user_id && $phone) {
            $u_stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $u_stmt->execute([$phone]);
            $user_id = (int)$u_stmt->fetchColumn();
        }
        
        if (!$user_id) {
            
            $clean_name = trim($customer_name ?: 'Walk-in Customer');
            $name_parts = preg_split('/\s+/', $clean_name, 2);
            $first_name = $name_parts[0] ?? 'Walk-in';
            $last_name  = $name_parts[1] ?? 'Customer';

            $u_email = $email;
            if (empty($u_email)) {
                $slug = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($first_name));
                $u_email = 'walkin_' . ($slug ?: 'cust') . '_' . time() . rand(100, 999) . '@rgcartint.com';
            } else {
                $check_u = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $check_u->execute([$u_email]);
                $existing_id = $check_u->fetchColumn();
                if ($existing_id) {
                    $user_id = (int)$existing_id;
                }
            }

            if (!$user_id) {
                $dummy_pass = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                $ins_u = $pdo->prepare("
                    INSERT INTO users (full_name, first_name, last_name, email, phone, address, password, role, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'customer', 1, NOW())
                ");
                $ins_u->execute([
                    $clean_name,
                    $first_name,
                    $last_name,
                    $u_email,
                    $phone ?: null,
                    $address ?: null,
                    $dummy_pass
                ]);
                $user_id = (int)$pdo->lastInsertId();
            }
        }

        
        if ($user_id <= 0) {
            $user_id = (int)($_SESSION['user_id'] ?? 1);
        }

        
        $customer_id = null;
        if ($phone) {
            $c_find = $pdo->prepare("SELECT id FROM customers WHERE phone = ? LIMIT 1");
            $c_find->execute([$phone]);
            $customer_id = $c_find->fetchColumn();
        }
        if (!$customer_id && $email) {
            $c_find = $pdo->prepare("SELECT id FROM customers WHERE email = ? LIMIT 1");
            $c_find->execute([$email]);
            $customer_id = $c_find->fetchColumn();
        }
        if (!$customer_id) {
            $c_ins = $pdo->prepare("INSERT INTO customers (customer_name, phone, email, address) VALUES (?, ?, ?, ?)");
            $c_ins->execute([$customer_name, $phone ?: null, $email ?: null, $address ?: null]);
            $customer_id = (int)$pdo->lastInsertId();
        }

        
        if ($vehicle_type_id <= 0) {
            $vt_row = $pdo->query("SELECT id FROM vehicle_types WHERE is_active = 1 LIMIT 1")->fetchColumn();
            $vehicle_type_id = $vt_row ? (int)$vt_row : 1;
        }

        
        if ($package_id) {
            $pkg_chk = $pdo->prepare("SELECT id FROM tint_packages WHERE id = ?");
            $pkg_chk->execute([$package_id]);
            if (!$pkg_chk->fetchColumn()) {
                $package_id = null;
            }
        }
        
        
        $booking_ref = 'TINT' . date('Ymd') . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        
        
        $stmt = $pdo->prepare("
            INSERT INTO bookings 
            (user_id, customer_id, reference_code, booking_ref, customer_name, phone, address, vehicle_model, vehicle_type_id, vehicle_year, vehicle_color, package_id, total_price, appointment_date, appointment_time, notes, status, payment_method, payment_status, tint_type, shade_percentage, warranty_years, installation_time, created_by_admin, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $user_id,
            $customer_id,
            $booking_ref,
            $booking_ref,
            $customer_name,
            $phone,
            $address,
            $vehicle_model,
            $vehicle_type_id,
            $vehicle_year,
            $vehicle_color,
            $package_id,
            $total_price,
            $appointment_datetime,
            $appointment_t,
            $notes,
            $status,
            $payment_method,
            $payment_status,
            $tint_type,
            $shade,
            $warranty_years,
            $install_time,
            $_SESSION['admin_name'] ?? 'Admin'
        ]);
        
        $new_booking_id = $pdo->lastInsertId();
        
        
        if ($payment_status === 'Paid' || $status === 'Completed') {
            $pdo->prepare("
                INSERT INTO sales (booking_id, customer_id, customer_name, product_name, quantity, unit_price, total_price, payment_method, payment_status, sale_date) 
                VALUES (?, ?, ?, 'Tint Installation', 1, ?, ?, ?, 'paid', CURDATE())
            ")->execute([$new_booking_id, $user_id, $customer_name, $total_price, $total_price, strtolower($payment_method)]);
            
            $pdo->prepare("
                INSERT INTO sales_analytics (sale_date, total_sales, booking_count, avg_booking_value) 
                VALUES (CURDATE(), ?, 1, ?) 
                ON DUPLICATE KEY UPDATE 
                total_sales = total_sales + ?, 
                booking_count = booking_count + 1,
                avg_booking_value = total_sales / booking_count
            ")->execute([$total_price, $total_price, $total_price]);
        }
        
        
        $pdo->prepare("
            INSERT INTO notifications (user_type, title, message, type, related_id, related_type, is_read, created_at)
            VALUES ('admin', 'Walk-in Booking Created', 'Walk-in booking #{$new_booking_id} ({$booking_ref}) created for {$customer_name}. Total: ₱" . number_format($total_price, 2) . "', 'success', ?, 'booking', 0, NOW())
        ")->execute([$new_booking_id]);
        
        
        if ($phone) {
            try {
                require_once __DIR__ . '/../includes/sms_helper.php';
                sendBookingSMS($pdo, $new_booking_id, $status);
            } catch (Exception $e) {
                error_log("Failed sending walk-in booking SMS: " . $e->getMessage());
            }
        }
        
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => "Walk-in Booking #{$new_booking_id} ({$booking_ref}) created successfully!"];

    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Error creating booking: ' . $e->getMessage()];
        error_log("Create booking error: " . $e->getMessage());
    }
    header("Location: index.php#bookings");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && !isset($_POST['booking_action'])) {
    verify_admin_csrf('bookings');
    
    $booking_id = filter_var($_POST['booking_id'], FILTER_VALIDATE_INT);
    $new_status = htmlspecialchars($_POST['status'] ?? '');
    
    try {
        if ($new_status == 'Delete' || (isset($_POST['action']) && strtolower($_POST['action']) === 'delete')) {
            
            $booking_stmt = $pdo->prepare("SELECT customer_name, total_price FROM bookings WHERE id = ?");
            $booking_stmt->execute([$booking_id]);
            $booking = $booking_stmt->fetch();
            $custName = $booking['customer_name'] ?? 'Customer';
            
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM booking_details WHERE booking_id = ?")->execute([$booking_id]);
            $pdo->prepare("DELETE FROM payment_transactions WHERE booking_id = ?")->execute([$booking_id]);
            $pdo->prepare("UPDATE sales SET booking_id = NULL WHERE booking_id = ?")->execute([$booking_id]);
            $pdo->prepare("DELETE FROM notifications WHERE related_type = 'booking' AND related_id = ?")->execute([$booking_id]);
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
            $stmt->execute([$booking_id]);
            $pdo->commit();

            $_SESSION['flash_message'] = ['type' => 'success', 'message' => "Booking #{$booking_id} deleted successfully"];
            
            try { 
                $pdo->prepare("INSERT INTO notifications (user_type, title, message, type, related_id, related_type) 
                              VALUES ('admin', 'Booking Deleted', 'Booking #{$booking_id} for {$custName} has been permanently deleted', 'danger', 0, 'booking')")
                    ->execute(); 
            } catch (PDOException $e) { error_log("Notification error: " . $e->getMessage()); }
        } else {
            
            $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $booking_id]);
            
            
            try {
                require_once __DIR__ . '/../includes/sms_helper.php';
                sendBookingSMS($pdo, $booking_id, $new_status);
            } catch (Exception $e) {
                error_log("Failed sending status update booking SMS: " . $e->getMessage());
            }
            
            
            $booking_stmt = $pdo->prepare("SELECT user_id, customer_name, total_price, tint_type, shade_percentage, payment_method FROM bookings WHERE id = ?");
            $booking_stmt->execute([$booking_id]);
            $booking = $booking_stmt->fetch(PDO::FETCH_ASSOC);

            
            if ($booking && !empty($booking['user_id'])) {
                $notif_title = "Booking " . $new_status;
                $notif_msg = "Your booking #{$booking_id} status has been updated to {$new_status}.";
                $notif_type = ($new_status == 'Approved' || $new_status == 'Completed') ? 'success' : 'warning';
                
                $pdo->prepare("INSERT INTO notifications (user_id, user_type, title, message, type, related_id, related_type, is_read, created_at) 
                              VALUES (?, 'customer', ?, ?, ?, ?, 'booking', 0, NOW())")
                    ->execute([$booking['user_id'], $notif_title, $notif_msg, $notif_type, $booking_id]);
            }

            if ($new_status == 'Approved' || $new_status == 'Completed') {
                $payment_stmt = $pdo->prepare("UPDATE bookings SET payment_status = 'Paid' WHERE id = ?");
                $payment_stmt->execute([$booking_id]);
                try { 
                    $pdo->prepare("INSERT INTO notifications (user_type, title, message, type, related_id, related_type) 
                                  VALUES ('admin', 'Payment Status Updated', 'Booking #{$booking_id} payment status auto-updated to Paid', 'success', ?, 'booking')")
                        ->execute([$booking_id]); 
                } catch (PDOException $e) { error_log("Notification error: " . $e->getMessage()); }
            }
            
            if ($new_status == 'Completed') {
                try {
                    $stmt = $pdo->prepare("UPDATE bookings SET completed_at = NOW() WHERE id = ?");
                    $stmt->execute([$booking_id]);
                    
                    if ($booking) {
                        $pdo->prepare("INSERT INTO sales (booking_id, customer_id, customer_name, product_name, quantity, unit_price, total_price, payment_method, payment_status, sale_date) 
                                      VALUES (?, ?, ?, 'Tint Installation', 1, ?, ?, ?, 'paid', CURDATE())")
                            ->execute([$booking_id, $booking['user_id'] ?? 0, $booking['customer_name'], $booking['total_price'], $booking['total_price'], $booking['payment_method'] ?? 'cash']);
                        
                        
                        $analytics_stmt = $pdo->prepare("INSERT INTO sales_analytics (sale_date, total_sales, booking_count, avg_booking_value) 
                                                        VALUES (CURDATE(), ?, 1, ?) 
                                                        ON DUPLICATE KEY UPDATE 
                                                        total_sales = total_sales + ?, 
                                                        booking_count = booking_count + 1,
                                                        avg_booking_value = total_sales / booking_count");
                        $analytics_stmt->execute([$booking['total_price'], $booking['total_price'], $booking['total_price']]);
                        
                        try {
                            $tint_type = $booking['tint_type'] ?? 'carbon';
                            $shade = $booking['shade_percentage'] ?? 35;
                            $tint_stmt = $pdo->prepare("SELECT * FROM tint_stock WHERE tint_type = ? AND FIND_IN_SET(?, shade_percentage) > 0 AND quantity_rolls > 0 ORDER BY quantity_rolls ASC LIMIT 1");
                            $tint_stmt->execute([$tint_type, $shade]);
                            $tint = $tint_stmt->fetch();
                            if ($tint) {
                                $rolls_used = 0.5;
                                $new_quantity = $tint['quantity_rolls'] - $rolls_used;
                                if ($new_quantity < 0) $new_quantity = 0;
                                $pdo->prepare("UPDATE tint_stock SET quantity_rolls = ? WHERE id = ?")->execute([$new_quantity, $tint['id']]);
                                if ($new_quantity <= $tint['min_rolls']) {
                                    $pdo->prepare("INSERT INTO notifications (user_type, title, message, type, related_id, related_type) 
                                                  VALUES ('admin', 'Low Tint Stock Alert', 'Film \"{$tint['film_name']}\" is now low ({$new_quantity} rolls left after booking #{$booking_id})', 'warning', ?, 'tint_stock')")
                                        ->execute([$tint['id']]);
                                }
                            }
                        } catch (PDOException $e) { error_log("Inventory update error on booking completion: " . $e->getMessage()); }
                    }
                    
                    $pdo->prepare("INSERT INTO notifications (user_type, title, message, type, related_id, related_type) 
                                  VALUES ('admin', 'Booking Completed', 'Booking #{$booking_id} marked as completed', 'success', ?, 'booking')")
                        ->execute([$booking_id]);
                } catch (PDOException $e) { error_log("Completion error: " . $e->getMessage()); }
            }
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => "Booking #{$booking_id} status updated to {$new_status} successfully"];
            
            try { 
                $pdo->prepare("INSERT INTO notifications (user_type, title, message, type, related_id, related_type) 
                              VALUES ('admin', 'Booking Updated', 'Booking #{$booking_id} status changed to {$new_status}', 'info', ?, 'booking')")
                    ->execute([$booking_id]); 
            } catch (PDOException $e) { error_log("Notification error: " . $e->getMessage()); }
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Error: ' . $e->getMessage()];
    }
    header("Location: index.php#bookings");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['car_action']) && $_POST['car_action'] == 'delete') {
    verify_admin_csrf('packages');
    try {
        $cm_id = filter_var($_POST['car_model_id'], FILTER_VALIDATE_INT);
        if ($cm_id) {
            $pdo->prepare("DELETE FROM car_models WHERE id = ?")->execute([$cm_id]);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Car model deleted successfully'];
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Error deleting car model: ' . $e->getMessage()];
    }
    header("Location: index.php#packages");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['package_action'])) {
    verify_admin_csrf('packages');
    
    try {
        if ($_POST['package_action'] == 'add' || $_POST['package_action'] == 'update') {
            $name = htmlspecialchars(trim($_POST['name']));
            $package_id = $_POST['package_action'] === 'update'
                ? filter_var($_POST['package_id'] ?? null, FILTER_VALIDATE_INT)
                : null;

            
            
            $duplicate_name_stmt = $pdo->prepare(
                "SELECT id FROM tint_packages WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) AND (? IS NULL OR id <> ?) LIMIT 1"
            );
            $duplicate_name_stmt->execute([$name, $package_id, $package_id]);
            if ($duplicate_name_stmt->fetchColumn()) {
                $_SESSION['flash_message'] = [
                    'type' => 'danger',
                    'message' => 'This package name has already been used. Please enter a different name.'
                ];
                header("Location: index.php#packages");
                exit;
            }
            $description = trim(htmlspecialchars_decode($_POST['description'] ?? '', ENT_QUOTES));
            $features = json_encode(array_filter(array_map('trim', explode("\n", trim($_POST['features'] ?? '')))));
            $tint_type = htmlspecialchars($_POST['tint_type'] ?? 'carbon');
            $shade_percentage = $_POST['shade_percentage'] ?? '35';
            
            $shade_decoded = json_decode($shade_percentage, true);
            if (!is_array($shade_decoded)) {
                
                $first_type = explode(',', $tint_type)[0];
                $shade_percentage = json_encode([$first_type => [intval($shade_percentage)]]);
            }
            $installation_time = htmlspecialchars($_POST['installation_time'] ?? '2-3 hours');
            $warranty_years = filter_var($_POST['warranty_years'] ?? 3, FILTER_VALIDATE_INT);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $display_order = filter_var($_POST['display_order'] ?? 0, FILTER_VALIDATE_INT);
            $valid_windows = ['windshield','front_win','rear_win','side_win','rear_ws','sunstrip'];
            $windows_raw = array_filter(array_map('trim', explode(',', $_POST['windows_included'] ?? '')));
            $windows_included = json_encode(array_values(array_intersect($windows_raw, $valid_windows)));

            
            $combo_prices_input = [];
            $raw_combo = trim($_POST['combo_prices_json'] ?? '{}');
            $parsed_combo = json_decode($raw_combo, true);
            if (is_array($parsed_combo)) {
                foreach ($parsed_combo as $combo_key => $data) {
                    $parts = explode('|', $combo_key, 2);
                    $tt_c  = htmlspecialchars(strip_tags($parts[0] ?? 'carbon'));
                    $sh_c  = (int)($parts[1] ?? 35);
                    if (!is_array($data)) continue;
                    $win_prices = $data['windows'] ?? $data;
                    $car_model  = htmlspecialchars(strip_tags($data['car'] ?? ''));
                    
                    if (isset($data['cars']) && is_array($data['cars'])) {
                        $pkg_price = filter_var($data['price'] ?? 0, FILTER_VALIDATE_FLOAT);
                        if ($pkg_price > 0) {
                            foreach ($data['cars'] as $car_item) {
                                $cm_clean = htmlspecialchars(strip_tags(trim((string)$car_item)));
                                if ($cm_clean) {
                                    $combo_prices_input[] = [
                                        'car_model' => $cm_clean, 'tint_type' => $tt_c, 'shade' => $sh_c,
                                        'price' => $pkg_price, 'wholesale_price' => null, 'wholesale_min_qty' => 3,
                                    ];
                                }
                            }
                        }
                    } elseif (isset($data['windows']) && is_array($data['windows'])) {
                        
                        $total_price = 0;
                        foreach ($data['windows'] as $wk => $wp) {
                            $total_price += filter_var($wp, FILTER_VALIDATE_FLOAT) ?: 0;
                        }
                        if ($car_model && $total_price > 0) {
                            $combo_prices_input[] = [
                                'car_model' => $car_model, 'tint_type' => $tt_c, 'shade' => $sh_c,
                                'price' => $total_price, 'wholesale_price' => null, 'wholesale_min_qty' => 3,
                            ];
                        }
                    } else {
                        
                        foreach ($win_prices as $cm => $p) {
                            $pv = filter_var($p, FILTER_VALIDATE_FLOAT);
                            if ($pv !== false && $pv > 0) {
                                $combo_prices_input[] = [
                                    'car_model' => htmlspecialchars(strip_tags($cm)),
                                    'tint_type' => $tt_c, 'shade' => $sh_c, 'price' => $pv,
                                ];
                            }
                        }
                    }
                }
            }
            
            $raw_window_prices = trim($_POST['window_prices_json'] ?? '{}');
            $parsed_wp = json_decode($raw_window_prices, true);
            if (is_array($parsed_wp) && !empty($parsed_wp)) {
                
                $enriched = [];
                foreach ($parsed_wp as $wk => $wp) {
                    if (in_array($wk, $valid_windows)) {
                        $enriched[$wk] = filter_var($wp, FILTER_VALIDATE_FLOAT) ?: 0;
                    }
                }
                $windows_included = json_encode($enriched);
            }
            
            $wp_total = 0;
            $wp_decoded = json_decode($windows_included, true);
            if (is_array($wp_decoded)) {
                foreach ($wp_decoded as $wk => $wval) {
                    if (is_numeric($wval)) $wp_total += (float)$wval;
                }
            }
            $all_prices_flat = array_column($combo_prices_input, 'price');
            
            if ($avg_pkg_price <= 0) {
                $avg_pkg_price = 4500.00;
            }
            $sedan_price = $avg_pkg_price;
            $suv_price   = $avg_pkg_price;
            $van_price   = $avg_pkg_price;

            
            if (empty($combo_prices_input)) {
                $all_cars_stmt = $pdo->query("SELECT full_name FROM car_models WHERE is_active = 1");
                $available_cars = $all_cars_stmt->fetchAll(PDO::FETCH_COLUMN);
                $selected_types = array_values(array_filter(array_map('trim', explode(',', $tint_type))));
                if (empty($selected_types)) $selected_types = ['carbon'];
                $selected_shades_arr = json_decode($shade_percentage, true);
                
                foreach ($selected_types as $tt_item) {
                    $shades_list = is_array($selected_shades_arr) && isset($selected_shades_arr[$tt_item]) ? $selected_shades_arr[$tt_item] : [35];
                    foreach ($shades_list as $sh_item) {
                        foreach ($available_cars as $cm_item) {
                            $combo_prices_input[] = [
                                'car_model' => $cm_item,
                                'tint_type' => $tt_item,
                                'shade' => (int)$sh_item,
                                'price' => $avg_pkg_price,
                                'wholesale_price' => null,
                                'wholesale_min_qty' => 3,
                            ];
                        }
                    }
                }
            }

            
            $pdo->exec("CREATE TABLE IF NOT EXISTS package_model_prices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                package_id INT NOT NULL,
                car_model VARCHAR(200) NOT NULL,
                tint_type VARCHAR(50) NOT NULL DEFAULT 'carbon',
                shade_percentage INT NOT NULL DEFAULT 35,
                price DECIMAL(10,2) NOT NULL DEFAULT 0,
                wholesale_price DECIMAL(10,2) DEFAULT NULL,
                wholesale_min_qty INT DEFAULT 3,
                UNIQUE KEY pkg_car_combo (package_id, car_model, tint_type, shade_percentage),
                INDEX idx_pkg (package_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            try { $pdo->exec("ALTER TABLE package_model_prices ADD COLUMN tint_type VARCHAR(50) NOT NULL DEFAULT 'carbon' AFTER car_model"); } catch (PDOException $e2) {}
            try { $pdo->exec("ALTER TABLE package_model_prices ADD COLUMN shade_percentage INT NOT NULL DEFAULT 35 AFTER tint_type"); } catch (PDOException $e2) {}
            try { $pdo->exec("ALTER TABLE package_model_prices DROP INDEX pkg_car"); } catch (PDOException $e2) {}
            try { $pdo->exec("ALTER TABLE package_model_prices ADD UNIQUE KEY pkg_car_combo (package_id, car_model, tint_type, shade_percentage)"); } catch (PDOException $e2) {}
            try { $pdo->exec("ALTER TABLE package_model_prices ADD COLUMN wholesale_price DECIMAL(10,2) DEFAULT NULL AFTER price"); } catch (PDOException $e2) {}
            try { $pdo->exec("ALTER TABLE package_model_prices ADD COLUMN wholesale_min_qty INT DEFAULT 3 AFTER wholesale_price"); } catch (PDOException $e2) {}

            
            $image_url = null;
            if (!empty($_FILES['package_image']['name']) && $_FILES['package_image']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['package_image']['tmp_name']);
                if (in_array($mime, $allowed) && $_FILES['package_image']['size'] <= 2 * 1024 * 1024) {
                    $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime];
                    $filename = 'pkg_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $dest = __DIR__ . '/../uploads/packages/' . $filename;
                    if (move_uploaded_file($_FILES['package_image']['tmp_name'], $dest)) {
                        $image_url = '../uploads/packages/' . $filename;
                    }
                }
            }

            if ($_POST['package_action'] == 'add') {
                $stmt = $pdo->prepare("INSERT INTO tint_packages (name, description, price, sedan_price, suv_price, van_price, features, tint_type, shade_percentage, installation_time, warranty_years, is_active, is_featured, display_order, windows_included, image_url, created_at)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $description, $avg_pkg_price, $sedan_price, $suv_price, $van_price, $features, $tint_type, $shade_percentage, $installation_time, $warranty_years, $is_active, $is_featured, $display_order, $windows_included, $image_url]);
                $pkg_id = $pdo->lastInsertId();
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Package added successfully'];
            } else {
                $pkg_id = filter_var($_POST['package_id'], FILTER_VALIDATE_INT);
                if ($image_url) {
                    $stmt = $pdo->prepare("UPDATE tint_packages SET name = ?, description = ?, price = ?, sedan_price = ?, suv_price = ?, van_price = ?, features = ?, tint_type = ?, shade_percentage = ?, installation_time = ?, warranty_years = ?, is_active = ?, is_featured = ?, display_order = ?, windows_included = ?, image_url = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $avg_pkg_price, $sedan_price, $suv_price, $van_price, $features, $tint_type, $shade_percentage, $installation_time, $warranty_years, $is_active, $is_featured, $display_order, $windows_included, $image_url, $pkg_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE tint_packages SET name = ?, description = ?, price = ?, sedan_price = ?, suv_price = ?, van_price = ?, features = ?, tint_type = ?, shade_percentage = ?, installation_time = ?, warranty_years = ?, is_active = ?, is_featured = ?, display_order = ?, windows_included = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $avg_pkg_price, $sedan_price, $suv_price, $van_price, $features, $tint_type, $shade_percentage, $installation_time, $warranty_years, $is_active, $is_featured, $display_order, $windows_included, $pkg_id]);
                }
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Package updated successfully'];
            }
            
            if (!empty($combo_prices_input)) {
                $pdo->prepare("DELETE FROM package_model_prices WHERE package_id = ?")->execute([$pkg_id]);
                $price_ins = $pdo->prepare("INSERT INTO package_model_prices (package_id, car_model, tint_type, shade_percentage, price, wholesale_price, wholesale_min_qty) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE price = VALUES(price), wholesale_price = VALUES(wholesale_price), wholesale_min_qty = VALUES(wholesale_min_qty)");
                foreach ($combo_prices_input as $row) {
                    $price_ins->execute([$pkg_id, $row['car_model'], $row['tint_type'], $row['shade'], $row['price'], $row['wholesale_price'] ?? null, $row['wholesale_min_qty'] ?? 3]);
                }
            }
        } elseif ($_POST['package_action'] == 'delete') {
            $id = filter_var($_POST['package_id'], FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare("DELETE FROM tint_packages WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Package deleted successfully'];
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Error: ' . $e->getMessage()];
    }
    header("Location: index.php#packages");
    exit;
}

if (isset($_POST['stock_action'])) {
    verify_admin_csrf('tint-stock');
    
    try {
        if ($_POST['stock_action'] == 'add') {
            $film_name = htmlspecialchars($_POST['film_name']);
            $tint_type = !empty($_POST['tint_type']) ? htmlspecialchars($_POST['tint_type']) : strtolower(trim($film_name));
            $brand = htmlspecialchars($_POST['brand'] ?? '');
            $width_cm = filter_var($_POST['width_cm'], FILTER_VALIDATE_FLOAT);
            $length_ft = filter_var($_POST['length_ft'], FILTER_VALIDATE_FLOAT);
            $quantity_rolls = filter_var($_POST['quantity_rolls'], FILTER_VALIDATE_INT);
            $min_rolls = filter_var($_POST['min_rolls'], FILTER_VALIDATE_INT);
            $heat_rejection = filter_var($_POST['heat_rejection'], FILTER_VALIDATE_INT);
            $uv_protection = filter_var($_POST['uv_protection'], FILTER_VALIDATE_INT);
            $warranty_years = filter_var($_POST['warranty_years'], FILTER_VALIDATE_INT);
            $location = htmlspecialchars($_POST['location'] ?? 'Main Storage');
            $width_in = $width_cm / 2.54;
            $roll_sqft = ($width_in / 12) * $length_ft;
            $avg_price = filter_var($_POST['price_per_roll'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0;
            $avg_sqft = $roll_sqft > 0 ? $avg_price / $roll_sqft : 0;
            $total_stock = filter_var($_POST['quantity_rolls'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
            $shade_csv = '';
            $shade_json = null;
            $stmt = $pdo->prepare("INSERT INTO tint_stock (film_name, tint_type, brand, shade_percentage, shade_prices, width_cm, length_ft, quantity_rolls, min_rolls, price_per_roll, price_per_sqft, heat_rejection, uv_protection, warranty_years, location, last_restocked) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
            $stmt->execute([$film_name, $tint_type, $brand, $shade_csv, $shade_json, $width_cm, $length_ft, $total_stock, $min_rolls, $avg_price, $avg_sqft, $heat_rejection, $uv_protection, $warranty_years, $location]);
            if ($total_stock <= $min_rolls) {
                try { $lastId = $pdo->lastInsertId(); $pdo->prepare("INSERT INTO notifications (user_type, title, message, type, related_id, related_type) VALUES ('admin', 'Low Tint Stock', 'Film \"$film_name\" is running low ($total_stock rolls left)', 'warning', ?, 'tint_stock')")->execute([$lastId]); } catch (PDOException $e) { error_log("Notification error: " . $e->getMessage()); }
            }
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Tint film added successfully'];
        } elseif ($_POST['stock_action'] == 'update') {
            $stock_id = filter_var($_POST['stock_id'], FILTER_VALIDATE_INT);
            $film_name = htmlspecialchars($_POST['film_name']);
            $tint_type = !empty($_POST['tint_type']) ? htmlspecialchars($_POST['tint_type']) : strtolower(trim($film_name));
            $quantity_rolls = filter_var($_POST['quantity_rolls'] ?? 0, FILTER_VALIDATE_INT);
            $price_per_roll = filter_var($_POST['price_per_roll'] ?? 0, FILTER_VALIDATE_FLOAT);
            $brand = htmlspecialchars($_POST['brand'] ?? '');
            $warranty_years = filter_var($_POST['warranty_years'] ?? 3, FILTER_VALIDATE_INT);
            $width_cm = filter_var($_POST['width_cm'] ?? 152.4, FILTER_VALIDATE_FLOAT);
            $length_ft = filter_var($_POST['length_ft'] ?? 100, FILTER_VALIDATE_FLOAT);
            $heat_rejection = filter_var($_POST['heat_rejection'] ?? 50, FILTER_VALIDATE_INT);
            $uv_protection = filter_var($_POST['uv_protection'] ?? 99, FILTER_VALIDATE_INT);
            $location = htmlspecialchars($_POST['location'] ?? 'Main Storage');
            if ($stock_id) {
                $stmt = $pdo->prepare("UPDATE tint_stock SET film_name = ?, tint_type = ?, brand = ?, warranty_years = ?, width_cm = ?, length_ft = ?, heat_rejection = ?, uv_protection = ?, location = ?, quantity_rolls = ?, price_per_roll = ? WHERE id = ?");
                $stmt->execute([$film_name, $tint_type, $brand, $warranty_years, $width_cm, $length_ft, $heat_rejection, $uv_protection, $location, $quantity_rolls, $price_per_roll, $stock_id]);
            }
            $id = filter_var($_POST['stock_id'], FILTER_VALIDATE_INT);
            $film_name = htmlspecialchars($_POST['film_name']);
            $tint_type = !empty($_POST['tint_type']) ? htmlspecialchars($_POST['tint_type']) : strtolower(trim($film_name));
            $brand = htmlspecialchars($_POST['brand'] ?? '');
            $width_cm = filter_var($_POST['width_cm'], FILTER_VALIDATE_FLOAT);
            $length_ft = filter_var($_POST['length_ft'], FILTER_VALIDATE_FLOAT);
            $quantity_rolls = filter_var($_POST['quantity_rolls'], FILTER_VALIDATE_INT);
            $min_rolls = filter_var($_POST['min_rolls'], FILTER_VALIDATE_INT);
            $heat_rejection = filter_var($_POST['heat_rejection'], FILTER_VALIDATE_INT);
            $uv_protection = filter_var($_POST['uv_protection'], FILTER_VALIDATE_INT);
            $warranty_years = filter_var($_POST['warranty_years'], FILTER_VALIDATE_INT);
            $location = htmlspecialchars($_POST['location'] ?? 'Main Storage');
            $width_in = $width_cm / 2.54;
            $roll_sqft = ($width_in / 12) * $length_ft;
            $avg_price = filter_var($_POST['price_per_roll'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0;
            $avg_sqft = $roll_sqft > 0 ? $avg_price / $roll_sqft : 0;
            $total_stock = filter_var($_POST['quantity_rolls'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
            $shade_csv = '';
            $shade_json = null;
            $stmt = $pdo->prepare("UPDATE tint_stock SET film_name = ?, tint_type = ?, brand = ?, shade_percentage = ?, shade_prices = ?, width_cm = ?, length_ft = ?, quantity_rolls = ?, min_rolls = ?, price_per_roll = ?, price_per_sqft = ?, heat_rejection = ?, uv_protection = ?, warranty_years = ?, location = ? WHERE id = ?");
            $stmt->execute([$film_name, $tint_type, $brand, $shade_csv, $shade_json, $width_cm, $length_ft, $total_stock, $min_rolls, $avg_price, $avg_sqft, $heat_rejection, $uv_protection, $warranty_years, $location, $id]);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Tint film updated successfully'];
        } elseif ($_POST['stock_action'] == 'delete') {
            $id = filter_var($_POST['stock_id'], FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare("DELETE FROM tint_stock WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Tint film deleted successfully'];
        } elseif ($_POST['stock_action'] == 'restock') {
            $id = filter_var($_POST['stock_id'], FILTER_VALIDATE_INT);
            $add_rolls = filter_var($_POST['add_rolls'], FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare("UPDATE tint_stock SET quantity_rolls = quantity_rolls + ?, last_restocked = CURDATE() WHERE id = ?");
            $stmt->execute([$add_rolls, $id]);
            try {
                $film_stmt = $pdo->prepare("SELECT film_name, quantity_rolls, min_rolls FROM tint_stock WHERE id = ?");
                $film_stmt->execute([$id]);
                $film = $film_stmt->fetch();
                if ($film) {
                    $pdo->prepare("INSERT INTO notifications (user_type, title, message, type, related_id, related_type) VALUES ('admin', 'Tint Restocked', 'Film \"{$film['film_name']}\" restocked with $add_rolls rolls. Current stock: {$film['quantity_rolls']} rolls', 'success', ?, 'tint_stock')")->execute([$id]);
                }
            } catch (PDOException $e) { error_log("Notification error: " . $e->getMessage()); }
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Tint film restocked successfully'];
        } elseif ($_POST['stock_action'] == 'manual_update') {
            $id = filter_var($_POST['stock_id'], FILTER_VALIDATE_INT);
            $new_quantity = filter_var($_POST['new_quantity'], FILTER_VALIDATE_INT);
            $update_reason = htmlspecialchars($_POST['update_reason'] ?? 'Manual update');
            if ($new_quantity < 0) throw new Exception("Quantity cannot be negative");
            $current_stmt = $pdo->prepare("SELECT film_name, quantity_rolls, min_rolls FROM tint_stock WHERE id = ?");
            $current_stmt->execute([$id]);
            $current = $current_stmt->fetch();
            if (!$current) throw new Exception("Tint film not found");
            $old_quantity = $current['quantity_rolls'];
            $stmt = $pdo->prepare("UPDATE tint_stock SET quantity_rolls = ?, last_restocked = CURDATE() WHERE id = ?");
            $stmt->execute([$new_quantity, $id]);
            $change = $new_quantity - $old_quantity;
            $change_direction = $change > 0 ? 'increased' : 'decreased';
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => "Inventory updated: {$current['film_name']} {$change_direction} from {$old_quantity} to {$new_quantity} rolls"];
            try {
                $pdo->prepare("INSERT INTO notifications (user_type, title, message, type, related_id, related_type) VALUES ('admin', 'Inventory Updated', 'Film \"{$current['film_name']}\" quantity manually updated from {$old_quantity} to {$new_quantity} rolls. Reason: {$update_reason}', 'info', ?, 'tint_stock')")->execute([$id]);
                if ($new_quantity <= $current['min_rolls']) {
                    $pdo->prepare("INSERT INTO notifications (user_type, title, message, type, related_id, related_type) VALUES ('admin', 'Low Stock Alert', 'Film \"{$current['film_name']}\" is low: {$new_quantity} rolls (minimum: {$current['min_rolls']})', 'warning', ?, 'tint_stock')")->execute([$id]);
                }
            } catch (PDOException $e) { error_log("Notification error: " . $e->getMessage()); }
        }
    } catch (Exception $e) { $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Error: ' . $e->getMessage()]; }
    header("Location: index.php#tint-stock");
    exit;
}

if (isset($_POST['car_model_action'])) {
    verify_admin_csrf('packages');
    try {
        if ($_POST['car_model_action'] === 'add') {
            $brand     = htmlspecialchars(trim($_POST['cm_brand'] ?? ''));
            $model     = htmlspecialchars(trim($_POST['cm_model'] ?? ''));
            $vtype_id  = filter_var($_POST['cm_vehicle_type_id'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
            $full_name = trim($brand . ' ' . $model);
            if ($brand && $model) {
                $pdo->exec("CREATE TABLE IF NOT EXISTS package_model_prices (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    package_id INT NOT NULL,
                    car_model VARCHAR(200) NOT NULL,
                    tint_type VARCHAR(50) NOT NULL DEFAULT 'carbon',
                    shade_percentage INT NOT NULL DEFAULT 35,
                    price DECIMAL(10,2) NOT NULL DEFAULT 0,
                    wholesale_price DECIMAL(10,2) DEFAULT NULL,
                    wholesale_min_qty INT DEFAULT 3,
                    UNIQUE KEY pkg_car_combo (package_id, car_model, tint_type, shade_percentage),
                    INDEX idx_pkg (package_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO car_models (brand, model, vehicle_type_id, full_name, is_active) VALUES (?,?,?,?,1)');
                $stmt->execute([$brand, $model, $vtype_id, $full_name]);

                
                

                $copy_prices = $pdo->prepare("INSERT INTO package_model_prices
                    (package_id, car_model, tint_type, shade_percentage, price, wholesale_price, wholesale_min_qty)
                    SELECT package_id, ?, tint_type, shade_percentage, MAX(price), MAX(wholesale_price), MAX(wholesale_min_qty)
                    FROM package_model_prices
                    GROUP BY package_id, tint_type, shade_percentage
                    ON DUPLICATE KEY UPDATE price = VALUES(price), wholesale_price = VALUES(wholesale_price), wholesale_min_qty = VALUES(wholesale_min_qty)");
                $copy_prices->execute([$full_name]);

                
                
                $packages_for_car = $pdo->query("SELECT p.id, p.tint_type, p.shade_percentage,
                    COALESCE(NULLIF(p.price, 0), NULLIF(p.sedan_price, 0), 4500) AS package_price
                    FROM tint_packages p
                    WHERE NOT EXISTS (
                        SELECT 1 FROM package_model_prices pmp
                        WHERE pmp.package_id = p.id AND pmp.car_model = " . $pdo->quote($full_name) . "
                    )")->fetchAll();
                $add_package_price = $pdo->prepare("INSERT INTO package_model_prices
                    (package_id, car_model, tint_type, shade_percentage, price, wholesale_price, wholesale_min_qty)
                    VALUES (?, ?, ?, ?, ?, NULL, 3)
                    ON DUPLICATE KEY UPDATE price = VALUES(price)");
                foreach ($packages_for_car as $package_for_car) {
                    $types = array_values(array_filter(array_map('trim', explode(',', $package_for_car['tint_type'] ?: 'carbon'))));
                    if (!$types) $types = ['carbon'];
                    $shade_map = json_decode($package_for_car['shade_percentage'] ?? '', true);
                    foreach ($types as $type) {
                        $shades = is_array($shade_map) && !empty($shade_map[$type]) ? $shade_map[$type] : [35];
                        foreach ($shades as $shade) {
                            $add_package_price->execute([
                                $package_for_car['id'], $full_name, $type, (int)$shade, $package_for_car['package_price']
                            ]);
                        }
                    }
                }

                $package_count_stmt = $pdo->prepare('SELECT COUNT(DISTINCT package_id) FROM package_model_prices WHERE car_model = ?');
                $package_count_stmt->execute([$full_name]);
                $package_count = (int)$package_count_stmt->fetchColumn();
                $pdo->commit();
                $_SESSION['flash_message'] = [
                    'type'=>'success',
                    'message'=>"Car model '$full_name' saved and automatically added to $package_count package(s)."
                ];
            } else {
                $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Brand and model name are required.'];
            }
        } elseif ($_POST['car_model_action'] === 'delete') {
            $id = filter_var($_POST['cm_id'] ?? 0, FILTER_VALIDATE_INT);
            if ($id) { $pdo->prepare('DELETE FROM car_models WHERE id = ?')->execute([$id]); }
            $_SESSION['flash_message'] = ['type'=>'success','message'=>'Car model deleted.'];
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash_message'] = ['type'=>'danger','message'=>'DB error: ' . $e->getMessage()];
    }
    header('Location: index.php#packages');
    exit;
}

if (isset($_POST['service_action'])) {
    verify_admin_csrf('service');
    
    try {
        if ($_POST['service_action'] == 'respond') {
            $id = filter_var($_POST['service_id'], FILTER_VALIDATE_INT);
            $response = htmlspecialchars($_POST['response']);
            $status = htmlspecialchars($_POST['status']);
            $stmt = $pdo->prepare("UPDATE customer_services SET response = ?, status = ?, assigned_to = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$response, $status, $_SESSION['admin_id'] ?? null, $id]);

            
            $ticket_stmt = $pdo->prepare("SELECT user_id, subject FROM customer_services WHERE id = ?");
            $ticket_stmt->execute([$id]);
            $t_data = $ticket_stmt->fetch(PDO::FETCH_ASSOC);
            if ($t_data && !empty($t_data['user_id'])) {
                $notif_msg = "Support response received for ticket '" . mb_substr($t_data['subject'], 0, 35) . "...': " . mb_substr($response, 0, 50);
                $pdo->prepare("
                    INSERT INTO notifications (user_id, user_type, title, message, type, related_id, related_type, is_read, created_at)
                    VALUES (?, 'customer', 'Support Ticket Response', ?, 'info', ?, 'support', 0, NOW())
                ")->execute([$t_data['user_id'], $notif_msg, $id]);
            }

            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Response sent and customer notified successfully'];
        } elseif ($_POST['service_action'] == 'update_status') {
            $id = filter_var($_POST['service_id'], FILTER_VALIDATE_INT);
            $status = htmlspecialchars($_POST['status']);
            $priority = htmlspecialchars($_POST['priority'] ?? 'medium');
            $assigned_to = filter_var($_POST['assigned_to'] ?? null, FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare("UPDATE customer_services SET status = ?, priority = ?, assigned_to = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $priority, $assigned_to, $id]);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Status updated successfully'];
        } elseif ($_POST['service_action'] == 'delete') {
            $id = filter_var($_POST['service_id'], FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare("DELETE FROM customer_services WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Service ticket deleted successfully'];
        }
    } catch (Exception $e) { $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Error: ' . $e->getMessage()]; }
    header("Location: index.php#service");
    exit;
}

if (isset($_POST['feedback_action'])) {
    verify_admin_csrf('feedback');
    
    try {
        $id = filter_var($_POST['feedback_id'], FILTER_VALIDATE_INT);
        if ($_POST['feedback_action'] == 'approve') {
            $stmt = $pdo->prepare("UPDATE testimonials SET is_approved = 1, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Customer review approved and published!'];
        } elseif ($_POST['feedback_action'] == 'hide') {
            $stmt = $pdo->prepare("UPDATE testimonials SET is_approved = 0, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Customer review hidden successfully'];
        } elseif ($_POST['feedback_action'] == 'delete') {
            $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Customer review deleted successfully'];
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Error: ' . $e->getMessage()];
    }
    header("Location: index.php#feedback");
    exit;
}

if (isset($_POST['sales_action'])) {
    verify_admin_csrf('sales');
    
    try {
        if ($_POST['sales_action'] == 'add_sale') {
            $customer_name = htmlspecialchars(trim($_POST['customer_name']));
            $product_name = htmlspecialchars(trim($_POST['product_name']));
            $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
            $unit_price = filter_var($_POST['unit_price'], FILTER_VALIDATE_FLOAT);
            $payment_method = htmlspecialchars($_POST['payment_method']);
            $payment_status = htmlspecialchars($_POST['payment_status']);
            $sale_date = htmlspecialchars($_POST['sale_date'] ?? date('Y-m-d'));
            $total_price = $quantity * $unit_price;
            if (!$customer_name || !$product_name || !$quantity || !$unit_price || !$payment_method || !$payment_status) throw new Exception("All required fields must be filled");
            if ($quantity <= 0) throw new Exception("Quantity must be greater than 0");
            if ($unit_price <= 0) throw new Exception("Unit price must be greater than 0");
            $stmt = $pdo->prepare("INSERT INTO sales (customer_name, product_name, quantity, unit_price, total_price, payment_method, payment_status, sale_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$customer_name, $product_name, $quantity, $unit_price, $total_price, $payment_method, $payment_status, $sale_date]);
            $sale_id = $pdo->lastInsertId();
            
            
            if ($payment_status == 'paid') {
                $analytics_stmt = $pdo->prepare("INSERT INTO sales_analytics (sale_date, total_sales, booking_count) 
                                                VALUES (?, ?, 1) 
                                                ON DUPLICATE KEY UPDATE 
                                                total_sales = total_sales + ?,
                                                booking_count = booking_count + 1");
                $analytics_stmt->execute([$sale_date, $total_price, $total_price]);
            }
            
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => "Sale #$sale_id recorded successfully for $customer_name"];
        } elseif ($_POST['sales_action'] == 'update') {
            $sale_id = filter_var($_POST['sale_id'], FILTER_VALIDATE_INT);
            $customer_name = htmlspecialchars(trim($_POST['customer_name']));
            $product_name = htmlspecialchars(trim($_POST['product_name']));
            $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
            $unit_price = filter_var($_POST['unit_price'], FILTER_VALIDATE_FLOAT);
            $payment_method = htmlspecialchars($_POST['payment_method']);
            $payment_status = htmlspecialchars($_POST['payment_status']);
            $sale_date = htmlspecialchars($_POST['sale_date'] ?? date('Y-m-d'));
            $total_price = $quantity * $unit_price;
            if (!$sale_id || !$customer_name || !$product_name || !$quantity || !$unit_price || !$payment_method || !$payment_status) throw new Exception("All required fields must be filled");
            $stmt = $pdo->prepare("UPDATE sales SET customer_name = ?, product_name = ?, quantity = ?, unit_price = ?, total_price = ?, payment_method = ?, payment_status = ?, sale_date = ? WHERE id = ?");
            $stmt->execute([$customer_name, $product_name, $quantity, $unit_price, $total_price, $payment_method, $payment_status, $sale_date, $sale_id]);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => "Sale #$sale_id updated successfully"];
        } elseif ($_POST['sales_action'] == 'delete') {
            $sale_id = filter_var($_POST['sale_id'], FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
            $stmt->execute([$sale_id]);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Sale record deleted successfully'];
        } elseif ($_POST['sales_action'] == 'refund') {
            $sale_id = filter_var($_POST['sale_id'], FILTER_VALIDATE_INT);
            $sale_stmt = $pdo->prepare("SELECT total_price, sale_date FROM sales WHERE id = ?");
            $sale_stmt->execute([$sale_id]);
            $sale = $sale_stmt->fetch();
            
            $stmt = $pdo->prepare("UPDATE sales SET payment_status = 'refunded' WHERE id = ?");
            $stmt->execute([$sale_id]);
            
            
            $analytics_stmt = $pdo->prepare("UPDATE sales_analytics SET total_sales = total_sales - ? WHERE sale_date = ?");
            $analytics_stmt->execute([$sale['total_price'], $sale['sale_date']]);
            
            $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Sale refunded successfully'];
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Error: ' . $e->getMessage()];
        error_log("Sales action error: " . $e->getMessage());
    }
    header("Location: index.php#sales");
    exit;
}

if (isset($_POST['notification_action'])) {
    verify_admin_csrf('dashboard');
    
    if ($_POST['notification_action'] == 'mark_read') {
        $notification_id = filter_var($_POST['notification_id'], FILTER_VALIDATE_INT);
        try { $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_type = 'admin'"); $stmt->execute([$notification_id]); } catch (PDOException $e) { error_log("Notification error: " . $e->getMessage()); }
    } elseif ($_POST['notification_action'] == 'mark_all_read') {
        try { $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_type = 'admin' AND is_read = FALSE"); $stmt->execute([]); } catch (PDOException $e) { error_log("Notification error: " . $e->getMessage()); }
    } elseif ($_POST['notification_action'] == 'delete') {
        $notification_id = filter_var($_POST['notification_id'], FILTER_VALIDATE_INT);
        try { $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_type = 'admin'"); $stmt->execute([$notification_id]); } catch (PDOException $e) { error_log("Notification error: " . $e->getMessage()); }
    }
}

if (isset($_POST['admin_action'])) {
    verify_admin_csrf('admin');
    
    try {
        if ($_POST['admin_action'] == 'add') {
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $role = isset($_POST['role']) ? $_POST['role'] : '';
            $department = isset($_POST['department']) ? trim($_POST['department'] ?? '') : '';
            error_log("Admin add attempt - Username: $username, Full Name: $full_name, Email: $email, Role: $role");
            if (!empty($email)) { $email = filter_var($email, FILTER_VALIDATE_EMAIL); if (!$email) throw new Exception("Invalid email format"); }
            if (empty($username) || empty($password) || empty($full_name) || empty($email) || empty($role)) throw new Exception("All required fields must be filled correctly");
            if (strlen($password) < 6) throw new Exception("Password must be at least 6 characters");
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) throw new Exception("Username can only contain letters, numbers and underscore");
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ?"); $stmt_check->execute([$username]); if ($stmt_check->fetchColumn() > 0) throw new Exception("Username already exists");
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE email = ?"); $stmt_check->execute([$email]); if ($stmt_check->fetchColumn() > 0) throw new Exception("Email already registered");
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, full_name, email, role, department, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
            $result = $stmt->execute([$username, $hashedPassword, $full_name, $email, $role, $department]);
            if (!$result) { $errorInfo = $stmt->errorInfo(); throw new Exception("Database error: " . $errorInfo[2]); }
            $newAdminId = $pdo->lastInsertId();
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Admin user added successfully: ' . $full_name];
            try { $pdo->prepare("INSERT INTO notifications (user_type, title, message, type, related_id, related_type) VALUES ('admin', 'New Admin User', ?, 'info', ?, 'admin_user')")->execute(['User "' . $username . '" has been added as ' . $role, $newAdminId]); } catch (PDOException $e) { error_log("Notification error: " . $e->getMessage()); }
        } elseif ($_POST['admin_action'] == 'update') {
            $id = isset($_POST['admin_id']) ? filter_var($_POST['admin_id'], FILTER_VALIDATE_INT) : 0;
            $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $role = isset($_POST['role']) ? $_POST['role'] : '';
            $department = isset($_POST['department']) ? trim($_POST['department'] ?? '') : '';
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            if (!empty($email)) { $email = filter_var($email, FILTER_VALIDATE_EMAIL); if (!$email) throw new Exception("Invalid email format"); }
            if (!$id || empty($full_name) || empty($email) || empty($role)) throw new Exception("All required fields must be filled correctly");
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE email = ? AND id != ?"); $stmt_check->execute([$email, $id]); if ($stmt_check->fetchColumn() > 0) throw new Exception("Email already registered by another user");
            $stmt = $pdo->prepare("UPDATE admin_users SET full_name = ?, email = ?, role = ?, department = ?, is_active = ? WHERE id = ?");
            $result = $stmt->execute([$full_name, $email, $role, $department, $is_active, $id]);
            if (!$result) { $errorInfo = $stmt->errorInfo(); throw new Exception("Database error: " . $errorInfo[2]); }
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Admin user updated successfully: ' . $full_name];
        } elseif ($_POST['admin_action'] == 'delete') {
            $id = isset($_POST['admin_id']) ? filter_var($_POST['admin_id'], FILTER_VALIDATE_INT) : 0;
            if (!$id) throw new Exception("Invalid user ID");
            if ($id == ($_SESSION['admin_id'] ?? 0)) throw new Exception("Cannot delete your own account");
            if ($id == 1) throw new Exception("Cannot delete main admin user");
            $user_stmt = $pdo->prepare("SELECT username, full_name FROM admin_users WHERE id = ?"); $user_stmt->execute([$id]); $user = $user_stmt->fetch();
            if (!$user) throw new Exception("User not found");
            $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ?"); $result = $stmt->execute([$id]);
            if (!$result) { $errorInfo = $stmt->errorInfo(); throw new Exception("Database error: " . $errorInfo[2]); }
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Admin user deleted successfully: ' . $user['full_name']];
        } elseif ($_POST['admin_action'] == 'change_password') {
            $id = isset($_POST['admin_id']) ? filter_var($_POST['admin_id'], FILTER_VALIDATE_INT) : 0;
            $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
            $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            if (!$id) throw new Exception("Invalid user ID");
            if (empty($new_password) || empty($confirm_password)) throw new Exception("Password fields are required");
            if (strlen($new_password) < 6) throw new Exception("Password must be at least 6 characters");
            if ($new_password !== $confirm_password) throw new Exception("Passwords do not match");
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?"); $result = $stmt->execute([$hashedPassword, $id]);
            if (!$result) { $errorInfo = $stmt->errorInfo(); throw new Exception("Database error: " . $errorInfo[2]); }
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Password changed successfully'];
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Error: ' . $e->getMessage()];
        error_log("Admin action error: " . $e->getMessage());
    }
    header("Location: index.php#admin");
    exit;
}

try {
    
    $total_earnings = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status = 'Completed'")->fetchColumn() ?: 0;
    $pending_count = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'Pending'")->fetchColumn();
    $declined_count = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'Declined'")->fetchColumn();
    $total_count = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    $today_count = $pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE(appointment_date) = CURDATE()")->fetchColumn();
    $today_revenue = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status = 'Completed' AND DATE(appointment_date) = CURDATE()")->fetchColumn() ?: 0;
    
    
    $date_bookings_stmt = $pdo->query("SELECT DATE(appointment_date) as b_date, appointment_time, COUNT(*) as count FROM bookings WHERE status NOT IN ('Cancelled', 'Declined') GROUP BY DATE(appointment_date), appointment_time");
    $date_bookings_rows = $date_bookings_stmt->fetchAll(PDO::FETCH_ASSOC);
    $date_totals_map = [];
    $slot_totals_map = [];
    foreach ($date_bookings_rows as $row) {
        $d = $row['b_date'];
        $t = $row['appointment_time'];
        $c = (int)$row['count'];
        $date_totals_map[$d] = ($date_totals_map[$d] ?? 0) + $c;
        $slot_totals_map[$d . '|' . $t] = $c;
    }
    
    require_once __DIR__ . '/../includes/HttpSmsGateway.php';
    $smsGateway = new HttpSmsGateway($pdo);
    $sms_config = $smsGateway->getConfiguration();
    $sms_stats = $smsGateway->getStatistics();
    $sms_logs = $smsGateway->getSMSLogs(20);

    
    $vehicle_types_stmt = $pdo->query("SELECT * FROM vehicle_types ORDER BY type_name");
    $vehicle_types = $vehicle_types_stmt->fetchAll();
    
    
    $tint_stock_items = $pdo->query("SELECT * FROM tint_stock ORDER BY film_name")->fetchAll();
    $total_rolls = $pdo->query("SELECT SUM(quantity_rolls) FROM tint_stock")->fetchColumn() ?: 0;
    $inventory_value = $pdo->query("SELECT SUM(quantity_rolls * price_per_roll) FROM tint_stock")->fetchColumn() ?: 0;
    $unique_types = $pdo->query("SELECT COUNT(DISTINCT tint_type) FROM tint_stock")->fetchColumn() ?: 0;
    $low_stock_items = $pdo->query("SELECT * FROM tint_stock WHERE quantity_rolls <= min_rolls ORDER BY (quantity_rolls / min_rolls) ASC, quantity_rolls ASC")->fetchAll();
    $inventory_by_type = $pdo->query("SELECT LOWER(TRIM(COALESCE(NULLIF(tint_type, ''), film_name))) as type_key, LOWER(TRIM(COALESCE(NULLIF(tint_type, ''), film_name))) as tint_type, COUNT(*) as film_count, SUM(quantity_rolls) as total_rolls, SUM(quantity_rolls * price_per_roll) as total_value, AVG(price_per_roll) as avg_price, SUM(CASE WHEN quantity_rolls <= min_rolls THEN 1 ELSE 0 END) as low_stock_count FROM tint_stock GROUP BY type_key ORDER BY type_key")->fetchAll();
    $tint_rolls_map = [];
    $tint_price_map = [];
    foreach ($inventory_by_type as $inv) {
        $tk = strtolower(trim($inv['type_key']));
        if ($tk) {
            $tint_rolls_map[$tk] = (int)$inv['total_rolls'];
            $tint_price_map[$tk] = (float)($inv['avg_price'] ?? 0);
        }
    }
    
    
    $shade_price_map = [];
    $shade_price_rows = $pdo->query("SELECT tint_type, shade_prices FROM tint_stock WHERE shade_prices IS NOT NULL")->fetchAll();
    foreach ($shade_price_rows as $sp) {
        $prices = json_decode($sp['shade_prices'], true) ?: [];
        foreach ($prices as $sh => $pr) {
            $price_val = is_array($pr) ? ($pr['price'] ?? 0) : (float)$pr;
            if (!isset($shade_price_map[$sp['tint_type']][$sh])) {
                $shade_price_map[$sp['tint_type']][$sh] = [];
            }
            $shade_price_map[$sp['tint_type']][$sh][] = (float)$price_val;
        }
    }
    
    foreach ($shade_price_map as $type => &$shades) {
        foreach ($shades as $sh => &$arr) { $arr = round(array_sum($arr) / count($arr), 2); }
    }
    unset($shades, $arr);

    
    $shade_stock_map = [];
    foreach ($shade_price_rows as $sp) {
        $data = json_decode($sp['shade_prices'], true) ?: [];
        foreach ($data as $sh => $d) {
            $st = is_array($d) ? ($d['stock'] ?? 0) : 0;
            $shade_stock_map[$sp['tint_type']][$sh] = ($shade_stock_map[$sp['tint_type']][$sh] ?? 0) + $st;
        }
    }
    
    
    $service_tickets = $pdo->query("SELECT cs.*, a.full_name as assigned_to_name FROM customer_services cs LEFT JOIN admin_users a ON cs.assigned_to = a.id ORDER BY CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 END, cs.created_at DESC")->fetchAll();
    
    
    $sales_filter_type = $_GET['sales_filter'] ?? '';
    $sales_filter_date = $_GET['sales_date'] ?? '';
    $sales_filter_month = $_GET['sales_month'] ?? '';
    $sales_filter_year = $_GET['sales_year'] ?? '';

    $sales_sql = "SELECT * FROM sales WHERE 1=1";
    $sales_params = [];

    if ($sales_filter_type === 'date' && !empty($sales_filter_date)) {
        $sales_sql .= " AND DATE(sale_date) = ?";
        $sales_params[] = $sales_filter_date;
    } elseif ($sales_filter_type === 'month' && !empty($sales_filter_month)) {
        
        $sf_parts = explode('-', $sales_filter_month);
        if (count($sf_parts) === 2) {
            $sales_sql .= " AND YEAR(sale_date) = ? AND MONTH(sale_date) = ?";
            $sales_params[] = (int)$sf_parts[0];
            $sales_params[] = (int)$sf_parts[1];
        }
    } elseif ($sales_filter_type === 'year' && !empty($sales_filter_year)) {
        $sales_sql .= " AND YEAR(sale_date) = ?";
        $sales_params[] = (int)$sales_filter_year;
    }

    $sales_sql .= " ORDER BY sale_date DESC, created_at DESC";
    if (empty($sales_filter_type)) $sales_sql .= " LIMIT 50";

    $sales_stmt = $pdo->prepare($sales_sql);
    $sales_stmt->execute($sales_params);
    $recent_sales = $sales_stmt->fetchAll();

    
    $sales_filtered_total = 0;
    foreach ($recent_sales as $s) {
        if ($s['payment_status'] === 'paid') $sales_filtered_total += (float)$s['total_price'];
    }
    
    
    $notifications = $pdo->query("SELECT * FROM notifications WHERE (user_id = " . ($_SESSION['admin_id'] ?? 0) . " OR user_type = 'admin') ORDER BY created_at DESC LIMIT 20")->fetchAll();
    
    
    $admin_users = $pdo->query("SELECT * FROM admin_users ORDER BY role, full_name")->fetchAll();
    
    
    $sales_analytics = ['daily' => 0, 'weekly' => 0, 'monthly' => 0, 'yearly' => 0, 'declined_today' => 0, 'declined_week' => 0, 'declined_month' => 0, 'revenue_loss' => 0];
    try {
        $sales_analytics['daily'] = $pdo->query("SELECT SUM(total_sales) FROM sales_analytics WHERE sale_date = CURDATE()")->fetchColumn() ?: 0;
        $sales_analytics['weekly'] = $pdo->query("SELECT SUM(total_sales) FROM sales_analytics WHERE YEARWEEK(sale_date) = YEARWEEK(CURDATE())")->fetchColumn() ?: 0;
        $sales_analytics['monthly'] = $pdo->query("SELECT SUM(total_sales) FROM sales_analytics WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())")->fetchColumn() ?: 0;
        $sales_analytics['yearly'] = $pdo->query("SELECT SUM(total_sales) FROM sales_analytics WHERE YEAR(sale_date) = YEAR(CURDATE())")->fetchColumn() ?: 0;
        $sales_analytics['declined_today'] = $pdo->query("SELECT SUM(declined_count) FROM sales_analytics WHERE sale_date = CURDATE()")->fetchColumn() ?: 0;
        $sales_analytics['declined_week'] = $pdo->query("SELECT SUM(declined_count) FROM sales_analytics WHERE YEARWEEK(sale_date) = YEARWEEK(CURDATE())")->fetchColumn() ?: 0;
        $sales_analytics['declined_month'] = $pdo->query("SELECT SUM(declined_count) FROM sales_analytics WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())")->fetchColumn() ?: 0;
        $sales_analytics['revenue_loss'] = $pdo->query("SELECT SUM(declined_revenue_loss) FROM sales_analytics WHERE YEARWEEK(sale_date) = YEARWEEK(CURDATE())")->fetchColumn() ?: 0;
    } catch (PDOException $e) {}

    
    $booking_stats = ['total' => 0, 'completed' => 0, 'pending' => 0, 'approved' => 0, 'declined' => 0, 'conversion' => 0, 'avg_value' => 0, 'today' => 0, 'this_month' => 0];
    try {
        $booking_stats['total']     = (int)($pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn() ?: 0);
        $booking_stats['completed'] = (int)($pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'Completed'")->fetchColumn() ?: 0);
        $booking_stats['pending']   = (int)($pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'Pending'")->fetchColumn() ?: 0);
        $booking_stats['approved']  = (int)($pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('Approved','Confirmed')")->fetchColumn() ?: 0);
        $booking_stats['declined']  = (int)($pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('Declined','Cancelled')")->fetchColumn() ?: 0);
        $booking_stats['today']     = (int)($pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE(appointment_date) = CURDATE()")->fetchColumn() ?: 0);
        $booking_stats['this_month']= (int)($pdo->query("SELECT COUNT(*) FROM bookings WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetchColumn() ?: 0);
        $avg_raw = $pdo->query("SELECT AVG(total_price) FROM bookings WHERE status = 'Completed'")->fetchColumn();
        $booking_stats['avg_value'] = $avg_raw ? number_format((float)$avg_raw, 0) : 0;
        $booking_stats['conversion']= $booking_stats['total'] > 0 ? round(($booking_stats['completed'] / $booking_stats['total']) * 100) : 0;
    } catch (PDOException $e) {}
    
    
    try { 
        $top_packages_stmt = $pdo->query("SELECT p.id, p.name, COUNT(b.id) as sales, SUM(b.total_price) as revenue FROM bookings b JOIN tint_packages p ON b.package_id = p.id WHERE b.status = 'Completed' GROUP BY p.id, p.name ORDER BY sales DESC LIMIT 5"); 
        $top_packages = $top_packages_stmt->fetchAll(); 
    } catch (PDOException $e) { $top_packages = []; }
    
    
    $packages_stmt = $pdo->query("SELECT * FROM tint_packages ORDER BY display_order ASC, id ASC");
    $packages = $packages_stmt->fetchAll();
    $all_packages = $packages;

    
    $pdo->exec("CREATE TABLE IF NOT EXISTS package_model_prices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        package_id INT NOT NULL,
        car_model VARCHAR(200) NOT NULL,
        tint_type VARCHAR(50) NOT NULL DEFAULT 'carbon',
        shade_percentage INT NOT NULL DEFAULT 35,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        UNIQUE KEY pkg_car_combo (package_id, car_model, tint_type, shade_percentage),
        INDEX idx_pkg (package_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    try { $pdo->exec("ALTER TABLE package_model_prices ADD COLUMN tint_type VARCHAR(50) NOT NULL DEFAULT 'carbon' AFTER car_model"); } catch (PDOException $e2) {}
    try { $pdo->exec("ALTER TABLE package_model_prices ADD COLUMN shade_percentage INT NOT NULL DEFAULT 35 AFTER tint_type"); } catch (PDOException $e2) {}
    try { $pdo->exec("ALTER TABLE package_model_prices DROP INDEX pkg_car"); } catch (PDOException $e2) {}
    try { $pdo->exec("ALTER TABLE package_model_prices ADD UNIQUE KEY pkg_car_combo (package_id, car_model, tint_type, shade_percentage)"); } catch (PDOException $e2) {}
    try { $pdo->exec("ALTER TABLE package_model_prices ADD COLUMN wholesale_price DECIMAL(10,2) DEFAULT NULL AFTER price"); } catch (PDOException $e2) {}
    try { $pdo->exec("ALTER TABLE package_model_prices ADD COLUMN wholesale_min_qty INT DEFAULT 3 AFTER wholesale_price"); } catch (PDOException $e2) {}

    
    $all_car_models = [];
    $car_model_cats = [];
    try {
        $cm_rows = $pdo->query("SELECT full_name, model FROM car_models ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $suv_models = ['Fortuner','Everest','CR-V','Montero Sport','Tucson','Terra'];
        $van_models = ['Ertiga','HiAce','Urvan','Staria','Carnival'];
        $pickup_models = ['Hilux','Ranger','Navara','Strada','D-Max'];
        foreach ($cm_rows as $cmr) {
            $all_car_models[] = $cmr['full_name'];
            $m = $cmr['model'];
            if (in_array($m, $suv_models)) $cat = 'suv';
            elseif (in_array($m, $van_models)) $cat = 'van';
            elseif (in_array($m, $pickup_models)) $cat = 'pickup';
            else $cat = 'sedan';
            $car_model_cats[$cmr['full_name']] = $cat;
        }
    } catch (PDOException $e) {}

    
    $pkg_model_prices_all = [];
    $pkg_model_ws_all = [];
    try {
        $pmp_rows = $pdo->query("SELECT package_id, car_model, tint_type, shade_percentage, price, wholesale_price, wholesale_min_qty FROM package_model_prices")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($pmp_rows as $r) {
            $combo_key = $r['tint_type'] . '|' . $r['shade_percentage'];
            $pkg_model_prices_all[$r['package_id']][$combo_key][$r['car_model']] = (float)$r['price'];
            if ($r['wholesale_price'] !== null) {
                $pkg_model_ws_all[$r['package_id']][$combo_key][$r['car_model']] = [
                    'price' => (float)$r['wholesale_price'],
                    'min'   => (int)($r['wholesale_min_qty'] ?? 3),
                ];
            }
        }
    } catch (PDOException $e) {}
    
    
    $vehicle_types_list = [];
    try {
        $vehicle_types_list = $pdo->query("SELECT id, type_name FROM vehicle_types WHERE is_active=1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}

    
    $price_history_stmt = $pdo->query("SELECT ph.*, p.name as package_name, a.full_name as changed_by_name FROM price_history ph JOIN tint_packages p ON ph.package_id = p.id LEFT JOIN admin_users a ON ph.changed_by = a.id ORDER BY ph.changed_at DESC LIMIT 20");
    $price_history = $price_history_stmt->fetchAll();
    
    
    $car_models_list = [];
    try {
        $car_models_list = $pdo->query("SELECT cm.*, vt.type_name FROM car_models cm LEFT JOIN vehicle_types vt ON cm.vehicle_type_id = vt.id ORDER BY cm.brand ASC, cm.model ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
    
} catch (PDOException $e) {
    error_log("Data fetch error: " . $e->getMessage());
    $bookings = [];
    $packages = [];
    $price_history = [];
}

$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? '';
$vehicle_filter = $_GET['vehicle_type'] ?? 'all';
$search_term = $_GET['booking_search'] ?? '';

try {
    $sql = "SELECT b.*, v.type_name, tp.name as package_name, a.username as created_by_admin_name, ra.username as rejected_by_name 
            FROM bookings b 
            JOIN vehicle_types v ON b.vehicle_type_id = v.id 
            LEFT JOIN tint_packages tp ON b.package_id = tp.id 
            LEFT JOIN admin_users a ON b.created_by_admin = a.id 
            LEFT JOIN admin_users ra ON b.rejected_by = ra.id 
            WHERE 1=1";
    $params = [];
    if ($status_filter != 'all') { $sql .= " AND b.status = ?"; $params[] = htmlspecialchars($status_filter); }
    if (!empty($date_filter)) { $sql .= " AND DATE(b.appointment_date) = ?"; $params[] = htmlspecialchars($date_filter); }
    if ($vehicle_filter != 'all') { $sql .= " AND b.vehicle_type_id = ?"; $params[] = filter_var($vehicle_filter, FILTER_VALIDATE_INT); }
    if (!empty($search_term)) {
        $sql .= " AND (b.id LIKE ? OR b.customer_name LIKE ? OR b.phone LIKE ? OR b.vehicle_model LIKE ? OR b.email LIKE ?)";
        $search_param = '%' . $search_term . '%';
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    }
    $sql .= " ORDER BY b.id DESC, b.appointment_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) { $bookings = []; error_log("Bookings fetch error: " . $e->getMessage()); }

$tint_types = [];
try {
    $ts_stmt = $pdo->query("SELECT DISTINCT film_name, tint_type FROM tint_stock WHERE film_name IS NOT NULL AND film_name != '' ORDER BY film_name ASC");
    while ($ts_row = $ts_stmt->fetch()) {
        $k_slug = strtolower(trim($ts_row['tint_type'] ?: $ts_row['film_name']));
        $v_name = trim($ts_row['film_name']);
        if ($k_slug && $v_name) {
            $tint_types[$k_slug] = $v_name;
        }
    }
} catch (Exception $e) {}

$default_tint_types = ['dyed' => 'Dyed Film', 'metalized' => 'Metalized Film', 'carbon' => 'Carbon Film', 'hybrid' => 'Hybrid Film', 'ceramic' => 'Ceramic Film', 'nano-ceramic' => 'Nano-Ceramic Film', 'crystalline' => 'Crystalline Film', 'infrared' => 'Infrared Film', 'security' => 'Security Film'];
foreach ($default_tint_types as $dk => $dv) {
    if (!isset($tint_types[$dk])) {
        $tint_types[$dk] = $dv;
    }
}
$payment_methods = ['gcash' => 'GCash'];

$sched_date = !empty($date_filter) ? $date_filter : date('Y-m-d');
try {
    $sched_stmt = $pdo->prepare("
        SELECT b.id, b.customer_name, b.phone, b.vehicle_model, b.appointment_date,
               b.status, b.tint_type, b.shade_percentage, b.installation_time,
               v.type_name
        FROM bookings b
        LEFT JOIN vehicle_types v ON b.vehicle_type_id = v.id
        WHERE DATE(b.appointment_date) = ?
          AND b.status NOT IN ('Cancelled','Declined')
        ORDER BY b.appointment_date ASC
    ");
    $sched_stmt->execute([$sched_date]);
    $sched_bookings = $sched_stmt->fetchAll();
} catch (PDOException $e) { $sched_bookings = []; }

try {
    $sched_month = date('Y-m', strtotime($sched_date));
    $month_stmt = $pdo->prepare("
        SELECT DATE(appointment_date) as d, COUNT(*) as cnt
        FROM bookings
        WHERE DATE_FORMAT(appointment_date,'%Y-%m') = ?
          AND status NOT IN ('Cancelled','Declined')
        GROUP BY DATE(appointment_date)
    ");
    $month_stmt->execute([$sched_month]);
    $sched_month_data = [];
    foreach ($month_stmt->fetchAll() as $row) $sched_month_data[$row['d']] = (int)$row['cnt'];
} catch (PDOException $e) { $sched_month_data = []; }

try {
    $full_cal_stmt = $pdo->prepare("
        SELECT b.id, b.customer_name, b.phone, b.address,
               b.vehicle_model, b.vehicle_year,
               b.tint_type, b.shade_percentage, b.warranty_years,
               b.installation_time, b.total_price,
               b.appointment_date, b.appointment_time, b.status,
               b.payment_method, b.payment_type, b.payment_status,
               b.payment_reference, b.rejection_reason, b.package_id,
               v.type_name, tp.name AS package_name, au.full_name AS rejected_by_name
        FROM bookings b
        LEFT JOIN vehicle_types v  ON b.vehicle_type_id = v.id
        LEFT JOIN tint_packages tp ON b.package_id      = tp.id
        LEFT JOIN admin_users au   ON b.rejected_by     = au.id
        WHERE DATE_FORMAT(b.appointment_date,'%Y-%m') = ?
          AND b.status NOT IN ('Cancelled','Declined')
        ORDER BY b.appointment_date ASC
    ");
    $full_cal_stmt->execute([$sched_month]);
    $sched_month_bookings = [];
    foreach ($full_cal_stmt->fetchAll() as $fcr) {
        $fd = date('Y-m-d', strtotime($fcr['appointment_date']));
        $sched_month_bookings[$fd][] = [
            'id'                => (int)$fcr['id'],
            'customer_name'     => $fcr['customer_name'],
            'phone'             => $fcr['phone'] ?? '',
            'address'           => $fcr['address'] ?? '',
            'vehicle_model'     => $fcr['vehicle_model'] ?? '',
            'vehicle_year'      => $fcr['vehicle_year'] ?? '',
            'type_name'         => $fcr['type_name'] ?? '',
            'package'           => $fcr['package_name'] ?? '',
            'package_id'        => $fcr['package_id'] ?? '',
            'price'             => number_format((float)$fcr['total_price'], 2),
            'date'              => date('F j, Y', strtotime($fcr['appointment_date'])),
            'time'              => $fcr['appointment_time'] ? preg_replace_callback('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', function($m) {
                $h = (int)$m[1]; if (strtoupper($m[3]) === 'PM' && $h < 12) $h += 12; if (strtoupper($m[3]) === 'AM' && $h === 12) $h = 0;
                return sprintf('%02d:%s', $h, $m[2]);
            }, trim($fcr['appointment_time'])) : '—',
            'status'            => $fcr['status'],
            'tint_type'         => $fcr['tint_type']         ?? 'carbon',
            'shade'             => (string)($fcr['shade_percentage'] ?? '35'),
            'warranty'          => (int)($fcr['warranty_years'] ?? 3),
            'installation'      => $fcr['installation_time']  ?? '2-3 hours',
            'payment_method'    => $fcr['payment_method']     ?? 'GCash',
            'payment_type'      => $fcr['payment_type']       ?? 'full',
            'payment_status'    => $fcr['payment_status']     ?? 'Pending',
            'payment_reference' => $fcr['payment_reference']  ?? '',
            'rejection_reason'  => $fcr['rejection_reason']   ?? '',
            'rejected_by'       => $fcr['rejected_by_name']   ?? '',
        ];
    }
} catch (PDOException $e) { $sched_month_bookings = []; }

$payment_status_colors = ['Pending' => 'warning', 'Pending Verification' => 'info', 'Paid' => 'success', 'Failed' => 'danger', 'Refunded' => 'secondary'];
$admin_roles = ['admin', 'manager', 'staff'];
$tint_shades = [5, 10, 15, 20, 30, 35, 40, 50, 60, 70, 80, 90];

$customers = [];
try {
    $customers = $pdo->query("
        SELECT u.id, u.full_name, u.email, u.phone, u.created_at,
               COUNT(b.id)                                       AS total_bookings,
               SUM(CASE WHEN b.status = 'Completed' THEN 1 ELSE 0 END) AS completed_bookings,
               SUM(CASE WHEN b.status = 'Pending'   THEN 1 ELSE 0 END) AS pending_bookings,
               SUM(CASE WHEN b.status = 'Declined'  THEN 1 ELSE 0 END) AS declined_bookings,
               SUM(CASE WHEN b.status = 'Completed' THEN b.total_price ELSE 0 END) AS total_spent,
               MAX(b.appointment_date)                          AS last_booking_date,
               MAX(b.status)                                    AS last_status,
               GROUP_CONCAT(DISTINCT tp.name ORDER BY tp.name SEPARATOR ', ') AS packages_used
        FROM users u
        LEFT JOIN bookings b ON b.user_id = u.id
        LEFT JOIN tint_packages tp ON b.package_id = tp.id
        GROUP BY u.id
        ORDER BY total_bookings DESC, u.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) { $customers = []; }
$total_customers = count($customers);

try {
    $pending_feedback_count = $pdo->query("SELECT COUNT(*) FROM testimonials WHERE is_approved = 0")->fetchColumn();
    $approved_feedback_count = $pdo->query("SELECT COUNT(*) FROM testimonials WHERE is_approved = 1")->fetchColumn();
    $total_feedback = $pending_feedback_count + $approved_feedback_count;
    $avg_rating = 0;
    if ($approved_feedback_count > 0) { $avg = $pdo->query("SELECT AVG(rating) FROM testimonials WHERE is_approved = 1")->fetchColumn(); $avg_rating = number_format($avg, 1); }
    $all_testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) { $pending_feedback_count = 0; $approved_feedback_count = 0; $total_feedback = 0; $avg_rating = 0; $recent_pending_feedback = []; }

$admin_id_for_notif = (int)($_SESSION['admin_id'] ?? 0);
$unreadNotifications = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE user_type = 'admin' AND is_read = FALSE")->fetchColumn();
$admin_notifications = $pdo->query("SELECT id, title, message, type, is_read, related_id, related_type, created_at FROM notifications WHERE user_type = 'admin' ORDER BY created_at DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Rg Cartint Works Davao</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?= filemtime(__DIR__.'/../assets/css/admin.css') ?>">
    <script>
    
    
    var SECTIONS = ['dashboard','bookings','schedule','packages','sales','tint-stock','service','feedback','customers','admin','sms-gateway'];
    function showSection(id) {
        SECTIONS.forEach(function(s) {
            var el = document.getElementById('section-' + s);
            var link = document.getElementById('nav-' + s);
            if (el) {
                var isActive = s === id;
                el.classList.toggle('active', isActive);
                el.style.display = isActive ? 'block' : 'none';
                el.style.visibility = 'visible';
                el.style.opacity = '1';
            }
            if (link) link.classList.toggle('active', s === id);
        });
        var titleEl = document.getElementById('topbarTitle');
        var TITLES = {dashboard:'Dashboard',bookings:'Bookings',schedule:'Schedule',packages:'Packages',sales:'Sales','tint-stock':'Tint Stock',service:'Support',feedback:'Feedback',customers:'Customers',admin:'Admin Users','sms-gateway':'SMS Gateway'};
        if (titleEl) titleEl.textContent = TITLES[id] || 'Dashboard';
        var cleanUrl = window.location.pathname + '#' + id;
        history.pushState(null, null, cleanUrl);
    }
    function openSidebar() {
        var sb = document.getElementById('adminSidebar');
        var ov = document.getElementById('sidebarOverlay');
        if (sb) sb.classList.add('open');
        if (ov) ov.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        var sb = document.getElementById('adminSidebar');
        var ov = document.getElementById('sidebarOverlay');
        if (sb) sb.classList.remove('open');
        if (ov) ov.classList.remove('active');
        document.body.style.overflow = '';
    }
    window.showSection = showSection;
    window.openSidebar = openSidebar;
    window.closeSidebar = closeSidebar;
    
    window.selectTintType = function(){};
    window.onShadeChange = function(){};
    window.filterCarModels = function(){};
    window.applyBulkPrice = function(){};
    window.toggleCmRow = function(){};
    window.syncWindowsField = function(){};
    window._setWindowChips = function(){};
    window._shadePriceMap = <?= json_encode($shade_price_map) ?>;
    window._tintRollsMap  = <?= json_encode($tint_rolls_map) ?>;
    </script>

    <style>
        .admin-notif-row {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border);
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            cursor: pointer;
        }
        .admin-notif-row:hover {
            background: #f8fafc !important;
        }
        .admin-notif-row.unread {
            background: #fffdf0;
            border-left: 3px solid #D4A017;
        }
        .admin-notif-row.active-notif {
            background: #fef3c7 !important;
            border-left: 4px solid #b45309 !important;
            box-shadow: inset 0 0 8px rgba(212, 160, 23, 0.25);
        }
        .admin-notif-row.active-notif strong {
            color: #78350f !important;
        }
        .notif-jump-target {
            outline: 2px solid rgba(212,160,23,.45);
            box-shadow: 0 0 0 4px rgba(212,160,23,.12);
            border-radius: 12px;
            transition: box-shadow .2s ease, outline-color .2s ease;
        }
    </style>

<style>

.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
div.dataTables_wrapper div.dataTables_length,
div.dataTables_wrapper div.dataTables_filter {
    padding-top: 6px !important;
    padding-bottom: 12px !important;
}
.dataTables_wrapper .dataTables_length label,
div.dataTables_wrapper div.dataTables_length label {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    color: #475569 !important;
    margin: 0 !important;
    white-space: nowrap !important;
    line-height: 1 !important;
}
.dataTables_wrapper .dataTables_length select,
div.dataTables_wrapper div.dataTables_length select.form-select,
div.dataTables_wrapper div.dataTables_length select.form-select-sm {
    display: inline-block !important;
    width: auto !important;
    min-width: 70px !important;
    height: 36px !important;
    line-height: 36px !important;
    padding: 0 28px 0 10px !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 8px !important;
    background-color: #ffffff !important;
    font-family: inherit !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    margin: 0 4px !important;
    vertical-align: middle !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}
.dataTables_wrapper .dataTables_filter label,
div.dataTables_wrapper div.dataTables_filter label {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: flex-end !important;
    gap: 8px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    color: #475569 !important;
    margin: 0 !important;
    white-space: nowrap !important;
    line-height: 1 !important;
}
.dataTables_wrapper .dataTables_filter input,
div.dataTables_wrapper div.dataTables_filter input.form-control,
div.dataTables_wrapper div.dataTables_filter input.form-control-sm {
    display: inline-block !important;
    width: 220px !important;
    max-width: 100% !important;
    height: 36px !important;
    padding: 0 12px !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 8px !important;
    background-color: #ffffff !important;
    font-family: inherit !important;
    font-size: 13px !important;
    color: #0f172a !important;
    margin: 0 0 0 4px !important;
    vertical-align: middle !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}
.dataTables_wrapper .dataTables_filter input:focus,
.dataTables_wrapper .dataTables_length select:focus,
div.dataTables_wrapper div.dataTables_filter input:focus,
div.dataTables_wrapper div.dataTables_length select:focus {
    outline: none !important;
    border-color: #D4A017 !important;
    box-shadow: 0 0 0 3px rgba(212, 160, 23, 0.2) !important;
}
</style>
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<div class="toast-wrap" id="toastWrap">
<?php if (isset($_SESSION['flash_message'])): ?>
    <?php $fm = $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
    <?php
        $icons = ['success' => 'fa-circle-check', 'danger' => 'fa-circle-xmark', 'warning' => 'fa-triangle-exclamation', 'info' => 'fa-circle-info'];
        $icon = $icons[$fm['type']] ?? 'fa-circle-info';
    ?>
    <div class="toast-card toast-<?= $fm['type'] ?>" id="mainToast">
        <div class="toast-icon-wrap">
            <i class="fas <?= $icon ?>"></i>
        </div>
        <div class="toast-msg"><?= htmlspecialchars($fm['message']) ?></div>
        <button class="toast-close" onclick="this.closest('.toast-card').remove()">
            <i class="fas fa-xmark"></i>
        </button>
    </div>
<?php endif; ?>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-inner" style="gap:10px;">
            <div class="sidebar-brand-icon" style="background:none;box-shadow:none;width:auto;height:auto;display:flex;align-items:center;justify-content:center;">
                <svg width="26" height="26" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 2L20 8L26 10L24 16L26 22L20 24L16 30L12 24L6 22L8 16L6 10L12 8L16 2Z" fill="#D4A017"/>
                    <path d="M16 8L19 12L24 14L22 18L24 22L19 24L16 28L13 24L8 22L10 18L8 14L13 12L16 8Z" fill="#0A0A0A"/>
                    <path d="M16 12L18 15L22 16L20 19L22 22L18 23L16 26L14 23L10 22L12 19L10 16L14 15L16 12Z" fill="#F5C842"/>
                </svg>
            </div>
            <div>
                <div class="sidebar-brand-text" style="font-family:'Bebas Neue', sans-serif;font-size:1.35rem;letter-spacing:.05em;color:#FFFFFF;line-height:1.1;font-weight:700;">RG CARTINTWORKS</div>
                <div class="sidebar-brand-sub" style="font-size:9px;color:rgba(255,255,255,.5);font-weight:500;text-transform:uppercase;letter-spacing:.05em;margin-top:1px;">Admin Panel</div>
            </div>
        </div>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-section-label">Main</div>
    </div>
    <ul class="sidebar-nav">
        <li class="sidebar-nav-item">
            <a href="#dashboard" class="sidebar-nav-link active" id="nav-dashboard" onclick="showSection('dashboard');return false;" data-tooltip="Dashboard">
                <div class="sidebar-nav-icon"><i class="fas fa-gauge-high"></i></div>
                <span class="nav-label">Dashboard</span>
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="#bookings" class="sidebar-nav-link" id="nav-bookings" onclick="showSection('bookings');return false;" data-tooltip="Bookings">
                <div class="sidebar-nav-icon"><i class="fas fa-calendar-check"></i></div>
                <span class="nav-label">Bookings</span>
                <?php
                    $pending_bookings_count = 0;
                    try { $pb = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='Pending'"); $pending_bookings_count = (int)$pb->fetchColumn(); } catch(PDOException $e) {}
                ?>
                <?php if($pending_bookings_count > 0): ?>
                <span class="sidebar-badge"><?= $pending_bookings_count ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="#schedule" class="sidebar-nav-link" id="nav-schedule" onclick="showSection('schedule');return false;" data-tooltip="Schedule">
                <div class="sidebar-nav-icon"><i class="fas fa-calendar-days"></i></div>
                <span class="nav-label">Schedule</span>
            </a>
        </li>
    </ul>
    <div class="sidebar-section">
        <div class="sidebar-section-label">Management</div>
    </div>
    <ul class="sidebar-nav">
        <li class="sidebar-nav-item">
            <a href="#packages" class="sidebar-nav-link" id="nav-packages" onclick="showSection('packages');return false;" data-tooltip="Packages">
                <div class="sidebar-nav-icon"><i class="fas fa-box-open"></i></div>
                <span class="nav-label">Packages</span>
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="#sales" class="sidebar-nav-link" id="nav-sales" onclick="showSection('sales');return false;" data-tooltip="Sales">
                <div class="sidebar-nav-icon"><i class="fas fa-chart-line"></i></div>
                <span class="nav-label">Sales</span>
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="#tint-stock" class="sidebar-nav-link" id="nav-tint-stock" onclick="showSection('tint-stock');return false;" data-tooltip="Tint Stock">
                <div class="sidebar-nav-icon"><i class="fas fa-film"></i></div>
                <span class="nav-label">Tint Stock</span>
            </a>
        </li>
    </ul>
    <div class="sidebar-section">
        <div class="sidebar-section-label">Communication</div>
    </div>
    <ul class="sidebar-nav">
        <li class="sidebar-nav-item">
            <a href="#service" class="sidebar-nav-link" id="nav-service" onclick="showSection('service');return false;" data-tooltip="Support">
                <div class="sidebar-nav-icon"><i class="fas fa-headset"></i></div>
                <span class="nav-label">Support</span>
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="#feedback" class="sidebar-nav-link" id="nav-feedback" onclick="showSection('feedback');return false;" data-tooltip="Feedback">
                <div class="sidebar-nav-icon"><i class="fas fa-star"></i></div>
                <span class="nav-label">Feedback</span>
                <?php if($pending_feedback_count > 0): ?>
                <span class="sidebar-badge"><?= $pending_feedback_count ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>
    <div class="sidebar-section">
        <div class="sidebar-section-label">System</div>
    </div>
    <ul class="sidebar-nav">
        <li class="sidebar-nav-item">
            <a href="#customers" class="sidebar-nav-link" id="nav-customers" onclick="showSection('customers');return false;" data-tooltip="Customers">
                <div class="sidebar-nav-icon"><i class="fas fa-users"></i></div>
                <span class="nav-label">Customers</span>
                <?php if($total_customers > 0): ?>
                <span class="sidebar-badge gold"><?= $total_customers ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="#admin" class="sidebar-nav-link" id="nav-admin" onclick="showSection('admin');return false;" data-tooltip="Admin Users">
                <div class="sidebar-nav-icon"><i class="fas fa-users-gear"></i></div>
                <span class="nav-label">Admin Users</span>
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="#sms-gateway" class="sidebar-nav-link" id="nav-sms-gateway" onclick="showSection('sms-gateway');return false;" data-tooltip="SMS Gateway">
                <div class="sidebar-nav-icon"><i class="fas fa-comment-sms"></i></div>
                <span class="nav-label">SMS Gateway</span>
            </a>
        </li>
    </ul>
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar"><?= strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1)) ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></div>
                <div class="sidebar-user-role">Administrator</div>
            </div>
        </div>
    </div>
</aside>

<header class="admin-topbar">
    <button class="topbar-toggle" onclick="openSidebar()" title="Menu">
        <i class="fas fa-bars"></i>
    </button>
    <div class="topbar-title" id="topbarTitle">Dashboard</div>
    <div style="flex:1"></div>
    <div class="date-chip">
        <i class="far fa-calendar"></i>
        <?= date('F j, Y') ?>
    </div>
    <div class="date-chip">
        <i class="far fa-clock"></i>
        <span id="liveClock"><?= date('h:i A') ?></span>
    </div>
    
    <div class="dropdown">
        <button class="topbar-icon-btn" id="adminNotifBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
            <i class="fas fa-bell"></i>
            <span class="admin-notif-badge<?= $unreadNotifications > 0 ? '' : ' d-none' ?>" id="adminNotifBadge"><?= $unreadNotifications > 9 ? '9+' : $unreadNotifications ?></span>
        </button>
        <div class="dropdown-menu dropdown-menu-end admin-notif-panel p-0" id="adminNotifPanel">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom" style="border-color:var(--border)!important;">
                <span style="font-size:13px;font-weight:700;color:var(--text-primary);"><i class="fas fa-bell me-2" style="color:var(--gold);"></i>Notifications</span>
                <button type="button" id="adminMarkAllReadBtn" style="background:none;border:none;font-size:12px;color:var(--gold);font-weight:600;cursor:pointer;padding:0;">Mark all read</button>
            </div>
            <div id="adminNotifList">
                <?php if (empty($admin_notifications)): ?>
                <div class="p-4 text-center text-muted small">No notifications yet</div>
                <?php else: foreach ($admin_notifications as $an):
                    $an_icon = 'fa-info-circle'; $an_cls = 'bg-info';
                    if ($an['type'] === 'success') { $an_icon = 'fa-check-circle'; $an_cls = 'bg-success'; }
                    elseif ($an['type'] === 'warning') { $an_icon = 'fa-exclamation-triangle'; $an_cls = 'bg-warning'; }
                    elseif ($an['type'] === 'danger') { $an_icon = 'fa-times-circle'; $an_cls = 'bg-danger'; }
                ?>
                <div class="admin-notif-row<?= ((int)$an['is_read'] === 0) ? ' unread' : '' ?>" data-id="<?= (int)$an['id'] ?>" data-type="<?= htmlspecialchars($an['related_type'] ?? '') ?>" data-rel-id="<?= (int)($an['related_id'] ?? 0) ?>" data-title="<?= htmlspecialchars($an['title']) ?>" data-msg="<?= htmlspecialchars($an['message']) ?>" style="cursor:pointer;">
                    <div class="admin-notif-icon <?= $an_cls ?>"><i class="fas <?= $an_icon ?> fa-xs"></i></div>
                    <div class="flex-grow-1" style="min-width:0;">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <strong style="font-size:12px;line-height:1.3;color:var(--text-primary);"><?= htmlspecialchars($an['title']) ?></strong>
                            <form method="POST" style="margin:0;flex-shrink:0;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <input type="hidden" name="notification_id" value="<?= (int)$an['id'] ?>">
                                <input type="hidden" name="notification_action" value="delete">
                                <button type="submit" class="btn-close" style="width:.45em;height:.45em;opacity:.4;"></button>
                            </form>
                        </div>
                        <p style="font-size:11px;color:var(--text-secondary);margin:2px 0;"><?= htmlspecialchars($an['message']) ?></p>
                        <div style="font-size:.65rem;color:var(--text-muted);margin-top:3px;"><?= date('M d, h:i A', strtotime($an['created_at'])) ?></div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
    <div class="dropdown">
        <a class="topbar-user" href="#" data-bs-toggle="dropdown" aria-expanded="false" style="cursor:pointer;">
            <div class="topbar-user-avatar"><?= strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1)) ?></div>
            <div class="topbar-user-info">
                <div class="topbar-user-name"><?= htmlspecialchars($_SESSION['name'] ?? 'Administrator') ?></div>
                <div class="topbar-user-role">Administrator</div>
            </div>
            <i class="fas fa-chevron-down" style="font-size:10px;color:var(--text-muted);margin-left:4px;"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" style="min-width:200px;border-radius:10px;border:1px solid var(--border);box-shadow:0 8px 24px rgba(0,0,0,.1);padding:6px 0;">
            <li>
                <button class="dropdown-item" style="font-size:13px;" data-bs-toggle="modal" data-bs-target="#createBookingModal">
                    <i class="fas fa-plus me-2" style="color:var(--gold);"></i>New Booking
                </button>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item text-danger" href="#" style="font-size:13px;" onclick="event.preventDefault();rgConfirm({title:'Logout',message:'Are you sure you want to logout?',confirmText:'Logout',type:'warning',onConfirm:function(){window.location='logout.php';}})">
                    <i class="fas fa-arrow-right-from-bracket me-2"></i>Logout
                </a>
            </li>
        </ul>
    </div>
</header>

<div class="admin-main">

    
    
    
    <div id="section-dashboard" class="section-card active">
        <div class="section-card-header">
            <div class="section-card-title">
                <div class="title-icon" style="background:var(--gold-pale);color:var(--gold)"><i class="fas fa-gauge-high"></i></div>
                Dashboard Overview
            </div>
            <div style="display:flex;gap:10px;align-items:center">
                <div class="date-chip"><i class="far fa-calendar"></i> <?= date('F j, Y') ?></div>
            </div>
        </div>

        <div class="section-card-body">
        <div class="metrics-grid">
            <div class="metric-card accent-gold" onclick="showSection('sales')" style="cursor:pointer">
                <div class="metric-icon icon-gold"><i class="fas fa-sack-dollar"></i></div>
                <div class="metric-value">₱<?= number_format($today_revenue, 0) ?></div>
                <div class="metric-label">Today's Revenue</div>
            </div>
            <div class="metric-card accent-gold" onclick="showSection('sales')" style="cursor:pointer">
                <div class="metric-icon icon-gold"><i class="fas fa-receipt"></i></div>
                <div class="metric-value"><?= count($recent_sales) ?></div>
                <div class="metric-label">Total Sales</div>
            </div>
            <div class="metric-card accent-amber" onclick="showSection('bookings')" style="cursor:pointer">
                <div class="metric-icon icon-amber"><i class="fas fa-hourglass-half"></i></div>
                <div class="metric-value"><?= $pending_count ?></div>
                <div class="metric-label">Pending Bookings</div>
            </div>
            <div class="metric-card accent-red" onclick="showSection('bookings')" style="cursor:pointer">
                <div class="metric-icon icon-red"><i class="fas fa-ban"></i></div>
                <div class="metric-value"><?= $declined_count ?></div>
                <div class="metric-label">Declined</div>
            </div>
            <div class="metric-card accent-red" onclick="showSection('tint-stock')" style="cursor:pointer">
                <div class="metric-icon icon-red"><i class="fas fa-triangle-exclamation"></i></div>
                <div class="metric-value"><?= count($low_stock_items) ?></div>
                <div class="metric-label">Low Stock Films</div>
            </div>
            <div class="metric-card accent-blue" onclick="showSection('tint-stock')" style="cursor:pointer">
                <div class="metric-icon icon-blue"><i class="fas fa-boxes-stacked"></i></div>
                <div class="metric-value"><?= $total_rolls ?></div>
                <div class="metric-label">Total Rolls</div>
            </div>
            <div class="metric-card accent-gold" onclick="showSection('tint-stock')" style="cursor:pointer">
                <div class="metric-icon icon-gold"><i class="fas fa-peso-sign"></i></div>
                <div class="metric-value">₱<?= number_format($inventory_value/1000, 0) ?>K</div>
                <div class="metric-label">Inventory Value</div>
            </div>
            <div class="metric-card accent-blue" onclick="showSection('service')" style="cursor:pointer">
                <div class="metric-icon icon-blue"><i class="fas fa-headset"></i></div>
                <div class="metric-value"><?= count($service_tickets) ?></div>
                <div class="metric-label">Support Tickets</div>
            </div>
            <div class="metric-card accent-amber" onclick="showSection('feedback')" style="cursor:pointer">
                <div class="metric-icon icon-amber"><i class="fas fa-star-half-stroke"></i></div>
                <div class="metric-value"><?= $pending_feedback_count ?></div>
                <div class="metric-label">Pending Feedback</div>
            </div>
        </div>
        </div>
    </div>

    
    
    
    <div id="section-bookings" class="section-card">
        <div class="section-card-header">
            <div class="section-card-title">
                <div class="title-icon" style="background:var(--info-bg);color:var(--info)"><i class="fas fa-calendar-check"></i></div>
                Bookings
            </div>
        </div>

                <div class="filter-bar mb-3">
                    <form method="GET" class="search-wrap flex-wrap gap-2">
                        <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                        <input type="hidden" name="vehicle_type" value="<?= htmlspecialchars($vehicle_filter) ?>">
                        <input type="hidden" name="date" value="<?= htmlspecialchars($date_filter) ?>">
                        <div class="search-input-wrap" style="min-width:260px;flex:1">
                            <i class="fas fa-search"></i>
                            <input type="text" class="search-input" name="booking_search"
                                   placeholder="Search by ID, name, phone, vehicle..."
                                   value="<?= htmlspecialchars($search_term) ?>">
                        </div>
                        <select name="status" class="form-select-sm2" onchange="this.form.submit()">
                            <option value="all" <?= $status_filter=='all'?'selected':'' ?>>All Status</option>
                            <option value="Pending" <?= $status_filter=='Pending'?'selected':'' ?>>⏳ Pending</option>
                            <option value="Approved" <?= $status_filter=='Approved'?'selected':'' ?>>✅ Approved</option>
                            <option value="Completed" <?= $status_filter=='Completed'?'selected':'' ?>>🏆 Completed</option>
                            <option value="Cancelled" <?= $status_filter=='Cancelled'?'selected':'' ?>>❌ Cancelled</option>
                            <option value="Declined" <?= $status_filter=='Declined'?'selected':'' ?>>🚫 Declined</option>
                        </select>
                        <select name="vehicle_type" class="form-select-sm2" onchange="this.form.submit()">
                            <option value="all" <?= $vehicle_filter=='all'?'selected':'' ?>>All Vehicles</option>
                            <?php foreach($vehicle_types as $vt): ?>
                            <option value="<?= $vt['id'] ?>" <?= $vehicle_filter==$vt['id']?'selected':'' ?>><?= htmlspecialchars($vt['type_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="date" name="date" class="form-select-sm2" value="<?= htmlspecialchars($date_filter) ?>" onchange="this.form.submit()">
                        <button type="submit" class="header-btn btn-create" style="padding:8px 16px">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if(!empty($search_term)): ?>
                        <a href="index.php#bookings" class="header-btn btn-logout" style="padding:8px 14px;text-decoration:none">
                            <i class="fas fa-xmark"></i> Clear
                        </a>
                        <?php endif; ?>
                    </form>
                    <div style="margin-top:10px;font-size:12px;color:var(--text-muted)">
                        <i class="fas fa-info-circle" style="margin-right:4px"></i>
                        <?= count($bookings) ?> booking(s) found
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="styled-table" id="serviceTicketsTable">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag" style="margin-right:4px"></i>ID</th>
                                <th><i class="fas fa-user" style="margin-right:4px"></i>Customer</th>
                                <th><i class="far fa-calendar" style="margin-right:4px"></i>Appointment</th>
                                <th><i class="fas fa-clock" style="margin-right:4px"></i>Time</th>
                                <th><i class="fas fa-car" style="margin-right:4px"></i>Vehicle</th>
                                <th><i class="fas fa-film" style="margin-right:4px"></i>Package</th>
                                <th><i class="fas fa-credit-card" style="margin-right:4px"></i>Payment</th>
                                <th><i class="fas fa-peso-sign" style="margin-right:4px"></i>Amount</th>
                                <th><i class="fas fa-circle-dot" style="margin-right:4px"></i>Status</th>
                                <th class="no-sort text-end" style="text-align:right"><i class="fas fa-sliders" style="margin-right:4px"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($bookings as $b):
                                $package_name = !empty($b['package_name']) ? $b['package_name'] : 'Custom Package';
                                $payment_method = $b['payment_method'] ?? 'Cash';
                                $pm_cls = match(strtolower($payment_method)) { 'cash'=>'pay-cash','gcash'=>'pay-gcash','card'=>'pay-card','bank transfer'=>'pay-bank',default=>'badge-muted'};
                                $pm_icon = match(strtolower($payment_method)) { 'cash'=>'fa-money-bill-wave','gcash'=>'fa-mobile-screen','card'=>'fa-credit-card','bank transfer'=>'fa-building-columns',default=>'fa-circle-dollar'};
                                $payment_status = $b['payment_status'] ?? 'Pending';
                                $ps_cls = match($payment_status) { 'Paid'=>'badge-success','Pending'=>'badge-warning','Failed'=>'badge-danger','Refunded'=>'badge-muted',default=>'badge-info'};
                                $s_cls = match($b['status']) { 
                                    'Pending'=>'badge-pending',
                                    'Approved'=>'badge-approved',
                                    'Completed'=>'badge-completed',
                                    'Cancelled'=>'badge-cancelled',
                                    'Declined'=>'badge-declined',
                                    default=>'badge-muted'
                                };
                                $s_icon = match($b['status']) { 
                                    'Pending'=>'fa-hourglass-half',
                                    'Approved'=>'fa-thumbs-up',
                                    'Completed'=>'fa-circle-check',
                                    'Cancelled'=>'fa-ban',
                                    'Declined'=>'fa-times-circle',
                                    default=>'fa-circle'
                                };
                            ?>
                            <tr id="booking-row-<?= (int)$b['id'] ?>">
                                <td>
                                    <span style="font-weight:700;font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--primary)">#<?= $b['id'] ?></span>
                                </td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px">
                                        <div class="customer-avatar"><?= strtoupper(substr($b['customer_name'],0,2)) ?></div>
                                        <div style="min-width:0">
                                            <div class="cust-name"><?= htmlspecialchars($b['customer_name']) ?></div>
                                            <div class="cust-phone"><?= htmlspecialchars($b['phone']) ?></div>
                                            <?php if($b['created_by_admin']): ?>
                                            <div style="font-size:10px;color:var(--primary);display:flex;align-items:center;gap:3px;margin-top:2px">
                                                <i class="fas fa-shield-halved" style="font-size:9px"></i> Admin Created
                                            </div>
                                            <?php endif; ?>
                                            <?php if($b['status'] == 'Declined' && $b['rejection_reason']): ?>
                                            <div style="font-size:10px;color:var(--declined);margin-top:2px" title="<?= htmlspecialchars($b['rejection_reason']) ?>">
                                                <i class="fas fa-info-circle"></i> <?= substr(htmlspecialchars($b['rejection_reason']),0,24) ?>...
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight:600"><?= date('M d, Y', strtotime($b['appointment_date'])) ?></div>
                                </td>
                                <td>
                                    <span style="font-weight:600;font-size:12px;color:var(--text-primary)"><?= htmlspecialchars($b['appointment_time'] ?: '—') ?></span>
                                </td>
                                <td>
                                    <div style="font-weight:600"><?= htmlspecialchars($b['vehicle_model']) ?></div>
                                    <div style="font-size:12px;color:var(--primary)"><?= htmlspecialchars($b['type_name']) ?></div>
                                    <?php if(!empty($b['vehicle_year'])): ?>
                                    <div style="font-size:11px;color:var(--text-muted)">
                                        <i class="far fa-calendar" style="margin-right:2px"></i><?= $b['vehicle_year'] ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight:600;font-size:12px;margin-bottom:5px"><?= $package_name ?></div>
                                    <div style="display:flex;gap:4px;flex-wrap:wrap">
                                        <?php if(!empty($b['tint_type'])): ?>
                                        <span class="badge-pill tint-<?= $b['tint_type'] ?>" style="font-size:10px;padding:3px 8px"><?= ucfirst($b['tint_type']) ?></span>
                                        <?php endif; ?>
                                        <?php if(!empty($b['shade_percentage'])): ?>
                                        <span class="badge-pill badge-muted" style="font-size:10px;padding:3px 8px"><?= $b['shade_percentage'] ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge-pill <?= $pm_cls ?>" style="font-size:11px">
                                            <i class="fas <?= $pm_icon ?>"></i>
                                            <?= htmlspecialchars($payment_method) ?>
                                        </span>
                                    </div>
                                    <div style="margin-top:4px">
                                        <span class="badge-pill <?= ($b['payment_type'] ?? 'full') === 'downpayment' ? 'badge-warning' : 'badge-info' ?>" style="font-size:10px">
                                            <?= ($b['payment_type'] ?? 'full') === 'downpayment' ? 'Down Payment' : 'Full Payment' ?>
                                        </span>
                                    </div>
                                    <div style="margin-top:4px">
                                        <span class="badge-pill <?= $ps_cls ?>" style="font-size:10px">
                                            <?= htmlspecialchars($payment_status) ?>
                                        </span>
                                    </div>
                                    <?php if(!empty($b['payment_reference'])): ?>
                                    <div style="margin-top:4px">
                                        <span class="ref-code">
                                            <i class="fas fa-hashtag" style="font-size:9px"></i>
                                            <?= substr(htmlspecialchars($b['payment_reference']),0,10) ?>...
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-weight:700;color:var(--success);font-size:14px">
                                        ₱<?= number_format($b['total_price'],2) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-pill <?= $s_cls ?>">
                                        <i class="fas <?= $s_icon ?>"></i>
                                        <?= htmlspecialchars($b['status']) ?>
                                    </span>
                                    <?php if($b['status'] == 'Declined' && $b['rejected_by_name']): ?>
                                    <div style="font-size:10px;color:var(--text-muted);margin-top:2px">
                                        by <?= htmlspecialchars($b['rejected_by_name']) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <?php if(strtolower($b['status']) === 'completed'): ?>
                                    <a href="../download_warranty.php?id=<?= $b['id'] ?>" target="_blank" class="btn-xs btn-xs-success" title="Download Warranty Certificate" style="margin-right:3px;display:inline-flex;align-items:center;background:#10b981;border-color:#10b981;color:#fff;padding:4px 7px;border-radius:6px;text-decoration:none;">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    <?php endif; ?>
                                    <button class="btn-xs btn-xs-primary view-btn"
                                            data-id="<?= $b['id'] ?>"
                                            data-name="<?= htmlspecialchars($b['customer_name']) ?>"
                                            data-phone="<?= htmlspecialchars($b['phone']) ?>"
                                            data-address="<?= htmlspecialchars($b['address']) ?>"
                                            data-car="<?= htmlspecialchars($b['vehicle_model']) ?>"
                                            data-year="<?= htmlspecialchars($b['vehicle_year'] ?? '') ?>"
                                            data-color="<?= htmlspecialchars($b['vehicle_color'] ?? '') ?>"
                                            data-type="<?= htmlspecialchars($b['type_name']) ?>"
                                            data-package="<?= $package_name ?>"
                                            data-package-id="<?= $b['package_id'] ?>"
                                            data-price="<?= number_format($b['total_price'],2) ?>"
                                            data-date="<?= date('F j, Y', strtotime($b['appointment_date'])) ?>"
                                            data-time="<?= htmlspecialchars($b['appointment_time'] ?: '—') ?>"
                                            data-status="<?= htmlspecialchars($b['status']) ?>"
                                            data-tint-type="<?= htmlspecialchars($b['tint_type'] ?? 'carbon') ?>"
                                            data-shade="<?= htmlspecialchars($b['shade_percentage'] ?? '35') ?>"
                                            data-warranty="<?= $b['warranty_years'] ?? 3 ?>"
                                            data-installation="<?= htmlspecialchars($b['installation_time'] ?? '2-3 hours') ?>"
                                            data-payment-method="<?= htmlspecialchars($b['payment_method'] ?? 'GCash') ?>"
                                            data-payment-status="<?= htmlspecialchars($b['payment_status'] ?? 'Pending') ?>"
                                            data-payment-type="<?= htmlspecialchars($b['payment_type'] ?? 'full') ?>"
                                            data-payment-reference="<?= htmlspecialchars($b['payment_reference'] ?? '') ?>"
                                            data-rejection-reason="<?= htmlspecialchars($b['rejection_reason'] ?? '') ?>"
                                            data-rejected-by="<?= htmlspecialchars($b['rejected_by_name'] ?? '') ?>"
                                            style="padding:4px 8px;font-size:11.5px;">
                                        <i class="fas fa-pen-to-square"></i> Manage
                                    </button>
                                    <form method="POST" class="d-inline" data-rg-confirm="Permanently delete booking #<?= $b['id'] ?> (<?= htmlspecialchars($b['customer_name']) ?>)? This action cannot be undone." data-rg-type="danger" data-rg-title="Delete Booking">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                        <input type="hidden" name="action" value="Delete">
                                        <input type="hidden" name="status" value="Delete">
                                        <button type="submit" class="btn-xs btn-xs-danger" title="Delete Booking #<?= $b['id'] ?>" style="margin-left:3px;display:inline-flex;align-items:center;padding:4px 7px;border-radius:6px;cursor:pointer;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if(empty($bookings)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-calendar-xmark"></i></div>
                        <h5>No bookings found</h5>
                        <p>Try adjusting your filters or clear the search</p>
                        <?php if(!empty($search_term)): ?>
                        <a href="index.php#bookings" class="btn-xs btn-xs-primary" style="margin-top:12px;display:inline-flex">
                            <i class="fas fa-xmark"></i> Clear Search
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
    </div>

    
    
    
    <div id="section-schedule" class="section-card">

        
        <div class="section-card-header" style="flex-wrap:wrap;gap:12px;">
            <div class="sched-fc-nav">
                <button class="sched-nav-btn" id="fcPrev" title="Previous month"><i class="fas fa-chevron-left"></i></button>
                <span class="sched-fc-month-lbl" id="fcMonthLabel"></span>
                <button class="sched-nav-btn" id="fcNext" title="Next month"><i class="fas fa-chevron-right"></i></button>
                <button class="sched-nav-btn sched-nav-today" id="fcToday">Today</button>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <span class="sched-legend-pill" style="background:rgba(245,158,11,.15);color:#b45309;"><span class="sched-legend-dot" style="background:#f59e0b;"></span>Pending</span>
                <span class="sched-legend-pill" style="background:rgba(59,130,246,.12);color:#1d4ed8;"><span class="sched-legend-dot" style="background:#3b82f6;"></span>Approved</span>
                <span class="sched-legend-pill" style="background:rgba(16,185,129,.12);color:#065f46;"><span class="sched-legend-dot" style="background:#10b981;"></span>Completed</span>
                <span class="sched-legend-pill" style="background:rgba(249,115,22,.12);color:#c2410c;"><span class="sched-legend-dot" style="background:#f97316;"></span>On Hold</span>
                <button class="header-btn btn-create" data-bs-toggle="modal" data-bs-target="#createBookingModal" style="padding:7px 14px;margin-left:4px;">
                    <i class="fas fa-plus"></i> New Booking
                </button>
            </div>
        </div>

        
        <div class="sched-fc-dow">
            <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
        </div>

        
        <div class="sched-fc-grid" id="fcGrid"></div>

        
        <div id="fcLoading" style="display:none;text-align:center;padding:60px 20px;color:var(--text-muted);">
            <i class="fas fa-spinner fa-spin" style="font-size:28px;display:block;margin-bottom:10px;"></i>
            Loading schedule…
        </div>

    </div>

            
            
            
            <div id="section-packages" class="section-card">
                <div class="inner-section-header">
                    <div>
                        <h5 style="font-weight:700;margin:0;display:flex;align-items:center;gap:8px">
                            <div style="width:28px;height:28px;background:var(--primary-bg);border-radius:7px;display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:12px"><i class="fas fa-box"></i></div>
                            Tint Packages - Price Management
                        </h5>
                        <p style="font-size:12px;color:var(--text-muted);margin:4px 0 0">Edit package prices that display on the landing page</p>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">

                        <button class="header-btn btn-create" data-bs-toggle="modal" data-bs-target="#addPackageModal" style="padding:8px 14px">
                            <i class="fas fa-plus"></i> Add Package
                        </button>
                    </div>
                </div>

                <div class="table-wrap mb-4">
                    <table class="styled-table">
                        <thead>
                            <tr id="stock-row-<?= (int)$item['id'] ?>">
                                <th><i class="fas fa-hashtag" style="margin-right:4px"></i>ID</th>
                                <th><i class="fas fa-box" style="margin-right:4px"></i>Package / Single</th>
                                <th><i class="fas fa-tags" style="margin-right:4px"></i>Price</th>
                                <th><i class="fas fa-film" style="margin-right:4px"></i>Tint Type</th>
                                <th><i class="fas fa-clock" style="margin-right:4px"></i>Install</th>
                                <th><i class="fas fa-circle-dot" style="margin-right:4px"></i>Active</th>
                                <th><i class="fas fa-gears" style="margin-right:4px"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($packages as $pkg): ?>
                            <tr id="ticket-row-<?= (int)$ticket['id'] ?>">
                                <td><span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--primary);font-weight:700">#<?= $pkg['id'] ?></span></td>
                                <td>
                                    <div style="font-weight:600"><?= htmlspecialchars($pkg['name']) ?></div>
                                    <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars(substr($pkg['description'] ?? '', 0, 50)) ?>...</div>
                                </td>
                                <td><?php
                                    $pm = $pkg_model_prices_all[$pkg['id']] ?? [];
                                    $ws = $pkg_model_ws_all[$pkg['id']] ?? [];
                                    if (!empty($pm)) {
                                        $all_prices = [];
                                        foreach ($pm as $combo_prices) {
                                            if (is_array($combo_prices)) {
                                                foreach ($combo_prices as $v) {
                                                    if (is_numeric($v)) $all_prices[] = (float)$v;
                                                }
                                            }
                                        }
                                        if (!empty($all_prices)) {
                                            $min_p = min($all_prices); $max_p = max($all_prices);
                                            echo '<div style="font-size:10px;color:rgba(255,255,255,0.4);margin-bottom:2px;">Single</div>';
                                            if ($min_p == $max_p) {
                                                echo '<span style="font-weight:700;color:var(--success)">&#8369;' . number_format($min_p,0) . '</span>';
                                            } else {
                                                echo '<span style="font-weight:700;color:var(--success)">&#8369;' . number_format($min_p,0) . '</span><span style="color:rgba(255,255,255,0.3);margin:0 4px">&ndash;</span><span style="font-weight:700;color:var(--success)">&#8369;' . number_format($max_p,0) . '</span>';
                                            }
                                            
                                            $ws_prices = [];
                                            $ws_min = 3;
                                            foreach ($ws as $combo_ws) {
                                                if (is_array($combo_ws)) {
                                                    foreach ($combo_ws as $wsv) {
                                                        if (is_array($wsv) && isset($wsv['price'])) {
                                                            $ws_prices[] = (float)$wsv['price'];
                                                            $ws_min = (int)($wsv['min'] ?? 3);
                                                        }
                                                    }
                                                }
                                            }
                                            if (!empty($ws_prices)) {
                                                $ws_min_p = min($ws_prices); $ws_max_p = max($ws_prices);
                                                echo '<div style="margin-top:4px;font-size:10px;color:rgba(255,255,255,0.4);">Wholesale (' . $ws_min . '+ cars)</div>';
                                                if ($ws_min_p == $ws_max_p) {
                                                    echo '<span style="font-weight:700;color:#63CAB7;">&#8369;' . number_format($ws_min_p,0) . '</span>';
                                                } else {
                                                    echo '<span style="font-weight:700;color:#63CAB7;">&#8369;' . number_format($ws_min_p,0) . '</span><span style="color:rgba(255,255,255,0.3);margin:0 4px">&ndash;</span><span style="font-weight:700;color:#63CAB7;">&#8369;' . number_format($ws_max_p,0) . '</span>';
                                                }
                                            }
                                        } else {
                                            echo '<span style="color:rgba(255,255,255,0.3);font-size:11px">No prices set</span>';
                                        }
                                    } else {
                                        echo '<span style="color:rgba(255,255,255,0.3);font-size:11px">No prices set</span>';
                                    }
                                ?></td>
                                <td><?php
                                    $pkg_types = array_filter(array_map('trim', explode(',', $pkg['tint_type'])));
                                    foreach ($pkg_types as $pt): ?><span class="badge-pill tint-<?= strtolower(str_replace(' ','-',$pt)) ?>" style="margin:1px;"><?= htmlspecialchars($pt) ?></span><?php endforeach; ?></td>
                                <td><span class="badge-pill badge-info"><?= htmlspecialchars($pkg['installation_time'] ?: $pkg['duration'] ?? '') ?></span></td>
                                <td>
                                    <?php if($pkg['is_active']): ?>
                                    <span class="badge-pill badge-success"><i class="fas fa-check-circle"></i> Active</span>
                                    <?php else: ?>
                                    <span class="badge-pill badge-muted"><i class="fas fa-ban"></i> Inactive</span>
                                    <?php endif; ?>
                                    <?php if($pkg['is_featured']): ?>
                                    <span class="badge-pill badge-warning mt-1"><i class="fas fa-star"></i> Featured</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-action-group">
                                        <button type="button" class="btn-xs btn-xs-primary edit-package-btn" onclick="editPackageModalHandler(this)"
                                                data-id="<?= $pkg['id'] ?>"
                                                data-name="<?= htmlspecialchars($pkg['name']) ?>"
                                                data-description="<?= htmlspecialchars($pkg['description'] ?? '') ?>"require
                                                data-combo-prices="<?= htmlspecialchars(json_encode($pkg_model_prices_all[$pkg['id']] ?? (object)[])) ?>"
                                                data-features="<?= htmlspecialchars(is_string($pkg['features']) ? $pkg['features'] : json_encode($pkg['features'])) ?>"
                                                data-tint-type="<?= htmlspecialchars($pkg['tint_type']) ?>"
                                                data-shade="<?= htmlspecialchars($pkg['shade_percentage']) ?>"
                                                data-installation="<?= htmlspecialchars($pkg['installation_time'] ?: $pkg['duration'] ?? '') ?>"
                                                data-warranty="<?= $pkg['warranty_years'] ?>"
                                                data-active="<?= $pkg['is_active'] ?>"
                                                data-featured="<?= $pkg['is_featured'] ?>"
                                                data-order="<?= $pkg['display_order'] ?? 0 ?>"
                                                data-windows="<?= htmlspecialchars($pkg['windows_included'] ?? '[]') ?>"
                                                data-image-url="<?= htmlspecialchars($pkg['image_url'] ?? '') ?>"
                                                title="Edit Package">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <?php if($pkg['id'] > 6): ?>
                                        <form method="POST" class="d-inline" data-rg-confirm="Delete package <?= htmlspecialchars($pkg['name']) ?>?" data-rg-type="danger" data-rg-title="Delete Package">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="package_id" value="<?= $pkg['id'] ?>">
                                            <input type="hidden" name="package_action" value="delete">
                                            <button type="submit" class="btn-xs btn-xs-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                
                
                <div class="panel mt-4">
                    <div class="panel-header" style="display:flex;align-items:center;justify-content:space-between;">
                        <div class="panel-title">
                            <div class="panel-title-icon" style="background:var(--primary-bg);color:var(--primary)"><i class="fas fa-car"></i></div>
                            Registered Car Models Database (<?= count($car_models_list) ?>)
                        </div>
                        <button class="header-btn btn-create" data-bs-toggle="modal" data-bs-target="#addCarModelModal" style="padding:6px 14px;font-size:12px;">
                            <i class="fas fa-plus"></i> Add Car Model
                        </button>
                    </div>
                    <div style="padding:16px">
                        <?php if(empty($car_models_list)): ?>
                        <div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px;">
                            <i class="fas fa-car-side" style="font-size:24px;opacity:0.4;display:block;margin-bottom:8px;"></i>
                            No custom car models registered yet. Click <strong>+ Add Car Model</strong> above to register one.
                        </div>
                        <?php else: ?>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px">
                            <?php foreach($car_models_list as $cm): ?>
                            <div style="background:var(--body-bg);border-radius:var(--radius-sm);padding:10px 12px;border:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
                                <div>
                                    <div style="font-weight:700;font-size:13px"><i class="fas fa-car-side me-1" style="color:var(--primary)"></i><?= htmlspecialchars($cm['full_name'] ?: ($cm['brand'].' '.$cm['model'])) ?></div>
                                    <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($cm['type_name'] ?? 'Sedan/SUV') ?></div>
                                </div>
                                <form method="POST" class="d-inline" data-rg-confirm="Delete car model <?= htmlspecialchars($cm['full_name'] ?: $cm['model']) ?>?" data-rg-type="danger" data-rg-title="Delete Car Model">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="car_model_id" value="<?= $cm['id'] ?>">
                                    <input type="hidden" name="car_action" value="delete">
                                    <button type="submit" class="btn-xs btn-xs-danger" title="Delete Car Model"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if(!empty($price_history)): ?>
                <div class="panel mt-4">
                    <div class="panel-header">
                        <div class="panel-title">
                            <div class="panel-title-icon" style="background:var(--info-bg);color:var(--info)"><i class="fas fa-history"></i></div>
                            Price Change History
                        </div>
                    </div>
                    <div style="padding:16px">
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px">
                            <?php foreach($price_history as $ph): 
                                $price_diff = $ph['new_price'] - $ph['old_price'];
                                $diff_class = $price_diff > 0 ? 'price-up' : ($price_diff < 0 ? 'price-down' : '');
                                $diff_icon = $price_diff > 0 ? 'fa-arrow-up' : ($price_diff < 0 ? 'fa-arrow-down' : 'fa-equals');
                            ?>
                            <div style="background:var(--body-bg);border-radius:var(--radius-sm);padding:14px;border:1px solid var(--border)">
                                <div style="font-weight:700;margin-bottom:6px"><?= htmlspecialchars($ph['package_name']) ?></div>
                                <div style="font-size:12px;display:flex;gap:10px;margin-bottom:6px">
                                    <span style="color:var(--text-muted)">₱<?= number_format($ph['old_price'],0) ?></span>
                                    <i class="fas <?= $diff_icon ?> <?= $diff_class ?>"></i>
                                    <span style="font-weight:700;color:var(--success)">₱<?= number_format($ph['new_price'],0) ?></span>
                                </div>
                                <div style="font-size:11px;color:var(--text-muted)">
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($ph['changed_by_name'] ?? 'Admin') ?> · 
                                    <i class="far fa-clock"></i> <?= date('M d, Y', strtotime($ph['changed_at'])) ?>
                                </div>
                                <?php if($ph['reason'] && $ph['reason'] != 'Price update'): ?>
                                <div style="font-size:11px;margin-top:4px;padding:4px 8px;background:var(--card-bg);border-radius:4px">
                                    "<?= htmlspecialchars($ph['reason']) ?>"
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            
            
            
            <div id="section-sales" class="section-card">

                
                <div class="section-card-header" style="flex-wrap:wrap;gap:12px">
                    <div>
                        <h5 style="font-weight:700;margin:0;display:flex;align-items:center;gap:8px">
                            <div style="width:28px;height:28px;background:var(--primary-bg);border-radius:7px;display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:12px">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            Sales Records
                        </h5>
                        <p style="color:var(--text-muted);font-size:12px;margin:4px 0 0">Track and manage all completed sales</p>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <button class="sched-nav-btn" id="salesToggleAnalytics" title="Toggle analytics" style="font-size:12px;padding:6px 12px;">
                            <i class="fas fa-chart-pie"></i> <span style="font-size:11px;font-weight:600">Analytics</span>
                        </button>
                        <button class="header-btn btn-create" data-bs-toggle="modal" data-bs-target="#addSaleModal" style="padding:8px 14px">
                            <i class="fas fa-plus"></i> Record Sale
                        </button>
                    </div>
                </div>

                
                <div style="padding:12px 24px;background:var(--surface-alt,#f8f9fa);border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                    <span style="font-size:12px;font-weight:600;color:var(--text-muted);margin-right:4px"><i class="fas fa-filter" style="margin-right:4px"></i>Generate By:</span>
                    
                    
                    <form method="GET" style="display:flex;align-items:center;gap:4px" id="salesFilterDateForm">
                        <input type="hidden" name="section" value="sales">
                        <input type="hidden" name="sales_filter" value="date">
                        <input type="date" name="sales_date" value="<?= htmlspecialchars($sales_filter_date) ?>" 
                               style="font-size:12px;padding:5px 10px;border:1px solid var(--border);border-radius:6px;background:var(--surface);color:var(--text-primary)" 
                               onchange="this.form.submit()">
                    </form>

                    <span style="color:var(--border);font-size:14px">|</span>

                    
                    <form method="GET" style="display:flex;align-items:center;gap:4px" id="salesFilterMonthForm">
                        <input type="hidden" name="section" value="sales">
                        <input type="hidden" name="sales_filter" value="month">
                        <input type="month" name="sales_month" value="<?= htmlspecialchars($sales_filter_month) ?>" 
                               style="font-size:12px;padding:5px 10px;border:1px solid var(--border);border-radius:6px;background:var(--surface);color:var(--text-primary)" 
                               onchange="this.form.submit()">
                    </form>

                    <span style="color:var(--border);font-size:14px">|</span>

                    
                    <form method="GET" style="display:flex;align-items:center;gap:4px" id="salesFilterYearForm">
                        <input type="hidden" name="section" value="sales">
                        <input type="hidden" name="sales_filter" value="year">
                        <select name="sales_year" style="font-size:12px;padding:5px 10px;border:1px solid var(--border);border-radius:6px;background:var(--surface);color:var(--text-primary)" onchange="this.form.submit()">
                            <option value="">-- Year --</option>
                            <?php for($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?= $y ?>" <?= ($sales_filter_year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </form>

                    <?php if (!empty($sales_filter_type)): ?>
                    <a href="?section=sales" style="font-size:11px;padding:5px 10px;background:var(--danger);color:#fff;border-radius:6px;text-decoration:none;font-weight:600">
                        <i class="fas fa-times"></i> Clear
                    </a>
                    <span style="margin-left:auto;font-size:12px;font-weight:600;color:var(--text-primary)">
                        <i class="fas fa-receipt" style="color:var(--primary);margin-right:4px"></i>
                        <?= count($recent_sales) ?> record<?= count($recent_sales) !== 1 ? 's' : '' ?>
                        &bull; Total Paid: <span style="color:var(--success)">₱<?= number_format($sales_filtered_total, 2) ?></span>
                    </span>
                    <?php endif; ?>

                    <div style="margin-left:<?= empty($sales_filter_type) ? 'auto' : '8px' ?>;display:flex;gap:6px">
                        <button type="button" onclick="exportSalesExcel()" style="font-size:11px;padding:6px 12px;background:#217346;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;display:flex;align-items:center;gap:4px">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                        <button type="button" onclick="printSalesReport()" style="font-size:11px;padding:6px 12px;background:var(--primary);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;display:flex;align-items:center;gap:4px">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                    </div>
                </div>

                
                <div class="sales-revenue-strip">
                    <div class="sales-rev-card">
                        <div class="sales-rev-icon" style="background:rgba(212,160,23,.12);color:var(--gold)"><i class="fas fa-sun"></i></div>
                        <div>
                            <div class="sales-rev-label">Today</div>
                            <div class="sales-rev-value" style="color:var(--gold)">₱<?= number_format($sales_analytics['daily'],0) ?></div>
                        </div>
                    </div>
                    <div class="sales-rev-card">
                        <div class="sales-rev-icon" style="background:rgba(8,145,178,.12);color:#0891b2"><i class="fas fa-calendar-week"></i></div>
                        <div>
                            <div class="sales-rev-label">This Week</div>
                            <div class="sales-rev-value" style="color:#0891b2">₱<?= number_format($sales_analytics['weekly'],0) ?></div>
                        </div>
                    </div>
                    <div class="sales-rev-card">
                        <div class="sales-rev-icon" style="background:rgba(79,70,229,.12);color:#4f46e5"><i class="fas fa-calendar"></i></div>
                        <div>
                            <div class="sales-rev-label">This Month</div>
                            <div class="sales-rev-value" style="color:#4f46e5">₱<?= number_format($sales_analytics['monthly'],0) ?></div>
                        </div>
                    </div>
                    <div class="sales-rev-card">
                        <div class="sales-rev-icon" style="background:rgba(124,58,237,.12);color:#7c3aed"><i class="fas fa-calendar-days"></i></div>
                        <div>
                            <div class="sales-rev-label">This Year</div>
                            <div class="sales-rev-value" style="color:#7c3aed">₱<?= number_format($sales_analytics['yearly'],0) ?></div>
                        </div>
                    </div>
                    <div class="sales-rev-divider"></div>
                    <div class="sales-rev-card">
                        <div class="sales-rev-icon" style="background:rgba(5,150,105,.12);color:var(--success)"><i class="fas fa-circle-check"></i></div>
                        <div>
                            <div class="sales-rev-label">Completed</div>
                            <div class="sales-rev-value" style="color:var(--success)"><?= $booking_stats['completed'] ?></div>
                        </div>
                    </div>
                    <div class="sales-rev-card">
                        <div class="sales-rev-icon" style="background:rgba(245,158,11,.12);color:var(--warning)"><i class="fas fa-hourglass-half"></i></div>
                        <div>
                            <div class="sales-rev-label">Pending</div>
                            <div class="sales-rev-value" style="color:var(--warning)"><?= $booking_stats['pending'] ?></div>
                        </div>
                    </div>
                    <div class="sales-rev-card">
                        <div class="sales-rev-icon" style="background:rgba(59,130,246,.12);color:#3b82f6"><i class="fas fa-thumbs-up"></i></div>
                        <div>
                            <div class="sales-rev-label">Approved</div>
                            <div class="sales-rev-value" style="color:#3b82f6"><?= $booking_stats['approved'] ?></div>
                        </div>
                    </div>
                </div>

                
                <div class="sales-analytics-panel" id="salesAnalyticsPanel" style="display:none">
                    <div class="sales-analytics-grid">
                        
                        <div class="sales-stat-box">
                            <div class="sales-stat-hdr"><i class="fas fa-percent" style="color:var(--success)"></i> Completion Rate</div>
                            <div style="font-size:22px;font-weight:800;color:var(--success);margin:6px 0"><?= $booking_stats['conversion'] ?>%</div>
                            <div style="height:5px;background:rgba(0,0,0,.06);border-radius:99px;overflow:hidden">
                                <div style="height:100%;width:<?= $booking_stats['conversion'] ?>%;background:linear-gradient(90deg,#10b981,#34d399);border-radius:99px;transition:width .5s"></div>
                            </div>
                            <div style="font-size:10px;color:var(--text-muted);margin-top:6px"><?= $booking_stats['completed'] ?> / <?= $booking_stats['total'] ?> bookings</div>
                        </div>
                        
                        <div class="sales-stat-box">
                            <div class="sales-stat-hdr"><i class="fas fa-peso-sign" style="color:var(--primary)"></i> Avg Booking Value</div>
                            <div style="font-size:22px;font-weight:800;color:var(--primary);margin:6px 0">₱<?= $booking_stats['avg_value'] ?></div>
                            <div style="font-size:10px;color:var(--text-muted)">per completed job</div>
                            <div style="font-size:10px;color:var(--text-muted);margin-top:2px">Today: <strong style="color:var(--text-primary)"><?= $booking_stats['today'] ?> appts</strong></div>
                        </div>
                        
                        <div class="sales-stat-box" style="border-color:rgba(239,68,68,.15);background:linear-gradient(135deg,rgba(239,68,68,.04),transparent)">
                            <div class="sales-stat-hdr"><i class="fas fa-ban" style="color:var(--danger)"></i> Declined Bookings</div>
                            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-top:8px">
                                <div><div style="font-size:10px;color:var(--text-muted)">Today</div><div style="font-weight:700"><?= $sales_analytics['declined_today'] ?></div></div>
                                <div><div style="font-size:10px;color:var(--text-muted)">Week</div><div style="font-weight:700"><?= $sales_analytics['declined_week'] ?></div></div>
                                <div><div style="font-size:10px;color:var(--text-muted)">Month</div><div style="font-weight:700"><?= $sales_analytics['declined_month'] ?></div></div>
                                <div><div style="font-size:10px;color:var(--text-muted)">Revenue Loss</div><div style="font-weight:700;color:var(--danger)">₱<?= number_format($sales_analytics['revenue_loss'],0) ?></div></div>
                            </div>
                        </div>
                        
                        <?php if(!empty($top_packages)): ?>
                        <div class="sales-stat-box">
                            <div class="sales-stat-hdr"><i class="fas fa-trophy" style="color:#f59e0b"></i> Top Packages</div>
                            <div style="margin-top:8px;display:flex;flex-direction:column;gap:6px">
                                <?php foreach($top_packages as $i => $pkg):
                                    $max_rev = $top_packages[0]['revenue'] > 0 ? $top_packages[0]['revenue'] : 1;
                                    $pct = round(($pkg['revenue'] / $max_rev) * 100);
                                ?>
                                <div style="display:flex;justify-content:space-between;align-items:center">
                                    <div style="flex:1;min-width:0;margin-right:10px">
                                        <div style="font-size:12px;font-weight:600;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($pkg['name']) ?></div>
                                        <div style="height:3px;background:rgba(0,0,0,.06);border-radius:99px;margin-top:4px;overflow:hidden">
                                            <div style="height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,var(--gold),var(--gold-light));border-radius:99px"></div>
                                        </div>
                                    </div>
                                    <div style="text-align:right;flex-shrink:0">
                                        <span style="font-size:12px;font-weight:700;color:var(--success)">₱<?= number_format($pkg['revenue'],0) ?></span>
                                        <span style="font-size:10px;color:var(--text-muted);margin-left:4px">(<?= $pkg['sales'] ?>)</span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div style="padding:8px 20px;font-size:11px;color:var(--text-muted);display:flex;align-items:center;gap:6px;border-top:1px solid var(--border)">
                        <i class="fas fa-rotate" style="color:var(--primary)"></i>
                        Last updated: <?= date('M d, Y h:i A') ?>
                    </div>
                </div>

                
                <div style="padding:0 24px 24px">
                    <div class="table-wrap">
                        <table class="styled-table" id="salesTable">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag" style="margin-right:4px"></i>ID</th>
                                    <th><i class="far fa-calendar" style="margin-right:4px"></i>Date</th>
                                    <th><i class="fas fa-user" style="margin-right:4px"></i>Customer</th>
                                    <th><i class="fas fa-box" style="margin-right:4px"></i>Product</th>
                                    <th><i class="fas fa-hashtag" style="margin-right:4px"></i>Qty</th>
                                    <th><i class="fas fa-peso-sign" style="margin-right:4px"></i>Amount</th>
                                    <th><i class="fas fa-credit-card" style="margin-right:4px"></i>Payment</th>
                                    <th><i class="fas fa-circle-dot" style="margin-right:4px"></i>Status</th>
                                    <th><i class="fas fa-sliders" style="margin-right:4px"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_sales as $sale):
                                    $pm_cls = match($sale['payment_method']) { 'cash'=>'pay-cash','gcash'=>'pay-gcash','card'=>'pay-card','bank_transfer'=>'pay-bank',default=>'badge-muted'};
                                    $ps_cls = match($sale['payment_status']) { 'paid'=>'badge-success','pending'=>'badge-warning','partial'=>'badge-info','refunded'=>'badge-danger',default=>'badge-muted'};
                                ?>
                                <tr>
                                    <td><span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--primary);font-weight:700">#<?= $sale['id'] ?></span></td>
                                    <td><div style="font-weight:600"><?= date('M d, Y', strtotime($sale['sale_date'])) ?></div></td>
                                    <td><?= htmlspecialchars($sale['customer_name']) ?></td>
                                    <td style="max-width:200px">
                                        <div style="font-size:12px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($sale['product_name']) ?></div>
                                    </td>
                                    <td><span class="badge-pill badge-muted"><?= $sale['quantity'] ?>x</span></td>
                                    <td>
                                        <div style="font-weight:700;color:var(--success)">₱<?= number_format($sale['total_price'],2) ?></div>
                                        <div style="font-size:11px;color:var(--text-muted)">₱<?= number_format($sale['unit_price'],2) ?>/ea</div>
                                    </td>
                                    <td><span class="badge-pill <?= $pm_cls ?>" style="font-size:11px"><?= ucfirst(str_replace('_',' ',$sale['payment_method'])) ?></span></td>
                                    <td><span class="badge-pill <?= $ps_cls ?>"><?= ucfirst($sale['payment_status']) ?></span></td>
                                    <td>
                                        <div class="btn-action-group">
                                            <button class="btn-xs btn-xs-primary edit-sale-btn"
                                                    data-id="<?= $sale['id'] ?>"
                                                    data-customer="<?= htmlspecialchars($sale['customer_name']) ?>"
                                                    data-product="<?= htmlspecialchars($sale['product_name']) ?>"
                                                    data-quantity="<?= $sale['quantity'] ?>"
                                                    data-unitprice="<?= $sale['unit_price'] ?>"
                                                    data-paymentmethod="<?= $sale['payment_method'] ?>"
                                                    data-paymentstatus="<?= $sale['payment_status'] ?>"
                                                    data-saledate="<?= $sale['sale_date'] ?>">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <?php if($sale['payment_status'] != 'refunded'): ?>
                                            <form method="POST" class="d-inline" data-rg-confirm="Process refund for sale #<?= $sale['id'] ?>?" data-rg-type="warning" data-rg-title="Process Refund">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="sale_id" value="<?= $sale['id'] ?>">
                                                <input type="hidden" name="sales_action" value="refund">
                                                <button type="submit" class="btn-xs btn-xs-warning"><i class="fas fa-rotate-left"></i></button>
                                            </form>
                                            <?php endif; ?>
                                            <form method="POST" class="d-inline" data-rg-confirm="Delete sale #<?= $sale['id'] ?>?" data-rg-type="danger" data-rg-title="Delete Sale">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="sale_id" value="<?= $sale['id'] ?>">
                                                <input type="hidden" name="sales_action" value="delete">
                                                <button type="submit" class="btn-xs btn-xs-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if(empty($recent_sales)): ?>
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-chart-line"></i></div>
                            <h5>No sales recorded</h5>
                            <p>Click "Record Sale" to add your first sale</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            
            <div id="section-tint-stock" class="section-card">
                
                <div class="inv-summary">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <h5 style="font-weight:800;margin:0;font-size:16px;display:flex;align-items:center;gap:10px">
                                <i class="fas fa-chart-pie" style="opacity:0.7"></i> Inventory Summary
                            </h5>
                            <p style="margin:4px 0 0;opacity:0.6;font-size:12px">
                                <i class="fas fa-clock" style="margin-right:4px"></i>Updated: <?= date('M d, Y h:i A') ?>
                            </p>
                        </div>
                        <?php if(count($low_stock_items) > 0): ?>
                        <div style="background:rgba(239,68,68,0.2);border:1px solid rgba(239,68,68,0.3);border-radius:var(--radius-sm);padding:8px 14px;font-size:12px">
                            <i class="fas fa-triangle-exclamation" style="color:#fca5a5;margin-right:6px"></i>
                            <strong><?= count($low_stock_items) ?></strong> film(s) need restocking
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="inv-summary-grid">
                        <div class="inv-summary-item">
                            <div class="lbl"><i class="fas fa-boxes-stacked" style="margin-right:4px"></i>Total Rolls</div>
                            <div class="val"><?= $total_rolls ?></div>
                        </div>
                        <div class="inv-summary-item">
                            <div class="lbl"><i class="fas fa-peso-sign" style="margin-right:4px"></i>Value</div>
                            <div class="val">₱<?= number_format($inventory_value/1000,1) ?>K</div>
                        </div>
                        <div class="inv-summary-item">
                            <div class="lbl"><i class="fas fa-triangle-exclamation" style="margin-right:4px"></i>Low Stock</div>
                            <div class="val" style="color:#fca5a5"><?= count($low_stock_items) ?></div>
                        </div>
                        <div class="inv-summary-item">
                            <div class="lbl"><i class="fas fa-film" style="margin-right:4px"></i>Film Types</div>
                            <div class="val"><?= $unique_types ?></div>
                        </div>
                    </div>
                </div>

                
                <?php if(!empty($inventory_by_type)): ?>
                <div class="panel mb-4">
                    <div class="panel-header">
                        <div class="panel-title">
                            <div class="panel-title-icon" style="background:#f0fdf4;color:#059669;width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:12px"><i class="fas fa-tags"></i></div>
                            Inventory by Film Type
                        </div>
                    </div>
                    <div style="padding:16px;display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px">
                        <?php foreach($inventory_by_type as $type): ?>
                        <div style="background:var(--body-bg);border-radius:var(--radius-sm);padding:14px;border:1px solid var(--border)">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                                <?php $t_display = htmlspecialchars($type['tint_type'] ?? $type['type_key'] ?? 'general'); ?><span class="badge-pill tint-<?= $t_display ?>" style="font-size:10px"><?= ucfirst($t_display) ?></span>
                                <span style="font-size:11px;color:var(--text-muted)"><?= $type['film_count'] ?> film(s)</span>
                            </div>
                            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
                                <span style="color:var(--text-muted)">Rolls</span>
                                <strong><?= $type['total_rolls'] ?></strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
                                <span style="color:var(--text-muted)">Value</span>
                                <strong>₱<?= number_format($type['total_value'],0) ?></strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;font-size:12px">
                                <span style="color:var(--text-muted)">Low Stock</span>
                                <strong style="color:<?= $type['low_stock_count']>0?'var(--danger)':'var(--success)' ?>"><?= $type['low_stock_count'] ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(count($low_stock_items) > 0): ?>
                <div style="background:var(--warning-bg);border:1px solid #fde68a;border-radius:var(--radius);padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;gap:12px">
                    <div style="width:36px;height:36px;background:#fef3c7;border-radius:9px;display:flex;align-items:center;justify-content:center;color:var(--warning);flex-shrink:0">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:13px;color:#92400e">Low Stock Alert</div>
                        <div style="font-size:12px;color:#b45309"><?= count($low_stock_items) ?> tint film(s) are running low and need restocking</div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="inner-section-header">
                    <div>
                        <h5 style="font-weight:700;margin:0;display:flex;align-items:center;gap:8px">
                            <div style="width:28px;height:28px;background:var(--primary-bg);border-radius:7px;display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:12px"><i class="fas fa-film"></i></div>
                            Tint Film Inventory
                        </h5>
                        <p style="font-size:12px;color:var(--text-muted);margin:4px 0 0">Real-time stock levels and availability</p>
                    </div>
                    <button class="header-btn btn-create" data-bs-toggle="modal" data-bs-target="#addTintStockModal" style="padding:8px 14px">
                        <i class="fas fa-plus"></i> Add Film
                    </button>
                </div>

                <div class="table-wrap">
                    <table class="styled-table" id="tintStockTable">
                        <thead>
                            <tr id="feedback-row-<?= (int)$fb['id'] ?>">
                                <th><i class="fas fa-film" style="margin-right:4px"></i>Film Details</th>
                                <th><i class="fas fa-palette" style="margin-right:4px"></i>Type & Shade</th>
                                <th><i class="fas fa-sliders" style="margin-right:4px"></i>Specs</th>
                                <th><i class="fas fa-boxes-stacked" style="margin-right:4px"></i>Quantity</th>
                                <th><i class="fas fa-circle-dot" style="margin-right:4px"></i>Availability</th>
                                <th><i class="fas fa-peso-sign" style="margin-right:4px"></i>Pricing</th>
                                <th><i class="fas fa-location-dot" style="margin-right:4px"></i>Location</th>
                                <th><i class="fas fa-calendar-check" style="margin-right:4px"></i>Restocked</th>
                                <th><i class="fas fa-gears" style="margin-right:4px"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tint_stock_items as $item):
                                $stock_value = $item['quantity_rolls'] * $item['price_per_roll'];
                                $stock_pct = $item['min_rolls'] > 0 ? ($item['quantity_rolls'] / ($item['min_rolls']*2)) * 100 : 100;
                                if($item['quantity_rolls'] == 0) { $s_lbl='Out of Stock';$s_cls='badge-danger';$bar='fill-bad';$dot='dot-bad'; }
                                elseif($item['quantity_rolls'] <= $item['min_rolls']) { $s_lbl='Low Stock';$s_cls='badge-warning';$bar='fill-warn';$dot='dot-warn'; }
                                else { $s_lbl='In Stock';$s_cls='badge-success';$bar='fill-good';$dot='dot-good'; }
                                $width_ft = ($item['width_cm']/2.54)/12;
                                $roll_sqft = $width_ft * $item['length_ft'];
                                $cars_per_roll = max(1,floor($roll_sqft/50));
                            ?>
                            <tr id="customer-row-<?= (int)$cust['id'] ?>">
                                <td>
                                    <div style="font-weight:700;color:var(--text-primary)"><?= htmlspecialchars($item['film_name']) ?></div>
                                    <?php if(!empty($item['brand'])): ?>
                                    <div style="font-size:12px;color:var(--text-muted)">
                                        <i class="fas fa-tag" style="font-size:10px;margin-right:3px"></i><?= htmlspecialchars($item['brand']) ?>
                                    </div>
                                    <?php endif; ?>
                                    <div style="font-size:11px;color:var(--text-muted);font-family:'JetBrains Mono',monospace">ID #<?= $item['id'] ?></div>
                                </td>
                                <td>
                                    <span class="badge-pill tint-<?= $item['tint_type'] ?>"><?= ucfirst($item['tint_type']) ?></span>
                                    <div style="display:flex;flex-direction:column;gap:3px;margin-top:5px;">
                                        <?php
                                        $shades_arr = explode(',', $item['shade_percentage']);
                                        $sp_json = json_decode($item['shade_prices'] ?? '{}', true) ?: [];
                                        foreach ($shades_arr as $sh):
                                            $sh = trim($sh);
                                            if (!$sh) continue;
                                            $sh_data = $sp_json[$sh] ?? null;
                                            $sh_stock = is_array($sh_data) ? ($sh_data['stock'] ?? 0) : 0;
                                            $sh_price = is_array($sh_data) ? ($sh_data['price'] ?? 0) : (is_numeric($sh_data) ? $sh_data : 0);
                                        ?>
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <span style="display:inline-block;padding:2px 7px;border-radius:10px;font-size:11px;background:rgba(212,160,23,0.12);color:#D4A017;border:1px solid rgba(212,160,23,0.25);min-width:42px;text-align:center;">
                                                <i class="fas fa-droplet" style="font-size:9px;margin-right:2px"></i><?= $sh ?>%
                                            </span>
                                            <span style="font-size:10px;color:var(--text-muted);background:rgba(255,255,255,0.06);padding:1px 5px;border-radius:8px;"><?= $sh_stock ?> rolls</span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:12px;color:var(--text-secondary)">
                                        <div><i class="fas fa-ruler-combined" style="width:14px;color:var(--text-muted);margin-right:4px"></i><?= number_format($width_ft,1) ?>ft × <?= number_format($item['length_ft'],0) ?>ft</div>
                                        <div style="margin-top:3px"><i class="fas fa-fire" style="width:14px;color:#ef4444;margin-right:4px"></i><?= $item['heat_rejection'] ?>% Heat</div>
                                        <div style="margin-top:3px"><i class="fas fa-sun" style="width:14px;color:#f59e0b;margin-right:4px"></i><?= $item['uv_protection'] ?>% UV</div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight:700;font-size:16px;color:var(--text-primary)"><?= $item['quantity_rolls'] ?></div>
                                    <div style="font-size:11px;color:var(--text-muted)">rolls (min: <?= $item['min_rolls'] ?>)</div>
                                    <div class="stock-bar-wrap">
                                        <div class="stock-bar-bg">
                                            <div class="stock-bar-fill <?= $bar ?>" style="width:<?= min(100,$stock_pct) ?>%"></div>
                                        </div>
                                    </div>
                                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px">
                                        <i class="fas fa-car" style="margin-right:3px"></i>~<?= $cars_per_roll * $item['quantity_rolls'] ?> cars
                                    </div>
                                    
                                    <form method="POST" class="quick-update-form" style="display:none" id="quickUpdateForm_<?= $item['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="stock_id" value="<?= $item['id'] ?>">
                                        <input type="hidden" name="stock_action" value="manual_update">
                                        <input type="hidden" name="update_reason" value="Quick update">
                                        <input type="number" name="new_quantity" class="quick-update-input" min="0" value="<?= $item['quantity_rolls'] ?>" required>
                                        <button type="submit" class="btn-xs btn-xs-success" style="padding:4px 8px"><i class="fas fa-check"></i></button>
                                        <button type="button" class="btn-xs btn-xs-muted" onclick="cancelQuickUpdate(<?= $item['id'] ?>)" style="padding:4px 8px"><i class="fas fa-xmark"></i></button>
                                    </form>
                                </td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:7px">
                                        <span class="avail-dot <?= $dot ?>"></span>
                                        <span class="badge-pill <?= $s_cls ?>" style="font-size:11px"><?= $s_lbl ?></span>
                                    </div>
                                    <?php if($item['quantity_rolls'] > 0 && $item['quantity_rolls'] <= $item['min_rolls']): ?>
                                    <div style="font-size:11px;color:var(--warning);margin-top:4px;display:flex;align-items:center;gap:3px">
                                        <i class="fas fa-cart-shopping"></i> Order soon
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight:700;font-size:15px;color:var(--text-primary)">₱<?= number_format($item['price_per_roll'],0) ?></div>
                                    <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">per roll</div>
                                    <?php if($item['price_per_sqft'] > 0): ?>
                                    <div style="font-size:11px;color:var(--text-secondary);margin-top:4px;">₱<?= number_format($item['price_per_sqft'],2) ?>/sqft</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-pill badge-muted" style="font-size:11px">
                                        <i class="fas fa-location-dot" style="font-size:9px"></i>
                                        <?= htmlspecialchars($item['location']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($item['last_restocked']): ?>
                                    <div style="font-weight:600;font-size:12px"><?= date('M d', strtotime($item['last_restocked'])) ?></div>
                                    <div style="font-size:11px;color:var(--text-muted)"><?= date('Y', strtotime($item['last_restocked'])) ?></div>
                                    <?php else: ?>
                                    <span style="font-size:12px;color:var(--text-muted)">
                                        <i class="fas fa-clock" style="margin-right:3px"></i>Never
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-action-group">
                                        <button class="btn-xs btn-xs-primary edit-tint-btn"
                                                data-id="<?= $item['id'] ?>"
                                                data-name="<?= htmlspecialchars($item['film_name']) ?>"
                                                data-type="<?= $item['tint_type'] ?>"
                                                data-brand="<?= htmlspecialchars($item['brand']) ?>"
                                                data-shade="<?= $item['shade_percentage'] ?>"
                                                data-shade-prices="<?= htmlspecialchars($item['shade_prices'] ?? '{}') ?>"
                                                data-width="<?= $item['width_cm'] ?>"
                                                data-length="<?= $item['length_ft'] ?>"
                                                data-quantity="<?= $item['quantity_rolls'] ?>"
                                                data-min="<?= $item['min_rolls'] ?>"
                                                data-price="<?= $item['price_per_roll'] ?>"
                                                data-heat="<?= $item['heat_rejection'] ?>"
                                                data-uv="<?= $item['uv_protection'] ?>"
                                                data-warranty="<?= $item['warranty_years'] ?>"
                                                data-location="<?= htmlspecialchars($item['location']) ?>"
                                                title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="btn-xs btn-xs-success restock-tint-btn"
                                                data-id="<?= $item['id'] ?>"
                                                data-name="<?= htmlspecialchars($item['film_name']) ?>"
                                                title="Restock">
                                            <i class="fas fa-boxes-stacked"></i>
                                        </button>
                                        <button class="btn-xs btn-xs-info quick-update-toggle"
                                                data-id="<?= $item['id'] ?>"
                                                title="Quick Update Qty">
                                            <i class="fas fa-pencil"></i>
                                        </button>
                                        <form method="POST" class="d-inline tint-delete-form">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="stock_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="stock_action" value="delete">
                                            <button type="submit" class="btn-xs btn-xs-danger delete-tint-btn"
                                                    data-id="<?= $item['id'] ?>"
                                                    data-name="<?= htmlspecialchars($item['film_name']) ?>"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div id="section-service" class="section-card">
                <div class="inner-section-header">
                    <div>
                        <h5 style="font-weight:700;margin:0;display:flex;align-items:center;gap:8px">
                            <div style="width:28px;height:28px;background:var(--info-bg);border-radius:7px;display:flex;align-items:center;justify-content:center;color:var(--info);font-size:12px"><i class="fas fa-headset"></i></div>
                            Customer Support Tickets
                        </h5>
                        <p style="font-size:12px;color:var(--text-muted);margin:4px 0 0">View and manage customer support requests</p>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="styled-table" id="serviceTicketsTable">
                        <thead>
                            <tr id="admin-row-<?= (int)$user['id'] ?>">
                                <th><i class="fas fa-hashtag" style="margin-right:4px"></i>ID</th>
                                <th><i class="fas fa-user" style="margin-right:4px"></i>Customer</th>
                                <th><i class="fas fa-envelope" style="margin-right:4px"></i>Subject</th>
                                <th><i class="fas fa-flag" style="margin-right:4px"></i>Priority</th>
                                <th><i class="fas fa-circle-dot" style="margin-right:4px"></i>Status</th>
                                <th><i class="far fa-calendar" style="margin-right:4px"></i>Created</th>
                                <th><i class="fas fa-gears" style="margin-right:4px"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($service_tickets as $ticket): ?>
                            <tr>
                                <td><span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--primary);font-weight:700">#<?= $ticket['id'] ?></span></td>
                                <td>
                                    <div style="font-weight:600"><?= htmlspecialchars($ticket['customer_name']) ?></div>
                                    <div style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($ticket['customer_email']) ?></div>
                                </td>
                                <td style="max-width:200px">
                                    <div style="font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($ticket['subject']) ?></div>
                                </td>
                                <td>
                                    <span class="badge-pill priority-<?= $ticket['priority'] ?>">
                                        <i class="fas fa-<?= $ticket['priority']=='urgent'?'bolt':($ticket['priority']=='high'?'arrow-up':($ticket['priority']=='medium'?'minus':'arrow-down')) ?>"></i>
                                        <?= ucfirst($ticket['priority']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-pill badge-<?= $ticket['status'] ?>">
                                        <?= ucfirst(str_replace('_',' ',$ticket['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size:13px"><?= date('M d, Y', strtotime($ticket['created_at'])) ?></div>
                                </td>
                                <td>
                                    <button class="btn-xs btn-xs-primary view-ticket-btn"
                                            data-id="<?= $ticket['id'] ?>"
                                            data-customer="<?= htmlspecialchars($ticket['customer_name']) ?>"
                                            data-email="<?= htmlspecialchars($ticket['customer_email']) ?>"
                                            data-phone="<?= htmlspecialchars($ticket['customer_phone']) ?>"
                                            data-subject="<?= htmlspecialchars($ticket['subject']) ?>"
                                            data-message="<?= htmlspecialchars($ticket['message']) ?>"
                                            data-priority="<?= $ticket['priority'] ?>"
                                            data-status="<?= $ticket['status'] ?>"
                                            data-response="<?= htmlspecialchars($ticket['response'] ?? '') ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if(empty($service_tickets)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-headset"></i></div>
                        <h5>No support tickets</h5>
                        <p>All customer issues are resolved!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div id="section-feedback" class="section-card">
                <div class="section-card-header">
                    <div class="section-card-title">
                        <div class="title-icon" style="background:var(--gold-pale);color:var(--gold)"><i class="fas fa-star"></i></div>
                        Customer Feedback
                    </div>
                    <div style="font-size:12px;color:var(--text-muted);font-weight:500;">Approve or reject customer reviews and testimonials</div>
                </div>

                <div class="section-card-body">
                    
                    <div class="metrics-grid mb-4">
                        <div class="metric-card accent-gold">
                            <div class="metric-icon icon-gold"><i class="fas fa-comments"></i></div>
                            <div class="metric-value"><?= $total_feedback ?></div>
                            <div class="metric-label">Total Reviews</div>
                        </div>
                        <div class="metric-card accent-amber">
                            <div class="metric-icon icon-amber"><i class="fas fa-hourglass-half"></i></div>
                            <div class="metric-value"><?= $pending_feedback_count ?></div>
                            <div class="metric-label">Pending Approval</div>
                        </div>
                        <div class="metric-card accent-green">
                            <div class="metric-icon icon-green"><i class="fas fa-circle-check"></i></div>
                            <div class="metric-value"><?= $approved_feedback_count ?></div>
                            <div class="metric-label">Approved</div>
                        </div>
                        <div class="metric-card accent-gold">
                            <div class="metric-icon icon-gold"><i class="fas fa-star"></i></div>
                            <div class="metric-value"><?= $avg_rating ?> ★</div>
                            <div class="metric-label">Avg Rating</div>
                        </div>
                    </div>

                    <div class="table-wrap">
                    <table class="styled-table" id="serviceTicketsTable">
                        <thead>
                            <tr>
                                <th><i class="far fa-calendar" style="margin-right:4px"></i>Date</th>
                                <th><i class="fas fa-user" style="margin-right:4px"></i>Customer</th>
                                <th><i class="fas fa-star" style="margin-right:4px"></i>Rating</th>
                                <th><i class="fas fa-comment" style="margin-right:4px"></i>Comment</th>
                                <th><i class="fas fa-car" style="margin-right:4px"></i>Vehicle</th>
                                <th><i class="fas fa-film" style="margin-right:4px"></i>Tint</th>
                                <th><i class="fas fa-circle-dot" style="margin-right:4px"></i>Status</th>
                                <th><i class="fas fa-gears" style="margin-right:4px"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($all_testimonials as $fb): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($fb['created_at'])) ?></td>
                                <td>
                                    <div style="font-weight:600"><?= htmlspecialchars($fb['name']) ?></div>
                                    <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($fb['email'] ?? '') ?></div>
                                </td>
                                <td>
                                    <div style="display:flex;gap:2px;color:#d4a017;">
                                        <?php for($i=1;$i<=5;$i++): ?>
                                        <i class="fas fa-star" style="<?= $i>floatval($fb['rating'])?'opacity:0.2;color:#cbd5e1;':'' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div style="font-size:10px;font-weight:700;color:#d4a017;margin-top:2px;"><?= number_format($fb['rating'], 1) ?> Stars</div>
                                </td>
                                <td style="max-width:240px">
                                    <div style="font-size:12px;color:var(--text-secondary);line-height:1.3;">
                                        "<?= htmlspecialchars($fb['comment']) ?>"
                                    </div>
                                </td>
                                <td style="font-size:12px;font-weight:600;"><?= htmlspecialchars($fb['vehicle'] ?: 'Vehicle') ?></td>
                                <td>
                                    <?php if(!empty($fb['tint_type'])): ?>
                                    <span class="badge-pill tint-<?= strtolower($fb['tint_type']) ?>" style="font-size:10px;padding:3px 8px;"><i class="fas fa-film me-1"></i><?= htmlspecialchars($fb['tint_type']) ?></span>
                                    <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:12px">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if((int)$fb['is_approved'] === 1): ?>
                                    <span class="badge-pill badge-success" style="font-size:10px;"><i class="fas fa-circle-check"></i> Published</span>
                                    <?php else: ?>
                                    <span class="badge-pill badge-warning" style="font-size:10px;"><i class="fas fa-hourglass-half"></i> Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-action-group">
                                        <?php if((int)$fb['is_approved'] === 0): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                                            <input type="hidden" name="feedback_action" value="approve">
                                            <button type="submit" class="btn-xs btn-xs-success" title="Approve Review"><i class="fas fa-check"></i> Approve</button>
                                        </form>
                                        <?php else: ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                                            <input type="hidden" name="feedback_action" value="hide">
                                            <button type="submit" class="btn-xs btn-xs-warning" title="Hide Review"><i class="fas fa-eye-slash"></i> Hide</button>
                                        </form>
                                        <?php endif; ?>
                                        <form method="POST" class="d-inline" data-rg-confirm="Delete this customer review?" data-rg-type="danger" data-rg-title="Delete Review">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                                            <input type="hidden" name="feedback_action" value="delete">
                                            <button type="submit" class="btn-xs btn-xs-danger" title="Delete Review"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($all_testimonials)): ?>
                            <tr><td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fas fa-comments"></i></div>
                                    <h5>No customer reviews yet</h5>
                                    <p>Reviews submitted by customers will appear here.</p>
                                </div>
                            </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div style="padding:16px;border-top:1px solid var(--border)">
                        <a href="feedback.php" class="header-btn btn-create" style="padding:9px 18px;text-decoration:none;display:inline-flex">
                            <i class="fas fa-arrow-right"></i> Manage All Feedback
                        </a>
                    </div>
                </div>
            </div>
            </div>

            
            <div id="section-customers" class="section-card">
                <div class="inner-section-header">
                    <div>
                        <h5 style="font-weight:700;margin:0;display:flex;align-items:center;gap:8px">
                            <div style="width:28px;height:28px;background:var(--primary-bg);border-radius:7px;display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:12px"><i class="fas fa-users"></i></div>
                            Registered Customers
                        </h5>
                        <p style="font-size:12px;color:var(--text-muted);margin:4px 0 0"><?= $total_customers ?> registered customer account<?= $total_customers!=1?'s':'' ?> — bookings &amp; packages linked</p>
                    </div>
                </div>

                <?php if(empty($customers)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-users"></i></div>
                    <h5>No customers yet</h5>
                    <p>Customers will appear here once they register and book.</p>
                </div>
                <?php else: ?>
                
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
                    <div style="background:linear-gradient(135deg,rgba(212,160,23,.1),rgba(212,160,23,.03));border:1px solid rgba(212,160,23,.18);border-radius:12px;padding:16px;text-align:center">
                        <div style="font-size:22px;font-weight:800;color:var(--gold)"><?= $total_customers ?></div>
                        <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:4px">Total Customers</div>
                    </div>
                    <div style="background:linear-gradient(135deg,rgba(5,150,105,.08),rgba(5,150,105,.02));border:1px solid rgba(5,150,105,.15);border-radius:12px;padding:16px;text-align:center">
                        <div style="font-size:22px;font-weight:800;color:var(--success)"><?= array_sum(array_column($customers,'completed_bookings')) ?></div>
                        <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:4px">Completed Jobs</div>
                    </div>
                    <div style="background:linear-gradient(135deg,rgba(245,158,11,.08),rgba(245,158,11,.02));border:1px solid rgba(245,158,11,.15);border-radius:12px;padding:16px;text-align:center">
                        <div style="font-size:22px;font-weight:800;color:var(--warning)"><?= array_sum(array_column($customers,'pending_bookings')) ?></div>
                        <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:4px">Pending Bookings</div>
                    </div>
                    <div style="background:linear-gradient(135deg,rgba(212,160,23,.08),rgba(212,160,23,.02));border:1px solid rgba(212,160,23,.12);border-radius:12px;padding:16px;text-align:center">
                        <div style="font-size:18px;font-weight:800;color:var(--primary)">&#8369;<?= number_format(array_sum(array_column($customers,'total_spent')),0) ?></div>
                        <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:4px">Total Revenue</div>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="styled-table" id="customersTable">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag" style="margin-right:4px"></i>ID</th>
                                <th><i class="fas fa-user" style="margin-right:4px"></i>Customer</th>
                                <th><i class="fas fa-calendar-check" style="margin-right:4px"></i>Bookings</th>
                                <th><i class="fas fa-film" style="margin-right:4px"></i>Packages Used</th>
                                <th><i class="fas fa-peso-sign" style="margin-right:4px"></i>Total Spent</th>
                                <th><i class="fas fa-calendar" style="margin-right:4px"></i>Last Booking</th>
                                <th><i class="fas fa-circle-dot" style="margin-right:4px"></i>Status</th>
                                <th><i class="fas fa-sliders" style="margin-right:4px"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($customers as $cust):
                                $bk_total = (int)$cust['total_bookings'];
                                $bk_done  = (int)$cust['completed_bookings'];
                                $bk_pend  = (int)$cust['pending_bookings'];
                                $bk_decl  = (int)$cust['declined_bookings'];
                                $spent    = (float)$cust['total_spent'];
                                $conv     = $bk_total > 0 ? round($bk_done / $bk_total * 100) : 0;
                                $cust_status = $bk_done > 0 ? 'active' : ($bk_pend > 0 ? 'pending' : ($bk_total > 0 ? 'inactive' : 'new'));
                                $cst_cls_map = ['active'=>'badge-success','pending'=>'badge-warning','inactive'=>'badge-muted','new'=>'badge-info'];
                                $cst_lbl_map = ['active'=>'Active','pending'=>'Booked','inactive'=>'Inactive','new'=>'New'];
                                $cst_cls = $cst_cls_map[$cust_status] ?? 'badge-muted';
                                $cst_lbl = $cst_lbl_map[$cust_status] ?? '—';
                            ?>
                            <tr>
                                <td><span style="font-weight:700;font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--text-muted)">#<?= $cust['id'] ?></span></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px">
                                        <div class="customer-avatar"><?= strtoupper(substr($cust['full_name'],0,2)) ?></div>
                                        <div>
                                            <div style="font-weight:700;color:var(--text-primary)"><?= htmlspecialchars($cust['full_name']) ?></div>
                                            <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($cust['email']) ?></div>
                                            <?php if(!empty($cust['phone'])): ?>
                                            <div style="font-size:11px;color:var(--text-muted)"><i class="fas fa-phone" style="font-size:9px;margin-right:3px"></i><?= htmlspecialchars($cust['phone']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:5px">
                                        <span class="badge-pill badge-muted" style="font-size:10px" title="Total"><?= $bk_total ?> total</span>
                                        <?php if($bk_done):  ?><span class="badge-pill badge-success" style="font-size:10px"><?= $bk_done ?> done</span><?php endif; ?>
                                        <?php if($bk_pend):  ?><span class="badge-pill badge-warning" style="font-size:10px"><?= $bk_pend ?> pending</span><?php endif; ?>
                                        <?php if($bk_decl):  ?><span class="badge-pill badge-declined" style="font-size:10px"><?= $bk_decl ?> declined</span><?php endif; ?>
                                    </div>
                                    <?php if($bk_total > 0): ?>
                                    <div style="height:4px;background:rgba(0,0,0,.07);border-radius:99px;overflow:hidden;width:80px">
                                        <div style="height:100%;width:<?= $conv ?>%;background:linear-gradient(90deg,#10b981,#34d399);border-radius:99px"></div>
                                    </div>
                                    <div style="font-size:9px;color:var(--text-muted);margin-top:2px"><?= $conv ?>% completed</div>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width:160px">
                                    <?php if(!empty($cust['packages_used'])): ?>
                                    <div style="font-size:11px;font-weight:500;color:var(--text-primary);line-height:1.5"><?= htmlspecialchars($cust['packages_used']) ?></div>
                                    <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:12px">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($spent > 0): ?>
                                    <span style="font-weight:700;color:var(--success);font-size:14px">&#8369;<?= number_format($spent,2) ?></span>
                                    <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:12px">&#8369;0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(!empty($cust['last_booking_date'])): ?>
                                    <div style="font-weight:600;font-size:12px"><?= date('M d, Y', strtotime($cust['last_booking_date'])) ?></div>
                                    <div style="font-size:11px;color:var(--text-muted)"><?= date('h:i A', strtotime($cust['last_booking_date'])) ?></div>
                                    <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:12px">No bookings yet</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-pill <?= $cst_cls ?>"><?= $cst_lbl ?></span>
                                    <div style="font-size:10px;color:var(--text-muted);margin-top:2px">
                                        Joined <?= date('M Y', strtotime($cust['created_at'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn-xs btn-xs-primary" title="View bookings"
                                        onclick="showSection('bookings')">
                                        <i class="fas fa-eye"></i> View Bookings
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            
            <div id="section-admin" class="section-card">
                <div class="inner-section-header">
                    <div>
                        <h5 style="font-weight:700;margin:0;display:flex;align-items:center;gap:8px">
                            <div style="width:28px;height:28px;background:var(--gold-pale);border-radius:7px;display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:12px"><i class="fas fa-users-gear"></i></div>
                            Admin Users Management
                        </h5>
                        <p style="font-size:12px;color:var(--text-muted);margin:4px 0 0">Manage system access and user roles</p>
                    </div>
                    <button class="header-btn btn-create" data-bs-toggle="modal" data-bs-target="#addAdminModal" style="padding:8px 14px">
                        <i class="fas fa-user-plus"></i> Add Admin
                    </button>
                </div>

                <div class="table-wrap">
                    <table class="styled-table" id="adminTable">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag" style="margin-right:4px"></i>ID</th>
                                <th><i class="fas fa-at" style="margin-right:4px"></i>Username</th>
                                <th><i class="fas fa-id-card" style="margin-right:4px"></i>Full Name</th>
                                <th><i class="fas fa-envelope" style="margin-right:4px"></i>Email</th>
                                <th><i class="fas fa-shield-halved" style="margin-right:4px"></i>Role</th>
                                <th><i class="fas fa-building" style="margin-right:4px"></i>Department</th>
                                <th><i class="fas fa-circle-dot" style="margin-right:4px"></i>Status</th>
                                <th><i class="fas fa-clock" style="margin-right:4px"></i>Last Login</th>
                                <th><i class="fas fa-gears" style="margin-right:4px"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($admin_users as $user): ?>
                            <tr>
                                <td><span style="font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:700;color:var(--text-muted)"><?= $user['id'] ?></span></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px">
                                        <div style="width:30px;height:30px;border-radius:7px;background:var(--primary-bg);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px">
                                            <?= strtoupper(substr($user['username'],0,1)) ?>
                                        </div>
                                        <span style="font-weight:700"><?= htmlspecialchars($user['username']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($user['full_name']) ?></td>
                                <td style="font-size:12px;color:var(--text-secondary)"><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <?php
                                    $role_cls = match($user['role']) { 'admin'=>'badge-danger','manager'=>'badge-warning','staff'=>'badge-info',default=>'badge-muted'};
                                    $role_icon = match($user['role']) { 'admin'=>'fa-crown','manager'=>'fa-star','staff'=>'fa-user',default=>'fa-circle'};
                                    ?>
                                    <span class="badge-pill <?= $role_cls ?>">
                                        <i class="fas <?= $role_icon ?>"></i>
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td style="font-size:12px"><?= htmlspecialchars($user['department'] ?: '—') ?></td>
                                <td>
                                    <?php if($user['is_active']): ?>
                                    <span class="badge-pill badge-success"><i class="fas fa-circle-check"></i> Active</span>
                                    <?php else: ?>
                                    <span class="badge-pill badge-danger"><i class="fas fa-circle-xmark"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:12px">
                                    <?php if($user['last_login']): ?>
                                    <?= date('M d, Y', strtotime($user['last_login'])) ?>
                                    <?php else: ?>
                                    <span style="color:var(--text-muted)"><i class="fas fa-ban" style="font-size:10px;margin-right:3px"></i>Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-action-group">
                                        <button class="btn-xs btn-xs-primary edit-admin-btn"
                                                data-id="<?= $user['id'] ?>"
                                                data-username="<?= htmlspecialchars($user['username']) ?>"
                                                data-fullname="<?= htmlspecialchars($user['full_name']) ?>"
                                                data-email="<?= htmlspecialchars($user['email']) ?>"
                                                data-role="<?= $user['role'] ?>"
                                                data-department="<?= htmlspecialchars($user['department']) ?>"
                                                data-active="<?= $user['is_active'] ?>"
                                                title="Edit User">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="btn-xs btn-xs-info password-btn"
                                                data-id="<?= $user['id'] ?>"
                                                data-username="<?= htmlspecialchars($user['username']) ?>"
                                                title="Change Password">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <?php if($user['id'] != 1 && $user['id'] != ($_SESSION['admin_id'] ?? 0)): ?>
                                        <form method="POST" class="d-inline" id="delUser<?= $user['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="admin_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="admin_action" value="delete">
                                            <button type="button" class="btn-xs btn-xs-danger" title="Delete" onclick="confirmDeleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>', this.closest('form'))"><i class="fas fa-trash"></i></button>
                                        </form>
                                        <?php else: ?>
                                        <button class="btn-xs btn-xs-muted" disabled title="Protected"><i class="fas fa-lock"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div id="section-sms-gateway" class="section-card">
                <div class="section-hdr">
                    <div>
                        <div class="section-title">SMS Gateway Manager</div>
                        <div class="section-sub">Configure providers (Semaphore, Twilio, SMS Gate) and view logs.</div>
                    </div>
                </div>

                
                <div class="metrics-grid mb-4">
                    <div class="metric-card" style="background:rgba(34,197,94,0.06);border:1px solid rgba(34,197,94,0.12);">
                        <div class="metric-icon"><i class="fas fa-paper-plane" style="color:var(--success)"></i></div>
                        <div class="metric-value"><?= $sms_stats['sent_today'] ?></div>
                        <div class="metric-label">Sent Today</div>
                    </div>
                    <div class="metric-card" style="background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.12);">
                        <div class="metric-icon"><i class="fas fa-triangle-exclamation" style="color:var(--danger)"></i></div>
                        <div class="metric-value"><?= $sms_stats['failed_today'] ?></div>
                        <div class="metric-label">Failed Today</div>
                    </div>
                    <div class="metric-card" style="background:rgba(99,202,183,0.06);border:1px solid rgba(99,202,183,0.12);">
                        <div class="metric-icon"><i class="fas fa-chart-line" style="color:#63CAB7"></i></div>
                        <div class="metric-value"><?= $sms_stats['sent_this_month'] ?></div>
                        <div class="metric-label">Sent This Month</div>
                    </div>
                </div>

                <div class="row g-4">
                    
                    <div class="col-lg-8">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="save_sms_settings">

                            <div class="card mb-4">
                                <div class="card-header" style="background:var(--body-bg);border-bottom:1px solid var(--border);padding:14px 20px;">
                                    <h5 style="margin:0;font-weight:700;font-size:14px;color:var(--text-primary)"><i class="fas fa-cog me-2"></i>Gateway Settings</h5>
                                </div>
                                <div class="card-body" style="padding:20px;">
                                    <div class="mb-4">
                                        <label class="form-label-styled"><i class="fas fa-shield-halved"></i> Active Provider</label>
                                        <select class="form-select-styled" name="sms_gateway_type" id="sms_gateway_type" onchange="toggleSMSProviderFields(this.value)">
                                            <option value="smsgate" <?= $sms_config['gateway_type'] === 'smsgate' ? 'selected' : '' ?>>📱 SMS Gate (Self-Hosted Phone)</option>
                                            <option value="semaphore" <?= $sms_config['gateway_type'] === 'semaphore' ? 'selected' : '' ?>>🇵🇭 Semaphore (Philippines)</option>
                                            <option value="twilio" <?= $sms_config['gateway_type'] === 'twilio' ? 'selected' : '' ?>>🌐 Twilio (Global)</option>
                                        </select>
                                    </div>

                                    
                                    <div id="provider-fields-semaphore" class="provider-fields-group" style="display:none">
                                        <div class="section-divider"><div class="section-title">Semaphore Config</div></div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label-styled">API Key</label>
                                                <input type="password" class="form-control-styled" name="semaphore_api_key" value="<?= htmlspecialchars($sms_config['semaphore_api_key'] ? '********' : '') ?>" placeholder="Enter API Key">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label-styled">Sender Name</label>
                                                <input type="text" class="form-control-styled" name="semaphore_sender_name" value="<?= htmlspecialchars($sms_config['semaphore_sender_name']) ?>" placeholder="e.g. SEMAPHORE">
                                            </div>
                                        </div>
                                    </div>

                                    
                                    <div id="provider-fields-twilio" class="provider-fields-group" style="display:none">
                                        <div class="section-divider"><div class="section-title">Twilio Config</div></div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label-styled">Account SID</label>
                                                <input type="password" class="form-control-styled" name="twilio_account_sid" value="<?= htmlspecialchars($sms_config['twilio_account_sid'] ? '********' : '') ?>" placeholder="ACxxxxxxxxxxxxxx">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label-styled">Auth Token</label>
                                                <input type="password" class="form-control-styled" name="twilio_auth_token" value="<?= htmlspecialchars($sms_config['twilio_auth_token'] ? '********' : '') ?>" placeholder="Enter Twilio Token">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label-styled">Twilio Phone Number</label>
                                                <input type="text" class="form-control-styled" name="twilio_phone_number" value="<?= htmlspecialchars($sms_config['twilio_phone_number']) ?>" placeholder="+1234567890">
                                            </div>
                                        </div>
                                    </div>

                                    
                                    <div id="provider-fields-smsgate" class="provider-fields-group" style="display:none">
                                        <div class="section-divider"><div class="section-title">SMS Gate Config</div></div>
                                        
                                        <div style="background:rgba(212,160,23,0.06);border:1px solid rgba(212,160,23,0.15);border-radius:10px;padding:12px;margin-bottom:16px;font-size:12px;color:#854D0E;">
                                            <i class="fas fa-circle-info"></i> <strong>SMS Gate Fallback Chain:</strong> Local IP &rarr; Public IP &rarr; Cloud Server. It automatically tries the next one if the previous is offline.
                                        </div>

                                        <div class="row g-3">
                                            
                                            <div class="col-md-4">
                                                <label class="form-label-styled">Local URL</label>
                                                <input type="text" class="form-control-styled" name="smsgate_local_url" value="<?= htmlspecialchars($sms_config['smsgate_local_url']) ?>" placeholder="http://192.168.1.50:8080">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label-styled">Local Username</label>
                                                <input type="text" class="form-control-styled" name="smsgate_local_username" value="<?= htmlspecialchars($sms_config['smsgate_local_username']) ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label-styled">Local Password</label>
                                                <input type="password" class="form-control-styled" name="smsgate_local_password" value="<?= htmlspecialchars($sms_config['smsgate_local_password'] ? '********' : '') ?>">
                                            </div>

                                            
                                            <div class="col-md-6">
                                                <label class="form-label-styled">Public URL</label>
                                                <input type="text" class="form-control-styled" name="smsgate_public_url" value="<?= htmlspecialchars($sms_config['smsgate_public_url']) ?>" placeholder="http://public-ip:port">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label-styled">Device ID</label>
                                                <input type="text" class="form-control-styled" name="smsgate_device_id" value="<?= htmlspecialchars($sms_config['smsgate_device_id']) ?>">
                                            </div>

                                            
                                            <div class="col-md-4">
                                                <label class="form-label-styled">Cloud API URL</label>
                                                <input type="text" class="form-control-styled" name="smsgate_cloud_url" value="<?= htmlspecialchars($sms_config['smsgate_cloud_url']) ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label-styled">Cloud Username</label>
                                                <input type="text" class="form-control-styled" name="smsgate_cloud_username" value="<?= htmlspecialchars($sms_config['smsgate_cloud_username']) ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label-styled">Cloud Password</label>
                                                <input type="password" class="form-control-styled" name="smsgate_cloud_password" value="<?= htmlspecialchars($sms_config['smsgate_cloud_password'] ? '********' : '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer" style="background:var(--body-bg);border-top:1px solid var(--border);padding:14px 20px;display:flex;justify-content:flex-end;">
                                    <button type="submit" class="header-btn btn-create" style="padding:9px 24px;">
                                        <i class="fas fa-save me-1"></i> Save Configuration
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header" style="background:var(--body-bg);border-bottom:1px solid var(--border);padding:14px 20px;">
                                <h5 style="margin:0;font-weight:700;font-size:14px;color:var(--text-primary)"><i class="fas fa-paper-plane me-2"></i>Send Test SMS</h5>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="send_test_sms">
                                <div class="card-body" style="padding:20px;">
                                    <div class="mb-3">
                                        <label class="form-label-styled">Phone Number</label>
                                        <input type="text" class="form-control-styled" name="test_phone" placeholder="+639xxxxxxxx" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label-styled">Test Message</label>
                                        <textarea class="form-control-styled" name="test_message" rows="3" required>Test message from RG Cartint Works SMS gateway.</textarea>
                                    </div>
                                </div>
                                <div class="card-footer" style="background:var(--body-bg);border-top:1px solid var(--border);padding:14px 20px;display:flex;justify-content:flex-end;">
                                    <button type="submit" class="header-btn" style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;padding:9px 20px;border:none;">
                                        <i class="fas fa-paper-plane me-1"></i> Send Test
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                
                <div class="card mt-4">
                    <div class="card-header" style="background:var(--body-bg);border-bottom:1px solid var(--border);padding:14px 20px;">
                        <h5 style="margin:0;font-weight:700;font-size:14px;color:var(--text-primary)"><i class="fas fa-file-lines me-2"></i>Recent SMS Logs</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-wrap">
                            <table class="styled-table" id="feedbackTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Provider</th>
                                        <th>Recipient</th>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Sent At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($sms_logs)): ?>
                                        <tr>
                                            <td colspan="6" style="text-align:center;color:var(--text-muted);padding:20px;">No SMS logs found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($sms_logs as $log): ?>
                                        <tr>
                                            <td><span style="font-family:'JetBrains Mono',monospace;font-size:11px;font-weight:700;color:var(--text-muted)"><?= $log['id'] ?></span></td>
                                            <td><?= htmlspecialchars(ucfirst($log['from_number'])) ?></td>
                                            <td><strong style="font-size:12px;color:var(--text-primary)"><?= htmlspecialchars($log['to_number']) ?></strong></td>
                                            <td style="font-size:12px;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($log['message']) ?>"><?= htmlspecialchars($log['message']) ?></td>
                                            <td>
                                                <?php if ($log['status'] === 'sent'): ?>
                                                    <span class="badge-pill badge-success"><i class="fas fa-circle-check"></i> Sent</span>
                                                <?php elseif ($log['status'] === 'failed'): ?>
                                                    <span class="badge-pill badge-danger" title="<?= htmlspecialchars($log['error_message']) ?>"><i class="fas fa-circle-xmark"></i> Failed</span>
                                                <?php else: ?>
                                                    <span class="badge-pill badge-warning"><i class="fas fa-hourglass-half"></i> Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="font-size:12px;color:var(--text-secondary)"><?= date('M d, Y h:i A', strtotime($log['sent_at'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            function toggleSMSProviderFields(val) {
                document.querySelectorAll('.provider-fields-group').forEach(function(g) {
                    g.style.display = 'none';
                });
                var target = document.getElementById('provider-fields-' + val);
                if (target) {
                    target.style.display = 'block';
                }
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                toggleSMSProviderFields(document.getElementById('sms_gateway_type').value);
            });
            </script>

</div>

<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content bk-modal">
            <div class="modal-header modal-header-gold">
                <div class="modal-title-styled" style="color:#0a0a0a;">
                    <div class="title-icon" style="background:rgba(0,0,0,0.15);"><i class="fas fa-file-invoice"></i></div>
                    Booking Details
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    
                    <div class="col-lg-8 bk-left">

                        
                        <div class="bk-section">
                            <div class="bk-section-title"><i class="fas fa-user"></i> Customer Info</div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="bk-label">Name</div>
                                    <div class="bk-value" id="m_name"></div>
                                </div>
                                <div class="col-6">
                                    <div class="bk-label"><i class="fas fa-phone"></i> Phone</div>
                                    <div class="bk-value" id="m_phone"></div>
                                </div>
                                <div class="col-12">
                                    <div class="bk-label"><i class="fas fa-location-dot"></i> Address</div>
                                    <div class="bk-value" id="m_address"></div>
                                </div>
                            </div>
                        </div>

                        
                        <div class="bk-section">
                            <div class="bk-section-title"><i class="fas fa-car"></i> Vehicle</div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="bk-label">Model &amp; Year</div>
                                    <div class="bk-value" id="m_car"></div>
                                    <div class="bk-sub" id="m_year"></div>
                                </div>
                                <div class="col-6">
                                    <div class="bk-label">Type &amp; Color</div>
                                    <div class="bk-value" id="m_type"></div>
                                    <div class="bk-sub" id="m_color"></div>
                                </div>
                            </div>
                        </div>

                        
                        <div class="bk-section">
                            <div class="bk-section-title"><i class="fas fa-film"></i> Package &amp; Specs</div>
                            <div class="bk-value" style="font-size:14px;color:#F5C842;margin-bottom:10px;" id="m_package"></div>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                <span class="bk-badge" id="m_tint_type_badge"></span>
                                <span class="bk-badge bk-badge-muted" id="m_shade"></span>
                                <span class="bk-badge bk-badge-info" id="m_warranty_badge"></span>
                                <span class="bk-badge bk-badge-muted" id="m_installation"></span>
                            </div>
                        </div>

                        
                        <div class="bk-section">
                            <div class="bk-section-title"><i class="fas fa-credit-card"></i> Payment</div>
                            <div class="bk-payment-grid">
                                <div>
                                    <div class="bk-label">Method</div>
                                    <div class="bk-value" id="m_payment_method"></div>
                                </div>
                                <div>
                                    <div class="bk-label">Type</div>
                                    <div class="bk-value" id="m_payment_type"></div>
                                </div>
                                <div>
                                    <div class="bk-label">Status</div>
                                    <div class="bk-value" id="m_payment_status"></div>
                                </div>
                            </div>
                            <div id="payment_reference_container" style="display:none;margin-top:10px;padding-top:10px;border-top:1px solid rgba(212,160,23,0.12)">
                                <div class="bk-label">Reference</div>
                                <span class="bk-ref-code" id="m_payment_reference"></span>
                            </div>
                        </div>

                        
                        <div class="bk-section" style="border-bottom:none;">
                            <div class="row g-3 align-items-end">
                                <div class="col-6">
                                    <div class="bk-label"><i class="fas fa-peso-sign"></i> Total</div>
                                    <div class="bk-total" id="m_price"></div>
                                </div>
                                <div class="col-3">
                                    <div class="bk-label"><i class="far fa-calendar"></i> Date</div>
                                    <div class="bk-value" id="m_date"></div>
                                </div>
                                <div class="col-3">
                                    <div class="bk-label"><i class="far fa-clock"></i> Time</div>
                                    <div class="bk-value" id="m_time"></div>
                                </div>
                            </div>
                        </div>

                        
                        <div id="decline_info_container" style="display:none;padding:0 20px 20px;">
                            <div class="bk-decline-box">
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                                    <i class="fas fa-times-circle" style="color:#ef4444"></i>
                                    <span style="font-weight:700;color:#ef4444">Declined</span>
                                </div>
                                <div style="font-size:13px;color:rgba(255,255,255,0.7);margin-bottom:4px" id="m_rejection_reason"></div>
                                <div style="font-size:11px;color:rgba(255,255,255,0.35)" id="m_rejected_by"></div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="col-lg-4 bk-right">
                        <div style="position:sticky;top:0;">
                            <div class="bk-label" style="margin-bottom:8px">Current Status</div>
                            <div style="margin-bottom:20px">
                                <span class="bk-status-pill" id="m_status_badge"></span>
                            </div>

                            
                            <div id="m_warranty_download_container" style="display:none;margin-bottom:20px;">
                                <a href="#" id="m_warranty_download_link" target="_blank" class="bk-action-btn" style="background:#10b981;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:600;font-size:13px;display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;">
                                    <i class="fas fa-file-pdf"></i> Download Warranty PDF
                                </a>
                            </div>

                            
                            <form method="POST" id="actionForm">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="booking_id" id="m_booking_id">
                                <input type="hidden" name="action" value="update">
                                <div class="bk-label" style="margin-bottom:10px">Update Status</div>
                                <div style="display:flex;flex-direction:column;gap:8px">
                                    <button type="button" class="bk-action-btn bk-btn-approve" onclick="copyAdminSMS('approved'); var f=this.closest('form'); rgConfirm({title:'Approve Booking',message:'Approve this booking? (SMS template copied to clipboard)',confirmText:'Approve',type:'info',onConfirm:function(){var h=document.createElement('input');h.type='hidden';h.name='status';h.value='Approved';f.appendChild(h);f.submit();}})">
                                        <i class="fas fa-thumbs-up"></i> Approve
                                    </button>
                                    <button type="button" class="bk-action-btn bk-btn-complete" onclick="copyAdminSMS('completed'); var f=this.closest('form'); rgConfirm({title:'Mark Completed',message:'Mark this booking as completed? (SMS template copied to clipboard)',confirmText:'Complete',type:'info',onConfirm:function(){var h=document.createElement('input');h.type='hidden';h.name='status';h.value='Completed';f.appendChild(h);f.submit();}})">
                                        <i class="fas fa-circle-check"></i> Mark Completed
                                    </button>
                                    <button type="button" class="bk-action-btn bk-btn-cancel" onclick="copyAdminSMS('cancelled'); var f=this.closest('form'); rgConfirm({title:'Cancel Booking',message:'Cancel this booking? (SMS template copied to clipboard)',confirmText:'Yes, Cancel',type:'warning',onConfirm:function(){var h=document.createElement('input');h.type='hidden';h.name='status';h.value='Cancelled';f.appendChild(h);f.submit();}})">
                                        <i class="fas fa-ban"></i> Cancel
                                    </button>
                                </div>
                            </form>

                            
                            <button type="button" id="viewTransferBtn" class="bk-action-btn transfer-btn" style="margin-top:12px;width:100%;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:600;font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
                                <i class="fas fa-calendar-arrow-right"></i> Transfer Schedule
                            </button>

                            
                            <form method="POST" id="declineForm" style="margin-top:16px">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="booking_id" id="decline_booking_id">
                                <input type="hidden" name="booking_action" value="decline">
                                <div class="bk-label" style="margin-bottom:10px">Decline Booking</div>
                                <textarea class="bk-textarea" name="rejection_reason" id="rejection_reason" rows="3" placeholder="Reason for declining (e.g., Location too far, Owner sick, etc.)" required></textarea>
                                <button type="button" class="bk-action-btn bk-btn-decline" onclick="if(!document.getElementById('rejection_reason').value.trim()){ rgToast('Error','Please write a decline reason first!','error'); return; } copyAdminSMS('declined'); var f=this.closest('form'); rgConfirm({title:'Decline Booking',message:'Are you sure you want to decline this booking? (SMS template copied to clipboard)',confirmText:'Decline',type:'danger',onConfirm:function(){f.submit();}})">
                                    <i class="fas fa-times-circle"></i> Decline Booking
                                </button>
                            </form>

                            
                            <div class="sms-helper-box">
                                <div class="bk-label" style="margin-bottom:10px;display:flex;align-items:center;gap:6px;font-size:11px;font-weight:800;color:#B45309 !important;">
                                    <i class="fas fa-comment-sms"></i> SMS GATEWAY HELPER
                                </div>
                                <div style="display:flex;flex-direction:column;gap:6px;">
                                    <button type="button" class="sms-helper-btn" onclick="copyAdminSMS('approved')">
                                        <i class="fas fa-copy" style="color:#D97706;"></i> Copy Approved SMS
                                    </button>
                                    <button type="button" class="sms-helper-btn" onclick="copyAdminSMS('completed')">
                                        <i class="fas fa-copy" style="color:#059669;"></i> Copy Completed SMS
                                    </button>
                                    <button type="button" class="sms-helper-btn" onclick="copyAdminSMS('cancelled')">
                                        <i class="fas fa-copy" style="color:#EA580C;"></i> Copy Cancelled SMS
                                    </button>
                                    <button type="button" class="sms-helper-btn" onclick="copyAdminSMS('declined')">
                                        <i class="fas fa-copy" style="color:#DC2626;"></i> Copy Declined SMS
                                    </button>
                                </div>
                            </div>
                            <script>
                            window.copyAdminSMS = function(type) {
                                const d = window._currentBookingDataset;
                                if (!d) {
                                    rgToast('Error', 'No booking loaded!', 'error');
                                    return;
                                }
                                
                                const name = d.name || 'Customer';
                                const ref = d.id || '';
                                const date = d.date || '';
                                const time = d.time || '';
                                const pkg = d.package || '';
                                const price = d.price || '';
                                const car = d.car || '';
                                
                                let text = "";
                                if (type === 'approved') {
                                    text = `RG Cartint Works: Hi ${name}! Good news! Your booking (Ref: #${ref}) for ${car} on ${date} at ${time} has been APPROVED. See you soon!`;
                                } else if (type === 'completed') {
                                    text = `RG Cartint Works: Hi ${name}! Your booking (Ref: #${ref}) for ${car} on ${date} at ${time} is now COMPLETED. Thank you for choosing RG Cartint Works!`;
                                } else if (type === 'cancelled') {
                                    text = `RG Cartint Works: Hi ${name}! Your booking (Ref: #${ref}) for ${car} on ${date} at ${time} has been CANCELLED.`;
                                } else if (type === 'declined') {
                                    const reason = document.getElementById('rejection_reason').value || 'Scheduling conflict';
                                    text = `RG Cartint Works: Hi ${name}! Unfortunately, we could not accept your booking (Ref: #${ref}) for ${car} on ${date} at ${time}. Reason: ${reason}.`;
                                }
                                
                                navigator.clipboard.writeText(text).then(() => {
                                    rgToast('Copied', 'SMS text copied to clipboard!', 'success');
                                }).catch(err => {
                                    console.error("Failed to copy text: ", err);
                                });
                            }
                            </script>

                            <hr style="border-color:rgba(212,160,23,0.12);margin:16px 0">

                            
                            <form method="POST" id="deleteBookingForm" data-rg-confirm="Permanently delete this record? This action cannot be undone." data-rg-type="danger" data-rg-title="Delete Record">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="booking_id" id="delete_booking_id">
                                <input type="hidden" name="action" value="Delete">
                                <input type="hidden" name="status" value="Delete">
                                <button type="submit" class="bk-action-btn bk-btn-delete" style="cursor:pointer;">
                                    <i class="fas fa-trash"></i> Delete Record
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="priceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-gold">
                <div class="modal-title-styled">
                    <div class="title-icon"><i class="fas fa-tag"></i></div>
                    Update Package Price
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="package_id" id="price_package_id">
                    <input type="hidden" name="price_action" value="update">
                    
                    <div style="background:var(--body-bg);border-radius:var(--radius-sm);padding:12px 14px;margin-bottom:16px;display:flex;align-items:center;gap:10px;border:1px solid var(--border)">
                        <div style="width:32px;height:32px;border-radius:8px;background:var(--primary-bg);display:flex;align-items:center;justify-content:center;color:var(--primary)">
                            <i class="fas fa-box"></i>
                        </div>
                        <div>
                            <div style="font-size:11px;color:var(--text-muted)">Updating price for</div>
                            <div style="font-weight:700" id="price_package_name"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label-styled"><i class="fas fa-peso-sign"></i> New Price *</label>
                        <div style="position:relative">
                            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-weight:700;color:var(--text-muted)">₱</span>
                            <input type="number" class="form-control-styled" name="new_price" id="new_price" step="0.01" min="1" style="padding-left:26px" required>
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px" id="current_price_display"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label-styled"><i class="fas fa-pen"></i> Reason for change</label>
                        <input type="text" class="form-control-styled" name="reason" placeholder="e.g., Promo, Market adjustment, etc.">
                    </div>
                    
                    <div style="background:var(--info-bg);border:1px solid #a5f3fc;border-radius:var(--radius-sm);padding:10px 12px;font-size:12px;color:var(--info)">
                        <i class="fas fa-info-circle me-2"></i> Price will be updated immediately on the landing page.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="header-btn btn-create" style="padding:9px 20px">
                        <i class="fas fa-save"></i> Update Price
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addCarModelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header modal-header-gold">
                <div class="modal-title-styled" style="color:#0a0a0a;">
                    <div class="title-icon" style="background:rgba(0,0,0,0.15);"><i class="fas fa-car-side"></i></div>
                    <span>Add Car Model</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body" style="padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:14px;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="car_model_action" value="add">
                    <div>
                        <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-trademark me-1"></i> Brand *</label>
                        <input type="text" class="form-control-styled" name="cm_brand" placeholder="e.g. Toyota" required style="font-size:13px;">
                    </div>
                    <div>
                        <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-car me-1"></i> Model *</label>
                        <input type="text" class="form-control-styled" name="cm_model" placeholder="e.g. Vios" required style="font-size:13px;">
                    </div>
                    <div>
                        <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-layer-group me-1"></i> Vehicle Type *</label>
                        <select class="form-select-styled" name="cm_vehicle_type_id" required style="font-size:13px;">
                            <?php foreach($vehicle_types_list as $vt): ?>
                            <option value="<?= $vt['id'] ?>"><?= htmlspecialchars($vt['type_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="padding:10px 1.5rem;">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="header-btn btn-create" style="padding:8px 20px;font-size:13px;">
                        <i class="fas fa-plus"></i> Add Car
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addPackageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header modal-header-gold">
                <div class="modal-title-styled" style="color:#0a0a0a;">
                    <div class="title-icon" style="background:rgba(0,0,0,0.15);"><i class="fas fa-box"></i></div>
                    <span id="packageModalTitle">Add Package</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="packageForm" enctype="multipart/form-data">
                <div class="modal-body" style="padding:1rem 1.25rem;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="package_id" id="package_id">
                    <input type="hidden" name="package_action" id="package_action" value="add">
                    <input type="hidden" name="combo_prices_json" id="comboPricesJson" value="{}">

                    <div class="row g-2">

                        
                        <div class="col-lg-6">

                            
                            <div class="mb-3">
                                <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-box me-1"></i> Package Name *</label>
                                <input type="text" class="form-control-styled" name="name" id="pkg_name" required
                                       placeholder="Enter a unique package name"
                                       data-existing-packages="<?= htmlspecialchars(json_encode(array_map(static fn($pkg) => ['id' => (int)$pkg['id'], 'name' => $pkg['name']], $packages)), ENT_QUOTES, 'UTF-8') ?>"
                                       aria-describedby="pkgNameFeedback" style="font-size:13px;">
                                <small id="pkgNameFeedback" role="alert" style="display:none;color:red !important;font-size:11px;font-weight:600;margin-top:5px;">
                                    <i class="fas fa-circle-exclamation me-1"></i><span style="color:red !important;">This package name has already been used.</span>
                                </small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-align-left me-1"></i> Description</label>
                                <textarea class="form-control-styled" name="description" id="pkg_description" required rows="1" style="font-size:13px;resize:vertical;"></textarea>
                            </div>

                            
                            <div>
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                    <label class="form-label-styled" style="font-size:11px;margin:0;"><i class="fas fa-car me-1"></i> Select Car Models</label>
                                    <span id="cmCheckedCount" style="font-size:11px;color:rgba(255,255,255,0.35);">None selected</span>
                                </div>
                                
                                <div style="display:flex;gap:5px;margin-bottom:5px;">
                                    <button type="button" onclick="selectAllCarModels()" style="font-size:10px;padding:2px 8px;border:1px solid rgba(99,202,183,0.35);background:rgba(99,202,183,0.08);color:#63CAB7;border-radius:4px;cursor:pointer;">&#10003; All</button>
                                    <button type="button" onclick="clearAllCarModels()" style="font-size:10px;padding:2px 8px;border:1px solid rgba(255,255,255,0.12);background:rgba(255,255,255,0.04);color:rgba(255,255,255,0.4);border-radius:4px;cursor:pointer;">&#10005; Clear</button>
                                </div>
                                
                                <div style="margin-bottom:6px;">
                                    <div style="position:relative;">
                                        <i class="fas fa-magnifying-glass" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,0.25);font-size:11px;pointer-events:none;"></i>
                                        <input type="text" id="carModelSearch" class="form-control-styled"
                                               placeholder="Search model..."
                                               style="padding-left:28px;font-size:12px;height:32px;"
                                               oninput="filterCarModels(this.value)">
                                    </div>
                                </div>
                                
                                <div id="carModelPriceList"
                                     style="max-height:130px;overflow-y:auto;border:1px solid rgba(255,255,255,0.09);border-radius:8px;padding:6px 8px;background:rgba(0,0,0,0.18);">
                                    <?php if(empty($all_car_models)): ?>
                                    <p style="color:rgba(255,255,255,0.3);font-size:12px;margin:4px 0;">No car models found.</p>
                                    <?php else: ?>
                                    <?php foreach($all_car_models as $cm): $cmKey = htmlspecialchars($cm); $cmCat = $car_model_cats[$cm] ?? 'sedan'; ?>
                                    <div class="cm-row" data-name="<?= strtolower($cmKey) ?>" data-cat="<?= $cmCat ?>">
                                        <input type="checkbox" id="cm_cb_<?= md5($cm) ?>"
                                               class="cm-checkbox"
                                               data-model="<?= $cmKey ?>"
                                               data-cat="<?= $cmCat ?>"
                                               onchange="toggleCmRow(this)">
                                        <label for="cm_cb_<?= md5($cm) ?>" class="cm-label" title="<?= $cmKey ?>"><?= $cmKey ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div style="margin-top:8px;background:rgba(212,160,23,0.08);border:1px solid rgba(212,160,23,0.22);border-radius:8px;padding:8px 10px;">
                                    <label style="font-size:11px;color:#F5C842;font-weight:600;margin-bottom:4px;display:block;"><i class="fas fa-tag me-1"></i>Package Price / Car (&#8369;) <span style="font-weight:400;opacity:0.55;">&mdash; auto-sum from windows</span></label>
                                    <div style="display:flex;align-items:center;gap:4px;">
                                        <span style="color:rgba(255,255,255,0.4);font-size:14px;font-weight:600;">&#8369;</span>
                                        <input type="number" id="pkgPriceInput" class="form-control-styled" placeholder="0" min="0" step="1" style="font-size:14px;height:34px;font-weight:700;"
                                               oninput="_saveComboFromUI(window._currentCombo);_updatePkgTotal()">
                                    </div>
                                    <small style="color:rgba(255,255,255,0.3);font-size:10px;">Filled automatically when you enter window prices. Same price saved for ALL selected car models</small>
                                </div>
                            </div>

                            
                            <div style="margin-top:8px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">
                                    <label class="form-label-styled" style="font-size:11px;margin:0;"><i class="fas fa-car-side me-1"></i> Windows &amp; Price per Window</label>
                                    <span id="winTotalDisplay" style="font-size:11px;font-weight:700;color:#D4A017;display:none;">Total: &#8369;0</span>
                                </div>
                                <input type="hidden" name="windows_included" id="pkg_windows_included" value="">
                                <input type="hidden" name="window_prices_json" id="windowPricesJson" value="{}">
                                <div class="win-price-list" id="winPriceList">
                                    <?php
                                    $win_opts = [
                                        'windshield' => ['label'=>'Windshield','icon'=>'fa-wind'],
                                        'front_win'  => ['label'=>'Front Win','icon'=>'fa-door-open'],
                                        'rear_win'   => ['label'=>'Rear Win','icon'=>'fa-car-rear'],
                                        'side_win'   => ['label'=>'Side Win','icon'=>'fa-car-side'],
                                        'rear_ws'    => ['label'=>'Rear WS','icon'=>'fa-window-restore'],
                                        'sunstrip'   => ['label'=>'Sunstrip','icon'=>'fa-sun'],
                                    ];
                                    foreach ($win_opts as $wk => $wo): ?>
                                    <div class="win-price-row" data-win="<?= $wk ?>">
                                        <label class="win-price-check" style="cursor:pointer;">
                                            <input type="checkbox" class="win-chk" value="<?= $wk ?>" onchange="syncWindowsField()">
                                            <span class="win-price-pill"><i class="fas <?= $wo['icon'] ?>"></i> <?= $wo['label'] ?></span>
                                        </label>
                                        <div class="win-price-input-wrap">
                                            <span class="win-peso">&#8369;</span>
                                            <input type="number" class="win-price-input" data-win="<?= $wk ?>" step="1" min="0" placeholder="0" disabled>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <small style="color:rgba(255,255,255,0.25);font-size:10px;margin-top:4px;display:block;">&#10003; Check a window to enter its price &mdash; total auto-fills the Package Price above</small>
                                <small id="winSectionHint" style="display:none;color:#F5C842;font-size:10px;margin-top:4px;background:rgba(212,160,23,0.1);padding:3px 8px;border-radius:4px;">&#9432; Multi-car selected &mdash; enter Package Price / Car directly above</small>
                            </div>
                        </div>

                        
                        <div class="col-lg-6">

                            
                            <div class="mb-3">
                                <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-film me-1"></i> Tint Type <span style="font-size:10px;opacity:0.45;font-weight:400;">(multi-select)</span></label>
                                <input type="hidden" name="tint_type" id="pkg_tint_type" value="carbon">
                                <div class="tint-type-grid" id="tintTypeCards">
                                    <?php
                                    $ttIcons = ['dyed'=>'fa-tint','metalized'=>'fa-layer-group','carbon'=>'fa-film','hybrid'=>'fa-compress-arrows-alt','ceramic'=>'fa-gem','nano-ceramic'=>'fa-atom','crystalline'=>'fa-snowflake','infrared'=>'fa-fire','security'=>'fa-shield-halved'];
                                    foreach($tint_types as $k=>$label):
                                        $ico = $ttIcons[$k] ?? 'fa-film';
                                    ?>
                                    <?php
                                        $rolls = $tint_rolls_map[$k] ?? 0;
                                        $type_price = $tint_price_map[$k] ?? 0;
                                    ?>
                                    <div class="tint-type-card<?= $rolls === 0 ? ' no-stock' : '' ?>" data-value="<?= $k ?>" data-rolls="<?= $rolls ?>" data-price="<?= $type_price ?>" <?= $rolls === 0 ? 'title="No stock available — cannot select"' : 'onclick="toggleTintType(\'' . $k . '\')"' ?> <?= $rolls === 0 ? 'aria-disabled="true"' : '' ?>>
                                        <div class="ttc-icon ttc-icon-<?= $k ?>">
                                            <i class="fas <?= $ico ?>"></i>
                                        </div>
                                        <span><?= $label ?></span>
                                        <span class="ttc-stock-badge"><?= $rolls > 0 ? $rolls.' rolls' : 'No stock' ?></span>
                                        <span class="ttc-shade-badge"><?= $type_price > 0 ? '&#8369;'.number_format($type_price,0) : 'No price' ?></span>
                                        <span class="ttc-price" id="ttcPrice_<?= $k ?>"></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            
                            <input type="hidden" name="shade_percentage" id="pkg_shade_percentage" value="{}">
                            <script>window._shadeStockMap = <?= json_encode($shade_stock_map) ?>;</script>
                            
                            <div id="comboInfoBar" style="margin-bottom:10px;padding:5px 10px;background:rgba(212,160,23,0.10);border:1px solid rgba(212,160,23,0.20);border-radius:6px;font-size:11px;color:#F5C842;display:flex;align-items:center;gap:6px;">
                                <i class="fas fa-layer-group"></i>
                                <span>Editing prices for: <strong id="comboInfoText">Carbon — 35%</strong></span>
                            </div>
                            
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                                <div>
                                    <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-shield-halved me-1"></i> Warranty (yrs)</label>
                                    <input type="number" class="form-control-styled" name="warranty_years" id="pkg_warranty" min="0" value="3" style="font-size:13px;">
                                </div>
                                <div>
                                    <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-clock me-1"></i> Install Time</label>
                                    <input type="text" class="form-control-styled" name="installation_time" id="pkg_install" value="2-3 hours" style="font-size:13px;">
                                </div>
                            </div>
                            <input type="hidden" name="display_order" id="pkg_order" value="0">
                            <input type="hidden" name="features" id="pkg_features" value="[]">

                            
                            <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:10px;">
                                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;">
                                    <input type="checkbox" name="is_active" id="pkg_active" value="1" checked style="width:15px;height:15px;accent-color:#D4A017;">
                                    Active (visible on site)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;">
                                    <input type="checkbox" name="is_featured" id="pkg_featured" value="1" style="width:15px;height:15px;accent-color:#D4A017;">
                                    Featured (show in offers)
                                </label>
                            </div>

                            
                            <div style="margin-top:6px;">
                                <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-image me-1"></i> Package Image</label>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <img id="pkgImagePreview" src="" alt="" style="width:60px;height:60px;object-fit:cover;border-radius:8px;border:1px solid rgba(255,255,255,0.12);display:none;">
                                    <div style="flex:1;">
                                        <input type="file" name="package_image" id="pkgImageInput" accept="image/jpeg,image/png,image/webp" class="form-control-styled" style="font-size:11px;padding:5px 8px;">
                                        <small style="color:rgba(255,255,255,0.3);font-size:10px;">JPG, PNG or WebP — max 2MB</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer" style="padding:8px 1.25rem;">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="header-btn btn-create" style="padding:8px 20px;font-size:13px;">
                        <i class="fas fa-save"></i> Save Package
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="createBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-gold">
                <div class="modal-title-styled">
                    <div class="title-icon"><i class="fas fa-plus-circle"></i></div>
                    Create New Booking
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="createBookingForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="booking_action" value="create">
                    <input type="hidden" name="tint_type" id="selected_tint_type" value="">
                    <input type="hidden" name="shade_percentage" id="selected_shade" value="">
                    <input type="hidden" name="warranty_years" id="selected_warranty" value="">
                    <input type="hidden" name="installation_time" id="selected_installation" value="">

                    <div style="background:#FFF9E6;border:1px solid #FDE68A;border-radius:var(--radius-sm);padding:12px 14px;margin-bottom:20px;font-size:13px;color:#92400e;display:flex;align-items:center;gap:8px">
                        <i class="fas fa-circle-info" style="flex-shrink:0"></i>
                        Use this form to create bookings for walk-in customers or those without phones.
                    </div>

                    <div class="section-divider"><div class="section-title"><i class="fas fa-user" style="margin-right:5px"></i>Customer Info</div></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="fas fa-user"></i> Customer Name *</label>
                            <input type="text" class="form-control-styled" name="customer_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="fas fa-phone"></i> Phone</label>
                            <input type="tel" class="form-control-styled" name="phone" placeholder="Optional">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" class="form-control-styled" name="email" placeholder="Optional">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="fas fa-location-dot"></i> Address</label>
                            <input type="text" class="form-control-styled" name="address" placeholder="Optional">
                        </div>
                    </div>

                    <div class="section-divider"><div class="section-title"><i class="fas fa-film" style="margin-right:5px"></i>Package</div></div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label-styled"><i class="fas fa-box"></i> Tint Package *</label>
                            <select class="form-select-styled" name="package_id" id="packageSelect" required onchange="updatePackageDetails()">
                                <option value="">-- Select Package --</option>
                                <?php foreach($all_packages as $pkg):
                                    $min_p = (float)($pkg['sedan_price'] ?: ($pkg['suv_price'] ?: ($pkg['van_price'] ?: 0)));
                                    $s_price = (float)($pkg['sedan_price'] ?? $min_p);
                                    $suv_price = (float)($pkg['suv_price'] ?? $min_p);
                                    $v_price = (float)($pkg['van_price'] ?? $min_p);
                                ?>
                                <option value="<?= $pkg['id'] ?>" 
                                        data-price="<?= $min_p ?>"
                                        data-sedan="<?= $s_price ?>"
                                        data-suv="<?= $suv_price ?>"
                                        data-van="<?= $v_price ?>"
                                        data-type="<?= htmlspecialchars($pkg['tint_type']) ?>" 
                                        data-shade="<?= (int)$pkg['shade_percentage'] ?>" 
                                        data-warranty="<?= (int)$pkg['warranty_years'] ?>" 
                                        data-installation="<?= htmlspecialchars($pkg['installation_time']) ?>" 
                                        data-featured="<?= (int)$pkg['is_featured'] ?>">
                                    <?= $pkg['is_featured'] ? '⭐ ' : '' ?><?= htmlspecialchars($pkg['name']) ?> — ₱<?= number_format($min_p, 0) ?> • <?= ucfirst($pkg['tint_type']) ?> • <?= $pkg['warranty_years'] ?>yr warranty
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="fas fa-peso-sign"></i> Total Price *</label>
                            <div style="position:relative">
                                <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-weight:700;color:var(--text-muted)">₱</span>
                                <input type="number" class="form-control-styled" name="total_price" id="booking_price" style="padding-left:26px" step="0.01" min="0" readonly required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="fas fa-info-circle"></i> Package Details</label>
                            <div style="padding:10px 12px;background:var(--body-bg);border-radius:var(--radius-sm);border:1px solid var(--border);min-height:42px" id="packageDetailsCard">
                                <div id="tintSpecs"><span style="color:var(--text-muted);font-size:12px">← Select a package</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="section-divider"><div class="section-title"><i class="fas fa-calendar-alt" style="margin-right:5px"></i>Appointment</div></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="far fa-calendar"></i> Date *</label>
                            <input type="date" class="form-control-styled" name="appointment_date" id="appointment_date" required min="<?= date('Y-m-d') ?>">
                            <div id="bookingWorkloadBadge" style="margin-top:6px;font-size:11px;font-weight:600;display:none;align-items:center;gap:6px;"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="far fa-clock"></i> Time *</label>
                            <select class="form-select-styled" name="appointment_time" id="appointment_time" required>
                                <option value="">-- Select Time --</option>
                                <?php foreach(['08:00:00'=>'8:00 AM','09:00:00'=>'9:00 AM','10:00:00'=>'10:00 AM','11:00:00'=>'11:00 AM','13:00:00'=>'1:00 PM','14:00:00'=>'2:00 PM','15:00:00'=>'3:00 PM','16:00:00'=>'4:00 PM','17:00:00'=>'5:00 PM'] as $v=>$l): ?>
                                <option value="<?= $v ?>"><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label-styled"><i class="fas fa-note-sticky"></i> Notes</label>
                            <textarea class="form-control-styled" name="notes" rows="3" placeholder="Special instructions..."></textarea>
                        </div>
                    </div>

                    <div class="section-divider"><div class="section-title"><i class="fas fa-credit-card" style="margin-right:5px"></i>Payment &amp; Status</div></div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label-styled"><i class="fas fa-wallet"></i> Payment Method</label>
                            <select class="form-select-styled" name="payment_method">
                                <option value="Cash" selected>Cash</option>
                                <option value="GCash">GCash</option>
                                <option value="Card">Credit/Debit Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-styled"><i class="fas fa-shield-check"></i> Payment Status</label>
                            <select class="form-select-styled" name="payment_status">
                                <option value="Paid" selected>Paid (Full)</option>
                                <option value="Downpayment">Downpayment</option>
                                <option value="Pending">Pending Payment</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-styled"><i class="fas fa-circle-dot"></i> Booking Status</label>
                            <select class="form-select-styled" name="status">
                                <option value="Approved" selected>Approved</option>
                                <option value="Completed">Completed</option>
                                <option value="Pending">Pending</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="header-btn btn-create" style="padding:9px 20px">
                        <i class="fas fa-plus"></i> Create Booking
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addSaleModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-gold">
                <div class="modal-title-styled">
                    <div class="title-icon"><i class="fas fa-cash-register"></i></div>
                    <span id="saleModalTitle">Record New Sale</span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="saleForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="sale_id" id="sale_id">
                    <input type="hidden" name="sales_action" id="sales_action" value="add_sale">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="fas fa-user"></i> Customer Name *</label>
                            <input type="text" class="form-control-styled" name="customer_name" id="sale_customer_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="fas fa-box"></i> Product *</label>
                            <select class="form-select-styled" name="product_name" id="sale_product_name" required>
                                <option value="">-- Select Product / Service --</option>
                                
                                <optgroup label="🎬 Tint Packages">
                                <?php foreach($all_packages as $pkg):
                                    $min_p = (float)($pkg['sedan_price'] ?: ($pkg['suv_price'] ?: ($pkg['van_price'] ?: 0)));
                                ?>
                                    <option value="<?= htmlspecialchars($pkg['name']) ?>" data-price="<?= $min_p ?>">
                                        <?= htmlspecialchars($pkg['name']) ?> — ₱<?= number_format($min_p, 0) ?>
                                    </option>
                                <?php endforeach; ?>
                                </optgroup>

                                <optgroup label="📦 Film Stock Rolls">
                                <?php foreach($tint_stock_items as $item): ?>
                                    <option value="<?= htmlspecialchars($item['film_name']) ?>" data-price="<?= (float)$item['price_per_roll'] ?>">
                                        <?= htmlspecialchars($item['film_name']) ?> (<?= htmlspecialchars($item['tint_type']) ?>) — ₱<?= number_format((float)$item['price_per_roll'], 0) ?>/roll
                                    </option>
                                <?php endforeach; ?>
                                </optgroup>

                                <optgroup label="🛠️ Tinting Services & Extra Services">
                                    <option value="Full Installation Service" data-price="1500">Full Installation Service — ₱1,500</option>
                                    <option value="Tint Removal Service" data-price="1000">Tint Removal Service — ₱1,000</option>
                                    <option value="Windshield Only Tinting" data-price="1800">Windshield Only Tinting — ₱1,800</option>
                                    <option value="Side Windows Tinting" data-price="2200">Side Windows Tinting — ₱2,200</option>
                                    <option value="Warranty Extension Service" data-price="2000">Warranty Extension Service — ₱2,000</option>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <div style="border:1px solid rgba(212,160,23,0.25);border-radius:8px;overflow:hidden;">
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:rgba(212,160,23,0.07);">
                                    <label style="font-size:12px;font-weight:600;color:#F5C842;margin:0;">
                                        <i class="fas fa-car-side me-1"></i> Price Per Window
                                        <span style="font-size:10px;opacity:0.55;font-weight:400;margin-left:4px;">&#8212; fills Unit Price automatically</span>
                                    </label>
                                    <button type="button" onclick="clearWinPrices()" style="font-size:10px;padding:2px 10px;border:1px solid rgba(255,255,255,0.12);background:transparent;color:rgba(255,255,255,0.35);border-radius:4px;cursor:pointer;">&#10005; Clear</button>
                                </div>
                                <div style="padding:10px 12px;background:rgba(0,0,0,0.15);">
                                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;">
                                        <?php
                                        $wb_windows = [
                                            'windshield' => ['label'=>'Windshield','icon'=>'fa-wind'],
                                            'front_win'  => ['label'=>'Front Win','icon'=>'fa-door-open'],
                                            'rear_win'   => ['label'=>'Rear Win','icon'=>'fa-car-rear'],
                                            'side_win'   => ['label'=>'Side Win','icon'=>'fa-car-side'],
                                            'rear_ws'    => ['label'=>'Rear WS','icon'=>'fa-window-restore'],
                                            'sunstrip'   => ['label'=>'Sunstrip','icon'=>'fa-sun'],
                                        ];
                                        foreach ($wb_windows as $wbk => $wbv): ?>
                                        <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:6px;padding:6px 8px;">
                                            <div style="font-size:10px;color:rgba(255,255,255,0.45);margin-bottom:3px;"><i class="fas <?= $wbv['icon'] ?>"></i> <?= $wbv['label'] ?></div>
                                            <div style="display:flex;align-items:center;gap:3px;">
                                                <span style="color:rgba(255,255,255,0.3);font-size:12px;">&#8369;</span>
                                                <input type="number" class="form-control-styled sale-win-price" id="sale_win_<?= $wbk ?>"
                                                       placeholder="0" min="0" step="1"
                                                       style="font-size:12px;height:28px;padding:2px 6px;"
                                                       oninput="calcWinTotal()">
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div style="margin-top:6px;font-size:10px;color:rgba(255,255,255,0.25);font-style:italic;">Leave blank to set Unit Price manually below</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label-styled"><i class="fas fa-hashtag"></i> Quantity *</label>
                            <input type="number" class="form-control-styled" name="quantity" id="sale_quantity" min="1" value="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-styled"><i class="fas fa-peso-sign"></i> Unit Price *</label>
                            <input type="number" class="form-control-styled" name="unit_price" id="sale_unit_price" step="0.01" min="0" required oninput="calcTotal()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-styled"><i class="fas fa-calculator"></i> Total</label>
                            <input type="text" class="form-control-styled" id="sale_total_price" style="background:var(--body-bg)" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-styled"><i class="fas fa-credit-card"></i> Payment Method *</label>
                            <select class="form-select-styled" name="payment_method" id="sale_payment_method" required>
                                <option value="">-- Method --</option>
                                <?php foreach($payment_methods as $k=>$m): ?>
                                <option value="<?= $k ?>"><?= htmlspecialchars($m) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-styled"><i class="fas fa-circle-dot"></i> Status *</label>
                            <select class="form-select-styled" name="payment_status" id="sale_payment_status" required>
                                <option value="paid">Paid</option>
                                <option value="pending">Pending</option>
                                <option value="partial">Partial</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-styled"><i class="far fa-calendar"></i> Date *</label>
                            <input type="date" class="form-control-styled" name="sale_date" id="sale_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="header-btn btn-create" style="padding:9px 20px" id="saleSubmitBtn">
                        <i class="fas fa-floppy-disk"></i> Record Sale
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addTintStockModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header modal-header-gold">
                <div class="modal-title-styled" style="color:#0a0a0a;">
                    <div class="title-icon" style="background:rgba(0,0,0,0.15);"><i class="fas fa-film"></i></div>
                    <span id="tintStockModalTitle">Add Tint Film</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="tintStockForm">
                <div class="modal-body" style="padding:1.25rem 1.5rem;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="stock_id" id="stock_id">
                    <input type="hidden" name="stock_action" id="stock_action" value="add">

                    <div class="row g-3">
                        
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-film me-1"></i> Tint Type *</label>
                                <input type="text" class="form-control-styled" name="film_name" id="film_name" required style="font-size:13px;">
                            </div>
                            <input type="hidden" name="tint_type" id="tint_type" value="">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
                                <div>
                                    <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-trademark me-1"></i> Brand</label>
                                    <input type="text" class="form-control-styled" name="brand" id="tint_brand" style="font-size:13px;">
                                </div>
                                <div>
                                    <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-shield-halved me-1"></i> Warranty (yrs)</label>
                                    <input type="number" class="form-control-styled" name="warranty_years" id="tint_warranty" min="0" value="3" style="font-size:13px;">
                                </div>
                            </div>

                            
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
                                <div>
                                    <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-boxes-stacked me-1"></i> Quantity (Rolls) *</label>
                                    <input type="number" class="form-control-styled" name="quantity_rolls" id="quantity_rolls" min="0" value="100" required style="font-size:13px;">
                                </div>
                                <div>
                                    <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-peso-sign me-1"></i> Price / Roll (₱) *</label>
                                    <input type="number" class="form-control-styled" name="price_per_roll" id="price_per_roll" min="0" step="0.01" value="200" placeholder="0.00" required style="font-size:13px;">
                                </div>
                            </div>
                            
                            <input type="hidden" name="shade_percentage" id="shade_percentage" value="">
                        </div>

                        
                        <div class="col-lg-6">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
                                <div>
                                    <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-ruler-horizontal me-1"></i> Width (cm) *</label>
                                    <input type="number" class="form-control-styled" name="width_cm" id="width_cm" step="0.01" min="0" value="152.4" required style="font-size:13px;">
                                </div>
                                <div>
                                    <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-ruler-vertical me-1"></i> Length (ft) *</label>
                                    <input type="number" class="form-control-styled" name="length_ft" id="length_ft" step="0.01" min="0" value="100" required style="font-size:13px;">
                                </div>
                            </div>
                            <input type="hidden" name="min_rolls" id="min_rolls" value="5">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
                                <div>
                                    <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-fire me-1"></i> Heat Rejection %</label>
                                    <input type="number" class="form-control-styled" name="heat_rejection" id="heat_rejection" min="0" max="100" value="50" style="font-size:13px;">
                                </div>
                                <div>
                                    <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-sun me-1"></i> UV Protection %</label>
                                    <input type="number" class="form-control-styled" name="uv_protection" id="uv_protection" min="0" max="100" value="99" style="font-size:13px;">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label-styled" style="font-size:11px;"><i class="fas fa-location-dot me-1"></i> Location</label>
                                <input type="text" class="form-control-styled" name="location" id="tint_location" value="Main Storage" style="font-size:13px;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="padding:10px 1.5rem;">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="header-btn btn-create" style="padding:8px 20px;font-size:13px;">
                        <i class="fas fa-floppy-disk"></i> Save Film
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="restockTintModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-gold">
                <div class="modal-title-styled">
                    <div class="title-icon"><i class="fas fa-boxes-stacked"></i></div>
                    Restock Tint Film
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="stock_id" id="restock_tint_id">
                    <input type="hidden" name="stock_action" value="restock">
                    <div style="margin-bottom:16px;padding:12px 14px;background:var(--body-bg);border-radius:var(--radius-sm);display:flex;align-items:center;gap:10px">
                        <div style="width:32px;height:32px;background:var(--primary-bg);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--primary)">
                            <i class="fas fa-film"></i>
                        </div>
                        <div>
                            <div style="font-size:11px;color:var(--text-muted)">Film</div>
                            <div style="font-weight:700" id="restock_tint_name"></div>
                        </div>
                    </div>
                    <div>
                        <label class="form-label-styled"><i class="fas fa-plus"></i> Rolls to Add *</label>
                        <input type="number" class="form-control-styled" name="add_rolls" min="1" value="10" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="header-btn btn-create" style="padding:9px 20px">
                        <i class="fas fa-plus"></i> Restock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewTicketModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-gold">
                <div class="modal-title-styled">
                    <div class="title-icon"><i class="fas fa-headset"></i></div>
                    Support Ticket
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="service_id" id="ticket_id">
                    <input type="hidden" name="service_action" id="ticket_action">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="fas fa-user"></i> Customer</label>
                            <div style="font-weight:700" id="ticket_customer"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="fas fa-address-book"></i> Contact</label>
                            <div id="ticket_email"></div>
                            <div id="ticket_phone"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-styled"><i class="fas fa-envelope"></i> Subject</label>
                        <div style="font-weight:700" id="ticket_subject"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-styled"><i class="fas fa-message"></i> Message</label>
                        <div style="background:var(--body-bg);border-radius:var(--radius-sm);padding:12px 14px;font-size:13px;border:1px solid var(--border)" id="ticket_message"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-styled"><i class="fas fa-reply"></i> Response</label>
                        <textarea class="form-control-styled" name="response" id="ticket_response" rows="4"></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="fas fa-circle-dot"></i> Status</label>
                            <select class="form-select-styled" name="status" id="ticket_status">
                                <option value="new">New</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="fas fa-flag"></i> Priority</label>
                            <select class="form-select-styled" name="priority" id="ticket_priority">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="header-btn btn-create" style="padding:9px 20px" onclick="document.getElementById('ticket_action').value='respond'">
                        <i class="fas fa-paper-plane"></i> Send Response
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-gold">
                <div class="modal-title-styled">
                    <div class="title-icon"><i class="fas fa-user-plus"></i></div>
                    <span id="adminModalTitle">Add Admin User</span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="adminForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="admin_id" id="admin_id">
                    <input type="hidden" name="admin_action" id="admin_action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="fas fa-at"></i> Username *</label>
                            <input type="text" class="form-control-styled" name="username" id="admin_username" required>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Letters, numbers & underscore only</div>
                        </div>
                        <div class="col-md-6" id="passwordField">
                            <label class="form-label-styled"><i class="fas fa-lock"></i> Password *</label>
                            <input type="password" class="form-control-styled" name="password" id="admin_password" required minlength="6">
                            <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Minimum 6 characters</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="fas fa-id-card"></i> Full Name *</label>
                            <input type="text" class="form-control-styled" name="full_name" id="admin_fullname" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" class="form-control-styled" name="email" id="admin_email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="fas fa-shield-halved"></i> Role *</label>
                            <select class="form-select-styled" name="role" id="admin_role" required>
                                <option value="">-- Role --</option>
                                <option value="staff">Staff</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-styled"><i class="fas fa-building"></i> Department</label>
                            <input type="text" class="form-control-styled" name="department" id="admin_department" placeholder="e.g. Sales, Operations">
                        </div>
                        <div class="col-12" id="admin_active_section" style="display:none">
                            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;font-weight:500">
                                <input type="checkbox" name="is_active" id="admin_active" value="1" checked style="width:16px;height:16px;accent-color:var(--primary)">
                                <span>Active User (can login to system)</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="header-btn btn-create" style="padding:9px 20px" id="adminSubmitBtn">
                        <i class="fas fa-user-plus"></i> Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="passwordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-gold">
                <div class="modal-title-styled">
                    <div class="title-icon"><i class="fas fa-key"></i></div>
                    Change Password
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="passwordChangeForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="admin_id" id="password_id">
                    <input type="hidden" name="admin_action" value="change_password">
                    <div style="background:var(--body-bg);border-radius:var(--radius-sm);padding:12px 14px;margin-bottom:16px;display:flex;align-items:center;gap:10px;border:1px solid var(--border)">
                        <div style="width:32px;height:32px;border-radius:8px;background:var(--primary-bg);display:flex;align-items:center;justify-content:center;color:var(--primary)">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <div style="font-size:11px;color:var(--text-muted)">Changing password for</div>
                            <div style="font-weight:700" id="password_username"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-styled"><i class="fas fa-lock"></i> New Password *</label>
                        <input type="password" class="form-control-styled" name="new_password" id="new_password" required minlength="6">
                    </div>
                    <div>
                        <label class="form-label-styled"><i class="fas fa-lock-open"></i> Confirm Password *</label>
                        <input type="password" class="form-control-styled" name="confirm_password" id="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="header-btn btn-create" style="padding:9px 20px;background:linear-gradient(135deg,var(--info),#06b6d4)">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

window._schedMonthData     = <?= json_encode($sched_month_data ?? [], JSON_THROW_ON_ERROR) ?>;
window._schedMonthBookings = <?= json_encode($sched_month_bookings ?? [], JSON_THROW_ON_ERROR) ?>;
window._schedViewYear      = <?= (int)date('Y', strtotime($sched_date)) ?>;
window._schedViewMonth     = <?= (int)date('n', strtotime($sched_date)) - 1 ?>;
window._bookingDateTotals  = <?= json_encode($date_totals_map ?? [], JSON_THROW_ON_ERROR) ?>;
window._bookingSlotTotals  = <?= json_encode($slot_totals_map ?? [], JSON_THROW_ON_ERROR) ?>;
</script>

<div class="sched-drawer-overlay" id="fcDrawerOverlay"></div>
<div class="sched-drawer" id="fcDrawer">
    <div class="sched-drawer-hdr">
        <div>
            <div class="sched-drawer-date" id="fcDrawerDate"></div>
            <div class="sched-drawer-count" id="fcDrawerCount"></div>
        </div>
        <button class="sched-drawer-close" id="fcDrawerClose"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="sched-drawer-body" id="fcDrawerBody">
        <div class="sched-drawer-empty">Select a date to view bookings.</div>
    </div>
</div>

<div class="modal fade" id="transferModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:var(--radius-lg);border:1px solid var(--border);overflow:hidden">
      <div class="modal-header modal-header-gold">
        <div style="display:flex;align-items:center;gap:10px">
          <div style="width:32px;height:32px;background:#f59e0b;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px">
            <i class="fas fa-right-left"></i>
          </div>
          <div>
            <h6 class="modal-title" style="font-weight:700;margin:0;font-size:14px">Transfer Schedule</h6>
            <div style="font-size:11px;color:var(--text-muted)">Move this booking to an available date &amp; time</div>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:20px">
        
        <div id="tfCurrentInfo" style="background:var(--smoke);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:16px">
          <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:8px"><i class="fas fa-calendar-check me-1"></i>Current Schedule</div>
          <div style="display:flex;gap:12px;flex-wrap:wrap">
            <div><span style="font-size:11px;color:var(--text-muted)">Customer:</span> <strong id="tfName"></strong></div>
            <div><span style="font-size:11px;color:var(--text-muted)">Date:</span> <strong id="tfCurDate"></strong></div>
            <div><span style="font-size:11px;color:var(--text-muted)">Time:</span> <strong id="tfCurTime"></strong></div>
          </div>
          <div style="margin-top:6px;font-size:12px;color:var(--text-muted)" id="tfVehicle"></div>
        </div>

        
        <div style="margin-bottom:14px">
          <label style="font-size:12px;font-weight:600;margin-bottom:6px;display:block">
            <i class="fas fa-calendar-day me-1" style="color:var(--primary)"></i> Select New Date
          </label>
          <input type="date" id="tfNewDate" class="form-control" style="border-radius:8px;font-size:13px">
        </div>

        
        <div style="margin-bottom:14px">
          <label style="font-size:12px;font-weight:600;margin-bottom:6px;display:block">
            <i class="fas fa-clock me-1" style="color:var(--primary)"></i> Available Time Slots
          </label>
          <div id="tfSlots" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:8px">
            <div style="grid-column:1/-1;text-align:center;padding:20px;color:var(--text-muted);font-size:12px">
              <i class="fas fa-calendar-day" style="font-size:20px;display:block;margin-bottom:6px;opacity:.4"></i>
              Pick a date to see available slots
            </div>
          </div>
        </div>

        
        <div style="margin-bottom:6px">
          <label style="font-size:12px;font-weight:600;margin-bottom:6px;display:block">
            <i class="fas fa-comment-dots me-1" style="color:var(--primary)"></i> Reason <span style="font-weight:400;color:var(--text-muted)">(optional)</span>
          </label>
          <textarea id="tfReason" rows="2" class="form-control" placeholder="e.g. Flood in customer area, customer request..." style="border-radius:8px;font-size:13px;resize:none"></textarea>
        </div>
      </div>
      <div class="modal-footer" style="border-top:1px solid var(--border);padding:14px 20px;gap:8px">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:8px;font-size:12px">Cancel</button>
        <button type="button" id="tfSubmit" class="btn btn-sm btn-warning" disabled style="border-radius:8px;font-size:12px;font-weight:600;min-width:130px">
          <i class="fas fa-right-left me-1"></i> Transfer Schedule
        </button>
      </div>
      <input type="hidden" id="tfBookingId">
      <input type="hidden" id="tfNewTime">
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<div class="rg-toast-wrap" id="rgToastWrap"></div>

<script src="../assets/js/admin.js?v=<?= time() ?>"></script>

<script>

window.confirmDeleteUser = function(id, username, form) {
    const me = <?= $_SESSION['admin_id'] ?? 0 ?>;
    if(id==1){ rgToast('Restricted','Cannot delete main admin','error'); return; }
    if(id==me){ rgToast('Restricted','Cannot delete your own account','error'); return; }
    rgConfirm({title:'Delete User',message:'Delete user "'+username+'"? This cannot be undone.',confirmText:'Delete',type:'danger',onConfirm:function(){ if(form) form.submit(); }});
};
</script>

<?php include __DIR__ . '/../includes/toast.php'; ?>
</body>
</html>
