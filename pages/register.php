<?php
$base_path = '../';
require_once '../includes/config.php';


if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: ../index.php");
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Harap isi semua kolom!";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi kata sandi tidak cocok!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        try {
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE username = ?");
            $stmt->execute([$username]);
            $user_exists = $stmt->fetchColumn() > 0;
            
            if ($user_exists) {
                $error = "Nama pengguna sudah terdaftar! Pilih nama lain.";
            } else {
                
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                
                $stmt = $pdo->prepare("INSERT INTO user (username, password, role, email) VALUES (?, ?, 'user', ?)");
                $stmt->execute([$username, $hashed_password, $email]);
                
                $success = "Pendaftaran berhasil! Silakan login.";
                
                $username = $email = '';
            }
        } catch (\PDOException $e) {
            $error = "Terjadi kesalahan database: " . $e->getMessage();
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container d-flex align-items-center justify-content-center" style="min-height: calc(100vh - 200px); padding-top: 40px; padding-bottom: 40px;">
    <div class="auth-box glass-panel-dark" style="max-width: 500px;">
        
        <div class="auth-logo">
            <a href="<?= $base_path ?>index.php" class="auth-logo-text">
                <span class="badge" style="background: linear-gradient(135deg, var(--secondary-color), #500018); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 10px; padding: 10px;"><i class="fa-solid fa-music"></i></span>
                <span class="ms-2">Yuk<span class="logo-text-highlight">Band!</span></span>
            </a>
        </div>
        
        <h2 class="auth-title">Daftar Akun</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-custom alert-custom-danger" id="register-error-alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-custom alert-custom-success" id="register-success-alert">
                <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($success) ?>. <a href="login.php" class="alert-link text-white font-sans-header">Login di sini</a>
            </div>
        <?php endif; ?>
        
        
        <form action="register.php" method="POST" id="registerForm" onsubmit="return validateRegisterForm();">
            
            <div class="form-group-custom">
                <label for="username" class="form-label-custom">Nama Pengguna (Username)</label>
                <div class="input-container-custom">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="username" id="username" class="form-control-custom" placeholder="Masukkan nama pengguna" value="<?= isset($username) ? htmlspecialchars($username) : '' ?>" autocomplete="off">
                </div>
            </div>
            
            
            <div class="form-group-custom">
                <label for="email" class="form-label-custom">Email</label>
                <div class="input-container-custom">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" id="email" class="form-control-custom" placeholder="Masukkan alamat email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" autocomplete="off">
                </div>
            </div>
            
            
            <div class="form-group-custom">
                <label for="password" class="form-label-custom">Kata Sandi</label>
                <div class="input-container-custom">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" id="password" class="form-control-custom" placeholder="Buat kata sandi minimal 6 karakter">
                </div>
            </div>
            
            
            <div class="form-group-custom">
                <label for="confirm_password" class="form-label-custom">Konfirmasi Kata Sandi</label>
                <div class="input-container-custom">
                    <i class="fa-solid fa-circle-check"></i>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control-custom" placeholder="Ulangi kata sandi">
                </div>
            </div>
            
            
            <button type="submit" class="btn btn-auth-submit" id="btn-register-submit">Daftar Akun</button>
            
            
            <div class="auth-footer-text mt-3">
                Sudah Punya Akun? <a href="login.php">Login Sekarang!</a>
            </div>
        </form>
    </div>
</div>


<script>
function validateRegisterForm() {
    var usernameInput = document.getElementById('username');
    var emailInput = document.getElementById('email');
    var passwordInput = document.getElementById('password');
    var confirmInput = document.getElementById('confirm_password');
    
    var username = usernameInput.value.trim();
    var email = emailInput.value.trim();
    var password = passwordInput.value.trim();
    var confirmPassword = confirmInput.value.trim();
    
    
    var existingAlert = document.getElementById('js-validation-alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    var errorMessage = "";
    
    if (username === "" || email === "" || password === "" || confirmPassword === "") {
        errorMessage = "Semua kolom wajib diisi!";
    } else if (password.length < 6) {
        errorMessage = "Kata sandi minimal harus 6 karakter!";
    } else if (password !== confirmPassword) {
        errorMessage = "Konfirmasi kata sandi tidak sesuai!";
    } else {
        
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            errorMessage = "Format email tidak valid!";
        }
    }
    
    if (errorMessage !== "") {
        
        var alertDiv = document.createElement('div');
        alertDiv.id = 'js-validation-alert';
        alertDiv.className = 'alert alert-custom alert-custom-danger';
        alertDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-2"></i> ' + errorMessage;
        
        var form = document.getElementById('registerForm');
        form.insertBefore(alertDiv, form.firstChild);
        
        
        if (username === "") {
            usernameInput.focus();
        } else if (email === "" || (email !== "" && !emailRegex.test(email))) {
            emailInput.focus();
        } else if (password === "" || password.length < 6) {
            passwordInput.focus();
        } else {
            confirmInput.focus();
        }
        
        return false; 
    }
    return true; 
}
</script>

<?php include '../includes/footer.php'; ?>
