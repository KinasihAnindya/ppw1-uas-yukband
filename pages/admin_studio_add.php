<?php
$base_path = '../';
require_once '../includes/config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_studio = trim($_POST['nama_studio']);
    $tipe_studio = trim($_POST['tipe_studio']);
    $harga = floatval($_POST['harga']);
    $deskripsi = trim($_POST['deskripsi']);
    
    
    if (empty($nama_studio) || empty($tipe_studio) || $harga <= 0 || empty($deskripsi)) {
        $error = "Semua kolom input wajib diisi dengan benar!";
    } elseif (!in_array($tipe_studio, ['Solo Space', 'Jam Space'])) {
        $error = "Tipe studio tidak valid!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO studio (nama_studio, tipe_studio, harga, deskripsi) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nama_studio, $tipe_studio, $harga, $deskripsi]);
            
            
            header("Location: admin_studios.php?success=" . urlencode("Studio baru '" . $nama_studio . "' berhasil ditambahkan!"));
            exit;
        } catch (\PDOException $e) {
            $error = "Terjadi kesalahan database: " . $e->getMessage();
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
                    <span class="badge badge-custom text-white mb-2" style="background: var(--secondary-color); font-size:0.8rem;">PENGELOLAAN STUDIO</span>
                    <h2 class="text-white" style="font-family: var(--font-serif); font-size: 2.2rem;">Tambah Studio</h2>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-custom alert-custom-danger" id="add-studio-error-alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form action="admin_studio_add.php" method="POST" id="addStudioForm" onsubmit="return validateAddStudioForm();">
                    
                    <div class="form-group-custom">
                        <label for="nama_studio" class="form-label-custom">Nama Studio</label>
                        <div class="input-container-custom">
                            <i class="fa-solid fa-guitar"></i>
                            <input type="text" name="nama_studio" id="nama_studio" class="form-control-custom" placeholder="Masukkan nama studio (contoh: Studio Yogya 1)" autocomplete="off">
                        </div>
                    </div>
                    
                    
                    <div class="form-group-custom">
                        <label for="tipe_studio" class="form-label-custom">Tipe Studio</label>
                        <div class="input-container-custom">
                            <i class="fa-solid fa-sliders"></i>
                            <select name="tipe_studio" id="tipe_studio" class="form-control-custom">
                                <option value="">-- Pilih Tipe --</option>
                                <option value="Solo Space">Solo Space</option>
                                <option value="Jam Space">Jam Space</option>
                            </select>
                        </div>
                    </div>
                    
                    
                    <div class="form-group-custom">
                        <label for="harga" class="form-label-custom">Harga Sewa Per Jam (Rupiah)</label>
                        <div class="input-container-custom">
                            <i class="fa-solid fa-money-bill-wave"></i>
                            <input type="number" name="harga" id="harga" class="form-control-custom" placeholder="Masukkan nominal harga (contoh: 40000)" min="1">
                        </div>
                    </div>
                    
                    
                    <div class="form-group-custom">
                        <label for="deskripsi" class="form-label-custom">Deskripsi Studio & Fasilitas</label>
                        <div class="input-container-custom">
                            <i class="fa-solid fa-comment-dots" style="top: 25px;"></i>
                            <textarea name="deskripsi" id="deskripsi" class="form-control-custom" rows="4" placeholder="Masukkan deskripsi studio dan rincian fasilitas..." style="border-radius: 20px; padding-left: 48px; padding-top: 12px; resize: none;"></textarea>
                        </div>
                    </div>
                    
                    
                    <div class="d-flex gap-3 mt-4">
                        <a href="admin_studios.php" class="btn btn-auth-submit text-center d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.15); color: #ffffff !important; border: 1px solid rgba(255,255,255,0.25); flex: 1;">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-auth-submit" style="flex: 2; margin-top: 0; margin-bottom: 0;">Simpan Studio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
function validateAddStudioForm() {
    var namaInput = document.getElementById('nama_studio');
    var tipeInput = document.getElementById('tipe_studio');
    var hargaInput = document.getElementById('harga');
    var descInput = document.getElementById('deskripsi');
    
    var nama = namaInput.value.trim();
    var tipe = tipeInput.value;
    var harga = parseFloat(hargaInput.value) || 0;
    var desc = descInput.value.trim();
    
    
    var existingAlert = document.getElementById('js-validation-alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    var errorMessage = "";
    
    if (nama === "" || tipe === "" || harga <= 0 || desc === "") {
        errorMessage = "Semua kolom input wajib diisi dengan benar!";
    }
    
    if (errorMessage !== "") {
        var alertDiv = document.createElement('div');
        alertDiv.id = 'js-validation-alert';
        alertDiv.className = 'alert alert-custom alert-custom-danger';
        alertDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-2"></i> ' + errorMessage;
        
        var form = document.getElementById('addStudioForm');
        form.insertBefore(alertDiv, form.firstChild);
        
        
        if (nama === "") namaInput.focus();
        else if (tipe === "") tipeInput.focus();
        else if (harga <= 0) hargaInput.focus();
        else descInput.focus();
        
        return false;
    }
    return true;
}
</script>

<?php include '../includes/footer.php'; ?>
