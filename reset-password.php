<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
boot_session();

if (isset($_SESSION['user_id'])) {
    header("Location: customer/dashboard.php");
    exit;
}

$success = "";
$error = "";
$token = $_GET['token'] ?? '';
$show_form = true;
$user_id = null;

if (empty($token)) {
    $error = "Invalid or missing reset token.";
    $show_form = false;
} else {
    try {
        
        $stmt = $pdo->prepare("SELECT id, email, token_expiry FROM users WHERE reset_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = "Invalid or expired reset token. Please request a new link.";
            $show_form = false;
        } elseif (empty($user['token_expiry']) || strtotime($user['token_expiry']) < time()) {
            $error = "This password reset token has expired. Please request a new one.";
            $show_form = false;

            
            $clear_stmt = $pdo->prepare("UPDATE users SET reset_token = NULL, token_expiry = NULL WHERE id = ?");
            $clear_stmt->execute([$user['id']]);
        } else {
            $user_id = (int)$user['id'];
            $show_form = true;
        }
    } catch (Exception $e) {
        error_log("Reset password token check error: " . $e->getMessage());
        $error = "An error occurred during verification. Please try again later.";
        $show_form = false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $show_form) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password === '' || $confirm_password === '') {
        $error = "Please fill in both password fields.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match. Please ensure both passwords match.";
    } else {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $update_stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE id = ?");
            $result = $update_stmt->execute([$hashed_password, $user_id]);

            if ($result) {
                $success = "Your password has been reset successfully! You can now sign in with your new password.";
                $show_form = false;
            } else {
                $error = "Failed to update password. Please try again.";
            }
        } catch (Exception $e) {
            error_log("Reset password update error: " . $e->getMessage());
            $error = "An error occurred while resetting password. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | RG CARTINTWORKS DAVAO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold: #D4A017;
            --gold-light: #F5C842;
            --ink: #0A0A0A;
            --ink-mid: #161616;
            --font-display: 'Bebas Neue', sans-serif;
            --font-body: 'DM Sans', sans-serif;
        }
        body {
            font-family: var(--font-body);
            background: var(--ink);
            color: #ffffff;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .reset-wrapper { width: 100%; max-width: 1100px; margin: 0 auto; padding: 20px; }
        .reset-container {
            background: var(--ink-mid);
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid rgba(212,160,23,0.25);
            box-shadow: 0 20px 60px rgba(0,0,0,0.6);
            min-height: 620px;
        }
        .reset-left {
            background: linear-gradient(135deg, #0A0A0A 0%, #1A1A1A 100%);
            color: white;
            padding: 50px 40px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            border-right: 1px solid rgba(212,160,23,0.15);
        }
        .logo-circle {
            width: 70px; height: 70px;
            background: rgba(212,160,23,0.15);
            border: 1.5px solid var(--gold);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 25px;
            box-shadow: 0 8px 30px rgba(212,160,23,0.25);
        }
        .logo-circle i { font-size: 2rem; color: var(--gold-light); }
        .brand-title {
            font-family: var(--font-display);
            font-size: 2.8rem;
            letter-spacing: .04em;
            line-height: 1;
            margin-bottom: 10px;
            color: #ffffff;
        }
        .brand-title span { color: var(--gold-light); }
        .brand-tagline { font-size: 0.95rem; color: rgba(255,255,255,0.6); margin-bottom: 35px; }
        .features-list { list-style: none; padding: 0; margin: 0; }
        .features-list li { display: flex; align-items: center; margin-bottom: 18px; font-size: 0.92rem; color: rgba(255,255,255,0.8); }
        .feature-icon {
            width: 36px; height: 36px; background: rgba(212,160,23,0.12);
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
            margin-right: 14px; flex-shrink: 0; border: 1px solid rgba(212,160,23,0.25);
        }
        .feature-icon i { color: var(--gold-light); font-size: 1rem; }
        .reset-right { padding: 50px 40px; display: flex; flex-direction: column; justify-content: center; height: 100%; position: relative; }
        .back-login-link {
            position: absolute; top: 30px; left: 40px;
            display: inline-flex; align-items: center;
            color: rgba(255,255,255,0.6); text-decoration: none; font-weight: 600; font-size: 0.88rem;
            transition: all 0.3s ease;
        }
        .back-login-link:hover { color: var(--gold-light); transform: translateX(-4px); }
        .back-login-link i { margin-right: 8px; }
        .form-header { margin-bottom: 30px; margin-top: 20px; }
        .welcome-title { font-family: var(--font-display); font-size: 2.6rem; letter-spacing: .03em; color: #ffffff; margin-bottom: 8px; }
        .welcome-subtitle { color: rgba(255,255,255,0.6); font-size: 0.95rem; line-height: 1.6; }
        .form-group { margin-bottom: 22px; }
        .form-label { font-weight: 600; color: rgba(255,255,255,0.85); margin-bottom: 8px; font-size: 0.88rem; display: block; }
        .input-with-icon { position: relative; }
        .input-with-icon i.input-icon {
            position: absolute; left: 16px; top: 50%;
            transform: translateY(-50%); color: var(--gold); font-size: 1rem;
        }
        .form-control-custom {
            background: rgba(255,255,255,0.06);
            border: 1.5px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 13px 48px 13px 46px; font-size: 0.95rem;
            color: #ffffff;
            width: 100%;
            transition: all 0.3s ease;
        }
        .form-control-custom:focus {
            outline: none;
            border-color: var(--gold);
            background: rgba(255,255,255,0.09);
            box-shadow: 0 0 0 3px rgba(212,160,23,0.2);
        }
        .password-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            border: none; background: transparent; color: rgba(255,255,255,0.5);
            padding: 6px 10px; cursor: pointer; transition: 0.2s ease;
        }
        .password-toggle:hover { color: var(--gold-light); }
        .alert-error {
            background: rgba(220,53,69,0.15);
            border: 1px solid rgba(220,53,69,0.4);
            color: #ff858d; border-radius: 12px; padding: 16px 20px; margin-bottom: 25px; font-size: 0.9rem;
        }
        .alert-success-card {
            background: rgba(212,160,23,0.08);
            border: 1px solid rgba(212,160,23,0.35);
            border-radius: 16px; padding: 25px; margin-bottom: 25px; text-align: center;
        }
        .btn-submit-gold {
            background: var(--gold);
            border: none; color: var(--ink); font-weight: 700; font-size: 1rem;
            padding: 14px; border-radius: 50px; width: 100%;
            transition: all 0.3s ease; margin-top: 10px;
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 8px 24px rgba(212,160,23,0.25);
            cursor: pointer;
            text-decoration: none;
        }
        .btn-submit-gold:hover { background: var(--gold-light); color: var(--ink); transform: translateY(-2px); box-shadow: 0 12px 30px rgba(212,160,23,0.4); }
        .instructions {
            background: rgba(255,255,255,0.03);
            border-radius: 12px; padding: 18px 20px; margin-bottom: 25px; border-left: 3px solid var(--gold);
        }
        .instructions h6 { color: var(--gold-light); margin-bottom: 8px; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; }
        .instructions ul { margin: 0; padding-left: 18px; color: rgba(255,255,255,0.65); font-size: 0.85rem; }
        .instructions li { margin-bottom: 4px; }
        .links-section { margin-top: 25px; text-align: center; font-size: 0.9rem; color: rgba(255,255,255,0.5); }
        .links-section a { color: var(--gold-light); text-decoration: none; font-weight: 600; }
        .links-section a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="reset-wrapper">
    <div class="reset-container">
        <div class="row g-0">
            
            <div class="col-lg-5">
                <div class="reset-left">
                    <div class="logo-circle">
                        <i class="fas fa-car-side"></i>
                    </div>
                    <h1 class="brand-title">RG CARTINT<span>WORKS</span></h1>
                    <p class="brand-tagline">Secure Password Update</p>
                    <ul class="features-list">
                        <li><div class="feature-icon"><i class="fas fa-shield-alt"></i></div><span>Encrypted Password Hashing</span></li>
                        <li><div class="feature-icon"><i class="fas fa-key"></i></div><span>Strong Security Protocols</span></li>
                        <li><div class="feature-icon"><i class="fas fa-user-check"></i></div><span>Instant Account Access</span></li>
                    </ul>
                </div>
            </div>

            
            <div class="col-lg-7">
                <div class="reset-right">
                    <a href="index.php" class="back-login-link"><i class="fas fa-arrow-left"></i> Back to Home</a>

                    <div class="form-header">
                        <h2 class="welcome-title">RESET YOUR PASSWORD</h2>
                        <p class="welcome-subtitle">Enter your new password below to secure your account.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert-error">
                            <i class="fas fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                            <?php if (!$show_form): ?>
                                <div class="mt-3">
                                    <a href="forgot-password.php" class="btn-submit-gold" style="padding:10px 20px; font-size:0.88rem; width:auto;">
                                        <i class="fas fa-rotate-left me-1"></i> Request New Reset Link
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert-success-card">
                            <div style="width:50px; height:50px; background:rgba(34,197,94,0.15); border:1px solid #22c55e; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px; color:#22c55e; font-size:1.4rem;">
                                <i class="fas fa-check"></i>
                            </div>
                            <h5 style="color:var(--gold-light); font-weight:700; margin-bottom:8px;">Password Updated!</h5>
                            <p style="font-size:0.9rem; color:rgba(255,255,255,0.75); margin-bottom:20px;">
                                <?= htmlspecialchars($success) ?>
                            </p>
                            <a href="index.php?show_login=1" class="btn-submit-gold" style="width:auto; padding:12px 32px;">
                                <i class="fas fa-sign-in-alt me-2"></i> Sign In Now
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($show_form): ?>
                        <div class="instructions">
                            <h6><i class="fas fa-circle-info me-1"></i> Password Guidelines</h6>
                            <ul>
                                <li>Must be at least 8 characters long</li>
                                <li>Both password fields must match exactly</li>
                            </ul>
                        </div>

                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" name="password" id="password" class="form-control-custom"
                                           placeholder="Enter new password (min. 8 characters)" required minlength="8">
                                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Confirm New Password</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control-custom"
                                           placeholder="Confirm new password" required minlength="8">
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn-submit-gold">
                                    <i class="fas fa-save me-2"></i> Update Password
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="links-section">
                        <p>Back to <a href="index.php?show_login=1">Sign In</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (!input) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>
</body>
</html>
