<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'anggota') {
    header("Location: ../index.php"); exit;
}
include '../koneksi.php';

$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$where = $search ? "WHERE judul LIKE '%$search%' OR pengarang LIKE '%$search%' OR kategori LIKE '%$search%'" : '';

$result = mysqli_query($koneksi, "SELECT * FROM buku $where ORDER BY judul ASC");
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
    <title>Cari Buku - Portal Anggota</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f1f5f9; color: #333; display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: linear-gradient(160deg, #4a148c, #7b1fa2); color: white; display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-header .logo { font-size: 28px; margin-bottom: 8px; }
        .sidebar-header h3 { font-size: 16px; font-weight: bold; }
        .sidebar-header p { font-size: 12px; opacity: 0.7; margin-top: 3px; }
        .sidebar-menu { padding: 15px 0; flex: 1; overflow-y: auto; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; padding: 13px 20px; color: rgba(255,255,255,0.85); text-decoration: none; font-size: 14px; transition: all 0.2s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.15); color: white; border-left: 3px solid #ce93d8; padding-left: 17px; }
        .sidebar-menu a .icon { font-size: 18px; min-width: 22px; text-align: center; }
        .sidebar-footer { padding: 15px 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .sidebar-footer a { display: flex; align-items: center; gap: 10px; color: #ef9a9a; text-decoration: none; font-size: 14px; }
        .sidebar-footer a:hover { color: #ef5350; }

        .main { margin-left: 250px; flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .topbar { background: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
        .topbar h1 { font-size: 20px; color: #4a148c; }
        .topbar .user-info { display: flex; align-items: center; gap: 12px; }
        .topbar .avatar { width: 38px; height: 38px; background: #7b1fa2; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 15px; }
        .topbar .user-name { font-size: 14px; font-weight: bold; }
        .topbar .user-role { font-size: 12px; background: #7b1fa2; color: white; padding: 2px 8px; border-radius: 10px; }
        .content { padding: 25px 30px; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .alert.success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert.error   { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        .search-container { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); margin-bottom: 25px; display: flex; gap: 15px; align-items: center; }
        .search-container input { flex: 1; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; }
        .search-container input:focus { outline: none; border-color: #7b1fa2; box-shadow: 0 0 0 3px rgba(123, 31, 162, 0.1); }
        .search-container button { padding: 12px 25px; background: #7b1fa2; color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .search-container button:hover { background: #4a148c; }

        .books-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px; }
        .book-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 3px 10px rgba(0,0,0,0.06); display: flex; flex-direction: column; transition: transform 0.2s; }
        .book-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
        .book-cover { height: 160px; background: linear-gradient(135deg, #e1bee7, #ce93d8); display: flex; align-items: center; justify-content: center; font-size: 48px; color: white; }
        .book-info { padding: 18px; flex: 1; display: flex; flex-direction: column; }
        .book-category { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #7b1fa2; font-weight: bold; margin-bottom: 5px; }
        .book-title { font-size: 16px; font-weight: bold; color: #333; margin-bottom: 8px; line-height: 1.3; }
        .book-author { font-size: 13px; color: #666; margin-bottom: 12px; }
        .book-meta { display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 15px; border-top: 1px solid #eee; }
        .stok-info { font-size: 12px; font-weight: bold; display: flex; align-items: center; gap: 5px; }
        .stok-info.ada { color: #2e7d32; }
        .stok-info.habis { color: #c62828; }
        .btn-pinjam { padding: 8px 16px; background: #7b1fa2; color: white; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold; transition: 0.2s; border: none; cursor: pointer; }
        .btn-pinjam:hover { background: #4a148c; }
        .btn-pinjam.disabled { background: #ccc; cursor: not-allowed; }

        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); grid-column: 1 / -1; }
        .empty-state .es-icon { font-size: 60px; margin-bottom: 15px; opacity: 0.5; }
        .empty-state p { font-size: 15px; color: #666; }

        /* Modal konfirmasi pinjam */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; align-items: center; justify-content: center; }
        .modal-overlay.show { display: flex; }
        .modal { background: white; border-radius: 12px; padding: 30px; max-width: 400px; width: 90%; text-align: center; }
        .modal .modal-icon { font-size: 48px; margin-bottom: 12px; }
        .modal h3 { font-size: 18px; margin-bottom: 8px; color: #4a148c; }
        .modal p  { font-size: 14px; color: #666; margin-bottom: 22px; line-height: 1.5; }
        .modal-btns { display: flex; gap: 10px; justify-content: center; }
        .modal-btns .btn-cancel { padding: 10px 22px; border: 1px solid #ccc; border-radius: 8px; background: white; cursor: pointer; font-size: 14px; }
        .modal-btns .btn-confirm { padding: 10px 22px; border: none; border-radius: 8px; background: #7b1fa2; color: white; cursor: pointer; font-size: 14px; font-weight: bold; text-decoration: none; }
        .modal-btns .btn-confirm:hover { background: #4a148c; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">📚</div>
        <h3>Perpustakaan</h3>
        <p>Portal Anggota</p>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
        <a href="cari_buku.php" class="active"><span class="icon">🔍</span> Cari Buku</a>
        <a href="#" title="Segera hadir"><span class="icon">🔄</span> Riwayat Pinjam</a>
        <a href="#" title="Segera hadir"><span class="icon">👤</span> Profil Saya</a>
    </div>
    <div class="sidebar-footer">
        <a href="../logout.php"><span>🚪</span> Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Katalog Buku</h1>
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1)) ?></div>
            <div>
                <div class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Anggota') ?></div>
                <div><span class="user-role">Anggota</span></div>
            </div>
        </div>
    </div>

    <div class="content">
        <?php if ($pesan): ?>
        <div class="alert <?= $tipe ?>">
            <?= $tipe === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($pesan) ?>
        </div>
        <?php endif; ?>

        <form method="GET" class="search-container">
            <input type="text" name="search" placeholder="Ketik judul buku, nama pengarang, atau kategori..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Cari Buku 🔍</button>
        </form>

        <div class="books-grid">
            <?php if ($total > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="book-card">
                    <div class="book-cover">📖</div>
                    <div class="book-info">
                        <div class="book-category"><?= htmlspecialchars($row['kategori']) ?></div>
                        <div class="book-title" title="<?= htmlspecialchars($row['judul']) ?>"><?= htmlspecialchars($row['judul']) ?></div>
                        <div class="book-author">Oleh: <?= htmlspecialchars($row['pengarang']) ?></div>
                        
                        <div class="book-meta">
                            <?php if ($row['stok'] > 0): ?>
                                <div class="stok-info ada"><span>📦</span> <?= $row['stok'] ?> Tersedia</div>
                                <button type="button" class="btn-pinjam" onclick="konfirmasiPinjam(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['judul'])) ?>')">Pinjam</button>
                            <?php else: ?>
                                <div class="stok-info habis"><span>🚫</span> Habis</div>
                                <button type="button" class="btn-pinjam disabled" disabled>Pinjam</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="es-icon">🔍</div>
                    <h3>Buku Tidak Ditemukan</h3>
                    <p>Coba gunakan kata kunci pencarian yang lain.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Pinjam -->
<div class="modal-overlay" id="modalPinjam">
    <div class="modal">
        <div class="modal-icon">📚</div>
        <h3>Konfirmasi Peminjaman</h3>
        <p id="modalText">Anda yakin ingin meminjam buku ini? Masa peminjaman adalah 7 hari.</p>
        <div class="modal-btns">
            <button class="btn-cancel" onclick="tutupModal()">Batal</button>
            <a href="#" id="btnKonfirmPinjam" class="btn-confirm">Ya, Pinjam Buku</a>
        </div>
    </div>
</div>

<script>
function konfirmasiPinjam(id, judul) {
    document.getElementById('modalText').innerHTML = 'Anda yakin ingin meminjam buku:<br><strong>' + judul + '</strong>?<br><br><small>Masa peminjaman adalah 7 hari dari sekarang.</small>';
    document.getElementById('btnKonfirmPinjam').href = 'pinjam_buku.php?id=' + id;
    document.getElementById('modalPinjam').classList.add('show');
}
function tutupModal() {
    document.getElementById('modalPinjam').classList.remove('show');
}
document.getElementById('modalPinjam').addEventListener('click', function(e) {
    if (e.target === this) tutupModal();
});
</script>

</body>
</html>
