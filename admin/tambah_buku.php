<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    header("Location: ../index.php"); exit;
}
include '../koneksi.php';

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode      = trim(mysqli_real_escape_string($koneksi, $_POST['kode_buku']));
    $judul     = trim(mysqli_real_escape_string($koneksi, $_POST['judul']));
    $pengarang = trim(mysqli_real_escape_string($koneksi, $_POST['pengarang']));
    $penerbit  = trim(mysqli_real_escape_string($koneksi, $_POST['penerbit']));
    $tahun     = (int)$_POST['tahun_terbit'];
    $kategori  = trim(mysqli_real_escape_string($koneksi, $_POST['kategori']));
    $stok      = (int)$_POST['stok'];

    if (!$kode || !$judul || !$pengarang || !$penerbit || !$tahun || !$kategori || $stok < 0) {
        $pesan = ['tipe'=>'error','teks'=>'Semua field wajib diisi dan stok tidak boleh negatif.'];
    } else {
        $cek = mysqli_query($koneksi, "SELECT id FROM buku WHERE kode_buku='$kode'");
        if (mysqli_num_rows($cek) > 0) {
            $pesan = ['tipe'=>'error','teks'=>'Kode buku sudah terdaftar, gunakan kode lain.'];
        } else {
            $query = "INSERT INTO buku (kode_buku, judul, pengarang, penerbit, tahun_terbit, kategori, stok) 
                      VALUES ('$kode', '$judul', '$pengarang', '$penerbit', $tahun, '$kategori', $stok)";
            if (mysqli_query($koneksi, $query)) {
                $_SESSION['pesan'] = "Buku \"$judul\" berhasil ditambahkan.";
                $_SESSION['tipe']  = 'success';
                header("Location: kelola_buku.php"); exit;
            } else {
                $pesan = ['tipe'=>'error','teks'=>'Gagal menyimpan data buku, coba lagi.'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku - Admin Perpustakaan</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f1f5f9; color: #333; display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: linear-gradient(160deg, #1a237e, #283593); color: white; display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-header .logo { font-size: 28px; margin-bottom: 8px; }
        .sidebar-header h3 { font-size: 16px; }
        .sidebar-header p { font-size: 12px; opacity: 0.7; margin-top: 3px; }
        .sidebar-menu { padding: 15px 0; flex: 1; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; padding: 13px 20px; color: rgba(255,255,255,0.85); text-decoration: none; font-size: 14px; transition: all 0.2s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.15); color: white; border-left: 3px solid #90caf9; padding-left: 17px; }
        .sidebar-menu a .icon { font-size: 18px; min-width: 22px; text-align: center; }
        .sidebar-footer { padding: 15px 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .sidebar-footer a { display: flex; align-items: center; gap: 10px; color: #ef9a9a; text-decoration: none; font-size: 14px; }
        .main { margin-left: 250px; flex: 1; display: flex; flex-direction: column; }
        .topbar { background: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
        .topbar h1 { font-size: 20px; color: #1a237e; }
        .topbar .user-info { display: flex; align-items: center; gap: 12px; }
        .topbar .avatar { width: 38px; height: 38px; background: #1a237e; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 15px; }
        .topbar .user-name { font-size: 14px; font-weight: bold; }
        .topbar .user-role { font-size: 12px; background: #1a237e; color: white; padding: 2px 8px; border-radius: 10px; }
        
        .content { padding: 30px; display: flex; justify-content: center; }

        .form-card { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); width: 100%; max-width: 650px; overflow: hidden; }
        .form-card .fc-header { background: linear-gradient(135deg, #1a237e, #3949ab); color: white; padding: 22px 28px; }
        .form-card .fc-header h2 { font-size: 18px; margin-bottom: 4px; }
        .form-card .fc-header p { font-size: 13px; opacity: 0.85; }
        .form-body { padding: 28px; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert.error   { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .alert.success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 7px; font-weight: bold; font-size: 13px; color: #555; }
        .form-group label span { color: #dc3545; }
        .form-group input, .form-group select {
            width: 100%; padding: 11px 14px; border: 1px solid #ddd; border-radius: 8px;
            font-size: 14px; transition: border 0.2s; background: white;
        }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #1a237e; box-shadow: 0 0 0 3px rgba(26,35,126,0.08); }

        .form-actions { display: flex; gap: 10px; margin-top: 22px; }
        .btn-simpan { flex: 1; padding: 12px; background: #e65100; color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: bold; cursor: pointer; }
        .btn-simpan:hover { background: #bf360c; }
        .btn-batal { padding: 12px 20px; border: 1px solid #ccc; border-radius: 8px; background: white; color: #555; text-decoration: none; font-size: 15px; display: flex; align-items: center; }
        .btn-batal:hover { background: #f5f5f5; }
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
        <h1>Tambah Buku</h1>
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1)) ?></div>
            <div>
                <div class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></div>
                <div><span class="user-role">Admin</span></div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="form-card">
            <div class="fc-header">
                <h2>➕ Tambah Buku Baru</h2>
                <p>Isi data katalog buku perpustakaan di bawah ini</p>
            </div>
            <div class="form-body">

                <?php if ($pesan): ?>
                <div class="alert <?= $pesan['tipe'] ?>">
                    <?= $pesan['tipe'] === 'error' ? '❌' : '✅' ?> <?= htmlspecialchars($pesan['teks']) ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Kode Buku <span>*</span></label>
                            <input type="text" name="kode_buku" placeholder="Contoh: BK006" value="<?= htmlspecialchars($_POST['kode_buku'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Kategori <span>*</span></label>
                            <select name="kategori" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php
                                $kategoris = ['Umum', 'Fiksi', 'Sains', 'Sejarah', 'Teknologi', 'Ekonomi', 'Pendidikan'];
                                $sel_kat = $_POST['kategori'] ?? '';
                                foreach($kategoris as $k) {
                                    $selected = ($sel_kat == $k) ? 'selected' : '';
                                    echo "<option value=\"$k\" $selected>$k</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Judul Buku <span>*</span></label>
                        <input type="text" name="judul" placeholder="Masukkan judul buku lengkap" value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>" required>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Pengarang <span>*</span></label>
                            <input type="text" name="pengarang" placeholder="Nama penulis/pengarang" value="<?= htmlspecialchars($_POST['pengarang'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Penerbit <span>*</span></label>
                            <input type="text" name="penerbit" placeholder="Penerbit buku" value="<?= htmlspecialchars($_POST['penerbit'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Tahun Terbit <span>*</span></label>
                            <input type="number" name="tahun_terbit" placeholder="YYYY" min="1900" max="<?= date('Y') ?>" value="<?= htmlspecialchars($_POST['tahun_terbit'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Stok Buku <span>*</span></label>
                            <input type="number" name="stok" placeholder="Jumlah fisik buku" min="0" value="<?= htmlspecialchars($_POST['stok'] ?? 1) ?>" required>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="kelola_buku.php" class="btn-batal">Batal</a>
                        <button type="submit" class="btn-simpan">💾 Simpan Buku</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
