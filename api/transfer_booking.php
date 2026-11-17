<?php

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (isset($_GET['available_slots'], $_GET['date'])) {
    $date = trim($_GET['date']);
    $exclude_id = (int)($_GET['exclude'] ?? 0);

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date']);
        exit;
    }

    
    $dow = (int)date('w', strtotime($date));
    if ($dow === 0 || $dow === 6) {
        echo json_encode(['success' => true, 'slots' => [], 'message' => 'Closed on weekends']);
        exit;
    }

    
    function normalizeTime($t) {
        $t = trim($t);
        if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $t, $m)) {
            $h = (int)$m[1]; $min = $m[2]; $ap = strtoupper($m[3]);
            if ($ap === 'PM' && $h < 12) $h += 12;
            if ($ap === 'AM' && $h === 12) $h = 0;
            return sprintf('%02d:%s', $h, $min);
        }
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $t, $m)) {
            return sprintf('%02d:%s', (int)$m[1], $m[2]);
        }
        return $t;
    }

    
    $sql = "SELECT appointment_time as booked_time
            FROM bookings
            WHERE appointment_date = ?
            AND status IN ('Pending', 'Confirmed', 'Processing', 'Approved', 'On Hold', 'Completed')
            AND appointment_time IS NOT NULL AND appointment_time != ''";
    $params = [$date];
    if ($exclude_id > 0) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $booked_times = array_map('normalizeTime', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'booked_time'));

    
    $all_slots = ['06:00', '08:20', '10:40', '13:00', '15:20'];
    $available = [];

    foreach ($all_slots as $slot) {
        [$sh, $sm] = array_map('intval', explode(':', $slot));
        $slot_mins = $sh * 60 + $sm;
        $blocked = false;
        foreach ($booked_times as $b) {
            [$bh, $bm] = array_map('intval', explode(':', $b));
            $b_mins = $bh * 60 + $bm;
            if (abs($slot_mins - $b_mins) < 140) {
                $blocked = true;
                break;
            }
        }
        $h = (int)substr($slot, 0, 2);
        $m = substr($slot, 3, 2);
        $ampm = $h >= 12 ? 'PM' : 'AM';
        $h12 = $h % 12 ?: 12;
        $available[] = [
            'time'      => $slot,
            'label'     => "$h12:$m $ampm",
            'available' => !$blocked
        ];
    }

    
    if ($date === date('Y-m-d')) {
        $now_mins = (int)date('H') * 60 + (int)date('i');
        foreach ($available as &$s) {
            if ($s['available']) {
                [$sh, $sm] = array_map('intval', explode(':', $s['time']));
                if ($sh * 60 + $sm <= $now_mins) {
                    $s['available'] = false;
                }
            }
        }
        unset($s);
    }

    echo json_encode(['success' => true, 'slots' => $available]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['booking_id'])) {
    $id = (int)$_GET['booking_id'];
    $stmt = $pdo->prepare("
        SELECT b.id, b.customer_name, b.appointment_date, b.appointment_time, b.status, b.user_id,
               b.vehicle_model, b.phone,
               tp.name AS package_name
        FROM bookings b
        LEFT JOIN tint_packages tp ON b.package_id = tp.id
        WHERE b.id = ?
    ");
    $stmt->execute([$id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'booking' => [
            'id'            => (int)$booking['id'],
            'customer_name' => $booking['customer_name'],
            'vehicle_model' => $booking['vehicle_model'] ?? '',
            'phone'         => $booking['phone'] ?? '',
            'package'       => $booking['package_name'] ?? '',
            'date'          => date('Y-m-d', strtotime($booking['appointment_date'])),
            'time'       => $booking['appointment_time'] ?: '—',
            'time_label' => $booking['appointment_time'] ?: '—',
            'date_label'    => date('F j, Y', strtotime($booking['appointment_date'])),
            'status'        => $booking['status'],
            'user_id'       => (int)($booking['user_id'] ?? 0),
        ]
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $new_date   = trim($_POST['new_date'] ?? '');
    $new_time   = trim($_POST['new_time'] ?? '');
    $reason     = trim($_POST['reason'] ?? '');
    
    $allow_customer_override = true;

    if (!$booking_id || !$new_date || !$new_time) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_date) || !preg_match('/^\d{2}:\d{2}$/', $new_time)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date or time format']);
        exit;
    }

    
    $dow = (int)date('w', strtotime($new_date));
    if ($dow === 0 || $dow === 6) {
        echo json_encode(['success' => false, 'message' => 'Cannot schedule on weekends']);
        exit;
    }

    
    if (strtotime($new_date) < strtotime(date('Y-m-d'))) {
        echo json_encode(['success' => false, 'message' => 'Cannot transfer to a past date']);
        exit;
    }

    
    $valid_slots = ['06:00', '08:20', '10:40', '13:00', '15:20'];
    if (!in_array($new_time, $valid_slots)) {
        echo json_encode(['success' => false, 'message' => 'Invalid time slot']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        
        $stmt = $pdo->prepare("SELECT id, customer_name, appointment_date, appointment_time, user_id, status FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
            exit;
        }

        $old_datetime = $booking['appointment_date'];
        $old_time_label = $booking['appointment_time'] ?: '—';
        $new_datetime = $new_date . ' ' . $new_time . ':00';

        
        $new_time_12 = date('g:i A', strtotime($new_time));

        
        
        $conflict = $pdo->prepare("
            SELECT COUNT(*) FROM bookings
            WHERE appointment_date = ?
            AND appointment_time = ?
            AND status IN ('Pending', 'Confirmed', 'Processing', 'Approved', 'On Hold')
            AND id != ?
        ");
        $conflict->execute([$new_date, $new_time_12, $booking_id]);
        if ((int)$conflict->fetchColumn() > 0) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Selected time slot is no longer available']);
            exit;
        }

        
        $update = $pdo->prepare("
            UPDATE bookings
            SET appointment_date = ?,
                appointment_time = ?,
                original_appointment_date = COALESCE(original_appointment_date, CONCAT(?, ' ', COALESCE(appointment_time, ''))),
                reschedule_count = reschedule_count + 1,
                transfer_status = 'pending_customer_approval',
                admin_transfer_reason = ?,
                admin_transferred_at = NOW()
            WHERE id = ?
        ");
        $update->execute([$new_date, $new_time_12, $old_datetime, $reason ?: null, $booking_id]);

        
        $user_id = (int)($booking['user_id'] ?? 0);
        if ($user_id > 0) {
            $old_label = date('F j, Y', strtotime($old_datetime)) . ' ' . $old_time_label;
            $new_label = date('F j, Y', strtotime($new_date)) . ' ' . $new_time_12;
            $reason_text = $reason ? " Reason: $reason" : '';

            $notify = $pdo->prepare("
                INSERT INTO notifications (user_id, user_type, type, title, message, is_read, created_at)
                VALUES (?, 'customer', 'warning', '📅 Admin Rescheduled Your Booking', ?, 0, NOW())
            ");
            $reason_display = $reason ? " Reason: {$reason}." : '';
            $msg = "Your booking #{$booking_id} was moved by admin from {$old_label} to {$new_label}.{$reason_display} Please check your bookings to accept or pick your own preferred date.";
            $notify->execute([$user_id, $msg]);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Booking transferred successfully',
            'new_date' => date('F j, Y', strtotime($new_date)),
            'new_time' => $new_time_12
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("transfer_booking error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
