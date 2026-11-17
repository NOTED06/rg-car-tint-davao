<?php
require __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

try {
    $count = $pdo->query("SELECT COUNT(*) FROM testimonials WHERE is_approved = 0")->fetchColumn();
    echo json_encode(['count' => (int)$count]);
} catch (PDOException $e) {
    echo json_encode(['count' => 0]);
}
?>
