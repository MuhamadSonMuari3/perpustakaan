<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    header("Location: ../index.php"); exit;
}
include '../koneksi.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['pesan'] = 'ID user tidak valid.';
    $_SESSION['tipe']  = 'error';
    header("Location: kelola_user.php"); exit;
}

// Cegah admin menghapus dirinya sendiri
$user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id=$id"));

if (!$user) {
    $_SESSION['pesan'] = 'User tidak ditemukan.';
    $_SESSION['tipe']  = 'error';
    header("Location: kelola_user.php"); exit;
}

if ($user['username'] === $_SESSION['username']) {
    $_SESSION['pesan'] = 'Anda tidak dapat menghapus akun sendiri.';
    $_SESSION['tipe']  = 'error';
    header("Location: kelola_user.php"); exit;
}

if (mysqli_query($koneksi, "DELETE FROM users WHERE id=$id")) {
    $_SESSION['pesan'] = "User \"" . $user['nama'] . "\" berhasil dihapus.";
    $_SESSION['tipe']  = 'success';
} else {
    $_SESSION['pesan'] = 'Gagal menghapus user.';
    $_SESSION['tipe']  = 'error';
}

header("Location: kelola_user.php");
exit;
?>
