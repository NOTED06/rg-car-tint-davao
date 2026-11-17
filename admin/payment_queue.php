<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
boot_session();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php"); exit;
}

$admin_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $action     = $_POST['action'];

    if ($booking_id > 0 && in_array($action, ['approve','reject'])) {
        try {
            $pdo->beginTransaction();

            if ($action === 'approve') {
                
                $pdo->prepare("
                    UPDATE bookings
                    SET status = 'Confirmed',
                        payment_status = 'Verified',
                        updated_at = NOW()
                    WHERE id = ? AND payment_status = 'Proof Submitted'
                ")->execute([$booking_id]);

                
                $pdo->prepare("UPDATE payment_transactions SET status='Verified', updated_at=NOW() WHERE booking_id=?")->execute([$booking_id]);

                
                $row = $pdo->prepare("SELECT user_id, booking_ref, appointment_date, appointment_time FROM bookings WHERE id=?");
                $row->execute([$booking_id]);
                $bk = $row->fetch(PDO::FETCH_ASSOC);
                if ($bk) {
                    $title = 'Payment Verified — Booking Confirmed';
                    $msg   = "Your payment has been verified! Your appointment on " . date('M d, Y', strtotime($bk['appointment_date'])) . ' at ' . ($bk['appointment_time'] ?: 'TBD') . " (Ref: {$bk['booking_ref']}) is now confirmed.";
                    try { $pdo->prepare("INSERT INTO notifications (user_id,user_type,title,message,type,is_read,related_id,related_type) VALUES (?,'customer',?,?,'success',0,?,'booking')")->execute([$bk['user_id'],$title,$msg,$booking_id]); } catch(PDOException $ne){}
                }
                
                try { $pdo->prepare("INSERT INTO notifications (user_type,title,message,type,is_read,related_id,related_type) VALUES ('admin','Payment Verified',?,  'success',0,?,'booking')")->execute(["Booking #{$booking_id} payment verified and confirmed.", $booking_id]); } catch(PDOException $ne){}
                $flash = ['type'=>'success','msg'=>'Booking #'.$booking_id.' approved and confirmed.'];

            } else {
                
                $reason = trim(htmlspecialchars($_POST['reject_reason'] ?? 'Invalid or incorrect payment proof'));
                if (empty($reason)) $reason = 'Payment proof could not be verified';

                $pdo->prepare("
                    UPDATE bookings
                    SET payment_status    = 'Rejected',
                        rejection_reason  = ?,
                        updated_at        = NOW()
                    WHERE id = ? AND payment_status = 'Proof Submitted'
                ")->execute([$reason, $booking_id]);

                $pdo->prepare("UPDATE payment_transactions SET status='Rejected', updated_at=NOW() WHERE booking_id=?")->execute([$booking_id]);

                $row = $pdo->prepare("SELECT user_id, booking_ref FROM bookings WHERE id=?");
                $row->execute([$booking_id]);
                $bk = $row->fetch(PDO::FETCH_ASSOC);
                if ($bk) {
                    $title = 'Payment Proof Rejected';
                    $msg   = "Your payment proof for booking {$bk['booking_ref']} was rejected. Reason: {$reason}. Please re-upload or contact us.";
                    try { $pdo->prepare("INSERT INTO notifications (user_id,user_type,title,message,type,is_read,related_id,related_type) VALUES (?,'customer',?,?,'danger',0,?,'booking')")->execute([$bk['user_id'],$title,$msg,$booking_id]); } catch(PDOException $ne){}
                }
                
                try { $pdo->prepare("INSERT INTO notifications (user_type,title,message,type,is_read,related_id,related_type) VALUES ('admin','Payment Rejected',?,'warning',0,?,'booking')")->execute(["Booking #{$booking_id} payment proof rejected. Reason: {$reason}", $booking_id]); } catch(PDOException $ne){}
                $flash = ['type'=>'danger','msg'=>'Booking #'.$booking_id.' payment rejected.'];
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $flash = ['type'=>'danger','msg'=>'Error: '.$e->getMessage()];
        }
    }
    header("Location: payment_queue.php"); exit;
}

try {
    $expired_stmt = $pdo->query("SELECT id, user_id FROM bookings WHERE status='On Hold' AND hold_expires_at IS NOT NULL AND hold_expires_at < NOW()");
    $expired_bookings = $expired_stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($expired_bookings)) {
        $ids = array_column($expired_bookings, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE bookings SET status='Cancelled', cancellation_reason='Hold expired — no payment proof uploaded within 20 minutes', updated_at=NOW() WHERE id IN ($placeholders)")->execute($ids);
        foreach ($expired_bookings as $eb) {
            try {
                $pdo->prepare("INSERT INTO notifications (user_id,user_type,title,message,type,is_read,related_id,related_type) VALUES (?,'customer','Booking Reservation Expired','Your booking reservation expired. No payment proof was uploaded within 20 minutes. Please book again.','warning',0,?,'booking')")->execute([$eb['user_id'],$eb['id']]);
            } catch (PDOException $e) {}
        }
    }
} catch (PDOException $e) {}

try {
    $queue = $pdo->query("
        SELECT b.*, u.full_name AS customer_full_name, u.email AS customer_email,
               vt.type_name, tp.name AS package_name,
               pt.amount AS paid_amount
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN vehicle_types vt ON b.vehicle_type_id = vt.id
        LEFT JOIN tint_packages tp ON b.package_id = tp.id
        LEFT JOIN payment_transactions pt ON pt.booking_id = b.id
        WHERE b.payment_status = 'Proof Submitted'
        ORDER BY b.updated_at ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $queue = [];
}

try {
    $recent = $pdo->query("
        SELECT b.*, u.full_name AS customer_full_name,
               vt.type_name, tp.name AS package_name
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN vehicle_types vt ON b.vehicle_type_id = vt.id
        LEFT JOIN tint_packages tp ON b.package_id = tp.id
        WHERE b.payment_status IN ('Verified','Rejected')
          AND b.updated_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
        ORDER BY b.updated_at DESC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Verification Queue — Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,600;9..40,700;9..40,800&display=swap" rel="stylesheet">
<style>
:root{--gold:#D4A017;--ink:#0A0A0A;--smoke:#F5F3EF;--border:#E6E2DA;--radius:12px;}
*{box-sizing:border-box;}
body{background:#F5F3EF;font-family:'DM Sans',sans-serif;min-height:100vh;}
.adm-wrap{max-width:1200px;margin:0 auto;padding:32px 20px;}
.page-hdr{margin-bottom:28px;}
.page-hdr h1{font-size:1.6rem;font-weight:800;color:var(--ink);margin:0 0 4px;}
.page-hdr p{color:#64748b;font-size:14px;margin:0;}
.kpi-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:28px;}
.kpi{background:#fff;border:1.5px solid var(--border);border-radius:var(--radius);padding:18px;}
.kpi-val{font-size:1.8rem;font-weight:800;color:var(--ink);}
.kpi-lbl{font-size:12px;color:#64748b;font-weight:600;margin-top:2px;}
.kpi.warn .kpi-val{color:#d97706;}
.kpi.ok   .kpi-val{color:#16a34a;}
.panel{background:#fff;border:1.5px solid var(--border);border-radius:var(--radius);margin-bottom:24px;overflow:hidden;}
.panel-head{padding:16px 20px;border-bottom:1.5px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.panel-head-title{font-size:14px;font-weight:700;color:var(--ink);display:flex;align-items:center;gap:8px;}
.panel-head-title i{color:var(--gold);}
.badge-count{background:var(--gold);color:var(--ink);font-size:11px;font-weight:700;border-radius:99px;padding:2px 8px;}
table{width:100%;border-collapse:collapse;font-size:13px;}
th{background:#F5F3EF;color:#64748b;text-transform:uppercase;letter-spacing:.05em;font-size:11px;font-weight:700;padding:10px 14px;border-bottom:1.5px solid var(--border);text-align:left;}
td{padding:13px 14px;border-bottom:1px solid var(--border);vertical-align:middle;}
tr:last-child td{border:none;}
.proof-thumb{width:52px;height:42px;object-fit:cover;border-radius:6px;border:1px solid var(--border);cursor:pointer;transition:transform .2s;}
.proof-thumb:hover{transform:scale(1.08);}
.pill{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;}
.pill-hold     {background:#FEF3C7;color:#92400e;}
.pill-submitted{background:#EFF6FF;color:#1d4ed8;}
.pill-verified {background:#F0FDF4;color:#166534;}
.pill-rejected {background:#FEF2F2;color:#991b1b;}
.btn-approve{background:#16a34a;color:#fff;border:none;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:all .2s;}
.btn-approve:hover{background:#15803d;}
.btn-reject{background:transparent;color:#dc2626;border:1.5px solid #fca5a5;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:all .2s;}
.btn-reject:hover{background:#fef2f2;}
.btn-view{background:#F5F3EF;color:var(--ink);border:1.5px solid var(--border);padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:all .2s;text-decoration:none;}
.btn-view:hover{border-color:var(--gold);color:var(--gold);}
.back-link{font-size:13px;color:#64748b;text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-bottom:20px;}
.back-link:hover{color:var(--gold);}
.alert{padding:12px 16px;border-radius:10px;font-size:13px;display:flex;align-items:center;gap:10px;margin-bottom:20px;}
.alert-success{background:#F0FDF4;border:1px solid #86EFAC;color:#166534;}
.alert-danger {background:#FEF2F2;border:1px solid #FCA5A5;color:#991b1b;}
.empty-state{padding:48px;text-align:center;color:#94a3b8;}
.empty-state i{font-size:2.5rem;margin-bottom:12px;display:block;}

.modal-backdrop-custom{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1050;align-items:center;justify-content:center;}
.modal-backdrop-custom.show{display:flex;}
.modal-box{background:#fff;border-radius:16px;width:90%;max-width:600px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);}
.modal-box-head{padding:20px 24px;border-bottom:1.5px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.modal-box-head h5{font-size:16px;font-weight:700;margin:0;}
.modal-close{background:none;border:none;font-size:1.2rem;color:#94a3b8;cursor:pointer;padding:4px;}
.modal-body-inner{padding:24px;}
.modal-proof-img{width:100%;border-radius:10px;border:1.5px solid var(--border);margin-bottom:16px;}
.info-row{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border);font-size:13px;}
.info-row:last-child{border:none;}
.info-lbl{color:#64748b;}
.info-val{font-weight:600;}
.reject-form{background:#FEF2F2;border:1px solid #FCA5A5;border-radius:10px;padding:16px;margin-top:16px;}
.reject-form label{font-size:13px;font-weight:600;color:#991b1b;margin-bottom:6px;display:block;}
.reject-form select,.reject-form textarea{width:100%;border:1px solid #FCA5A5;border-radius:8px;padding:8px 12px;font-size:13px;margin-bottom:10px;background:#fff;}
</style>
</head>
<body>
<div class="adm-wrap">

    <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Admin Dashboard</a>

    <div class="page-hdr">
        <h1><i class="fas fa-shield-check" style="color:var(--gold);margin-right:8px;"></i>Payment Verification Queue</h1>
        <p>Review and approve or reject customer payment proofs for GCash/Maya bookings.</p>
    </div>

    <?php if(isset($flash)): ?>
    <div class="alert alert-<?= $flash['type'] ?>">
        <i class="fas <?= $flash['type']==='success'?'fa-check-circle':'fa-triangle-exclamation' ?>"></i>
        <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>

    
    <div class="kpi-row">
        <div class="kpi warn">
            <div class="kpi-val"><?= count($queue) ?></div>
            <div class="kpi-lbl">Awaiting Review</div>
        </div>
        <?php
        $verified_count  = count(array_filter($recent, fn($r) => $r['payment_status']==='Verified'));
        $rejected_count  = count(array_filter($recent, fn($r) => $r['payment_status']==='Rejected'));
        ?>
        <div class="kpi ok">
            <div class="kpi-val"><?= $verified_count ?></div>
            <div class="kpi-lbl">Approved (48h)</div>
        </div>
        <div class="kpi">
            <div class="kpi-val" style="color:#dc2626;"><?= $rejected_count ?></div>
            <div class="kpi-lbl">Rejected (48h)</div>
        </div>
    </div>

    
    <div class="panel">
        <div class="panel-head">
            <span class="panel-head-title">
                <i class="fas fa-hourglass-half"></i>Pending Proofs
                <?php if(count($queue)>0): ?><span class="badge-count"><?= count($queue) ?></span><?php endif; ?>
            </span>
        </div>
        <?php if(empty($queue)): ?>
        <div class="empty-state">
            <i class="fas fa-check-double" style="color:#4ade80;"></i>
            <div style="font-weight:600;color:#475569;">All caught up!</div>
            <div style="font-size:13px;margin-top:4px;">No payment proofs awaiting verification.</div>
        </div>
        <?php else: ?>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Booking Ref</th>
                    <th>Customer</th>
                    <th>Package / Vehicle</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Submitted</th>
                    <th>Proof</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($queue as $q):
                $proof_url = !empty($q['payment_proof']) ? '../' . htmlspecialchars($q['payment_proof']) : null;
                $amount_label = ($q['payment_type'] === 'downpayment') ? 'Deposit' : 'Full';
                $amount_val   = !empty($q['paid_amount']) ? (float)$q['paid_amount'] : (($q['payment_type']==='downpayment') ? (float)$q['deposit_amount'] : (float)$q['total_price']);
            ?>
            <tr>
                <td>
                    <span style="font-weight:700;font-size:12px;"><?= htmlspecialchars($q['booking_ref']) ?></span><br>
                    <small style="color:#94a3b8;"><?= date('M d, Y', strtotime($q['appointment_date'])) . ' ' . htmlspecialchars($q['appointment_time'] ?: '') ?></small>
                </td>
                <td>
                    <span style="font-weight:600;"><?= htmlspecialchars($q['customer_full_name'] ?? $q['customer_name']) ?></span><br>
                    <small style="color:#94a3b8;"><?= htmlspecialchars($q['phone'] ?? '') ?></small>
                </td>
                <td>
                    <span><?= htmlspecialchars($q['package_name'] ?? 'Package') ?></span><br>
                    <small style="color:#94a3b8;"><?= htmlspecialchars($q['type_name'] ?? '') ?> — <?= htmlspecialchars($q['vehicle_model'] ?? '') ?></small>
                </td>
                <td>
                    <strong>₱<?= number_format($amount_val, 0) ?></strong><br>
                    <small style="color:#94a3b8;"><?= $amount_label ?></small>
                </td>
                <td><span class="pill pill-submitted"><?= htmlspecialchars($q['payment_method']) ?></span></td>
                <td style="color:#64748b;font-size:12px;"><?= date('M d, h:i A', strtotime($q['updated_at'])) ?></td>
                <td>
                    <?php if($proof_url): ?>
                    <img src="<?= $proof_url ?>" alt="Proof" class="proof-thumb" onclick="openModal(<?= (int)$q['id'] ?>)" title="Click to enlarge">
                    <?php else: ?>
                    <span style="color:#94a3b8;font-size:12px;">No image</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <button class="btn-view" onclick="openModal(<?= (int)$q['id'] ?>)"><i class="fas fa-eye"></i>Review</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    
    <?php if(!empty($recent)): ?>
    <div class="panel">
        <div class="panel-head">
            <span class="panel-head-title"><i class="fas fa-history"></i>Recent Decisions (Last 48h)</span>
        </div>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Ref</th>
                    <th>Customer</th>
                    <th>Package</th>
                    <th>Amount</th>
                    <th>Decision</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($recent as $r): ?>
            <tr>
                <td style="font-weight:700;font-size:12px;"><?= htmlspecialchars($r['booking_ref']) ?></td>
                <td><?= htmlspecialchars($r['customer_full_name'] ?? $r['customer_name']) ?></td>
                <td><?= htmlspecialchars($r['package_name'] ?? '') ?></td>
                <td>₱<?= number_format((float)$r['total_price'], 0) ?></td>
                <td>
                    <span class="pill <?= $r['payment_status']==='Verified'?'pill-verified':'pill-rejected' ?>">
                        <i class="fas <?= $r['payment_status']==='Verified'?'fa-check':'fa-times' ?>"></i>
                        <?= $r['payment_status'] ?>
                    </span>
                </td>
                <td style="color:#64748b;font-size:12px;"><?= date('M d, h:i A', strtotime($r['updated_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<div class="modal-backdrop-custom" id="reviewModal">
    <div class="modal-box">
        <div class="modal-box-head">
            <h5><i class="fas fa-shield-check" style="color:var(--gold);margin-right:8px;"></i>Verify Payment Proof</h5>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body-inner" id="modalContent">
            <div class="text-center py-4"><i class="fas fa-spinner fa-spin" style="color:var(--gold);font-size:1.5rem;"></i></div>
        </div>
    </div>
</div>

<script>
const bookingsData = <?= json_encode(array_column($queue, null, 'id')) ?>;

function openModal(id) {
    const q = bookingsData[id];
    if (!q) return;

    const payType    = q.payment_type === 'downpayment' ? 'Deposit (10%)' : 'Full Payment';
    const amtVal     = q.payment_type === 'downpayment' ? parseFloat(q.deposit_amount||0) : parseFloat(q.total_price||0);
    const proofSrc   = q.payment_proof ? '../' + q.payment_proof : null;
    const refNum     = q.payment_reference || '—';

    let proofHtml = proofSrc
        ? `<a href="${proofSrc}" target="_blank"><img src="${proofSrc}" class="modal-proof-img" alt="Payment Proof"></a>`
        : `<div style="background:#F5F3EF;border-radius:10px;padding:32px;text-align:center;margin-bottom:16px;color:#94a3b8;"><i class="fas fa-image" style="font-size:2rem;"></i><div style="margin-top:8px;font-size:13px;">No proof image uploaded</div></div>`;

    const expectedAmt = '₱' + amtVal.toLocaleString('en-PH');
    const refMatch    = refNum !== '—'
        ? `<span style="color:#16a34a;font-weight:700;"><i class="fas fa-check-circle me-1"></i>${escHtml(refNum)}</span>`
        : `<span style="color:#dc2626;">Not provided</span>`;

    document.getElementById('modalContent').innerHTML = `
        ${proofHtml}
        <div class="info-row"><span class="info-lbl">Booking Ref</span><span class="info-val">${escHtml(q.booking_ref)}</span></div>
        <div class="info-row"><span class="info-lbl">Customer</span><span class="info-val">${escHtml(q.customer_full_name||q.customer_name)}</span></div>
        <div class="info-row"><span class="info-lbl">Phone</span><span class="info-val">${escHtml(q.phone||'—')}</span></div>
        <div class="info-row"><span class="info-lbl">Package</span><span class="info-val">${escHtml(q.package_name||'—')}</span></div>
        <div class="info-row"><span class="info-lbl">Appointment</span><span class="info-val">${formatDate(q.appointment_date)}</span></div>
        <div class="info-row"><span class="info-lbl">Payment Method</span><span class="info-val">${escHtml(q.payment_method)}</span></div>
        <div class="info-row"><span class="info-lbl">Payment Type</span><span class="info-val">${payType}</span></div>
        <div class="info-row"><span class="info-lbl">Expected Amount</span><span class="info-val" style="color:#D4A017;font-size:1.1rem;">${expectedAmt}</span></div>
        <div class="info-row"><span class="info-lbl">Reference Number</span><span class="info-val">${refMatch}</span></div>

        <div style="display:flex;gap:10px;margin-top:20px;">
            <form method="POST" style="flex:1;">
                <input type="hidden" name="booking_id" value="${id}">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn-approve" style="width:100%;justify-content:center;padding:12px;">
                    <i class="fas fa-check-circle"></i> Approve & Confirm Booking
                </button>
            </form>
        </div>

        <div class="reject-form">
            <label><i class="fas fa-times-circle me-1"></i>Reject Payment</label>
            <form method="POST">
                <input type="hidden" name="booking_id" value="${id}">
                <input type="hidden" name="action" value="reject">
                <select name="reject_reason">
                    <option value="Incorrect amount paid">Incorrect amount paid</option>
                    <option value="Invalid or unreadable proof screenshot">Invalid or unreadable proof screenshot</option>
                    <option value="Reference number does not match">Reference number does not match</option>
                    <option value="Duplicate submission">Duplicate submission</option>
                    <option value="Other">Other (specify below)</option>
                </select>
                <textarea name="reject_reason" rows="2" placeholder="Optional: add more detail..." style="resize:none;"></textarea>
                <button type="submit" class="btn-reject" style="width:100%;justify-content:center;padding:10px;">
                    <i class="fas fa-times"></i> Reject Payment Proof
                </button>
            </form>
        </div>
    `;

    document.getElementById('reviewModal').classList.add('show');
}

function closeModal() {
    document.getElementById('reviewModal').classList.remove('show');
}

document.getElementById('reviewModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

function escHtml(s) {
    return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
}

function formatDate(d) {
    if (!d) return '—';
    const dt = new Date(d.replace(' ','T'));
    return dt.toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'}) + ' ' + dt.toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'});
}

// Handle reject form: merge select + textarea into reject_reason
document.addEventListener('submit', function(e) {
    const form = e.target;
    const action = form.querySelector('[name="action"]');
    if (!action || action.value !== 'reject') return;
    const sel = form.querySelector('select[name="reject_reason"]');
    const ta  = form.querySelector('textarea[name="reject_reason"]');
    if (sel && ta) {
        const combined = sel.value + (ta.value.trim() ? ': ' + ta.value.trim() : '');
        // Remove both, add single hidden
        sel.removeAttribute('name');
        ta.removeAttribute('name');
        const h = document.createElement('input');
        h.type = 'hidden'; h.name = 'reject_reason'; h.value = combined;
        form.appendChild(h);
    }
});
</script>
</body>
</html>
