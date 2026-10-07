<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'petugas') {
    header("Location: ../index.php");
    exit;
}

include '../koneksi.php';

// Statistik
$total_buku       = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM buku")) ?: 0;
$total_dipinjam   = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM peminjaman WHERE status='dipinjam'")) ?: 0;
$total_dikembalikan = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM peminjaman WHERE status='dikembalikan'")) ?: 0;
$total_anggota    = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM users WHERE level='anggota'"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas - Perpustakaan</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f1f5f9; color: #333; display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(160deg, #1b5e20, #2e7d32);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
        }
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        .sidebar-header .logo { font-size: 28px; margin-bottom: 8px; }
        .sidebar-header h3 { font-size: 16px; font-weight: bold; }
        .sidebar-header p { font-size: 12px; opacity: 0.7; margin-top: 3px; }
        .sidebar-menu { padding: 15px 0; flex: 1; }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 20px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left: 3px solid #a5d6a7;
            padding-left: 17px;
        }
        .sidebar-menu a .icon { font-size: 18px; }
        .sidebar-footer { padding: 15px 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .sidebar-footer a {
            display: flex; align-items: center; gap: 10px;
            color: #ef9a9a; text-decoration: none; font-size: 14px;
        }
        .sidebar-footer a:hover { color: #ef5350; }

        /* Main */
        .main { margin-left: 250px; flex: 1; display: flex; flex-direction: column; }
        .topbar {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .topbar h1 { font-size: 20px; color: #1b5e20; }
        .topbar .user-info { display: flex; align-items: center; gap: 12px; }
        .topbar .avatar {
            width: 38px; height: 38px;
            background: #2e7d32;
            color: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 15px;
        }
        .topbar .user-name { font-size: 14px; font-weight: bold; }
        .topbar .user-role {
            font-size: 12px; background: #2e7d32; color: white;
            padding: 2px 8px; border-radius: 10px;
        }
        .content { padding: 30px; }

        .welcome-card {
            background: linear-gradient(135deg, #1b5e20, #388e3c);
            color: white;
            border-radius: 12px;
            padding: 25px 30px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .welcome-card h2 { font-size: 22px; margin-bottom: 5px; }
        .welcome-card p { opacity: 0.85; font-size: 14px; }
        .welcome-card .wc-icon { font-size: 60px; opacity: 0.3; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 5px solid;
        }
        .stat-card.green  { border-color: #2e7d32; }
        .stat-card.blue   { border-color: #1565c0; }
        .stat-card.orange { border-color: #e65100; }
        .stat-card.teal   { border-color: #00695c; }
        .stat-icon { font-size: 36px; min-width: 45px; text-align: center; }
        .stat-info h3 { font-size: 28px; font-weight: bold; color: #333; }
        .stat-info p  { font-size: 13px; color: #666; margin-top: 2px; }

        .section-title { font-size: 16px; font-weight: bold; color: #555; margin-bottom: 15px; }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        }
        .menu-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
            text-align: center;
            border-top: 4px solid;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: #333;
            display: block;
        }
        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        .menu-card.green  { border-top-color: #2e7d32; }
        .menu-card.blue   { border-top-color: #1565c0; }
        .menu-card.orange { border-top-color: #e65100; }
        .menu-card.teal   { border-top-color: #00695c; }
        .menu-card .mc-icon { font-size: 36px; margin-bottom: 10px; }
        .menu-card h4 { font-size: 15px; margin-bottom: 6px; }
        .menu-card p  { font-size: 13px; color: #777; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">📚</div>
        <h3>Perpustakaan</h3>
        <p>Panel Petugas</p>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="active">
            <span class="icon">🏠</span> Dashboard
        </a>
        <a href="#" title="Segera hadir">
            <span class="icon">📖</span> Data Buku
        </a>
        <a href="#" title="Segera hadir">
            <span class="icon">🔄</span> Peminjaman
        </a>
        <a href="#" title="Segera hadir">
            <span class="icon">✅</span> Pengembalian
        </a>
        <a href="#" title="Segera hadir">
            <span class="icon">👤</span> Data Anggota
        </a>
    </div>
    <div class="sidebar-footer">
        <a href="../logout.php">
            <span>🚪</span> Logout
        </a>
    </div>
</div>

<!-- Main -->
<div class="main">
    <div class="topbar">
        <h1>Dashboard Petugas</h1>
        <div class="user-info">
            <div class="avatar"><?php echo strtoupper(substr($_SESSION['nama'] ?? 'P', 0, 1)); ?></div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'Petugas'); ?></div>
                <div><span class="user-role">Petugas</span></div>
            </div>
        </div>
    </div>

    <div class="content">
        <!-- Welcome -->
        <div class="welcome-card">
            <div>
                <h2>Halo, <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Petugas'); ?>! 👋</h2>
                <p>Anda login sebagai <strong>Petugas Perpustakaan</strong>. Kelola transaksi peminjaman & pengembalian buku.</p>
            </div>
            <div class="wc-icon">📋</div>
        </div>

        <!-- Statistik -->
        <p class="section-title">📊 Statistik Layanan</p>
        <div class="stats-grid">
            <div class="stat-card green">
                <div class="stat-icon">📖</div>
                <div class="stat-info">
                    <h3><?php echo $total_buku; ?></h3>
                    <p>Total Buku</p>
                </div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon">👤</div>
                <div class="stat-info">
                    <h3><?php echo $total_anggota; ?></h3>
                    <p>Total Anggota</p>
                </div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon">🔄</div>
                <div class="stat-info">
                    <h3><?php echo $total_dipinjam; ?></h3>
                    <p>Sedang Dipinjam</p>
                </div>
            </div>
            <div class="stat-card teal">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3><?php echo $total_dikembalikan; ?></h3>
                    <p>Sudah Dikembalikan</p>
                </div>
            </div>
        </div>

        <!-- Menu -->
        <p class="section-title">📋 Menu Layanan</p>
        <div class="menu-grid">
            <a href="#" class="menu-card green">
                <div class="mc-icon">🔄</div>
                <h4>Proses Peminjaman</h4>
                <p>Catat peminjaman buku oleh anggota</p>
            </a>
            <a href="#" class="menu-card teal">
                <div class="mc-icon">✅</div>
                <h4>Proses Pengembalian</h4>
                <p>Catat pengembalian buku dari anggota</p>
            </a>
            <a href="#" class="menu-card blue">
                <div class="mc-icon">📖</div>
                <h4>Data Buku</h4>
                <p>Lihat ketersediaan koleksi buku</p>
            </a>
            <a href="#" class="menu-card orange">
                <div class="mc-icon">👤</div>
                <h4>Data Anggota</h4>
                <p>Lihat dan verifikasi data anggota</p>
            </a>
        </div>
    </div>
</div>

</body>
</html>
