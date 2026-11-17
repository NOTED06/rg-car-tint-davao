<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();
header("Content-Type: application/json");
if (!isset($_SESSION["user_id"])) { echo json_encode(["success"=>false]); exit; }
$input = json_decode(file_get_contents("php://input"),true);
$date=$input["date"]??""; $type=$input["type"]??""; $uid=$_SESSION["user_id"];
if (!$date||!$type) { echo json_encode(["success"=>false]); exit; }
try {
    $pdo->prepare("INSERT INTO notifications (user_id,booking_id,notification_type,message,status) VALUES (?,0,?,?,'Unread')")->execute([$uid,$type,"Slot conflict on ".date("F j, Y",strtotime($date))]);
    echo json_encode(["success"=>true]);
} catch(PDOException $e) { echo json_encode(["success"=>false]); }
