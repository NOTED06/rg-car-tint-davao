<?php

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

try {
    $stmt = $pdo->prepare("
        SELECT
            b.id, b.customer_name, b.phone, b.address,
            b.vehicle_model, b.vehicle_year,
            b.tint_type, b.shade_percentage, b.warranty_years,
            b.installation_time, b.total_price,
            b.appointment_date, b.appointment_time, b.status,
            b.payment_method, b.payment_type, b.payment_status,
            b.payment_reference, b.rejection_reason,
            b.package_id,
            v.type_name,
            tp.name  AS package_name,
            au.full_name AS rejected_by_name
        FROM bookings b
        LEFT JOIN vehicle_types  v   ON b.vehicle_type_id = v.id
        LEFT JOIN tint_packages  tp  ON b.package_id      = tp.id
        LEFT JOIN admin_users    au  ON b.rejected_by     = au.id
        WHERE DATE_FORMAT(b.appointment_date, '%Y-%m') = ?
          AND b.status NOT IN ('Cancelled', 'Declined')
        ORDER BY b.appointment_date ASC
    ");
    $stmt->execute([$month]);

    $grouped = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $dateKey = date('Y-m-d', strtotime($r['appointment_date']));
        $grouped[$dateKey][] = [
            'id'                => (int)$r['id'],
            'customer_name'     => $r['customer_name'],
            'phone'             => $r['phone'] ?? '',
            'address'           => $r['address'] ?? '',
            'vehicle_model'     => $r['vehicle_model'] ?? '',
            'vehicle_year'      => $r['vehicle_year'] ?? '',
            'type_name'         => $r['type_name'] ?? '',
            'package'           => $r['package_name'] ?? '',
            'package_id'        => $r['package_id'] ?? '',
            'price'             => number_format((float)$r['total_price'], 2),
            'date'              => date('F j, Y',  strtotime($r['appointment_date'])),
            'time'              => $r['appointment_time'] ?: '—',
            'status'            => $r['status'],
            'tint_type'         => $r['tint_type']         ?? 'carbon',
            'shade'             => (string)($r['shade_percentage'] ?? '35'),
            'warranty'          => (int)($r['warranty_years'] ?? 3),
            'installation'      => $r['installation_time']  ?? '2-3 hours',
            'payment_method'    => $r['payment_method']     ?? 'GCash',
            'payment_type'      => $r['payment_type']       ?? 'full',
            'payment_status'    => $r['payment_status']     ?? 'Pending',
            'payment_reference' => $r['payment_reference']  ?? '',
            'rejection_reason'  => $r['rejection_reason']   ?? '',
            'rejected_by'       => $r['rejected_by_name']   ?? '',
        ];
    }

    echo json_encode(['success' => true, 'data' => $grouped]);
} catch (PDOException $e) {
    error_log('get_schedule error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'data' => []]);
}
