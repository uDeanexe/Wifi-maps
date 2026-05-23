-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 23 Bulan Mei 2026 pada 04.20
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mapping`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `entity_type` varchar(255) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `incidents`
--

CREATE TABLE `incidents` (
  `id` int(11) NOT NULL,
  `node_id` int(11) DEFAULT NULL,
  `category` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `reporter_name` varchar(255) DEFAULT NULL,
  `reporter_contact` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `noc_admin_name` varchar(255) DEFAULT NULL,
  `technician_name` varchar(255) DEFAULT NULL,
  `technician_contact` varchar(255) DEFAULT NULL,
  `technician_email` varchar(255) DEFAULT NULL,
  `work_order_notes` text DEFAULT NULL,
  `technician_report` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'reported',
  `assigned_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `incidents`
--

INSERT INTO `incidents` (`id`, `node_id`, `category`, `title`, `description`, `reporter_name`, `reporter_contact`, `photo_path`, `noc_admin_name`, `technician_name`, `technician_contact`, `technician_email`, `work_order_notes`, `technician_report`, `status`, `assigned_at`, `completed_at`, `created_at`, `updated_at`) VALUES
(2, 5, 'internet_mati', 'Internet mati total', 'Tidak ada koneksi sejak pagi. Modem LOS.', 'User Demo', '08xxxx', NULL, 'NOC Demo', NULL, NULL, NULL, 'Cek redaman, pastikan patchcord, follow SOP. Update via WA.', NULL, 'reported', NULL, NULL, '2026-05-21 15:28:55', '2026-05-21 15:28:55'),
(3, 5, 'kerusakan', 'Kabel drop putus', 'Kabel terlihat putus di dekat tiang.', 'User Demo', '08xxxx', NULL, 'NOC Demo', NULL, NULL, NULL, 'Bawa dropcore cadangan + konektor. Dokumentasikan foto sebelum/sesudah.', NULL, 'reported', NULL, NULL, '2026-05-21 15:28:55', '2026-05-21 15:28:55');

-- --------------------------------------------------------

--
-- Struktur dari tabel `links`
--

CREATE TABLE `links` (
  `id` int(11) NOT NULL,
  `source_node_id` int(11) NOT NULL,
  `target_node_id` int(11) NOT NULL,
  `cable_type` varchar(255) DEFAULT NULL,
  `core_count` int(11) DEFAULT NULL,
  `core_number` varchar(255) DEFAULT NULL,
  `pon_name` varchar(255) DEFAULT NULL,
  `odc_name` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `links`
--

INSERT INTO `links` (`id`, `source_node_id`, `target_node_id`, `cable_type`, `core_count`, `core_number`, `pon_name`, `odc_name`, `notes`, `created_at`, `updated_at`) VALUES
(7, 5, 6, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-21 11:28:41', '2026-05-21 11:28:41'),
(8, 6, 7, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-21 11:28:48', '2026-05-21 11:28:48'),
(9, 6, 13, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-21 11:29:20', '2026-05-21 11:29:20'),
(10, 6, 12, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-21 11:29:22', '2026-05-21 11:29:22'),
(11, 6, 14, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-21 11:29:26', '2026-05-21 11:29:26'),
(12, 6, 10, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-21 11:29:28', '2026-05-21 11:29:28'),
(13, 6, 11, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-21 11:29:34', '2026-05-21 11:29:34');

-- --------------------------------------------------------

--
-- Struktur dari tabel `nodes`
--

CREATE TABLE `nodes` (
  `id` int(11) NOT NULL,
  `node_type_id` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `address` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `topology_x` int(11) DEFAULT 100,
  `topology_y` int(11) DEFAULT 100,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `nodes`
--

INSERT INTO `nodes` (`id`, `node_type_id`, `code`, `name`, `latitude`, `longitude`, `address`, `photo_path`, `notes`, `topology_x`, `topology_y`, `created_at`, `updated_at`) VALUES
(5, 6, 'Server utama', 'server', -6.2092639700598475, 107.18153981435307, 'Pilar Mas Persada 2, Blok C4 No.12A, Karanganyar, Kec. Karangbahagia, Kabupaten Bekasi, Jawa Barat 17530', NULL, 'ini rumah pak adam', -100, -80, '2026-05-21 10:31:49', '2026-05-21 13:17:34'),
(6, 1, 'ODC A1-PON 1 HQ', 'perumahan Pilar mas', -6.2073656725553565, 107.18086999446324, 'Q5VJ+2CR, Jl. Katimaha, Karanganyar, Kec. Karangbahagia, Kabupaten Bekasi, Jawa Barat 17530', NULL, 'ODC A1-PON 1 HQ  pilar mas di dekat pertigaan, ODC dan ODP', 500, -460, '2026-05-21 10:36:29', '2026-05-21 13:18:17'),
(7, 1, 'ODC A2-PON 1 HQ', 'perumahan pilar mas PMP', -6.208147, 107.182218, ', Kec. Karangbahagia, Kabupaten Bekasi,\r\n\r\n-6.208147, 107.182218', NULL, 'ODC dan OCP', 490, -290, '2026-05-21 10:41:24', '2026-05-21 13:17:51'),
(8, 1, 'ODC B1-PON 2 HQ', 'OCD dekat rumah pak adam', -6.209117, 107.181717, 'Kab. Bekasi\r\nKaranganyar, Kec. Karangbahagia, Kabupaten Bekasi, Jawa Barat 17530', NULL, 'ODC dan ODP', 500, -80, '2026-05-21 10:43:18', '2026-05-21 14:11:38'),
(9, 1, 'ODC B2-PON 2 HQ', 'tiang ODC dan ODP blok F', -6.209202, 107.182552, 'Kab. Bekasi,Karanganyar, Kec. Karangbahagia, Kabupaten Bekasi, Jawa Barat 17530\r\n6.209202, 107.182552', NULL, 'ODC dan ODP', 470, -180, '2026-05-21 10:45:47', '2026-05-21 14:11:55'),
(10, 3, 'ODP 2A/HQ', 'ODP A2', -6.20772, 107.181169, 'Perumahan pilar mas persada No.2, Karanganyar, Kec. Karangbahagia, Kabupaten Bekasi, Jawa Barat 17530', NULL, 'ODP A2 dekat pos sama musollah', 960, 350, '2026-05-21 10:47:43', '2026-05-21 13:18:28'),
(11, 3, 'ODP 3A/HQ', 'tiang ODP', -6.207359, 107.181212, 'Perumahan pilar mas persada No.2, Karanganyar, Kec. Karangbahagia, Kabupaten Bekasi, Jawa Barat 17530', NULL, 'gang depan musolah', 860, 460, '2026-05-21 11:20:55', '2026-05-21 13:20:39'),
(12, 3, 'ODP 4A/HQ', 'tiang ODP 4A', 6.20799, 107181366, 'Jl. Katimaha, Karanganyar, Kec. Karangbahagia, Kabupaten Bekasi, Jawa Barat 17530', NULL, 'tiang ke 2 dari gang masuk musolah', 1180, 140, '2026-05-21 11:23:29', '2026-05-21 13:10:05'),
(13, 3, 'ODP A5/HQ', 'tiang ODP', -6.207511, 107.181434, 'Pilar Mas Persada 2 Jl.Raya Silasukatani Km.7, RW.5, Karangsetia, Kec. Karangbahagia, Kabupaten Bekasi, Jawa Barat 17530  -6.207513, 107.181443', NULL, 'tiang ke dua dari gang masuk', 1300, 50, '2026-05-21 11:26:35', '2026-05-21 13:10:06'),
(14, 3, 'ODP 5A/HQ', 'tiang ODP 5A', -6.207513, 107.181443, 'Pilar mas persada 2 blok D1/01, Karanganyar, karang bhagia, Kabupaten Bekasi, Jawa Barat 17530', NULL, 'tiang ke dua dari gang masuk dekat toko sembako kaffa', 1060, 240, '2026-05-21 11:27:56', '2026-05-21 13:10:03');

-- --------------------------------------------------------

--
-- Struktur dari tabel `node_types`
--

CREATE TABLE `node_types` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `node_types`
--

INSERT INTO `node_types` (`id`, `name`, `label`, `icon`, `created_at`) VALUES
(1, 'odc', 'ODC', 'odc.png', '2026-05-19 16:37:03'),
(2, 'pon', 'PON', 'pon.png', '2026-05-19 16:37:03'),
(3, 'box', 'Box / ODP', 'box.png', '2026-05-19 16:37:03'),
(4, 'pole', 'Tiang', 'pole.png', '2026-05-19 16:37:03'),
(5, 'customer', 'Customer', 'customer.png', '2026-05-19 16:37:03'),
(6, 'server', 'Server', 'server.png', '2026-05-19 16:37:03'),
(7, 'olc', 'OLC', 'olc.png', '2026-05-19 16:37:03');

-- --------------------------------------------------------

--
-- Struktur dari tabel `photos`
--

CREATE TABLE `photos` (
  `id` int(11) NOT NULL,
  `node_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `caption` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `schema_migrations`
--

CREATE TABLE `schema_migrations` (
  `id` varchar(120) NOT NULL,
  `applied_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `schema_migrations`
--

INSERT INTO `schema_migrations` (`id`, `applied_at`) VALUES
('001_init_schema', '2026-05-19 16:37:03'),
('002_incidents_add_columns', '2026-05-19 16:37:03'),
('003_users_add_columns', '2026-05-19 16:37:03'),
('004_incidents_normalize_status', '2026-05-19 16:37:03'),
('005_seed_node_types_and_superadmin', '2026-05-19 16:37:03'),
('006_optional_seed_demo', '2026-05-19 16:37:03'),
('007_optional_seed_demo_incidents', '2026-05-21 15:28:55');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `is_active` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'jonusadeveloper@gmail.com', '$2a$10$iJcs9wEUPTqRiATl82KSF.TsmGCkJD3U1qRbSyDTTs08lmbZw.aAm', 'superadmin', 1, '2026-05-19 16:37:03', '2026-05-19 16:37:03');

-- --------------------------------------------------------

--
-- Struktur dari tabel `work_reports`
--

CREATE TABLE `work_reports` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) DEFAULT NULL,
  `node_id` int(11) DEFAULT NULL,
  `technician_name` varchar(255) DEFAULT NULL,
  `report_title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'completed',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `work_reports`
--

INSERT INTO `work_reports` (`id`, `incident_id`, `node_id`, `technician_name`, `report_title`, `description`, `photo_path`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 9, NULL, 'Pekerjaan selesai - laser out', 'aea', NULL, 'completed', '2026-05-21 14:40:03', '2026-05-21 14:40:03');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `links`
--
ALTER TABLE `links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `links_unique_pair` (`source_node_id`,`target_node_id`);

--
-- Indeks untuk tabel `nodes`
--
ALTER TABLE `nodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indeks untuk tabel `node_types`
--
ALTER TABLE `node_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeks untuk tabel `photos`
--
ALTER TABLE `photos`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `schema_migrations`
--
ALTER TABLE `schema_migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `work_reports`
--
ALTER TABLE `work_reports`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `incidents`
--
ALTER TABLE `incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `links`
--
ALTER TABLE `links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `nodes`
--
ALTER TABLE `nodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `photos`
--
ALTER TABLE `photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `work_reports`
--
ALTER TABLE `work_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
