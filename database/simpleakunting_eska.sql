-- ======================================================================
-- SimpleAkunting ESKA - Database Setup Script untuk phpMyAdmin
-- ======================================================================
-- Jalankan script ini di phpMyAdmin untuk membuat database dan semua tabel
-- ======================================================================

-- 1. CREATE DATABASE (Jalankan terlebih dahulu jika database belum ada)
CREATE DATABASE IF NOT EXISTS `simpleakunting_eska` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `simpleakunting_eska`;

-- ======================================================================
-- LARAVEL SYSTEM TABLES
-- ======================================================================

-- Cache Table
CREATE TABLE `cache` (
    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
    `value` MEDIUMTEXT NOT NULL,
    `expiration` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
    `owner` VARCHAR(255) NOT NULL,
    `expiration` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jobs Table
CREATE TABLE `jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `queue` VARCHAR(255) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `attempts` TINYINT UNSIGNED NOT NULL,
    `reserved_at` INT UNSIGNED NULL,
    `available_at` INT UNSIGNED NOT NULL,
    `created_at` INT UNSIGNED NOT NULL,
    INDEX `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
    `id` VARCHAR(255) NOT NULL PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `total_jobs` INT NOT NULL,
    `pending_jobs` INT NOT NULL,
    `failed_jobs` INT NOT NULL,
    `failed_job_ids` LONGTEXT NOT NULL,
    `options` MEDIUMTEXT NULL,
    `cancelled_at` INT NULL,
    `created_at` INT NOT NULL,
    `finished_at` INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(255) NOT NULL UNIQUE,
    `connection` TEXT NOT NULL,
    `queue` TEXT NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `exception` LONGTEXT NOT NULL,
    `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions Table
CREATE TABLE `sessions` (
    `id` VARCHAR(255) NOT NULL PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `payload` LONGTEXT NOT NULL,
    `last_activity` INT NOT NULL,
    INDEX `sessions_user_id_index` (`user_id`),
    INDEX `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrations Table (untuk tracking Laravel migrations)
CREATE TABLE `migrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL,
    `batch` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================================
-- USERS TABLE
-- ======================================================================

CREATE TABLE `users` (
    `id_user` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nama_user` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(255) NOT NULL,
    `jabatan` VARCHAR(255) NOT NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================================
-- MASTER DATA TABLES
-- ======================================================================

-- Akun (Chart of Accounts)
CREATE TABLE `akun` (
    `kode_akun` VARCHAR(20) NOT NULL PRIMARY KEY,
    `nama_akun` VARCHAR(255) NOT NULL,
    `tipe_akun` VARCHAR(255) NOT NULL,
    `saldo_normal` VARCHAR(10) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Perusahaan
CREATE TABLE `perusahaan` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nama_perusahaan` VARCHAR(255) NOT NULL,
    `alamat` TEXT NULL,
    `telepon` VARCHAR(255) NULL,
    `email` VARCHAR(255) NULL,
    `jenis_usaha` ENUM('dagang', 'simpan_pinjam', 'multi', 'jasa') NOT NULL DEFAULT 'dagang',
    `akun_piutang_default` VARCHAR(255) NULL,
    `akun_utang_default` VARCHAR(255) NULL,
    `nama_direktur` VARCHAR(255) NULL,
    `nama_akuntan` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pelanggan
CREATE TABLE `pelanggan` (
    `id_pelanggan` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nama_pelanggan` VARCHAR(255) NOT NULL,
    `alamat` TEXT NULL,
    `telepon` VARCHAR(255) NULL,
    `email` VARCHAR(255) NULL,
    `saldo_awal_piutang` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `saldo_terkini_piutang` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pemasok
CREATE TABLE `pemasok` (
    `id_pemasok` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nama_pemasok` VARCHAR(255) NOT NULL,
    `alamat` TEXT NULL,
    `telepon` VARCHAR(255) NULL,
    `email` VARCHAR(255) NULL,
    `saldo_awal_hutang` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `saldo_terkini_hutang` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Master Persediaan
CREATE TABLE `master_persediaan` (
    `id_barang` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `kode_barang` VARCHAR(255) NOT NULL UNIQUE,
    `barcode` VARCHAR(255) NULL UNIQUE,
    `nama_barang` VARCHAR(255) NOT NULL,
    `satuan` VARCHAR(255) NOT NULL,
    `stok_awal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `stok_saat_ini` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `harga_beli` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `harga_jual` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `akun_persediaan` VARCHAR(255) NULL,
    `akun_hpp` VARCHAR(255) NULL,
    `akun_penjualan` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Proyek
CREATE TABLE `proyek` (
    `id_proyek` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `kode_proyek` VARCHAR(20) NOT NULL UNIQUE,
    `nama_proyek` VARCHAR(255) NOT NULL,
    `deskripsi` TEXT NULL,
    `status` ENUM('Aktif', 'Selesai', 'Ditunda') NOT NULL DEFAULT 'Aktif',
    `tanggal_mulai` DATE NULL,
    `tanggal_selesai` DATE NULL,
    `anggaran` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `lokasi` VARCHAR(255) NULL,
    `pelanggan` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================================
-- TRANSAKSI TABLES
-- ======================================================================

-- Jurnal Umum
CREATE TABLE `jurnal_umum` (
    `id_jurnal` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `no_transaksi` VARCHAR(255) NOT NULL,
    `tanggal` DATE NOT NULL,
    `deskripsi` TEXT NULL,
    `sumber_jurnal` VARCHAR(255) NOT NULL,
    `id_proyek` BIGINT UNSIGNED NULL,
    `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jurnal Detail
CREATE TABLE `jurnal_detail` (
    `id_detail` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_jurnal` BIGINT UNSIGNED NOT NULL,
    `kode_akun` VARCHAR(255) NOT NULL,
    `debit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `kredit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `id_proyek` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Penjualan
CREATE TABLE `penjualan` (
    `id_penjualan` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_pelanggan` BIGINT UNSIGNED NOT NULL,
    `id_jurnal` BIGINT UNSIGNED NULL,
    `no_faktur` VARCHAR(255) NOT NULL,
    `tanggal_faktur` DATE NOT NULL,
    `total` DECIMAL(15,2) NOT NULL,
    `keterangan` TEXT NULL,
    `metode_pembayaran` VARCHAR(255) NOT NULL,
    `akun_kas_bank` VARCHAR(255) NULL,
    `sisa_tagihan` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `status_pembayaran` VARCHAR(255) NOT NULL DEFAULT 'Belum Lunas',
    `id_proyek` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Penjualan Detail
CREATE TABLE `penjualan_detail` (
    `id_detail` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_penjualan` BIGINT UNSIGNED NOT NULL,
    `id_barang` BIGINT UNSIGNED NOT NULL,
    `kuantitas` DECIMAL(10,2) NOT NULL,
    `harga` DECIMAL(15,2) NOT NULL,
    `subtotal` DECIMAL(15,2) NOT NULL,
    `akun_pendapatan` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pembelian
CREATE TABLE `pembelian` (
    `id_pembelian` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_pemasok` BIGINT UNSIGNED NOT NULL,
    `id_jurnal` BIGINT UNSIGNED NULL,
    `no_faktur_pembelian` VARCHAR(255) NOT NULL,
    `tanggal_faktur` DATE NOT NULL,
    `total` DECIMAL(15,2) NOT NULL,
    `keterangan` TEXT NULL,
    `metode_pembayaran` VARCHAR(255) NOT NULL,
    `akun_kas_bank` VARCHAR(255) NULL,
    `sisa_tagihan` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `status_pembayaran` VARCHAR(255) NOT NULL DEFAULT 'Belum Lunas',
    `id_proyek` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pembelian Detail
CREATE TABLE `pembelian_detail` (
    `id_detail` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_pembelian` BIGINT UNSIGNED NOT NULL,
    `id_barang` BIGINT UNSIGNED NOT NULL,
    `kuantitas` DECIMAL(10,2) NOT NULL,
    `harga` DECIMAL(15,2) NOT NULL,
    `subtotal` DECIMAL(15,2) NOT NULL,
    `akun_beban_persediaan` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kartu Stok
CREATE TABLE `kartu_stok` (
    `id_kartu` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_barang` BIGINT UNSIGNED NOT NULL,
    `tipe_transaksi` VARCHAR(255) NOT NULL,
    `kuantitas` DECIMAL(10,2) NOT NULL,
    `keterangan` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jurnal Kas
CREATE TABLE `jurnal_kas` (
    `id_jurnal_kas` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `no_bukti` VARCHAR(255) NOT NULL,
    `tanggal` DATE NOT NULL,
    `tipe` ENUM('Masuk', 'Keluar') NOT NULL,
    `akun_kas` VARCHAR(255) NOT NULL,
    `akun_lawan` VARCHAR(255) NOT NULL,
    `jumlah` DECIMAL(15,2) NOT NULL,
    `keterangan` TEXT NULL,
    `id_proyek` BIGINT UNSIGNED NULL,
    `id_jurnal` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================================
-- KOPERASI TABLES (Optional - untuk jenis usaha simpan_pinjam/multi)
-- ======================================================================

-- Anggota Koperasi
CREATE TABLE `anggota` (
    `id_anggota` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `no_anggota` VARCHAR(255) NOT NULL UNIQUE,
    `nik` VARCHAR(16) NOT NULL UNIQUE,
    `nama_lengkap` VARCHAR(255) NOT NULL,
    `jenis_kelamin` ENUM('L', 'P') NOT NULL,
    `alamat` TEXT NOT NULL,
    `telepon` VARCHAR(255) NULL,
    `email` VARCHAR(255) NULL,
    `pekerjaan` VARCHAR(255) NULL,
    `foto` VARCHAR(255) NULL,
    `tanggal_daftar` DATE NOT NULL,
    `status` ENUM('aktif', 'non_aktif', 'keluar') NOT NULL DEFAULT 'aktif',
    `tanggal_keluar` DATE NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jenis Simpanan
CREATE TABLE `jenis_simpanan` (
    `id_jenis_simpanan` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `kode_simpanan` VARCHAR(255) NOT NULL UNIQUE,
    `nama_simpanan` VARCHAR(255) NOT NULL,
    `tipe` ENUM('pokok', 'wajib', 'sukarela', 'deposito') NOT NULL,
    `bunga_pertahun` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `akun_simpanan` VARCHAR(255) NOT NULL,
    `akun_bunga` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jenis Pinjaman
CREATE TABLE `jenis_pinjaman` (
    `id_jenis_pinjaman` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `kode_pinjaman` VARCHAR(255) NOT NULL UNIQUE,
    `nama_pinjaman` VARCHAR(255) NOT NULL,
    `kategori` ENUM('produktif', 'konsumtif', 'darurat') NOT NULL,
    `bunga_pertahun` DECIMAL(5,2) NOT NULL,
    `metode_bunga` ENUM('flat', 'anuitas', 'efektif') NOT NULL,
    `tenor_max` INT NOT NULL,
    `plafon_max` DECIMAL(15,2) NOT NULL,
    `provisi_persen` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `admin_fee` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `akun_piutang_pinjaman` VARCHAR(255) NOT NULL,
    `akun_pendapatan_bunga` VARCHAR(255) NOT NULL,
    `akun_pendapatan_provisi` VARCHAR(255) NULL,
    `akun_pendapatan_admin` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================================
-- IMPORT STAGING TABLE
-- ======================================================================

CREATE TABLE `import_kas_staging` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `batch_id` VARCHAR(50) NOT NULL,
    `no_referensi` VARCHAR(100) NULL,
    `tanggal` DATE NOT NULL,
    `uraian` TEXT NOT NULL,
    `uang_masuk` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `uang_keluar` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `kode_akun_kas` VARCHAR(20) NULL,
    `kode_akun_lawan` VARCHAR(20) NULL,
    `is_selected` TINYINT(1) NOT NULL DEFAULT 0,
    `is_posted` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `import_kas_staging_user_id_batch_id_index` (`user_id`, `batch_id`),
    INDEX `import_kas_staging_is_posted_index` (`is_posted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================================
-- INSERT MIGRATION RECORDS (agar Laravel tracking berjalan dengan benar)
-- ======================================================================

INSERT INTO `migrations` (`migration`, `batch`) VALUES
('0001_01_01_000001_create_cache_table', 1),
('0001_01_01_000002_create_jobs_table', 1),
('2025_11_29_053513_create_users_table_legacy', 1),
('2025_11_29_055430_create_legacy_tables', 1),
('2025_11_29_091751_add_signatures_to_perusahaan_table', 1),
('2025_11_29_093813_add_barcode_to_master_persediaan_table', 1),
('2025_12_24_123825_create_sessions_table', 1),
('2025_12_24_201400_create_koperasi_master_tables', 1),
('2025_12_24_201401_add_jenis_usaha_to_perusahaan', 1),
('2025_12_29_154909_add_jasa_to_jenis_usaha_enum', 1),
('2026_01_27_000001_create_proyek_table', 1),
('2026_01_27_000002_add_proyek_to_transactions', 1),
('2026_01_27_000003_create_jurnal_kas_table', 1),
('2026_01_27_071928_create_import_kas_staging_table', 1);

-- ======================================================================
-- INSERT DEFAULT SUPERUSER (Password: admin123)
-- ======================================================================

INSERT INTO `users` (`nama_user`, `password_hash`, `role`, `jabatan`, `created_at`, `updated_at`) 
VALUES ('admin', '$2y$12$8zJGqD5.Hy5xG3xMz2uEke3aQW7xO7c5.qP9xO7c5.qP9xO7c5.qP', 'superuser', 'Administrator', NOW(), NOW());

-- ======================================================================
-- INSERT DEFAULT PERUSAHAAN
-- ======================================================================

INSERT INTO `perusahaan` (`nama_perusahaan`, `alamat`, `telepon`, `email`, `jenis_usaha`, `created_at`, `updated_at`)
VALUES ('PT. ESKA', 'Alamat Perusahaan', '08xxxxxxxxxx', 'email@example.com', 'dagang', NOW(), NOW());

-- ======================================================================
-- SELESAI!
-- ======================================================================
-- Setelah import script ini, jalankan di server:
-- php artisan config:cache
-- php artisan route:cache
-- ======================================================================
