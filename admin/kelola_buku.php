<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    header("Location: ../index.php"); exit;
}
include '../koneksi.php';

$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$where = $search ? "WHERE judul LIKE '%$search%' OR kode_buku LIKE '%$search%' OR pengarang LIKE '%$search%'" : '';

$result = mysqli_query($koneksi, "SELECT * FROM buku $where ORDER BY id DESC");
$total  = mysqli_num_rows($result);

$pesan = $_SESSION['pesan'] ?? '';
$tipe  = $_SESSION['tipe']  ?? '';
unset($_SESSION['pesan'], $_SESSION['tipe']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Buku - Admin Perpustakaan</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f1f5f9; color: #333; display: flex; min-height: 100vh; }
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

        .main { margin-left: 250px; flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .topbar { background: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
        .topbar h1 { font-size: 20px; color: #1a237e; }
        .topbar .user-info { display: flex; align-items: center; gap: 12px; }
        .topbar .avatar { width: 38px; height: 38px; background: #1a237e; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 15px; }
        .topbar .user-name { font-size: 14px; font-weight: bold; }
        .topbar .user-role { font-size: 12px; background: #1a237e; color: white; padding: 2px 8px; border-radius: 10px; }
        .content { padding: 25px 30px; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .alert.success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert.error   { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        .toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 18px; }
        .search-box { display: flex; }
        .search-box input { padding: 9px 14px; border: 1px solid #ccc; border-radius: 8px 0 0 8px; font-size: 14px; width: 260px; }
        .search-box input:focus { outline: none; border-color: #1a237e; }
        .search-box button { padding: 9px 14px; background: #1a237e; color: white; border: none; border-radius: 0 8px 8px 0; cursor: pointer; font-size: 14px; }
        .btn-tambah { display: flex; align-items: center; gap: 6px; padding: 9px 18px; background: #e65100; color: white; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: bold; white-space: nowrap; transition: 0.2s; }
        .btn-tambah:hover { background: #bf360c; }

        .table-card { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); overflow: hidden; }
        .table-card .tc-header { padding: 16px 20px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .table-card .tc-header h4 { font-size: 15px; color: #1a237e; }
        .table-card .tc-header span { font-size: 13px; color: #777; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #e8eaf6; color: #1a237e; padding: 12px 16px; text-align: left; font-size: 13px; font-weight: bold; white-space: nowrap; }
        tbody td { padding: 13px 16px; border-bottom: 1px solid #f5f5f5; font-size: 14px; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #fafafa; }

        .action-btns { display: flex; gap: 7px; }
        .btn-edit { padding: 6px 10px; background: #f0ad00; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-edit:hover { background: #e09e00; }
        .btn-hapus { padding: 6px 10px; background: #dc3545; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-hapus:hover { background: #bb2d3b; }

        .empty-state { text-align: center; padding: 50px 20px; color: #aaa; }
        .empty-state .es-icon { font-size: 48px; margin-bottom: 12px; }
        .empty-state p { font-size: 14px; }

        .stok-badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .stok-badge.ada { background: #e8f5e9; color: #2e7d32; }
        .stok-badge.habis { background: #ffebee; color: #c62828; }

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

<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">📚</div>
        <h3>Perpustakaan</h3>
        <p>Panel Admin</p>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
        <a href="kelola_user.php"><span class="icon">👥</span> Kelola User</a>
        <a href="kelola_buku.php" class="active"><span class="icon">📖</span> Kelola Buku</a>
        <a href="#"><span class="icon">🔄</span> Data Peminjaman</a>
        <a href="#"><span class="icon">📊</span> Laporan</a>
        <a href="#"><span class="icon">⚙️</span> Pengaturan</a>
    </div>
    <div class="sidebar-footer">
        <a href="../logout.php"><span>🚪</span> Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Kelola Buku</h1>
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

        <div class="toolbar">
            <form method="GET" class="search-box">
                <input type="text" name="search" placeholder="Cari judul, kode, pengarang..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit">🔍</button>
            </form>
            <a href="tambah_buku.php" class="btn-tambah">➕ Tambah Buku</a>
        </div>

        <div class="table-card">
            <div class="tc-header">
                <h4>Daftar Buku</h4>
                <span>Menampilkan <?= $total ?> buku</span>
            </div>
            <?php if ($total > 0): ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Kode</th>
                            <th>Judul Buku</th>
                            <th>Pengarang</th>
                            <th>Penerbit</th>
                            <th>Tahun</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th style="width:140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong style="color: #1a237e;"><?= htmlspecialchars($row['kode_buku']) ?></strong></td>
                            <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($row['judul']) ?>">
                                <?= htmlspecialchars($row['judul']) ?>
                            </td>
                            <td><?= htmlspecialchars($row['pengarang']) ?></td>
                            <td><?= htmlspecialchars($row['penerbit']) ?></td>
                            <td><?= htmlspecialchars($row['tahun_terbit']) ?></td>
                            <td><?= htmlspecialchars($row['kategori']) ?></td>
                            <td>
                                <span class="stok-badge <?= $row['stok'] > 0 ? 'ada' : 'habis' ?>">
                                    <?= $row['stok'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="edit_buku.php?id=<?= $row['id'] ?>" class="btn-edit">✏️ Edit</a>
                                    <a href="#" class="btn-hapus" onclick="konfirmasiHapus(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['judul'])) ?>')">🗑️ Hapus</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="es-icon">📚</div>
                <p>Tidak ada buku ditemukan<?= $search ? " untuk pencarian \"$search\"" : '' ?>.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal-overlay" id="modalHapus">
    <div class="modal">
        <div class="modal-icon">⚠️</div>
        <h3>Hapus Buku?</h3>
        <p id="modalText">Apakah Anda yakin ingin menghapus buku ini?</p>
        <div class="modal-btns">
            <button class="btn-cancel" onclick="tutupModal()">Batal</button>
            <a href="#" id="btnKonfirmHapus" class="btn-confirm">Ya, Hapus</a>
        </div>
    </div>
</div>

<script>
function konfirmasiHapus(id, judul) {
    document.getElementById('modalText').textContent = 'Hapus buku "' + judul + '"? Data peminjaman terkait mungkin mencegah penghapusan.';
    document.getElementById('btnKonfirmHapus').href = 'hapus_buku.php?id=' + id;
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
