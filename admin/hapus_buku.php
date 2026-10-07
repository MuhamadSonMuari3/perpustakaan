<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    header("Location: ../index.php"); exit;
}
include '../koneksi.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['pesan'] = 'ID buku tidak valid.';
    $_SESSION['tipe']  = 'error';
    header("Location: kelola_buku.php"); exit;
}

$buku = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT judul FROM buku WHERE id=$id"));

if (!$buku) {
    $_SESSION['pesan'] = 'Buku tidak ditemukan.';
    $_SESSION['tipe']  = 'error';
    header("Location: kelola_buku.php"); exit;
}

// Cek apakah buku sedang / pernah dipinjam. Jika iya, hindari hapus untuk menjaga integritas data.
$cek_pinjam = mysqli_query($koneksi, "SELECT id FROM peminjaman WHERE id_buku = $id");
if (mysqli_num_rows($cek_pinjam) > 0) {
    $_SESSION['pesan'] = "Buku \"{$buku['judul']}\" tidak bisa dihapus karena memiliki riwayat peminjaman.";
    $_SESSION['tipe']  = 'error';
    header("Location: kelola_buku.php"); exit;
}

if (mysqli_query($koneksi, "DELETE FROM buku WHERE id=$id")) {
    $_SESSION['pesan'] = "Buku \"{$buku['judul']}\" berhasil dihapus.";
    $_SESSION['tipe']  = 'success';
} else {
    $_SESSION['pesan'] = 'Gagal menghapus buku.';
    $_SESSION['tipe']  = 'error';
}

header("Location: kelola_buku.php");
exit;
?>
