<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'anggota') {
    header("Location: ../index.php");
    exit;
}

include '../koneksi.php';

$username = $_SESSION['username'];

// Ambil ID Anggota berdasarkan username
$user_query = mysqli_query($koneksi, "SELECT id FROM users WHERE username = '$username'");
$user_data = mysqli_fetch_assoc($user_query);
$id_anggota = $user_data['id'];

// Riwayat peminjaman anggota ini
$sql_pinjam = "SELECT p.*, b.judul FROM peminjaman p 
               LEFT JOIN buku b ON p.id_buku = b.id 
               WHERE p.id_anggota = '$id_anggota' 
               ORDER BY p.tanggal_pinjam DESC LIMIT 5";
$result_pinjam = mysqli_query($koneksi, $sql_pinjam);

$total_pinjam     = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM peminjaman WHERE id_anggota='$id_anggota' AND status='dipinjam'")) ?: 0;
$total_kembali    = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM peminjaman WHERE id_anggota='$id_anggota' AND status='dikembalikan'")) ?: 0;
$total_semua      = $total_pinjam + $total_kembali;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Anggota - Perpustakaan</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f1f5f9; color: #333; display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(160deg, #4a148c, #7b1fa2);
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
            border-left: 3px solid #ce93d8;
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
        .topbar h1 { font-size: 20px; color: #4a148c; }
        .topbar .user-info { display: flex; align-items: center; gap: 12px; }
        .topbar .avatar {
            width: 38px; height: 38px;
            background: #7b1fa2;
            color: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 15px;
        }
        .topbar .user-name { font-size: 14px; font-weight: bold; }
        .topbar .user-role {
            font-size: 12px; background: #7b1fa2; color: white;
            padding: 2px 8px; border-radius: 10px;
        }
        .content { padding: 30px; }

        .welcome-card {
            background: linear-gradient(135deg, #4a148c, #8e24aa);
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
        .stat-card.purple { border-color: #7b1fa2; }
        .stat-card.orange { border-color: #e65100; }
        .stat-card.teal   { border-color: #00695c; }
        .stat-icon { font-size: 36px; min-width: 45px; text-align: center; }
        .stat-info h3 { font-size: 28px; font-weight: bold; color: #333; }
        .stat-info p  { font-size: 13px; color: #666; margin-top: 2px; }

        .section-title { font-size: 16px; font-weight: bold; color: #555; margin-bottom: 15px; }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        .menu-card.purple { border-top-color: #7b1fa2; }
        .menu-card.blue   { border-top-color: #1565c0; }
        .menu-card.teal   { border-top-color: #00695c; }
        .menu-card .mc-icon { font-size: 36px; margin-bottom: 10px; }
        .menu-card h4 { font-size: 15px; margin-bottom: 6px; }
        .menu-card p  { font-size: 13px; color: #777; }

        /* Tabel riwayat */
        .table-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
        }
        .table-card .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .table-card .table-header h4 { font-size: 15px; color: #4a148c; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { background: #f3e5f5; color: #4a148c; padding: 10px 12px; text-align: left; font-size: 13px; }
        td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge.dipinjam    { background: #fff3cd; color: #856404; }
        .badge.dikembalikan { background: #d1e7dd; color: #0f5132; }
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #aaa;
            font-size: 14px;
        }
        .empty-state .es-icon { font-size: 40px; margin-bottom: 10px; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">📚</div>
        <h3>Perpustakaan</h3>
        <p>Portal Anggota</p>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="active">
            <span class="icon">🏠</span> Dashboard
        </a>
        <a href="cari_buku.php" title="Cari Buku">
            <span class="icon">🔍</span> Cari Buku
        </a>
        <a href="#" title="Segera hadir">
            <span class="icon">🔄</span> Riwayat Pinjam
        </a>
        <a href="#" title="Segera hadir">
            <span class="icon">👤</span> Profil Saya
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
        <h1>Dashboard Anggota</h1>
        <div class="user-info">
            <div class="avatar"><?php echo strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1)); ?></div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'Anggota'); ?></div>
                <div><span class="user-role">Anggota</span></div>
            </div>
        </div>
    </div>

    <div class="content">
        <?php if (isset($_SESSION['pesan'])): ?>
        <div class="alert <?= $_SESSION['tipe'] ?>" style="padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; background: <?= $_SESSION['tipe'] == 'success' ? '#d1e7dd' : '#f8d7da' ?>; color: <?= $_SESSION['tipe'] == 'success' ? '#0f5132' : '#842029' ?>; border: 1px solid <?= $_SESSION['tipe'] == 'success' ? '#badbcc' : '#f5c2c7' ?>;">
            <?= $_SESSION['tipe'] === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($_SESSION['pesan']) ?>
        </div>
        <?php unset($_SESSION['pesan'], $_SESSION['tipe']); endif; ?>

        <!-- Welcome -->
        <div class="welcome-card">
            <div>
                <h2>Halo, <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Anggota'); ?>! 👋</h2>
                <p>Selamat datang di <strong>Portal Anggota Perpustakaan</strong>. Temukan dan pinjam buku favoritmu.</p>
            </div>
            <div class="wc-icon">📚</div>
        </div>

        <!-- Statistik -->
        <p class="section-title">📊 Aktivitas Saya</p>
        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-icon">📚</div>
                <div class="stat-info">
                    <h3><?php echo $total_semua; ?></h3>
                    <p>Total Pinjam</p>
                </div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon">🔄</div>
                <div class="stat-info">
                    <h3><?php echo $total_pinjam; ?></h3>
                    <p>Sedang Dipinjam</p>
                </div>
            </div>
            <div class="stat-card teal">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3><?php echo $total_kembali; ?></h3>
                    <p>Sudah Dikembalikan</p>
                </div>
            </div>
        </div>

        <!-- Menu -->
        <p class="section-title">🔍 Layanan Anggota</p>
        <div class="menu-grid">
            <a href="cari_buku.php" class="menu-card purple">
                <div class="mc-icon">🔍</div>
                <h4>Cari Buku</h4>
                <p>Temukan buku yang ingin dipinjam</p>
            </a>
            <a href="#" class="menu-card blue">
                <div class="mc-icon">🔄</div>
                <h4>Riwayat Pinjam</h4>
                <p>Lihat riwayat peminjaman buku</p>
            </a>
            <a href="#" class="menu-card teal">
                <div class="mc-icon">👤</div>
                <h4>Profil Saya</h4>
                <p>Lihat & edit data profil anggota</p>
            </a>
        </div>

        <!-- Riwayat terbaru -->
        <p class="section-title">📋 Riwayat Peminjaman Terbaru</p>
        <div class="table-card">
            <?php if ($result_pinjam && mysqli_num_rows($result_pinjam) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Judul Buku</th>
                        <th>Tanggal Pinjam</th>
                        <th>Tanggal Kembali</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($result_pinjam)): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($row['judul'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['tanggal_pinjam'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['tanggal_kembali'] ?? '-'); ?></td>
                        <td>
                            <span class="badge <?php echo $row['status']; ?>">
                                <?php echo ucfirst($row['status'] ?? '-'); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="es-icon">📭</div>
                <p>Belum ada riwayat peminjaman buku.</p>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>
