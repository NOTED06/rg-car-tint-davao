<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
boot_session();

if (isset($_SESSION['user_id'])) {
    header("Location: customer/dashboard.php");
    exit;
}

$success = false;
$error = "";
$email = "";
$reset_link = "";
$user_name = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    
    if ($email === '') {
        $error = "Please enter your registered email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            
            $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $user_name = $user['full_name'] ?: 'Customer';
                
                $reset_token = bin2hex(random_bytes(32)); 
                $token_expiry = date('Y-m-d H:i:s', time() + 3600); 

                
                $update_stmt = $pdo->prepare("UPDATE users SET reset_token = ?, token_expiry = ? WHERE id = ?");
                $update_stmt->execute([$reset_token, $token_expiry, $user['id']]);

                
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/');
                $reset_link = $scheme . "://" . $host . $basePath . "/reset-password.php?token=" . urlencode($reset_token);

                $success = true;
            } else {
                $error = "No account found with this email address. Please check your spelling or register a new account.";
            }
        } catch (PDOException $e) {
            error_log("Forgot password DB error: " . $e->getMessage());
            $error = "A database error occurred. Please try again.";
        } catch (Exception $e) {
            error_log("Forgot password error: " . $e->getMessage());
            $error = "An error occurred. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | RG CARTINTWORKS DAVAO</title>
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
            --ink-soft: #2A2A2A;
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
        .forgot-wrapper { width: 100%; max-width: 1100px; margin: 0 auto; padding: 20px; }
        .forgot-container {
            background: var(--ink-mid);
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid rgba(212,160,23,0.25);
            box-shadow: 0 20px 60px rgba(0,0,0,0.6);
            min-height: 620px;
        }
        .forgot-left {
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
        .forgot-right { padding: 50px 40px; display: flex; flex-direction: column; justify-content: center; height: 100%; position: relative; }
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
        .input-with-icon i {
            position: absolute; left: 16px; top: 50%;
            transform: translateY(-50%); color: var(--gold); font-size: 1rem;
        }
        .form-control-custom {
            background: rgba(255,255,255,0.06);
            border: 1.5px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 13px 20px 13px 46px; font-size: 0.95rem;
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
        .btn-outline-gold {
            background: transparent;
            border: 1.5px solid var(--gold);
            color: var(--gold-light);
            font-weight: 600; font-size: 0.9rem;
            padding: 12px 20px; border-radius: 50px;
            transition: all 0.3s ease; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            cursor: pointer; text-decoration: none; margin-top: 10px;
        }
        .btn-outline-gold:hover { background: rgba(212,160,23,0.15); color: #ffffff; }
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
<div class="forgot-wrapper">
    <div class="forgot-container">
        <div class="row g-0">
            
            <div class="col-lg-5">
                <div class="forgot-left">
                    <div class="logo-circle">
                        <i class="fas fa-car-side"></i>
                    </div>
                    <h1 class="brand-title">RG CARTINT<span>WORKS</span></h1>
                    <p class="brand-tagline">Davao's #1 Premium Window Tinting Specialist</p>
                    <ul class="features-list">
                        <li><div class="feature-icon"><i class="fas fa-shield-alt"></i></div><span>Secure Password Recovery System</span></li>
                        <li><div class="feature-icon"><i class="fas fa-user-check"></i></div><span>Verified Account Security</span></li>
                        <li><div class="feature-icon"><i class="fas fa-clock"></i></div><span>One-Time Reset Link (Expires in 1 hr)</span></li>
                        <li><div class="feature-icon"><i class="fas fa-lock"></i></div><span>Protected Member Portal</span></li>
                    </ul>
                </div>
            </div>

            
            <div class="col-lg-7">
                <div class="forgot-right">
                    <a href="index.php" class="back-login-link"><i class="fas fa-arrow-left"></i> Back to Home</a>

                    <div class="form-header">
                        <h2 class="welcome-title">FORGOT PASSWORD?</h2>
                        <p class="welcome-subtitle">Enter your registered email address below to generate a secure password reset link.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert-error">
                            <i class="fas fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert-success-card">
                            <div style="width:50px; height:50px; background:rgba(34,197,94,0.15); border:1px solid #22c55e; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px; color:#22c55e; font-size:1.4rem;">
                                <i class="fas fa-check"></i>
                            </div>
                            <h5 style="color:var(--gold-light); font-weight:700; margin-bottom:8px;">Reset Link Ready!</h5>
                            <p style="font-size:0.9rem; color:rgba(255,255,255,0.75); margin-bottom:18px;">
                                Hello <strong><?= htmlspecialchars($user_name) ?></strong>, your one-time password reset link has been created successfully.
                            </p>
                            
                            <div style="background:rgba(0,0,0,0.4); border:1px solid rgba(212,160,23,0.3); border-radius:12px; padding:15px; margin-bottom:20px; word-break:break-all; font-family:monospace; font-size:0.85rem; color:var(--gold-light);">
                                <?= htmlspecialchars($reset_link) ?>
                            </div>

                            <div class="d-flex flex-wrap gap-2 justify-content-center">
                                <a href="<?= htmlspecialchars($reset_link) ?>" class="btn-submit-gold" style="width:auto; padding:12px 28px;">
                                    <i class="fas fa-key me-2"></i> Reset Password Now
                                </a>
                                <button type="button" onclick="copyResetUrl('<?= htmlspecialchars($reset_link) ?>', this)" class="btn-outline-gold">
                                    <i class="fas fa-copy me-1"></i> Copy Link
                                </button>
                            </div>
                            <small class="d-block text-muted mt-3" style="font-size:0.78rem;">Link expires in 60 minutes for security.</small>
                        </div>
                    <?php endif; ?>

                    <?php if (!$success): ?>
                        <div class="instructions">
                            <h6><i class="fas fa-circle-info me-1"></i> Instructions</h6>
                            <ul>
                                <li>Enter the email address associated with your account</li>
                                <li>Click "Send Reset Link" to generate your secure password reset URL</li>
                                <li>Use the generated link to set up your new password</li>
                            </ul>
                        </div>

                        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                            <div class="form-group">
                                <label class="form-label">Registered Email Address</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="email" class="form-control-custom"
                                           placeholder="e.g. customer@gmail.com"
                                           value="<?= htmlspecialchars($email) ?>" required autofocus>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn-submit-gold">
                                    <i class="fas fa-paper-plane me-2"></i> Send Reset Link
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="links-section">
                        <p>Remember your password? <a href="index.php?show_login=1">Sign In Now</a></p>
                        <a href="register.php" style="color:rgba(255,255,255,0.6); font-size:0.85rem;"><i class="fas fa-user-plus me-1"></i> Don't have an account? Register</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyResetUrl(url, btn) {
    navigator.clipboard.writeText(url).then(function() {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        btn.style.borderColor = '#22c55e';
        btn.style.color = '#22c55e';
        setTimeout(function() {
            btn.innerHTML = originalHtml;
            btn.style.borderColor = '';
            btn.style.color = '';
        }, 2500);
    });
}
</script>
</body>
</html>
