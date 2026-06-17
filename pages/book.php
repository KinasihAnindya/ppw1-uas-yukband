<?php
$base_path = '../';
require_once '../includes/config.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'user') {
    
    header("Location: admin_dashboard.php");
    exit;
}

$error = '';
$success = '';


try {
    $stmt = $pdo->query("SELECT * FROM studio ORDER BY nama_studio ASC");
    $studios = $stmt->fetchAll();
} catch (\PDOException $e) {
    $error = "Gagal memuat data studio: " . $e->getMessage();
    $studios = [];
}


$prefill_studio_id = isset($_GET['id_studio']) ? intval($_GET['id_studio']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_studio = intval($_POST['id_studio']);
    $tanggal_pesan = trim($_POST['tanggal_pesan']);
    $jam_mulai = trim($_POST['jam_mulai']) . ":00"; 
    $durasi = intval($_POST['durasi']);
    $id_user = $_SESSION['user_id'];
    
    
    if ($id_studio <= 0 || empty($tanggal_pesan) || empty($_POST['jam_mulai']) || $durasi <= 0) {
        $error = "Semua kolom pemesanan wajib diisi dengan benar!";
    } else {
        
        $today = date('Y-m-d');
        if ($tanggal_pesan < $today) {
            $error = "Tanggal pemesanan tidak boleh di masa lalu!";
        } else {
            try {
                
                
                $stmt = $pdo->prepare("SELECT cek_ketersediaan_studio(?, ?, ?) AS terisi");
                $stmt->execute([$id_studio, $tanggal_pesan, $jam_mulai]);
                $terisi = $stmt->fetchColumn();
                
                if ($terisi > 0) {
                    $error = "Studio sudah dipesan pada tanggal dan jam tersebut! Silakan pilih waktu lain.";
                } else {
                    
                    
                    $stmt = $pdo->prepare("INSERT INTO reservation (id_user, id_studio, tanggal_pesan, jam_mulai, durasi, status_pemesanan) VALUES (?, ?, ?, ?, ?, 'Menunggu')");
                    $stmt->execute([$id_user, $id_studio, $tanggal_pesan, $jam_mulai, $durasi]);
                    
                    $success = "Reservasi studio berhasil diajukan! Silakan lakukan simulasi pembayaran pada menu 'Reservasi Saya'.";
                }
            } catch (\PDOException $e) {
                $error = "Terjadi kesalahan sistem: " . $e->getMessage();
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container py-5" style="margin-top: 20px; min-height: calc(100vh - 200px);">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="glass-panel-dark p-5">
                <div class="text-center mb-4">
                    <span class="badge badge-custom text-white mb-2" style="background: var(--secondary-color); font-size:0.8rem;">RESERVASI STUDIO</span>
                    <h2 class="text-white" style="font-family: var(--font-serif); font-size: 2.2rem;">Form Pemesanan</h2>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-custom alert-custom-danger" id="book-error-alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-custom alert-custom-success" id="book-success-alert">
                        <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($success) ?> 
                        <br><a href="my_bookings.php" class="alert-link text-white font-sans-header mt-2 d-inline-block"><i class="fa-solid fa-list-check me-1"></i> Lihat Daftar Reservasi Saya</a>
                    </div>
                <?php endif; ?>
                
                <form action="book.php" method="POST" id="bookingForm" onsubmit="return confirmBookingSubmit();">
                    
                    <div class="form-group-custom">
                        <label for="id_studio" class="form-label-custom">Pilih Studio</label>
                        <div class="input-container-custom">
                            <i class="fa-solid fa-music"></i>
                            <select name="id_studio" id="id_studio" class="form-control-custom" onchange="updateEstimatedPrice();">
                                <option value="">-- Pilih Studio --</option>
                                <?php foreach ($studios as $studio): ?>
                                    <option value="<?= $studio['id_studio'] ?>" data-harga="<?= $studio['harga'] ?>" <?= ($prefill_studio_id == $studio['id_studio']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($studio['nama_studio']) ?> (<?= htmlspecialchars($studio['tipe_studio']) ?>) - Rp<?= number_format($studio['harga'], 0, ',', '.') ?>/jam
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    
                    <div class="form-group-custom">
                        <label for="tanggal_pesan" class="form-label-custom">Tanggal Pemesanan</label>
                        <div class="input-container-custom">
                            <i class="fa-solid fa-calendar-days"></i>
                            <input type="date" name="tanggal_pesan" id="tanggal_pesan" class="form-control-custom" min="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    
                    
                    <div class="form-group-custom">
                        <label for="jam_mulai" class="form-label-custom">Jam Mulai</label>
                        <div class="input-container-custom">
                            <i class="fa-solid fa-clock"></i>
                            <select name="jam_mulai" id="jam_mulai" class="form-control-custom">
                                <option value="">-- Pilih Jam --</option>
                                <?php
                                
                                for ($h = 8; $h <= 22; $h++) {
                                    $time_str = sprintf('%02d:00', $h);
                                    echo "<option value='$time_str'>$time_str WIB</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    
                    <div class="form-group-custom">
                        <label for="durasi" class="form-label-custom">Durasi Latihan (Jam)</label>
                        <div class="input-container-custom">
                            <i class="fa-solid fa-hourglass-half"></i>
                            <input type="number" name="durasi" id="durasi" class="form-control-custom" placeholder="Masukkan durasi dalam jam (minimal 1)" min="1" max="24" oninput="updateEstimatedPrice();">
                        </div>
                    </div>
                    
                    
                    <div class="mb-4 p-3 rounded" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); font-family: var(--font-sans);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-white-50 small">Estimasi Total Bayar:</span>
                            <span id="estimated_price" class="font-sans-header text-white" style="font-size: 1.15rem;">Rp0</span>
                        </div>
                    </div>
                    
                    
                    <button type="submit" class="btn btn-auth-submit" id="btn-booking-submit">Pesan Sekarang</button>
                </form>
            </div>
        </div>
    </div>
</div>


<script>

function formatRupiah(value) {
    return "Rp" + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function updateEstimatedPrice() {
    var studioSelect = document.getElementById('id_studio');
    var durasiInput = document.getElementById('durasi');
    var estimateSpan = document.getElementById('estimated_price');
    
    var selectedOption = studioSelect.options[studioSelect.selectedIndex];
    var harga = 0;
    
    if (selectedOption && selectedOption.value !== "") {
        harga = parseFloat(selectedOption.getAttribute('data-harga'));
    }
    
    var durasi = parseInt(durasiInput.value) || 0;
    if (durasi < 0) durasi = 0;
    
    var total = harga * durasi;
    estimateSpan.innerHTML = formatRupiah(total);
}


window.onload = function() {
    updateEstimatedPrice();
};

function confirmBookingSubmit() {
    var studioSelect = document.getElementById('id_studio');
    var tanggalInput = document.getElementById('tanggal_pesan');
    var jamSelect = document.getElementById('jam_mulai');
    var durasiInput = document.getElementById('durasi');
    
    var studio = studioSelect.value;
    var tanggal = tanggalInput.value;
    var jam = jamSelect.value;
    var durasi = parseInt(durasiInput.value) || 0;
    
    
    var existingAlert = document.getElementById('js-validation-alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    var errorMessage = "";
    
    if (studio === "" || tanggal === "" || jam === "" || durasi <= 0) {
        errorMessage = "Semua kolom wajib diisi dengan benar!";
    } else {
        
        var today = new Date();
        today.setHours(0,0,0,0);
        var inputDate = new Date(tanggal);
        inputDate.setHours(0,0,0,0);
        
        if (inputDate < today) {
            errorMessage = "Tanggal pemesanan tidak boleh di masa lalu!";
        }
    }
    
    if (errorMessage !== "") {
        var alertDiv = document.createElement('div');
        alertDiv.id = 'js-validation-alert';
        alertDiv.className = 'alert alert-custom alert-custom-danger';
        alertDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-2"></i> ' + errorMessage;
        
        var form = document.getElementById('bookingForm');
        form.insertBefore(alertDiv, form.firstChild);
        return false; 
    }
    
    
    var studioText = studioSelect.options[studioSelect.selectedIndex].text.split(' - ')[0];
    return confirm("Konfirmasi Pemesanan:\n\nStudio: " + studioText + "\nTanggal: " + tanggal + "\nJam Mulai: " + jam + " WIB\nDurasi: " + durasi + " jam\n\nApakah Anda yakin ingin memproses pemesanan ini?");
}
</script>

<?php include '../includes/footer.php'; ?>
