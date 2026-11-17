<?php

function boot_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_start([
            'cookie_lifetime'  => 7200,
            'cookie_secure'    => $isSecure,
            'cookie_httponly'  => true,
            'cookie_samesite'  => 'Lax',
            'use_strict_mode'  => true,
            'use_only_cookies' => true,
        ]);
    }

    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
        session_unset();
        session_destroy();
        return;
    }
    $_SESSION['last_activity'] = time();

    
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }

    
    if (isset($_SESSION['ip']) && $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
        session_unset();
        session_destroy();
        return;
    }
    $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];

    
    
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
}

function require_customer_auth(string $login_url = ''): void
{
    boot_session();

    if (!isset($_SESSION['user_id'])) {
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            header("Location: ../admin/index.php");
            exit;
        }
        $redirect = $login_url ?: '../login.php';
        header("Location: $redirect");
        exit;
    }

    
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page !== 'profile.php' && $current_page !== 'logout.php') {
        global $pdo;
        if (!isset($pdo)) {
            require_once __DIR__ . '/db.php';
        }
        if (isset($pdo)) {
            try {
                $stmt = $pdo->prepare("SELECT phone, address FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user_prof = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user_prof || empty(trim((string)($user_prof['phone'] ?? ''))) || empty(trim((string)($user_prof['address'] ?? '')))) {
                    header("Location: profile.php?incomplete=1");
                    exit;
                }
            } catch (Exception $e) {
                
            }
        }
    }
}

function require_admin_auth(string $login_url = ''): void
{
    boot_session();

    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        $redirect = $login_url ?: '../admin/login.php';
        header("Location: $redirect");
        exit;
    }
}

function destroy_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }
    session_destroy();
}
