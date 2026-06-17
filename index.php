<?php
$base_path = './';
require_once 'includes/config.php';

try {
    
    $stmt = $pdo->query("SELECT * FROM studio ORDER BY id_studio ASC");
    $studios = $stmt->fetchAll();
} catch (\PDOException $e) {
    $studios = [];
    $error_msg = "Gagal mengambil data studio: " . $e->getMessage();
}


function getFacilities($tipe_studio) {
    if ($tipe_studio === 'Solo Space') {
        return [
            '1 orang per sesi',
            'Tersedia gitar, keyboard & amplifier',
            'AC, Wi-Fi, dan recording monitor'
        ];
    } else {
        return [
            'Maksimal 6 orang per sesi',
            'Drum set, amplifier, keyboard & mixer',
            'Sound system profesional dan AC'
        ];
    }
}
?>

<?php include 'includes/header.php'; ?>


<section id="hero" class="container-fluid d-flex flex-column justify-content-center align-items-start hero-with-bg" style="min-height: 100vh; padding-top: 100px; padding-bottom: 60px; position: relative;">
    
    <div class="container">
        
        <div class="row w-100 align-items-center">
            <div class="col-lg-7">
                <h1 class="hero-title">Temukan Studio Musik Favoritmu dengan Mudah!</h1>
                
                <div class="d-flex flex-column gap-2 mb-4">
                    <div class="hero-info-pill">
                        <i class="fa-solid fa-check"></i> Cek <b>ketersediaan studio</b> secara real-time
                    </div>
                    <div class="hero-info-pill">
                        <i class="fa-solid fa-check"></i> <b>Booking online</b> kapan saja dan di mana saja
                    </div>
                    <div class="hero-info-pill">
                        <i class="fa-solid fa-check"></i> <b>Kelola jadwal</b> latihan dengan lebih praktis
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="pages/book.php" class="btn btn-primary-custom" id="btn-hero-cta">
                        Pesan Studio <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-12 mt-5">
                <div class="stats-capsule">
                    <div class="row text-center align-items-center">
                        <div class="col-md-4 mb-3 mb-md-0 border-end border-white-10">
                            <div class="stat-item">
                                <div class="stat-number">500+</div>
                                <div class="stat-label">Pelanggan</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0 border-end border-white-10">
                            <div class="stat-item">
                                <div class="stat-number">10+</div>
                                <div class="stat-label">Studio</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-item">
                                <div class="stat-number">2000+</div>
                                <div class="stat-label">Transaksi Berhasil</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> 
    </div> 
</section>


<section id="workflow" class="py-5" style="background: rgba(0, 0, 0, 0.25); border-top: 1px solid rgba(255, 255, 255, 0.05); border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
    <div class="container py-4">
        <h2 class="text-center text-white mb-5" style="font-family: var(--font-serif); font-size: 2.5rem;">
            Pesan Studio dengan 4 Langkah!
        </h2>
        
        <div class="row g-4">
            
            <div class="col-md-6 col-lg-3">
                <div class="step-card">
                    <div class="step-icon">
                        <i class="fa-solid fa-music text-white"></i>
                    </div>
                    <h4 class="step-title">Pilih Studio</h4>
                    <p class="step-desc">Jelajahi daftar studio yang tersedia, lalu pilih jenis studio yang sesuai dengan kebutuhanmu.</p>
                </div>
            </div>
            
            
            <div class="col-md-6 col-lg-3">
                <div class="step-card">
                    <div class="step-icon">
                        <i class="fa-solid fa-calendar-check text-white"></i>
                    </div>
                    <h4 class="step-title">Tentukan Jadwal</h4>
                    <p class="step-desc">Pilih tanggal dan jam yang masih tersedia sesuai waktu latihan yang kamu inginkan.</p>
                </div>
            </div>
            
            
            <div class="col-md-6 col-lg-3">
                <div class="step-card">
                    <div class="step-icon">
                        <i class="fa-solid fa-file-invoice text-white"></i>
                    </div>
                    <h4 class="step-title">Isi Data Pemesanan</h4>
                    <p class="step-desc">Lengkapi informasi seperti nama, nomor telepon, dan durasi penggunaan studio.</p>
                </div>
            </div>
            
            
            <div class="col-md-6 col-lg-3">
                <div class="step-card">
                    <div class="step-icon">
                        <i class="fa-solid fa-circle-check text-white"></i>
                    </div>
                    <h4 class="step-title">Konfirmasi Booking</h4>
                    <p class="step-desc">Periksa detail pemesanan, lalu lakukan konfirmasi. Reservasi berhasil dan siap digunakan!</p>
                </div>
            </div>
        </div>
    </div>
</section>


<section id="studio-catalog" class="container py-5">
    <div class="text-center py-4">
        <h2 class="text-white mb-2" style="font-family: var(--font-serif); font-size: 2.5rem;">Katalog Studio</h2>
        <p class="text-white-50 mb-5" style="font-family: var(--font-sans);">Pilih studio terbaik yang sesuai dengan kebutuhan latihan musikmu</p>
    </div>
    
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-custom alert-custom-danger text-center">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>
    
    <div class="row g-4 justify-content-center">
        <?php if (empty($studios)): ?>
            <div class="col-12 text-center text-white-50 py-5">
                <i class="fa-solid fa-folder-open mb-3" style="font-size: 3rem;"></i>
                <p>Belum ada data studio di database.</p>
            </div>
        <?php else: ?>
            <?php foreach ($studios as $studio): ?>
                <div class="col-md-6 col-lg-5 col-xl-4 d-flex">
                    <div class="catalog-card">
                        
                        <div class="catalog-img-container">
                            <?php 
                            
                        $gambar_file = 'default.jpg'; 
    
                        switch ($studio['tipe_studio']) {
                            case 'Solo Space':
                                $gambar_file = 'assets/SoloSpace.png';
                                break;
                            case 'Jam Space':
                                $gambar_file = 'assets/JamSpace.png';
                                break;
                        }
                        ?>
    
                        <img src="<?= $gambar_file ?>" alt="<?= htmlspecialchars($studio['nama_studio']) ?>" class="w-100 h-100 object-fit-cover border-bottom border-white-10">
    
                        <div class="price-tag">
                            Rp<?= number_format($studio['harga'], 0, ',', '.') ?>/jam
                        </div>
                    </div>
                        
                        
                        <div class="catalog-body">
                            <h3 class="catalog-title text-white"><?= htmlspecialchars($studio['nama_studio']) ?> <span class="badge badge-custom text-white" style="font-size:0.7rem; background: var(--secondary-color);"><?= htmlspecialchars($studio['tipe_studio']) ?></span></h3>
                            <p class="catalog-desc"><?= htmlspecialchars($studio['deskripsi']) ?></p>
                            
                            
                            <div class="catalog-facilities-title">Fasilitas:</div>
                            <ul class="catalog-facilities-list">
                                <?php foreach (getFacilities($studio['tipe_studio']) as $facility): ?>
                                    <li><?= htmlspecialchars($facility) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            
                            
                            <div class="mt-auto">
                                <a href="pages/book.php?id_studio=<?= $studio['id_studio'] ?>" class="btn btn-primary-custom w-100 justify-content-center align-items-center">
                                    Pesan Sekarang <i class="fa-solid fa-calendar-plus ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>