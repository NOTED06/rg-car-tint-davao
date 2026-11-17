<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
boot_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin/index.php?error=invalid_request");
    exit;
}

if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: ../admin/index.php?error=csrf_failed");
    exit;
}

if (($_POST['booking_action'] ?? '') !== 'create') {
    header("Location: ../admin/index.php?error=invalid_action");
    exit;
}

$customer_name     = trim($_POST['customer_name'] ?? '');
$phone             = trim($_POST['phone'] ?? '');
$email             = trim($_POST['email'] ?? '');
$address           = trim($_POST['address'] ?? '');

$vehicle_type_id   = (int)($_POST['vehicle_type_id'] ?? 0);
$vehicle_model     = trim($_POST['vehicle_model'] ?? '');
$vehicle_year      = trim($_POST['vehicle_year'] ?? '');
$vehicle_color     = trim($_POST['vehicle_color'] ?? '');

$package_id        = trim($_POST['package_id'] ?? ''); 
$total_price       = (float)($_POST['total_price'] ?? 0);

$tint_type         = trim($_POST['tint_type'] ?? 'carbon');
$shade_percentage  = (int)($_POST['shade_percentage'] ?? 35);
$warranty_years    = (int)($_POST['warranty_years'] ?? 3);
$installation_time = trim($_POST['installation_time'] ?? '2-3 hours');

$appointment_date  = trim($_POST['appointment_date'] ?? '');
$appointment_time  = trim($_POST['appointment_time'] ?? '');
$notes             = trim($_POST['notes'] ?? '');

if ($customer_name === '' || $vehicle_type_id <= 0 || $vehicle_model === '' || $package_id === '' || $total_price <= 0 || $appointment_date === '' || $appointment_time === '') {
    header("Location: ../admin/index.php?error=missing_fields");
    exit;
}

$vehicle_year = ($vehicle_year === '') ? null : (int)$vehicle_year;

$check = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE appointment_date = :d AND appointment_time = :t AND status IN ('Pending','Approved')");
$check->execute([':d' => $appointment_date, ':t' => $appointment_time]);
if ((int)$check->fetchColumn() > 0) {
    header("Location: ../admin/index.php?error=slot_taken");
    exit;
}

try {
    $pdo->beginTransaction();

    
    
    $customer_id = null;

    if ($phone !== '') {
        $find = $pdo->prepare("SELECT id FROM customers WHERE phone = :phone LIMIT 1");
        $find->execute([':phone' => $phone]);
        $customer_id = $find->fetchColumn();
    }

    if (!$customer_id && $email !== '') {
        $find = $pdo->prepare("SELECT id FROM customers WHERE email = :email LIMIT 1");
        $find->execute([':email' => $email]);
        $customer_id = $find->fetchColumn();
    }

    if ($customer_id) {
        
        $upd = $pdo->prepare("
            UPDATE customers
            SET customer_name = :name,
                address = :address
            WHERE id = :id
        ");
        $upd->execute([
            ':name' => $customer_name,
            ':address' => $address,
            ':id' => $customer_id
        ]);
    } else {
        $insCustomer = $pdo->prepare("
            INSERT INTO customers (customer_name, phone, email, address)
            VALUES (:name, :phone, :email, :address)
        ");
        $insCustomer->execute([
            ':name' => $customer_name,
            ':phone' => ($phone !== '' ? $phone : null),
            ':email' => ($email !== '' ? $email : null),
            ':address' => ($address !== '' ? $address : null),
        ]);
        $customer_id = (int)$pdo->lastInsertId();
    }

    
    $user_id = 0;
    if (!empty($email)) {
        $u_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $u_stmt->execute([$email]);
        $user_id = (int)$u_stmt->fetchColumn();
    }
    if (!$user_id && !empty($phone)) {
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

    $insBooking = $pdo->prepare("
        INSERT INTO bookings (
            user_id,
            customer_id,
            reference_code,
            booking_ref,
            customer_name,
            phone,
            address,
            vehicle_type_id,
            package_id,
            vehicle_model,
            vehicle_year,
            vehicle_color,
            tint_type,
            shade_percentage,
            warranty_years,
            installation_time,
            total_price,
            appointment_date,
            appointment_time,
            notes,
            status,
            created_by_admin
        ) VALUES (
            :user_id,
            :customer_id,
            :reference_code,
            :booking_ref,
            :customer_name,
            :phone,
            :address,
            :vehicle_type_id,
            :package_id,
            :vehicle_model,
            :vehicle_year,
            :vehicle_color,
            :tint_type,
            :shade_percentage,
            :warranty_years,
            :installation_time,
            :total_price,
            :appointment_date,
            :appointment_time,
            :notes,
            'Approved',
            :created_by_admin
        )
    ");

    
    $created_by_admin = $_SESSION['admin_name'] ?? 'Admin';

    $insBooking->execute([
        ':user_id' => $user_id,
        ':customer_id' => $customer_id,
        ':reference_code' => $booking_ref,
        ':booking_ref' => $booking_ref,
        ':customer_name' => $customer_name,
        ':phone' => ($phone !== '' ? $phone : null),
        ':address' => ($address !== '' ? $address : null),
        ':vehicle_type_id' => $vehicle_type_id,
        ':package_id' => $package_id, 
        ':vehicle_model' => $vehicle_model,
        ':vehicle_year' => $vehicle_year,
        ':vehicle_color' => ($vehicle_color !== '' ? $vehicle_color : null),
        ':tint_type' => $tint_type,
        ':shade_percentage' => $shade_percentage,
        ':warranty_years' => $warranty_years,
        ':installation_time' => $installation_time,
        ':total_price' => $total_price,
        ':appointment_date' => $appointment_date,
        ':appointment_time' => $appointment_time,
        ':notes' => ($notes !== '' ? $notes : null),
        ':created_by_admin' => $created_by_admin
    ]);

    $booking_id = (int)$pdo->lastInsertId();

    $pdo->commit();

    
    try {
        require_once __DIR__ . '/../includes/sms_helper.php';
        sendBookingSMS($pdo, $booking_id, 'Approved');
    } catch (Exception $e) {
        error_log("Failed sending walk-in booking SMS: " . $e->getMessage());
    }

    
    header("Location: ../admin/index.php?success=1&booking_id={$booking_id}");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    
    header("Location: ../admin/index.php?error=insert_failed");
    exit;
}

