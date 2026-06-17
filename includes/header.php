<?php
require_once __DIR__ . '/config.php';


$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YukBand! - Temukan & Sewa Studio Musik Favoritmu</title>
    
    
    <meta name="description" content="Sewa studio musik favoritmu dengan mudah secara real-time di YukBand! Menyediakan Solo Space dan Jam Space dengan fasilitas lengkap.">
    <meta name="keywords" content="studio musik, rental studio, sewa studio, band, solo space, jam space, yukband">
    <meta name="author" content="YukBand Dev Team">
    
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    
    
    <link href="<?= $base_path ?>assets/style.css" rel="stylesheet">
</head>
<body>

    
    <div class="bg-studio-wrapper"></div>

    
    <div class="container floating-navbar-container">
        <nav class="navbar navbar-expand-lg navbar-dark floating-navbar">
            <div class="container-fluid p-0">
                
                <a class="navbar-brand-custom" href="<?= $base_path ?>index.php">
                    <img src="<?= $base_path ?>assets/LogoYukBand.png" alt="YukBand! Logo" height="40" class="d-inline-block align-top">
                </a>
                
                
                <button class="navbar-toggler border-0 ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarYukBand" aria-controls="navbarYukBand" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                
                <div class="collapse navbar-collapse" id="navbarYukBand">
                    <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>" href="<?= $base_path ?>index.php">Beranda</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_path ?>index.php#studio-catalog">Studio</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $base_path ?>index.php#workflow">Cara Pesan</a>
                        </li>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($_SESSION['role'] === 'user'): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?= ($current_page == 'book.php') ? 'active' : '' ?>" href="<?= $base_path ?>pages/book.php">Pesan Studio</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= ($current_page == 'my_bookings.php') ? 'active' : '' ?>" href="<?= $base_path ?>pages/my_bookings.php">Reservasi Saya</a>
                                </li>
                            <?php elseif ($_SESSION['role'] === 'admin'): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?= ($current_page == 'admin_dashboard.php') ? 'active' : '' ?>" href="<?= $base_path ?>pages/admin_dashboard.php">Dashboard Admin</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= ($current_page == 'admin_studios.php') ? 'active' : '' ?> font-sans-header" href="<?= $base_path ?>pages/admin_studios.php">Kelola Studio</a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                    
                    
                    <div class="d-flex align-items-center gap-3">
                        <?php if (isset($_SESSION['username'])): ?>
                            <span class="text-white d-none d-lg-inline-block small" style="font-family: var(--font-sans);">
                                Halo, <strong class="logo-text-highlight"><?= htmlspecialchars($_SESSION['username']) ?></strong> (<?= htmlspecialchars(ucfirst($_SESSION['role'])) ?>)
                            </span>
                            <a href="<?= $base_path ?>pages/logout.php" class="btn btn-nav-login" id="btn-logout-navbar">
                                <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                            </a>
                        <?php else: ?>
                            <a href="<?= $base_path ?>pages/login.php" class="btn btn-nav-login" id="btn-login-navbar">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </div>

    
    <main class="flex-shrink-0">
