<?php
$base_path = '../';
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$success = '';
$error = '';

if (isset($_POST['action']) && $_POST['action'] === 'confirm') {
    $id_reservasi = intval($_POST['id_reservasi']);
    try {
        $pdo->beginTransaction();
        
        $stmt_res = $pdo->prepare("UPDATE reservation SET status_pemesanan = 'Dikonfirmasi' WHERE id_reservasi = ?");
        $stmt_res->execute([$id_reservasi]);
        
        $stmt_pay = $pdo->prepare("UPDATE payment SET status_pembayaran = 'Lunas', tanggal_bayar = NOW() WHERE id_reservasi = ?");
        $stmt_pay->execute([$id_reservasi]);
        
        $pdo->commit();
        $success = "Reservasi #" . $id_reservasi . " berhasil dikonfirmasi dan ditandai Lunas!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Gagal mengkonfirmasi reservasi: " . $e->getMessage();
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $id_reservasi = intval($_POST['id_reservasi']);
    try {
        $stmt = $pdo->prepare("UPDATE reservation SET status_pemesanan = 'Dibatalkan' WHERE id_reservasi = ?");
        $stmt->execute([$id_reservasi]);
        
        $success = "Reservasi #" . $id_reservasi . " berhasil dibatalkan!";
    } catch (\PDOException $e) {
        $error = "Gagal membatalkan reservasi: " . $e->getMessage();
    }
}

try {
    $stmt_tot_rev = $pdo->query("SELECT SUM(total_payment) FROM payment WHERE status_pembayaran = 'Lunas'");
    $total_revenue = $stmt_tot_rev->fetchColumn() ?: 0.00;
    
    $stmt_wait = $pdo->query("SELECT COUNT(*) FROM reservation WHERE status_pemesanan = 'Menunggu'");
    $pending_bookings = $stmt_wait->fetchColumn() ?: 0;
    
    $stmt_conf = $pdo->query("SELECT COUNT(*) FROM reservation WHERE status_pemesanan = 'Dikonfirmasi'");
    $confirmed_bookings = $stmt_conf->fetchColumn() ?: 0;
    
    $stmt_monthly = $pdo->query("SELECT * FROM view_transaksi_valid ORDER BY tahun DESC, bulan DESC LIMIT 12");
    $monthly_stats = $stmt_monthly->fetchAll();
    
} catch (\PDOException $e) {
    $error = "Gagal mengambil data ringkasan statistik: " . $e->getMessage();
    $total_revenue = 0;
    $pending_bookings = 0;
    $confirmed_bookings = 0;
    $monthly_stats = [];
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = 5;
$offset = ($page - 1) * $limit;

try {
    $count_query = "SELECT COUNT(*) FROM view_transaksi";
    if ($search !== '') {
        $count_query .= " WHERE (username LIKE :search OR nama_studio LIKE :search OR status_pemesanan LIKE :search OR status_pembayaran LIKE :search)";
    }
    
    $count_stmt = $pdo->prepare($count_query);
    if ($search !== '') {
        $count_stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    $count_stmt->execute();
    $total_rows = $count_stmt->fetchColumn();
    
    $total_pages = ceil($total_rows / $limit);
    if ($total_pages < 1) $total_pages = 1;
    if ($page > $total_pages) {
        $page = $total_pages;
        $offset = ($page - 1) * $limit;
    }
    
    $data_query = "SELECT * FROM view_transaksi";
    if ($search !== '') {
        $data_query .= " WHERE (username LIKE :search OR nama_studio LIKE :search OR status_pemesanan LIKE :search OR status_pembayaran LIKE :search)";
    }
    $data_query .= " ORDER BY id_reservasi DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($data_query);
    if ($search !== '') {
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reservations = $stmt->fetchAll();
    
} catch (\PDOException $e) {
    $error = "Gagal memuat daftar transaksi: " . $e->getMessage();
    $reservations = [];
    $total_pages = 1;
}

$months_in = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>

<?php include '../includes/header.php'; ?>

<div class="container py-5" style="margin-top: 20px; min-height: calc(100vh - 200px);">
    
    <div class="mb-4">
        <h2 class="section-title text-white">Dashboard Admin</h2>
        <p class="section-subtitle">Pantau performa bisnis, kelola reservasi, dan lihat statistik omset</p>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-custom alert-custom-danger" id="admin-error-alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-custom alert-custom-success" id="admin-success-alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    
    <div class="row g-4 mb-5">
        
        <div class="col-md-4">
            <div class="dashboard-stat-card">
                <div class="dashboard-stat-info">
                    <h5>Total Omset Lunas</h5>
                    <p>Rp<?= number_format($total_revenue, 0, ',', '.') ?></p>
                </div>
                <div class="dashboard-stat-icon">
                    <i class="fa-solid fa-money-bill-trend-up"></i>
                </div>
            </div>
        </div>
        
        
        <div class="col-md-4">
            <div class="dashboard-stat-card">
                <div class="dashboard-stat-info">
                    <h5>Menunggu Konfirmasi</h5>
                    <p><?= $pending_bookings ?></p>
                </div>
                <div class="dashboard-stat-icon" style="color: #ffc107;">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
        </div>
        
        
        <div class="col-md-4">
            <div class="dashboard-stat-card">
                <div class="dashboard-stat-info">
                    <h5>Sewa Dikonfirmasi</h5>
                    <p><?= $confirmed_bookings ?></p>
                </div>
                <div class="dashboard-stat-icon" style="color: #28a745;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>
    </div>
    
    
    <div class="row g-4 mb-5">
        
        <div class="col-lg-6">
            <div class="chart-container glass-panel">
                <h4 class="text-white mb-4" style="font-family: var(--font-serif); font-size: 1.5rem;"><i class="fa-solid fa-chart-column me-2 text-warning"></i>Grafik Omset Bulanan</h4>
                
                <?php if (empty($monthly_stats)): ?>
                    <div class="text-center text-white-50 py-5">
                        <i class="fa-solid fa-chart-bar mb-2" style="font-size: 2.5rem;"></i>
                        <p class="small">Belum ada omset transaksi valid yang tercatat.</p>
                    </div>
                <?php else: ?>
                    
                    <svg viewBox="0 0 500 240" width="100%" height="220" style="background: transparent;">
                        
                        <line x1="40" y1="20" x2="480" y2="20" stroke="rgba(255,255,255,0.05)" stroke-width="1" />
                        <line x1="40" y1="80" x2="480" y2="80" stroke="rgba(255,255,255,0.05)" stroke-width="1" />
                        <line x1="40" y1="140" x2="480" y2="140" stroke="rgba(255,255,255,0.05)" stroke-width="1" />
                        <line x1="40" y1="200" x2="480" y2="200" stroke="rgba(255,255,255,0.15)" stroke-width="1" />
                        
                        <?php
                        
                        $max_omset = 10000;
                        foreach ($monthly_stats as $ms) {
                            if ($ms['total_omset'] > $max_omset) {
                                $max_omset = $ms['total_omset'];
                            }
                        }
                        
                        $num_bars = count($monthly_stats);
                        $bar_width = 35;
                        $spacing = 15;
                        $chart_height = 180;
                        
                        
                        $reversed_stats = array_reverse($monthly_stats);
                        foreach ($reversed_stats as $index => $ms) {
                            $x = 50 + $index * ($bar_width + $spacing);
                            $percentage = $ms['total_omset'] / $max_omset;
                            $bar_height = max($percentage * $chart_height, 6);
                            $y = 200 - $bar_height;
                            
                            
                            echo "<rect x='$x' y='$y' width='$bar_width' height='$bar_height' class='chart-bar-rect'></rect>";
                            
                            
                            $label = substr($months_in[$ms['bulan']], 0, 3) . " " . substr($ms['tahun'], 2);
                            $text_x = $x + $bar_width / 2;
                            echo "<text x='$text_x' y='218' text-anchor='middle' class='chart-text'>$label</text>";
                            
                            
                            $val_label = "Rp" . number_format($ms['total_omset'] / 1000, 0) . "k";
                            $val_y = $y - 6;
                            echo "<text x='$text_x' y='$val_y' text-anchor='middle' class='chart-text-value'>$val_label</text>";
                        }
                        ?>
                    </svg>
                <?php endif; ?>
            </div>
        </div>
        
        
        <div class="col-lg-6">
            <div class="chart-container glass-panel" style="height: calc(100% - 30px); overflow-y: auto;">
                <h4 class="text-white mb-4" style="font-family: var(--font-serif); font-size: 1.5rem;"><i class="fa-solid fa-list-check me-2 text-warning"></i>Statistik Penyewaan Perbulan</h4>
                
                <div class="table-responsive">
                    <table class="table table-borderless text-white mb-0" style="font-family: var(--font-sans); font-size: 0.9rem;">
                        <thead>
                            <tr class="border-bottom border-white-10 text-white-50">
                                <th>Bulan & Tahun</th>
                                <th class="text-center">Jumlah Transaksi</th>
                                <th class="text-end">Total Omset</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($monthly_stats)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-white-50 py-4">Belum ada statistik tersedia.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($monthly_stats as $stat): ?>
                                    <tr class="border-bottom border-white-05">
                                        <td class="py-3 font-sans-header"><?= $months_in[$stat['bulan']] ?> <?= $stat['tahun'] ?></td>
                                        <td class="py-3 text-center"><?= $stat['jumlah_transaksi'] ?> kali sewa</td>
                                        <td class="py-3 text-end text-success font-sans-header">Rp<?= number_format($stat['total_omset'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    
    <div class="row">
        <div class="col-12">
            <h4 class="text-white mb-3" style="font-family: var(--font-serif); font-size: 1.7rem;"><i class="fa-solid fa-rectangle-list me-2 text-warning"></i>Kelola Semua Reservasi</h4>
            
            
            <div class="search-pagination-bar">
                <form action="admin_dashboard.php" method="GET" class="search-input-group">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control form-control-custom no-icon" placeholder="Cari username, studio, atau status..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-nav-login" type="submit" style="border-radius: 0 50px 50px 0; margin-left: -2px;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                </form>
                
                
                <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination-custom">
                            <li class="page-item-custom <?= ($page <= 1) ? 'disabled d-none' : '' ?>">
                                <a class="page-link-custom" href="admin_dashboard.php?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item-custom <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link-custom" href="admin_dashboard.php?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item-custom <?= ($page >= $total_pages) ? 'disabled d-none' : '' ?>">
                                <a class="page-link-custom" href="admin_dashboard.php?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
            
            
            <div class="table-responsive-glass">
                <table class="table-glass">
                    <thead>
                        <tr>
                            <th style="width: 70px;">ID</th>
                            <th>Penyewa</th>
                            <th>Studio</th>
                            <th>Tanggal & Waktu</th>
                            <th style="width: 80px;">Durasi</th>
                            <th>Nominal</th>
                            <th>Pembayaran</th>
                            <th>Status Reservasi</th>
                            <th style="text-align: center; width: 220px;">Aksi Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservations)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-white-50 py-5">
                                    <i class="fa-solid fa-folder-open mb-3" style="font-size: 2.5rem; display: block;"></i>
                                    Tidak ada data reservasi ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reservations as $res): ?>
                                <tr>
                                    <td>#<?= $res['id_reservasi'] ?></td>
                                    <td><strong class="text-white"><i class="fa-solid fa-user-circle me-1 opacity-50"></i><?= htmlspecialchars($res['username']) ?></strong></td>
                                    <td>
                                        <strong class="text-white"><?= htmlspecialchars($res['nama_studio']) ?></strong>
                                        <div class="text-white-50 small" style="font-size: 0.8rem;"><?= htmlspecialchars($res['tipe_studio']) ?></div>
                                    </td>
                                    <td>
                                        <div><i class="fa-solid fa-calendar-day opacity-50 me-1"></i><?= date('d-m-Y', strtotime($res['tanggal_pesan'])) ?></div>
                                        <div class="text-white-50 small"><i class="fa-solid fa-clock opacity-50 me-1"></i><?= date('H:i', strtotime($res['jam_mulai'])) ?> WIB</div>
                                    </td>
                                    <td><?= htmlspecialchars($res['durasi']) ?> jam</td>
                                    <td><strong class="text-white">Rp<?= number_format($res['total_payment'], 0, ',', '.') ?></strong></td>
                                    <td>
                                        <?php if ($res['status_pembayaran'] === 'Lunas'): ?>
                                            <span class="badge-custom badge-paid"><i class="fa-solid fa-check"></i> Lunas</span>
                                        <?php else: ?>
                                            <span class="badge-custom badge-unpaid"><i class="fa-solid fa-xmark"></i> Belum Lunas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($res['status_pemesanan'] === 'Dikonfirmasi'): ?>
                                            <span class="badge-custom badge-confirmed"><i class="fa-solid fa-circle-check"></i> Dikonfirmasi</span>
                                        <?php elseif ($res['status_pemesanan'] === 'Dibatalkan'): ?>
                                            <span class="badge-custom badge-cancelled"><i class="fa-solid fa-ban"></i> Dibatalkan</span>
                                        <?php else: ?>
                                            <span class="badge-custom badge-waiting"><i class="fa-solid fa-clock"></i> Menunggu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            
                                            <?php if ($res['status_pemesanan'] === 'Menunggu'): ?>
                                                <form action="admin_dashboard.php" method="POST" onsubmit="return confirmConfirmAction(<?= $res['id_reservasi'] ?>, '<?= htmlspecialchars($res['username']) ?>');">
                                                    <input type="hidden" name="id_reservasi" value="<?= $res['id_reservasi'] ?>">
                                                    <input type="hidden" name="action" value="confirm">
                                                    <button type="submit" class="btn-table-action btn-table-confirm">
                                                        <i class="fa-solid fa-check-circle"></i> Setujui
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            
                                            <?php if ($res['status_pemesanan'] !== 'Dibatalkan'): ?>
                                                <form action="admin_dashboard.php" method="POST" onsubmit="return confirmCancelAction(<?= $res['id_reservasi'] ?>, '<?= htmlspecialchars($res['username']) ?>');">
                                                    <input type="hidden" name="id_reservasi" value="<?= $res['id_reservasi'] ?>">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <button type="submit" class="btn-table-action btn-table-cancel">
                                                        <i class="fa-solid fa-trash-can"></i> Batalkan
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-white-50 small" style="font-family: var(--font-sans);">Selesai / Batal</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function confirmConfirmAction(id, username) {
    return confirm("Konfirmasi Persetujuan:\n\nApakah Anda yakin ingin menyetujui reservasi #" + id + " oleh user '" + username + "'?\nTindakan ini akan mengaktifkan reservasi dan melunasi pembayaran.");
}

function confirmCancelAction(id, username) {
    return confirm("Konfirmasi Pembatalan:\n\nApakah Anda yakin ingin membatalkan reservasi #" + id + " oleh user '" + username + "'?\nSistem akan mencatat log pembatalan otomatis untuk tindakan ini.");
}
</script>

<?php include '../includes/footer.php'; ?>
