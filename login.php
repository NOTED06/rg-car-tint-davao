<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
boot_session();

$isAjax = isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    if ($isAjax) {
        echo json_encode(['success' => true, 'redirect' => 'admin/index.php']);
        exit;
    }
    header("Location: admin/index.php");
    exit;
}
if (isset($_SESSION['user_id'])) {
    if ($isAjax) {
        echo json_encode(['success' => true, 'redirect' => 'customer/dashboard.php']);
        exit;
    }
    header("Location: customer/dashboard.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                
                session_regenerate_id(true);

                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = 'customer';
                $_SESSION['created'] = time();
                $_SESSION['last_activity'] = time();
                $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

                if ($isAjax) {
                    echo json_encode(['success' => true, 'redirect' => 'customer/dashboard.php']);
                    exit;
                }
                header("Location: customer/dashboard.php");
                exit;
            } else {
                
                $adminStmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? OR username = ? LIMIT 1");
                $adminStmt->execute([$email, $email]);
                $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

                if ($admin && password_verify($password, $admin['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id']        = $admin['id'];
                    $_SESSION['admin_username']  = $admin['username'];
                    $_SESSION['admin_email']     = $admin['email'] ?? '';
                    $_SESSION['admin_name']      = $admin['full_name'] ?? $admin['username'];
                    $_SESSION['role']            = 'admin';
                    $_SESSION['name']            = $admin['full_name'] ?? $admin['username'];
                    $_SESSION['created']         = time();
                    $_SESSION['last_activity']   = time();
                    $_SESSION['ip']              = $_SERVER['REMOTE_ADDR'];
                    $_SESSION['user_agent']      = $_SERVER['HTTP_USER_AGENT'] ?? '';

                    if ($isAjax) {
                        echo json_encode(['success' => true, 'redirect' => 'admin/index.php']);
                        exit;
                    }
                    header("Location: admin/index.php");
                    exit;
                } else {
                    $error = "Invalid email or password. Please try again.";
                }
            }
        } catch (Exception $e) {
            $error = "An error occurred. Please try again later.";
        }
    }

    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $error]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        header("Location: admin/index.php");
        exit;
    }
    header("Location: index.php?show_login=1");
    exit;
}
?>
