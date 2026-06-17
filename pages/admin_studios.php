<?php
$base_path = '../';
require_once '../includes/config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$success = isset($_GET['success']) ? trim($_GET['success']) : '';
$error = isset($_GET['error']) ? trim($_GET['error']) : '';


$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = 5;
$offset = ($page - 1) * $limit;

try {
    
    $count_query = "SELECT COUNT(*) FROM studio";
    if ($search !== '') {
        $count_query .= " WHERE nama_studio LIKE :search OR tipe_studio LIKE :search OR deskripsi LIKE :search";
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
    
    
    $data_query = "SELECT * FROM studio";
    if ($search !== '') {
        $data_query .= " WHERE nama_studio LIKE :search OR tipe_studio LIKE :search OR deskripsi LIKE :search";
    }
    $data_query .= " ORDER BY id_studio ASC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($data_query);
    if ($search !== '') {
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $studios = $stmt->fetchAll();
    
} catch (\PDOException $e) {
    $error = "Gagal memuat daftar studio: " . $e->getMessage();
    $studios = [];
    $total_pages = 1;
}
?>

<?php include '../includes/header.php'; ?>

<div class="container py-5" style="margin-top: 20px; min-height: calc(100vh - 200px);">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                <div>
                    <h2 class="section-title text-white">Kelola Studio Musik</h2>
                    <p class="section-subtitle">Tambah, ubah, dan hapus jenis layanan studio musik YukBand!</p>
                </div>
                <div>
                    <a href="admin_studio_add.php" class="btn btn-primary-custom" id="btn-add-studio">
                        <i class="fa-solid fa-plus-circle me-1"></i> Tambah Studio Baru
                    </a>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-custom alert-custom-danger" id="studios-error-alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-custom alert-custom-success" id="studios-success-alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            
            <div class="search-pagination-bar">
                <form action="admin_studios.php" method="GET" class="search-input-group">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control form-control-custom no-icon" placeholder="Cari nama atau tipe studio..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-nav-login" type="submit" style="border-radius: 0 50px 50px 0; margin-left: -2px;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                </form>
                
                
                <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination-custom">
                            <li class="page-item-custom <?= ($page <= 1) ? 'disabled d-none' : '' ?>">
                                <a class="page-link-custom" href="admin_studios.php?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item-custom <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link-custom" href="admin_studios.php?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item-custom <?= ($page >= $total_pages) ? 'disabled d-none' : '' ?>">
                                <a class="page-link-custom" href="admin_studios.php?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
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
                            <th>Nama Studio</th>
                            <th>Tipe Studio</th>
                            <th>Harga Sewa</th>
                            <th>Deskripsi</th>
                            <th style="width: 220px; text-align: center;">Aksi Pengelolaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($studios)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-white-50 py-5">
                                    <i class="fa-solid fa-folder-open mb-3" style="font-size: 2.5rem; display: block;"></i>
                                    Tidak ada data studio ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = $offset + 1;
                            foreach ($studios as $studio): 
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong class="text-white"><?= htmlspecialchars($studio['nama_studio']) ?></strong></td>
                                    <td>
                                        <span class="badge badge-custom text-white" style="background: var(--secondary-color); font-size: 0.75rem;">
                                            <?= htmlspecialchars($studio['tipe_studio']) ?>
                                        </span>
                                    </td>
                                    <td><strong class="text-white">Rp<?= number_format($studio['harga'], 0, ',', '.') ?>/jam</strong></td>
                                    <td class="text-white-50 small"><?= htmlspecialchars($studio['deskripsi']) ?></td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            
                                            <a href="admin_studio_edit.php?id_studio=<?= $studio['id_studio'] ?>" class="btn-table-action btn-table-edit">
                                                <i class="fa-solid fa-pen-to-square"></i> Ubah
                                            </a>
                                            
                                            <a href="admin_studio_delete.php?id_studio=<?= $studio['id_studio'] ?>" class="btn-table-action btn-table-cancel" onclick="return confirmDeleteStudio('<?= htmlspecialchars($studio['nama_studio']) ?> (<?= htmlspecialchars($studio['tipe_studio']) ?>)');">
                                                <i class="fa-solid fa-trash-can"></i> Hapus
                                            </a>
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
function confirmDeleteStudio(name) {
    return confirm("Peringatan Hapus:\n\nApakah Anda yakin ingin menghapus studio \"" + name + "\"?\nSemua transaksi dan reservasi yang terikat dengan studio ini akan dihapus secara permanen dari sistem.");
}
</script>

<?php include '../includes/footer.php'; ?>
