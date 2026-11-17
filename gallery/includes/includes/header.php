<?php

require __DIR__ . '/../../../includes/auth.php';
boot_session();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RG CARTINTWORKS | Window Tinting Davao</title>
    
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #FFC107;
            --primary-dark: #FF9800;
            --secondary: #2c3e50;
            --dark: #1a1a1a;
            --light: #f8f9fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #1a1a1a 0%, #2c3e50 100%);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 800;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-car me-2"></i>RG CARTINTWORKS
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>" href="../index.php">
                        <i class="fas fa-home me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'services') !== false) ? 'active' : '' ?>" href="../services.php">
                        <i class="fas fa-tools me-1"></i> Services
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'index.php' && basename(dirname($_SERVER['PHP_SELF'])) == 'gallery') ? 'active' : '' ?>" href="index.php">
                        <i class="fas fa-images me-1"></i> Gallery
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'about') !== false) ? 'active' : '' ?>" href="../about.php">
                        <i class="fas fa-info-circle me-1"></i> About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], 'contact') !== false) ? 'active' : '' ?>" href="../contact.php">
                        <i class="fas fa-phone me-1"></i> Contact
                    </a>
                </li>
                <li class="nav-item ms-2">
                    <a class="nav-link btn btn-warning text-dark px-4" href="../book.php">
                        <i class="fas fa-calendar-check me-1"></i> Book Now
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div style="height: 76px;"></div>