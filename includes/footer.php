
<footer class="bg-dark text-white py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h4 class="fw-bold mb-3 text-warning">Rg Cartint Works Davao</h4>
                <p class="text-light opacity-75 mb-4">
                    Davao's premier window tinting service. Professional installation with Official Warranty 
                    using only authentic films from global brands.
                </p>
                <div class="d-flex gap-3">
                    <a href="#" class="text-white opacity-75 hover-warning"><i class="fab fa-facebook fs-5"></i></a>
                    <a href="#" class="text-white opacity-75 hover-warning"><i class="fab fa-instagram fs-5"></i></a>
                    <a href="#" class="text-white opacity-75 hover-warning"><i class="fab fa-tiktok fs-5"></i></a>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <h5 class="fw-bold mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?= BASE_URL ?>/index.php" class="text-light opacity-75 text-decoration-none hover-warning">Home</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/services.php" class="text-light opacity-75 text-decoration-none hover-warning">Services</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/customer/book.php" class="text-light opacity-75 text-decoration-none hover-warning">Book Appointment</a></li>
                </ul>
            </div>
            <div class="col-lg-4 mb-4">
                <h5 class="fw-bold mb-3">Contact Info</h5>
                <ul class="list-unstyled">
                    <li class="mb-3 d-flex align-items-start">
                        <i class="fas fa-map-marker-alt text-warning mt-1 me-2"></i>
                        <span class="text-light opacity-75">Davao City, Philippines</span>
                    </li>
                    <li class="mb-3 d-flex align-items-center">
                        <i class="fas fa-phone text-warning me-2"></i>
                        <a href="tel:+9382985532" class="text-light opacity-75 text-decoration-none hover-warning">+9382985532</a>
                    </li>
                    <li class="mb-3 d-flex align-items-center">
                        <i class="fas fa-envelope text-warning me-2"></i>
                        <a href="mailto:Greybuster27@gmail.com" class="text-light opacity-75 text-decoration-none hover-warning">Greybuster27@gmail.com</a>
                    </li>
                    <li class="d-flex align-items-center">
                        <i class="fas fa-clock text-warning me-2"></i>
                        <span class="text-light opacity-75">Mon-Sat: 8AM-6PM</span>
                    </li>
                </ul>
            </div>
        </div>
        <hr class="opacity-25 my-4">
        <div class="text-center">
            <small class="opacity-75">&copy; RgCartint Works Davao. All rights reserved. | Premium Window Tint Solutions</small>
        </div>
    </div>
</footer>

<script>
    
    document.querySelectorAll('.hover-warning').forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.color = '#FFC107';
        });
        link.addEventListener('mouseleave', function() {
            this.style.color = '';
        });
    });
</script>
