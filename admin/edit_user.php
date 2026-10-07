<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    header("Location: ../index.php"); exit;
}
include '../koneksi.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: kelola_user.php"); exit; }

$user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id=$id"));
if (!$user) {
    $_SESSION['pesan'] = 'User tidak ditemukan.';
    $_SESSION['tipe']  = 'error';
    header("Location: kelola_user.php"); exit;
}

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim(mysqli_real_escape_string($koneksi, $_POST['nama']));
    $username = trim(mysqli_real_escape_string($koneksi, $_POST['username']));
    $level    = $_POST['level'];
    $password = $_POST['password'];

    if (!$nama || !$username || !$level) {
        $pesan = ['tipe'=>'error','teks'=>'Nama, username, dan role wajib diisi.'];
    } elseif (!in_array($level, ['admin','petugas','anggota'])) {
        $pesan = ['tipe'=>'error','teks'=>'Role tidak valid.'];
    } else {
        // Cek username unik (kecuali user sendiri)
        $cek = mysqli_query($koneksi, "SELECT id FROM users WHERE username='$username' AND id != $id");
        if (mysqli_num_rows($cek) > 0) {
            $pesan = ['tipe'=>'error','teks'=>'Username sudah digunakan user lain.'];
        } else {
            if ($password && strlen($password) >= 6) {
                $hash  = password_hash($password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET nama='$nama', username='$username', password='$hash', level='$level' WHERE id=$id";
            } elseif ($password && strlen($password) < 6) {
                $pesan = ['tipe'=>'error','teks'=>'Password minimal 6 karakter.'];
                goto render;
            } else {
                // Password kosong = tidak diubah
                $query = "UPDATE users SET nama='$nama', username='$username', level='$level' WHERE id=$id";
            }
            if (mysqli_query($koneksi, $query)) {
                // Update session jika user mengedit dirinya sendiri
                if ($_SESSION['username'] === $user['username']) {
                    $_SESSION['nama']     = $nama;
                    $_SESSION['username'] = $username;
                }
                $_SESSION['pesan'] = "User \"$nama\" berhasil diperbarui.";
                $_SESSION['tipe']  = 'success';
                header("Location: kelola_user.php"); exit;
            } else {
                $pesan = ['tipe'=>'error','teks'=>'Gagal memperbarui data, coba lagi.'];
            }
        }
    }
}

render:
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin Perpustakaan</title>
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

        .form-card { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); width: 100%; max-width: 520px; overflow: hidden; }
        .form-card .fc-header { background: linear-gradient(135deg, #e65100, #f57c00); color: white; padding: 22px 28px; display: flex; align-items: center; gap: 15px; }
        .fc-header .user-avatar { width: 50px; height: 50px; border-radius: 50%; background: rgba(255,255,255,0.25); display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: bold; flex-shrink: 0; }
        .fc-header .fc-info h2 { font-size: 18px; margin-bottom: 3px; }
        .fc-header .fc-info p  { font-size: 13px; opacity: 0.85; }
        .form-body { padding: 28px; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert.error   { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .alert.success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 7px; font-weight: bold; font-size: 13px; color: #555; }
        .form-group label span { color: #dc3545; }
        .form-group input, .form-group select {
            width: 100%; padding: 11px 14px; border: 1px solid #ddd; border-radius: 8px;
            font-size: 14px; transition: border 0.2s; background: white;
        }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #e65100; box-shadow: 0 0 0 3px rgba(230,81,0,0.08); }
        .form-group .hint { font-size: 12px; color: #888; margin-top: 5px; }

        .role-options { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .role-option input[type="radio"] { display: none; }
        .role-option label { display: block; padding: 12px 8px; border: 2px solid #e0e0e0; border-radius: 10px; text-align: center; cursor: pointer; transition: all 0.2s; font-size: 13px; }
        .role-option label .ro-icon { font-size: 24px; display: block; margin-bottom: 5px; }
        .role-option input[type="radio"]:checked + label.admin   { border-color: #c62828; background: #ffebee; color: #c62828; }
        .role-option input[type="radio"]:checked + label.petugas { border-color: #2e7d32; background: #e8f5e9; color: #2e7d32; }
        .role-option input[type="radio"]:checked + label.anggota { border-color: #1565c0; background: #e3f2fd; color: #1565c0; }

        .password-wrap { position: relative; }
        .password-wrap input { padding-right: 42px; }
        .toggle-pw { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 17px; background: none; border: none; color: #777; }

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
        <a href="kelola_user.php" class="active"><span class="icon">👥</span> Kelola User</a>
        <a href="#"><span class="icon">📖</span> Kelola Buku</a>
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
        <h1>Edit User</h1>
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
                <div class="user-avatar"><?= strtoupper(substr($user['nama'], 0, 1)) ?></div>
                <div class="fc-info">
                    <h2>✏️ Edit User</h2>
                    <p>Mengedit: <?= htmlspecialchars($user['nama']) ?> (@<?= htmlspecialchars($user['username']) ?>)</p>
                </div>
            </div>
            <div class="form-body">

                <?php if ($pesan): ?>
                <div class="alert <?= $pesan['tipe'] ?>">
                    <?= $pesan['tipe'] === 'error' ? '❌' : '✅' ?> <?= htmlspecialchars($pesan['teks']) ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Nama Lengkap <span>*</span></label>
                        <input type="text" name="nama" value="<?= htmlspecialchars($_POST['nama'] ?? $user['nama']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Username <span>*</span></label>
                        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? $user['username']) ?>" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Password Baru</label>
                        <div class="password-wrap">
                            <input type="password" name="password" id="pwInput" placeholder="Kosongkan jika tidak ingin mengubah password" autocomplete="new-password">
                            <button type="button" class="toggle-pw" onclick="togglePw()">👁️</button>
                        </div>
                        <div class="hint">Kosongkan jika tidak ingin mengubah password. Minimal 6 karakter jika diisi.</div>
                    </div>
                    <div class="form-group">
                        <label>Role / Level <span>*</span></label>
                        <div class="role-options">
                            <?php $cur_level = $_POST['level'] ?? $user['level']; ?>
                            <div class="role-option">
                                <input type="radio" name="level" id="r_admin" value="admin" <?= $cur_level==='admin' ? 'checked' : '' ?>>
                                <label for="r_admin" class="admin"><span class="ro-icon">👑</span>Admin</label>
                            </div>
                            <div class="role-option">
                                <input type="radio" name="level" id="r_petugas" value="petugas" <?= $cur_level==='petugas' ? 'checked' : '' ?>>
                                <label for="r_petugas" class="petugas"><span class="ro-icon">👨‍💼</span>Petugas</label>
                            </div>
                            <div class="role-option">
                                <input type="radio" name="level" id="r_anggota" value="anggota" <?= $cur_level==='anggota' ? 'checked' : '' ?>>
                                <label for="r_anggota" class="anggota"><span class="ro-icon">👤</span>Anggota</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <a href="kelola_user.php" class="btn-batal">Batal</a>
                        <button type="submit" class="btn-simpan">💾 Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePw() {
    const i = document.getElementById('pwInput');
    i.type = i.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
