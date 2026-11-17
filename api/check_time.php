<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();
header("Content-Type: application/json");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_GET["month"])) {
    $month = trim($_GET["month"] ?? "");

    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        echo json_encode(["error" => "Invalid month format. Use YYYY-MM"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT 
                DATE(appointment_date) as booking_date, 
                COUNT(*) as booked_count 
            FROM bookings 
            WHERE DATE_FORMAT(appointment_date, '%Y-%m') = ? 
            AND status IN ('Pending', 'On Hold', 'Confirmed', 'Processing', 'Approved', 'Completed') 
            GROUP BY DATE(appointment_date)
        ");
        $stmt->execute([$month]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $month_data = [];
        foreach ($rows as $row) {
            $month_data[$row["booking_date"]] = (int)$row["booked_count"];
        }

        echo json_encode([
            "month_data" => $month_data,
            "month"      => $month,
            "max_per_day" => 5
        ]);

    } catch (PDOException $e) {
        error_log("check_time.php month error: " . $e->getMessage());
        echo json_encode(["error" => "Database error"]);
    }
    exit;
}

$date = trim($_GET["date"] ?? "");

if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(["error" => "Invalid date format. Use YYYY-MM-DD"]);
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

try {
    
    $stmt = $pdo->prepare("
        SELECT appointment_time as booked_time 
        FROM bookings 
        WHERE appointment_date = ? 
        AND status IN ('Pending', 'On Hold', 'Confirmed', 'Processing', 'Approved', 'Completed')
        AND appointment_time IS NOT NULL AND appointment_time != ''
        ORDER BY appointment_time ASC
    ");
    $stmt->execute([$date]);
    $booked_times = array_map('normalizeTime', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), "booked_time"));

    
    $cnt_stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM bookings 
        WHERE appointment_date = ? 
        AND status IN ('Pending', 'On Hold', 'Confirmed', 'Processing', 'Approved', 'Completed')
    ");
    $cnt_stmt->execute([$date]);
    $total_booked = (int)($cnt_stmt->fetch(PDO::FETCH_ASSOC)["total"] ?? 0);

    
    $all_slots = ['06:00', '08:20', '10:40', '13:00', '15:20'];
    $max_per_day = 5;

    
    $blocked_slots = [];
    foreach ($all_slots as $slot) {
        list($sh, $sm) = array_map('intval', explode(':', $slot));
        $slot_mins = $sh * 60 + $sm;
        foreach ($booked_times as $b) {
            list($bh, $bm) = array_map('intval', explode(':', $b));
            $b_mins = $bh * 60 + $bm;
            if (abs($slot_mins - $b_mins) < 140) {
                $blocked_slots[] = $slot;
                break;
            }
        }
    }

    
    if ($date === date('Y-m-d')) {
        $now_mins = (int)date('H') * 60 + (int)date('i');
        foreach ($all_slots as $slot) {
            if (!in_array($slot, $blocked_slots)) {
                [$sh, $sm] = array_map('intval', explode(':', $slot));
                if ($sh * 60 + $sm <= $now_mins) {
                    $blocked_slots[] = $slot;
                }
            }
        }
    }

    $available = count($all_slots) - count($blocked_slots);

    echo json_encode([
        "booked_times"  => array_values($booked_times),
        "blocked_slots" => array_values($blocked_slots),
        "total_booked"  => $total_booked,
        "available"     => max(0, $available),
        "max_per_day"   => $max_per_day,
        "date"          => $date,
        "fully_booked"  => ($total_booked >= $max_per_day)
    ]);

} catch (PDOException $e) {
    error_log("check_time.php day error: " . $e->getMessage());
    echo json_encode(["error" => "Database error"]);
}
