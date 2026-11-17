<?php

?>

<div class="modal fade" id="customerReviewModal" tabindex="-1" aria-labelledby="customerReviewModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:390px;margin:1.5rem auto;">
        <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 20px 40px -10px rgba(0,0,0,0.3);overflow:hidden;background:#ffffff;">
            
            <div style="background:linear-gradient(135deg, #1e293b 0%, #0f172a 100%);padding:14px 18px;color:#fff;position:relative;display:flex;align-items:center;justify-content:space-between;">
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

<script>
if (typeof BASE_URL === 'undefined') {
    var BASE_URL = '<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on'?'https':'http').':
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

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

document.addEventListener('DOMContentLoaded', function() {
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

                    if (typeof showCustToast === 'function') {
                        showCustToast('Review Submitted', data.message, 'success');
                    } else {
                        alert(data.message);
                    }
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    if (typeof showCustToast === 'function') {
                        showCustToast('Error', data.error || 'Failed to submit review', 'danger');
                    } else {
                        alert(data.error || 'Failed to submit review');
                    }
                }
            } catch (err) {
                if (typeof showCustToast === 'function') {
                    showCustToast('Error', 'Network error submitting review', 'danger');
                } else {
                    alert('Network error submitting review');
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = `<i class="fas fa-paper-plane me-1"></i> Submit Review`;
            }
        });
    }

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

                    if (typeof showCustToast === 'function') {
                        showCustToast('Support Message Sent', data.message, 'success');
                    } else {
                        alert(data.message);
                    }
                } else {
                    if (typeof showCustToast === 'function') {
                        showCustToast('Error', data.error || 'Failed to send message', 'danger');
                    } else {
                        alert(data.error || 'Failed to send message');
                    }
                }
            } catch (err) {
                if (typeof showCustToast === 'function') {
                    showCustToast('Error', 'Network error sending support message', 'danger');
                } else {
                    alert('Network error sending support message');
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = `<i class="fas fa-paper-plane me-1" style="color:#fbbf24;"></i> Send Message`;
            }
        });
    }
});
</script>

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

<div class="modal fade" id="customerPaymentModal" tabindex="-1" aria-labelledby="customerPaymentModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;margin:1.5rem auto;">
        <div class="modal-content" style="border-radius:18px;border:none;box-shadow:0 20px 50px rgba(0,0,0,0.4);overflow:hidden;background:#ffffff;">
            
            <div style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%);padding:16px 20px;color:#fff;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:34px;height:34px;border-radius:10px;background:rgba(212,160,23,0.2);display:flex;align-items:center;justify-content:center;color:#D4A017;font-size:1rem;">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <span style="font-size:1rem;font-weight:700;color:#fff;display:block;line-height:1.2;" id="customerPaymentModalLabel">GCash Payment</span>
                        <small style="color:#cbd5e1;font-size:11px;" id="pay_modal_ref_text">Ref # ---</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="opacity:0.8;font-size:0.8rem;"></button>
            </div>

            
            <div class="modal-body" style="padding:24px 20px;">
                <input type="hidden" id="pay_modal_booking_id" value="">
                
                
                <div style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%);border-radius:14px;padding:16px 20px;color:#fff;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 4px 15px rgba(0,0,0,0.15);">
                    <div>
                        <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;font-weight:700;letter-spacing:0.05em;">Amount to Pay</div>
                        <div style="font-size:12px;color:#cbd5e1;" id="pay_modal_type_label">Full Payment</div>
                    </div>
                    <div style="font-family:'Bebas Neue',sans-serif;font-size:2rem;color:#D4A017;letter-spacing:0.05em;" id="pay_modal_amount_due">₱0.00</div>
                </div>

                
                <div style="text-align:center;margin-bottom:14px;">
                    <div style="font-size:0.83rem;color:#475569;margin-bottom:14px;line-height:1.4;">
                        Click below to proceed to PayMongo's secure payment gateway for instant GCash authorization.
                    </div>
                    
                    <a id="paymongo_checkout_link" href="#" onclick="triggerPayMongoCheckout(event)" class="btn w-100 fw-bold d-flex align-items-center justify-content-center gap-2" style="background:linear-gradient(135deg, #0066ff 0%, #0044cc 100%); color:#ffffff; border:none; border-radius:12px; padding:14px; font-size:0.95rem; box-shadow:0 6px 20px rgba(0,102,255,0.35); text-decoration:none; transition:all .2s;">
                        <i class="fas fa-bolt" style="color:#F5C842;font-size:1.1rem;"></i> Pay Now via PayMongo (GCash)
                    </a>
                </div>

                <div style="display:flex;align-items:center;justify-content:center;gap:6px;font-size:0.75rem;color:#94a3b8;margin-top:16px;">
                    <i class="fas fa-shield-halved" style="color:#22c55e;"></i> 256-bit SSL · Official PayMongo Checkout Gateway
                </div>

                
                <div style="margin-top:20px;">
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal" style="border-radius:10px;font-weight:600;padding:10px;font-size:0.85rem;color:#64748b;">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

window.triggerPayMongoCheckout = async function(e) {
    e.preventDefault();
    const bookingId = document.getElementById("pay_modal_booking_id")?.value;
    if (!bookingId) {
        if (typeof window.rgAlert === 'function') window.rgAlert('Error', 'Invalid booking reference', 'danger');
        return;
    }
    const linkBtn = document.getElementById("paymongo_checkout_link");
    if (linkBtn) {
        linkBtn.style.pointerEvents = 'none';
        linkBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Creating PayMongo Checkout...`;
    }
    try {
        const res = await fetch(BASE_URL + "/api/create_paymongo_checkout.php?booking_id=" + bookingId);
        const data = await res.json();
        if (data && data.success && data.checkout_url) {
            window.location.href = data.checkout_url;
        } else {
            if (typeof window.rgAlert === 'function') {
                window.rgAlert('PayMongo Error', data.error || 'Could not initiate PayMongo checkout. Please try uploading reference manually.', 'danger');
            }
            if (linkBtn) {
                linkBtn.style.pointerEvents = 'auto';
                linkBtn.innerHTML = `<i class="fas fa-bolt" style="color:#F5C842;"></i> Pay Online via PayMongo (Instant GCash)`;
            }
        }
    } catch (err) {
        if (typeof window.rgAlert === 'function') {
            window.rgAlert('Error', 'Network error connecting to PayMongo checkout', 'danger');
        }
        if (linkBtn) {
            linkBtn.style.pointerEvents = 'auto';
            linkBtn.innerHTML = `<i class="fas fa-bolt" style="color:#F5C842;"></i> Pay Online via PayMongo (Instant GCash)`;
        }
    }
};

window.openCustomerPaymentModal = function(data) {
    const bookingId = (typeof data === 'object') ? (data.id || data.booking_id || 0) : data;
    if (bookingId) {
        const baseUrl = (typeof BASE_URL !== 'undefined') ? BASE_URL : '';
        window.location.href = baseUrl + "/api/create_paymongo_checkout.php?booking_id=" + bookingId + "&redirect=1";
    }
};

document.addEventListener("DOMContentLoaded", function() {
    const ajaxPayForm = document.getElementById("ajaxPaymentProofForm");
    if (ajaxPayForm) {
        ajaxPayForm.addEventListener("submit", async function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById("pay_modal_submit_btn");
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i> Uploading...`;

            const formData = new FormData(ajaxPayForm);

            try {
                const res = await fetch(BASE_URL + "/api/upload_payment_proof.php", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    const modalEl = document.getElementById("customerPaymentModal");
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    if (typeof showCustToast === "function") {
                        showCustToast("Payment Proof Uploaded", data.message || "Your payment proof has been submitted successfully!", "success");
                    } else {
                        alert(data.message || "Payment proof submitted successfully!");
                    }
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    if (typeof showCustToast === "function") {
                        showCustToast("Error", data.error || "Failed to upload payment proof", "danger");
                    } else {
                        alert(data.error || "Failed to upload payment proof");
                    }
                }
            } catch (err) {
                if (typeof showCustToast === "function") {
                    showCustToast("Error", "Network error uploading payment proof", "danger");
                } else {
                    alert("Network error uploading payment proof");
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = `<i class="fas fa-check-circle me-1"></i> Submit Payment Proof`;
            }
        });
    }
});

</script>
