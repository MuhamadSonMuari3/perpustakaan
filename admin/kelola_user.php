<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    header("Location: ../index.php"); exit;
}
include '../koneksi.php';

// Filter berdasarkan level
$filter = isset($_GET['filter']) ? mysqli_real_escape_string($koneksi, $_GET['filter']) : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';

$where = [];
if ($filter && in_array($filter, ['admin','petugas','anggota'])) {
    $where[] = "level = '$filter'";
}
if ($search) {
    $where[] = "(nama LIKE '%$search%' OR username LIKE '%$search%')";
}
$where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$result = mysqli_query($koneksi, "SELECT * FROM users $where_sql ORDER BY level, nama ASC");
$total  = mysqli_num_rows($result);

// Hitung per role
$total_admin   = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM users WHERE level='admin'"));
$total_petugas = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM users WHERE level='petugas'"));
$total_anggota = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM users WHERE level='anggota'"));

$pesan = $_SESSION['pesan'] ?? '';
$tipe  = $_SESSION['tipe']  ?? '';
unset($_SESSION['pesan'], $_SESSION['tipe']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin Perpustakaan</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f1f5f9; color: #333; display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar { width: 250px; background: linear-gradient(160deg, #1a237e, #283593); color: white; display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-header .logo { font-size: 28px; margin-bottom: 8px; }
        .sidebar-header h3 { font-size: 16px; font-weight: bold; }
        .sidebar-header p { font-size: 12px; opacity: 0.7; margin-top: 3px; }
        .sidebar-menu { padding: 15px 0; flex: 1; overflow-y: auto; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; padding: 13px 20px; color: rgba(255,255,255,0.85); text-decoration: none; font-size: 14px; transition: all 0.2s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.15); color: white; border-left: 3px solid #90caf9; padding-left: 17px; }
        .sidebar-menu a .icon { font-size: 18px; min-width: 22px; text-align: center; }
        .sidebar-footer { padding: 15px 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .sidebar-footer a { display: flex; align-items: center; gap: 10px; color: #ef9a9a; text-decoration: none; font-size: 14px; }
        .sidebar-footer a:hover { color: #ef5350; }

        /* Main */
        .main { margin-left: 250px; flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .topbar { background: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
        .topbar h1 { font-size: 20px; color: #1a237e; }
        .topbar .user-info { display: flex; align-items: center; gap: 12px; }
        .topbar .avatar { width: 38px; height: 38px; background: #1a237e; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 15px; }
        .topbar .user-name { font-size: 14px; font-weight: bold; }
        .topbar .user-role { font-size: 12px; background: #1a237e; color: white; padding: 2px 8px; border-radius: 10px; }

        .content { padding: 25px 30px; }

        /* Alert */
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .alert.success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert.error   { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        /* Summary cards */
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .summary-card { background: white; border-radius: 10px; padding: 16px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); display: flex; align-items: center; gap: 12px; border-left: 5px solid; cursor: pointer; transition: transform 0.15s; text-decoration: none; color: #333; }
        .summary-card:hover { transform: translateY(-2px); }
        .summary-card.all    { border-color: #546e7a; }
        .summary-card.admin  { border-color: #c62828; }
        .summary-card.petugas{ border-color: #2e7d32; }
        .summary-card.anggota{ border-color: #1565c0; }
        .summary-card .sc-icon { font-size: 28px; }
        .summary-card .sc-info h4 { font-size: 22px; font-weight: bold; line-height: 1; }
        .summary-card .sc-info p  { font-size: 12px; color: #666; margin-top: 2px; }

        /* Toolbar */
        .toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 18px; }
        .toolbar-left { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .search-box { display: flex; }
        .search-box input { padding: 9px 14px; border: 1px solid #ccc; border-radius: 8px 0 0 8px; font-size: 14px; width: 230px; }
        .search-box input:focus { outline: none; border-color: #1a237e; }
        .search-box button { padding: 9px 14px; background: #1a237e; color: white; border: none; border-radius: 0 8px 8px 0; cursor: pointer; font-size: 14px; }
        .filter-tabs { display: flex; gap: 6px; }
        .filter-tab { padding: 7px 14px; border-radius: 20px; font-size: 13px; text-decoration: none; border: 1px solid #ddd; color: #555; background: white; transition: all 0.15s; }
        .filter-tab:hover  { border-color: #1a237e; color: #1a237e; }
        .filter-tab.active { background: #1a237e; color: white; border-color: #1a237e; }
        .btn-tambah { display: flex; align-items: center; gap: 6px; padding: 9px 18px; background: #1a237e; color: white; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: bold; white-space: nowrap; }
        .btn-tambah:hover { background: #283593; }

        /* Table */
        .table-card { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); overflow: hidden; }
        .table-card .tc-header { padding: 16px 20px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .table-card .tc-header h4 { font-size: 15px; color: #1a237e; }
        .table-card .tc-header span { font-size: 13px; color: #777; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #e8eaf6; color: #1a237e; padding: 12px 16px; text-align: left; font-size: 13px; font-weight: bold; white-space: nowrap; }
        tbody td { padding: 13px 16px; border-bottom: 1px solid #f5f5f5; font-size: 14px; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #fafafa; }

        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge.admin   { background: #ffebee; color: #c62828; }
        .badge.petugas { background: #e8f5e9; color: #2e7d32; }
        .badge.anggota { background: #e3f2fd; color: #1565c0; }

        .avatar-sm { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; color: white; flex-shrink: 0; }
        .avatar-sm.admin   { background: #c62828; }
        .avatar-sm.petugas { background: #2e7d32; }
        .avatar-sm.anggota { background: #1565c0; }

        .user-cell { display: flex; align-items: center; gap: 10px; }
        .user-cell .uc-name { font-weight: bold; font-size: 14px; }
        .user-cell .uc-user { font-size: 12px; color: #888; }

        .action-btns { display: flex; gap: 7px; }
        .btn-edit { padding: 6px 14px; background: #f0ad00; color: white; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-edit:hover { background: #e09e00; }
        .btn-hapus { padding: 6px 14px; background: #dc3545; color: white; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-hapus:hover { background: #bb2d3b; }

        .empty-state { text-align: center; padding: 50px 20px; color: #aaa; }
        .empty-state .es-icon { font-size: 48px; margin-bottom: 12px; }
        .empty-state p { font-size: 14px; }

        /* Modal konfirmasi hapus */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; align-items: center; justify-content: center; }
        .modal-overlay.show { display: flex; }
        .modal { background: white; border-radius: 12px; padding: 30px; max-width: 380px; width: 90%; text-align: center; }
        .modal .modal-icon { font-size: 48px; margin-bottom: 12px; }
        .modal h3 { font-size: 18px; margin-bottom: 8px; }
        .modal p  { font-size: 14px; color: #666; margin-bottom: 22px; }
        .modal-btns { display: flex; gap: 10px; justify-content: center; }
        .modal-btns .btn-cancel { padding: 10px 22px; border: 1px solid #ccc; border-radius: 8px; background: white; cursor: pointer; font-size: 14px; }
        .modal-btns .btn-confirm { padding: 10px 22px; border: none; border-radius: 8px; background: #dc3545; color: white; cursor: pointer; font-size: 14px; font-weight: bold; }
        .modal-btns .btn-confirm:hover { background: #bb2d3b; }
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
        <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
        <a href="kelola_user.php" class="active"><span class="icon">👥</span> Kelola User</a>
        <a href="kelola_buku.php"><span class="icon">📖</span> Kelola Buku</a>
        <a href="#"><span class="icon">🔄</span> Data Peminjaman</a>
        <a href="#"><span class="icon">📊</span> Laporan</a>
        <a href="#"><span class="icon">⚙️</span> Pengaturan</a>
    </div>
    <div class="sidebar-footer">
        <a href="../logout.php"><span>🚪</span> Logout</a>
    </div>
</div>

<!-- Main -->
<div class="main">
    <div class="topbar">
        <h1>Kelola User</h1>
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1)) ?></div>
            <div>
                <div class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></div>
                <div><span class="user-role">Admin</span></div>
            </div>
        </div>
    </div>

    <div class="content">

        <?php if ($pesan): ?>
        <div class="alert <?= $tipe ?>">
            <?= $tipe === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($pesan) ?>
        </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <a href="kelola_user.php" class="summary-card all <?= !$filter ? 'active' : '' ?>">
                <div class="sc-icon">👥</div>
                <div class="sc-info">
                    <h4><?= $total_admin + $total_petugas + $total_anggota ?></h4>
                    <p>Total User</p>
                </div>
            </a>
            <a href="kelola_user.php?filter=admin" class="summary-card admin">
                <div class="sc-icon">👑</div>
                <div class="sc-info">
                    <h4><?= $total_admin ?></h4>
                    <p>Admin</p>
                </div>
            </a>
            <a href="kelola_user.php?filter=petugas" class="summary-card petugas">
                <div class="sc-icon">👨‍💼</div>
                <div class="sc-info">
                    <h4><?= $total_petugas ?></h4>
                    <p>Petugas</p>
                </div>
            </a>
            <a href="kelola_user.php?filter=anggota" class="summary-card anggota">
                <div class="sc-icon">👤</div>
                <div class="sc-info">
                    <h4><?= $total_anggota ?></h4>
                    <p>Anggota</p>
                </div>
            </a>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="toolbar-left">
                <!-- Search -->
                <form method="GET" class="search-box">
                    <?php if ($filter): ?><input type="hidden" name="filter" value="<?= $filter ?>"><?php endif; ?>
                    <input type="text" name="search" placeholder="Cari nama / username..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">🔍</button>
                </form>
                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <a href="kelola_user.php<?= $search ? '?search='.urlencode($search) : '' ?>" class="filter-tab <?= !$filter ? 'active' : '' ?>">Semua</a>
                    <a href="kelola_user.php?filter=admin<?= $search ? '&search='.urlencode($search) : '' ?>" class="filter-tab <?= $filter==='admin' ? 'active' : '' ?>">Admin</a>
                    <a href="kelola_user.php?filter=petugas<?= $search ? '&search='.urlencode($search) : '' ?>" class="filter-tab <?= $filter==='petugas' ? 'active' : '' ?>">Petugas</a>
                    <a href="kelola_user.php?filter=anggota<?= $search ? '&search='.urlencode($search) : '' ?>" class="filter-tab <?= $filter==='anggota' ? 'active' : '' ?>">Anggota</a>
                </div>
            </div>
            <a href="tambah_user.php" class="btn-tambah">➕ Tambah User</a>
        </div>

        <!-- Table -->
        <div class="table-card">
            <div class="tc-header">
                <h4>Daftar User</h4>
                <span>Menampilkan <?= $total ?> user<?= $filter ? " (filter: $filter)" : '' ?><?= $search ? " — pencarian: \"$search\"" : '' ?></span>
            </div>
            <?php if ($total > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Nama / Username</th>
                        <th>Role</th>
                        <th style="width:180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <div class="user-cell">
                                <div class="avatar-sm <?= $row['level'] ?>">
                                    <?= strtoupper(substr($row['nama'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="uc-name"><?= htmlspecialchars($row['nama']) ?></div>
                                    <div class="uc-user">@<?= htmlspecialchars($row['username']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge <?= $row['level'] ?>"><?= ucfirst($row['level']) ?></span></td>
                        <td>
                            <div class="action-btns">
                                <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn-edit">✏️ Edit</a>
                                <?php if ($row['username'] !== $_SESSION['username']): ?>
                                <a href="#" class="btn-hapus"
                                   onclick="konfirmasiHapus(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama'])) ?>')">
                                   🗑️ Hapus
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="es-icon">🔍</div>
                <p>Tidak ada user ditemukan<?= $search ? " untuk pencarian \"$search\"" : '' ?>.</p>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal-overlay" id="modalHapus">
    <div class="modal">
        <div class="modal-icon">⚠️</div>
        <h3>Hapus User?</h3>
        <p id="modalText">Apakah Anda yakin ingin menghapus user ini? Tindakan ini tidak bisa dibatalkan.</p>
        <div class="modal-btns">
            <button class="btn-cancel" onclick="tutupModal()">Batal</button>
            <a href="#" id="btnKonfirmHapus" class="btn-confirm">Ya, Hapus</a>
        </div>
    </div>
</div>

<script>
function konfirmasiHapus(id, nama) {
    document.getElementById('modalText').textContent = 'Hapus user "' + nama + '"? Tindakan ini tidak bisa dibatalkan.';
    document.getElementById('btnKonfirmHapus').href = 'hapus_user.php?id=' + id;
    document.getElementById('modalHapus').classList.add('show');
}
function tutupModal() {
    document.getElementById('modalHapus').classList.remove('show');
}
document.getElementById('modalHapus').addEventListener('click', function(e) {
    if (e.target === this) tutupModal();
});
</script>

</body>
</html>
