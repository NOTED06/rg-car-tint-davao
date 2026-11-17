

document.addEventListener("DOMContentLoaded", function () {
    var toastElList = [].slice.call(document.querySelectorAll('.toast'));
    toastElList.map(function (toastEl) {
        return new bootstrap.Toast(toastEl);
    });
});

setTimeout(function () {
    var popup = document.getElementById('actionPopup');
    if (popup && !localStorage.getItem('actionPopupClosed')) {
        popup.style.display = 'block';
        var fabContainer = document.getElementById('floatingButtons');
        var chatbotToggle = document.getElementById('chatbotToggle');
        if (fabContainer) fabContainer.style.bottom = '160px';
        if (chatbotToggle) chatbotToggle.style.bottom = '230px';
    }
}, 3000);

function closePopup() {
    var popup = document.getElementById('actionPopup');
    if (popup) {
        var fabContainer = document.getElementById('floatingButtons');
        var chatbotToggle = document.getElementById('chatbotToggle');
        if (fabContainer) fabContainer.style.bottom = '30px';
        if (chatbotToggle) chatbotToggle.style.bottom = '160px';
        setTimeout(function () {
            popup.style.display = 'none';
            localStorage.setItem('actionPopupClosed', 'true');
        }, 300);
    }
}

setTimeout(function () {
    var popup = document.getElementById('actionPopup');
    if (popup && popup.style.display === 'block') {
        closePopup();
    }
}, 15000);

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closePopup();
});

document.addEventListener('DOMContentLoaded', function () {
    var fabContainer  = document.getElementById('floatingButtons');
    var fabSecondary  = document.getElementById('fabSecondary');
    var fabToggle     = document.getElementById('fabToggle');
    var fabToggleIcon = document.getElementById('fabToggleIcon');
    var chatbotToggle = document.getElementById('chatbotToggle');

    if (fabToggle) {
        fabToggle.addEventListener('click', function () {
            fabSecondary.classList.toggle('show');
            fabToggle.classList.toggle('rotate');
            fabToggleIcon.classList.toggle('fa-plus');
            fabToggleIcon.classList.toggle('fa-times');
            if (fabSecondary.classList.contains('show')) {
                var buttons = fabSecondary.querySelectorAll('.fab-button');
                buttons.forEach(function (button, index) {
                    button.style.animationDelay = index * 0.08 + 's';
                    button.style.animation = 'fadeInUp 0.3s ease forwards';
                });
            }
        });
    }

    
    var lastScrollTop = 0;
    window.addEventListener('scroll', function () {
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        if (scrollTop > lastScrollTop && scrollTop > 100) {
            if (fabContainer) { fabContainer.style.opacity = '0.7'; fabContainer.style.transform = 'translateY(18px)'; }
            if (chatbotToggle) { chatbotToggle.style.opacity = '0.7'; chatbotToggle.style.transform = 'translateY(18px)'; }
        } else {
            if (fabContainer) { fabContainer.style.opacity = '1'; fabContainer.style.transform = 'translateY(0)'; }
            if (chatbotToggle) { chatbotToggle.style.opacity = '1'; chatbotToggle.style.transform = 'translateY(0)'; }
        }
        lastScrollTop = scrollTop;

        
        var scrollPosition = window.scrollY + window.innerHeight;
        var documentHeight = document.documentElement.scrollHeight;
        if (scrollPosition >= documentHeight - 50) {
            if (fabContainer) fabContainer.style.display = 'none';
            if (chatbotToggle) chatbotToggle.style.display = 'none';
        } else {
            if (fabContainer) fabContainer.style.display = 'flex';
            if (chatbotToggle) chatbotToggle.style.display = 'flex';
        }
    });

    
    function adjustForPopup() {
        var popup = document.getElementById('actionPopup');
        if (popup && popup.style.display !== 'none') {
            if (fabContainer) fabContainer.style.bottom = '160px';
            if (chatbotToggle) chatbotToggle.style.bottom = '230px';
        } else {
            if (fabContainer) fabContainer.style.bottom = '30px';
            if (chatbotToggle) chatbotToggle.style.bottom = '160px';
        }
    }
    setInterval(adjustForPopup, 500);

    
    document.querySelectorAll('.fab-button').forEach(function (button) {
        button.addEventListener('click', function () {
            this.classList.add('active');
            var self = this;
            setTimeout(function () { self.classList.remove('active'); }, 300);
            if (self.classList.contains('fab-secondary') && fabSecondary) {
                fabSecondary.classList.remove('show');
                if (fabToggle) fabToggle.classList.remove('rotate');
                if (fabToggleIcon) { fabToggleIcon.classList.remove('fa-times'); fabToggleIcon.classList.add('fa-plus'); }
            }
        });
    });

    
    if (!localStorage.getItem('fabWelcomeShown') && fabSecondary && fabToggle && fabToggleIcon) {
        setTimeout(function () {
            fabSecondary.classList.add('show');
            fabToggle.classList.add('rotate');
            fabToggleIcon.classList.remove('fa-plus');
            fabToggleIcon.classList.add('fa-times');
            setTimeout(function () {
                fabSecondary.classList.remove('show');
                fabToggle.classList.remove('rotate');
                fabToggleIcon.classList.remove('fa-times');
                fabToggleIcon.classList.add('fa-plus');
            }, 3000);
            localStorage.setItem('fabWelcomeShown', 'true');
        }, 2000);
    }
});

function playVideo(videoUrl) {
    var modal = new bootstrap.Modal(document.getElementById('videoModal'));
    var videoPlayer = document.getElementById('videoPlayer');
    var videoId = videoUrl.split('/').pop().split('?')[0];
    if (videoUrl.includes('youtube.com')) {
        videoId = videoUrl.split('v=')[1];
        var amp = videoId.indexOf('&');
        if (amp !== -1) videoId = videoId.substring(0, amp);
    }
    videoPlayer.src = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1&rel=0&modestbranding=1';
    modal.show();
    document.getElementById('videoModal').addEventListener('hidden.bs.modal', function () { videoPlayer.src = ''; });
}

var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
        if (entry.isIntersecting) entry.target.classList.add('animate-fadeInUp');
    });
}, { threshold: 0.1 });

document.querySelectorAll(
    '.section-header, .offer-card, .testimonial-card, .car-type-offer, .video-item, .capacity-card, .gallery-preview-item, .benefit-cell'
).forEach(function (el) {
    observer.observe(el);
});

document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
        var targetId = this.getAttribute('href');
        if (targetId === '#') return;
        var targetElement = document.querySelector(targetId);
        if (targetElement) {
            e.preventDefault();
            window.scrollTo({ top: targetElement.offsetTop - 100, behavior: 'smooth' });
        }
    });
});

document.querySelectorAll('.hover-warning').forEach(function (link) {
    link.addEventListener('mouseenter', function () { this.style.color = 'var(--gold-light)'; this.style.transform = 'translateX(4px)'; });
    link.addEventListener('mouseleave', function () { this.style.color = ''; this.style.transform = ''; });
});

var resizeTimer;
window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
        var popup = document.getElementById('actionPopup');
        var fabContainer = document.getElementById('floatingButtons');
        var chatbotToggle = document.getElementById('chatbotToggle');
        if (popup && popup.style.display !== 'none') {
            if (fabContainer) fabContainer.style.bottom = window.innerWidth <= 768 ? '100px' : '160px';
            if (chatbotToggle) chatbotToggle.style.bottom = window.innerWidth <= 768 ? '170px' : '230px';
        }
    }, 250);
});

function filterGalleryItems(category) {
    
    document.querySelectorAll('.gallery-filter-btn').forEach(function (btn) {
        btn.classList.toggle('active', btn.getAttribute('data-filter') === category);
    });

    
    document.querySelectorAll('.gallery-grid-item').forEach(function (item) {
        if (category === 'all' || item.getAttribute('data-category') === category) {
            item.classList.remove('hidden');
        } else {
            item.classList.add('hidden');
        }
    });
}

(function () {
    var sections = ['services', 'gallery', 'about'];
    var navLinks = {};
    sections.forEach(function (id) {
        var link = document.querySelector('.navbar-nav a[href="#' + id + '"]');
        if (link) navLinks[id] = link;
    });
    if (Object.keys(navLinks).length === 0) return;

    window.addEventListener('scroll', function () {
        var scrollPos = window.scrollY + 200;
        var activeId = '';
        sections.forEach(function (id) {
            var section = document.getElementById(id);
            if (section && section.offsetTop <= scrollPos) {
                activeId = id;
            }
        });
        Object.keys(navLinks).forEach(function (id) {
            navLinks[id].classList.toggle('nav-active', id === activeId);
        });
    });
})();

document.addEventListener('DOMContentLoaded', function () {
    if (typeof bootstrap === 'undefined') return;
    if (document.querySelector('.nav-user-btn')) return; 
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('show_login') || urlParams.has('open_signin')) {
        var signInModalEl = document.getElementById('signInModal');
        if (signInModalEl) {
            var modal = bootstrap.Modal.getInstance(signInModalEl) || new bootstrap.Modal(signInModalEl);
            modal.show();
        }
    }
});
