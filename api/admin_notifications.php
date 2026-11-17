<?php

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

header('Content-Type: application/json');

if ((!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') && empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

function admin_time_ago($timestamp) {
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
        $unread = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE (user_type = 'admin' OR user_type IS NULL OR user_id IS NULL) AND is_read = 0")->fetchColumn();

        $stmt = $pdo->query("SELECT id, title, message, type, is_read, related_id, related_type, created_at
                             FROM notifications
                             WHERE (user_type = 'admin' OR user_type IS NULL OR user_id IS NULL)
                             ORDER BY created_at DESC LIMIT 15");
        $raw_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $notifications = array_map(function($n) {
            $n['time_ago'] = admin_time_ago($n['created_at']);
            return $n;
        }, $raw_notifications);

        echo json_encode(['success' => true, 'unread' => $unread, 'notifications' => $notifications]);
    } catch (PDOException $e) {
        error_log('admin_notifications GET error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'unread' => 0, 'notifications' => []]);
    }
    exit;
}

if ($method === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? '';

    
    $csrf = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!empty($_SESSION['csrf_token']) && !empty($csrf) && !hash_equals($_SESSION['csrf_token'], $csrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }

    try {
        if ($action === 'mark_read') {
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$id) { echo json_encode(['success' => false, 'error' => 'Invalid ID']); exit; }
            $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_type = 'admin'")->execute([$id]);
            echo json_encode(['success' => true]);

        } elseif ($action === 'mark_all_read') {
            $pdo->exec("UPDATE notifications SET is_read = 1 WHERE (user_type = 'admin' OR user_type IS NULL OR user_id IS NULL)");
            echo json_encode(['success' => true]);

        } elseif ($action === 'delete') {
            $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$id) { echo json_encode(['success' => false, 'error' => 'Invalid ID']); exit; }
            $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_type = 'admin'")->execute([$id]);
            echo json_encode(['success' => true]);

        } else {
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    } catch (PDOException $e) {
        error_log('admin_notifications POST error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
