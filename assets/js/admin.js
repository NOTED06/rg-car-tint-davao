

window.rgToast = function(title, msg, type, duration) {
    type = type || 'info';
    duration = duration || 4000;
    var wrap = document.getElementById('rgToastWrap');
    if (!wrap) { wrap = document.createElement('div'); wrap.className='rg-toast-wrap'; wrap.id='rgToastWrap'; document.body.appendChild(wrap); }
    var icons = {success:'fa-check-circle',error:'fa-times-circle',warning:'fa-exclamation-triangle',info:'fa-info-circle'};
    var el = document.createElement('div');
    el.className = 'rg-toast t-' + type;
    el.style.setProperty('--toast-dur', duration + 'ms');
    el.innerHTML = '<div class="rg-toast-icon"><i class="fas ' + (icons[type]||icons.info) + '"></i></div>' +
        '<div class="rg-toast-body"><div class="rg-toast-title">' + _escH(title) + '</div><div class="rg-toast-msg">' + _escH(msg) + '</div></div>' +
        '<button class="rg-toast-close" onclick="this.parentElement.classList.add(\'out\');setTimeout(function(){this.remove()}.bind(this.parentElement),' + 350 + ')">&times;</button>' +
        '<div class="rg-toast-bar"></div>';
    wrap.appendChild(el);
    setTimeout(function(){ if(el.parentElement){el.classList.add('out');setTimeout(function(){el.remove();},350);} }, duration);
};
function _escH(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

window.rgConfirm = function(opts) {
    var old = document.getElementById('rgConfirmOverlay');
    if (old) old.remove();

    var type = opts.type || 'danger';
    var iconMap = {danger:'fa-trash-can',warning:'fa-exclamation-triangle',info:'fa-question-circle'};
    var overlay = document.createElement('div');
    overlay.className = 'rg-confirm-overlay';
    overlay.id = 'rgConfirmOverlay';
    overlay.innerHTML =
        '<div class="rg-confirm-box">' +
            '<div class="rg-confirm-head">' +
                '<div class="rg-confirm-icon c-' + type + '"><i class="fas ' + (iconMap[type]||iconMap.info) + '"></i></div>' +
                '<div class="rg-confirm-title">' + _escH(opts.title || 'Are you sure?') + '</div>' +
            '</div>' +
            '<div class="rg-confirm-body">' + _escH(opts.message || '') + '</div>' +
            '<div class="rg-confirm-actions">' +
                '<button class="rg-confirm-btn cb-cancel" id="rgConfirmNo">' + _escH(opts.cancelText || 'Cancel') + '</button>' +
                '<button class="rg-confirm-btn cb-' + type + '" id="rgConfirmYes">' + _escH(opts.confirmText || 'Confirm') + '</button>' +
            '</div>' +
        '</div>';
    document.body.appendChild(overlay);

    
    overlay.addEventListener('click', function(ev) { if (ev.target === overlay) close(); });
    document.getElementById('rgConfirmNo').addEventListener('click', close);
    document.getElementById('rgConfirmYes').addEventListener('click', function() { close(); if (opts.onConfirm) opts.onConfirm(); });
    
    function onKey(ev){ if(ev.key==='Escape') close(); }
    document.addEventListener('keydown', onKey);
    function close(){ overlay.remove(); document.removeEventListener('keydown', onKey); }
};

document.addEventListener('submit', function(e) {
    var form = e.target;
    var msg = form.getAttribute('data-rg-confirm');
    if (!msg) return;
    e.preventDefault();
    rgConfirm({
        title: form.getAttribute('data-rg-title') || 'Confirm',
        message: msg,
        confirmText: form.getAttribute('data-rg-btn') || 'Confirm',
        type: form.getAttribute('data-rg-type') || 'danger',
        onConfirm: function() { form.removeAttribute('data-rg-confirm'); form.submit(); }
    });
});

if (typeof SECTIONS === 'undefined') var SECTIONS = ['dashboard','bookings','schedule','packages','sales','tint-stock','service','feedback','customers','admin','sms-gateway'];

var SECTION_TITLES = {
    dashboard: 'Dashboard', bookings: 'Bookings', schedule: 'Schedule', packages: 'Packages',
    sales: 'Sales', 'tint-stock': 'Tint Stock', service: 'Support',
    feedback: 'Feedback', customers: 'Customers', admin: 'Admin Users', 'sms-gateway': 'SMS Gateway'
};

function showSection(id) {
    SECTIONS.forEach(s => {
        const el = document.getElementById('section-' + s);
        const link = document.getElementById('nav-' + s);
        if (el) {
            const isActive = s === id;
            el.classList.toggle('active', isActive);
            el.style.display = isActive ? 'block' : 'none';
            el.style.width = isActive ? '100%' : '';
            el.style.minHeight = isActive ? '1px' : '';
            el.style.height = isActive ? 'auto' : '';
            el.style.position = isActive ? 'relative' : '';
            el.style.visibility = 'visible';
            el.style.opacity = '1';
            el.style.transform = 'none';
            el.style.overflow = 'visible';
            el.style.animation = 'none';
        }
        if (link) link.classList.toggle('active', s === id);
    });
    const titleEl = document.getElementById('topbarTitle');
    if (titleEl) titleEl.textContent = SECTION_TITLES[id] || 'Dashboard';
    
    const cleanUrl = window.location.pathname + '#' + id;
    history.pushState(null, null, cleanUrl);
    
    if (window.innerWidth <= 1024) closeSidebar();
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    if (id === 'schedule' && typeof window._schedBuildCalendar === 'function') {
        window._schedBuildCalendar();
    }
}
window.showSection = showSection;

function activateFromHash() {
    const hash = window.location.hash.substring(1);
    const params = new URLSearchParams(window.location.search);
    const sectionParam = params.get('section');
    
    if (hash && SECTIONS.includes(hash)) {
        showSection(hash);
    } else if (sectionParam && SECTIONS.includes(sectionParam)) {
        showSection(sectionParam);
    } else {
        showSection('dashboard');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    activateFromHash();
});
activateFromHash();
window.addEventListener('popstate', activateFromHash);
window.addEventListener('hashchange', activateFromHash);

var sidebar = document.getElementById('adminSidebar');
var overlay = document.getElementById('sidebarOverlay');
var toggleBtn = document.getElementById('sidebarToggle');

function openSidebar() {
    if (window.innerWidth > 1024) {
        
        const isHidden = document.body.classList.toggle('sidebar-hidden');
        try { localStorage.setItem('adminSidebarHidden', isHidden ? '1' : '0'); } catch(e) {}
    } else {
        
        if (sidebar) sidebar.classList.add('open');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeSidebar() {
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
}

window.openSidebar = openSidebar;
window.closeSidebar = closeSidebar;

window.addEventListener('load', function() {
    if (window.innerWidth > 1024) {
        try {
            const saved = localStorage.getItem('adminSidebarHidden');
            if (saved === '1') document.body.classList.add('sidebar-hidden');
        } catch(e) {}
    }
});

if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
if (overlay) overlay.addEventListener('click', closeSidebar);

setTimeout(() => {
    const t = document.getElementById('mainToast');
    if (t) {
        t.style.opacity = '0';
        t.style.transition = 'opacity 0.5s';
        setTimeout(() => t.remove(), 500);
    }
}, 4000);

window.updatePackageDetails = function() {
    const sel = document.getElementById('packageSelect');
    if (!sel) return;
    const opt = sel.options[sel.selectedIndex];
    const priceEl = document.getElementById('booking_price');
    const specsEl = document.getElementById('tintSpecs');
    const vTypeSelect = document.querySelector('#createBookingModal select[name="vehicle_type_id"]');

    if (opt && opt.value) {
        let price = parseFloat(opt.dataset.price) || 0;
        if (vTypeSelect && vTypeSelect.selectedIndex > 0) {
            const vText = vTypeSelect.options[vTypeSelect.selectedIndex].text.toLowerCase();
            if (vText.includes('suv') || vText.includes('pickup')) {
                if (opt.dataset.suv) price = parseFloat(opt.dataset.suv);
            } else if (vText.includes('van')) {
                if (opt.dataset.van) price = parseFloat(opt.dataset.van);
            } else if (vText.includes('sedan') || vText.includes('hatchback')) {
                if (opt.dataset.sedan) price = parseFloat(opt.dataset.sedan);
            }
        }

        const type = opt.dataset.type || 'carbon';
        const shade = opt.dataset.shade || '35';
        const warranty = opt.dataset.warranty || '3';
        const install = opt.dataset.installation || '2-3 hours';

        if (priceEl) priceEl.value = price;
        ['selected_tint_type','selected_shade','selected_warranty','selected_installation']
            .forEach((id, i) => {
                const el = document.getElementById(id);
                if (el) el.value = [type, shade, warranty, install][i];
            });

        const tnames = {dyed:'Dyed',metalized:'Metalized',carbon:'Carbon',hybrid:'Hybrid',ceramic:'Ceramic','nano-ceramic':'Nano-Ceramic',crystalline:'Crystalline',infrared:'Infrared (IR)',security:'Security'};

        let typeBadgeHtml = '';
        if (type.includes(',')) {
            const typesArr = type.split(',').map(s=>s.trim());
            if (typesArr.length > 3) {
                typeBadgeHtml = `<span class="badge-pill badge-info" style="font-size:10px;background:#1e293b;color:#fff"><i class="fas fa-layer-group me-1"></i>Multi-Tint Spectrum (${typesArr.length} Series)</span>`;
            } else {
                typeBadgeHtml = typesArr.map(t=>`<span class="badge-pill tint-${t}" style="font-size:10px">${tnames[t]||t}</span>`).join(' ');
            }
        } else {
            typeBadgeHtml = `<span class="badge-pill tint-${type}" style="font-size:10px">${tnames[type]||type}</span>`;
        }

        specsEl.innerHTML = `
            <div style="display:flex;flex-wrap:wrap;gap:5px;align-items:center;">
                ${typeBadgeHtml}
                <span class="badge-pill badge-muted" style="font-size:10px">${shade}% Shade</span>
                <span class="badge-pill badge-info" style="font-size:10px"><i class="fas fa-shield-halved"></i> ${warranty}yr</span>
                <span class="badge-pill badge-muted" style="font-size:10px"><i class="fas fa-clock"></i> ${install}</span>
            </div>
            <div style="margin-top:8px;font-weight:800;font-size:16px;color:var(--success)">₱${parseFloat(price).toLocaleString('en-PH',{minimumFractionDigits:2})}</div>
        `;
    } else {
        if (priceEl) priceEl.value = '';
        specsEl.innerHTML = '<span style="color:var(--text-muted);font-size:12px">← Select a package</span>';
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const appDateInp = document.getElementById('appointment_date');
    if (!appDateInp) return;

    function updateWorkloadStatus() {
        const selectedDate = appDateInp.value;
        const badge = document.getElementById('bookingWorkloadBadge');
        const timeSelect = document.getElementById('appointment_time');

        if (!selectedDate) {
            if (badge) badge.style.display = 'none';
            return;
        }

        const totalsMap = window._bookingDateTotals || {};
        const count = totalsMap[selectedDate] || 0;

        if (badge) {
            badge.style.display = 'flex';
            if (count === 0) {
                badge.innerHTML = `<span style="color:#059669;background:#d1fae5;padding:3px 8px;border-radius:4px;"><i class="fas fa-calendar-check me-1"></i>0 Bookings scheduled on this day (Day is open)</span>`;
            } else {
                badge.innerHTML = `<span style="color:#b45309;background:#fef3c7;padding:3px 8px;border-radius:4px;"><i class="fas fa-calendar-days me-1"></i>${count} Booking${count > 1 ? 's' : ''} already scheduled on this day</span>`;
            }
        }

        if (timeSelect) {
            const slotMap = window._bookingSlotTotals || {};
            Array.from(timeSelect.options).forEach(opt => {
                if (!opt.value) return;
                const rawTime = opt.value;
                const key = selectedDate + '|' + rawTime;
                const bookedCount = slotMap[key] || 0;
                const baseLabel = opt.text.split(' (')[0];
                if (bookedCount > 0) {
                    opt.text = baseLabel + ` (${bookedCount} booked)`;
                } else {
                    opt.text = baseLabel + ` (Available)`;
                }
            });
        }
    }

    appDateInp.addEventListener('change', updateWorkloadStatus);
    appDateInp.addEventListener('input', updateWorkloadStatus);
    updateWorkloadStatus();
});

function calcTotal() {
    const q = parseFloat(document.getElementById('sale_quantity')?.value) || 0;
    const p = parseFloat(document.getElementById('sale_unit_price')?.value) || 0;
    const el = document.getElementById('sale_total_price');
    if (el) el.value = '₱' + (q * p).toLocaleString('en-PH', {minimumFractionDigits: 2});
}
document.getElementById('sale_quantity')?.addEventListener('input', calcTotal);
document.getElementById('sale_unit_price')?.addEventListener('input', calcTotal);
document.getElementById('sale_product_name')?.addEventListener('change', function() {
    const price = this.options[this.selectedIndex]?.getAttribute('data-price');
    if (price) { document.getElementById('sale_unit_price').value = price; calcTotal(); }
});

window.calcWinTotal = function() {
    let sum = 0;
    document.querySelectorAll('.sale-win-price').forEach(inp => {
        sum += parseFloat(inp.value || 0) || 0;
    });
    if (sum > 0) {
        const up = document.getElementById('sale_unit_price');
        if (up) { up.value = sum; calcTotal(); }
    }
};

window.clearWinPrices = function() {
    document.querySelectorAll('.sale-win-price').forEach(inp => { inp.value = ''; });
};

document.querySelector('[data-bs-target="#addSaleModal"]')?.addEventListener('click', () => {
    document.getElementById('saleForm')?.reset();
    document.getElementById('sale_id').value = '';
    document.getElementById('sales_action').value = 'add_sale';
    document.getElementById('saleModalTitle').textContent = 'Record New Sale';
    document.getElementById('saleSubmitBtn').innerHTML = '<i class="fas fa-floppy-disk"></i> Record Sale';
    var _sd = new Date(); document.getElementById('sale_date').value = _sd.getFullYear()+'-'+String(_sd.getMonth()+1).padStart(2,'0')+'-'+String(_sd.getDate()).padStart(2,'0');
    document.getElementById('sale_total_price').value = '';
    
    document.querySelectorAll('.sale-win-price').forEach(inp => { inp.value = ''; });
});

document.querySelector('[data-bs-target="#addTintStockModal"]')?.addEventListener('click', () => {
    document.getElementById('stock_action').value = 'add';
    document.getElementById('tintStockForm')?.reset();
    document.getElementById('stock_id').value = '';
    document.getElementById('width_cm').value = '152.4';
    document.getElementById('length_ft').value = '100';
    document.getElementById('min_rolls').value = '5';
    document.getElementById('heat_rejection').value = '50';
    document.getElementById('uv_protection').value = '99';
    document.getElementById('tint_warranty').value = '3';
    document.getElementById('tint_location').value = 'Main Storage';
    document.getElementById('shade_percentage').value = '';
    document.getElementById('price_per_roll').value = '';
    
    _setShadeMode('add');
    document.getElementById('tintStockModalTitle').textContent = 'Add Tint Film';
});

function _syncShadePrices() {
    document.querySelectorAll('#shadePriceGrid .shade-price-row').forEach(row => {
        const chk = row.querySelector('.shade-chk');
        const priceInp = row.querySelector('.shade-price-input');
        const stockInp = row.querySelector('.shade-stock-input');
        if (chk && priceInp) {
            const on = chk.checked;
            priceInp.disabled = !on;
            if (stockInp) stockInp.disabled = !on;
            if (!on) { priceInp.value = ''; if (stockInp) stockInp.value = ''; }
            row.classList.toggle('active', on);
        }
    });
}
window._syncShadePrices = _syncShadePrices;

function _setShadeMode(mode, shadeCsv, shadePricesJson) {
    const grid = document.getElementById('shadePriceGrid');
    if (!grid) return;
    const shadeSet = new Set((shadeCsv || '').split(',').map(s => s.trim()).filter(Boolean));
    let dataMap = {};
    if (typeof shadePricesJson === 'string') { try { dataMap = JSON.parse(shadePricesJson); } catch(e){} }
    else if (typeof shadePricesJson === 'object' && shadePricesJson) { dataMap = shadePricesJson; }

    grid.querySelectorAll('.shade-price-row').forEach(row => {
        const chk = row.querySelector('.shade-chk');
        const priceInp = row.querySelector('.shade-price-input');
        const stockInp = row.querySelector('.shade-stock-input');
        if (!chk || !priceInp) return;
        if (mode === 'edit') {
            row.style.display = '';
            chk.disabled = false;
            if (shadeSet.has(chk.value)) {
                const d = dataMap[chk.value];
                const price = (typeof d === 'object' && d) ? d.price : d;
                const stock = (typeof d === 'object' && d) ? d.stock : 0;
                chk.checked = true;
                priceInp.disabled = false;
                priceInp.value = price || '';
                if (stockInp) { stockInp.disabled = false; stockInp.value = stock || ''; }
                row.classList.add('active');
            } else {
                chk.checked = false;
                priceInp.disabled = true; priceInp.value = '';
                if (stockInp) { stockInp.disabled = true; stockInp.value = ''; }
                row.classList.remove('active');
            }
        } else {
            row.style.display = '';
            chk.checked = false; chk.disabled = false;
            priceInp.disabled = true; priceInp.value = '';
            if (stockInp) { stockInp.disabled = true; stockInp.value = ''; }
            row.classList.remove('active');
        }
    });
}
document.getElementById('tint_type')?.addEventListener('change', function() {
    
    if (document.getElementById('stock_action')?.value !== 'add') return;
    const map = window._shadePriceMap || {};
    const prices = map[this.value] || {};
    document.querySelectorAll('#shadePriceGrid .shade-price-input').forEach(inp => {
        const sh = inp.dataset.shade;
        if (prices[sh] && !inp.value) inp.placeholder = '₱' + Number(prices[sh]).toLocaleString();
        else inp.placeholder = 'Price / Roll';
    });
});

document.querySelector('[data-bs-target="#addAdminModal"]')?.addEventListener('click', () => {
    document.getElementById('admin_action').value = 'add';
    document.getElementById('adminForm')?.reset();
    document.getElementById('admin_id').value = '';
    document.getElementById('admin_username').readOnly = false;
    document.getElementById('passwordField').style.display = '';
    document.getElementById('admin_active_section').style.display = 'none';
    document.getElementById('adminModalTitle').textContent = 'Add Admin User';
    document.getElementById('adminSubmitBtn').innerHTML = '<i class="fas fa-user-plus"></i> Add User';
});

document.querySelector('[data-bs-target="#addPackageModal"]')?.addEventListener('click', () => {
    window.comboPrices     = {};
    window._selectedTypes  = [];
    window._selectedShades = {};
    window._focusedType    = null;
    window._currentCombo   = null;
    document.getElementById('package_action').value = 'add';
    document.getElementById('packageForm')?.reset();
    document.getElementById('package_id').value = '';
    document.getElementById('packageModalTitle').textContent = 'Add Package';
    document.querySelectorAll('.cm-checkbox').forEach(cb => {
        cb.checked = false;
        const row = cb.closest('.cm-row');
        if (row) row.classList.remove('checked');
    });
    const pi = document.getElementById('pkgPriceInput'); if (pi) pi.value = '';
    updateCmCount();
    _syncWinSection();
    const cs = document.getElementById('carModelSearch'); if (cs) { cs.value = ''; filterCarModels(''); }
    if (window._setWindowChips) window._setWindowChips([]);
    _syncTintTypeCards();
    _syncShadePills();
    if (window._currentCombo) _loadComboToUI(window._currentCombo);
    _updateComboUI();
    _updatePkgTotal();
    
    const imgPrev = document.getElementById('pkgImagePreview');
    if (imgPrev) { imgPrev.style.display = 'none'; imgPrev.src = ''; }
    if (typeof window.validatePackageName === 'function') window.validatePackageName();
});

(function initPackageNameValidation() {
    const input = document.getElementById('pkg_name');
    const form = document.getElementById('packageForm');
    const feedback = document.getElementById('pkgNameFeedback');
    if (!input || !form || !feedback) return;

    let existingPackages = [];
    try { existingPackages = JSON.parse(input.dataset.existingPackages || '[]'); } catch (e) {}

    window.validatePackageName = function() {
        const normalizedName = input.value.trim().toLocaleLowerCase();
        const currentId = String(document.getElementById('package_id')?.value || '');
        const duplicate = normalizedName !== '' && existingPackages.some(pkg =>
            String(pkg.id) !== currentId && String(pkg.name || '').trim().toLocaleLowerCase() === normalizedName
        );

        feedback.style.display = duplicate ? 'block' : 'none';
        input.setCustomValidity(duplicate ? 'This package name has already been used.' : '');
        input.style.borderColor = duplicate ? '#dc3545' : '';
        input.setAttribute('aria-invalid', duplicate ? 'true' : 'false');
        return !duplicate;
    };

    input.addEventListener('input', window.validatePackageName);
    input.addEventListener('blur', window.validatePackageName);
    form.addEventListener('submit', function(event) {
        if (!window.validatePackageName()) {
            event.preventDefault();
            input.reportValidity();
            input.focus();
        }
    });
})();

document.getElementById('pkgImageInput')?.addEventListener('change', function() {
        if (this.files && this.files[0]) {
        const reader = new FileReader();
        const preview = document.getElementById('pkgImagePreview');
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(this.files[0]);
    } else {
        const preview = document.getElementById('pkgImagePreview');
        preview.style.display = 'none'; preview.src = '';
    }
});

window.comboPrices     = {};
window._selectedTypes  = [];
window._selectedShades = {}; 
window._focusedType    = 'carbon';
window._currentCombo   = 'carbon|35';

function _saveComboFromUI(key) {
    if (!key) return;
    const wp = {};
    document.querySelectorAll('.win-chk:checked').forEach(cb => {
        const row = cb.closest('.win-price-row');
        const inp = row?.querySelector('.win-price-input');
        wp[cb.value] = parseFloat(inp?.value || 0) || 0;
    });
    const cars = [];
    document.querySelectorAll('.cm-checkbox:checked').forEach(cb => cars.push(cb.dataset.model));
    
    let winSum = Object.values(wp).reduce((a, b) => a + b, 0);
    const priceInput = document.getElementById('pkgPriceInput');
    const price = winSum > 0 ? winSum : (parseFloat(priceInput?.value || 0) || 0);
    if (Object.keys(wp).length || cars.length || price > 0) {
        window.comboPrices[key] = { windows: wp, cars, price };
    } else {
        delete window.comboPrices[key];
    }
    _updatePkgTotal();
}

function _loadComboToUI(key) {
    const data = window.comboPrices[key] || {};
    const wp = data.windows || {};
    let savedCars = Array.isArray(data.cars) ? data.cars : (data.car ? [data.car] : []);
    if (!savedCars.length && typeof data === 'object' && data !== null && !data.windows) {
        savedCars = Object.keys(data).filter(k => k !== 'windows' && k !== 'price' && k !== 'cars' && k !== 'car');
    }
    
    document.querySelectorAll('.cm-checkbox').forEach(cb => {
        const isMatch = savedCars.indexOf(cb.dataset.model) !== -1;
        cb.checked = isMatch;
        const row = cb.closest('.cm-row');
        if (row) row.classList.toggle('checked', isMatch);
    });
    updateCmCount();
    
    const winKeys = Object.keys(wp);
    document.querySelectorAll('.win-chk').forEach(cb => {
        const row = cb.closest('.win-price-row');
        const inp = row?.querySelector('.win-price-input');
        if (winKeys.includes(cb.value)) {
            cb.checked = true;
            if (inp) { inp.disabled = false; inp.value = wp[cb.value] || ''; }
            if (row) row.classList.add('active');
        } else {
            cb.checked = false;
            if (inp) { inp.disabled = true; inp.value = ''; }
            if (row) row.classList.remove('active');
        }
    });
    
    const winSum = Object.values(wp).reduce((a, b) => a + parseFloat(b || 0), 0);
    const priceInput = document.getElementById('pkgPriceInput');
    let fallbackPrice = data.price > 0 ? data.price : 0;
    if (!fallbackPrice && savedCars.length && typeof data[savedCars[0]] === 'number') {
        fallbackPrice = data[savedCars[0]];
    }
    if (priceInput) priceInput.value = winSum > 0 ? winSum : (fallbackPrice > 0 ? fallbackPrice : '');
    syncWindowsField();
    _syncWinSection();
}

const _tintLabels = {dyed:'Dyed',metalized:'Metalized',carbon:'Carbon',hybrid:'Hybrid',ceramic:'Ceramic','nano-ceramic':'Nano-Ceramic',crystalline:'Crystalline',infrared:'Infrared (IR)',security:'Security'};

function _updateComboUI() {
    const tt    = window._focusedType || null;
    const shades = tt ? (window._selectedShades[tt] || []) : [];
    const shade  = shades.length > 0 ? shades[shades.length - 1] : '?';
    const el    = document.getElementById('comboInfoText');
    const bar   = document.getElementById('comboInfoBar');
    if (tt && window._selectedTypes.length > 0) {
        if (bar) bar.style.display = 'flex';
        if (el) el.textContent = (_tintLabels[tt] || tt) + ' \u2014 ' + shade + '%';
    } else {
        if (bar) bar.style.display = 'none';
    }
    
    document.querySelectorAll('.tint-type-card').forEach(card => {
        const tv = card.dataset.value;
        const has = Object.keys(window.comboPrices).some(k => k.startsWith(tv + '|'));
        card.classList.toggle('ttc-has-data', has);
    });
    _renderShadeTotals();
}

function _comboTotal(data) {
    if (data && data.windows && Object.keys(data.windows).length > 0) {
        let total = 0;
        for (const w in data.windows) total += parseFloat(data.windows[w]) || 0;
        return total;
    }
    return parseFloat(data?.price || 0) || 0;
}

function _formatComboLabel(key) {
    const parts = String(key || '').split('|');
    const tt = parts[0] || 'carbon';
    const sh = parts[1] || '35';
    return (_tintLabels[tt] || tt) + ' — ' + sh + '%';
}

function _inventoryShadePrice(tintType, shade) {
    const priceMap = window._shadePriceMap || {};
    return parseFloat((priceMap[tintType] || {})[String(shade)] || 0) || 0;
}

function _renderShadeTotals() {
    const wrap = document.getElementById('pkgShadeTotalsList');
    if (!wrap) return;
    const keys = [];
    (window._selectedTypes || []).forEach(tt => {
        (window._selectedShades[tt] || []).forEach(sh => keys.push(tt + '|' + sh));
    });
    if (!keys.length) {
        wrap.innerHTML = '<div class="pkg-shade-total-empty">Select tint type, shade, and windows to see totals.</div>';
        return;
    }
    wrap.innerHTML = keys.map(key => {
        const data = window.comboPrices[key] || {};
        const total = _comboTotal(data);
        const cars = Array.isArray(data.cars) ? data.cars : (data.car ? [data.car] : []);
        const carsCount = cars.length;
        const car = carsCount === 1 ? cars[0] : (carsCount > 1 ? carsCount + ' cars' : 'No car selected');
        const parts = String(key).split('|');
        const invPrice = _inventoryShadePrice(parts[0] || 'carbon', parts[1] || '35');
        const isActive = key === window._currentCombo;
        const escapedKey = key.replace(/'/g, "\\'");
        return '<div class="pkg-shade-total-item' + (isActive ? ' active' : '') + '" onclick="_switchCombo(\'' + escapedKey + '\')" title="Click to edit this combo">' +
            '<div class="pkg-shade-total-head">' +
                '<span class="pkg-shade-total-name">' + _formatComboLabel(key) + '</span>' +
                '<span class="pkg-shade-total-price">' + (total > 0 ? '₱' + total.toLocaleString() : '<span style="color:rgba(255,255,255,0.25);font-size:10px;">no price</span>') + '</span>' +
            '</div>' +
            '<div class="pkg-shade-total-sub">' +
                '<span style="color:rgba(255,255,255,0.3);">Inv ₱' + invPrice.toLocaleString() + '</span>' +
                '<span>•</span>' +
                '<span>' + carsCount + (carsCount === 1 ? ' tint' : ' tints') + '</span>' +
                '<span>•</span>' +
                '<span style="max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + (car !== 'No car selected' ? car : '<em style="opacity:.35">no car</em>') + '</span>' +
            '</div>' +
        '</div>';
    }).join('');
}

window._switchCombo = function(key) {
    _saveComboFromUI(window._currentCombo);
    window._currentCombo = key;
    const parts = String(key).split('|');
    const newType = parts[0];
    
    if (window._selectedTypes.indexOf(newType) === -1) { window._selectedTypes.push(newType); }
    window._focusedType = newType;
    
    const sh = parseInt(parts[1] || 35);
    if (!window._selectedShades[newType]) window._selectedShades[newType] = [];
    if (window._selectedShades[newType].indexOf(sh) === -1) window._selectedShades[newType].push(sh);
    _syncTintTypeCards();
    _syncShadePills();
    _loadComboToUI(key);
    _updateComboUI();
    _updatePkgTotal();
};

window._syncTintTypeCards = _syncTintTypeCards;
window._syncShadePills = _syncShadePills;
window._loadComboToUI = _loadComboToUI;
window._updateComboUI = _updateComboUI;
window._updatePkgTotal = _updatePkgTotal;

function _updatePkgTotal() {
    let total = 0;
    for (const key in window.comboPrices) {
        const d = window.comboPrices[key];
        total += _comboTotal(d);
    }
    const el = document.getElementById('pkgTotalAmount');
    if (el) el.textContent = '\u20b1' + total.toLocaleString();
    const currentEl = document.getElementById('pkgCurrentComboAmount');
    if (currentEl) currentEl.textContent = '\u20b1' + _comboTotal(window.comboPrices[window._currentCombo] || {}).toLocaleString();
    const ov = document.getElementById('pkgTotalOverride');
    if (ov && !ov.value) ov.placeholder = total > 0 ? total.toLocaleString() : 'auto';
    _renderShadeTotals();
}

function _syncTintTypeCards() {
    const rolls = window._tintRollsMap || {};
    document.querySelectorAll('.tint-type-card').forEach(c => {
        const v = c.dataset.value;
        c.classList.toggle('selected', window._selectedTypes.indexOf(v) !== -1);
        c.classList.toggle('focused', v === window._focusedType);
        
        const r = parseInt(c.dataset.rolls || '0');
        c.classList.toggle('no-stock', r === 0);
    });
    
    const h = document.getElementById('pkg_tint_type');
    if (h) h.value = window._selectedTypes.join(',');
}

function _updateShadeBadges(tintType) {
    const map = window._shadeStockMap || {};
    const typeData = map[tintType] || {};
    [70,50,35,20,5].forEach(sh => {
        const el = document.getElementById('shadeBadge_' + sh);
        const pill = el?.closest('.shade-pill');
        if (!el) return;
        const stock = typeData[String(sh)] || 0;
        el.textContent = stock;
        el.className = 'shade-stock-badge' + (stock <= 0 ? ' out' : (stock <= 5 ? ' low' : ''));
        
        if (pill) {
            pill.classList.toggle('disabled', stock <= 0);
            const chk = pill.querySelector('.shade-chk');
            if (chk && stock <= 0) { chk.checked = false; chk.disabled = true; }
            else if (chk) { chk.disabled = false; }
        }
    });
    
    const priceEl = document.getElementById('ttcPrice_' + tintType);
    if (priceEl) {
        const priceMap = window._shadePriceMap || {};
        const typePrices = priceMap[tintType] || {};
        const vals = Object.values(typePrices).map(Number).filter(v => v > 0);
        if (vals.length) {
            const avg = Math.round(vals.reduce((a,b)=>a+b,0) / vals.length);
            priceEl.textContent = '\u20b1' + avg.toLocaleString();
        } else {
            priceEl.textContent = '';
        }
    }
}

function _syncShadePills() {
    const tt = window._focusedType;
    const shades = window._selectedShades[tt] || [];
    _updateShadeBadges(tt);
    document.querySelectorAll('.shade-chk').forEach(chk => {
        const pill = chk.closest('.shade-pill');
        if (pill && !pill.classList.contains('disabled')) {
            chk.checked = shades.indexOf(parseInt(chk.value)) !== -1;
        }
    });
    
    const h = document.getElementById('pkg_shade_percentage');
    if (h) h.value = JSON.stringify(window._selectedShades);
}

window.toggleTintType = function(val) {
    
    const card = document.querySelector('.tint-type-card[data-value="' + val + '"]');
    if (card && (parseInt(card.dataset.rolls || 0) === 0 || card.classList.contains('no-stock'))) return;
    _saveComboFromUI(window._currentCombo);
    const prevCombo = window._currentCombo; 
    const idx = window._selectedTypes.indexOf(val);
    if (idx === -1) {
        
        window._selectedTypes.push(val);
        window._focusedType = val;
        
        if (!window._selectedShades[val] || window._selectedShades[val].length === 0) {
            const stock = (window._shadeStockMap || {})[val] || {};
            const first = [70,50,35,20,5].find(s => (stock[String(s)] || 0) > 0) || 35;
            window._selectedShades[val] = [first];
        }
        
        const newShade = (window._selectedShades[val] || [])[0] || 35;
        const newKey = val + '|' + newShade;
        if (!window.comboPrices[newKey]) {
            if (prevCombo && window.comboPrices[prevCombo]) {
                
                const prev = window.comboPrices[prevCombo];
                window.comboPrices[newKey] = {
                    windows: Object.assign({}, prev.windows || {}),
                    cars: (prev.cars || []).slice(),
                    price: prev.price || 0
                };
            } else {
                
                const wp = {};
                document.querySelectorAll('.win-chk:checked').forEach(cb => {
                    const row = cb.closest('.win-price-row');
                    const inp = row?.querySelector('.win-price-input');
                    wp[cb.value] = parseFloat(inp?.value || 0) || 0;
                });
                const cars = [];
                document.querySelectorAll('.cm-checkbox:checked').forEach(cb => cars.push(cb.dataset.model));
                const pi = document.getElementById('pkgPriceInput');
                const winSum = Object.values(wp).reduce((a, b) => a + b, 0);
                const price = winSum > 0 ? winSum : (parseFloat(pi?.value || 0) || 0);
                if (cars.length || price > 0 || Object.keys(wp).length) {
                    window.comboPrices[newKey] = { windows: wp, cars, price };
                }
            }
        }
    } else if (window._focusedType === val && window._selectedTypes.length > 1) {
        
        window._selectedTypes.splice(idx, 1);
        delete window._selectedShades[val];
        
        Object.keys(window.comboPrices).forEach(k => { if (k.startsWith(val + '|')) delete window.comboPrices[k]; });
        window._focusedType = window._selectedTypes[0];
    } else if (window._selectedTypes.length === 1 && window._selectedTypes[0] === val) {
        
        window._selectedTypes = [];
        delete window._selectedShades[val];
        Object.keys(window.comboPrices).forEach(k => { if (k.startsWith(val + '|')) delete window.comboPrices[k]; });
        window._focusedType = null;
        window._currentCombo = null;
        _syncTintTypeCards();
        _syncShadePills();
        _updateComboUI();
        _updatePkgTotal();
        return;
    } else {
        
        window._focusedType = val;
    }
    _syncTintTypeCards();
    _syncShadePills();
    
    const shades = window._selectedShades[window._focusedType] || [];
    window._currentCombo = window._focusedType + '|' + (shades[0] || 35);
    _loadComboToUI(window._currentCombo);
    _updateComboUI();
    _updatePkgTotal();
};

window.onShadeToggle = function(chk) {
    _saveComboFromUI(window._currentCombo);
    const tt = window._focusedType;
    const sv = parseInt(chk.value);
    if (!window._selectedShades[tt]) window._selectedShades[tt] = [];
    const arr = window._selectedShades[tt];
    const idx = arr.indexOf(sv);
    if (chk.checked && idx === -1) {
        arr.push(sv);
        window._currentCombo = tt + '|' + sv;
    } else if (!chk.checked && idx !== -1) {
        arr.splice(idx, 1);
        
        delete window.comboPrices[tt + '|' + sv];
        
        if (arr.length > 0) {
            window._currentCombo = tt + '|' + arr[arr.length - 1];
        }
    }
    _syncShadePills();
    _loadComboToUI(window._currentCombo);
    _updateComboUI();
    _updatePkgTotal();
};

window.selectTintType = window.toggleTintType;
window.onShadeChange = function(v) {  };

window.syncWindowsField = function() {
    var vals = [];
    var total = 0;
    document.querySelectorAll('.win-chk').forEach(function(cb) {
        var row = cb.closest('.win-price-row');
        var inp = row ? row.querySelector('.win-price-input') : null;
        if (cb.checked) {
            vals.push(cb.value);
            if (inp) { inp.disabled = false; total += parseFloat(inp.value || 0); }
            if (row) row.classList.add('active');
        } else {
            if (inp) { inp.disabled = true; inp.value = ''; }
            if (row) row.classList.remove('active');
        }
    });
    var h = document.getElementById('pkg_windows_included');
    if (h) h.value = vals.join(',');
    
    var pi = document.getElementById('pkgPriceInput');
    if (pi && total > 0) {
        pi.value = total;
        if (typeof _saveComboFromUI === 'function' && window._currentCombo) _saveComboFromUI(window._currentCombo);
        if (typeof _updatePkgTotal === 'function') _updatePkgTotal();
    }
    
    var td = document.getElementById('winTotalDisplay');
    if (td) {
        if (total > 0) { td.textContent = 'Total: \u20b1' + total.toLocaleString(); td.style.display = ''; }
        else if (vals.length > 0) { td.textContent = 'Total: \u20b10'; td.style.display = ''; }
        else { td.style.display = 'none'; }
    }
    
    var wp = {};
    document.querySelectorAll('.win-chk:checked').forEach(function(cb) {
        var row = cb.closest('.win-price-row');
        var inp = row ? row.querySelector('.win-price-input') : null;
        wp[cb.value] = parseFloat(inp ? inp.value || 0 : 0);
    });
    var wj = document.getElementById('windowPricesJson');
    if (wj) wj.value = JSON.stringify(wp);
};

document.getElementById('winPriceList')?.addEventListener('input', function(e) {
    if (e.target.classList.contains('win-price-input')) {
        syncWindowsField();
        _saveComboFromUI(window._currentCombo);
        _updatePkgTotal();
    }
});

document.getElementById('winPriceList')?.addEventListener('change', function(e) {
    if (e.target.classList.contains('win-chk')) {
        setTimeout(function() { _saveComboFromUI(window._currentCombo); _updatePkgTotal(); }, 50);
    }
});
window._setWindowChips = function(data) {
    var arr = [], prices = {};
    if (typeof data === 'string') { try { data = JSON.parse(data || '[]'); } catch(e) { data = []; } }
    if (Array.isArray(data)) { arr = data; }
    else if (typeof data === 'object' && data !== null) { arr = Object.keys(data); prices = data; }
    document.querySelectorAll('.win-chk').forEach(function(cb) {
        var checked = arr.indexOf(cb.value) !== -1;
        cb.checked = checked;
        var row = cb.closest('.win-price-row');
        var inp = row ? row.querySelector('.win-price-input') : null;
        if (checked) {
            if (inp) { inp.disabled = false; inp.value = prices[cb.value] || ''; }
            if (row) row.classList.add('active');
        } else {
            if (inp) { inp.disabled = true; inp.value = ''; }
            if (row) row.classList.remove('active');
        }
    });
    syncWindowsField();
};

document.getElementById('packageForm')?.addEventListener('submit', function(e) {
    
    if (typeof _saveComboFromUI === 'function' && window._currentCombo) {
        _saveComboFromUI(window._currentCombo);
    }

    
    var types = window._selectedTypes && window._selectedTypes.length ? window._selectedTypes : [];
    if (!types.length) {
        var activeCards = document.querySelectorAll('.tint-type-card.selected');
        if (activeCards.length) {
            activeCards.forEach(function(c) { types.push(c.dataset.value); });
        } else {
            types = ['carbon'];
        }
        window._selectedTypes = types;
    }
    var tintTypeInput = document.getElementById('pkg_tint_type');
    if (tintTypeInput) tintTypeInput.value = types.join(',');

    var shadeInput = document.getElementById('pkg_shade_percentage');
    if (shadeInput) {
        if (!window._selectedShades || !Object.keys(window._selectedShades).length) {
            window._selectedShades = {};
            types.forEach(function(t) { window._selectedShades[t] = [35]; });
        }
        shadeInput.value = JSON.stringify(window._selectedShades);
    }

    
    var allCars = [];
    document.querySelectorAll('.cm-checkbox:checked').forEach(function(cb) {
        if (cb.dataset.model) allCars.push(cb.dataset.model);
    });

    var winPrices = {};
    document.querySelectorAll('.win-chk:checked').forEach(function(cb) {
        var row = cb.closest('.win-price-row');
        var inp = row ? row.querySelector('.win-price-input') : null;
        winPrices[cb.value] = parseFloat(inp ? inp.value || 0 : 0) || 0;
    });

    var priceVal = parseFloat(document.getElementById('pkgPriceInput')?.value || 0);
    if (!priceVal) {
        var wTotal = Object.values(winPrices).reduce(function(a, b) { return a + b; }, 0);
        if (wTotal > 0) priceVal = wTotal;
    }

    
    if (!window.comboPrices || Object.keys(window.comboPrices).length === 0) {
        window.comboPrices = {};
        types.forEach(function(tt) {
            var shades = (window._selectedShades && window._selectedShades[tt] && window._selectedShades[tt].length) ? window._selectedShades[tt] : [35];
            shades.forEach(function(sh) {
                var key = tt + '|' + sh;
                window.comboPrices[key] = {
                    windows: winPrices,
                    cars: allCars,
                    price: priceVal
                };
            });
        });
    } else {
        
        types.forEach(function(tt) {
            var shades = (window._selectedShades && window._selectedShades[tt] && window._selectedShades[tt].length) ? window._selectedShades[tt] : [35];
            shades.forEach(function(sh) {
                var key = tt + '|' + sh;
                if (!window.comboPrices[key]) {
                    window.comboPrices[key] = {
                        windows: winPrices,
                        cars: allCars,
                        price: priceVal
                    };
                } else {
                    if ((!window.comboPrices[key].cars || !window.comboPrices[key].cars.length) && allCars.length) {
                        window.comboPrices[key].cars = allCars;
                    }
                    if ((!window.comboPrices[key].price || window.comboPrices[key].price <= 0) && priceVal > 0) {
                        window.comboPrices[key].price = priceVal;
                    }
                }
            });
        });
    }

    
    var comboJsonInput = document.getElementById('comboPricesJson');
    if (comboJsonInput) {
        comboJsonInput.value = JSON.stringify(window.comboPrices || {});
    }

    
    if (typeof syncWindowsField === 'function') syncWindowsField();
});

var _winByCat = {
    sedan:  ['windshield','front_win','rear_win','rear_ws','sunstrip'],
    suv:    ['windshield','front_win','side_win','rear_ws','sunstrip'],
    van:    ['windshield','front_win','side_win','rear_ws','sunstrip'],
    pickup: ['windshield','front_win','rear_ws','sunstrip']
};

function _autoWindowsFromCars() {
    var checkedCats = [];
    document.querySelectorAll('.cm-checkbox:checked').forEach(function(cb) {
        var cat = cb.dataset.cat || 'sedan';
        if (checkedCats.indexOf(cat) === -1) checkedCats.push(cat);
    });
    if (checkedCats.length === 0) return;
    var wins = {};
    checkedCats.forEach(function(cat) {
        (_winByCat[cat] || _winByCat.sedan).forEach(function(w) { wins[w] = true; });
    });
    document.querySelectorAll('.win-chk').forEach(function(cb) {
        var row = cb.closest('.win-price-row');
        var inp = row ? row.querySelector('.win-price-input') : null;
        cb.checked = !!wins[cb.value];
        if (cb.checked) { if (inp) inp.disabled = false; }
        else { if (inp) { inp.disabled = true; inp.value = ''; } }
    });
    syncWindowsField();
}

function _syncWinSection() {
    const count = document.querySelectorAll('.cm-checkbox:checked').length;
    const isMulti = count > 1;
    const isSingle = count === 1;
    const pkgPriceInput = document.getElementById('pkgPriceInput');
    const winList = document.getElementById('winPriceList');
    const hint = document.getElementById('winSectionHint');

    
    if (isMulti) {
        
        if (winList) {
            winList.style.opacity = '0.35';
            winList.style.pointerEvents = 'none';
        }
        
        document.querySelectorAll('.win-chk').forEach(cb => {
            cb.checked = false;
            const row = cb.closest('.win-price-row');
            const inp = row?.querySelector('.win-price-input');
            if (inp) { inp.disabled = true; inp.value = ''; }
            if (row) row.classList.remove('active');
        });
        var h = document.getElementById('pkg_windows_included');
        if (h) h.value = '';
        var wj = document.getElementById('windowPricesJson');
        if (wj) wj.value = '{}';
        var td = document.getElementById('winTotalDisplay');
        if (td) td.style.display = 'none';
        
        if (pkgPriceInput) {
            pkgPriceInput.disabled = false;
            pkgPriceInput.style.opacity = '1';
            pkgPriceInput.style.background = '';
        }
        if (hint) { hint.textContent = '\u2139\uFE0F Multi-car selected \u2014 enter Package Price / Car directly'; hint.style.display = ''; }

    
    } else if (isSingle) {
        if (winList) {
            winList.style.opacity = '1';
            winList.style.pointerEvents = '';
        }
        
        if (pkgPriceInput) {
            pkgPriceInput.disabled = true;
            pkgPriceInput.style.opacity = '0.6';
            pkgPriceInput.style.background = 'rgba(0,0,0,0.2)';
        }
        if (hint) { hint.textContent = '\u2139\uFE0F Single car \u2014 check windows & enter prices, Package Price fills automatically'; hint.style.display = ''; }

    
    } else {
        if (winList) { winList.style.opacity = '0.35'; winList.style.pointerEvents = 'none'; }
        if (pkgPriceInput) {
            pkgPriceInput.disabled = false;
            pkgPriceInput.style.opacity = '1';
            pkgPriceInput.style.background = '';
        }
        if (hint) hint.style.display = 'none';
    }
}

window.toggleCmRow = function(cb) {
    const row = cb.closest('.cm-row');
    if (!row) return;
    if (cb.checked) {
        row.classList.add('checked');
    } else {
        row.classList.remove('checked');
    }
    updateCmCount();
    _syncWinSection();
    const count = document.querySelectorAll('.cm-checkbox:checked').length;
    if (count <= 1) _autoWindowsFromCars();
    _saveComboFromUI(window._currentCombo);
    _updatePkgTotal();
};

function updateCmCount() {
    const checked = document.querySelectorAll('.cm-checkbox:checked');
    const el = document.getElementById('cmCheckedCount');
    if (el) {
        if (checked.length === 0) el.textContent = 'None selected';
        else if (checked.length === 1) el.textContent = checked[0].dataset.model;
        else el.textContent = checked.length + ' cars selected';
    }
}

window.selectAllCarModels = function() {
    document.querySelectorAll('.cm-checkbox:not([disabled])').forEach(cb => {
        cb.checked = true;
        const row = cb.closest('.cm-row'); if (row) row.classList.add('checked');
    });
    updateCmCount();
    _syncWinSection();
    _saveComboFromUI(window._currentCombo);
    _updatePkgTotal();
};

window.clearAllCarModels = function() {
    document.querySelectorAll('.cm-checkbox').forEach(cb => {
        cb.checked = false;
        const row = cb.closest('.cm-row'); if (row) row.classList.remove('checked');
    });
    updateCmCount();
    _syncWinSection();
    _saveComboFromUI(window._currentCombo);
    _updatePkgTotal();
};

(function(){
    
    function _ld(a,b){
        if(a===b) return 0;
        const la=a.length, lb=b.length;
        if(!la) return lb; if(!lb) return la;
        if(Math.abs(la-lb)>4) return 99;
        let prev=Array.from({length:lb+1},(_,i)=>i), curr=[];
        for(let i=1;i<=la;i++){
            curr[0]=i;
            for(let j=1;j<=lb;j++){
                curr[j]=a[i-1]===b[j-1]?prev[j-1]:1+Math.min(prev[j-1],prev[j],curr[j-1]);
            }
            [prev,curr]=[curr,[]];
        }
        return prev[lb];
    }
    
    function _score(name, term){
        if(!term) return 0;                       
        if(name.includes(term)) return 0;          
        
        const tw=term.split(/\s+/), nw=name.split(/\s+/);
        let total=0, matched=0;
        for(const t of tw){
            let best=99;
            for(const n of nw){
                
                if(n.startsWith(t)) { best=0; break; }
                
                const sub=n.substring(0,Math.max(t.length,3));
                const d=_ld(t,sub);
                if(d<best) best=d;
                
                if(t.length<=n.length+2){
                    const d2=_ld(t,n);
                    if(d2<best) best=d2;
                }
            }
            if(best<=2){ matched++; total+=best; }
        }
        return matched===tw.length ? total : -1;   
    }
    window.filterCarModels = function(q){
        const term=q.toLowerCase().trim();
        const list=document.getElementById('carModelPriceList');
        const rows=[...list.querySelectorAll('.cm-row')];
        let scored=rows.map(r=>({r, s:_score(r.dataset.name||'', term)}));
        
        scored.forEach(o=>{ o.r.style.display = o.s===-1 ? 'none' : ''; });
        scored.filter(o=>o.s!==-1).sort((a,b)=>a.s-b.s).forEach(o=>list.appendChild(o.r));
    };
})();

window.applyBulkPrice = function() {};

document.querySelectorAll('.quick-update-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        document.getElementById('quickUpdateForm_' + id).style.display = 'flex';
        this.style.display = 'none';
    });
});
window.cancelQuickUpdate = function(id) {
    document.getElementById('quickUpdateForm_' + id).style.display = 'none';
    const toggle = document.querySelector(`.quick-update-toggle[data-id="${id}"]`);
    if (toggle) toggle.style.display = '';
};

document.addEventListener('click', function(e) {

    
    const viewBtn = e.target.closest('.view-btn');
    if (viewBtn) {
        const d = viewBtn.dataset;
        const rawStatus = (d.status || '').trim();
        const normStatus = rawStatus.charAt(0).toUpperCase() + rawStatus.slice(1).toLowerCase();
        const sc = {
            Pending:'badge-pending', Approved:'badge-approved', Completed:'badge-completed',
            Cancelled:'badge-cancelled', Declined:'badge-declined', Confirmed:'badge-approved',
            'On hold':'badge-pending'
        };
        const si = {
            Pending:'fa-hourglass-half', Approved:'fa-thumbs-up', Completed:'fa-circle-check',
            Cancelled:'fa-ban', Declined:'fa-times-circle', Confirmed:'fa-thumbs-up',
            'On hold':'fa-circle'
        };

        window._currentBookingDataset = d;
        document.getElementById('m_booking_id').value = d.id;
        document.getElementById('decline_booking_id').value = d.id;
        document.getElementById('delete_booking_id').value = d.id;
        document.getElementById('m_name').textContent = d.name;
        document.getElementById('m_phone').textContent = d.phone || 'Not provided';
        document.getElementById('m_address').textContent = d.address || 'Not provided';
        document.getElementById('m_car').textContent = d.car;
        document.getElementById('m_year').textContent = d.year ? `Year: ${d.year}` : '';
        document.getElementById('m_type').textContent = d.type;
        document.getElementById('m_color').textContent = d.color ? `Color: ${d.color}` : '';
        document.getElementById('m_package').textContent = d.package;
        document.getElementById('m_price').textContent = '₱' + d.price;
        document.getElementById('m_date').textContent = d.date;
        document.getElementById('m_time').textContent = d.time;
        document.getElementById('m_installation').innerHTML = '<i class="fas fa-clock" style="margin-right:3px"></i>' + (d.installation || '2-3 hours');
        document.getElementById('m_payment_method').textContent = d.paymentMethod || 'GCash';
        document.getElementById('m_payment_type').textContent = d.paymentType === 'downpayment' ? 'Down Payment' : 'Full Payment';
        document.getElementById('m_payment_status').textContent = d.paymentStatus || 'Pending';

        
        const warrantyContainer = document.getElementById('m_warranty_download_container');
        const warrantyLink = document.getElementById('m_warranty_download_link');
        if (warrantyContainer && warrantyLink) {
            if (normStatus === 'Completed') {
                warrantyLink.href = '../download_warranty.php?id=' + d.id;
                warrantyContainer.style.display = '';
            } else {
                warrantyContainer.style.display = 'none';
            }
        }

        
        if (normStatus === 'Declined' && d.rejectionReason) {
            document.getElementById('decline_info_container').style.display = '';
            document.getElementById('m_rejection_reason').textContent = d.rejectionReason;
            document.getElementById('m_rejected_by').textContent = d.rejectedBy ? `Rejected by: ${d.rejectedBy}` : '';
            document.getElementById('actionForm').style.display = 'none';
            document.getElementById('declineForm').style.display = 'none';
        } else {
            document.getElementById('decline_info_container').style.display = 'none';
            document.getElementById('actionForm').style.display = '';
            document.getElementById('declineForm').style.display = '';
        }

        const refContainer = document.getElementById('payment_reference_container');
        if (d.paymentReference) {
            document.getElementById('m_payment_reference').textContent = d.paymentReference;
            refContainer.style.display = '';
        } else { refContainer.style.display = 'none'; }

        const tb = document.getElementById('m_tint_type_badge');
        if (tb) {
            tb.textContent = (d.tintType || 'carbon').charAt(0).toUpperCase() + (d.tintType || 'carbon').slice(1);
            tb.className = 'badge-pill tint-' + (d.tintType || 'carbon');
        }
        document.getElementById('m_shade').textContent = (d.shade || '35') + '% Shade';
        document.getElementById('m_warranty_badge').innerHTML = `<i class="fas fa-shield-halved" style="margin-right:3px"></i>${d.warranty || 3} Year Warranty`;

        const sb = document.getElementById('m_status_badge');
        if (sb) {
            sb.className = 'badge-pill ' + (sc[normStatus] || 'badge-muted');
            sb.innerHTML = `<i class="fas ${si[normStatus] || 'fa-circle'}" style="margin-right:6px"></i>${normStatus}`;
        }

        
        const vtb = document.getElementById('viewTransferBtn');
        if (vtb) {
            vtb.dataset.id      = d.id;
            vtb.dataset.name    = d.name;
            vtb.dataset.date    = d.date;
            vtb.dataset.time    = d.time;
            vtb.dataset.vehicle = (d.car || '') + (d.year ? ' (' + d.year + ')' : '');
        }

        
        var isLocked = (normStatus === 'Completed' || normStatus === 'Cancelled' || normStatus === 'Declined');
        var actionForm = document.getElementById('actionForm');
        var declineForm = document.getElementById('declineForm');
        var deleteForm = document.getElementById('deleteBookingForm') || (actionForm ? actionForm.closest('.bk-right').querySelector('[data-rg-confirm]') : null);

        
        if (actionForm) {
            actionForm.querySelectorAll('button').forEach(function(btn){ btn.disabled = isLocked; btn.style.opacity = isLocked ? '.4' : ''; btn.style.pointerEvents = isLocked ? 'none' : ''; });
            if (normStatus !== 'Declined') actionForm.style.display = isLocked ? '' : '';
        }
        
        if (vtb) { vtb.disabled = isLocked; vtb.style.opacity = isLocked ? '.4' : ''; vtb.style.pointerEvents = isLocked ? 'none' : ''; }
        
        if (declineForm && normStatus !== 'Declined') {
            declineForm.querySelectorAll('button,textarea').forEach(function(el){ el.disabled = isLocked; el.style.opacity = isLocked ? '.4' : ''; el.style.pointerEvents = isLocked ? 'none' : ''; });
        }
        
        if (deleteForm) {
            deleteForm.querySelectorAll('button').forEach(function(btn){ btn.disabled = false; btn.style.opacity = '1'; btn.style.pointerEvents = 'auto'; });
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('viewModal')).show();
    }

    
    const priceBtn = e.target.closest('.price-btn');
    if (priceBtn) {
        document.getElementById('price_package_id').value = priceBtn.dataset.id;
        document.getElementById('price_package_name').textContent = priceBtn.dataset.name;
        document.getElementById('current_price_display').innerHTML = `Current price: ₱${parseFloat(priceBtn.dataset.current).toLocaleString()}`;
        document.getElementById('new_price').value = priceBtn.dataset.current;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('priceModal')).show();
    }

    
    const editPackageBtn = e.target.closest('.edit-package-btn');
    if (editPackageBtn) {
        try {
            const d = editPackageBtn.dataset;
            document.getElementById('package_action').value = 'update';
            document.getElementById('package_id').value = d.id;
            document.getElementById('pkg_name').value = d.name || '';

            
            const descTxt = document.createElement('textarea');
            descTxt.innerHTML = d.description || '';
            document.getElementById('pkg_description').value = descTxt.value;

            
            try { window.comboPrices = JSON.parse(d.comboPrices || '{}'); } catch(e) { window.comboPrices = {}; }
            for (const comboKey in window.comboPrices) {
                const rawVal = window.comboPrices[comboKey];
                if (typeof rawVal === 'object' && rawVal !== null && !rawVal.cars && !rawVal.windows) {
                    const carKeys = Object.keys(rawVal);
                    const samplePrice = carKeys.length ? parseFloat(rawVal[carKeys[0]] || 0) : 0;
                    window.comboPrices[comboKey] = {
                        cars: carKeys,
                        price: samplePrice,
                        windows: {}
                    };
                }
            }
            
            const types = (d.tintType || 'carbon').split(',').map(s => s.trim()).filter(Boolean);
            window._selectedTypes = types;
            window._focusedType = types[0] || 'carbon';
            
            let shadeData = {};
            try { shadeData = JSON.parse(d.shade || '{}'); } catch(e) {}
            if (typeof shadeData !== 'object' || shadeData === null || Array.isArray(shadeData)) {
                const sv = parseInt(d.shade) || 35;
                shadeData = {};
                types.forEach(t => { shadeData[t] = [sv]; });
            }
            window._selectedShades = shadeData;
            
            const firstShades = shadeData[window._focusedType] || [35];
            window._currentCombo = window._focusedType + '|' + firstShades[0];
            try { if (typeof window._syncTintTypeCards === 'function') window._syncTintTypeCards(); } catch(e) {}
            try { if (typeof window._syncShadePills === 'function') window._syncShadePills(); } catch(e) {}
            try { if (typeof window._loadComboToUI === 'function') window._loadComboToUI(window._currentCombo); } catch(e) {}
            try { if (typeof window._updateComboUI === 'function') window._updateComboUI(); } catch(e) {}
            try { if (typeof window._updatePkgTotal === 'function') window._updatePkgTotal(); } catch(e) {}
            const cs = document.getElementById('carModelSearch'); if (cs) { cs.value = ''; try { if (typeof filterCarModels === 'function') filterCarModels(''); } catch(e){} }
            const pInst = document.getElementById('pkg_install'); if (pInst) pInst.value = d.installation || '';
            const pWarr = document.getElementById('pkg_warranty'); if (pWarr) pWarr.value = d.warranty || '3';
            const pOrd = document.getElementById('pkg_order'); if (pOrd) pOrd.value = d.order || '0';
            try {
                const feats = JSON.parse(d.features);
                document.getElementById('pkg_features').value = Array.isArray(feats) ? feats.join('\n') : d.features;
            } catch(e) {
                const pFeat = document.getElementById('pkg_features'); if (pFeat) pFeat.value = d.features || '';
            }
            const pAct = document.getElementById('pkg_active'); if (pAct) pAct.checked = d.active == '1';
            const pFeat = document.getElementById('pkg_featured'); if (pFeat) pFeat.checked = d.featured == '1';
            if (window._setWindowChips) try { window._setWindowChips(d.windows || '[]'); } catch(e){}
            
            const imgPrev = document.getElementById('pkgImagePreview');
            if (imgPrev) {
                if (d.imageUrl && d.imageUrl !== '/images/tint-packages/default-tint.jpg') {
                    imgPrev.src = d.imageUrl;
                    imgPrev.style.display = 'block';
                } else {
                    imgPrev.style.display = 'none'; imgPrev.src = '';
                }
            }
            const pTitle = document.getElementById('packageModalTitle'); if (pTitle) pTitle.textContent = 'Edit Package';
        } catch(err) {
            console.error('Error populating edit package modal:', err);
        }
        const modalEl = document.getElementById('addPackageModal');
        if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    
    const editSaleBtn = e.target.closest('.edit-sale-btn');
    if (editSaleBtn) {
        const d = editSaleBtn.dataset;
        document.getElementById('sales_action').value = 'update';
        document.getElementById('sale_id').value = d.id;
        document.getElementById('sale_customer_name').value = d.customer;
        document.getElementById('sale_product_name').value = d.product;
        document.getElementById('sale_quantity').value = d.quantity;
        document.getElementById('sale_unit_price').value = d.unitprice;
        document.getElementById('sale_payment_method').value = d.paymentmethod;
        document.getElementById('sale_payment_status').value = d.paymentstatus;
        document.getElementById('sale_date').value = d.saledate;
        calcTotal();
        document.getElementById('saleModalTitle').textContent = 'Edit Sale #' + d.id;
        document.getElementById('saleSubmitBtn').innerHTML = '<i class="fas fa-floppy-disk"></i> Update Sale';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addSaleModal')).show();
    }

    
    const editTintBtn = e.target.closest('.edit-tint-btn');
    if (editTintBtn) {
        const d = editTintBtn.dataset;
        document.getElementById('stock_action').value = 'update';
        document.getElementById('stock_id').value = d.id;
        document.getElementById('film_name').value = d.name;
        const ttEl = document.getElementById('tint_type'); if (ttEl) ttEl.value = d.type || d.name || '';
        document.getElementById('tint_brand').value = d.brand;
        document.getElementById('shade_percentage').value = d.shade;
        document.getElementById('price_per_roll').value = d.price || 0;
        document.getElementById('quantity_rolls').value = d.quantity || 0;
        document.getElementById('width_cm').value = d.width;
        document.getElementById('length_ft').value = d.length;
        document.getElementById('quantity_rolls').value = d.quantity;
        document.getElementById('min_rolls').value = d.min;
        document.getElementById('heat_rejection').value = d.heat;
        document.getElementById('uv_protection').value = d.uv;
        document.getElementById('tint_warranty').value = d.warranty;
        document.getElementById('tint_location').value = d.location;
        _setShadeMode('edit', d.shade, d.shadePrices || '{}');
        document.getElementById('tintStockModalTitle').textContent = 'Edit Tint Film';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addTintStockModal')).show();
    }

    
    const restockBtn = e.target.closest('.restock-tint-btn');
    if (restockBtn) {
        document.getElementById('restock_tint_id').value = restockBtn.dataset.id;
        document.getElementById('restock_tint_name').textContent = restockBtn.dataset.name;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('restockTintModal')).show();
    }

    
    const delTintBtn = e.target.closest('.delete-tint-btn');
    if (delTintBtn) {
        e.preventDefault();
        var delForm = delTintBtn.closest('form');
        rgConfirm({title:'Delete Tint Shade',message:'Delete "' + delTintBtn.dataset.name + '"? This cannot be undone.',confirmText:'Delete',type:'danger',onConfirm:function(){ if(delForm) delForm.submit(); }});
    }

    
    const viewTicketBtn = e.target.closest('.view-ticket-btn');
    if (viewTicketBtn) {
        const d = viewTicketBtn.dataset;
        document.getElementById('ticket_id').value = d.id;
        document.getElementById('ticket_customer').textContent = d.customer;
        document.getElementById('ticket_email').textContent = d.email;
        document.getElementById('ticket_phone').textContent = d.phone || 'No phone';
        document.getElementById('ticket_subject').textContent = d.subject;
        document.getElementById('ticket_message').textContent = d.message;
        document.getElementById('ticket_status').value = d.status;
        document.getElementById('ticket_priority').value = d.priority;
        document.getElementById('ticket_response').value = d.response || '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('viewTicketModal')).show();
    }

    
    const editAdminBtn = e.target.closest('.edit-admin-btn');
    if (editAdminBtn) {
        const d = editAdminBtn.dataset;
        document.getElementById('admin_action').value = 'update';
        document.getElementById('admin_id').value = d.id;
        document.getElementById('admin_username').value = d.username;
        document.getElementById('admin_fullname').value = d.fullname;
        document.getElementById('admin_email').value = d.email;
        document.getElementById('admin_role').value = d.role;
        document.getElementById('admin_department').value = d.department;
        document.getElementById('admin_active').checked = d.active === '1';
        document.getElementById('admin_username').readOnly = true;
        document.getElementById('passwordField').style.display = 'none';
        document.getElementById('admin_active_section').style.display = '';
        document.getElementById('adminModalTitle').textContent = 'Edit Admin User';
        document.getElementById('adminSubmitBtn').innerHTML = '<i class="fas fa-floppy-disk"></i> Update User';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addAdminModal')).show();
    }

    
    const passBtn = e.target.closest('.password-btn');
    if (passBtn) {
        document.getElementById('password_id').value = passBtn.dataset.id;
        document.getElementById('password_username').textContent = passBtn.dataset.username;
        document.getElementById('new_password').value = '';
        document.getElementById('confirm_password').value = '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('passwordModal')).show();
    }
});

const adEl = document.getElementById('appointment_date');
if (adEl) { var _ad = new Date(); adEl.value = _ad.getFullYear()+'-'+String(_ad.getMonth()+1).padStart(2,'0')+'-'+String(_ad.getDate()).padStart(2,'0'); }

$(document).ready(function() {
    const dtDefaults = {
        pageLength: 25,
        lengthMenu: [[10, 25, 50, -1], ['10', '25', '50', 'All']],
        responsive: true
    };
    if (!$.fn.DataTable.isDataTable('#salesTable') && document.getElementById('salesTable')) {
        $('#salesTable').DataTable(Object.assign({}, dtDefaults, {order: [[1, 'desc']]}));
    }
    if (!$.fn.DataTable.isDataTable('#customersTable') && document.getElementById('customersTable')) {
        $('#customersTable').DataTable(Object.assign({}, dtDefaults, {order: [[4, 'desc']]}));
    }
    if (!$.fn.DataTable.isDataTable('#adminTable') && document.getElementById('adminTable')) {
        $('#adminTable').DataTable(Object.assign({}, dtDefaults, {order: [[0, 'asc']]}));
    }
    if (!$.fn.DataTable.isDataTable('#tintStockTable') && document.getElementById('tintStockTable')) {
        $('#tintStockTable').DataTable(Object.assign({}, dtDefaults, {order: [[0, 'asc']]}));
    }
    if (!$.fn.DataTable.isDataTable('#serviceTicketsTable') && document.getElementById('serviceTicketsTable')) {
        $('#serviceTicketsTable').DataTable(Object.assign({}, dtDefaults, {
            order: [[0, 'desc']],
            autoWidth: false,
            columnDefs: [
                { orderable: false, targets: -1 }
            ]
        }));
    }
    if (!$.fn.DataTable.isDataTable('#feedbackTable') && document.getElementById('feedbackTable')) {
        $('#feedbackTable').DataTable(Object.assign({}, dtDefaults, {order: [[0, 'desc']]}));
    }

    
    var salesToggle = document.getElementById('salesToggleAnalytics');
    var salesPanel  = document.getElementById('salesAnalyticsPanel');
    if (salesToggle && salesPanel) {
        salesToggle.addEventListener('click', function() {
            var open = salesPanel.style.display !== 'none';
            salesPanel.style.display = open ? 'none' : 'block';
            salesToggle.classList.toggle('active', !open);
        });
    }
});

function getSalesFilterLabel() {
    var params = new URLSearchParams(window.location.search);
    var filterType = params.get('sales_filter') || '';
    if (filterType === 'date') return 'Sales for ' + params.get('sales_date');
    if (filterType === 'month') {
        var m = params.get('sales_month');
        if (m) { var d = new Date(m + '-01'); return 'Sales for ' + d.toLocaleDateString('en-US', {month:'long', year:'numeric'}); }
    }
    if (filterType === 'year') return 'Sales for Year ' + params.get('sales_year');
    return 'All Recent Sales';
}

function getSalesData() {
    var table = document.getElementById('salesTable');
    if (!table) return null;
    var rows = table.querySelectorAll('tbody tr');
    var data = [];
    var totalPaid = 0, totalAll = 0, paidCount = 0, pendingCount = 0, refundedCount = 0;
    rows.forEach(function(row) {
        var cells = row.querySelectorAll('td');
        if (cells.length < 8) return;
        var id = (cells[0].textContent || '').replace(/[^\d]/g, '');
        var date = (cells[1].textContent || '').trim();
        var customer = (cells[2].textContent || '').trim();
        var product = (cells[3].textContent || '').trim();
        var qty = (cells[4].textContent || '').replace(/[^\d]/g, '');
        var amtText = cells[5].textContent || '';
        var amounts = amtText.match(/[\d,.]+/g) || ['0','0'];
        var totalPrice = parseFloat((amounts[0]||'0').replace(/,/g,'')) || 0;
        var unitPrice = parseFloat((amounts[1]||'0').replace(/,/g,'')) || 0;
        var payment = (cells[6].textContent || '').trim();
        var status = (cells[7].textContent || '').trim().toLowerCase();
        totalAll += totalPrice;
        if (status === 'paid') { totalPaid += totalPrice; paidCount++; }
        else if (status === 'pending') pendingCount++;
        else if (status === 'refunded') refundedCount++;
        data.push({ id:id, date:date, customer:customer, product:product, qty:parseInt(qty)||1, unitPrice:unitPrice, totalPrice:totalPrice, payment:payment, status:status });
    });
    return { rows: data, totalPaid:totalPaid, totalAll:totalAll, count:data.length, paidCount:paidCount, pendingCount:pendingCount, refundedCount:refundedCount };
}

function printSalesReport() {
    var info = getSalesData();
    if (!info || info.count === 0) return alert('No sales data to print.');
    var label = getSalesFilterLabel();
    var now = new Date().toLocaleString();

    var html = '<!DOCTYPE html><html><head><title>Sales Report - RG Cartint Works</title><style>';
    html += '@page{size:landscape;margin:15mm}';
    html += 'body{font-family:"Segoe UI",Arial,sans-serif;color:#1e293b;padding:0;margin:0}';
    html += '.header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #1e40af;padding-bottom:14px;margin-bottom:18px}';
    html += '.header-left h1{margin:0;font-size:22px;color:#1e40af;letter-spacing:-.5px}';
    html += '.header-left p{margin:2px 0 0;font-size:12px;color:#64748b}';
    html += '.header-right{text-align:right;font-size:11px;color:#64748b}';
    html += '.header-right .report-title{font-size:16px;font-weight:700;color:#1e293b;margin-bottom:2px}';
    html += '.summary-bar{display:flex;gap:0;margin-bottom:18px;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden}';
    html += '.summary-item{flex:1;padding:10px 16px;text-align:center;border-right:1px solid #e2e8f0}';
    html += '.summary-item:last-child{border-right:none}';
    html += '.summary-item .val{font-size:18px;font-weight:800}';
    html += '.summary-item .lbl{font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-top:2px}';
    html += '.val-green{color:#059669} .val-blue{color:#2563eb} .val-orange{color:#d97706} .val-red{color:#dc2626}';
    html += 'table{width:100%;border-collapse:collapse;font-size:12px;margin-bottom:16px}';
    html += 'thead th{background:#1e293b;color:#fff;padding:9px 10px;text-align:left;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.3px}';
    html += 'thead th:first-child{border-radius:6px 0 0 0} thead th:last-child{border-radius:0 6px 0 0}';
    html += 'tbody td{padding:8px 10px;border-bottom:1px solid #e2e8f0}';
    html += 'tbody tr:nth-child(even){background:#f8fafc}';
    html += 'tbody tr:hover{background:#eff6ff}';
    html += '.text-right{text-align:right}';
    html += '.badge{display:inline-block;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;text-transform:uppercase}';
    html += '.badge-paid{background:#dcfce7;color:#15803d} .badge-pending{background:#fef3c7;color:#92400e} .badge-refunded{background:#fce7f3;color:#9d174d} .badge-other{background:#f1f5f9;color:#475569}';
    html += '.total-row td{font-weight:800;font-size:13px;border-top:2px solid #1e293b;background:#f8fafc}';
    html += '.footer{display:flex;justify-content:space-between;padding-top:12px;border-top:1px solid #e2e8f0;font-size:10px;color:#94a3b8}';
    html += '@media print{body{padding:0}}';
    html += '</style></head><body>';

    
    html += '<div class="header">';
    html += '<div class="header-left"><h1>RG Cartint Works</h1><p>Automotive Window Tinting Services</p></div>';
    html += '<div class="header-right"><div class="report-title">SALES REPORT</div>';
    html += '<div>' + label + '</div><div>Generated: ' + now + '</div></div></div>';

    
    html += '<div class="summary-bar">';
    html += '<div class="summary-item"><div class="val val-blue">' + info.count + '</div><div class="lbl">Total Records</div></div>';
    html += '<div class="summary-item"><div class="val val-green">₱' + info.totalPaid.toLocaleString('en-PH',{minimumFractionDigits:2}) + '</div><div class="lbl">Total Paid</div></div>';
    html += '<div class="summary-item"><div class="val val-blue">₱' + info.totalAll.toLocaleString('en-PH',{minimumFractionDigits:2}) + '</div><div class="lbl">Gross Amount</div></div>';
    html += '<div class="summary-item"><div class="val val-green">' + info.paidCount + '</div><div class="lbl">Paid</div></div>';
    html += '<div class="summary-item"><div class="val val-orange">' + info.pendingCount + '</div><div class="lbl">Pending</div></div>';
    html += '<div class="summary-item"><div class="val val-red">' + info.refundedCount + '</div><div class="lbl">Refunded</div></div>';
    html += '</div>';

    
    html += '<table><thead><tr><th>#</th><th>Date</th><th>Customer</th><th>Product/Service</th><th class="text-right">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Total</th><th>Payment</th><th>Status</th></tr></thead><tbody>';
    info.rows.forEach(function(r, i) {
        var bc = r.status==='paid'?'badge-paid':r.status==='pending'?'badge-pending':r.status==='refunded'?'badge-refunded':'badge-other';
        html += '<tr>';
        html += '<td>' + (i+1) + '</td>';
        html += '<td>' + r.date + '</td>';
        html += '<td>' + r.customer + '</td>';
        html += '<td>' + r.product + '</td>';
        html += '<td class="text-right">' + r.qty + '</td>';
        html += '<td class="text-right">₱' + r.unitPrice.toLocaleString('en-PH',{minimumFractionDigits:2}) + '</td>';
        html += '<td class="text-right">₱' + r.totalPrice.toLocaleString('en-PH',{minimumFractionDigits:2}) + '</td>';
        html += '<td>' + r.payment + '</td>';
        html += '<td><span class="badge ' + bc + '">' + r.status + '</span></td>';
        html += '</tr>';
    });
    html += '<tr class="total-row"><td colspan="6" class="text-right">TOTAL PAID:</td><td class="text-right">₱' + info.totalPaid.toLocaleString('en-PH',{minimumFractionDigits:2}) + '</td><td colspan="2"></td></tr>';
    html += '</tbody></table>';

    
    html += '<div class="footer"><span>RG Cartint Works &mdash; Confidential</span><span>Page 1</span><span>Printed by Admin on ' + now + '</span></div>';
    html += '</body></html>';

    
    var existingFrame = document.getElementById('salesPrintIframe');
    if (existingFrame) {
        try { existingFrame.remove(); } catch(e) {}
    }

    var iframe = document.createElement('iframe');
    iframe.id = 'salesPrintIframe';
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    iframe.style.opacity = '0';
    iframe.style.pointerEvents = 'none';
    document.body.appendChild(iframe);

    var doc = iframe.contentWindow.document;
    doc.open();
    doc.write(html);
    doc.close();

    function removeIframe() {
        setTimeout(function() {
            var f = document.getElementById('salesPrintIframe');
            if (f && f.parentNode) {
                try { f.parentNode.removeChild(f); } catch(e) {}
            }
        }, 1200);
    }

    setTimeout(function() {
        try {
            iframe.contentWindow.focus();
            if ('onafterprint' in iframe.contentWindow) {
                iframe.contentWindow.onafterprint = removeIframe;
            }
            iframe.contentWindow.print();
        } catch (err) {
            console.error('Iframe print error, falling back:', err);
            var win = window.open('', '_blank');
            if (win) {
                win.document.write(html);
                win.document.close();
                win.focus();
                setTimeout(function() { win.print(); }, 250);
            }
        }
        removeIframe();
    }, 300);
}

function exportSalesExcel() {
    var info = getSalesData();
    if (!info || info.count === 0) return alert('No sales data to export.');
    var label = getSalesFilterLabel();
    var now = new Date().toLocaleString();

    
    var xml = '<?xml version="1.0" encoding="UTF-8"?>';
    xml += '<?mso-application progid="Excel.Sheet"?>';
    xml += '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"';
    xml += ' xmlns:o="urn:schemas-microsoft-com:office:office"';
    xml += ' xmlns:x="urn:schemas-microsoft-com:office:excel"';
    xml += ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';

    
    xml += '<Styles>';
    
    xml += '<Style ss:ID="Default" ss:Name="Normal"><Font ss:FontName="Calibri" ss:Size="11" ss:Color="#1E293B"/></Style>';
    
    xml += '<Style ss:ID="s_company"><Font ss:FontName="Calibri" ss:Size="16" ss:Bold="1" ss:Color="#1E40AF"/></Style>';
    
    xml += '<Style ss:ID="s_title"><Font ss:FontName="Calibri" ss:Size="13" ss:Bold="1" ss:Color="#334155"/></Style>';
    
    xml += '<Style ss:ID="s_sub"><Font ss:FontName="Calibri" ss:Size="10" ss:Color="#64748B" ss:Italic="1"/></Style>';
    
    xml += '<Style ss:ID="s_hdr"><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/>';
    xml += '<Interior ss:Color="#1E293B" ss:Pattern="Solid"/>';
    xml += '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
    xml += '<Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FFFFFF"/></Borders></Style>';
    
    xml += '<Style ss:ID="s_cur"><NumberFormat ss:Format="₱#,##0.00"/><Font ss:FontName="Calibri" ss:Size="11" ss:Color="#1E293B"/><Alignment ss:Horizontal="Right"/></Style>';
    
    xml += '<Style ss:ID="s_cur_green"><NumberFormat ss:Format="₱#,##0.00"/><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#059669"/><Alignment ss:Horizontal="Right"/></Style>';
    
    xml += '<Style ss:ID="s_num"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Calibri" ss:Size="11"/></Style>';
    
    xml += '<Style ss:ID="s_rownum"><Font ss:FontName="Calibri" ss:Size="11" ss:Color="#64748B"/><Alignment ss:Horizontal="Center"/></Style>';
    
    xml += '<Style ss:ID="s_even"><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/><Font ss:FontName="Calibri" ss:Size="11"/></Style>';
    xml += '<Style ss:ID="s_even_cur"><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/><NumberFormat ss:Format="₱#,##0.00"/><Font ss:FontName="Calibri" ss:Size="11"/><Alignment ss:Horizontal="Right"/></Style>';
    xml += '<Style ss:ID="s_even_num"><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/><Font ss:FontName="Calibri" ss:Size="11"/></Style>';
    xml += '<Style ss:ID="s_even_rownum"><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/><Font ss:FontName="Calibri" ss:Size="11" ss:Color="#64748B"/><Alignment ss:Horizontal="Center"/></Style>';
    
    xml += '<Style ss:ID="s_paid"><Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#15803D"/><Interior ss:Color="#DCFCE7" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style>';
    xml += '<Style ss:ID="s_pending"><Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#92400E"/><Interior ss:Color="#FEF3C7" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style>';
    xml += '<Style ss:ID="s_refunded"><Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#9D174D"/><Interior ss:Color="#FCE7F3" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style>';
    xml += '<Style ss:ID="s_status_other"><Font ss:FontName="Calibri" ss:Size="10" ss:Color="#475569"/><Interior ss:Color="#F1F5F9" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style>';
    
    xml += '<Style ss:ID="s_sum_lbl"><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#1E293B"/><Interior ss:Color="#E2E8F0" ss:Pattern="Solid"/><Alignment ss:Horizontal="Right"/>';
    xml += '<Borders><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#1E293B"/></Borders></Style>';
    
    xml += '<Style ss:ID="s_sum_val"><NumberFormat ss:Format="₱#,##0.00"/><Font ss:FontName="Calibri" ss:Size="12" ss:Bold="1" ss:Color="#059669"/><Interior ss:Color="#E2E8F0" ss:Pattern="Solid"/><Alignment ss:Horizontal="Right"/>';
    xml += '<Borders><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#1E293B"/></Borders></Style>';
    
    xml += '<Style ss:ID="s_sum_empty"><Interior ss:Color="#E2E8F0" ss:Pattern="Solid"/>';
    xml += '<Borders><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#1E293B"/></Borders></Style>';
    
    xml += '<Style ss:ID="s_info_lbl"><Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#475569"/><Alignment ss:Horizontal="Right"/></Style>';
    xml += '<Style ss:ID="s_info_val"><Font ss:FontName="Calibri" ss:Size="10" ss:Color="#1E293B"/></Style>';
    xml += '<Style ss:ID="s_info_val_green"><Font ss:FontName="Calibri" ss:Size="10" ss:Bold="1" ss:Color="#059669"/><NumberFormat ss:Format="₱#,##0.00"/></Style>';
    xml += '</Styles>';

    
    xml += '<Worksheet ss:Name="Sales Report">';
    xml += '<Table ss:DefaultColumnWidth="90">';

    
    xml += '<Column ss:Index="1" ss:Width="40"/>';  
    xml += '<Column ss:Index="2" ss:Width="110"/>'; 
    xml += '<Column ss:Index="3" ss:Width="170"/>'; 
    xml += '<Column ss:Index="4" ss:Width="200"/>'; 
    xml += '<Column ss:Index="5" ss:Width="50"/>';  
    xml += '<Column ss:Index="6" ss:Width="110"/>'; 
    xml += '<Column ss:Index="7" ss:Width="120"/>'; 
    xml += '<Column ss:Index="8" ss:Width="100"/>'; 
    xml += '<Column ss:Index="9" ss:Width="90"/>';  

    
    xml += '<Row ss:Height="26"><Cell ss:StyleID="s_company"><Data ss:Type="String">RG Cartint Works</Data></Cell></Row>';
    
    xml += '<Row ss:Height="20"><Cell ss:StyleID="s_title"><Data ss:Type="String">SALES REPORT</Data></Cell></Row>';
    
    xml += '<Row ss:Height="16"><Cell ss:StyleID="s_sub"><Data ss:Type="String">' + escXml(label) + ' | Generated: ' + escXml(now) + '</Data></Cell></Row>';
    
    xml += '<Row ss:Height="8"></Row>';

    
    xml += '<Row ss:Height="18">';
    xml += '<Cell ss:StyleID="s_info_lbl"><Data ss:Type="String">Total Records:</Data></Cell>';
    xml += '<Cell ss:StyleID="s_info_val"><Data ss:Type="Number">' + info.count + '</Data></Cell>';
    xml += '<Cell/>';
    xml += '<Cell ss:StyleID="s_info_lbl"><Data ss:Type="String">Paid:</Data></Cell>';
    xml += '<Cell ss:StyleID="s_info_val"><Data ss:Type="Number">' + info.paidCount + '</Data></Cell>';
    xml += '<Cell ss:StyleID="s_info_lbl"><Data ss:Type="String">Pending:</Data></Cell>';
    xml += '<Cell ss:StyleID="s_info_val"><Data ss:Type="Number">' + info.pendingCount + '</Data></Cell>';
    xml += '<Cell ss:StyleID="s_info_lbl"><Data ss:Type="String">Refunded:</Data></Cell>';
    xml += '<Cell ss:StyleID="s_info_val"><Data ss:Type="Number">' + info.refundedCount + '</Data></Cell>';
    xml += '</Row>';
    xml += '<Row ss:Height="18">';
    xml += '<Cell ss:StyleID="s_info_lbl"><Data ss:Type="String">Total Paid:</Data></Cell>';
    xml += '<Cell ss:StyleID="s_info_val_green"><Data ss:Type="Number">' + info.totalPaid + '</Data></Cell>';
    xml += '<Cell/>';
    xml += '<Cell ss:StyleID="s_info_lbl"><Data ss:Type="String">Gross Amount:</Data></Cell>';
    xml += '<Cell ss:StyleID="s_info_val_green"><Data ss:Type="Number">' + info.totalAll + '</Data></Cell>';
    xml += '</Row>';
    
    xml += '<Row ss:Height="8"></Row>';

    
    xml += '<Row ss:Height="28">';
    ['#','Date','Customer','Product/Service','Qty','Unit Price','Total','Payment','Status'].forEach(function(h) {
        xml += '<Cell ss:StyleID="s_hdr"><Data ss:Type="String">' + h + '</Data></Cell>';
    });
    xml += '</Row>';

    
    info.rows.forEach(function(r, i) {
        var even = i % 2 === 1;
        var rnStyle = even ? 's_even_rownum' : 's_rownum';
        var defStyle = even ? 's_even' : 'Default';
        var curStyle = even ? 's_even_cur' : 's_cur';
        var numStyle = even ? 's_even_num' : 's_num';
        var stStyle = r.status==='paid'?'s_paid':r.status==='pending'?'s_pending':r.status==='refunded'?'s_refunded':'s_status_other';

        xml += '<Row>';
        xml += '<Cell ss:StyleID="' + rnStyle + '"><Data ss:Type="Number">' + (i+1) + '</Data></Cell>';
        xml += '<Cell ss:StyleID="' + defStyle + '"><Data ss:Type="String">' + escXml(r.date) + '</Data></Cell>';
        xml += '<Cell ss:StyleID="' + defStyle + '"><Data ss:Type="String">' + escXml(r.customer) + '</Data></Cell>';
        xml += '<Cell ss:StyleID="' + defStyle + '"><Data ss:Type="String">' + escXml(r.product) + '</Data></Cell>';
        xml += '<Cell ss:StyleID="' + numStyle + '"><Data ss:Type="Number">' + r.qty + '</Data></Cell>';
        xml += '<Cell ss:StyleID="' + curStyle + '"><Data ss:Type="Number">' + r.unitPrice + '</Data></Cell>';
        xml += '<Cell ss:StyleID="' + curStyle + '"><Data ss:Type="Number">' + r.totalPrice + '</Data></Cell>';
        xml += '<Cell ss:StyleID="' + defStyle + '"><Data ss:Type="String">' + escXml(r.payment) + '</Data></Cell>';
        xml += '<Cell ss:StyleID="' + stStyle + '"><Data ss:Type="String">' + escXml(r.status.toUpperCase()) + '</Data></Cell>';
        xml += '</Row>';
    });

    
    xml += '<Row ss:Height="26">';
    xml += '<Cell ss:MergeAcross="5" ss:StyleID="s_sum_lbl"><Data ss:Type="String">TOTAL PAID:</Data></Cell>';
    xml += '<Cell ss:StyleID="s_sum_val"><Data ss:Type="Number">' + info.totalPaid + '</Data></Cell>';
    xml += '<Cell ss:StyleID="s_sum_empty"/>';
    xml += '<Cell ss:StyleID="s_sum_empty"/>';
    xml += '</Row>';

    xml += '</Table>';

    
    xml += '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">';
    xml += '<PageSetup><Layout x:Orientation="Landscape"/><PageMargins x:Bottom="0.5" x:Left="0.5" x:Right="0.5" x:Top="0.5"/></PageSetup>';
    xml += '<Print><ValidPrinterInfo/><HorizontalResolution>600</HorizontalResolution><VerticalResolution>600</VerticalResolution></Print>';
    xml += '<FitToPage/>';
    xml += '</WorksheetOptions>';
    xml += '</Worksheet></Workbook>';

    
    var blob = new Blob([xml], {type: 'application/vnd.ms-excel'});
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    var fname = 'Sales_Report_' + (new Date().toISOString().slice(0,10)) + '.xls';
    a.href = url;
    a.download = fname;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function escXml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── LIVE CLOCK ───────────────────────────────────────────────
function updateClock() {
    const el = document.getElementById('liveClock');
    if (el) {
        const now = new Date();
        el.textContent = now.toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
    }
}
setInterval(updateClock, 1000);
updateClock();

// ══════════════════════════════════════════════════════════════
// UI/UX ENHANCEMENTS
// ══════════════════════════════════════════════════════════════

// ── Tooltips — init Bootstrap tooltips for data-bs-toggle="tooltip" ──
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        new bootstrap.Tooltip(el);
    });
});

// ── Intersection Observer — animate elements on scroll ──────
(function() {
    if (!('IntersectionObserver' in window)) return;
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('in-view');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.metric-card, .analytics-item, .inv-summary-item, .panel').forEach(function(el) {
            observer.observe(el);
        });
    });
})();

// ── Keyboard shortcuts ──────────────────────────────────────
document.addEventListener('keydown', function(e) {
    // Alt+1..8 to switch sections
    if (e.altKey && !e.ctrlKey && !e.shiftKey) {
        const idx = parseInt(e.key) - 1;
        if (idx >= 0 && idx < SECTIONS.length) {
            e.preventDefault();
            showSection(SECTIONS[idx]);
        }
    }
    // Escape closes sidebar on mobile
    if (e.key === 'Escape') {
        closeSidebar();
    }
});

// ── Sidebar active indicator smooth follow ──────────────────
(function() {
    const navLinks = document.querySelectorAll('.sidebar-nav-link');
    navLinks.forEach(function(link) {
        link.addEventListener('mouseenter', function() {
            this.style.transition = 'all .15s cubic-bezier(.4,0,.2,1)';
        });
    });
})();

// ── Auto-hide topbar on scroll down, show on scroll up ──────
(function() {
    let lastScroll = 0;
    const topbar = document.querySelector('.admin-topbar');
    if (!topbar) return;
    
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        if (currentScroll > 120 && currentScroll > lastScroll) {
            topbar.style.transform = 'translateY(-100%)';
            topbar.style.transition = 'transform .3s cubic-bezier(.4,0,.2,1)';
        } else {
            topbar.style.transform = 'translateY(0)';
        }
        lastScroll = currentScroll;
    }, { passive: true });
})();

// ── Confirm destructive actions with styled dialogs ─────────
document.addEventListener('click', function(e) {
    const dangerBtn = e.target.closest('[data-confirm]');
    if (dangerBtn) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var dangerForm = dangerBtn.closest('form');
        rgConfirm({title:'Confirm',message:dangerBtn.dataset.confirm,confirmText:'Confirm',type:'danger',onConfirm:function(){ if(dangerForm) dangerForm.submit(); else if(dangerBtn.href) window.location=dangerBtn.href; }});
    }
});

// ── Number counters — animate metric values on load ─────────
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.metric-value').forEach(function(el) {
        const text = el.textContent.trim();
        const match = text.match(/^([₱]?)([0-9,]+)(K?)$/);
        if (!match) return;
        
        const prefix = match[1];
        const target = parseInt(match[2].replace(/,/g, ''));
        const suffix = match[3];
        if (target === 0) return;
        
        const duration = 600;
        const start = performance.now();
        
        function tick(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic
            const current = Math.round(target * eased);
            el.textContent = prefix + current.toLocaleString() + suffix;
            if (progress < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    });
});

/* ═══════════════════════════════════════════════════════
   SCHEDULE — Full Monthly Calendar
   ═══════════════════════════════════════════════════════ */
(function () {
    'use strict';

    // DOM
    const grid      = document.getElementById('fcGrid');
    const loading   = document.getElementById('fcLoading');
    const monthLbl  = document.getElementById('fcMonthLabel');
    if (!grid) return;

    const drawer        = document.getElementById('fcDrawer');
    const drawerOverlay = document.getElementById('fcDrawerOverlay');
    const drawerDate    = document.getElementById('fcDrawerDate');
    const drawerCount   = document.getElementById('fcDrawerCount');
    const drawerBody    = document.getElementById('fcDrawerBody');
    const viewModal     = document.getElementById('viewModal');

    // State
    let bookingsByDate = window._schedMonthBookings || {};
    let viewYear       = window._schedViewYear  != null ? window._schedViewYear  : new Date().getFullYear();
    let viewMonth      = window._schedViewMonth != null ? window._schedViewMonth : new Date().getMonth();

    const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const DAYS   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

    function pad(n){ return String(n).padStart(2,'0'); }
    function toKey(y,m,d){ return `${y}-${pad(m+1)}-${pad(d)}`; }

    // Status helpers — colours match legend in HTML
    function statusClass(s){
        const st = (s||'').toLowerCase();
        if (st==='pending')                      return 'sched-fc-pill-pending';
        if (st==='approved'||st==='confirmed')   return 'sched-fc-pill-approved';
        if (st==='completed')                    return 'sched-fc-pill-completed';
        if (st==='on hold')                      return 'sched-fc-pill-hold';
        return 'sched-fc-pill-info';
    }
    function statusDot(s){
        const st = (s||'').toLowerCase();
        if (st==='pending')                      return '#f59e0b';
        if (st==='approved'||st==='confirmed')   return '#3b82f6';
        if (st==='completed')                    return '#10b981';
        if (st==='on hold')                      return '#f97316';
        return '#0ea5e9';
    }
    function badgeHtml(status){
        const map = {
            'pending':   ['warning','Pending'],
            'approved':  ['success','Approved'],
            'confirmed': ['success','Confirmed'],
            'completed': ['primary','Completed'],
            'on hold':   ['warning','On Hold'],
            'cancelled': ['danger','Cancelled'],
            'declined':  ['danger','Declined'],
        };
        const [color, label] = map[(status||'').toLowerCase()] || ['secondary', status||'—'];
        return `<span class="badge bg-${color}">${label}</span>`;
    }

    // Build the calendar grid
    function buildCalendar(){
        if (monthLbl) monthLbl.textContent = `${MONTHS[viewMonth]} ${viewYear}`;
        const today    = new Date();
        const firstDay = new Date(viewYear, viewMonth, 1).getDay();
        const daysInM  = new Date(viewYear, viewMonth+1, 0).getDate();
        const daysInP  = new Date(viewYear, viewMonth, 0).getDate();
        const cells    = [];

        for (let i = firstDay-1; i >= 0; i--)
            cells.push({ day: daysInP-i, month: viewMonth-1, year: viewYear, other: true });
        for (let d = 1; d <= daysInM; d++)
            cells.push({ day: d, month: viewMonth, year: viewYear, other: false });
        const rem = 42 - cells.length;
        for (let d = 1; d <= rem; d++)
            cells.push({ day: d, month: viewMonth+1, year: viewYear, other: true });

        grid.innerHTML = '';
        cells.forEach(c => {
            const y   = c.month < 0 ? c.year-1 : (c.month > 11 ? c.year+1 : c.year);
            const m   = c.month < 0 ? 11        : (c.month > 11 ? 0        : c.month);
            const key = toKey(y, m, c.day);
            const bks = c.other ? [] : (bookingsByDate[key] || []);
            const isToday = !c.other && today.getFullYear()===viewYear && today.getMonth()===viewMonth && today.getDate()===c.day;

            const cell = document.createElement('div');
            cell.className = 'sched-fc-cell'
                + (c.other   ? ' sched-fc-cell-other'   : '')
                + (isToday   ? ' sched-fc-cell-today'   : '');

            // Day number
            const numEl = document.createElement('div');
            numEl.className = 'sched-fc-num' + (isToday ? ' sched-fc-num-today' : '');
            numEl.textContent = c.day;
            cell.appendChild(numEl);

            // Pills
            if (!c.other && bks.length) {
                const pillWrap = document.createElement('div');
                pillWrap.className = 'sched-fc-pills';
                const showMax = 3;
                bks.slice(0, showMax).forEach(b => {
                    const timeStr = b.time || '';
                    const pill = document.createElement('div');
                    pill.className = `sched-fc-pill ${statusClass(b.status)}`;
                    pill.innerHTML = `<span class="sched-fc-pill-dot" style="background:${statusDot(b.status)}"></span>`
                        + `<span class="sched-fc-pill-time">${timeStr}</span>`
                        + `<span class="sched-fc-pill-name">${b.customer_name||'#'+b.id}</span>`;
                    pillWrap.appendChild(pill);
                });
                if (bks.length > showMax) {
                    const more = document.createElement('div');
                    more.className = 'sched-fc-more';
                    more.textContent = `+${bks.length - showMax} more`;
                    pillWrap.appendChild(more);
                }
                cell.appendChild(pillWrap);
                cell.addEventListener('click', () => openDrawer(key, bks));
            } else if (!c.other) {
                cell.addEventListener('click', () => openDrawer(key, []));
            }

            grid.appendChild(cell);
        });
    }

    // Drawer
    function openDrawer(dateKey, bks){
        const d = new Date(dateKey + 'T00:00:00');
        if (drawerDate)  drawerDate.textContent  = d.toLocaleDateString([],{weekday:'long',year:'numeric',month:'long',day:'numeric'});
        if (drawerCount) drawerCount.textContent = bks.length ? `${bks.length} booking${bks.length!==1?'s':''}` : 'No bookings';
        if (drawerBody){
            if (!bks.length){
                drawerBody.innerHTML = '<div class="sched-drawer-empty"><i class="fas fa-calendar-xmark" style="font-size:32px;margin-bottom:10px;display:block;opacity:.4"></i>No bookings for this day.</div>';
            } else {
                drawerBody.innerHTML = '';
                bks.forEach(b => {
                    const card = document.createElement('div');
                    card.className = 'sched-drawer-card';
                    card.innerHTML = `
                        <div class="sched-drawer-card-hdr">
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="font-weight:700;font-size:13px">#${b.id} — ${escHtml(b.customer_name||'')}</span>
                                ${badgeHtml(b.status)}
                            </div>
                            <span style="font-size:11px;color:var(--text-muted)">${escHtml(b.time||'')}</span>
                        </div>
                        <div class="sched-drawer-card-body">
                            <div class="sched-dc-row"><i class="fas fa-car"></i><span>${escHtml(b.vehicle_model||'—')}${b.vehicle_year?' ('+escHtml(b.vehicle_year)+')':''}</span></div>
                            <div class="sched-dc-row"><i class="fas fa-tint"></i><span>${escHtml(b.type_name||'—')} — ${escHtml(b.shade||'35')}% shade</span></div>
                            ${b.package ? `<div class="sched-dc-row"><i class="fas fa-box"></i><span>${escHtml(b.package)}</span></div>` : ''}
                            <div class="sched-dc-row"><i class="fas fa-peso-sign"></i><span>₱${escHtml(b.price||'0')} &mdash; ${escHtml(b.payment_status||'Pending')}</span></div>
                            ${b.phone ? `<div class="sched-dc-row"><i class="fas fa-phone"></i><span>${escHtml(b.phone)}</span></div>` : ''}
                        </div>
                        <div class="sched-drawer-card-footer" style="display:flex;gap:6px">
                            <button class="btn btn-sm btn-outline-secondary view-btn" style="flex:1"
                                data-id="${b.id}"
                                data-name="${escAttr(b.customer_name||'')}"
                                data-phone="${escAttr(b.phone||'')}"
                                data-address="${escAttr(b.address||'')}"
                                data-car="${escAttr(b.vehicle_model||'')}"
                                data-year="${escAttr(b.vehicle_year||'')}"
                                data-type="${escAttr(b.type_name||'')}"
                                data-package="${escAttr(b.package||'')}"
                                data-price="${escAttr(b.price||'')}"
                                data-date="${escAttr(b.date||'')}"
                                data-time="${escAttr(b.time||'')}"
                                data-status="${escAttr(b.status||'')}"
                                data-installation="${escAttr(b.installation||'')}"
                                data-tint-type="${escAttr(b.tint_type||'carbon')}"
                                data-shade="${escAttr(b.shade||'35')}"
                                data-warranty="${escAttr(b.warranty||'3')}"
                                data-payment-method="${escAttr(b.payment_method||'GCash')}"
                                data-payment-type="${escAttr(b.payment_type||'full')}"
                                data-payment-status="${escAttr(b.payment_status||'Pending')}"
                                data-payment-reference="${escAttr(b.payment_reference||'')}"
                                data-rejection-reason="${escAttr(b.rejection_reason||'')}"
                                data-rejected-by="${escAttr(b.rejected_by||'')}">
                                <i class="fas fa-eye me-1"></i> View
                            </button>
                            <button class="btn btn-sm btn-outline-warning transfer-btn" style="flex:1"
                                data-id="${b.id}"
                                data-name="${escAttr(b.customer_name||'')}"
                                data-vehicle="${escAttr(b.vehicle_model||'')}"
                                data-date="${escAttr(b.date||'')}"
                                data-time="${escAttr(b.time||'')}">
                                <i class="fas fa-right-left me-1"></i> Transfer
                            </button>
                            </button>
                        </div>`;
                    drawerBody.appendChild(card);
                });
            }
        }
        drawer?.classList.add('open');
        drawerOverlay?.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer(){
        drawer?.classList.remove('open');
        drawerOverlay?.classList.remove('active');
        document.body.style.overflow = '';
    }

    function escHtml(s){ const d=document.createElement('div');d.textContent=s;return d.innerHTML; }
    function escAttr(s){ return String(s).replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

    // Drawer close actions
    document.getElementById('fcDrawerClose')?.addEventListener('click', closeDrawer);
    drawerOverlay?.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', e => { if (e.key==='Escape') closeDrawer(); });

    // Month navigation
    function fetchMonth(y, m){
        if (m < 0)  { m = 11; y--; }
        if (m > 11) { m = 0;  y++; }
        viewYear  = y;
        viewMonth = m;
        const monthStr = `${y}-${pad(m+1)}`;

        if (loading) loading.style.display = 'flex';
        grid.style.opacity = '.4';

        fetch(`../api/get_schedule.php?month=${monthStr}`, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(res => {
                if (res.success) bookingsByDate = res.data;
                buildCalendar();
            })
            .catch(() => buildCalendar())
            .finally(() => {
                if (loading) loading.style.display = 'none';
                grid.style.opacity = '1';
            });
    }

    document.getElementById('fcPrev')?.addEventListener('click', () => fetchMonth(viewYear, viewMonth-1));
    document.getElementById('fcNext')?.addEventListener('click', () => fetchMonth(viewYear, viewMonth+1));
    document.getElementById('fcToday')?.addEventListener('click', () => {
        const now = new Date();
        fetchMonth(now.getFullYear(), now.getMonth());
    });

    // Expose so showSection can trigger re-render
    window._schedBuildCalendar = buildCalendar;

    // Initial render
    buildCalendar();
})();

/* ═══════════════════════════════════════════════════════
   TRANSFER SCHEDULE MODAL — move booking to new date/time
   ═══════════════════════════════════════════════════════ */
(function () {
    'use strict';

    const API = '../api/transfer_booking.php';

    // DOM refs
    const tfModal    = document.getElementById('transferModal');
    if (!tfModal) return;

    const tfName     = document.getElementById('tfName');
    const tfCurDate  = document.getElementById('tfCurDate');
    const tfCurTime  = document.getElementById('tfCurTime');
    const tfVehicle  = document.getElementById('tfVehicle');
    const tfNewDate  = document.getElementById('tfNewDate');
    const tfSlots    = document.getElementById('tfSlots');
    const tfReason   = document.getElementById('tfReason');
    const tfSubmit   = document.getElementById('tfSubmit');
    const tfBkId     = document.getElementById('tfBookingId');
    const tfNewTime  = document.getElementById('tfNewTime');

    let bsModal = null;

    // Open modal from drawer's "Transfer" button (delegated)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.transfer-btn');
        if (!btn) return;

        const id = btn.dataset.id;
        if (!id) return;

        
        const vm = document.getElementById('viewModal');
        if (vm) {
            const bsView = bootstrap.Modal.getInstance(vm);
            if (bsView) bsView.hide();
        }

        
        tfNewDate.value = '';
        tfNewTime.value = '';
        tfReason.value  = '';
        tfSubmit.disabled = true;
        tfSlots.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:20px;color:var(--text-muted);font-size:12px"><i class="fas fa-calendar-day" style="font-size:20px;display:block;margin-bottom:6px;opacity:.4"></i>Pick a date to see available slots</div>';

        
        tfName.textContent    = btn.dataset.name || '—';
        tfCurDate.textContent = btn.dataset.date || '—';
        tfCurTime.textContent = btn.dataset.time || '—';
        tfVehicle.textContent = btn.dataset.vehicle || '';
        tfBkId.value          = id;

        
        var _td = new Date(); tfNewDate.min = _td.getFullYear()+'-'+String(_td.getMonth()+1).padStart(2,'0')+'-'+String(_td.getDate()).padStart(2,'0');

        
        if (!bsModal) bsModal = bootstrap.Modal.getOrCreateInstance(tfModal);
        bsModal.show();
    });

    
    tfNewDate.addEventListener('change', async function () {
        const date = this.value;
        const bookingId = tfBkId.value;
        tfNewTime.value = '';
        tfSubmit.disabled = true;

        if (!date) {
            tfSlots.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:20px;color:var(--text-muted);font-size:12px">Pick a date to see available slots</div>';
            return;
        }

        
        const d = new Date(date + 'T00:00:00');
        if (d.getDay() === 0 || d.getDay() === 6) {
            tfSlots.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:20px;color:#ef4444;font-size:12px"><i class="fas fa-calendar-xmark" style="font-size:20px;display:block;margin-bottom:6px"></i>Closed on weekends</div>';
            return;
        }

        tfSlots.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:20px;color:var(--text-muted);font-size:12px"><div class="spinner-border spinner-border-sm me-2"></div>Loading slots...</div>';

        try {
            const res = await fetch(`${API}?available_slots=1&date=${date}&exclude=${bookingId}&_=${Date.now()}`, { credentials: 'same-origin' });
            const data = await res.json();

            if (!data.success || !data.slots) {
                tfSlots.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:16px;color:#ef4444;font-size:12px">' + (data.message || 'Error loading slots') + '</div>';
                return;
            }

            if (data.slots.length === 0) {
                tfSlots.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:16px;color:#ef4444;font-size:12px">No slots — closed on weekends</div>';
                return;
            }

            
            var _n = new Date(); var todayStr = _n.getFullYear()+'-'+String(_n.getMonth()+1).padStart(2,'0')+'-'+String(_n.getDate()).padStart(2,'0');
            if (date === todayStr) {
                var now = new Date();
                var nowMins = now.getHours() * 60 + now.getMinutes();
                data.slots.forEach(function (s) {
                    if (s.available) {
                        var parts = s.time.split(':').map(Number);
                        if (parts[0] * 60 + parts[1] <= nowMins) s.available = false;
                    }
                });
            }

            const avail = data.slots.filter(s => s.available).length;
            let html = '';
            data.slots.forEach(function (s) {
                if (s.available) {
                    html += '<div class="tf-slot tf-slot-avail" data-time="' + s.time + '">'
                        + '<i class="fas fa-circle-check" style="color:#10b981;font-size:10px"></i> '
                        + '<span style="font-weight:600">' + s.label + '</span>'
                        + '</div>';
                } else {
                    html += '<div class="tf-slot tf-slot-taken">'
                        + '<i class="fas fa-ban" style="color:#ef4444;font-size:10px"></i> '
                        + '<span style="text-decoration:line-through;opacity:.5">' + s.label + '</span>'
                        + '</div>';
                }
            });

            if (avail === 0) {
                html += '<div style="grid-column:1/-1;text-align:center;padding:8px;color:#ef4444;font-size:11px;margin-top:4px"><i class="fas fa-exclamation-triangle me-1"></i>All slots booked — try another date</div>';
            } else {
                html += '<div style="grid-column:1/-1;font-size:11px;color:var(--text-muted);margin-top:4px"><i class="fas fa-circle-info me-1"></i>' + avail + ' of ' + data.slots.length + ' slots available</div>';
            }

            tfSlots.innerHTML = html;

            
            tfSlots.querySelectorAll('.tf-slot-avail').forEach(function (el) {
                el.addEventListener('click', function () {
                    tfSlots.querySelectorAll('.tf-slot-avail').forEach(function (s) { s.classList.remove('tf-slot-selected'); });
                    el.classList.add('tf-slot-selected');
                    tfNewTime.value = el.dataset.time;
                    tfSubmit.disabled = false;
                });
            });

        } catch (err) {
            console.error('Transfer slots error:', err);
            tfSlots.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:16px;color:#ef4444;font-size:12px"><i class="fas fa-triangle-exclamation me-1"></i>Failed to load slots</div>';
        }
    });

    
    tfSubmit.addEventListener('click', async function () {
        const bookingId = tfBkId.value;
        const newDate   = tfNewDate.value;
        const newTime   = tfNewTime.value;
        const reason    = tfReason.value;

        if (!bookingId || !newDate || !newTime) return;

        tfSubmit.disabled = true;
        tfSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Transferring...';

        try {
            const form = new FormData();
            form.append('booking_id', bookingId);
            form.append('new_date', newDate);
            form.append('new_time', newTime);
            form.append('reason', reason);

            const res = await fetch(API, { method: 'POST', body: form, credentials: 'same-origin' });
            const data = await res.json();

            if (data.success) {
                tfSubmit.innerHTML = '<i class="fas fa-check me-1"></i> Transferred!';
                tfSubmit.classList.remove('btn-warning');
                tfSubmit.classList.add('btn-success');

                
                setTimeout(function () {
                    if (bsModal) bsModal.hide();
                    
                    document.getElementById('fcDrawer')?.classList.remove('open');
                    document.getElementById('fcDrawerOverlay')?.classList.remove('active');
                    document.body.style.overflow = '';
                    
                    if (typeof window._schedBuildCalendar === 'function') {
                        
                        var fcPrev = document.getElementById('fcPrev');
                        var fcNext = document.getElementById('fcNext');
                        
                        document.getElementById('fcToday')?.click();
                    }
                }, 1200);
            } else {
                rgToast('Transfer Failed', data.message || 'Transfer failed', 'error');
                tfSubmit.disabled = false;
                tfSubmit.innerHTML = '<i class="fas fa-right-left me-1"></i> Transfer Schedule';
            }
        } catch (err) {
            console.error('Transfer error:', err);
            rgToast('Network Error', 'Network error — please try again', 'error');
            tfSubmit.disabled = false;
            tfSubmit.innerHTML = '<i class="fas fa-right-left me-1"></i> Transfer Schedule';
        }
    });

    
    tfModal.addEventListener('hidden.bs.modal', function () {
        tfSubmit.classList.remove('btn-success');
        tfSubmit.classList.add('btn-warning');
        tfSubmit.innerHTML = '<i class="fas fa-right-left me-1"></i> Transfer Schedule';
        tfSubmit.disabled = true;
    });
})();

(function () {
    'use strict';

    const POLL_INTERVAL = 5000; 
    const API_URL = '../api/admin_notifications.php';
    let lastUnreadCount = null;
    let knownNotifIds = new Set();
    window.activeNotifId = null;

    
    function getCsrf() {
        const el = document.querySelector('input[name="csrf_token"]');
        return el ? el.value : '';
    }

    
    function iconForType(type) {
        if (type === 'success') return { icon: 'fa-check-circle', cls: 'bg-success' };
        if (type === 'warning') return { icon: 'fa-exclamation-triangle', cls: 'bg-warning' };
        if (type === 'danger')  return { icon: 'fa-times-circle', cls: 'bg-danger' };
        return { icon: 'fa-info-circle', cls: 'bg-info' };
    }

    
    function fmtDate(str, timeAgo) {
        if (timeAgo) return timeAgo;
        const d = new Date(str);
        if (isNaN(d)) return str;
        return d.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    
    
    function navigateNotification(title, msg, relType, relId) {
        title = String(title || '').toLowerCase();
        msg = String(msg || '').toLowerCase();
        relType = String(relType || '').toLowerCase();
        relId = String(relId || '').trim();
        var fullText = title + ' ' + msg;

        var targetSection = 'dashboard';
        var targetRowId = '';
        var targetButtonSelector = '';

        if (fullText.includes('booking') || fullText.includes('appointment') || fullText.includes('resched') || fullText.includes('payment') || fullText.includes('cancelled') || fullText.includes('decline') || fullText.includes('approved') || fullText.includes('confirmed') || relType === 'booking') {
            targetSection = 'bookings';
            if (relId) {
                targetRowId = 'booking-row-' + relId;
                targetButtonSelector = '.view-btn[data-id="' + relId + '"]';
            }
        } else if (fullText.includes('feedback') || fullText.includes('review') || fullText.includes('testimonial') || fullText.includes('star') || fullText.includes('rating') || relType === 'feedback') {
            targetSection = 'feedback';
            if (relId) targetRowId = 'feedback-row-' + relId;
        } else if (fullText.includes('support') || fullText.includes('ticket') || fullText.includes('inquiry') || fullText.includes('service') || relType === 'service') {
            targetSection = 'service';
            if (relId) {
                targetRowId = 'ticket-row-' + relId;
                targetButtonSelector = '.view-ticket-btn[data-id="' + relId + '"]';
            }
        } else if (fullText.includes('stock') || fullText.includes('film') || fullText.includes('roll') || fullText.includes('inventory') || relType === 'stock' || relType === 'tint_stock') {
            targetSection = 'tint-stock';
            if (relId) {
                targetRowId = 'stock-row-' + relId;
                targetButtonSelector = '.edit-tint-btn[data-id="' + relId + '"]';
            }
        } else if (fullText.includes('sale') || fullText.includes('revenue') || fullText.includes('paid') || relType === 'sale') {
            targetSection = 'sales';
            if (relId) {
                targetRowId = 'sale-row-' + relId;
                targetButtonSelector = '.edit-sale-btn[data-id="' + relId + '"]';
            }
        } else if (fullText.includes('customer') || fullText.includes('user') || fullText.includes('account') || relType === 'user') {
            targetSection = 'customers';
            if (relId) targetRowId = 'customer-row-' + relId;
        } else if (relType === 'admin_user') {
            targetSection = 'admin';
            if (relId) targetRowId = 'admin-row-' + relId;
        }

        function focusTarget() {
            var targetRow = targetRowId ? document.getElementById(targetRowId) : null;
            if (!targetRow && relId && targetButtonSelector) {
                var scoped = document.getElementById('section-' + targetSection);
                if (scoped) targetRow = scoped.querySelector(targetButtonSelector);
            }
            if (!targetRow) return;

            targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            targetRow.classList.add('notif-jump-target');
            setTimeout(function () { targetRow.classList.remove('notif-jump-target'); }, 1800);

            var triggerBtn = null;
            if (targetRow.matches && targetRow.matches('button')) {
                triggerBtn = targetRow;
            } else if (targetButtonSelector) {
                triggerBtn = targetRow.querySelector(targetButtonSelector);
            }
            if (triggerBtn) {
                triggerBtn.click();
            }
        }

        if (typeof window.showSection === 'function') {
            window.showSection(targetSection);
            setTimeout(focusTarget, 150);
        } else {
            focusTarget();
        }

        var dropdownEl = document.getElementById('adminNotifBtn');
        if (dropdownEl && typeof bootstrap !== 'undefined') {
            var dropdown = bootstrap.Dropdown.getInstance(dropdownEl);
            if (dropdown) dropdown.hide();
        }
    }

    
    function buildRow(n) {
        const { icon, cls } = iconForType(n.type);
        const unreadCls = parseInt(n.is_read) === 0 ? ' unread' : '';
        const csrf = getCsrf();
        const titleAttr = escHtml(n.title || '');
        const msgAttr = escHtml(n.message || '');
        const typeAttr = escHtml(n.related_type || '');
        const relIdAttr = n.related_id || 0;
        return `<div class="admin-notif-row${unreadCls}" data-id="${n.id}" data-title="${titleAttr}" data-msg="${msgAttr}" data-type="${typeAttr}" data-rel-id="${relIdAttr}" style="cursor:pointer;">
            <div class="admin-notif-icon ${cls}"><i class="fas ${icon} fa-xs"></i></div>
            <div class="flex-grow-1" style="min-width:0;">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <strong style="font-size:12px;line-height:1.3;color:var(--text-primary);">${escHtml(n.title)}</strong>
                    <button type="button" class="btn-close admin-notif-delete-btn"
                        style="width:.45em;height:.45em;opacity:.4;flex-shrink:0;"
                        data-id="${n.id}" data-csrf="${escHtml(csrf)}" title="Delete notification"></button>
                </div>
                <p style="font-size:11px;color:var(--text-secondary);margin:2px 0;">${escHtml(n.message)}</p>
                <div style="font-size:.65rem;color:var(--text-muted);margin-top:3px;">${fmtDate(n.created_at, n.time_ago)}</div>
            </div>
        </div>`;
    }

    function escHtml(str) {
        return String(str || '')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    // Update badge count
    function updateBadge(count) {
        const badge = document.getElementById('adminNotifBadge');
        const btn   = document.getElementById('adminNotifBtn');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 9 ? '9+' : count;
            badge.classList.remove('d-none');
            if (btn && lastUnreadCount !== null && count > lastUnreadCount) {
                btn.classList.add('animate-bounce');
                setTimeout(() => btn.classList.remove('animate-bounce'), 1000);
            }
        } else {
            badge.classList.add('d-none');
        }
    }

    // Re-render the notification list in the dropdown
    function renderList(notifications) {
        const list = document.getElementById('adminNotifList');
        if (!list) return;
        if (!notifications || notifications.length === 0) {
            list.innerHTML = '<div class="p-4 text-center" style="font-size:13px;color:var(--text-muted);">No notifications yet</div>';
            return;
        }
        list.innerHTML = notifications.map(buildRow).join('');
    }

    // Poll the API
    function poll() {
        fetch(API_URL, { method: 'GET', credentials: 'same-origin', cache: 'no-store' })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (data) {
                if (!data || data.success === false) return;
                const unread = data.unread || 0;
                const notifications = data.notifications || [];

                // Check for brand new notifications to trigger live Toast popup
                if (lastUnreadCount !== null && unread > lastUnreadCount) {
                    notifications.forEach(n => {
                        if (parseInt(n.is_read) === 0 && !knownNotifIds.has(parseInt(n.id))) {
                            let toastType = n.type || 'info';
                            if (typeof window.rgToast === 'function') {
                                window.rgToast(n.title, n.message, toastType, 5000);
                            }
                        }
                    });
                }

                // Update known set
                notifications.forEach(n => knownNotifIds.add(parseInt(n.id)));

                updateBadge(unread);
                renderList(notifications);
                lastUnreadCount = unread;
            })
            .catch(function () { /* ignore network errors silently */ });
    }

    // Handle click on a notification row or buttons
    document.addEventListener('click', function (e) {
        const row = e.target.closest('.admin-notif-row');
        const delBtn = e.target.closest('.admin-notif-delete-btn');
        const markAllBtn = e.target.closest('#adminMarkAllReadBtn');

        // Mark all read button
        if (markAllBtn) {
            e.preventDefault();
            const csrf = getCsrf();
            fetch(API_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body: JSON.stringify({ action: 'mark_all_read', csrf_token: csrf })
            }).then(function () { poll(); });
            return;
        }

        // Delete button
        if (delBtn) {
            e.stopPropagation();
            e.preventDefault();
            const id   = delBtn.dataset.id;
            const csrf = delBtn.dataset.csrf || getCsrf();
            fetch(API_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body: JSON.stringify({ action: 'delete', id: parseInt(id), csrf_token: csrf })
            }).then(function () { poll(); });
            return;
        }

        // Row click → mark single as read & navigate to section
        if (row) {
            // Highlight clicked row in dropdown and store active ID
            window.activeNotifId = row.getAttribute('data-id') || row.dataset.id;
            document.querySelectorAll('.admin-notif-row').forEach(r => r.classList.remove('active-notif'));
            row.classList.add('active-notif');

            const id = row.dataset.id;
            const title = row.dataset.title || (row.querySelector('strong') ? row.querySelector('strong').textContent : '');
            const msg = row.dataset.msg || (row.querySelector('p') ? row.querySelector('p').textContent : '');
            const relType = row.dataset.type || '';
            const relId = row.dataset.relId || '';

            if (row.classList.contains('unread')) {
                row.classList.remove('unread');
                const csrf = getCsrf();
                fetch(API_URL, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                    body: JSON.stringify({ action: 'mark_read', id: parseInt(id), csrf_token: csrf })
                }).then(function () { /* keep list updated silently */ });
            }

            navigateNotification(title, msg, relType, relId);
        }
    });

    // Start polling
    document.addEventListener('DOMContentLoaded', function () {
        poll(); // immediate first call
        setInterval(poll, POLL_INTERVAL);
    });
})();

window.editPackageModalHandler = function(btn) {
    if (!btn) return;
    try {
        const d = btn.dataset;
        const pAct = document.getElementById('package_action'); if (pAct) pAct.value = 'update';
        const pId  = document.getElementById('package_id'); if (pId) pId.value = d.id;
        const pNm  = document.getElementById('pkg_name'); if (pNm) pNm.value = d.name || '';
        if (typeof window.validatePackageName === 'function') window.validatePackageName();

        const descTxt = document.createElement('textarea');
        descTxt.innerHTML = d.description || '';
        const pDesc = document.getElementById('pkg_description'); if (pDesc) pDesc.value = descTxt.value;

        try { window.comboPrices = JSON.parse(d.comboPrices || '{}'); } catch(e) { window.comboPrices = {}; }
        for (const comboKey in window.comboPrices) {
            const rawVal = window.comboPrices[comboKey];
            if (typeof rawVal === 'object' && rawVal !== null && !rawVal.cars && !rawVal.windows) {
                const carKeys = Object.keys(rawVal);
                const samplePrice = carKeys.length ? parseFloat(rawVal[carKeys[0]] || 0) : 0;
                window.comboPrices[comboKey] = { cars: carKeys, price: samplePrice, windows: {} };
            }
        }

        const types = (d.tintType || 'carbon').split(',').map(s => s.trim()).filter(Boolean);
        window._selectedTypes = types;
        window._focusedType = types[0] || 'carbon';

        let shadeData = {};
        try { shadeData = JSON.parse(d.shade || '{}'); } catch(e) {}
        if (typeof shadeData !== 'object' || shadeData === null || Array.isArray(shadeData)) {
            const sv = parseInt(d.shade) || 35;
            shadeData = {};
            types.forEach(t => { shadeData[t] = [sv]; });
        }
        window._selectedShades = shadeData;

        const firstShades = shadeData[window._focusedType] || [35];
        window._currentCombo = window._focusedType + '|' + firstShades[0];

        try { if (typeof window._syncTintTypeCards === 'function') window._syncTintTypeCards(); } catch(e) {}
        try { if (typeof window._syncShadePills === 'function') window._syncShadePills(); } catch(e) {}
        try { if (typeof window._loadComboToUI === 'function') window._loadComboToUI(window._currentCombo); } catch(e) {}
        try { if (typeof window._updateComboUI === 'function') window._updateComboUI(); } catch(e) {}
        try { if (typeof window._updatePkgTotal === 'function') window._updatePkgTotal(); } catch(e) {}

        const cs = document.getElementById('carModelSearch'); if (cs) { cs.value = ''; try { if (typeof filterCarModels === 'function') filterCarModels(''); } catch(e){} }
        const pInst = document.getElementById('pkg_install'); if (pInst) pInst.value = d.installation || '';
        const pWarr = document.getElementById('pkg_warranty'); if (pWarr) pWarr.value = d.warranty || '3';
        const pOrd  = document.getElementById('pkg_order'); if (pOrd) pOrd.value = d.order || '0';
        try {
            const feats = JSON.parse(d.features);
            const pFeat = document.getElementById('pkg_features'); if (pFeat) pFeat.value = Array.isArray(feats) ? feats.join('\n') : d.features;
        } catch(e) {
            const pFeat = document.getElementById('pkg_features'); if (pFeat) pFeat.value = d.features || '';
        }
        const pActive = document.getElementById('pkg_active'); if (pActive) pActive.checked = d.active == '1';
        const pFeat   = document.getElementById('pkg_featured'); if (pFeat) pFeat.checked = d.featured == '1';
        if (window._setWindowChips) try { window._setWindowChips(d.windows || '[]'); } catch(e){}

        const imgPrev = document.getElementById('pkgImagePreview');
        if (imgPrev) {
            if (d.imageUrl && d.imageUrl !== '/images/tint-packages/default-tint.jpg') {
                imgPrev.src = d.imageUrl;
                imgPrev.style.display = 'block';
            } else {
                imgPrev.style.display = 'none'; imgPrev.src = '';
            }
        }
        const pTitle = document.getElementById('packageModalTitle'); if (pTitle) pTitle.textContent = 'Edit Package';
    } catch(err) {
        console.error('Error populating edit package modal:', err);
    }
    const modalEl = document.getElementById('addPackageModal');
    if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
};

/* Global Bootstrap Modal Backdrop & Scroll Lock Purger */
document.addEventListener('hidden.bs.modal', function () {
    setTimeout(function() {
        if (!document.querySelector('.modal.show')) {
            document.querySelectorAll('.modal-backdrop').forEach(function(b) { b.remove(); });
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }
    }, 50);
});
