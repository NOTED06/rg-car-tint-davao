<footer class="bg-dark text-white py-5 mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h4 class="fw-bold mb-3 text-warning">RG CARTINTWORKS</h4>
                <p class="text-light opacity-75 mb-4">
                    Davao's premier car window tinting specialist. Professional installation 
                    with lifetime warranty.
                </p>
                <div class="d-flex gap-3">
                    <a href="#" class="text-white opacity-75"><i class="fab fa-facebook fs-5"></i></a>
                    <a href="#" class="text-white opacity-75"><i class="fab fa-instagram fs-5"></i></a>
                    <a href="#" class="text-white opacity-75"><i class="fab fa-tiktok fs-5"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <h5 class="fw-bold mb-3">Services</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="../services.php" class="text-light opacity-75 text-decoration-none">Car Tinting</a></li>
                    <li class="mb-2"><a href="../services.php" class="text-light opacity-75 text-decoration-none">SUV Tinting</a></li>
                    <li class="mb-2"><a href="../services.php" class="text-light opacity-75 text-decoration-none">Tint Removal</a></li>
                    <li class="mb-2"><a href="../services.php" class="text-light opacity-75 text-decoration-none">Tint Repair</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6">
                <h5 class="fw-bold mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="../index.php" class="text-light opacity-75 text-decoration-none">Home</a></li>
                    <li class="mb-2"><a href="index.php" class="text-light opacity-75 text-decoration-none">Gallery</a></li>
                    <li class="mb-2"><a href="../about.php" class="text-light opacity-75 text-decoration-none">About</a></li>
                    <li class="mb-2"><a href="../book.php" class="text-light opacity-75 text-decoration-none">Book Now</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h5 class="fw-bold mb-3">Contact Us</h5>
                <ul class="list-unstyled">
                    <li class="mb-3 d-flex align-items-start">
                        <i class="fas fa-map-marker-alt text-warning mt-1 me-2"></i>
                        <span class="text-light opacity-75">Davao City, Philippines</span>
                    </li>
                    <li class="mb-3 d-flex align-items-center">
                        <i class="fas fa-phone text-warning me-2"></i>
                        <a href="tel:+09970628687" class="text-light opacity-75 text-decoration-none">+0997 062 8687</a>
                    </li>
                    <li class="mb-3 d-flex align-items-center">
                        <i class="fas fa-envelope text-warning me-2"></i>
                        <a href="mailto:rminguillo12@gmail.com" class="text-light opacity-75 text-decoration-none">rminguillo12@gmail.com</a>
                    </li>
                    <li class="d-flex align-items-center">
                        <i class="fas fa-clock text-warning me-2"></i>
                        <span class="text-light opacity-75">Mon-Sat: 8AM-6PM | Sun: 9AM-4PM</span>
                    </li>
                </ul>
            </div>
        </div>

        <hr class="opacity-25 my-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <small class="opacity-75">&copy; <?= date('Y') ?> RG CARTINTWORKS. All rights reserved.</small>
            </div>
            <div class="col-md-6 text-md-end">
                <small class="opacity-75">Professional Car Window Tinting in Davao</small>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

document.querySelectorAll('a').forEach(link => {
    link.addEventListener('mouseenter', function() {
        if (this.classList.contains('text-light')) {
            this.style.color = '#FFC107';
        }
    });
    link.addEventListener('mouseleave', function() {
        if (this.classList.contains('text-light')) {
            this.style.color = '';
        }
    });
});
</script>
</body>
</html>