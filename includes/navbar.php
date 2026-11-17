<?php

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<?php
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_customer = isset($_SESSION['user_id']);
?>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNavbar">
    <div class="container">

        
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>/index.php<?= $is_admin ? '?preview=1' : '' ?>">
            <div class="nav-logo-icon me-2">
                <svg width="30" height="30" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 2L20 8L26 10L24 16L26 22L20 24L16 30L12 24L6 22L8 16L6 10L12 8L16 2Z" fill="#D4A017"/>
                    <path d="M16 8L19 12L24 14L22 18L24 22L19 24L16 28L13 24L8 22L10 18L8 14L13 12L16 8Z" fill="#0A0A0A"/>
                    <path d="M16 12L18 15L22 16L20 19L22 22L18 23L16 26L14 23L10 22L12 19L10 16L14 15L16 12Z" fill="#F5C842"/>
                </svg>
            </div>
            <span class="nav-brand-text">RG CARTINTWORKS</span>
            <span class="nav-city-badge">DAVAO</span>
        </a>

        
        <button class="navbar-toggler nav-toggler-custom" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="toggler-bar"></span>
            <span class="toggler-bar"></span>
            <span class="toggler-bar"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link-custom <?= ($current_page == 'index') ? 'nav-active' : '' ?>" href="<?= BASE_URL ?>/index.php<?= $is_admin ? '?preview=1' : '' ?>">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                        <div class="nav-underline"></div>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link-custom" href="<?= ($current_page == 'index') ? '#services' : BASE_URL . '/index.php#services' ?>">
                        <i class="fas fa-car"></i>
                        <span>Services</span>
                        <div class="nav-underline"></div>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link-custom" href="<?= ($current_page == 'index') ? '#gallery' : BASE_URL . '/index.php#gallery' ?>">
                        <i class="fas fa-images"></i>
                        <span>Gallery</span>
                        <div class="nav-underline"></div>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link-custom" href="<?= ($current_page == 'index') ? '#about' : BASE_URL . '/index.php#about' ?>">
                        <i class="fas fa-info-circle"></i>
                        <span>About Us</span>
                        <div class="nav-underline"></div>
                    </a>
                </li>

                <?php if ($is_admin): ?>
                <li class="nav-item">
                    <a class="nav-link-custom" href="<?= BASE_URL ?>/admin/index.php" style="color:var(--nav-gold);" title="Admin Management Panel">
                        <i class="fas fa-shield-alt"></i>
                        <span>Admin Panel</span>
                        <div class="nav-underline"></div>
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            
            <div class="navbar-actions">
                <?php if ($is_admin): ?>

                    
                    <div class="dropdown">
                        <button class="nav-user-btn dropdown-toggle" type="button" id="adminDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="border-color: rgba(212,160,23,0.4); background: rgba(212,160,23,0.1);">
                            <div class="nav-avatar" style="background: linear-gradient(135deg, #D4A017, #966A0C);">
                                <span class="avatar-initials" style="color:#000;"><i class="fas fa-user-shield" style="font-size:0.75rem;"></i></span>
                            </div>
                            <span class="nav-username d-none d-md-inline" style="color: var(--nav-gold);">
                                <?= htmlspecialchars($_SESSION['admin_username'] ?? $_SESSION['name'] ?? 'Admin') ?>
                            </span>
                            <span class="badge bg-warning text-dark ms-1 d-none d-lg-inline" style="font-size:9px;font-weight:800;letter-spacing:0.5px;padding:3px 6px;">ADMIN</span>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end nav-dropdown-menu nav-user-dropdown">
                            <li>
                                <div class="nav-dropdown-user-header">
                                    <div class="nav-dropdown-avatar" style="background: linear-gradient(135deg, #D4A017, #966A0C); color: #000;">
                                        <span><i class="fas fa-user-shield"></i></span>
                                    </div>
                                    <div>
                                        <div class="nav-dropdown-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? $_SESSION['name'] ?? 'Administrator') ?></div>
                                        <div class="nav-dropdown-role" style="color:var(--nav-gold);font-weight:600;"><i class="fas fa-shield-alt"></i> Administrator</div>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="nav-dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item nav-dropdown-item" href="<?= BASE_URL ?>/admin/index.php">
                                    <i class="fas fa-tachometer-alt" style="color:#D4A017;"></i> Admin Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item nav-dropdown-item" href="<?= BASE_URL ?>/admin/index.php#bookings">
                                    <i class="fas fa-calendar-check" style="color:#3498db;"></i> Manage Bookings
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item nav-dropdown-item" href="<?= BASE_URL ?>/admin/index.php#customers">
                                    <i class="fas fa-users" style="color:#2ecc71;"></i> Manage Customers
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item nav-dropdown-item" href="<?= BASE_URL ?>/admin/index.php#tint-stock">
                                    <i class="fas fa-boxes" style="color:#e67e22;"></i> Tint Inventory
                                </a>
                            </li>
                            <li><hr class="nav-dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item nav-dropdown-item nav-signout text-danger" href="<?= BASE_URL ?>/admin/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Sign Out Admin
                                </a>
                            </li>
                        </ul>
                    </div>

                <?php elseif ($is_customer): ?>

                    
                    <div class="dropdown me-2">
                        <button class="nav-icon-btn dropdown-toggle" type="button" id="navNotifDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <span id="navNotifBadgeWrap" style="position:relative;"></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end notif-dropdown p-0" aria-labelledby="navNotifDropdown">
                            <div class="notif-header d-flex justify-content-between align-items-center px-3 py-2">
                                <h6 class="mb-0 fw-bold" style="font-size:.88rem;color:var(--nav-white);">Notifications</h6>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" id="custMarkAllReadBtn" class="btn btn-link p-0 text-decoration-none" style="font-size:11px;color:var(--nav-gold);font-weight:600;cursor:pointer;">Mark all read</button>
                                    <a href="<?= BASE_URL ?>/customer/profile.php" class="notif-view-all" style="font-size:.8rem;color:var(--nav-gold-light);">View all</a>
                                </div>
                            </div>
                            <div class="notif-list" id="navNotifList">
                                <div class="p-3 text-center text-muted"><small>Loading...</small></div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="dropdown">
                        <button class="nav-user-btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="nav-avatar">
                                <?php
                                $initials = '';
                                if (isset($_SESSION['user_name'])) {
                                    $nameParts = explode(' ', $_SESSION['user_name']);
                                    foreach ($nameParts as $part) {
                                        $initials .= strtoupper(substr($part, 0, 1));
                                    }
                                    $initials = substr($initials, 0, 2);
                                }
                                ?>
                                <span class="avatar-initials"><?= $initials ?: 'U' ?></span>
                            </div>
                            <span class="nav-username d-none d-md-inline">
                                <?= isset($_SESSION['user_name']) ? htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) : 'User' ?>
                            </span>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end nav-dropdown-menu nav-user-dropdown">
                            <li>
                                <div class="nav-dropdown-user-header">
                                    <div class="nav-dropdown-avatar">
                                        <span><?= $initials ?: 'U' ?></span>
                                    </div>
                                    <div>
                                        <div class="nav-dropdown-name"><?= isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User' ?></div>
                                        <div class="nav-dropdown-role">Member</div>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="nav-dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item nav-dropdown-item" href="<?= BASE_URL ?>/customer/dashboard.php">
                                    <i class="fas fa-tachometer-alt" style="color:#3498db;"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item nav-dropdown-item" href="<?= BASE_URL ?>/customer/my_bookings.php">
                                    <i class="fas fa-calendar-alt" style="color:#22c55e;"></i> My Bookings
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item nav-dropdown-item" href="<?= BASE_URL ?>/customer/profile.php">
                                    <i class="fas fa-user-circle" style="color:#D4A017;"></i> My Profile
                                </a>
                            </li>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                            <li>
                                <a class="dropdown-item nav-dropdown-item" href="<?= BASE_URL ?>/admin/index.php">
                                    <i class="fas fa-cog" style="color:#e74c3c;"></i> Admin Panel
                                </a>
                            </li>
                            <?php endif; ?>
                            <li><hr class="nav-dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item nav-dropdown-item nav-signout" href="<?= BASE_URL ?>/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Sign Out
                                </a>
                            </li>
                        </ul>
                    </div>

                <?php else: ?>
                    <div class="d-flex gap-2">
                        <button type="button" class="nav-signin-btn" data-bs-toggle="modal" data-bs-target="#signInModal">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Sign In</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="modal fade" id="signInModal" tabindex="-1" aria-labelledby="signInModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg signin-modal-dialog">
        <div class="modal-content signin-modal-content">
            <button type="button" class="signin-modal-close" data-bs-dismiss="modal" aria-label="Close">
                <i class="fas fa-xmark"></i>
            </button>

            <div class="signin-split">
                
                <div class="signin-left">
                    <div class="signin-left-inner">
                        <div class="signin-logo">
                            <div class="signin-logo-badge">
                                <i class="fas fa-car-side"></i>
                            </div>
                            <div>
                                <span class="signin-brand-rg">Rg Cartint</span>
                                <span class="signin-brand-sub">Works Davao</span>
                            </div>
                        </div>
                        <h2 class="signin-headline">Premium Tint.<br><span>Lifetime</span> Warranty.</h2>
                        <p class="signin-left-desc">Sign in to manage your bookings, warranty records, and service history — all in one secure place.</p>
                        <div class="signin-features">
                            <div class="signin-pill"><i class="fas fa-shield-alt" style="color:#D4A017;font-size:1.1rem;"></i><span>Secure Portal Access</span></div>
                            <div class="signin-pill"><i class="fas fa-calendar-check" style="color:#D4A017;font-size:1.1rem;"></i><span>Manage Appointments</span></div>
                            <div class="signin-pill"><i class="fas fa-file-alt" style="color:#D4A017;font-size:1.1rem;"></i><span>Warranty Documents</span></div>
                            <div class="signin-pill"><i class="fas fa-history" style="color:#D4A017;font-size:1.1rem;"></i><span>Service History</span></div>
                        </div>
                    </div>
                    <div class="signin-left-badge">
                        <i class="fas fa-circle-check"></i> Official Warranty Guaranteed
                    </div>
                </div>

                
                <div class="signin-right">
                    <div class="signin-form-eyebrow">Customer Portal</div>
                    <h3 class="signin-welcome" id="signInModalLabel">Welcome Back!</h3>
                    <p class="signin-welcome-sub">Sign in to access your bookings &amp; account</p>

                    <div class="signin-error-wrap" id="signinErrorWrap" style="display:none">
                        <i class="fas fa-circle-exclamation"></i>
                        <span id="signinErrorMsg"></span>
                    </div>

                    <form method="POST" action="<?= BASE_URL ?>/login.php" id="signInForm">
                        <div class="signin-field">
                            <label>Email Address</label>
                            <div class="signin-input-wrap">
                                <i class="fas fa-envelope signin-input-icon"></i>
                                <input type="email" name="email" placeholder="your@email.com" required autocomplete="email">
                            </div>
                        </div>
                        <div class="signin-field">
                            <label>Password</label>
                            <div class="signin-input-wrap">
                                <i class="fas fa-lock signin-input-icon"></i>
                                <input type="password" name="password" id="signinPassword" placeholder="Enter your password" required autocomplete="current-password">
                                <button type="button" class="signin-pw-toggle" onclick="toggleSigninPw()">
                                    <i class="fas fa-eye" id="signinEyeIcon"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="signin-submit-btn">
                            <i class="fas fa-arrow-right"></i> Sign In
                        </button>
                    </form>

                    <div class="signin-links">
                        <p>Don't have an account? <a href="#" onclick="switchToRegister(event)"><strong>Create Account</strong></a></p>
                        <a href="<?= BASE_URL ?>/forgot-password.php" class="signin-forgot">
                            <i class="fas fa-circle-question"></i> Forgot your password?
                        </a>
                    </div>

                    <div class="signin-divider"><span>Or continue with</span></div>

                    <div class="signin-social">
                        <button type="button" class="signin-social-btn" onclick="rgToast('Coming Soon','Google login coming soon!','info')">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                            Google
                        </button>
                        <button type="button" class="signin-social-btn" onclick="rgToast('Coming Soon','Facebook login coming soon!','info')">
                            <svg viewBox="0 0 24 24" width="18" height="18"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" fill="#1877F2"/></svg>
                            Facebook
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg reg-modal-dialog">
        <div class="modal-content reg-modal-content">
            <button type="button" class="reg-modal-close" data-bs-dismiss="modal" aria-label="Close">
                <i class="fas fa-xmark"></i>
            </button>

            <div class="reg-split">
                
                <div class="reg-left">
                    <div class="reg-left-inner">
                        <div class="reg-logo">
                            <div class="reg-logo-badge">
                                <i class="fas fa-car-side"></i>
                            </div>
                            <div>
                                <span class="reg-brand-rg">Rg Cartint</span>
                                <span class="reg-brand-sub">Works Davao</span>
                            </div>
                        </div>
                        <h2 class="reg-headline">Join the<br><span>Premium</span> Community.</h2>
                        <p class="reg-left-desc">Create your account and unlock exclusive benefits, warranty tracking, and seamless service management.</p>
                        <div class="reg-features">
                            <div class="reg-pill"><i class="fas fa-shield-alt" style="color:#D4A017;font-size:1.1rem;"></i><span>Official Warranty Access</span></div>
                            <div class="reg-pill"><i class="fas fa-calendar-check" style="color:#D4A017;font-size:1.1rem;"></i><span>Easy Scheduling</span></div>
                            <div class="reg-pill"><i class="fas fa-file-alt" style="color:#D4A017;font-size:1.1rem;"></i><span>Digital Documents</span></div>
                            <div class="reg-pill"><i class="fas fa-gift" style="color:#D4A017;font-size:1.1rem;"></i><span>Member Discounts</span></div>
                            <div class="reg-pill reg-pill-full"><i class="fas fa-history" style="color:#D4A017;font-size:1.1rem;"></i><span>Full Service History Tracking</span></div>
                        </div>
                    </div>
                    <div class="reg-left-badge">
                        <i class="fas fa-circle-check"></i> Official Warranty Guaranteed
                    </div>
                </div>

                
                <div class="reg-right">
                    <div class="reg-form-eyebrow">New Member</div>
                    <h3 class="reg-welcome" id="registerModalLabel">Create Account</h3>
                    <p class="reg-welcome-sub">Join Rg Cartint Works in less than a minute</p>

                    <div class="reg-error-wrap" id="regErrorWrap" style="display:none">
                        <i class="fas fa-circle-exclamation"></i>
                        <span id="regErrorMsg"></span>
                    </div>

                    <form method="POST" action="<?= BASE_URL ?>/register.php" id="registerForm">
                        <div class="reg-field">
                            <label>Full Name</label>
                            <div class="reg-input-wrap">
                                <i class="fas fa-user reg-input-icon"></i>
                                <input type="text" name="name" placeholder="Enter your full name" required autocomplete="name">
                            </div>
                        </div>
                        <div class="reg-field">
                            <label>Email Address</label>
                            <div class="reg-input-wrap">
                                <i class="fas fa-envelope reg-input-icon"></i>
                                <input type="email" name="email" placeholder="your@email.com" required autocomplete="email">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div class="reg-field">
                                <label>Phone Number</label>
                                <div class="reg-input-wrap">
                                    <i class="fas fa-phone reg-input-icon"></i>
                                    <input type="text" name="phone" placeholder="0917xxxxxxx" required>
                                </div>
                            </div>
                            <div class="reg-field">
                                <label>Address</label>
                                <div class="reg-input-wrap">
                                    <i class="fas fa-location-dot reg-input-icon"></i>
                                    <input type="text" name="address" placeholder="Davao City" required>
                                </div>
                            </div>
                        </div>
                        <div class="reg-field">
                            <label>Password</label>
                            <div class="reg-input-wrap">
                                <i class="fas fa-lock reg-input-icon"></i>
                                <input type="password" name="password" id="regPassword" placeholder="Create a strong password" required autocomplete="new-password">
                                <button type="button" class="reg-pw-toggle" onclick="toggleRegPw()">
                                    <i class="fas fa-eye" id="regEyeIcon"></i>
                                </button>
                            </div>
                            <div class="reg-strength-track">
                                <div class="reg-strength-bar" id="regStrengthBar"></div>
                            </div>
                            <div class="reg-strength-row">
                                <span class="reg-strength-text" id="regStrengthText"></span>
                            </div>
                            <ul class="reg-pw-requirements" id="regPwRequirements">
                                <li id="regReqLength"><span class="reg-req-dot"></span>8+ characters</li>
                                <li id="regReqUpper"><span class="reg-req-dot"></span>Uppercase letter</li>
                                <li id="regReqLower"><span class="reg-req-dot"></span>Lowercase letter</li>
                                <li id="regReqNumber"><span class="reg-req-dot"></span>One number</li>
                                <li id="regReqSpecial"><span class="reg-req-dot"></span>Special character</li>
                            </ul>
                        </div>

                        <div class="reg-terms">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="regTermsAgree" required>
                                <label class="form-check-label" for="regTermsAgree">
                                    I agree to the <a href="#" onclick="event.preventDefault()">Terms &amp; Conditions</a> and <a href="#" onclick="event.preventDefault()">Privacy Policy</a>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="reg-submit-btn" id="regSubmitBtn">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>
                    </form>

                    <div class="reg-links">
                        <p>Already have an account? <a href="#" onclick="switchToSignIn(event)"><strong>Sign In</strong></a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="customerReviewModal" tabindex="-1" aria-labelledby="customerReviewModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:390px;margin:1.5rem auto;">
        <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 20px 40px -10px rgba(0,0,0,0.3);overflow:hidden;background:#ffffff;">
            
            <div style="background:linear-gradient(135deg, #1e293b 0%, #0f172a 100%);padding:14px 18px;color:#fff;position:relative;display:flex;align-items:center;justify-space-between;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:28px;height:28px;border-radius:8px;background:rgba(212,160,23,0.2);display:flex;align-items:center;justify-content:center;color:#fbbf24;font-size:0.9rem;">
                        <i class="fas fa-star"></i>
                    </div>
                    <span style="font-size:0.95rem;font-weight:700;color:#fff;" id="customerReviewModalLabel">Rate Service Experience</span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="opacity:0.8;font-size:0.75rem;"></button>
            </div>

            
            <div class="modal-body" style="padding:16px 18px;">
                <form id="ajaxReviewForm">
                    <input type="hidden" name="booking_id" id="rev_modal_booking_id" value="">
                    
                    
                    <div id="rev_modal_vehicle_badge" style="background:#f8fafc;border:1px solid #e2e8f0;padding:8px 12px;border-radius:8px;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-shield-check" style="color:#d4a017;font-size:1rem;flex-shrink:0;"></i>
                        <div style="font-size:0.8rem;color:#334155;font-weight:600;line-height:1.2;" id="rev_modal_vehicle_text">
                            Completed Booking Tint Installation
                        </div>
                    </div>

                    
                    <div class="text-center mb-3">
                        <div class="star-rating-select" style="display:flex;justify-content:center;gap:6px;font-size:1.75rem;color:#cbd5e1;cursor:pointer;">
                            <i class="fas fa-star star-btn" data-value="1"></i>
                            <i class="fas fa-star star-btn" data-value="2"></i>
                            <i class="fas fa-star star-btn" data-value="3"></i>
                            <i class="fas fa-star star-btn" data-value="4"></i>
                            <i class="fas fa-star star-btn" data-value="5"></i>
                        </div>
                        <input type="hidden" name="rating" id="rev_modal_rating_val" value="5">
                        <div style="font-size:0.8rem;font-weight:700;color:#d4a017;margin-top:4px;" id="rev_modal_rating_label">
                            5.0 Stars — Excellent!
                        </div>
                    </div>

                    
                    <div class="mb-3">
                        <textarea class="form-control" name="comment" id="rev_modal_comment" rows="2" placeholder="Share your experience (quality, service, installation)..." required style="border-radius:8px;border:1px solid #cbd5e1;padding:8px 10px;font-size:0.82rem;resize:none;"></textarea>
                    </div>

                    
                    <div class="form-check mb-3" style="padding-left:1.5rem;">
                        <input class="form-check-input" type="checkbox" name="would_recommend" id="rev_modal_recommend" checked style="border-radius:4px;cursor:pointer;margin-left:-1.2rem;">
                        <label class="form-check-label" for="rev_modal_recommend" style="font-size:0.78rem;color:#475569;cursor:pointer;">
                            I recommend RG CARTINTWORKS
                        </label>
                    </div>

                    
                    <div style="display:flex;gap:8px;">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="flex:1;border-radius:8px;font-weight:600;padding:7px;font-size:0.82rem;">
                            Later
                        </button>
                        <button type="submit" id="rev_modal_submit_btn" class="btn" style="flex:2;background:linear-gradient(135deg,#d4a017,#b45309);color:#fff;border:none;border-radius:8px;font-weight:700;padding:7px;font-size:0.82rem;box-shadow:0 3px 8px rgba(212,160,23,0.3);">
                            <i class="fas fa-paper-plane me-1"></i> Submit Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="customerSupportModal" tabindex="-1" aria-labelledby="customerSupportModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:410px;margin:1.5rem auto;">
        <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 20px 40px -10px rgba(0,0,0,0.3);overflow:hidden;background:#ffffff;">
            
            <div style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%);padding:14px 18px;color:#fff;position:relative;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:30px;height:30px;border-radius:8px;background:rgba(212,160,23,0.2);display:flex;align-items:center;justify-content:center;color:#fbbf24;font-size:0.9rem;">
                        <i class="fas fa-headset"></i>
                    </div>
                    <span style="font-size:0.95rem;font-weight:700;color:#fff;" id="customerSupportModalLabel">Customer Support</span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="opacity:0.8;font-size:0.75rem;"></button>
            </div>

            
            <div class="modal-body" style="padding:16px 18px;">
                <form id="ajaxSupportForm">
                    <input type="hidden" name="booking_id" id="supp_modal_booking_id" value="">
                    
                    
                    <div id="supp_modal_vehicle_badge" style="background:#f8fafc;border:1px solid #e2e8f0;padding:8px 12px;border-radius:8px;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-calendar-check" style="color:#d4a017;font-size:1rem;flex-shrink:0;"></i>
                        <div style="font-size:0.8rem;color:#334155;font-weight:600;line-height:1.2;" id="supp_modal_vehicle_text">
                            General Inquiry / Customer Assistance
                        </div>
                    </div>

                    
                    <div class="mb-3">
                        <label style="font-size:0.78rem;font-weight:700;color:#475569;margin-bottom:4px;display:block;">
                            Inquiry Category
                        </label>
                        <select class="form-select" name="subject" id="supp_modal_subject" style="border-radius:8px;border:1px solid #cbd5e1;padding:7px 10px;font-size:0.82rem;">
                            <option value="Booking Inquiry">Booking Inquiry / Assistance</option>
                            <option value="Reschedule Request">Reschedule Request</option>
                            <option value="Cancellation &amp; Refund">Cancellation &amp; Refund Help</option>
                            <option value="Warranty Claim">Warranty Claim / Film Question</option>
                            <option value="General Support">General Support</option>
                        </select>
                    </div>

                    
                    <div class="mb-3">
                        <label style="font-size:0.78rem;font-weight:700;color:#475569;margin-bottom:4px;display:block;">
                            Describe Your Question or Issue
                        </label>
                        <textarea class="form-control" name="message" id="supp_modal_message" rows="3" placeholder="Write your question or request here..." required style="border-radius:8px;border:1px solid #cbd5e1;padding:8px 10px;font-size:0.82rem;resize:none;"></textarea>
                    </div>

                    
                    <style>
                        .supp-priority-btn {
                            transition: all 0.2s ease !important;
                            user-select: none;
                        }
                        .supp-priority-btn.active-normal {
                            background: #0f172a !important;
                            color: #ffffff !important;
                            border-color: #0f172a !important;
                            box-shadow: 0 2px 6px rgba(15,23,42,0.25);
                        }
                        .supp-priority-btn.active-urgent {
                            background: #dc2626 !important;
                            color: #ffffff !important;
                            border-color: #dc2626 !important;
                            box-shadow: 0 2px 6px rgba(220,38,38,0.3);
                        }
                    </style>
                    <div class="mb-3" style="display:flex;align-items:center;justify-content:space-between;">
                        <span style="font-size:0.78rem;font-weight:600;color:#64748b;">Priority Level:</span>
                        <div style="display:flex;gap:6px;" id="suppPriorityWrap">
                            <label class="supp-priority-btn active-normal" style="cursor:pointer;font-size:0.75rem;padding:4px 12px;border-radius:6px;border:1px solid #cbd5e1;">
                                <input type="radio" name="priority" value="medium" checked style="display:none;" onchange="updateSuppPriority(this)"> Normal
                            </label>
                            <label class="supp-priority-btn" style="cursor:pointer;font-size:0.75rem;padding:4px 12px;border-radius:6px;border:1px solid #f87171;color:#dc2626;background:#fff5f5;">
                                <input type="radio" name="priority" value="high" style="display:none;" onchange="updateSuppPriority(this)"> Urgent
                            </label>
                        </div>
                    </div>

                    
                    <div style="display:flex;gap:8px;margin-top:14px;">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="flex:1;border-radius:8px;font-weight:600;padding:7px;font-size:0.82rem;">
                            Cancel
                        </button>
                        <button type="submit" id="supp_modal_submit_btn" class="btn" style="flex:2;background:linear-gradient(135deg,#0f172a,#1e293b);color:#fff;border:none;border-radius:8px;font-weight:700;padding:7px;font-size:0.82rem;box-shadow:0 3px 8px rgba(15,23,42,0.3);">
                            <i class="fas fa-paper-plane me-1" style="color:#fbbf24;"></i> Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>

:root {
    --nav-gold:        #D4A017;
    --nav-gold-light:  #F5C842;
    --nav-gold-pale:   rgba(212,160,23,.10);
    --nav-ink:         #0A0A0A;
    --nav-ink-mid:     #1E1E1E;
    --nav-white:       #0A0A0A;      
    --nav-font:        'DM Sans', sans-serif;
    --nav-font-brand:  'Bebas Neue', sans-serif;
    --nav-radius-sm:   6px;
    --nav-radius-md:   12px;
    --nav-radius-pill: 50px;
    --nav-h:           72px;
}

body { padding-top: var(--nav-h); }

#mainNavbar {
    height: var(--nav-h);
    background: rgba(245,243,239,.95) !important;
    backdrop-filter: blur(18px) saturate(1.6);
    -webkit-backdrop-filter: blur(18px) saturate(1.6);
    border-bottom: 1px solid rgba(0,0,0,.08);
    box-shadow: 0 4px 28px rgba(0,0,0,.06);
    transition: height .35s ease, background .35s ease, border-color .35s ease;
    z-index: 1030;
    font-family: var(--nav-font);
}

#mainNavbar.scrolled {
    height: 60px;
    background: rgba(255,255,255,.98) !important;
    border-bottom-color: rgba(0,0,0,.08);
    box-shadow: 0 6px 36px rgba(0,0,0,.08);
}

#mainNavbar .container {
    height: 100%;
    display: flex;
    align-items: center;
}

.navbar-brand {
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0;
    transition: transform .3s ease;
}
.navbar-brand:hover { transform: translateY(-2px); }

.nav-logo-icon {
    width: 38px; height: 38px;
    background: var(--nav-gold-pale);
    border: 1px solid rgba(212,160,23,.25);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: .65rem;
    transition: transform .4s ease, background .3s;
    flex-shrink: 0;
}
.navbar-brand:hover .nav-logo-icon {
    transform: rotate(18deg);
    background: rgba(212,160,23,.18);
}

.nav-brand-text {
    font-family: var(--nav-font-brand);
    font-size: 1.45rem;
    letter-spacing: .08em;
    color: var(--nav-white) !important;
    line-height: 1;
}

.nav-city-badge {
    font-size: .62rem;
    font-weight: 800;
    letter-spacing: .12em;
    background: var(--nav-gold);
    color: var(--nav-ink);
    padding: .18rem .55rem;
    border-radius: var(--nav-radius-pill);
    margin-left: .6rem;
    line-height: 1.6;
    align-self: center;
}

.nav-toggler-custom {
    background: none;
    border: 1.5px solid rgba(10,10,10,.18);
    border-radius: var(--nav-radius-sm);
    padding: .45rem .6rem;
    display: flex;
    flex-direction: column;
    gap: 4px;
    cursor: pointer;
    transition: border-color .25s;
}
.nav-toggler-custom:focus { box-shadow: none; outline: none; }
.nav-toggler-custom:hover { border-color: var(--nav-gold); }
.toggler-bar {
    display: block;
    width: 22px; height: 2px;
    background: rgba(10,10,10,.75);
    border-radius: 2px;
    transition: background .25s;
}
.nav-toggler-custom:hover .toggler-bar { background: var(--nav-gold); }

.navbar-nav { gap: .35rem; }

.nav-link-custom {
    display: flex;
    align-items: center;
    gap: .4rem;
    padding: .45rem .85rem !important;
    border-radius: var(--nav-radius-sm);
    color: rgba(10,10,10,.72) !important;
    font-size: .875rem;
    font-weight: 500;
    letter-spacing: .01em;
    text-decoration: none;
    position: relative;
    transition: color .25s, background .25s;
}
.nav-link-custom i {
    font-size: .8rem;
    opacity: .6;
    transition: opacity .25s;
}
.nav-underline {
    position: absolute;
    bottom: 4px; left: 50%;
    width: 0; height: 2px;
    background: var(--nav-gold);
    border-radius: 2px;
    transform: translateX(-50%);
    transition: width .3s cubic-bezier(.4,0,.2,1);
}

.nav-link-custom:hover,
.nav-link-custom.nav-active {
    color: var(--nav-ink) !important;
    background: rgba(10,10,10,.05);
}
.nav-link-custom:hover i,
.nav-link-custom.nav-active i { opacity: 1; }
.nav-link-custom:hover .nav-underline,
.nav-link-custom.nav-active .nav-underline { width: calc(100% - 1.7rem); }

.nav-link-custom.nav-book {
    color: var(--nav-gold) !important;
    border: 1.5px solid rgba(212,160,23,.28);
    padding: .42rem 1rem !important;
}
.nav-link-custom.nav-book:hover,
.nav-link-custom.nav-book.nav-active {
    background: var(--nav-gold-pale);
    border-color: var(--nav-gold);
    color: var(--nav-gold-light) !important;
}
.nav-link-custom.nav-book .nav-underline { background: var(--nav-gold-light); }

.nav-link-custom.dropdown-toggle::after {
    margin-left: .3rem;
    border-top-color: rgba(255,255,255,.4);
}

.nav-dropdown-menu {
    background: #000000;
    border: 1px solid rgba(212,160,23,.18);
    border-radius: var(--nav-radius-md);
    box-shadow: 0 16px 50px rgba(0,0,0,.55);
    min-width: 220px;
    margin-top: 10px !important;
    padding: .4rem;
    animation: navDropFade .2s ease;
}
@keyframes navDropFade {
    from { opacity:0; transform:translateY(-8px); }
    to   { opacity:1; transform:translateY(0); }
}

.nav-dropdown-item {
    display: flex;
    align-items: center;
    gap: .7rem;
    padding: .65rem .85rem;
    border-radius: var(--nav-radius-sm);
    color: rgba(255,255,255,.7) !important;
    font-size: .875rem;
    font-weight: 500;
    transition: background .2s, color .2s, transform .2s;
    text-decoration: none;
}
.nav-dropdown-item i { width: 16px; text-align: center; flex-shrink: 0; }
.nav-dropdown-item:hover {
    background: rgba(212,160,23,.1);
    color: var(--nav-white) !important;
    transform: translateX(4px);
}
.nav-dropdown-item.nav-signout {
    color: rgba(255,100,100,.75) !important;
}
.nav-dropdown-item.nav-signout:hover {
    background: rgba(220,53,69,.12);
    color: #ff6b6b !important;
}
.nav-dropdown-divider {
    border-color: rgba(255,255,255,.07);
    margin: .3rem .5rem;
}

.nav-dropdown-user-header {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .85rem .85rem .6rem;
}
.nav-dropdown-avatar {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--nav-gold), #F5A623);
    display: flex; align-items: center; justify-content: center;
    color: var(--nav-ink);
    font-weight: 800;
    font-size: .85rem;
    flex-shrink: 0;
    border: 2px solid rgba(212,160,23,.35);
}
.nav-dropdown-name {
    font-size: .88rem;
    font-weight: 700;
    color: var(--nav-white);
    line-height: 1.2;
}
.nav-dropdown-role {
    font-size: .72rem;
    color: var(--nav-gold);
    letter-spacing: .06em;
    text-transform: uppercase;
    font-weight: 600;
}

.nav-icon-btn {
    background: none;
    border: 1.5px solid rgba(255,255,255,.14);
    border-radius: var(--nav-radius-sm);
    color: rgba(255,255,255,.65);
    width: 40px; height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .95rem;
    cursor: pointer;
    transition: all .25s;
    position: relative;
}
.nav-icon-btn:hover, .nav-icon-btn:focus {
    border-color: var(--nav-gold);
    color: var(--nav-gold);
    background: var(--nav-gold-pale);
    box-shadow: none;
    outline: none;
}
.nav-icon-btn::after { display: none; } 

.nav-user-btn {
    display: flex;
    align-items: center;
    gap: .6rem;
    background: rgba(255,255,255,.05);
    border: 1.5px solid rgba(255,255,255,.14);
    border-radius: var(--nav-radius-pill);
    padding: .32rem .9rem .32rem .32rem;
    color: rgba(255,255,255,.78);
    font-size: .875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all .25s;
    font-family: var(--nav-font);
}
.nav-user-btn:hover, .nav-user-btn:focus {
    border-color: var(--nav-gold);
    background: var(--nav-gold-pale);
    color: var(--nav-white);
    box-shadow: none;
    outline: none;
}
.nav-user-btn::after { border-top-color: rgba(255,255,255,.4); margin-left: .25rem; }
.nav-username { font-family: var(--nav-font); font-weight: 600; }

.nav-avatar {
    width: 30px; height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--nav-gold), #F5A623);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.avatar-initials {
    font-size: .72rem;
    font-weight: 800;
    color: var(--nav-ink);
    line-height: 1;
}

.nav-signin-btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    background: var(--nav-gold);
    border: 1.5px solid var(--nav-gold);
    border-radius: var(--nav-radius-pill);
    color: var(--nav-ink);
    padding: .42rem 1.2rem;
    font-size: .855rem;
    font-weight: 700;
    text-decoration: none;
    transition: all .25s;
    font-family: var(--nav-font);
    box-shadow: 0 4px 18px rgba(212,160,23,.3);
    animation: signinPop 2.8s ease-in-out infinite;
}
.nav-signin-btn:hover {
    background: var(--nav-gold-light);
    border-color: var(--nav-gold-light);
    color: var(--nav-ink);
    transform: translateY(-2px) scale(1.04);
    box-shadow: 0 6px 24px rgba(212,160,23,.45);
    animation: none;
}

@keyframes signinPop {
    0%, 100% { transform: scale(1); box-shadow: 0 4px 18px rgba(212,160,23,.3); }
    50%      { transform: scale(1.06); box-shadow: 0 6px 24px rgba(212,160,23,.5); }
}

.signin-modal-dialog { max-width: 900px; }
.signin-modal-content {
    background: #FFFFFF;
    border: 1px solid #E6E2DA;
    border-radius: 20px;
    box-shadow: 0 30px 80px rgba(0,0,0,.15), 0 0 100px rgba(212,160,23,.03);
    overflow: hidden;
    position: relative;
}
.signin-modal-close {
    position: absolute;
    top: 16px; right: 16px;
    width: 34px; height: 34px;
    border-radius: 50%;
    border: 1px solid #E6E2DA;
    background: #F5F3EF;
    color: #0A0A0A;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    cursor: pointer;
    transition: all .2s;
    z-index: 10;
}
.signin-modal-close:hover { background: rgba(220,38,38,.1); color: #ef4444; border-color: rgba(220,38,38,.2); }

.signin-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 560px;
}

.signin-left {
    background: #F5F3EF;
    padding: 40px 36px 28px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    border-right: 1px solid #E6E2DA;
    position: relative;
    overflow: hidden;
}
.signin-left::before {
    content: '';
    position: absolute;
    top: -120px; right: -120px;
    width: 300px; height: 300px;
    background: radial-gradient(circle, rgba(212,160,23,.12) 0%, transparent 70%);
    pointer-events: none;
}
.signin-left-inner { position: relative; z-index: 1; }

.signin-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 32px;
}
.signin-logo-badge {
    width: 44px; height: 44px;
    background: var(--nav-gold);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    color: #FFFFFF;
    flex-shrink: 0;
}
.signin-brand-rg {
    display: block;
    font-family: 'Syne', 'Bebas Neue', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--nav-gold);
    line-height: 1.1;
    letter-spacing: .02em;
}
.signin-brand-sub {
    display: block;
    font-size: .7rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .12em;
}

.signin-headline {
    font-family: 'Syne', 'Bebas Neue', sans-serif;
    font-size: 1.75rem;
    font-weight: 800;
    color: #0A0A0A;
    line-height: 1.15;
    margin-bottom: 14px;
}
.signin-headline span {
    color: var(--nav-gold);
    font-style: italic;
}

.signin-left-desc {
    font-size: .85rem;
    color: #64748b;
    line-height: 1.5;
    margin-bottom: 28px;
}

.signin-features {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.signin-pill {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: #FFFFFF;
    border: 1px solid #E6E2DA;
    border-radius: 10px;
    font-size: .78rem;
    color: #0A0A0A;
    transition: border-color .25s, background .25s;
}
.signin-pill:hover {
    border-color: var(--nav-gold);
    background: var(--nav-gold-pale);
}
.signin-pill i {
    font-size: .85rem;
    color: var(--nav-gold);
    opacity: .9;
    width: 16px;
    text-align: center;
    flex-shrink: 0;
}

.signin-left-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: .78rem;
    font-weight: 600;
    color: var(--nav-gold);
    background: #FFF8DC;
    border: 1px solid rgba(212,160,23,.3);
    border-radius: 50px;
    padding: 8px 16px;
    margin-top: 24px;
}
.signin-left-badge i { font-size: .75rem; }

.signin-right {
    padding: 40px 36px 32px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    background: #FFFFFF;
}

.signin-form-eyebrow {
    font-size: .68rem;
    font-weight: 800;
    color: var(--nav-gold);
    text-transform: uppercase;
    letter-spacing: .14em;
    margin-bottom: 8px;
}
.signin-welcome {
    font-family: 'Syne', 'Bebas Neue', sans-serif;
    font-size: 1.65rem;
    font-weight: 800;
    color: #0A0A0A;
    line-height: 1.1;
    margin: 0 0 6px;
}
.signin-welcome-sub {
    font-size: .85rem;
    color: #64748b;
    margin: 0 0 24px;
}

.signin-error-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(220,38,38,.1);
    border: 1px solid rgba(220,38,38,.25);
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 16px;
    font-size: .82rem;
    color: #ef4444;
}

.signin-field { margin-bottom: 16px; }
.signin-field label {
    display: block;
    font-size: .72rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 6px;
}
.signin-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.signin-input-icon {
    position: absolute;
    left: 14px;
    font-size: .82rem;
    color: #94a3b8;
    pointer-events: none;
    z-index: 1;
}
.signin-field input {
    width: 100%;
    padding: 12px 14px 12px 40px;
    background: #F5F3EF;
    border: 1.5px solid #E6E2DA;
    border-radius: 10px;
    color: #0A0A0A;
    font-size: .88rem;
    font-family: var(--nav-font);
    transition: all .25s;
}
.signin-field input::placeholder { color: #94a3b8; }
.signin-field input:focus {
    outline: none;
    border-color: var(--nav-gold);
    background: #FFFFFF;
    box-shadow: 0 0 0 3px rgba(212,160,23,.12);
}

.signin-pw-toggle {
    position: absolute;
    right: 12px;
    background: none; border: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 14px;
    padding: 4px;
    transition: color .2s;
    z-index: 1;
}
.signin-pw-toggle:hover { color: var(--nav-gold); }

.signin-submit-btn {
    width: 100%;
    padding: 13px;
    background: linear-gradient(135deg, var(--nav-gold), var(--nav-gold-light));
    border: none;
    border-radius: 12px;
    color: #0A0A0A;
    font-size: .92rem;
    font-weight: 700;
    font-family: var(--nav-font);
    cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: all .25s;
    box-shadow: 0 4px 18px rgba(212,160,23,.3);
    margin-top: 6px;
}
.signin-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(212,160,23,.45);
}
.signin-submit-btn:active { transform: translateY(0); }

.signin-links {
    text-align: center;
    margin-top: 18px;
    font-size: .82rem;
}
.signin-links p { color: #64748b; margin: 0 0 8px; }
.signin-links a { color: var(--nav-gold); text-decoration: none; transition: color .2s; }
.signin-links a:hover { color: var(--nav-gold-light); }
.signin-forgot {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .78rem;
    color: #64748b;
    text-decoration: none;
    transition: color .2s;
}
.signin-forgot:hover { color: var(--nav-gold); }

.signin-divider {
    display: flex; align-items: center; gap: 12px;
    margin: 16px 0;
}
.signin-divider::before, .signin-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #E6E2DA;
}
.signin-divider span {
    font-size: .73rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .08em;
}

.signin-social {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.signin-social-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 11px;
    background: #F5F3EF;
    border: 1.5px solid #E6E2DA;
    border-radius: 10px;
    color: #0A0A0A;
    font-size: .82rem;
    font-weight: 600;
    font-family: var(--nav-font);
    cursor: pointer;
    transition: all .2s;
}
.signin-social-btn:hover {
    background: #E6E2DA;
    border-color: #d1cbc0;
    color: #0A0A0A;
}

#signInModal .modal-dialog {
    transform: translateY(30px) scale(.95);
    transition: transform .35s cubic-bezier(.4,0,.2,1), opacity .35s;
}
#signInModal.show .modal-dialog {
    transform: translateY(0) scale(1);
}

@media (max-width: 768px) {
    .signin-modal-dialog { max-width: 95vw; margin: 16px auto; }
    .signin-split { grid-template-columns: 1fr; }
    .signin-left { display: none; }
    .signin-right { padding: 32px 24px 28px; }
}
@media (max-width: 480px) {
    .signin-right { padding: 28px 18px 24px; }
    .signin-social { grid-template-columns: 1fr; }
}

.reg-modal-dialog { max-width: 920px; }
.reg-modal-content {
    background: #FFFFFF;
    border: 1px solid #E6E2DA;
    border-radius: 20px;
    box-shadow: 0 30px 80px rgba(0,0,0,.15), 0 0 100px rgba(212,160,23,.03);
    overflow: hidden;
    position: relative;
}
.reg-modal-close {
    position: absolute;
    top: 16px; right: 16px;
    width: 34px; height: 34px;
    border-radius: 50%;
    border: 1px solid #E6E2DA;
    background: #F5F3EF;
    color: #0A0A0A;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    cursor: pointer;
    transition: all .2s;
    z-index: 10;
}
.reg-modal-close:hover { background: rgba(220,38,38,.1); color: #ef4444; border-color: rgba(220,38,38,.2); }

.reg-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 600px;
}

.reg-left {
    background: #F5F3EF;
    padding: 40px 36px 28px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    border-right: 1px solid #E6E2DA;
    position: relative;
    overflow: hidden;
}
.reg-left::before {
    content: '';
    position: absolute;
    top: -120px; right: -120px;
    width: 300px; height: 300px;
    background: radial-gradient(circle, rgba(212,160,23,.12) 0%, transparent 70%);
    pointer-events: none;
}
.reg-left-inner { position: relative; z-index: 1; }

.reg-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 32px;
}
.reg-logo-badge {
    width: 44px; height: 44px;
    background: var(--nav-gold);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    color: #FFFFFF;
    flex-shrink: 0;
}
.reg-brand-rg {
    display: block;
    font-family: 'Syne', 'Bebas Neue', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--nav-gold);
    line-height: 1.1;
    letter-spacing: .02em;
}
.reg-brand-sub {
    display: block;
    font-size: .7rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .12em;
}

.reg-headline {
    font-family: 'Syne', 'Bebas Neue', sans-serif;
    font-size: 1.75rem;
    font-weight: 800;
    color: #0A0A0A;
    line-height: 1.15;
    margin-bottom: 14px;
}
.reg-headline span { color: var(--nav-gold); font-style: italic; }

.reg-left-desc {
    font-size: .85rem;
    color: #64748b;
    line-height: 1.5;
    margin-bottom: 28px;
}

.reg-features {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.reg-pill {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: #FFFFFF;
    border: 1px solid #E6E2DA;
    border-radius: 10px;
    font-size: .78rem;
    color: #0A0A0A;
    transition: border-color .25s, background .25s;
}
.reg-pill:hover {
    border-color: var(--nav-gold);
    background: var(--nav-gold-pale);
}
.reg-pill i {
    font-size: .85rem;
    color: var(--nav-gold);
    opacity: .9;
    width: 16px;
    text-align: center;
    flex-shrink: 0;
}
.reg-pill-full { grid-column: 1 / -1; }

.reg-left-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: .78rem;
    font-weight: 600;
    color: var(--nav-gold);
    background: #FFF8DC;
    border: 1px solid rgba(212,160,23,.3);
    border-radius: 50px;
    padding: 8px 16px;
    margin-top: 24px;
}
.reg-left-badge i { font-size: .75rem; }

.reg-right {
    padding: 36px 36px 28px;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    max-height: 90vh;
    background: #FFFFFF;
}

.reg-form-eyebrow {
    font-size: .68rem;
    font-weight: 800;
    color: var(--nav-gold);
    text-transform: uppercase;
    letter-spacing: .14em;
    margin-bottom: 6px;
}
.reg-welcome {
    font-family: 'Syne', 'Bebas Neue', sans-serif;
    font-size: 1.55rem;
    font-weight: 800;
    color: #0A0A0A;
    line-height: 1.1;
    margin: 0 0 4px;
}
.reg-welcome-sub {
    font-size: .83rem;
    color: #64748b;
    margin: 0 0 20px;
}

.reg-error-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(220,38,38,.1);
    border: 1px solid rgba(220,38,38,.25);
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 14px;
    font-size: .82rem;
    color: #ef4444;
}

.reg-field { margin-bottom: 14px; }
.reg-field label {
    display: block;
    font-size: .72rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 5px;
}
.reg-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.reg-input-icon {
    position: absolute;
    left: 14px;
    font-size: .82rem;
    color: #94a3b8;
    pointer-events: none;
    z-index: 1;
}
.reg-field input {
    width: 100%;
    padding: 12px 14px 12px 40px;
    background: #F5F3EF;
    border: 1.5px solid #E6E2DA;
    border-radius: 10px;
    color: #0A0A0A;
    font-size: .88rem;
    font-family: var(--nav-font);
    transition: all .25s;
}
.reg-field input::placeholder { color: #94a3b8; }
.reg-field input:focus {
    outline: none;
    border-color: var(--nav-gold);
    background: #FFFFFF;
    box-shadow: 0 0 0 3px rgba(212,160,23,.12);
}

.reg-pw-toggle {
    position: absolute;
    right: 12px;
    background: none; border: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 14px;
    padding: 4px;
    transition: color .2s;
    z-index: 1;
}
.reg-pw-toggle:hover { color: var(--nav-gold); }

.reg-strength-track {
    height: 3px;
    background: #E6E2DA;
    border-radius: 2px;
    margin-top: 8px;
    overflow: hidden;
}
.reg-strength-bar {
    height: 100%; width: 0;
    border-radius: 2px;
    transition: all .35s ease;
}
.reg-strength-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 4px;
}
.reg-strength-text {
    font-size: .73rem;
    font-weight: 600;
    color: #94a3b8;
}

.reg-pw-requirements {
    list-style: none;
    padding: 0;
    margin: 8px 0 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3px 10px;
}
.reg-pw-requirements li {
    font-size: .73rem;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: color .2s;
}
.reg-req-dot {
    width: 5px; height: 5px;
    border-radius: 50%;
    background: #E6E2DA;
    flex-shrink: 0;
    transition: background .2s;
}
.reg-pw-requirements li.valid { color: #4ADE80; }
.reg-pw-requirements li.valid .reg-req-dot { background: #4ADE80; }

.reg-terms {
    background: #F5F3EF;
    border: 1px solid #E6E2DA;
    border-radius: 10px;
    padding: 12px 14px;
    margin: 14px 0 4px;
}
.reg-terms .form-check-label {
    font-size: .8rem;
    color: #64748b;
}
.reg-terms a {
    color: var(--nav-gold);
    font-weight: 600;
    text-decoration: none;
}
.reg-terms a:hover { color: var(--nav-gold-light); }
.reg-terms .form-check-input {
    background-color: #FFFFFF;
    border: 1.5px solid #E6E2DA;
}
.reg-terms .form-check-input:checked {
    background-color: var(--nav-gold);
    border-color: var(--nav-gold);
}

.reg-submit-btn {
    width: 100%;
    padding: 13px;
    background: linear-gradient(135deg, var(--nav-gold), var(--nav-gold-light));
    border: none;
    border-radius: 12px;
    color: #0A0A0A;
    font-size: .92rem;
    font-weight: 700;
    font-family: var(--nav-font);
    cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: all .25s;
    box-shadow: 0 4px 18px rgba(212,160,23,.3);
    margin-top: 6px;
}
.reg-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(212,160,23,.45);
}
.reg-submit-btn:active { transform: translateY(0); }
.reg-submit-btn:disabled { opacity: .7; cursor: not-allowed; transform: none; }

.reg-links {
    text-align: center;
    margin-top: 16px;
    font-size: .82rem;
}
.reg-links p { color: #64748b; margin: 0; }
.reg-links a { color: var(--nav-gold); text-decoration: none; transition: color .2s; }
.reg-links a:hover { color: var(--nav-gold-light); }

#registerModal .modal-dialog {
    transform: translateY(30px) scale(.95);
    transition: transform .35s cubic-bezier(.4,0,.2,1), opacity .35s;
}
#registerModal.show .modal-dialog {
    transform: translateY(0) scale(1);
}

@media (max-width: 768px) {
    .reg-modal-dialog { max-width: 95vw; margin: 16px auto; }
    .reg-split { grid-template-columns: 1fr; }
    .reg-left { display: none; }
    .reg-right { padding: 32px 24px 28px; }
}
@media (max-width: 480px) {
    .reg-right { padding: 28px 18px 24px; }
    .reg-pw-requirements { grid-template-columns: 1fr; }
}

.notification-badge {
    position: absolute;
    top: -6px; right: -8px;
    background: #e53e3e;
    color: var(--nav-white);
    border-radius: 50%;
    width: 18px; height: 18px;
    font-size: .65rem;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700;
    border: 2px solid var(--nav-ink);
}

.notif-dropdown {
    width: 340px;
    max-height: 420px;
    overflow-y: auto;
    border-radius: var(--nav-radius-md);
    background: #000000 !important;
    border: 1px solid rgba(212,160,23,.18);
    box-shadow: 0 16px 50px rgba(0,0,0,.55);
}
.notif-dropdown::-webkit-scrollbar { width: 4px; }
.notif-dropdown::-webkit-scrollbar-track { background: #000; }
.notif-dropdown::-webkit-scrollbar-thumb { background: var(--nav-gold); border-radius: 4px; }

.notif-header {
    padding: .85rem 1rem;
    border-bottom: 1px solid rgba(255,255,255,.07);
    background: rgba(255,255,255,.03);
}
.notif-header h6 { color: var(--nav-white); font-size: .88rem; }
.notif-view-all {
    font-size: .78rem;
    color: var(--nav-gold);
    text-decoration: none;
    font-weight: 600;
}
.notif-view-all:hover { color: var(--nav-gold-light); }

.notif-item { border-bottom: 1px solid rgba(255,255,255,.05); }
.notif-item.unread { background: rgba(212,160,23,.04); }
.notif-icon {
    width: 34px; height: 34px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin-right: 10px;
    flex-shrink: 0;
}

.navbar-actions {
    display: flex;
    align-items: center;
    gap: .65rem;
}

@media (max-width: 992px) {
    body { padding-top: 62px; }
    :root { --nav-h: 62px; }

    .navbar-collapse {
        background: rgba(8,8,8,.98);
        border: 1px solid rgba(212,160,23,.14);
        border-top: none;
        border-radius: 0 0 14px 14px;
        padding: .75rem 1rem 1rem;
        box-shadow: 0 16px 40px rgba(0,0,0,.5);
        backdrop-filter: blur(18px);
    }
    .navbar-nav { gap: .1rem; }
    .nav-link-custom {
        padding: .7rem 1rem !important;
        border-radius: var(--nav-radius-sm);
    }
    .nav-underline { display: none; }
    .nav-link-custom.nav-active {
        background: var(--nav-gold-pale);
        color: var(--nav-gold-light) !important;
    }
    .nav-link-custom.nav-active i { opacity: 1; color: var(--nav-gold); }

    .navbar-actions {
        margin-top: .85rem;
        padding-top: .85rem;
        border-top: 1px solid rgba(255,255,255,.07);
        width: 100%;
        justify-content: center;
        flex-wrap: wrap;
    }

    
    .nav-dropdown-menu {
        position: static !important;
        transform: none !important;
        border: none;
        background: rgba(255,255,255,.04);
        box-shadow: none;
        margin-top: .3rem !important;
        padding: .2rem;
        border-radius: var(--nav-radius-sm);
    }
    .nav-dropdown-item {
        color: rgba(255,255,255,.65) !important;
        background: rgba(255,255,255,.03);
        margin: .15rem 0;
    }
    .nav-dropdown-user-header { padding: .6rem .7rem; }
    .notif-dropdown { width: 300px; }

    
    .nav-brand-text { display: none; }
    .nav-city-badge { display: none; }
}

@media (max-width: 576px) {
    .nav-signin-btn span { display: none; }
    .nav-signin-btn {
        width: 40px; height: 40px;
        padding: 0;
        justify-content: center;
        border-radius: 50%;
    }
    .notif-dropdown { width: 90vw; right: 5vw !important; left: auto !important; }
}
</style>

<script>

window.addEventListener('scroll', function () {
    const navbar = document.getElementById('mainNavbar');
    if (!navbar) return;
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const navLinks = document.querySelectorAll('.nav-link-custom');

    navLinks.forEach(function (link) {
        const linkHref = link.getAttribute('href');
        if (!linkHref) return;
        if (
            linkHref === currentPage ||
            (currentPage === '' && linkHref === 'index.php') ||
            (currentPage === 'index.php' && linkHref === 'index.php') ||
            (linkHref !== 'index.php' && currentPage.includes(linkHref.replace('.php', '')))
        ) {
            link.classList.add('nav-active');
        }

        
        const underline = link.querySelector('.nav-underline');
        if (!underline) return;
        link.addEventListener('mouseenter', function () {
            underline.style.width = 'calc(100% - 1.7rem)';
        });
        link.addEventListener('mouseleave', function () {
            if (!link.classList.contains('nav-active')) {
                underline.style.width = '0';
            }
        });
    });
});
</script>

<?php if (isset($_SESSION['user_id'])): ?>
<script>

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, function (s) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[s]);
    });
}

function notifIcon(type) {
    if (type === 'success') return { bg: 'bg-success', icon: 'fa-check' };
    if (type === 'warning') return { bg: 'bg-warning', icon: 'fa-exclamation' };
    if (type === 'danger')  return { bg: 'bg-danger',  icon: 'fa-times' };
    return { bg: 'bg-primary', icon: 'fa-info-circle' };
}

let lastCustUnread = null;
let knownCustNotifIds = new Set();

function showCustToast(title, msg, type) {
    let wrap = document.getElementById('custToastWrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'custToastWrap';
        wrap.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;max-width:340px;pointer-events:none;';
        document.body.appendChild(wrap);
    }
    const toast = document.createElement('div');
    toast.style.cssText = 'background:#1a1a1a;color:#fff;border-left:4px solid #D4A017;padding:12px 16px;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,0.5);pointer-events:auto;animation:fadeInUp 0.3s ease;font-size:13px;';
    toast.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
            <strong style="color:#F5C842;font-size:13px;"><i class="fas fa-bell me-1"></i> ${escapeHtml(title)}</strong>
            <button type="button" style="background:none;border:none;color:#aaa;font-size:16px;line-height:1;cursor:pointer;" onclick="this.parentElement.parentElement.remove()">&times;</button>
        </div>
        <div style="color:rgba(255,255,255,0.8);font-size:12px;">${escapeHtml(msg)}</div>
    `;
    wrap.appendChild(toast);
    setTimeout(() => { if (toast.parentNode) toast.remove(); }, 6000);
}

async function pollNavNotifications() {
    try {
        const res = await fetch(BASE_URL + "/api/notifications.php", { cache: 'no-store' });
        if (!res.ok) return;
        const data = await res.json();
        if (!data || data.success === false) return;

        const unread = data.unread || 0;
        const notifications = data.notifications || [];

        // Trigger live Toast popup if new unread notification received
        if (lastCustUnread !== null && unread > lastCustUnread) {
            notifications.forEach(n => {
                if (parseInt(n.is_read) === 0 && !knownCustNotifIds.has(parseInt(n.id))) {
                    showCustToast(n.title, n.message, n.type || 'info');
                }
            });
        }

        notifications.forEach(n => knownCustNotifIds.add(parseInt(n.id)));

        // Update badge
        const badgeWrap = document.getElementById('navNotifBadgeWrap');
        if (badgeWrap) {
            badgeWrap.innerHTML = (unread > 0)
                ? `<span class="notification-badge">${unread > 9 ? '9+' : unread}</span>`
                : '';
        }

        // Update list
        const listEl = document.getElementById('navNotifList');
        if (!listEl) return;

        if (notifications.length === 0) {
            listEl.innerHTML = `<div class="p-3 text-center text-muted"><small>No notifications</small></div>`;
            return;
        }

        listEl.innerHTML = notifications.map(function (n) {
            const meta = notifIcon(n.type);
            const unreadClass = (parseInt(n.is_read) === 0) ? 'unread' : '';
            const timeAgo = n.time_ago || 'Just now';
            return `
                <div class="notif-item ${unreadClass} px-3 py-2" data-id="${n.id}" style="cursor:pointer;">
                    <div class="d-flex align-items-start">
                        <div class="notif-icon text-white ${meta.bg}">
                            <i class="fas ${meta.icon} fa-sm"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <strong style="font-size:.82rem;color:#fff;">${escapeHtml(n.title)}</strong>
                                <small style="color:rgba(255,255,255,.4);font-size:.7rem;white-space:nowrap;margin-left:.5rem;">${timeAgo}</small>
                            </div>
                            <div style="font-size:.8rem;color:rgba(255,255,255,.6);margin-top:.1rem;">${escapeHtml(n.message)}</div>
                        </div>
                    </div>
                </div>`;
        }).join('');

        lastCustUnread = unread;

        // Auto open review modal if completed booking needs review
        if (data.pending_review) {
            const pId = data.pending_review.id;
            if (!sessionStorage.getItem('review_dismissed_' + pId)) {
                setTimeout(() => {
                    openCustomerReviewModal(data.pending_review);
                }, 1000);
            }
        }
    } catch (e) {
        // Fail silently
    }
}

// Function to open the Customer Review Popup Modal
window.openCustomerReviewModal = function(data) {
    if (!data) return;
    const bookingId = data.id || data.booking_id;
    const vehicle = data.vehicle_model || 'Vehicle';
    const pkg = data.package_name || 'Tint Installation';
    
    const idEl = document.getElementById('rev_modal_booking_id');
    if (idEl) idEl.value = bookingId || '';
    
    const textEl = document.getElementById('rev_modal_vehicle_text');
    if (textEl) textEl.innerHTML = `<strong>Booking #${bookingId}:</strong> ${escapeHtml(vehicle)} &bull; ${escapeHtml(pkg)}`;
    
    setRatingStars(5);
    const commentEl = document.getElementById('rev_modal_comment');
    if (commentEl) commentEl.value = '';
    const recEl = document.getElementById('rev_modal_recommend');
    if (recEl) recEl.checked = true;

    const modalEl = document.getElementById('customerReviewModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
};

function setRatingStars(rating) {
    const valEl = document.getElementById('rev_modal_rating_val');
    if (valEl) valEl.value = rating;
    
    const labels = {
        1: "1.0 Star — Poor",
        2: "2.0 Stars — Fair",
        3: "3.0 Stars — Good",
        4: "4.0 Stars — Very Good",
        5: "5.0 Stars — Excellent!"
    };
    const labelEl = document.getElementById('rev_modal_rating_label');
    if (labelEl) labelEl.textContent = labels[rating] || (rating + " Stars");

    document.querySelectorAll('.star-rating-select .star-btn').forEach(star => {
        const val = parseInt(star.dataset.value);
        if (val <= rating) {
            star.style.color = '#d4a017';
        } else {
            star.style.color = '#cbd5e1';
        }
    });
}

document.querySelectorAll('.star-rating-select .star-btn').forEach(star => {
    star.addEventListener('click', function() {
        setRatingStars(parseInt(this.dataset.value));
    });
});

const ajaxRevForm = document.getElementById('ajaxReviewForm');
if (ajaxRevForm) {
    ajaxRevForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('rev_modal_submit_btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i> Submitting...`;

        const bookingId = document.getElementById('rev_modal_booking_id').value;
        const rating = document.getElementById('rev_modal_rating_val').value;
        const comment = document.getElementById('rev_modal_comment').value;
        const recommend = document.getElementById('rev_modal_recommend').checked ? 1 : 0;

        try {
            const res = await fetch(BASE_URL + "/api/submit_review.php", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    booking_id: bookingId,
                    rating: rating,
                    comment: comment,
                    would_recommend: recommend
                })
            });
            const data = await res.json();
            if (data.success) {
                const modalEl = document.getElementById('customerReviewModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                if (bookingId) {
                    sessionStorage.setItem('review_dismissed_' + bookingId, '1');
                }

                showCustToast('Review Submitted', data.message, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showCustToast('Error', data.error || 'Failed to submit review', 'danger');
            }
        } catch (err) {
            showCustToast('Error', 'Network error submitting review', 'danger');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = `<i class="fas fa-paper-plane me-1"></i> Submit Review`;
        }
    });
}

// Function to open the Customer Support Popup Modal
window.openCustomerSupportModal = function(data) {
    const bookingId = (data && (data.id || data.booking_id)) ? (data.id || data.booking_id) : 0;
    const vehicle = (data && data.vehicle_model) ? data.vehicle_model : '';
    
    const idEl = document.getElementById('supp_modal_booking_id');
    if (idEl) idEl.value = bookingId || '';
    
    const textEl = document.getElementById('supp_modal_vehicle_text');
    if (textEl) {
        textEl.innerHTML = bookingId 
            ? `<strong>Booking #${bookingId}:</strong> ${escapeHtml(vehicle || 'Vehicle Appointment')}`
            : `<strong>General Customer Assistance</strong>`;
    }
    
    const msgEl = document.getElementById('supp_modal_message');
    if (msgEl) msgEl.value = '';
    
    const modalEl = document.getElementById('customerSupportModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
};

const ajaxSuppForm = document.getElementById('ajaxSupportForm');
if (ajaxSuppForm) {
    ajaxSuppForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('supp_modal_submit_btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i> Sending...`;

        const bookingId = document.getElementById('supp_modal_booking_id').value;
        const subject = document.getElementById('supp_modal_subject').value;
        const message = document.getElementById('supp_modal_message').value;
        const priority = document.querySelector('input[name="priority"]:checked')?.value || 'medium';

        try {
            const res = await fetch(BASE_URL + "/api/submit_support.php", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    booking_id: bookingId,
                    subject: subject,
                    message: message,
                    priority: priority
                })
            });
            const data = await res.json();
            if (data.success) {
                const modalEl = document.getElementById('customerSupportModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                showCustToast('Support Message Sent', data.message, 'success');
            } else {
                showCustToast('Error', data.error || 'Failed to send message', 'danger');
            }
        } catch (err) {
            showCustToast('Error', 'Network error sending support message', 'danger');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = `<i class="fas fa-paper-plane me-1" style="color:#fbbf24;"></i> Send Message`;
        }
    });
}

// Event Listeners for Customer Notifications
document.addEventListener('click', function(e) {
    const row = e.target.closest('.notif-item');
    const markAllBtn = e.target.closest('#custMarkAllReadBtn');

    if (markAllBtn) {
        e.preventDefault();
        fetch(BASE_URL + "/api/notifications.php", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_all_read' })
        }).then(() => pollNavNotifications());
        return;
    }

    if (row && row.classList.contains('unread')) {
        const id = row.dataset.id;
        row.classList.remove('unread');
        fetch(BASE_URL + "/api/notifications.php", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_read', id: parseInt(id) })
        }).then(() => pollNavNotifications());
    }
});

pollNavNotifications();
setInterval(pollNavNotifications, 5000);
</script>
<?php endif; ?>

<script>

function toggleSigninPw() {
    const inp = document.getElementById('signinPassword');
    const icon = document.getElementById('signinEyeIcon');
    if (!inp || !icon) return;
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        inp.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

const signInModal = document.getElementById('signInModal');
if (signInModal) {
    signInModal.addEventListener('shown.bs.modal', function() {
        this.querySelector('input[name="email"]').focus();
    });
}

function toggleRegPw() {
    const inp = document.getElementById('regPassword');
    const icon = document.getElementById('regEyeIcon');
    if (!inp || !icon) return;
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        inp.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

const registerModal = document.getElementById('registerModal');
if (registerModal) {
    registerModal.addEventListener('shown.bs.modal', function() {
        const nameInput = this.querySelector('input[name="name"]');
        if (nameInput) nameInput.focus();
    });
}

function switchToRegister(e) {
    e.preventDefault();
    const siModal = bootstrap.Modal.getInstance(document.getElementById('signInModal'));
    if (siModal) siModal.hide();
    
    document.getElementById('signInModal').addEventListener('hidden.bs.modal', function handler() {
        this.removeEventListener('hidden.bs.modal', handler);
        new bootstrap.Modal(document.getElementById('registerModal')).show();
    });
}

function switchToSignIn(e) {
    e.preventDefault();
    const regModal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
    if (regModal) regModal.hide();
    document.getElementById('registerModal').addEventListener('hidden.bs.modal', function handler() {
        this.removeEventListener('hidden.bs.modal', handler);
        new bootstrap.Modal(document.getElementById('signInModal')).show();
    });
}

(function() {
    const pw = document.getElementById('regPassword');
    if (!pw) return;

    pw.addEventListener('input', function() {
        const val = this.value;
        const hasLen    = val.length >= 8;
        const hasUpper  = /[A-Z]/.test(val);
        const hasLower  = /[a-z]/.test(val);
        const hasNum    = /[0-9]/.test(val);
        const hasSpec   = /[^A-Za-z0-9]/.test(val);

        toggleReqClass('regReqLength', hasLen);
        toggleReqClass('regReqUpper',  hasUpper);
        toggleReqClass('regReqLower',  hasLower);
        toggleReqClass('regReqNumber', hasNum);
        toggleReqClass('regReqSpecial', hasSpec);

        const score = [hasLen, hasUpper, hasLower, hasNum, hasSpec].filter(Boolean).length;
        const bar  = document.getElementById('regStrengthBar');
        const text = document.getElementById('regStrengthText');

        const levels = [
            { pct: '20%', color: '#ef4444', label: 'Very Weak' },
            { pct: '40%', color: '#f97316', label: 'Weak' },
            { pct: '60%', color: '#eab308', label: 'Fair' },
            { pct: '80%', color: '#84cc16', label: 'Good' },
            { pct: '100%',color: '#22c55e', label: 'Strong' }
        ];

        if (val.length === 0) {
            bar.style.width = '0';
            text.textContent = '';
            text.style.color = 'rgba(255,255,255,.3)';
        } else {
            const lvl = levels[score - 1];
            bar.style.width = lvl.pct;
            bar.style.backgroundColor = lvl.color;
            text.textContent = lvl.label;
            text.style.color = lvl.color;
        }
    });

    function toggleReqClass(id, valid) {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('valid', valid);
    }
})();

(function() {
    const form = document.getElementById('registerForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const name     = this.querySelector('input[name="name"]').value.trim();
        const email    = this.querySelector('input[name="email"]').value.trim();
        const phone    = this.querySelector('input[name="phone"]').value.trim();
        const address  = this.querySelector('input[name="address"]').value.trim();
        const password = this.querySelector('input[name="password"]').value;
        const terms    = this.querySelector('#regTermsAgree').checked;
        const btn      = document.getElementById('regSubmitBtn');
        const originalBtnHtml = btn ? btn.innerHTML : 'Create Account';

        function showRegError(msg) {
            const wrap = document.getElementById('regErrorWrap');
            const span = document.getElementById('regErrorMsg');
            if (wrap && span) {
                wrap.style.display = 'flex';
                span.textContent = msg;
            }
        }

        if (!name) { showRegError('Please enter your full name.'); return; }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showRegError('Please enter a valid email address.'); return; }
        if (!phone) { showRegError('Please enter your phone number.'); return; }
        if (!address) { showRegError('Please enter your address.'); return; }
        if (password.length < 8) { showRegError('Password must be at least 8 characters long.'); return; }
        if (!terms) { showRegError('Please agree to the Terms & Conditions and Privacy Policy.'); return; }

        if (btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
            btn.disabled = true;
        }

        const formData = new FormData(this);
        formData.append('ajax', '1');

        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect || 'customer/dashboard.php';
            } else {
                showRegError(data.message || 'Failed to create account.');
                if (btn) {
                    btn.innerHTML = originalBtnHtml;
                    btn.disabled = false;
                }
            }
        })
        .catch(error => {
            showRegError('An error occurred. Please try again.');
            if (btn) {
                btn.innerHTML = originalBtnHtml;
                btn.disabled = false;
            }
        });
    });
})();

(function() {
    const form = document.getElementById('signInForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const email = this.querySelector('input[name="email"]').value.trim();
        const password = this.querySelector('input[name="password"]').value;
        const btn = this.querySelector('button[type="submit"]');
        const originalBtnHtml = btn ? btn.innerHTML : 'Sign In';

        function showSigninError(msg) {
            const wrap = document.getElementById('signinErrorWrap');
            const span = document.getElementById('signinErrorMsg');
            if (wrap && span) {
                wrap.style.display = 'flex';
                span.textContent = msg;
            }
        }

        if (!email || !password) {
            showSigninError('Please enter both email and password.');
            return;
        }

        if (btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
            btn.disabled = true;
        }

        const formData = new FormData(this);
        formData.append('ajax', '1');

        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect || 'customer/dashboard.php';
            } else {
                showSigninError(data.message || 'Invalid email or password.');
                if (btn) {
                    btn.innerHTML = originalBtnHtml;
                    btn.disabled = false;
                }
            }
        })
        .catch(error => {
            showSigninError('An error occurred. Please try again.');
            if (btn) {
                btn.innerHTML = originalBtnHtml;
                btn.disabled = false;
            }
        });
    });
})();

<?php if (!$is_admin && !$is_customer): ?>

document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap === 'undefined') return;
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('show_login') || urlParams.has('login') || urlParams.has('open_signin')) {
        const signInModalEl = document.getElementById('signInModal');
        if (signInModalEl) {
            const siModal = bootstrap.Modal.getInstance(signInModalEl) || new bootstrap.Modal(signInModalEl);
            siModal.show();
        }
    } else if (urlParams.has('show_register') || urlParams.has('register') || urlParams.has('open_register')) {
        const registerModalEl = document.getElementById('registerModal');
        if (registerModalEl) {
            const regModal = bootstrap.Modal.getInstance(registerModalEl) || new bootstrap.Modal(registerModalEl);
            regModal.show();
        }
    }
});
<?php endif; ?>
</script>
<?php include __DIR__ . '/toast.php'; ?>

<script>
function updateSuppPriority(input) {
    const wrap = input.closest("#suppPriorityWrap") || input.closest("div");
    if (!wrap) return;
    wrap.querySelectorAll("label").forEach(lbl => {
        lbl.classList.remove("active-normal", "active-urgent");
        lbl.style.background = "";
        lbl.style.color = "";
        lbl.style.borderColor = "";
    });
    const label = input.closest("label");
    if (input.value === "high" || input.value === "urgent") {
        label.classList.add("active-urgent");
    } else {
        label.classList.add("active-normal");
    }
}
</script>