<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
boot_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['sales_action'] ?? '';

    try {

        
        
        
        if ($action === 'update') {

            $id       = isset($_POST['sale_id']) ? (int)$_POST['sale_id'] : 0;
            $customer = $_POST['customer_name'] ?? '';
            $qty      = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
            $price    = isset($_POST['unit_price']) ? (float)$_POST['unit_price'] : 0;
            $status   = $_POST['payment_status'] ?? '';

            $total = $qty * $price;

            $stmt = $pdo->prepare("
                UPDATE sales 
                SET customer_name = ?, quantity = ?, unit_price = ?, total_price = ?, payment_status = ?
                WHERE id = ?
            ");

            $stmt->execute([$customer, $qty, $price, $total, $status, $id]);

            $_SESSION['msg'] = "Sale updated successfully!";
        }

        
        
        
        

elseif ($action === 'delete') {
    $id = (int)$_POST['sale_id'];
    
    
    $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['msg'] = "Sale record deleted successfully.";
}

        
        
        
        else {
            $_SESSION['msg'] = "Invalid action!";
        }

    } catch (Exception $e) {
        $_SESSION['msg'] = "Error: " . $e->getMessage();
    }
}

$redirect = $_SERVER['HTTP_REFERER'] ?? 'sales.php';
header("Location: " . $redirect);
exit();

