<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">
            <i class="fas fa-car me-2"></i>RG CARTINTWORKS
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>" href="../index.php">
                        Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'services.php') ? 'active' : '' ?>" href="../services.php">
                        Services
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>" href="index.php">
                        Gallery
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'about.php') ? 'active' : '' ?>" href="../about.php">
                        About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'contact.php') ? 'active' : '' ?>" href="../contact.php">
                        Contact
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn btn-warning text-dark ms-2 px-4" href="../book.php">
                        <i class="fas fa-calendar-check me-2"></i>Book Now
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>