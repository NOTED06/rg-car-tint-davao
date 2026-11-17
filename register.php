<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
boot_session();

$isAjax = isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

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
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $pass = $_POST['password'] ?? '';

    
    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($pass)) {
        $error = "All fields are required.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email is already registered.";
            } else {
                $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (full_name, email, phone, address, password) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$name, $email, $phone, $address, $hashed_pass])) {
                    
                    $userId = $pdo->lastInsertId();
                    
                    
                    session_regenerate_id(true);

                    
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
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
                    $error = "Failed to create account. Please try again.";
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
    header("Location: index.php?show_register=1");
    exit;
}
?>
