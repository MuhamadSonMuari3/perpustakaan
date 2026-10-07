<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'anggota') {
    header("Location: ../index.php"); exit;
}
include '../koneksi.php';

$id_buku = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id_buku) {
    header("Location: cari_buku.php"); exit;
}

$username = $_SESSION['username'];

// Ambil ID Anggota berdasarkan username
$user_query = mysqli_query($koneksi, "SELECT id FROM users WHERE username = '$username'");
$user_data = mysqli_fetch_assoc($user_query);
$id_anggota = $user_data['id'];

// Cek apakah buku ada dan stok tersedia
$cek_buku = mysqli_query($koneksi, "SELECT judul, stok FROM buku WHERE id = $id_buku");
if (mysqli_num_rows($cek_buku) === 0) {
    $_SESSION['pesan'] = 'Buku tidak ditemukan.';
    $_SESSION['tipe']  = 'error';
    header("Location: cari_buku.php"); exit;
}
$buku = mysqli_fetch_assoc($cek_buku);

if ($buku['stok'] <= 0) {
    $_SESSION['pesan'] = "Maaf, stok buku \"{$buku['judul']}\" sedang habis.";
    $_SESSION['tipe']  = 'error';
    header("Location: cari_buku.php"); exit;
}

// Cek apakah anggota ini sedang meminjam buku yang sama dan belum mengembalikannya
$cek_pinjam = mysqli_query($koneksi, "SELECT id FROM peminjaman WHERE id_buku = $id_buku AND id_anggota = $id_anggota AND status = 'dipinjam'");
if (mysqli_num_rows($cek_pinjam) > 0) {
    $_SESSION['pesan'] = "Anda sedang meminjam buku \"{$buku['judul']}\" ini dan belum dikembalikan.";
    $_SESSION['tipe']  = 'error';
    header("Location: cari_buku.php"); exit;
}

// Lakukan proses peminjaman menggunakan Transaction
mysqli_begin_transaction($koneksi);
try {
    $kode_pinjam   = 'PJ' . time() . rand(10, 99);
    $tgl_pinjam    = date('Y-m-d');
    $tgl_kembali   = date('Y-m-d', strtotime('+7 days'));

    // Insert ke tabel peminjaman (id_petugas NULL karena mandiri)
    $q_pinjam = "INSERT INTO peminjaman (kode_pinjam, id_buku, id_anggota, id_petugas, tanggal_pinjam, tanggal_kembali, status) 
                 VALUES ('$kode_pinjam', $id_buku, $id_anggota, NULL, '$tgl_pinjam', '$tgl_kembali', 'dipinjam')";
    mysqli_query($koneksi, $q_pinjam);

    // Kurangi stok buku
    $q_stok = "UPDATE buku SET stok = stok - 1 WHERE id = $id_buku";
    mysqli_query($koneksi, $q_stok);

    mysqli_commit($koneksi);

    $_SESSION['pesan'] = "Berhasil meminjam buku \"{$buku['judul']}\". Harap kembalikan sebelum tanggal $tgl_kembali.";
    $_SESSION['tipe']  = 'success';

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    $_SESSION['pesan'] = "Terjadi kesalahan sistem saat memproses peminjaman.";
    $_SESSION['tipe']  = 'error';
}

header("Location: dashboard.php");
exit;
?>
