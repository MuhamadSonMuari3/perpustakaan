-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2026 at 02:48 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_perpustakaan`
--

-- --------------------------------------------------------

--
-- Table structure for table `buku`
--

CREATE TABLE `buku` (
  `id` int(11) NOT NULL,
  `kode_buku` varchar(20) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `pengarang` varchar(100) NOT NULL,
  `penerbit` varchar(100) NOT NULL,
  `tahun_terbit` year(4) NOT NULL,
  `kategori` varchar(50) NOT NULL DEFAULT 'Umum',
  `stok` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buku`
--

INSERT INTO `buku` (`id`, `kode_buku`, `judul`, `pengarang`, `penerbit`, `tahun_terbit`, `kategori`, `stok`, `created_at`) VALUES
(1, 'BK001', 'Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', '2005', 'Fiksi', 3, '2026-10-07 00:32:48'),
(2, 'BK002', 'Bumi Manusia', 'Pramoedya Ananta Toer', 'Hasta Mitra', '1980', 'Fiksi', 2, '2026-10-07 00:32:48'),
(3, 'BK003', 'Algoritma dan Pemrograman', 'Rinaldi Munir', 'Informatika', '2016', 'Teknologi', 4, '2026-10-07 00:32:48'),
(4, 'BK004', 'Dasar-Dasar Akuntansi', 'Al. Haryono Jusup', 'STIE YKPN', '2011', 'Ekonomi', 2, '2026-10-07 00:32:48'),
(5, 'BK005', 'Sejarah Indonesia Modern', 'M.C. Ricklefs', 'Serambi', '2005', 'Sejarah', 3, '2026-10-07 00:32:48');

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id` int(11) NOT NULL,
  `kode_pinjam` varchar(20) NOT NULL,
  `id_buku` int(11) NOT NULL,
  `id_anggota` int(11) NOT NULL,
  `id_petugas` int(11) NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_kembali` date NOT NULL COMMENT 'Batas tanggal kembali',
  `status` enum('dipinjam','dikembalikan','terlambat') NOT NULL DEFAULT 'dipinjam',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengembalian`
--

CREATE TABLE `pengembalian` (
  `id` int(11) NOT NULL,
  `id_peminjaman` int(11) NOT NULL COMMENT 'Satu peminjaman hanya bisa dikembalikan sekali',
  `id_petugas` int(11) NOT NULL,
  `tanggal_kembali_aktual` date NOT NULL COMMENT 'Tanggal buku benar-benar dikembalikan',
  `denda` int(11) NOT NULL DEFAULT 0 COMMENT 'Denda dalam rupiah (keterlambatan)',
  `kondisi_buku` enum('baik','rusak_ringan','rusak_berat','hilang') NOT NULL DEFAULT 'baik',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `level` enum('admin','petugas','anggota') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `level`) VALUES
(1, 'Adnan', 'admin', '$2y$10$Yn4oM3Qk1uudD.VsoxxwhOVUxuifMjowy6z4bHPxLvxFr4S4Rn0NG', 'admin'),
(20, 'Adnan Fahrezy', 'adnan', '$2y$10$JmLa/Y0pevLTx5/Iq742VOhLut1a9ScCw3L0/9oiTj/OIDw1R9Asi', 'anggota'),
(21, 'diba', 'diba', '$2y$10$kbSChCqGrprLV9wF7.g4iuqQ7DLj.9kypYmk7rij4em/sbcoKXHmi', 'anggota'),
(23, 'fino', 'fino', '$2y$10$GRt1H.65vuz/1q0Ti8TvBeZQyCKknPZF/m1eUUpGLEgNND1kmRxTW', 'anggota');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_buku` (`kode_buku`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pinjam` (`kode_pinjam`),
  ADD KEY `fk_pinjam_buku` (`id_buku`),
  ADD KEY `fk_pinjam_anggota` (`id_anggota`),
  ADD KEY `fk_pinjam_petugas` (`id_petugas`);

--
-- Indexes for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_peminjaman` (`id_peminjaman`),
  ADD KEY `fk_kembali_peminjaman` (`id_peminjaman`),
  ADD KEY `fk_kembali_petugas` (`id_petugas`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buku`
--
ALTER TABLE `buku`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengembalian`
--
ALTER TABLE `pengembalian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `fk_pinjam_anggota` FOREIGN KEY (`id_anggota`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pinjam_buku` FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pinjam_petugas` FOREIGN KEY (`id_petugas`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD CONSTRAINT `fk_kembali_peminjaman` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kembali_petugas` FOREIGN KEY (`id_petugas`) REFERENCES `users` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
