<?php

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];

function time_ago_str($timestamp) {
    $time_ago = strtotime($timestamp);
    $cur_time = time();
    $time_sec = $cur_time - $time_ago;
    if ($time_sec < 60) return "Just now";
    $mins = round($time_sec / 60);
    if ($mins < 60) return $mins . "m ago";
    $hrs = round($time_sec / 3600);
    if ($hrs < 24) return $hrs . "h ago";
    $days = round($time_sec / 86400);
    if ($days < 30) return $days . "d ago";
    return date("M j", $time_ago);
}

if ($method === 'GET') {
    try {
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE (user_id = ? OR (user_id IS NULL AND user_type = 'customer')) AND is_read = 0");
        $stmt->execute([$user_id]);
        $unread = (int)$stmt->fetchColumn();
        
        
        $stmt = $pdo->prepare("SELECT id, title, message, type, is_read, related_id, related_type, created_at 
                               FROM notifications 
                               WHERE (user_id = ? OR (user_id IS NULL AND user_type = 'customer')) 
                               ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$user_id]);
        $raw_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $notifications = array_map(function($n) {
            $n['time_ago'] = time_ago_str($n['created_at']);
            return $n;
        }, $raw_notifications);
        
        
        $pending_review = null;
        try {
            $stmt = $pdo->prepare("
                SELECT b.id, b.booking_ref, b.vehicle_model, b.tint_type, p.name as package_name 
                FROM bookings b 
                LEFT JOIN tint_packages p ON b.package_id = p.id 
                LEFT JOIN testimonials t ON t.booking_id = b.id
                WHERE b.user_id = ? AND b.status IN ('Completed', 'completed') AND t.id IS NULL 
                ORDER BY b.updated_at DESC, b.id DESC LIMIT 1
            ");
            $stmt->execute([$user_id]);
            $pending_review = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        echo json_encode([
            'success' => true,
            'unread' => $unread,
            'notifications' => $notifications,
            'pending_review' => $pending_review ?: null
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? '';

    try {
        if ($action === 'mark_read') {
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'Invalid ID']);
                exit;
            }
            $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR user_type = 'customer')")
                ->execute([$id, $user_id]);
            echo json_encode(['success' => true]);

        } elseif ($action === 'mark_all_read') {
            $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE (user_id = ? OR user_type = 'customer')")
                ->execute([$user_id]);
            echo json_encode(['success' => true]);

        } elseif ($action === 'delete') {
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'Invalid ID']);
                exit;
            }
            $pdo->prepare("DELETE FROM notifications WHERE id = ? AND (user_id = ? OR user_type = 'customer')")
                ->execute([$id, $user_id]);
            echo json_encode(['success' => true]);

        } else {
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    } catch (PDOException $e) {
        error_log('Customer notifications POST error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
