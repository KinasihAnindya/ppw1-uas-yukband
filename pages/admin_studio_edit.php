<?php
$base_path = '../';
require_once '../includes/config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';


$id_studio = isset($_GET['id_studio']) ? intval($_GET['id_studio']) : 0;

if ($id_studio <= 0) {
    header("Location: admin_studios.php");
    exit;
}


try {
    $stmt = $pdo->prepare("SELECT * FROM studio WHERE id_studio = ?");
    $stmt->execute([$id_studio]);
    $studio = $stmt->fetch();
    
    if (!$studio) {
        header("Location: admin_studios.php?error=" . urlencode("Studio tidak ditemukan!"));
        exit;
    }
} catch (\PDOException $e) {
    header("Location: admin_studios.php?error=" . urlencode("Terjadi kesalahan database: " . $e->getMessage()));
    exit;
}

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
            $stmt = $pdo->prepare("UPDATE studio SET nama_studio = ?, tipe_studio = ?, harga = ?, deskripsi = ? WHERE id_studio = ?");
            $stmt->execute([$nama_studio, $tipe_studio, $harga, $deskripsi, $id_studio]);
            
            
            header("Location: admin_studios.php?success=" . urlencode("Perubahan studio '" . $nama_studio . "' berhasil disimpan!"));
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
                    <h2 class="text-white" style="font-family: var(--font-serif); font-size: 2.2rem;">Edit Studio</h2>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-custom alert-custom-danger" id="edit-studio-error-alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form action="admin_studio_edit.php?id_studio=<?= $id_studio ?>" method="POST" id="editStudioForm" onsubmit="return confirmEditStudioSubmit();">
                    
                    <div class="form-group-custom">
                        <label for="nama_studio" class="form-label-custom">Nama Studio</label>
                        <div class="input-container-custom">
                            <i class="fa-solid fa-guitar"></i>
                            <input type="text" name="nama_studio" id="nama_studio" class="form-control-custom" placeholder="Masukkan nama studio" value="<?= htmlspecialchars($studio['nama_studio']) ?>" autocomplete="off">
                        </div>
                    </div>
                    
                    
                    <div class="form-group-custom">
                        <label for="tipe_studio" class="form-label-custom">Tipe Studio</label>
                        <div class="input-container-custom">
                            <i class="fa-solid fa-sliders"></i>
                            <select name="tipe_studio" id="tipe_studio" class="form-control-custom">
                                <option value="">-- Pilih Tipe --</option>
                                <option value="Solo Space" <?= ($studio['tipe_studio'] === 'Solo Space') ? 'selected' : '' ?>>Solo Space</option>
                                <option value="Jam Space" <?= ($studio['tipe_studio'] === 'Jam Space') ? 'selected' : '' ?>>Jam Space</option>
                            </select>
                        </div>
                    </div>
                    
                    
                    <div class="form-group-custom">
                        <label for="harga" class="form-label-custom">Harga Sewa Per Jam (Rupiah)</label>
                        <div class="input-container-custom">
                            <i class="fa-solid fa-money-bill-wave"></i>
                            <input type="number" name="harga" id="harga" class="form-control-custom" placeholder="Masukkan nominal harga" value="<?= intval($studio['harga']) ?>" min="1">
                        </div>
                    </div>
                    
                    
                    <div class="form-group-custom">
                        <label for="deskripsi" class="form-label-custom">Deskripsi Studio & Fasilitas</label>
                        <div class="input-container-custom">
                            <i class="fa-solid fa-comment-dots" style="top: 25px;"></i>
                            <textarea name="deskripsi" id="deskripsi" class="form-control-custom" rows="4" placeholder="Masukkan deskripsi studio dan rincian fasilitas..." style="border-radius: 20px; padding-left: 48px; padding-top: 12px; resize: none;"><?= htmlspecialchars($studio['deskripsi']) ?></textarea>
                        </div>
                    </div>
                    
                    
                    <div class="d-flex gap-3 mt-4">
                        <a href="admin_studios.php" class="btn btn-auth-submit text-center d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.15); color: #ffffff !important; border: 1px solid rgba(255,255,255,0.25); flex: 1;">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-auth-submit" style="flex: 2; margin-top: 0; margin-bottom: 0;">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
function validateAddStudioFields() {
    var nama = document.getElementById('nama_studio').value.trim();
    var tipe = document.getElementById('tipe_studio').value;
    var harga = parseFloat(document.getElementById('harga').value) || 0;
    var desc = document.getElementById('deskripsi').value.trim();
    
    if (nama === "" || tipe === "" || harga <= 0 || desc === "") {
        return false;
    }
    return true;
}

function confirmEditStudioSubmit() {
    
    var existingAlert = document.getElementById('js-validation-alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    
    if (!validateAddStudioFields()) {
        var alertDiv = document.createElement('div');
        alertDiv.id = 'js-validation-alert';
        alertDiv.className = 'alert alert-custom alert-custom-danger';
        alertDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-2"></i> Semua kolom input wajib diisi dengan benar!';
        
        var form = document.getElementById('editStudioForm');
        form.insertBefore(alertDiv, form.firstChild);
        return false;
    }
    
    
    return confirm("Konfirmasi Perubahan:\n\nApakah Anda yakin ingin menyimpan perubahan data pada studio ini?");
}
</script>

<?php include '../includes/footer.php'; ?>
