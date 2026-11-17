    <?php
    require __DIR__ . '/../includes/db.php';
    require __DIR__ . '/../includes/auth.php';
    boot_session();

    
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: index.php");
        exit;
    }

    $error = "";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = trim($_POST['username'] ?? '');
        $pass     = $_POST['password'] ?? '';

        if (empty($username) || empty($pass)) {
            $error = "Please enter both username and password.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($pass, $admin['password'])) {
                
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
                $_SESSION['user_agent']      = $_SERVER['HTTP_USER_AGENT'];
                header("Location: index.php");
                exit;
            } else {
                $error = "Invalid credentials. Authorized personnel only.";
            }
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Portal | RG Cartint Works</title>
        <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
        <style>
            :root {
                --accent:       #D4A017;
                --accent-light: #B8860B;
                --accent-dark:  #966A0C;
                --gold:      #D4A017;
                --gold-dark: #B8860B;
                --dark:      #F5F3EF;      
                --dark-card: #FFFFFF;      
                --muted:     #64748b;
                --muted-light: #64748b;
                --white:     #0A0A0A;      
            }
            * { box-sizing: border-box; margin: 0; padding: 0; }

            body {
                font-family: 'DM Sans', sans-serif;
                background: var(--dark);
                min-height: 100vh;
                display: flex;
                align-items: center;
                overflow-x: hidden;
                color: #0A0A0A;
            }

            body::before {
                content: '';
                position: fixed;
                top: -200px; left: -200px;
                width: 600px; height: 600px;
                background: radial-gradient(circle, rgba(212,160,23,0.08) 0%, transparent 70%);
                pointer-events: none; z-index: 0;
            }
            body::after {
                content: '';
                position: fixed;
                bottom: -200px; right: -200px;
                width: 500px; height: 500px;
                background: radial-gradient(circle, rgba(245,166,35,0.06) 0%, transparent 70%);
                pointer-events: none; z-index: 0;
            }

            .login-wrapper {
                width: 100%; max-width: 1200px;
                margin: 0 auto; padding: 24px;
                position: relative; z-index: 1;
            }

            .login-container {
                background: #FFFFFF;
                border-radius: 28px;
                overflow: hidden;
                border: 1px solid #E6E2DA;
                box-shadow: 0 40px 80px rgba(0,0,0,0.06), 0 0 0 1px rgba(212,160,23,0.04);
                min-height: 680px;
                display: flex;
            }

            
            .login-left {
                background: #F5F3EF;
                padding: 56px 48px;
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                position: relative;
                overflow: hidden;
                border-right: 1px solid #E6E2DA;
            }

            .login-left::before {
                content: '';
                position: absolute; inset: 0;
                background-image:
                    linear-gradient(rgba(212,160,23,0.06) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(212,160,23,0.06) 1px, transparent 1px);
                background-size: 40px 40px;
                pointer-events: none;
            }

            .login-left::after {
                content: '';
                position: absolute;
                top: 0; right: 0;
                width: 3px; height: 100%;
                background: linear-gradient(180deg, transparent 0%, var(--accent) 40%, var(--accent-light) 60%, transparent 100%);
                opacity: 0.25;
            }

            .left-content { position: relative; z-index: 2; }

            .logo-wrap {
                display: flex; align-items: center; gap: 16px;
                margin-bottom: 48px;
            }

            .logo-badge {
                width: 52px; height: 52px;
                background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
                border-radius: 14px;
                display: flex; align-items: center; justify-content: center;
                box-shadow: 0 8px 24px rgba(212,160,23,0.25);
                flex-shrink: 0;
            }

            .logo-badge svg {
                width: 26px; height: 26px;
                stroke: #fff; fill: none;
                stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
            }

            .logo-text { display: flex; flex-direction: column; line-height: 1.1; }

            .brand-rg {
                font-family: 'Syne', sans-serif;
                font-weight: 800; font-size: 1.5rem;
                color: var(--accent-light); letter-spacing: -0.5px;
            }

            .brand-name {
                font-family: 'Syne', sans-serif;
                font-weight: 600; font-size: 0.78rem;
                color: #64748b;
                letter-spacing: 1.5px; text-transform: uppercase;
            }

            .left-headline {
                font-family: 'Syne', sans-serif;
                font-weight: 800; font-size: 2.6rem;
                color: var(--white); line-height: 1.15;
                margin-bottom: 16px; letter-spacing: -1px;
            }
            .left-headline span { color: var(--accent); }

            .left-sub {
                font-size: 0.95rem; color: #64748b;
                line-height: 1.6; margin-bottom: 48px; max-width: 320px;
            }

            .features-grid {
                display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
            }

            .feature-pill {
                background: #FFFFFF;
                border: 1px solid #E6E2DA;
                border-radius: 14px; padding: 16px 18px;
                display: flex; align-items: center; gap: 12px;
                transition: all 0.3s ease;
                box-shadow: var(--shadow-sm);
            }

            .feature-pill:hover {
                background: rgba(212,160,23,0.04);
                border-color: rgba(212,160,23,0.25);
            }

            .pill-icon {
                width: 36px; height: 36px;
                background: rgba(212,160,23,0.08);
                border-radius: 10px;
                display: flex; align-items: center; justify-content: center;
                flex-shrink: 0;
            }

            .pill-icon svg {
                width: 18px; height: 18px;
                stroke: var(--accent-light); fill: none;
                stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round;
            }

            .pill-text {
                font-size: 0.8rem; color: #0A0A0A;
                font-weight: 600; line-height: 1.3;
            }

            .left-bottom { position: relative; z-index: 2; margin-top: 40px; }

            .admin-badge {
                display: inline-flex; align-items: center; gap: 8px;
                background: rgba(212,160,23,0.08);
                border: 1px solid rgba(212,160,23,0.2);
                border-radius: 50px; padding: 8px 16px;
                font-size: 0.78rem; font-weight: 600;
                color: var(--accent-light); letter-spacing: 0.5px;
            }

            .admin-badge svg {
                width: 14px; height: 14px;
                stroke: var(--accent-light); fill: none; stroke-width: 2;
            }

            
            .login-right {
                background: var(--dark-card);
                padding: 56px 48px;
                width: 460px; flex-shrink: 0;
                display: flex; flex-direction: column; justify-content: center;
                position: relative;
            }

            .back-home-link {
                position: absolute; top: 28px; left: 48px;
                display: inline-flex; align-items: center; gap: 8px;
                color: #64748b; text-decoration: none;
                font-size: 0.82rem; font-weight: 500; letter-spacing: 0.3px;
                transition: all 0.25s ease;
            }
            .back-home-link:hover { color: var(--gold); transform: translateX(-4px); }
            .back-home-link svg {
                width: 14px; height: 14px;
                stroke: currentColor; fill: none;
                stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
            }

            .form-header { margin-bottom: 36px; }

            .form-eyebrow {
                font-size: 0.72rem; font-weight: 600;
                letter-spacing: 2px; text-transform: uppercase;
                color: var(--accent-light); margin-bottom: 10px;
            }

            .welcome-title {
                font-family: 'Syne', sans-serif;
                font-size: 2.1rem; font-weight: 800;
                color: var(--white); line-height: 1.15;
                letter-spacing: -0.5px; margin-bottom: 8px;
            }

            .welcome-subtitle { color: #64748b; font-size: 0.9rem; }

            .alert-error {
                background: rgba(229,62,62,0.1);
                border: 1px solid rgba(229,62,62,0.3);
                color: #e53e3e; border-radius: 12px;
                padding: 14px 18px; margin-bottom: 24px;
                font-size: 0.88rem; font-weight: 500;
                display: flex; align-items: center; gap: 10px;
            }
            .alert-error svg {
                width: 16px; height: 16px;
                stroke: #e53e3e; fill: none; stroke-width: 2; flex-shrink: 0;
            }

            .form-group { margin-bottom: 20px; }

            .form-label {
                display: block; font-size: 0.8rem; font-weight: 600;
                color: #64748b; margin-bottom: 8px;
                letter-spacing: 0.5px; text-transform: uppercase;
            }

            .input-wrap { position: relative; }

            .input-icon {
                position: absolute; left: 16px; top: 50%;
                transform: translateY(-50%);
                width: 18px; height: 18px;
                stroke: #94a3b8; fill: none;
                stroke-width: 1.7; stroke-linecap: round; stroke-linejoin: round;
                pointer-events: none; transition: stroke 0.25s ease;
            }

            .input-wrap:focus-within .input-icon { stroke: var(--accent); }

            .form-control {
                width: 100%;
                background: #F5F3EF;
                border: 1.5px solid #E6E2DA;
                border-radius: 14px;
                padding: 14px 48px 14px 46px;
                font-size: 0.93rem;
                font-family: 'DM Sans', sans-serif;
                color: #0A0A0A; -webkit-text-fill-color: #0A0A0A;
                caret-color: var(--accent);
                transition: all 0.25s ease;
                height: 52px; outline: none;
            }
            .form-control:-webkit-autofill,
            .form-control:-webkit-autofill:hover,
            .form-control:-webkit-autofill:focus {
                -webkit-text-fill-color: #0A0A0A;
                -webkit-box-shadow: 0 0 0px 1000px rgba(212,160,23,0.06) inset;
                transition: background-color 5000s ease-in-out 0s;
            }
            .form-control::placeholder { color: #94a3b8; font-size: 0.88rem; }
            .form-control:focus {
                border-color: var(--accent);
                background: #FFFFFF;
                box-shadow: 0 0 0 3px rgba(212,160,23,0.12);
            }

            .toggle-pw {
                position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
                background: none; border: none; cursor: pointer; padding: 4px;
                display: flex; align-items: center; justify-content: center;
            }
            .toggle-pw svg {
                width: 18px; height: 18px;
                stroke: #94a3b8; fill: none;
                stroke-width: 1.7; stroke-linecap: round; stroke-linejoin: round;
                transition: stroke 0.2s;
            }
            .toggle-pw:hover svg { stroke: var(--accent-light); }

            .btn-login {
                width: 100%;
                background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
                border: none; border-radius: 14px; padding: 15px;
                font-family: 'Syne', sans-serif;
                font-size: 0.95rem; font-weight: 700;
                color: #0A0A0A; letter-spacing: 0.3px;
                cursor: pointer; transition: all 0.3s ease;
                display: flex; align-items: center; justify-content: center; gap: 10px;
                margin-top: 8px;
                box-shadow: 0 8px 24px rgba(212,160,23,0.3);
            }
            .btn-login svg {
                width: 18px; height: 18px;
                stroke: #0A0A0A; fill: none; stroke-width: 2.2;
                stroke-linecap: round; stroke-linejoin: round;
            }
            .btn-login:hover { transform: translateY(-2px); box-shadow: 0 14px 32px rgba(212,160,23,0.4); }
            .btn-login:active { transform: translateY(0); }

            .links-section {
                margin-top: 28px;
                display: flex; flex-direction: column; align-items: center; gap: 10px;
            }

            .register-link { font-size: 0.88rem; color: #64748b; }
            .register-link a {
                color: var(--gold); font-weight: 600;
                text-decoration: none; transition: color 0.2s;
            }
            .register-link a:hover { color: var(--accent-dark); }

            .customer-login-link {
                margin-top: 2rem;
                padding-top: 1.5rem;
                border-top: 1px solid #E6E2DA;
                text-align: center;
            }
            .customer-login-link p {
                font-size: 0.8rem; color: #64748b; margin-bottom: 0.5rem;
            }
            .customer-login-link a {
                display: inline-flex; align-items: center; gap: 8px;
                font-size: 0.85rem; color: var(--gold);
                text-decoration: none; font-weight: 600;
                opacity: 0.8; transition: opacity 0.2s;
            }
            .customer-login-link a:hover { opacity: 1; color: var(--accent-dark); }
            .customer-login-link a svg {
                width: 15px; height: 15px;
                stroke: var(--gold); fill: none; stroke-width: 2;
            }

            @keyframes fadeUp {
                from { opacity: 0; transform: translateY(18px); }
                to   { opacity: 1; transform: translateY(0); }
            }
            .login-right > * { animation: fadeUp 0.5s ease-out both; }
            .login-right .back-home-link { animation-delay: 0.05s; }
            .login-right .form-header    { animation-delay: 0.1s; }
            .login-right form            { animation-delay: 0.15s; }
            .login-right .links-section  { animation-delay: 0.2s; }
            .login-right .customer-login-link { animation-delay: 0.25s; }

            @media (max-width: 992px) {
                .login-container { flex-direction: column; }
                .login-right { width: 100%; }
                .features-grid { grid-template-columns: 1fr 1fr; }
            }
            @media (max-width: 768px) {
                .login-left  { padding: 40px 28px; }
                .login-right { padding: 48px 28px 40px; }
                .back-home-link { left: 28px; }
                .left-headline { font-size: 2rem; }
            }
            @media (max-width: 480px) {
                .login-wrapper { padding: 16px; }
                .login-left, .login-right { padding: 36px 20px; }
                .back-home-link { left: 20px; top: 20px; }
                .features-grid { grid-template-columns: 1fr; }
                .left-headline { font-size: 1.7rem; }
                .welcome-title { font-size: 1.8rem; }
            }
        </style>
    </head>
    <body>
    <div class="login-wrapper">
        <div class="login-container">

            
            <div class="login-left">
                <div class="left-content">
                    <div class="logo-wrap" style="gap:12px;">
                        <div class="logo-badge" style="background:none;box-shadow:none;width:auto;height:auto;padding:0;">
                            <svg width="34" height="34" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16 2L20 8L26 10L24 16L26 22L20 24L16 30L12 24L6 22L8 16L6 10L12 8L16 2Z" fill="#D4A017"/>
                                <path d="M16 8L19 12L24 14L22 18L24 22L19 24L16 28L13 24L8 22L10 18L8 14L13 12L16 8Z" fill="#0A0A0A"/>
                                <path d="M16 12L18 15L22 16L20 19L22 22L18 23L16 26L14 23L10 22L12 19L10 16L14 15L16 12Z" fill="#F5C842"/>
                            </svg>
                        </div>
                        <div class="logo-text" style="display:flex;flex-direction:column;line-height:1.1;">
                            <span style="font-family:'Bebas Neue', sans-serif;font-size:1.45rem;letter-spacing:.05em;color:#0A0A0A;font-weight:700;">RG CARTINTWORKS</span>
                            <span style="font-size:8px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-top:1px;">Admin Portal</span>
                        </div>
                    </div>

                    <h1 class="left-headline">
                        Restricted<br><span>Access</span> Only.
                    </h1>
                    <p class="left-sub">
                        Authorized administrators only. Manage bookings, packages, inventory, and customer data from your secure control panel.
                    </p>

                    <div class="features-grid">
                        <div class="feature-pill">
                            <div class="pill-icon">
                                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                            </div>
                            <span class="pill-text">Full Dashboard</span>
                        </div>
                        <div class="feature-pill">
                            <div class="pill-icon">
                                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </div>
                            <span class="pill-text">Booking Management</span>
                        </div>
                        <div class="feature-pill">
                            <div class="pill-icon">
                                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </div>
                            <span class="pill-text">Customer Records</span>
                        </div>
                        <div class="feature-pill">
                            <div class="pill-icon">
                                <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            </div>
                            <span class="pill-text">Sales & Reports</span>
                        </div>
                    </div>
                </div>

                <div class="left-bottom">
                    <div class="admin-badge">
                        <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Authorized Personnel Only
                    </div>
                </div>
            </div>

            
            <div class="login-right">
                <a href="../index.php" class="back-home-link">
                    <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                    Back to Website
                </a>

                <div class="form-header">
                    <p class="form-eyebrow">Admin Portal</p>
                    <h2 class="welcome-title">Admin Sign In</h2>
                    <p class="welcome-subtitle">Restricted access — credentials required</p>
                </div>

                <?php if ($error): ?>
                <div class="alert-error">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <div class="input-wrap">
                            <svg class="input-icon" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <input type="text" name="username"
                                class="form-control"
                                placeholder="Enter admin username"
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                required autofocus>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-wrap">
                            <svg class="input-icon" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <input type="password" name="password" id="password"
                                class="form-control"
                                placeholder="Enter admin password"
                                required>
                            <button type="button" class="toggle-pw" id="togglePassword" aria-label="Toggle password">
                                <svg id="eyeIcon" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-login">
                            <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Login
                        </button>
                    </div>
                </form>

                <div class="links-section">
                    <p class="register-link">
                        Need an admin account? <a href="register.php">Create Admin</a>
                    </p>
                </div>

                <div class="customer-login-link">
                    <p>Looking for the customer portal?</p>
                    <a href="../login.php">
                        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Customer Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput  = document.getElementById('password');
        const eyeIcon        = document.getElementById('eyeIcon');
        const eyeOpen = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
        const eyeOff  = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>`;
        togglePassword.addEventListener('click', function () {
            const isPass = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPass ? 'text' : 'password');
            eyeIcon.innerHTML = isPass ? eyeOff : eyeOpen;
        });
    </script>
    </body>
    </html>
