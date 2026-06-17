<?php
require_once '../includes/config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id_studio = isset($_GET['id_studio']) ? intval($_GET['id_studio']) : 0;

if ($id_studio <= 0) {
    header("Location: admin_studios.php");
    exit;
}

try {
    
    $name_stmt = $pdo->prepare("SELECT nama_studio, tipe_studio FROM studio WHERE id_studio = ?");
    $name_stmt->execute([$id_studio]);
    $studio = $name_stmt->fetch();
    
    if ($studio) {
        $studio_name = $studio['nama_studio'] . " (" . $studio['tipe_studio'] . ")";
        
        
        
        $stmt = $pdo->prepare("DELETE FROM studio WHERE id_studio = ?");
        $stmt->execute([$id_studio]);
        
        header("Location: admin_studios.php?success=" . urlencode("Studio '" . $studio_name . "' dan seluruh riwayat transaksi terkait berhasil dihapus dari sistem!"));
        exit;
    } else {
        header("Location: admin_studios.php?error=" . urlencode("Studio tidak ditemukan atau sudah dihapus!"));
        exit;
    }
} catch (\PDOException $e) {
    header("Location: admin_studios.php?error=" . urlencode("Gagal menghapus studio: " . $e->getMessage()));
    exit;
}
?>
