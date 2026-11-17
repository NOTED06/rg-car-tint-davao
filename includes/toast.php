
<style>
.rg-toast-wrap{position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;}
.rg-toast{pointer-events:auto;display:flex;align-items:flex-start;gap:12px;padding:14px 18px;min-width:300px;max-width:420px;border-radius:12px;color:#fff;font-size:13px;font-family:'DM Sans',sans-serif;box-shadow:0 8px 30px rgba(0,0,0,.2);animation:rgToastIn .35s cubic-bezier(.21,1.02,.73,1) forwards;backdrop-filter:blur(4px);}
.rg-toast.out{animation:rgToastOut .35s ease forwards;}
.rg-toast-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px;}
.rg-toast-body{flex:1;min-width:0;}
.rg-toast-title{font-weight:700;font-size:13px;margin-bottom:2px;}
.rg-toast-msg{font-size:12px;opacity:.85;line-height:1.4;}
.rg-toast-close{background:none;border:none;color:rgba(255,255,255,.5);cursor:pointer;font-size:14px;padding:0;line-height:1;flex-shrink:0;transition:color .2s;}
.rg-toast-close:hover{color:#fff;}
.rg-toast-bar{position:absolute;bottom:0;left:0;height:3px;border-radius:0 0 12px 12px;animation:rgToastBar var(--toast-dur,4s) linear forwards;}
.rg-toast.t-success{background:linear-gradient(135deg,#065f46,#059669);}
.rg-toast.t-success .rg-toast-icon{background:rgba(255,255,255,.15);color:#6ee7b7;}
.rg-toast.t-success .rg-toast-bar{background:#6ee7b7;}
.rg-toast.t-error{background:linear-gradient(135deg,#991b1b,#dc2626);}
.rg-toast.t-error .rg-toast-icon{background:rgba(255,255,255,.15);color:#fca5a5;}
.rg-toast.t-error .rg-toast-bar{background:#fca5a5;}
.rg-toast.t-warning{background:linear-gradient(135deg,#78350f,#d97706);}
.rg-toast.t-warning .rg-toast-icon{background:rgba(255,255,255,.15);color:#fde68a;}
.rg-toast.t-warning .rg-toast-bar{background:#fde68a;}
.rg-toast.t-info{background:linear-gradient(135deg,#0A0A0A,#1a1a1a);border:1px solid rgba(212,160,23,.3);}
.rg-toast.t-info .rg-toast-icon{background:rgba(212,160,23,.15);color:#D4A017;}
.rg-toast.t-info .rg-toast-bar{background:#D4A017;}
@keyframes rgToastIn{from{opacity:0;transform:translateX(40px) scale(.95);}to{opacity:1;transform:translateX(0) scale(1);}}
@keyframes rgToastOut{from{opacity:1;transform:translateX(0);}to{opacity:0;transform:translateX(40px);}}
@keyframes rgToastBar{from{width:100%;}to{width:0;}}
.rg-confirm-overlay{position:fixed;inset:0;z-index:99998;background:rgba(0,0,0,.55);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;animation:rgFadeIn .2s ease;}
.rg-confirm-box{background:#0A0A0A;border:1px solid rgba(212,160,23,.25);border-radius:14px;width:380px;max-width:90vw;box-shadow:0 20px 60px rgba(0,0,0,.4);animation:rgScaleIn .25s cubic-bezier(.21,1.02,.73,1);font-family:'DM Sans',sans-serif;}
.rg-confirm-head{padding:18px 22px 0;display:flex;align-items:center;gap:12px;}
.rg-confirm-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
.rg-confirm-icon.c-danger{background:rgba(239,68,68,.15);color:#ef4444;}
.rg-confirm-icon.c-warning{background:rgba(245,158,11,.15);color:#f59e0b;}
.rg-confirm-icon.c-info{background:rgba(212,160,23,.15);color:#D4A017;}
.rg-confirm-icon.c-success{background:rgba(34,197,94,.15);color:#22c55e;}
.rg-confirm-title{font-weight:700;font-size:15px;color:#fff;}
.rg-confirm-body{padding:12px 22px 20px;font-size:13px;color:rgba(255,255,255,.75);line-height:1.55;}
.rg-confirm-actions{padding:0 22px 18px;display:flex;gap:10px;justify-content:flex-end;}
.rg-confirm-btn{padding:8px 20px;border-radius:8px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .2s;font-family:'DM Sans',sans-serif;}
.rg-confirm-btn.cb-cancel{background:rgba(255,255,255,.08);color:rgba(255,255,255,.6);border:1px solid rgba(255,255,255,.1);}
.rg-confirm-btn.cb-cancel:hover{background:rgba(255,255,255,.12);color:#fff;}
.rg-confirm-btn.cb-danger{background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;}
.rg-confirm-btn.cb-danger:hover{background:linear-gradient(135deg,#b91c1c,#dc2626);}
.rg-confirm-btn.cb-warning{background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;}
.rg-confirm-btn.cb-warning:hover{background:linear-gradient(135deg,#b45309,#d97706);}
.rg-confirm-btn.cb-success{background:linear-gradient(135deg,#059669,#10b981);color:#fff;}
.rg-confirm-btn.cb-success:hover{background:linear-gradient(135deg,#047857,#059669);}
.rg-confirm-btn.cb-primary{background:linear-gradient(135deg,#D4A017,#F5C842);color:#0A0A0A;font-weight:700;}
.rg-confirm-btn.cb-primary:hover{background:linear-gradient(135deg,#b8890f,#D4A017);}
@keyframes rgFadeIn{from{opacity:0;}to{opacity:1;}}
@keyframes rgScaleIn{from{opacity:0;transform:scale(.9) translateY(10px);}to{opacity:1;transform:scale(1) translateY(0);}}
</style>
<div class="rg-toast-wrap" id="rgToastWrap"></div>
<script>
if(!window.rgToast){
window.rgToast=function(title,msg,type,duration){
type=type||'info';duration=duration||4000;
var w=document.getElementById('rgToastWrap');
if(!w){w=document.createElement('div');w.className='rg-toast-wrap';w.id='rgToastWrap';document.body.appendChild(w);}
var ic={success:'fa-check-circle',error:'fa-times-circle',danger:'fa-times-circle',warning:'fa-exclamation-triangle',info:'fa-info-circle'};
var el=document.createElement('div');el.className='rg-toast t-'+(type==='error'?'error':type);el.style.setProperty('--toast-dur',duration+'ms');
el.innerHTML='<div class="rg-toast-icon"><i class="fas '+(ic[type]||ic.info)+'"></i></div><div class="rg-toast-body"><div class="rg-toast-title">'+_escH(title)+'</div><div class="rg-toast-msg">'+_escH(msg)+'</div></div><button class="rg-toast-close" onclick="this.parentElement.classList.add(\'out\');setTimeout(function(){this.remove()}.bind(this.parentElement),350)">&times;</button><div class="rg-toast-bar"></div>';
w.appendChild(el);setTimeout(function(){if(el.parentElement){el.classList.add('out');setTimeout(function(){el.remove();},350);}},duration);
};
function _escH(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
window.rgConfirm=function(o){
var old=document.getElementById('rgConfirmOverlay');if(old)old.remove();
var t=o.type||'danger',im={danger:'fa-trash-can',warning:'fa-exclamation-triangle',info:'fa-circle-info',success:'fa-circle-check'};
var showCancel = o.showCancel !== false;
var confirmBtnClass = (t === 'info' || t === 'success') ? 'cb-primary' : ('cb-' + t);
var ov=document.createElement('div');ov.className='rg-confirm-overlay';ov.id='rgConfirmOverlay';
ov.innerHTML='<div class="rg-confirm-box"><div class="rg-confirm-head"><div class="rg-confirm-icon c-'+t+'"><i class="fas '+(im[t]||im.info)+'"></i></div><div class="rg-confirm-title">'+_escH(o.title||'Are you sure?')+'</div></div><div class="rg-confirm-body">'+_escH(o.message||'')+'</div><div class="rg-confirm-actions">'+(showCancel ? '<button class="rg-confirm-btn cb-cancel" id="rgConfirmNo">'+_escH(o.cancelText||'Cancel')+'</button>' : '')+'<button class="rg-confirm-btn '+confirmBtnClass+'" id="rgConfirmYes">'+_escH(o.confirmText||'Confirm')+'</button></div></div>';
document.body.appendChild(ov);
ov.addEventListener('click',function(ev){if(ev.target===ov)cl();});
var noBtn = document.getElementById('rgConfirmNo');
if(noBtn) noBtn.addEventListener('click',cl);
document.getElementById('rgConfirmYes').addEventListener('click',function(){cl();if(o.onConfirm)o.onConfirm();});
function onK(ev){if(ev.key==='Escape')cl();}document.addEventListener('keydown',onK);
function cl(){ov.remove();document.removeEventListener('keydown',onK);if(o.onClose)o.onClose();}
};

window.rgAlert=function(title,msg,type,onOk){
if(typeof title==='object'){onOk=title.onOk;type=title.type;msg=title.message;title=title.title;}
if(typeof title==='string' && !msg){msg=title;title='Notification';}
window.rgConfirm({
title: title || 'Notification',
message: msg || '',
confirmText: 'OK',
showCancel: false,
type: type || 'info',
onConfirm: onOk
});
};

window.showCustToast=function(title,msg,type){
window.rgAlert(title,msg,type);
};

window.alert=function(msg){
var s=String(msg||'');
var isSuccess=/sent|success|submitted|uploaded|approved|saved|created|updated|done/i.test(s);
var isError=/error|fail|invalid|denied|wrong|required/i.test(s);
var type=isSuccess?'success':(isError?'danger':'info');
var title=isSuccess?'Success':(isError?'Error':'Notification');
window.rgAlert(title,s,type);
};

document.addEventListener('submit',function(e){var f=e.target,m=f.getAttribute('data-rg-confirm');if(!m)return;e.preventDefault();rgConfirm({title:f.getAttribute('data-rg-title')||'Confirm',message:m,confirmText:f.getAttribute('data-rg-btn')||'Confirm',type:f.getAttribute('data-rg-type')||'danger',onConfirm:function(){f.removeAttribute('data-rg-confirm');f.submit();}});});
}
</script>
