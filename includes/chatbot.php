<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION['user_id']) || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);

if (!isset($pdo)) {
    @include_once __DIR__ . '/db.php';
}

$is_in_customer_dir = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'customer');
$customer_url_prefix = $is_in_customer_dir ? '' : 'customer/';

$cb_vehicle_types = [];
$cb_car_models    = [];
$cb_packages      = [];
$cb_model_prices  = [];

if (isset($pdo)) {
    try {
        $cb_vehicle_types = $pdo->query("SELECT id, type_name, price_category FROM vehicle_types WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    try {
        $cb_car_models = $pdo->query("SELECT DISTINCT car_model FROM package_model_prices WHERE car_model IS NOT NULL AND car_model != '' ORDER BY car_model ASC")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {}

    try {
        $cb_packages = $pdo->query("SELECT id, name, description, price, sedan_price, suv_price, van_price, features, warranty_years, installation_time, tint_type FROM tint_packages WHERE is_active = 1 ORDER BY sedan_price ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    try {
        $cb_model_prices = $pdo->query("SELECT package_id, car_model, tint_type, shade_percentage, price FROM package_model_prices")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}
?>

<div id="chatbotContainer">
    <button id="chatbotToggle" aria-label="Open Tint Assistant">
        <div class="cb-icon-wrap">
            <i class="fas fa-car fa-lg"></i>
            <i class="fas fa-tint fa-sm cb-tint-overlay"></i>
        </div>
        <span class="cb-notif-dot" id="chatNotification" style="display:none;"><i class="fas fa-bell"></i></span>
    </button>

    <div id="chatbotWindow" style="display:none;">
        
        <div class="cb-header">
            <div class="d-flex align-items-center">
                <div class="cb-avatar me-3">
                    <i class="fas fa-car"></i>
                    <div class="cb-online-dot"></div>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold">Tint Assistant</h6>
                    <small class="cb-subtitle">Car Tint Expert 🤖</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge" style="background:var(--gold);color:#0A0A0A;font-size:.68rem;">AI</span>
                <button id="chatbotClose" class="cb-close-btn"><i class="fas fa-times fa-lg"></i></button>
            </div>
        </div>

        
        <div id="chatbotBody" class="cb-body" style="position:relative;">
            
            <div id="chatbotContentWrapper">
                <div id="welcomeMessage" class="mb-3">
                    <div class="cb-bubble cb-bot" style="border-left:3px solid var(--gold);">
                        <div class="d-flex align-items-center mb-2">
                            <div class="cb-bot-icon me-2">
                                <i class="fas fa-car" style="color:var(--gold);font-size:.75rem;"></i>
                            </div>
                            <div>
                                <strong>Tint Assistant</strong>
                                <div class="d-flex gap-1 mt-1">
                                    <span class="badge bg-dark" style="font-size:.6rem;">Car Expert</span>
                                    <span class="badge" style="background:var(--gold);color:#0A0A0A;font-size:.6rem;">Davao</span>
                                </div>
                            </div>
                        </div>
                        <p class="mb-2">Hello! 🚗 I'm your <strong style="color:var(--gold);">Car Tint Assistant</strong> 👋</p>
                        <p class="mb-2">I specialize in helping Davao car owners choose the perfect window tint!</p>
                        <p class="mb-0">Let me help you with:</p>
                        <ul class="mb-2 ps-3">
                            <li>🎯 Perfect tint recommendations</li>
                            <li>💰 Price estimates for your vehicle</li>
                            <li>🔥 Heat protection comparisons</li>
                            <li>⚖️ Legal tint limits in Philippines</li>
                        </ul>
                        <p class="mb-0">What would you like to know?</p>
                    </div>
                </div>

                
                <div id="quickQuestions" class="mb-3">
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <button type="button" id="startRecommendation" class="btn btn-sm cb-quick-action" data-action="recommend"><i class="fas fa-magic me-1"></i>Recommend Tint</button>
                        <button type="button" class="btn btn-sm cb-quick-action" data-action="price"><i class="fas fa-tag me-1"></i>Price Estimate</button>
                        <button type="button" class="btn btn-sm cb-quick-action" data-action="compare"><i class="fas fa-balance-scale me-1"></i>Compare Tints</button>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-sm cb-quick-q" data-question="How much for Sedan?">Sedan price?</button>
                        <button type="button" class="btn btn-sm cb-quick-q" data-question="How much for SUV?">SUV price?</button>
                        <button type="button" class="btn btn-sm cb-quick-q" data-question="What is the best tint for heat?">Best for heat?</button>
                        <button type="button" class="btn btn-sm cb-quick-q" data-question="What is the legal tint limit in PH?">Legal tint limit?</button>
                    </div>
                </div>

                <div id="chatMessages"></div>

                
                <div id="tintForm" style="display:none;">
                    <div class="cb-bubble cb-bot" style="border-left:3px solid var(--gold);">
                        <div class="d-flex align-items-center mb-3">
                            <div style="width:40px;height:40px;background:var(--gold);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-right:10px;">
                                <i class="fas fa-car" style="color:#0A0A0A;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">🎯 Let's find your perfect tint!</h6>
                                <small class="text-muted">Personalized recommendation in 4 steps</small>
                            </div>
                        </div>
                        <form id="recommendationForm">
                            <div class="mb-3">
                                <label class="form-label small fw-bold"><i class="fas fa-car me-1" style="color:var(--gold);"></i>1. Vehicle Type</label>
                                <select class="form-select form-select-sm cb-select" id="cbVehicleType" required>
                                    <option value="">Select your vehicle or car model...</option>
                                    <?php if (!empty($cb_car_models)): ?>
                                    <optgroup label="🚘 Specific Car Models (Admin Added)">
                                        <?php foreach ($cb_car_models as $cm): ?>
                                            <option value="<?= htmlspecialchars($cm) ?>" data-kind="model"><?= htmlspecialchars($cm) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php endif; ?>
                                    <optgroup label="🚙 Vehicle Categories">
                                        <?php foreach ($cb_vehicle_types as $vt): ?>
                                            <option value="<?= htmlspecialchars($vt['type_name']) ?>" data-kind="type" data-cat="<?= htmlspecialchars($vt['price_category']) ?>"><?= htmlspecialchars($vt['type_name']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold"><i class="fas fa-bullseye me-1" style="color:var(--gold);"></i>2. Main Concern</label>
                                <div class="row g-2">
                                    <div class="col-6"><div class="cb-check-card"><input class="form-check-input" type="radio" name="cbConcern" id="cbHeat" value="heat" required><label class="form-check-label w-100 text-center" for="cbHeat"><i class="fas fa-sun fa-lg d-block mb-1"></i><small>Heat Protection</small></label></div></div>
                                    <div class="col-6"><div class="cb-check-card"><input class="form-check-input" type="radio" name="cbConcern" id="cbPrivacy" value="privacy"><label class="form-check-label w-100 text-center" for="cbPrivacy"><i class="fas fa-eye-slash fa-lg d-block mb-1"></i><small>Privacy</small></label></div></div>
                                    <div class="col-6"><div class="cb-check-card"><input class="form-check-input" type="radio" name="cbConcern" id="cbLooks" value="looks"><label class="form-check-label w-100 text-center" for="cbLooks"><i class="fas fa-star fa-lg d-block mb-1"></i><small>Looks / Style</small></label></div></div>
                                    <div class="col-6"><div class="cb-check-card"><input class="form-check-input" type="radio" name="cbConcern" id="cbUv" value="uv"><label class="form-check-label w-100 text-center" for="cbUv"><i class="fas fa-shield-alt fa-lg d-block mb-1"></i><small>UV Protection</small></label></div></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold"><i class="fas fa-money-bill-wave me-1" style="color:var(--gold);"></i>3. Budget Range</label>
                                <select class="form-select form-select-sm cb-select" id="cbBudget" required>
                                    <option value="">Select budget range...</option>
                                    <option value="budget">💸 Budget (₱3,000 - ₱5,000)</option>
                                    <option value="mid">💰 Mid-range (₱5,000 - ₱8,000)</option>
                                    <option value="premium">💎 Premium (₱8,000+)</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold"><i class="fas fa-moon me-1" style="color:var(--gold);"></i>4. Desired Darkness</label>
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Light</small><small class="text-muted">Medium</small><small class="text-muted">Dark</small><small class="text-muted">Very Dark</small>
                                </div>
                                <input type="range" class="form-range" id="cbDarkness" min="1" max="4" step="1" value="2">
                            </div>
                            <button type="submit" class="btn w-100 fw-bold" style="background:var(--gold);color:#0A0A0A;border-radius:50px;">
                                <i class="fas fa-magic me-2"></i>Get My Tint Recommendation
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="cb-footer">
            <div class="input-group">
                <input type="text" id="chatInput" class="form-control cb-input" placeholder="Ask about car tinting..." autocomplete="off">
                <button id="chatSend" class="btn cb-send-btn" type="button" aria-label="Send Message">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="d-flex justify-content-end mt-2">
                <button id="clearChat" class="btn btn-sm cb-footer-btn-muted"><i class="fas fa-redo me-1"></i> Restart</button>
            </div>
        </div>
    </div>
</div>

<style>

#chatbotContainer {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 9999;
}
#chatbotToggle {
    width: 60px; height: 60px;
    border-radius: 50%;
    background: #0A0A0A;
    border: 2.5px solid var(--gold);
    color: var(--gold);
    font-size: 1.5rem;
    box-shadow: 0 8px 28px rgba(212,160,23,.3);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    animation: cbFloat 3s ease-in-out infinite;
    transition: all .3s;
}
#chatbotToggle:hover {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 12px 36px rgba(212,160,23,.5);
}
@keyframes cbFloat {
    0%,100% { transform: translateY(0); }
    50%     { transform: translateY(-6px); }
}
.cb-icon-wrap {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%; height: 100%;
}
.cb-tint-overlay {
    position: absolute;
    bottom: 10px; right: 10px;
    color: #3498db;
    background: #fff;
    border-radius: 50%;
    padding: 2px;
    font-size: .7rem;
}
.cb-notif-dot {
    position: absolute;
    top: -4px; right: -4px;
    background: #ef4444;
    color: #fff;
    font-size: .6rem;
    padding: 3px 6px;
    border-radius: 99px;
    font-weight: 700;
}
#chatbotWindow {
    position: fixed;
    bottom: 100px; right: 30px;
    width: 380px; max-width: 92vw;
    height: 560px; max-height: 75vh;
    background: #111111;
    border-radius: 20px;
    z-index: 9998;
    overflow: hidden;
    border: 2.5px solid var(--gold);
    box-shadow: 0 12px 50px rgba(212,160,23,.2);
    display: flex;
    flex-direction: column;
}
.cb-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 18px;
    background: #0A0A0A;
    color: #fff;
    border-bottom: 2px solid var(--gold);
    flex-shrink: 0;
}
.cb-avatar {
    width: 42px; height: 42px;
    background: var(--gold);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    border: 2px solid #fff;
    color: #0A0A0A;
    font-size: 1rem;
}
.cb-online-dot {
    position: absolute;
    bottom: 2px; right: 2px;
    width: 10px; height: 10px;
    background: #22c55e;
    border-radius: 50%;
    border: 2px solid #fff;
}
.cb-subtitle {
    color: rgba(245,200,66,.85);
    font-size: .75rem;
}
.cb-close-btn {
    background: none;
    border: none;
    color: #fff;
    cursor: pointer;
    padding: 0;
    font-size: 1.2rem;
    opacity: .7;
    transition: opacity .2s;
}
.cb-close-btn:hover { opacity: 1; }
.cb-body {
    flex: 1;
    overflow-y: auto;
    padding: 18px;
    background: #181818;
}
.cb-body::-webkit-scrollbar { width: 5px; }
.cb-body::-webkit-scrollbar-track { background: transparent; }
.cb-body::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 3px; }
.cb-bubble {
    padding: 12px 16px;
    border-radius: 16px;
    margin-bottom: 12px;
    max-width: 88%;
    position: relative;
    animation: cbFadeIn .3s ease;
    font-size: .88rem;
    line-height: 1.5;
}
@keyframes cbFadeIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }
.cb-bubble.cb-bot {
    background: #222222;
    border: 1px solid #333333;
    border-top-left-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,.2);
    color: #eeeeee;
}
.cb-bubble.cb-user {
    background: var(--gold);
    color: #0A0A0A;
    margin-left: auto;
    border-top-right-radius: 4px;
    font-weight: 500;
}
.cb-bot-icon {
    width: 26px; height: 26px;
    background: #181818;
    border: 1.5px solid var(--gold);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.cb-quick-action {
    border: 1.5px solid var(--gold);
    border-radius: 8px;
    padding: 6px 8px;
    font-size: .78rem;
    color: var(--gold);
    background: transparent;
    transition: all .2s;
    font-weight: 600;
}
.cb-quick-action:hover {
    background: var(--gold);
    color: #0A0A0A;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(212,160,23,.2);
}
.cb-quick-q {
    transition: all .2s;
    font-size: .78rem;
    border: 1px solid #333333;
    background: #222222;
    color: #eeeeee;
    border-radius: 50px;
    padding: 5px 12px;
}
.cb-quick-q:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(212,160,23,.1);
    border-color: var(--gold);
    background: rgba(212,160,23,.1);
}
.cb-select {
    background-color: #222;
    color: #fff;
    border: 1px solid #444;
}
.cb-select:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 .25rem rgba(212,160,23,.15);
    background-color: #222;
    color: #fff;
}
.cb-check-card {
    background: #222;
    border: 1.5px solid #333;
    border-radius: 8px;
    padding: 8px;
    transition: all .2s;
    cursor: pointer;
    color: #aaa;
}
.cb-check-card:hover {
    border-color: var(--gold);
    background: rgba(212,160,23,.1);
    color: #fff;
}
.cb-footer {
    padding: 12px 16px;
    background: #0A0A0A;
    border-top: 1px solid #222;
    flex-shrink: 0;
}
.cb-input {
    border-radius: 25px 0 0 25px;
    border: 1.5px solid #333;
    padding-left: 16px;
    font-size: .88rem;
    height: 40px;
    background: #222;
    color: #fff;
}
.cb-input:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(212,160,23,.12);
    background: #222;
    color: #fff;
}
.cb-input:disabled {
    background: #181818;
    color: #666;
    border-color: #222;
}
.cb-send-btn {
    border-radius: 0 25px 25px 0;
    background: var(--gold);
    border: 1.5px solid var(--gold);
    color: #0A0A0A;
    font-size: .9rem;
    padding: 0 16px;
    transition: all .2s;
}
.cb-send-btn:hover {
    background: #f5c842;
    border-color: #f5c842;
}
.cb-send-btn:disabled {
    background: #333;
    border-color: #333;
    color: #666;
}
.cb-footer-btn {
    border-radius: 50px;
    font-size: .78rem;
    border: 1.5px solid var(--gold);
    color: var(--gold);
    background: transparent;
    font-weight: 600;
}
.cb-footer-btn:hover {
    background: var(--gold);
    color: #0A0A0A;
}
.cb-footer-btn-muted {
    border-radius: 50px;
    font-size: .78rem;
    border: 1px solid #333;
    color: #888;
    background: transparent;
}
.cb-footer-btn-muted:hover {
    border-color: #555;
    background: #222;
}
.cb-typing {
    display: flex;
    align-items: center;
    padding: 10px 16px;
}
.cb-typing span {
    height: 8px; width: 8px;
    background: var(--gold);
    border-radius: 50%;
    display: inline-block;
    margin: 0 2px;
    animation: cbBounce 1.4s infinite ease-in-out;
}
.cb-typing span:nth-child(1) { animation-delay: -.3s; }
.cb-typing span:nth-child(2) { animation-delay: -.15s; }
@keyframes cbBounce {
    0%,80%,100% { transform: scale(0); }
    40%         { transform: scale(1); }
}
.cb-rec-card {
    background: rgba(212,160,23,.1);
    border: 2px solid var(--gold);
    border-radius: 12px;
    padding: 14px;
    margin: 10px 0;
    animation: cbFadeIn .5s ease;
    color: #eee;
}
.cb-bubble .table {
    margin-bottom: 6px;
    color: #eee;
}
.cb-bubble .table td,
.cb-bubble .table th {
    font-size: .8rem;
    padding: 5px 8px;
    border-color: #333;
}
.cb-bubble a {
    color: var(--gold);
    font-weight: 600;
    text-decoration: none;
}
.cb-bubble a:hover {
    color: #f5c842;
    text-decoration: underline;
}
@media (max-width: 575.98px) {
    #chatbotToggle {
        width: 52px; height: 52px;
        bottom: 20px; right: 16px;
        font-size: 1.2rem;
    }
    #chatbotWindow {
        bottom: 80px; right: 10px;
        width: calc(100vw - 20px);
        height: 70vh;
    }
    .cb-bubble { max-width: 92%; font-size: .82rem; }
}
</style>

<script>

window.cbPackages = <?= json_encode(array_values($cb_packages)) ?>;
window.cbModelPrices = <?= json_encode($cb_model_prices) ?>;

(function(){
    'use strict';

    var cbPackages = window.cbPackages || [];
    var cbModelPrices = window.cbModelPrices || [];
    var isUserLoggedIn = <?= json_encode($is_logged_in) ?>;
    var customerUrlPrefix = <?= json_encode($customer_url_prefix) ?>;

    var chatbotToggle       = document.getElementById('chatbotToggle');
    var chatbotWindow       = document.getElementById('chatbotWindow');
    var chatbotClose        = document.getElementById('chatbotClose');
    var chatInput           = document.getElementById('chatInput');
    var chatSend            = document.getElementById('chatSend');
    var chatMessages        = document.getElementById('chatMessages');
    var welcomeMessage      = document.getElementById('welcomeMessage');
    var quickQuestions      = document.getElementById('quickQuestions');
    var tintForm            = document.getElementById('tintForm');
    var startRecommendation = document.getElementById('startRecommendation');
    var clearChat           = document.getElementById('clearChat');
    var recommendationForm  = document.getElementById('recommendationForm');
    var chatNotification    = document.getElementById('chatNotification');

    if(!chatbotToggle) return;

    
    function escHtml(s){
        if (s == null) return '';
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    var escapeHtml = escHtml;

    /* ── Helper: Package Price Lookup ───── */
    function getPkgPrice(pkg, vt) {
        if (!pkg) return 0;
        vt = (vt || '').toLowerCase();
        if (vt === 'suv' && parseFloat(pkg.suv_price) > 0) return parseFloat(pkg.suv_price);
        if (vt === 'van' && parseFloat(pkg.van_price) > 0) return parseFloat(pkg.van_price);
        if (vt === 'pickup' && parseFloat(pkg.pickup_price || pkg.suv_price) > 0) return parseFloat(pkg.pickup_price || pkg.suv_price);
        if (parseFloat(pkg.sedan_price) > 0) return parseFloat(pkg.sedan_price);
        if (parseFloat(pkg.price) > 0) return parseFloat(pkg.price);
        return 0;
    }

    /* ── Helper: Booking Navigation ─────── */
    function navigateBooking(pkgId, model, shade) {
        if (chatbotClose) chatbotClose.click();
        pkgId = pkgId || (cbPackages && cbPackages[0] ? cbPackages[0].id : '');
        model = model || '';
        shade = shade || '';

        if (typeof window.applyBookingRecommendation === 'function') {
            window.applyBookingRecommendation(pkgId, model, shade);
            return;
        }
        if (typeof window.openBookingModal === 'function') {
            window.openBookingModal(pkgId, model, 'package');
            return;
        }
        var signInModalEl = document.getElementById('signInModal');
        if (!isUserLoggedIn && signInModalEl && typeof bootstrap !== 'undefined') {
            var modal = bootstrap.Modal.getInstance(signInModalEl) || new bootstrap.Modal(signInModalEl);
            modal.show();
            return;
        }
        var bookUrl = customerUrlPrefix + 'book.php';
        if (pkgId || model) {
            bookUrl += '?package_id=' + encodeURIComponent(pkgId) + '&car_model=' + encodeURIComponent(model) + '&shade=' + encodeURIComponent(shade);
        }
        window.location.href = bookUrl;
    }
    window.cbNavigateBooking = navigateBooking;

    /* ── Tint Knowledge Base ───────────── */
    var tintKB = {
        types: {
            ceramic:  { name:'Nano Ceramic Tint',     price:'₱4,500–₱12,000', warranty:'3-5 Year', heat:'99%', benefits:['Best heat rejection (up to 99%)','No signal interference','Longest lifespan (7+ years)','Highest UV protection (99%)'] },
            carbon:   { name:'Carbon Ceramic Tint',   price:'₱4,500–₱8,000',  warranty:'3-Year',   heat:'85-95%', benefits:['Good heat rejection','No signal interference','Non-reflective','Good value'] },
            chrome:   { name:'Magic Chrome Ceramic',  price:'₱4,500–₱9,000',  warranty:'3-Year',   heat:'90-95%', benefits:['One-way mirror privacy','High heat rejection','Luxury look'] }
        },
        faqs: {
            heat:       'For <strong>MAXIMUM heat protection</strong>, we recommend our <strong>Nano Ceramic</strong> packages. They block up to 99% of infrared solar heat and 99% of harmful UV rays — ideal for Davao\'s intense climate!',
            privacy:    'For privacy, our <strong>Magic Chrome Ceramic</strong> or dark shades (20% or 5% VLT) offer high exterior reflection with clear outward nighttime visibility.',
            legal:      '🇵🇭 <strong>PHILIPPINES LTO TINT REGULATIONS:</strong><br>• Front windshield: minimum 70% light transmission (clear or light)<br>• Front side windows: minimum 50% light transmission<br>• Rear & back: darker shades permitted<br>All our tints meet Philippine legal standards.',
            warranty:   '📋 <strong>WARRANTY COVERAGE:</strong><br>All our packages come with official warranty against bubbling, fading, discoloration, and peeling.',
            difference: '🔍 <strong>TINT COMPARISON:</strong><br>1. <strong>Nano Ceramic</strong> — Top-tier (blocks up to 99% infrared heat, crystal clear night vision)<br>2. <strong>HD Carbon Ceramic</strong> — Excellent heat reduction, sleek dark finish<br>3. <strong>Magic Chrome</strong> — Premium mirror-effect privacy with great solar rejection',
            time:       '⏰ <strong>INSTALLATION DURATION:</strong><br>• Sedan / Hatchback: 2 to 3 hours<br>• SUV / MPV / Pickup: 2.5 to 3.5 hours<br>• Van: 3 to 4 hours',
            maintenance:'🧼 <strong>AFTERCARE ADVICE:</strong><br>1. Keep windows rolled up for 3–5 days after installation<br>2. Clean only with soft microfiber and mild soapy water<br>3. Never use ammonia-based glass cleaners',
            payment:    '💳 <strong>PAYMENT METHODS:</strong><br>• Cash<br>• GCash / Maya<br>• Online Card Checkout via PayMongo<br>Downpayment: 10% on booking, balance paid upon vehicle completion.',
            mobile:     '🚗 <strong>MOBILE ON-SITE SERVICE:</strong><br>We can tint your vehicle directly at your home or office in Davao City and surrounding areas! Contact +0997 062 8687 for schedule availability.'
        },
        patterns: {
            'price.*sedan':'sedan', 'price.*suv':'suv', 'price.*pickup':'pickup', 'price.*van':'van',
            'how much.*sedan':'sedan', 'how much.*suv':'suv', 'how much.*fortuner':'suv',
            'how much.*vios':'sedan', 'how much.*hilux':'pickup', 'how much.*hiace':'van',
            'best.*heat':'heat', 'best.*privacy':'privacy',
            'legal.*tint':'legal', 'warranty':'warranty', 'remove.*tint':'removal',
            'mobile.*service':'mobile', 'payment':'payment',
            'how long.*install':'time', 'how.*clean':'maintenance'
        }
    };

    var GEMINI_KEY = 'YOUR_GOOGLE_API_KEY';
    var GEMINI_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=' + GEMINI_KEY;
    var geminiHistory = [];

    // Compact packages summary for system prompt
    var packagesSummary = (cbPackages || []).map(function(p) {
        var sed = parseFloat(p.sedan_price || p.price || 4500).toLocaleString('en-PH');
        var suv = parseFloat(p.suv_price || p.price || 4500).toLocaleString('en-PH');
        var van = parseFloat(p.van_price || p.price || 4500).toLocaleString('en-PH');
        var war = p.warranty_years ? p.warranty_years + ' Years Warranty' : 'Official Warranty';
        return '• ' + p.name + ' — Sedan: ₱' + sed + ', SUV: ₱' + suv + ', Van: ₱' + van + ' (' + war + ')' + (p.description ? ' - ' + p.description : '');
    }).join('\n');

    var systemPrompt = `You are the official AI Tint Assistant for RG CARTINTWORKS in Davao City, Philippines.
Your knowledge comes directly from our shop services and live database packages:

=== ACTIVE TINT PACKAGES ===
${packagesSummary}

=== SHOP DETAILS ===
• Location: Davao City, Philippines
• Phone: +0997 062 8687
• Operating Hours: Monday–Saturday 8:00 AM – 6:00 PM, Sunday 9:00 AM – 4:00 PM
• Services: Auto Tinting, Window Film Removal, Mobile On-site Service in Davao City

=== INSTRUCTIONS ===
1. Answer customer questions about prices, tint recommendations, warranties, heat rejection, and vehicle categories accurately.
2. If asked about a car model (e.g. Fortuner, Montero, Vios, Civic, Hilux, Wigo), cite the appropriate vehicle category price (Sedan for small cars, SUV for SUVs/crossovers, Pickup for trucks, Van for vans).
3. State prices in Philippine Pesos (₱) and mention warranty years.
4. Keep replies friendly, professional, and concise (2-4 sentences max). Use bold text for key details.
5. Remind customers that they can easily book their tint appointment online.`;

    var chatHistoryLocal = [];
    var isTyping = false;

    /* ── Toggle chatbot ─────────────────── */
    chatbotToggle.addEventListener('click', function(){
        chatbotWindow.style.display = chatbotWindow.style.display === 'none' ? 'flex' : 'none';
        chatNotification.style.display = 'none';
        if(chatbotWindow.style.display === 'flex'){
            setTimeout(function(){ 
                var body = document.getElementById('chatbotBody');
                if(body) body.scrollTop = body.scrollHeight; 
            }, 100);
        }
    });

    chatbotClose.addEventListener('click', function(){ chatbotWindow.style.display = 'none'; });

    /* ── Quick actions ──────────────────── */
    document.querySelectorAll('.cb-quick-action').forEach(function(btn){
        btn.addEventListener('click', function(){
            var action = this.dataset.action;
            switch(action){
                case 'recommend': if (startRecommendation) startRecommendation.click(); break;
                case 'price':    addUserMessage("How much does car tinting cost?"); processQuestion("How much does car tinting cost?"); break;
                case 'compare':  addUserMessage("Compare ceramic vs carbon tint"); processQuestion("Compare ceramic vs carbon tint"); break;
                case 'book':     navigateBooking(); break;
            }
        });
    });

    if (startRecommendation) {
        startRecommendation.addEventListener('click', function(){
            if (welcomeMessage) welcomeMessage.style.display = 'none';
            if (quickQuestions) quickQuestions.style.display  = 'none';
            if (tintForm) tintForm.style.display        = 'block';
            if (chatMessages) chatMessages.innerHTML        = '';
            var body = document.getElementById('chatbotBody');
            if(body) body.scrollTop = body.scrollHeight;
        });
    }

    if (clearChat) {
        clearChat.addEventListener('click', function(){
            if (chatMessages) chatMessages.innerHTML        = '';
            if (welcomeMessage) welcomeMessage.style.display  = 'block';
            if (quickQuestions) quickQuestions.style.display   = 'block';
            if (tintForm) tintForm.style.display        = 'none';
            chatHistoryLocal              = [];
            geminiHistory                 = [];
        });
    }

    /* ── Quick questions ────────────────── */
    document.querySelectorAll('.cb-quick-q').forEach(function(btn){
        btn.addEventListener('click', function(){
            var question = this.dataset.question;
            addUserMessage(question);
            processQuestion(question);
        });
    });

    /* ── Send message ───────────────────── */
    function sendMessage(){
        var message = chatInput.value.trim();
        if(message && !isTyping){
            addUserMessage(message);
            chatInput.value = '';
            processQuestion(message);
        }
    }
    chatSend.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', function(e){ if(e.key==='Enter') sendMessage(); });

    /* ── Message helpers ────────────────── */
    function addUserMessage(message){
        var div = document.createElement('div');
        div.className = 'cb-bubble cb-user';
        div.innerHTML = '<strong>You:</strong><br>' + escHtml(message);
        chatMessages.appendChild(div);
        var body = document.getElementById('chatbotBody');
        if(body) body.scrollTop = body.scrollHeight;
        chatHistoryLocal.push({role:'user',content:message});
    }

    function addBotMessage(message){
        if(isTyping) return;
        isTyping = true;
        var typingDiv = document.createElement('div');
        typingDiv.className = 'cb-typing';
        typingDiv.innerHTML = '<span></span><span></span><span></span>';
        chatMessages.appendChild(typingDiv);
        var body = document.getElementById('chatbotBody');
        if(body) body.scrollTop = body.scrollHeight;

        var delay = Math.min(500 + (message.length * 4), 1400);
        setTimeout(function(){
            if(typingDiv.parentNode) chatMessages.removeChild(typingDiv);
            var div = document.createElement('div');
            div.className = 'cb-bubble cb-bot';
            div.style.borderLeft = '3px solid var(--gold)';
            var hdr = '<div class="d-flex align-items-center mb-2">' +
                '<div class="cb-bot-icon me-2"><i class="fas fa-car" style="color:var(--gold);font-size:.65rem;"></i></div>' +
                '<strong>Tint Assistant</strong></div>';
            div.innerHTML = hdr + message;
            chatMessages.appendChild(div);
            var body = document.getElementById('chatbotBody');
            if(body) body.scrollTop = body.scrollHeight;
            isTyping = false;
            chatHistoryLocal.push({role:'bot',content:message});
            if(chatbotWindow.style.display === 'none') chatNotification.style.display = 'block';
        }, delay);
    }

    /* ── Gemini API call ────────────────── */
    function askGemini(question){
        if(isTyping) return;
        isTyping = true;
        var typingDiv = document.createElement('div');
        typingDiv.className = 'cb-typing';
        typingDiv.innerHTML = '<span></span><span></span><span></span>';
        chatMessages.appendChild(typingDiv);
        var body = document.getElementById('chatbotBody');
        if(body) body.scrollTop = body.scrollHeight;

        // Check if question mentions any car model with custom database price
        var modelNote = '';
        if (cbModelPrices && cbModelPrices.length > 0) {
            var lq = question.toLowerCase();
            var matchedModel = cbModelPrices.find(function(m){
                return m.car_model && lq.includes(m.car_model.toLowerCase());
            });
            if (matchedModel && parseFloat(matchedModel.price) > 0) {
                modelNote = ' (Live database quote for ' + matchedModel.car_model + ': ₱' + parseFloat(matchedModel.price).toLocaleString('en-PH') + ')';
            }
        }

        var promptToSend = question + (modelNote ? '\n[Note: ' + modelNote + ']' : '');
        geminiHistory.push({role:'user', parts:[{text: promptToSend}]});

        var contents = [
            {role:'user', parts:[{text: systemPrompt}]},
            {role:'model', parts:[{text:'Understood! I am the RG CARTINTWORKS Tint Assistant in Davao City. I will assist customers using our active packages and services accurately and concisely.'}]}
        ];
        contents = contents.concat(geminiHistory);

        fetch(GEMINI_URL, {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({
                contents: contents,
                generationConfig: {
                    maxOutputTokens: 800,
                    temperature: 0.7,
                    thinkingConfig: { thinkingBudget: 0 }
                }
            })
        })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if(typingDiv.parentNode) chatMessages.removeChild(typingDiv);
            isTyping = false;
            var text = '';
            try {
                var parts = data.candidates[0].content.parts;
                for (var pi = 0; pi < parts.length; pi++) {
                    if (parts[pi].text && !parts[pi].thought) {
                        text += parts[pi].text;
                    }
                }
                if (!text && parts[0] && parts[0].text) {
                    text = parts[0].text;
                }
            } catch(e){ text = ''; }
            if(!text){
                addBotMessage("I'm having trouble connecting right now. Please try again or call us at <a href='tel:+09970628687' style='color:var(--gold);font-weight:600;'>+0997 062 8687</a>");
                return;
            }
            geminiHistory.push({role:'model', parts:[{text:text}]});
            if(geminiHistory.length > 20) geminiHistory = geminiHistory.slice(-16);

            var formatted = text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>')
                .replace(/•/g, '&bull;');

            var div = document.createElement('div');
            div.className = 'cb-bubble cb-bot';
            div.style.borderLeft = '3px solid var(--gold)';
            var hdr = '<div class="d-flex align-items-center mb-2">' +
                '<div class="cb-bot-icon me-2"><i class="fas fa-car" style="color:var(--gold);font-size:.65rem;"></i></div>' +
                '<strong>Tint Assistant</strong></div>';
            div.innerHTML = hdr + formatted;
            chatMessages.appendChild(div);
            var body = document.getElementById('chatbotBody');
            if(body) body.scrollTop = body.scrollHeight;
            chatHistoryLocal.push({role:'bot',content:text});
        })
        .catch(function(){
            if(typingDiv.parentNode) chatMessages.removeChild(typingDiv);
            isTyping = false;
            addBotMessage("Connection error. Please try again or call <a href='tel:+09970628687' style='color:var(--gold);font-weight:600;'>+0997 062 8687</a>");
        });
    }

    /* ── Process question ───────────────── */
    function processQuestion(question){
        var lq = question.toLowerCase();

        /* Greetings */
        if(/(hello|hi|hey|good morning|good afternoon|good evening)/i.test(lq)){
            addBotMessage("Hello! 👋 I'm your Tint Assistant. How can I help you choose the best tint for your vehicle today?");
            return;
        }
        if(/(thank|thanks|salamat)/i.test(lq)){
            addBotMessage("You're very welcome! 😊 If you have any other questions, feel free to ask anytime!");
            return;
        }
        if(/(bye|goodbye|see you)/i.test(lq)){
            addBotMessage("Goodbye! 👋 Have a safe drive! Feel free to call us anytime at <a href='tel:+09970628687' style='color:var(--gold);'>+0997 062 8687</a>.");
            return;
        }

        /* Pattern matching against knowledge base */
        var matched = null;
        for(var pat in tintKB.patterns){
            if(new RegExp(pat,'i').test(lq)){
                matched = tintKB.patterns[pat];
                break;
            }
        }

        if(matched && (matched === 'sedan' || matched === 'suv' || matched === 'pickup' || matched === 'van')){
            if (cbPackages && cbPackages.length > 0) {
                var vUpper = matched.toUpperCase();
                var msg = '<strong>🚙 Active ' + vUpper + ' Database Pricing:</strong><br><ul style="padding-left:16px;margin:8px 0;font-size:0.82rem;">';
                cbPackages.forEach(function(pkg) {
                    var priceVal = getPkgPrice(pkg, matched);
                    var priceStr = priceVal > 0 ? ('₱' + priceVal.toLocaleString('en-PH', {minimumFractionDigits:0})) : '₱4,500';
                    var war = pkg.warranty_years ? ' (' + pkg.warranty_years + ' Yrs Warranty)' : '';
                    msg += '<li><strong>' + escapeHtml(pkg.name) + ':</strong> ' + priceStr + war + '</li>';
                });
                msg += '</ul><button type="button" class="btn btn-sm mt-1 fw-bold" style="background:var(--gold);color:#0A0A0A;border-radius:8px;" onclick="window.cbNavigateBooking();"><i class="fas fa-calendar-check me-1"></i> Book for ' + vUpper + ' →</button>';
                addBotMessage(msg);
                return;
            }
        }

        if(matched && tintKB.faqs[matched]){
            addBotMessage(tintKB.faqs[matched].replace(/\n/g,'<br>'));
            return;
        }

        /* FAQ keyword fallback */
        if (lq.includes("heat") || lq.includes("hot")) {
            if (cbPackages && cbPackages.length > 0) {
                var bestPkg = cbPackages.find(function(p){ return (p.name || '').toLowerCase().includes("ceramic") || (p.name || '').toLowerCase().includes("hybrid"); }) || cbPackages[0];
                var bPrice = getPkgPrice(bestPkg, 'sedan');
                var bPriceStr = bPrice > 0 ? ('₱' + bPrice.toLocaleString("en-PH", {minimumFractionDigits:0})) : '₱4,500';
                addBotMessage("☀️ <strong>Best for Maximum Heat Rejection:</strong><br>" +
                    "Our top database package is <strong>" + escapeHtml(bestPkg.name) + "</strong>.<br>" +
                    "• Base Price: <strong>" + bPriceStr + "</strong><br>" +
                    "• Warranty: <strong>" + (bestPkg.warranty_years ? bestPkg.warranty_years + " Years Official Warranty" : "3 Years Official Warranty") + "</strong><br>" +
                    "Engineered to block up to 99% infrared heat & UV rays for maximum cabin cooling in Davao.<br><br>" +
                    "<button type=\"button\" class=\"btn btn-sm fw-bold\" style=\"background:var(--gold);color:#0A0A0A;border-radius:50px;\" onclick=\"window.cbNavigateBooking('" + bestPkg.id + "');\"><i class=\"fas fa-calendar-check me-1\"></i> Book This Package</button>");
                return;
            }
        }

        if (lq.includes("warranty") || lq.includes("guarantee")) {
            if (cbPackages && cbPackages.length > 0) {
                var wMsg = "🏅 <strong>Active Database Package Warranties:</strong><br><ul style='padding-left:16px;margin:8px 0;font-size:0.82rem;'>";
                cbPackages.forEach(function(pkg) {
                    var war = pkg.warranty_years ? pkg.warranty_years + " Years Official Warranty" : "3 Years Official Warranty";
                    wMsg += "<li><strong>" + escapeHtml(pkg.name) + ":</strong> " + war + "</li>";
                });
                wMsg += "</ul>All installations include official RG CARTINTWORKS warranty coverage against bubbling, fading, or peeling.";
                addBotMessage(wMsg);
                return;
            }
        }

        for(var fk in tintKB.faqs){
            if(lq.includes(fk) ||
               (fk==='time' && (lq.includes('how long')||lq.includes('duration'))) ||
               (fk==='maintenance' && (lq.includes('clean')||lq.includes('care')))){
                addBotMessage(tintKB.faqs[fk].replace(/\n/g,'<br>'));
                return;
            }
        }

        /* Price questions */
        if(/(price|cost|how much|magkano|bayad)/i.test(lq)){
            var vt = 'general';
            if(lq.includes('sedan')||lq.includes('vios')||lq.includes('city'))          vt='sedan';
            else if(lq.includes('suv')||lq.includes('fortuner')||lq.includes('montero')) vt='suv';
            else if(lq.includes('pickup')||lq.includes('hilux')||lq.includes('ranger'))  vt='pickup';
            else if(lq.includes('van')||lq.includes('hiace')||lq.includes('urvan'))      vt='van';
            addBotMessage(getPriceResponse(vt));
            return;
        }

        /* Compare */
        if(/(difference|compare|vs|versus|which is better)/i.test(lq)){
            if (cbPackages && cbPackages.length > 0) {
                var cHtml = '⚖️ <strong>ACTIVE TINT PACKAGE COMPARISON:</strong><br>' +
                    '<div style="overflow-x:auto;"><table class="table table-sm table-dark table-bordered mt-2" style="font-size:.75rem;">' +
                    '<thead style="color:var(--gold);"><tr><th>Package</th><th>Features</th><th>Warranty</th></tr></thead><tbody>';
                cbPackages.forEach(function(pkg) {
                    var feat = pkg.features ? pkg.features.split("\n").slice(0,2).join(", ") : (pkg.description || "Premium Tint");
                    cHtml += '<tr><td><strong>' + escapeHtml(pkg.name) + '</strong></td>' +
                             '<td>' + escapeHtml(feat) + '</td>' +
                             '<td>' + (pkg.warranty_years ? pkg.warranty_years + ' Yrs' : '3 Yrs') + '</td></tr>';
                });
                cHtml += '</tbody></table></div>' +
                         '<button type="button" class="btn btn-sm mt-1 fw-bold" style="background:var(--gold);color:#0A0A0A;border-radius:8px;" onclick="window.cbNavigateBooking();"><i class="fas fa-calendar-check me-1"></i> Book An Appointment →</button>';
                addBotMessage(cHtml);
            } else {
                addBotMessage(tintKB.faqs.difference.replace(/\n/g,'<br>'));
            }
            return;
        }

        /* Booking questions */
        if(/(book|appointment|schedule|reserve)/i.test(lq)){
            addBotMessage('📅 <strong>Book Your Tint Appointment:</strong><br>' +
                'We offer in-shop service in Davao City or mobile on-site service.<br><br>' +
                '<button type="button" class="btn btn-sm fw-bold" style="background:var(--gold);color:#0A0A0A;border-radius:50px;" onclick="window.cbNavigateBooking();"><i class="fas fa-calendar-check me-1"></i> Book Appointment Now</button>');
            return;
        }

        /* Fallback → Gemini AI */
        askGemini(question);
    }

    /* ── Price response ─────────────────── */
    function getPriceResponse(vt){
        if (!cbPackages || cbPackages.length === 0) {
            return "<strong>🏷️ CURRENT PACKAGES:</strong><br>Please view our active packages page for real-time pricing.";
        }
        var vLabel = (vt && vt !== 'general') ? vt.toUpperCase() : 'BASE';
        var html = '<strong>🏷️ ACTIVE PACKAGES (' + escapeHtml(vLabel) + ' PRICING):</strong><br>' +
            '<div style="overflow-x:auto;"><table class="table table-sm table-dark table-bordered mt-2" style="font-size:.78rem;border-color:rgba(255,255,255,0.15);">' +
            '<thead style="color:var(--gold);"><tr><th>Package</th><th>Price</th><th>Warranty</th></tr></thead><tbody>';
        
        cbPackages.forEach(function(pkg) {
            var priceVal = getPkgPrice(pkg, vt);
            var priceTxt = priceVal > 0 ? ("₱" + priceVal.toLocaleString("en-PH", {minimumFractionDigits:0})) : "₱4,500";
            var war = pkg.warranty_years ? pkg.warranty_years + ' Yrs' : '3 Yrs';
            html += '<tr><td><strong>' + escapeHtml(pkg.name) + '</strong></td>' +
                    '<td style="color:#22c55e;font-weight:700;">' + priceTxt + '</td>' +
                    '<td>' + escapeHtml(war) + '</td></tr>';
        });
        html += '</tbody></table></div>' +
                '<button type="button" class="btn btn-sm mt-1 fw-bold" style="background:var(--gold);color:#0A0A0A;border-radius:8px;" onclick="window.cbNavigateBooking();"><i class="fas fa-calendar-check me-1"></i> Book & Get Exact Quote →</button>';
        return html;
    }

    /* ── Recommendation form ────────────── */
    var lastRecommendedPkg = null;

    if (recommendationForm) {
        recommendationForm.addEventListener('submit', function(e){
            e.preventDefault();
            var vehicleType = document.getElementById('cbVehicleType').value;
            var concern     = document.querySelector('input[name="cbConcern"]:checked');
            if(!concern) return;
            concern = concern.value;
            var budget   = document.getElementById('cbBudget').value;
            var darkness = document.getElementById('cbDarkness').value;

            tintForm.style.display = 'none';
            var recHtml = generateRecommendation(vehicleType, concern, budget, darkness);
            addBotMessage(recHtml);

            setTimeout(function(){
                var actionDiv = document.createElement('div');
                actionDiv.className = 'mt-3';
                var pkgId = (lastRecommendedPkg && lastRecommendedPkg.id) ? lastRecommendedPkg.id : "";
                actionDiv.innerHTML =
                    '<div class="d-flex flex-wrap gap-2">' +
                    '<button type="button" class="btn btn-sm fw-bold cb-book-rec-btn" data-pkg="' + pkgId + '" data-model="' + escHtml(vehicleType) + '" data-shade="' + darkness + '" style="background:var(--gold);color:#0A0A0A;border-radius:50px;border:none;"><i class="fas fa-calendar-check me-1"></i>Book This Package</button>' +
                    '<a href="tel:+09970628687" class="btn btn-sm btn-outline-success" style="border-radius:50px;font-size:.8rem;"><i class="fas fa-phone me-1"></i>Call to Book</a>' +
                    '</div>';
                
                var bBtn = actionDiv.querySelector(".cb-book-rec-btn");
                if (bBtn) {
                    bBtn.addEventListener("click", function() {
                        var pId = this.dataset.pkg;
                        var cMod = this.dataset.model;
                        var cShd = this.dataset.shade;
                        navigateBooking(pId, cMod, cShd);
                    });
                }
                chatMessages.appendChild(actionDiv);
                var body = document.getElementById('chatbotBody');
                if(body) body.scrollTop = body.scrollHeight;
            }, 500);
        });
    }

    function generateRecommendation(vehicleType, concern, budget, darkness){
        var matchedPkg = null;
        var displayPrice = "₱4,500.00";
        var installTime  = "2–3 hours";
        
        // Search matching package from database
        if (cbPackages && cbPackages.length > 0) {
            if (budget === "premium") {
                matchedPkg = cbPackages.find(function(p){ return (p.name || '').toLowerCase().includes("ceramic") || (p.name || '').toLowerCase().includes("black"); }) || cbPackages[cbPackages.length - 1];
            } else if (budget === "mid") {
                matchedPkg = cbPackages.find(function(p){ return (p.name || '').toLowerCase().includes("chrome") || (p.name || '').toLowerCase().includes("clear"); }) || cbPackages[Math.floor(cbPackages.length / 2)];
            } else {
                matchedPkg = cbPackages[0];
            }
        }
        
        // Search specific car model price if selected
        lastRecommendedPkg = matchedPkg;
        if (matchedPkg && cbModelPrices && cbModelPrices.length > 0) {
            var mp = cbModelPrices.find(function(m){
                return m.package_id == matchedPkg.id && (m.car_model || '').toLowerCase() === (vehicleType || '').toLowerCase();
            });
            if (mp && parseFloat(mp.price) > 0) {
                displayPrice = "₱" + parseFloat(mp.price).toLocaleString("en-PH", {minimumFractionDigits: 2});
            } else {
                var pVal = getPkgPrice(matchedPkg, 'sedan');
                displayPrice = "₱" + pVal.toLocaleString("en-PH", {minimumFractionDigits: 2});
            }
        } else if (matchedPkg) {
            var pVal = getPkgPrice(matchedPkg, 'sedan');
            displayPrice = "₱" + pVal.toLocaleString("en-PH", {minimumFractionDigits: 2});
        }
        
        var pkgTitle = matchedPkg ? matchedPkg.name : "Custom Tint Package";
        if (matchedPkg && matchedPkg.installation_time) {
            installTime = matchedPkg.installation_time;
        }
        var darkLabels = ["Light (50-70% VLT)","Medium (35-50% VLT)","Dark (20-35% VLT)","Very Dark (5-20% VLT)"];
        var selectedDarkness = darkLabels[darkness - 1] || "Medium (35-50% VLT)";
        
        var reason = "Best value and heat performance for your " + vehicleType;
        if (concern === "heat") reason = "Maximum solar heat & infrared rejection for Davao heat";
        else if (concern === "privacy") reason = "High privacy & one-way visibility for security";
        else if (concern === "looks") reason = "Sleek luxury finish and color retention";
        else if (concern === "uv") reason = "99.9% UV protection for skin and car interior";

        return '<div class="cb-rec-card">' +
            '<h6 class="fw-bold mb-3">🎯 YOUR TINT RECOMMENDATION</h6>' +
            '<div class="row mb-2"><div class="col-6"><small class="text-muted d-block">Vehicle / Model</small><strong>' + escapeHtml(vehicleType) + '</strong></div>' +
            '<div class="col-6"><small class="text-muted d-block">Concern</small><strong>' + escapeHtml(concern.charAt(0).toUpperCase() + concern.slice(1)) + '</strong></div></div>' +
            '<div class="row mb-2"><div class="col-6"><small class="text-muted d-block">Recommended Package</small><strong style="color:var(--gold);">' + escapeHtml(pkgTitle) + '</strong></div>' +
            '<div class="col-6"><small class="text-muted d-block">Darkness</small><strong>' + escapeHtml(selectedDarkness) + '</strong></div></div>' +
            '<hr style="margin:.6rem 0;opacity:.15;">' +
            '<div class="row mb-2"><div class="col-6"><small class="text-muted d-block">Database Price</small><strong style="color:var(--success);">' + displayPrice + '</strong></div>' +
            '<div class="col-6"><small class="text-muted d-block">Install Time</small><strong>' + escapeHtml(installTime) + '</strong></div></div>' +
            '<div class="mt-2 p-2" style="background:rgba(212,160,23,.08);border-radius:8px;font-size:.82rem;border-left:3px solid var(--gold);">' +
            '💡 <strong>Why?</strong> ' + escapeHtml(reason) + '</div></div>';
    }

    /* ── Global helpers ─────────────────── */
    window.cbStartRecommendation = function(){ if (startRecommendation) startRecommendation.click(); };
    window.cbClearChat = function(){ if (clearChat) clearChat.click(); };
    window.cbGenerateRecommendation = generateRecommendation;
    window.cbGetPriceResponse = getPriceResponse;
    window.cbEscapeHtml = escapeHtml;

})();
</script>
