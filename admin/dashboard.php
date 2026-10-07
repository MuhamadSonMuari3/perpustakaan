<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

include '../koneksi.php';

// Statistik
$total_anggota = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM users WHERE level='anggota'"));
$total_petugas = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM users WHERE level='petugas'"));
$total_buku    = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM buku")) ?: 0;
$total_pinjam  = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM peminjaman WHERE status='dipinjam'")) ?: 0 ;

?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Perpustakaan</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f1f5f9; color: #333; display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(160deg, #1a237e, #283593);
            color: white;
            display: flex;
            flex-direction: column;
            padding: 0;
            position: fixed;
            height: 100vh;
            z-index: 100;
        }
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        .sidebar-header .logo {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .sidebar-header h3 {
            font-size: 16px;
            font-weight: bold;
        }
        .sidebar-header p {
            font-size: 12px;
            opacity: 0.7;
            margin-top: 3px;
        }
        .sidebar-menu {
            padding: 15px 0;
            flex: 1;
        }
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
            border-left: 3px solid #90caf9;
            padding-left: 17px;
        }
        .sidebar-menu a .icon { font-size: 18px; }
        .sidebar-footer {
            padding: 15px 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ef9a9a;
            text-decoration: none;
            font-size: 14px;
        }
        .sidebar-footer a:hover { color: #ef5350; }

        /* Main Content */
        .main {
            margin-left: 250px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .topbar h1 { font-size: 20px; color: #1a237e; }
        .topbar .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .topbar .avatar {
            width: 38px; height: 38px;
            background: #1a237e;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 15px;
        }
        .topbar .user-name { font-size: 14px; font-weight: bold; }
        .topbar .user-role {
            font-size: 12px;
            background: #1a237e;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
        }

        .content { padding: 30px; }

        /* Stat Cards */
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
        .stat-card.blue   { border-color: #1565c0; }
        .stat-card.green  { border-color: #2e7d32; }
        .stat-card.orange { border-color: #e65100; }
        .stat-card.purple { border-color: #6a1b9a; }
        .stat-icon {
            font-size: 36px;
            min-width: 45px;
            text-align: center;
        }
        .stat-info h3 { font-size: 28px; font-weight: bold; color: #333; }
        .stat-info p  { font-size: 13px; color: #666; margin-top: 2px; }

        /* Menu Cards */
        .section-title { font-size: 16px; font-weight: bold; color: #555; margin-bottom: 15px; }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 30px;
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
        .menu-card.blue   { border-top-color: #1565c0; }
        .menu-card.green  { border-top-color: #2e7d32; }
        .menu-card.orange { border-top-color: #e65100; }
        .menu-card.purple { border-top-color: #6a1b9a; }
        .menu-card.red    { border-top-color: #c62828; }
        .menu-card .mc-icon { font-size: 36px; margin-bottom: 10px; }
        .menu-card h4 { font-size: 15px; margin-bottom: 6px; }
        .menu-card p  { font-size: 13px; color: #777; }

        .welcome-card {
            background: linear-gradient(135deg, #1a237e, #3949ab);
            color: white;
            border-radius: 12px;
            padding: 25px 30px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .welcome-card h2 { font-size: 22px; margin-bottom: 5px; }
        .welcome-card p  { opacity: 0.85; font-size: 14px; }
        .welcome-card .wc-icon { font-size: 60px; opacity: 0.3; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">📚</div>
        <h3>Perpustakaan</h3>
        <p>Panel Admin</p>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="active">
            <span class="icon">🏠</span> Dashboard
        </a>
        <a href="kelola_user.php">
            <span class="icon">👥</span> Kelola User
        </a>
        <a href="kelola_buku.php">
            <span class="icon">📖</span> Kelola Buku
        </a>
        <a href="#" title="Segera hadir">
            <span class="icon">🔄</span> Data Peminjaman
        </a>
        <a href="#" title="Segera hadir">
            <span class="icon">📊</span> Laporan
        </a>
        <a href="#" title="Segera hadir">
            <span class="icon">⚙️</span> Pengaturan
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
        <h1>Dashboard Admin</h1>
        <div class="user-info">
            <div class="avatar"><?php echo strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1)); ?></div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'Admin'); ?></div>
                <div><span class="user-role">Admin</span></div>
            </div>
        </div>
    </div>

    <div class="content">
        <!-- Welcome -->
        <div class="welcome-card">
            <div>
                <h2>Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Admin'); ?>! 👋</h2>
                <p>Anda login sebagai <strong>Administrator</strong>. Kelola seluruh sistem perpustakaan dari sini.</p>
            </div>
            <div class="wc-icon">👑</div>
        </div>

        <!-- Statistik -->
        <p class="section-title">📊 Statistik Sistem</p>
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon">👤</div>
                <div class="stat-info">
                    <h3><?php echo $total_anggota; ?></h3>
                    <p>Total Anggota</p>
                </div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon">👨‍💼</div>
                <div class="stat-info">
                    <h3><?php echo $total_petugas; ?></h3>
                    <p>Total Petugas</p>
                </div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon">📖</div>
                <div class="stat-info">
                    <h3><?php echo $total_buku; ?></h3>
                    <p>Total Buku</p>
                </div>
            </div>
            <div class="stat-card purple">
                <div class="stat-icon">🔄</div>
                <div class="stat-info">
                    <h3><?php echo $total_pinjam; ?></h3>
                    <p>Sedang Dipinjam</p>
                </div>
            </div>
        </div>

        <!-- Menu -->
        <p class="section-title">⚙️ Menu Admin</p>
        <div class="menu-grid">
            <a href="kelola_user.php" class="menu-card blue">
                <div class="mc-icon">👥</div>
                <h4>Kelola User</h4>
                <p>Tambah, edit & hapus semua user</p>
            </a>
            <a href="kelola_buku.php" class="menu-card orange">
                <div class="mc-icon">📖</div>
                <h4>Kelola Buku</h4>
                <p>Tambah, edit & hapus data buku</p>
            </a>
            <a href="#" class="menu-card purple">
                <div class="mc-icon">🔄</div>
                <h4>Data Peminjaman</h4>
                <p>Pantau semua transaksi peminjaman</p>
            </a>
            <a href="#" class="menu-card red">
                <div class="mc-icon">📊</div>
                <h4>Laporan</h4>
                <p>Cetak laporan perpustakaan</p>
            </a>
        </div>
    </div>
</div>

</body>
</html>
