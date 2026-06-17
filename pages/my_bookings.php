<?php
$base_path = '../';
require_once '../includes/config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];
$success = '';
$error = '';


if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $id_reservasi = intval($_POST['id_reservasi']);
    try {
        
        $stmt = $pdo->prepare("SELECT * FROM reservation WHERE id_reservasi = ? AND id_user = ?");
        $stmt->execute([$id_reservasi, $id_user]);
        $reservation = $stmt->fetch();
        
        if ($reservation) {
            if ($reservation['status_pemesanan'] === 'Dibatalkan') {
                $error = "Reservasi ini sudah dibatalkan sebelumnya!";
            } else {
                
                
                $update_stmt = $pdo->prepare("UPDATE reservation SET status_pemesanan = 'Dibatalkan' WHERE id_reservasi = ?");
                $update_stmt->execute([$id_reservasi]);
                
                $success = "Reservasi berhasil dibatalkan!";
            }
        } else {
            $error = "Akses ditolak atau reservasi tidak ditemukan.";
        }
    } catch (\PDOException $e) {
        $error = "Terjadi kesalahan database: " . $e->getMessage();
    }
}


if (isset($_POST['action']) && $_POST['action'] === 'pay') {
    $id_reservasi = intval($_POST['id_reservasi']);
    try {
        
        $stmt = $pdo->prepare("
            SELECT r.*, p.status_pembayaran 
            FROM reservation r 
            JOIN payment p ON r.id_reservasi = p.id_reservasi 
            WHERE r.id_reservasi = ? AND r.id_user = ?
        ");
        $stmt->execute([$id_reservasi, $id_user]);
        $data = $stmt->fetch();
        
        if ($data) {
            if ($data['status_pemesanan'] === 'Dibatalkan') {
                $error = "Tidak bisa membayar reservasi yang sudah dibatalkan!";
            } elseif ($data['status_pembayaran'] === 'Lunas') {
                $error = "Reservasi ini sudah lunas!";
            } else {
                
                $pdo->beginTransaction();
                
                
                $update_pay = $pdo->prepare("UPDATE payment SET status_pembayaran = 'Lunas', tanggal_bayar = NOW() WHERE id_reservasi = ?");
                $update_pay->execute([$id_reservasi]);
                
                
                $update_res = $pdo->prepare("UPDATE reservation SET status_pemesanan = 'Dikonfirmasi' WHERE id_reservasi = ?");
                $update_res->execute([$id_reservasi]);
                
                $pdo->commit();
                $success = "Pembayaran berhasil disimulasikan! Status pemesanan kini Dikonfirmasi.";
            }
        } else {
            $error = "Akses ditolak atau reservasi tidak ditemukan.";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Terjadi kesalahan sistem: " . $e->getMessage();
    }
}


$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = 5; 
$offset = ($page - 1) * $limit;

try {
    
    $count_query = "
        SELECT COUNT(*) 
        FROM reservation r
        JOIN studio s ON r.id_studio = s.id_studio
        JOIN payment p ON r.id_reservasi = p.id_reservasi
        WHERE r.id_user = :id_user
    ";
    
    if ($search !== '') {
        $count_query .= " AND (s.nama_studio LIKE :search OR r.status_pemesanan LIKE :search OR p.status_pembayaran LIKE :search)";
    }
    
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->bindValue(':id_user', $id_user, PDO::PARAM_INT);
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
    
    
    $data_query = "
        SELECT 
            r.id_reservasi,
            s.nama_studio,
            s.tipe_studio,
            r.tanggal_pesan,
            r.jam_mulai,
            r.durasi,
            r.status_pemesanan,
            p.total_payment,
            p.status_pembayaran
        FROM reservation r
        JOIN studio s ON r.id_studio = s.id_studio
        JOIN payment p ON r.id_reservasi = p.id_reservasi
        WHERE r.id_user = :id_user
    ";
    
    if ($search !== '') {
        $data_query .= " AND (s.nama_studio LIKE :search OR r.status_pemesanan LIKE :search OR p.status_pembayaran LIKE :search)";
    }
    
    $data_query .= " ORDER BY r.id_reservasi DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($data_query);
    $stmt->bindValue(':id_user', $id_user, PDO::PARAM_INT);
    if ($search !== '') {
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $bookings = $stmt->fetchAll();
    
} catch (\PDOException $e) {
    $error = "Gagal memuat data reservasi: " . $e->getMessage();
    $bookings = [];
    $total_pages = 1;
}
?>

<?php include '../includes/header.php'; ?>

<div class="container py-5 booking-section-wrapper" style="min-height: calc(100vh - 200px);">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                <div>
                    <h2 class="section-title text-white">Daftar Reservasi Saya</h2>
                    <p class="section-subtitle">Kelola dan lihat riwayat pemesanan studio musik Anda</p>
                </div>
                <div>
                    <a href="book.php" class="btn btn-primary-custom">
                        <i class="fa-solid fa-calendar-plus me-1"></i> Pesan Studio Lagi
                    </a>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-custom alert-custom-danger" id="bookings-error-alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-custom alert-custom-success" id="bookings-success-alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            
            <div class="search-pagination-bar">
                <form action="my_bookings.php" method="GET" class="search-input-group">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control form-control-custom no-icon" placeholder="Cari studio atau status..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-nav-login" type="submit" style="border-radius: 0 50px 50px 0; margin-left: -2px;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                </form>
                
                
                <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination-custom">
                            
                            <li class="page-item-custom <?= ($page <= 1) ? 'disabled d-none' : '' ?>">
                                <a class="page-link-custom" href="my_bookings.php?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item-custom <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link-custom" href="my_bookings.php?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            
                            <li class="page-item-custom <?= ($page >= $total_pages) ? 'disabled d-none' : '' ?>">
                                <a class="page-link-custom" href="my_bookings.php?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
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
                            <th style="width: 80px;">No.</th>
                            <th>Studio</th>
                            <th>Tanggal Latihan</th>
                            <th>Waktu (WIB)</th>
                            <th style="width: 100px;">Durasi</th>
                            <th>Total Bayar</th>
                            <th>Pembayaran</th>
                            <th>Status Booking</th>
                            <th style="width: 220px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-white-50 py-5">
                                    <i class="fa-solid fa-folder-open mb-3" style="font-size: 2.5rem; display: block;"></i>
                                    Tidak ada data reservasi ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = $offset + 1;
                            foreach ($bookings as $booking): 
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <strong class="text-white"><?= htmlspecialchars($booking['nama_studio']) ?></strong>
                                        <div class="text-white-50 small" style="font-size: 0.8rem;"><?= htmlspecialchars($booking['tipe_studio']) ?></div>
                                    </td>
                                    <td><?= date('d-m-Y', strtotime($booking['tanggal_pesan'])) ?></td>
                                    <td><?= date('H:i', strtotime($booking['jam_mulai'])) ?> WIB</td>
                                    <td><?= htmlspecialchars($booking['durasi']) ?> jam</td>
                                    <td><strong class="text-white">Rp<?= number_format($booking['total_payment'], 0, ',', '.') ?></strong></td>
                                    <td>
                                        <?php if ($booking['status_pembayaran'] === 'Lunas'): ?>
                                            <span class="badge-custom badge-paid"><i class="fa-solid fa-circle-check"></i> Lunas</span>
                                        <?php else: ?>
                                            <span class="badge-custom badge-unpaid"><i class="fa-solid fa-circle-xmark"></i> Belum Lunas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($booking['status_pemesanan'] === 'Dikonfirmasi'): ?>
                                            <span class="badge-custom badge-confirmed"><i class="fa-solid fa-square-check"></i> Dikonfirmasi</span>
                                        <?php elseif ($booking['status_pemesanan'] === 'Dibatalkan'): ?>
                                            <span class="badge-custom badge-cancelled"><i class="fa-solid fa-ban"></i> Dibatalkan</span>
                                        <?php else: ?>
                                            <span class="badge-custom badge-waiting"><i class="fa-solid fa-clock-rotate-left"></i> Menunggu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            
                                            <?php if ($booking['status_pembayaran'] === 'Belum Lunas' && $booking['status_pemesanan'] !== 'Dibatalkan'): ?>
                                                <form action="my_bookings.php" method="POST" onsubmit="return confirmPayment(<?= $booking['id_reservasi'] ?>, <?= $booking['total_payment'] ?>);">
                                                    <input type="hidden" name="id_reservasi" value="<?= $booking['id_reservasi'] ?>">
                                                    <input type="hidden" name="action" value="pay">
                                                    <button type="submit" class="btn-table-action btn-table-pay">
                                                        <i class="fa-solid fa-credit-card"></i> Bayar
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            
                                            <?php if ($booking['status_pemesanan'] !== 'Dibatalkan'): ?>
                                                <form action="my_bookings.php" method="POST" onsubmit="return confirmCancellation('<?= htmlspecialchars($booking['nama_studio']) ?>');">
                                                    <input type="hidden" name="id_reservasi" value="<?= $booking['id_reservasi'] ?>">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <button type="submit" class="btn-table-action btn-table-cancel">
                                                        <i class="fa-solid fa-xmark"></i> Batal
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-white-50 small" style="font-family: var(--font-sans);">Tidak ada aksi</span>
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
function confirmPayment(id, total) {
    return confirm("Simulasi Pembayaran:\n\nApakah Anda yakin ingin membayar tagihan sebesar Rp" + total.toLocaleString('id-ID') + " untuk reservasi #" + id + "?");
}

function confirmCancellation(studioName) {
    return confirm("Konfirmasi Pembatalan:\n\nApakah Anda yakin ingin membatalkan penyewaan di " + studioName + "?\nTindakan ini tidak dapat dibatalkan.");
}
</script>

<?php include '../includes/footer.php'; ?>
