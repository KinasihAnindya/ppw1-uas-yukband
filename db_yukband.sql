CREATE DATABASE yukband;
USE yukband;

-- Tabel user
CREATE TABLE user (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel studio
CREATE TABLE studio (
    id_studio INT AUTO_INCREMENT PRIMARY KEY,
    nama_studio VARCHAR(50) NOT NULL,
    tipe_studio ENUM('Solo Space', 'Jam Space') NOT NULL,
    harga DECIMAL(10,2) NOT NULL,
    deskripsi TEXT
);

-- Tabel reservation
CREATE TABLE reservation (
    id_reservasi INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_studio INT NOT NULL,
    tanggal_pesan DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    durasi INT NOT NULL,
    status_pemesanan ENUM('Menunggu', 'Dikonfirmasi', 'Dibatalkan') DEFAULT 'Menunggu',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_studio) REFERENCES studio(id_studio) ON DELETE CASCADE
);

-- Tabel payment
CREATE TABLE payment (
    id_payment INT AUTO_INCREMENT PRIMARY KEY,
    id_reservasi INT NOT NULL,
    total_payment DECIMAL(10,2) NOT NULL,
    status_pembayaran ENUM('Belum Lunas', 'Lunas') DEFAULT 'Belum Lunas',
    tanggal_bayar DATETIME DEFAULT NULL,
    FOREIGN KEY (id_reservasi) REFERENCES reservation(id_reservasi)
);

-- Tabel income_statistic
CREATE TABLE income_statistic (
    id_statistik INT AUTO_INCREMENT PRIMARY KEY,
    bulan INT NOT NULL,
    tahun INT NOT NULL,
    total_pendapatan DECIMAL(15,2) DEFAULT 0.00,
    total_transaksi INT DEFAULT 0,
    UNIQUE KEY uq_bulan_tahun (bulan, tahun)
);

-- Tabel logs_pembatalan
CREATE TABLE logs_pembatalan (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    id_reservasi INT,
    id_user INT,
    tanggal_pembatalan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    alasan VARCHAR(255) DEFAULT 'Dibatalkan oleh sistem/admin'
);

-- Seeding
INSERT INTO studio (nama_studio, tipe_studio, harga, deskripsi) VALUES
('Studio Yogya 1', 'Solo Space', 40000.0, 'Cocok untuk latihan individu, vokal, atau latihan instrumen'),
('Studio Yogya 1', 'Jam Space', 100000.0, 'Ruang latihan lengkap untuk band dan grup musik.');

INSERT INTO user (username, password, role, email) VALUES
('admin_yukband', 'admin123', 'admin', 'adminyukband@gmail.com'),
('anin', 'anin123', 'user', 'exampleanindya@gmail.com'),
('pandu', 'pandu123', 'user', 'examplepandu@gmail.com');

-- Fungsi 1: Menghitung Total Sewa Studio
DELIMITER $$
CREATE FUNCTION count_total_sewa(p_id_studio INT, p_durasi INT)
RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    DECLARE v_harga DECIMAL(10,2);
    SELECT harga INTO v_harga FROM studio WHERE id_studio = p_id_studio;
    RETURN (v_harga * p_durasi);
END$$
DELIMITER ;

-- Fungsi 2: Cek Ketersediaan Studio
DELIMITER $$
CREATE FUNCTION cek_ketersediaan_studio(p_id_studio INT, p_tanggal DATE, p_jam TIME)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_count INT;
    SELECT COUNT(*) INTO v_count
    FROM reservation
    WHERE id_studio = p_id_studio
        AND tanggal_pesan = p_tanggal
        AND jam_mulai = p_jam
        AND status_pemesanan != 'Dibatalkan';
    RETURN v_count;
END$$
DELIMITER ;

-- Trigger Insert ke Tabel Payments dan Menghitung Total Bayar
DELIMITER $$
CREATE TRIGGER insert_reservasi
AFTER INSERT ON reservation
FOR EACH ROW
BEGIN
    DECLARE v_total DECIMAL(10,2);
    SET v_total = count_total_sewa(NEW.id_studio, NEW.durasi);

    INSERT INTO payment (id_reservasi, total_payment, status_pembayaran)
    VALUES (NEW.id_reservasi, v_total, 'Belum Lunas');
END$$
DELIMITER ;

-- Trigger Log Otomatis untuk Pembatalan Reservasi
DELIMITER $$
CREATE TRIGGER setelah_batal_reservasi
AFTER UPDATE ON reservation
FOR EACH ROW
BEGIN
    IF NEW.status_pemesanan = 'Dibatalkan' AND OLD.status_pemesanan != 'Dibatalkan' THEN
        INSERT INTO logs_pembatalan (id_reservasi, id_user, alasan)
        VALUES (NEW.id_reservasi, NEW.id_user, 'Pemesanan studio dibatalkan oleh admin.');
    END IF;
END$$
DELIMITER ;

-- VIEWS: Menampilkan Reservasi sisi Admin
CREATE OR REPLACE VIEW view_transaksi AS
SELECT
    r.id_reservasi,
    u.username,
    s.nama_studio,
    s.tipe_studio,
    r.tanggal_pesan,
    r.jam_mulai,
    r.durasi,
    r.status_pemesanan,
    p.total_payment,
    p.status_pembayaran
FROM reservation r
JOIN user u ON r.id_user = u.id_user
JOIN studio s ON r.id_studio = s.id_studio
JOIN payment p ON r.id_reservasi = p.id_reservasi;

-- VIEWS: Rekapitulasi Transaksi Valid
CREATE OR REPLACE VIEW view_transaksi_valid AS
SELECT
    YEAR(r.tanggal_pesan) AS tahun,
    MONTH(r.tanggal_pesan) AS bulan,
    COUNT(r.id_reservasi) AS jumlah_transaksi,
    SUM(p.total_payment) AS total_omset
FROM reservation r
JOIN payment p ON r.id_reservasi = p.id_reservasi
WHERE r.status_pemesanan = 'Dikonfirmasi' AND p.status_pembayaran = 'Lunas'
GROUP BY YEAR(r.tanggal_pesan), MONTH(r.tanggal_pesan);

-- Query Complex 1: User Paling Sering Sewa
SELECT u.id_user, u.username, u.email, top_user.total_sewa
FROM user u
JOIN (
    SELECT id_user, COUNT(*) AS total_sewa
    FROM reservation
    WHERE status_pemesanan = 'Dikonfirmasi'
    GROUP BY id_user
) AS top_user ON u.id_user = top_user.id_user
ORDER BY top_user.total_sewa DESC;

-- Query Complex 2: Transaksi Nominal Diatas Rata-rata
SELECT p.id_payment, r.tanggal_pesan, u.username, p.total_payment
FROM payment p
JOIN reservation r ON p.id_reservasi = r.id_reservasi
JOIN user u ON r.id_user = u.id_user
WHERE p.total_payment > (SELECT AVG(total_payment) FROM payment)
ORDER BY p.total_payment DESC;

-- Query Complex 3: Jumlah Pemesanan Berdasarkan Tipe Studio
SELECT s.tipe_studio, COUNT(r.id_reservasi) AS total_dipesan, SUM(p.total_payment) AS akumulasi_dana
FROM studio s
LEFT JOIN reservation r ON s.id_studio = r.id_studio
LEFT JOIN payment p ON r.id_reservasi = p.id_reservasi
GROUP BY s.tipe_studio;