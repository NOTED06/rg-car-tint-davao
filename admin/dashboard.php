<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

use PDO;
use PDOException;

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit;
}

try {
    $date = $_GET['date'] ?? date('Y-m-d');
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE(appointment_date) as booking_date,
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'Processing' THEN 1 ELSE 0 END) as processing,
            GROUP_CONCAT(TIME(appointment_date) ORDER BY appointment_date) as booked_times
        FROM bookings 
        WHERE DATE(appointment_date) = ?
        GROUP BY DATE(appointment_date)
    ");
    $stmt->execute([$date]);
    $daily_summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    
    $bookings_stmt = $pdo->prepare("
        SELECT b.*, u.full_name, u.phone, v.type_name as vehicle_type, p.name as package_name
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN vehicle_types v ON b.vehicle_type_id = v.id
        LEFT JOIN tint_packages p ON b.package_id = p.id
        WHERE DATE(b.appointment_date) = ?
        ORDER BY b.appointment_date
    ");
    $bookings_stmt->execute([$date]);
    $bookings = $bookings_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $daily_summary = [];
    $bookings = [];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Booking Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1>Booking Schedule Dashboard</h1>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="GET" class="d-flex">
                    <input type="date" name="date" value="<?= $date ?>" class="form-control me-2">
                    <button type="submit" class="btn btn-primary">View Date</button>
                </form>
            </div>
        </div>
        
        <?php if ($daily_summary): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Daily Summary for <?= $date ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h6>Total Bookings</h6>
                                <h3><?= $daily_summary['total_bookings'] ?? 0 ?>/5</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h6>Pending</h6>
                                <h3><?= $daily_summary['pending'] ?? 0 ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h6>Confirmed</h6>
                                <h3><?= $daily_summary['confirmed'] ?? 0 ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h6>Processing</h6>
                                <h3><?= $daily_summary['processing'] ?? 0 ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (isset($daily_summary['booked_times'])): ?>
                <div class="mt-3">
                    <h6>Booked Time Slots:</h6>
                    <p><?= $daily_summary['booked_times'] ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php
        
        $pending_proofs = 0;
        try {
            $pp = $pdo->query("SELECT COUNT(*) FROM bookings WHERE payment_status='Proof Submitted'");
            $pending_proofs = (int)$pp->fetchColumn();
        } catch (PDOException $e) {}
        ?>
        <div class="card mb-3" style="border-left:4px solid #D4A017;">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-1"><i class="fas fa-shield-check me-2" style="color:#D4A017;"></i>Payment Verification Queue</h6>
                    <p class="mb-0 text-muted small">Review GCash / Maya payment proofs submitted by customers</p>
                </div>
                <a href="payment_queue.php" class="btn btn-warning btn-sm fw-bold">
                    <?php if($pending_proofs > 0): ?>
                        Review (<?= $pending_proofs ?> pending)
                    <?php else: ?>
                        View Queue
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Bookings for <?= $date ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($bookings)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Package</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?= htmlspecialchars($booking['appointment_time'] ?: '—') ?></td>
                                <td><?= htmlspecialchars($booking['full_name']) ?><br>
                                    <small><?= htmlspecialchars($booking['phone']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($booking['vehicle_model']) ?><br>
                                    <small><?= htmlspecialchars($booking['vehicle_type']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($booking['package_name']) ?></td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $booking['status'] == 'Confirmed' ? 'success' : 
                                        ($booking['status'] == 'Pending' ? 'warning' : 'info') 
                                    ?>">
                                        <?= $booking['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_booking.php?id=<?= $booking['id'] ?>" class="btn btn-sm btn-info">View</a>
                                    <a href="edit_booking.php?id=<?= $booking['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-muted">No bookings found for this date.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html
