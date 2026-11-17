<?php

date_default_timezone_set('Asia/Manila');

$http_host = $_SERVER['HTTP_HOST'] ?? '';
$is_local = (
    in_array($http_host, ['localhost', '127.0.0.1'])
    || str_starts_with($http_host, 'localhost:')
    || str_starts_with($http_host, '127.0.0.1:')
    || (php_sapi_name() === 'cli' && empty($http_host))
);

if ($is_local) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'tint_system');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    
    define('DB_HOST', 'sql301.infinityfree.com');
    define('DB_NAME', 'if0_43008284_tint_system');
    define('DB_USER', 'if0_43008284');
    define('DB_PASS', 'lUo2ApR4NKi');
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    if (!$is_local) {
        die("<div style='font-family:sans-serif;padding:24px;border:1px solid #f87171;background:#fef2f2;border-radius:8px;max-width:600px;margin:40px auto;'>"
            . "<h3 style='color:#dc2626;margin-top:0;'>Database Connection Error</h3>"
            . "<p>Could not connect to the InfinityFree MySQL database.</p>"
            . "<p><strong>Host:</strong> " . htmlspecialchars(DB_HOST) . "<br>"
            . "<strong>Database:</strong> " . htmlspecialchars(DB_NAME) . "<br>"
            . "<strong>User:</strong> " . htmlspecialchars(DB_USER) . "</p>"
            . "<p style='color:#6b7280;font-size:13px;'>Tip: In your InfinityFree Control Panel, click <strong>MySQL Databases</strong> to verify the <em>MySQL Hostname</em>, then edit line 25 of <code>includes/db.php</code>.</p>"
            . "<p style='color:#991b1b;font-size:12px;'>Details: " . htmlspecialchars($e->getMessage()) . "</p>"
            . "</div>");
    }
    die("Database connection failed. Please try again later.");
}

if (!defined('BASE_URL')) {
    $proto      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host       = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docRoot    = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $projRoot   = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $webPath    = str_replace($docRoot, '', $projRoot);   
    define('BASE_URL', $proto . '://' . $host . str_replace(' ', '%20', $webPath));
}
