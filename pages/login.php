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
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = "Harap isi semua kolom!";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                $password_matches = false;
                
                
                if (password_verify($password, $user['password'])) {
                    $password_matches = true;
                }
                
                else if ($password === $user['password']) {
                    $password_matches = true;
                    
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $pdo->prepare("UPDATE user SET password = ? WHERE id_user = ?");
                    $update_stmt->execute([$hashed_password, $user['id_user']]);
                }
                
                if ($password_matches) {
                    
                    $_SESSION['user_id'] = $user['id_user'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    
                    if ($user['role'] === 'admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: ../index.php");
                    }
                    exit;
                } else {
                    $error = "Kata sandi yang Anda masukkan salah!";
                }
            } else {
                $error = "Nama pengguna tidak terdaftar!";
            }
        } catch (\PDOException $e) {
            $error = "Terjadi kesalahan database: " . $e->getMessage();
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container d-flex align-items-center justify-content-center" style="min-height: calc(100vh - 200px); padding-top: 40px; padding-bottom: 40px;">
    <div class="auth-box glass-panel-dark">
        
        <div class="auth-logo">
            <a href="<?= $base_path ?>index.php" class="auth-logo-text">
                <img src="<?= $base_path ?>assets/LogoYukBand.png" alt="YukBand! Logo" height="40" class="d-inline-block align-top">
            </a>
        </div>
        
        <h2 class="auth-title">Login</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-custom alert-custom-danger" id="login-error-alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-custom alert-custom-success">
                <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        
        <form action="login.php" method="POST" id="loginForm" onsubmit="return validateLoginForm();">
            
            <div class="form-group-custom">
                <label for="username" class="form-label-custom">Nama Pengguna</label>
                <div class="input-container-custom">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="username" id="username" class="form-control-custom" placeholder="Masukkan nama pengguna" autocomplete="off">
                </div>
            </div>
            
            
            <div class="form-group-custom">
                <label for="password" class="form-label-custom">Kata Sandi</label>
                <div class="input-container-custom">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" id="password" class="form-control-custom" placeholder="Masukkan kata sandi">
                </div>
            </div>
            
            
            <div class="d-flex justify-content-between align-items-center mb-4 text-start" style="font-family: var(--font-sans); font-size: 0.85rem;">
                <div class="form-check p-0 d-flex align-items-center gap-2">
                    <input class="form-check-input-custom" type="checkbox" id="rememberMe">
                    <label class="form-check-label text-white-50" for="rememberMe" style="cursor: pointer; user-select: none;">
                        Ingat Saya
                    </label>
                </div>
                <div>
                    <a href="#" class="text-white-50 text-decoration-none" onclick="alert('Fitur Lupa Kata Sandi belum tersedia secara sistem. Silakan hubungi Admin!'); return false;">Lupa Kata Sandi?</a>
                </div>
            </div>
            
            
            <button type="submit" class="btn btn-auth-submit" id="btn-login-submit">Login</button>
            
            
            <div class="auth-footer-text mt-3">
                Tidak Punya Akun? <a href="register.php">Daftar Sekarang!</a>
            </div>
        </form>
    </div>
</div>


<script>
function validateLoginForm() {
    var usernameInput = document.getElementById('username');
    var passwordInput = document.getElementById('password');
    
    var username = usernameInput.value.trim();
    var password = passwordInput.value.trim();
    
    
    var existingAlert = document.getElementById('js-validation-alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    if (username === "" || password === "") {
        
        var alertDiv = document.createElement('div');
        alertDiv.id = 'js-validation-alert';
        alertDiv.className = 'alert alert-custom alert-custom-danger';
        alertDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-2"></i> Username dan Password wajib diisi!';
        
        var form = document.getElementById('loginForm');
        form.insertBefore(alertDiv, form.firstChild);
        
        
        if (username === "") {
            usernameInput.focus();
        } else {
            passwordInput.focus();
        }
        
        return false; 
    }
    return true; 
}
</script>

<?php include '../includes/footer.php'; ?>
