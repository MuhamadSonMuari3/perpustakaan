<?php
session_start();

if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

$level = $_SESSION['level'] ?? '';

if ($level === 'admin') {
    header("Location: admin/dashboard.php");
} elseif ($level === 'petugas') {
    header("Location: petugas/dashboard.php");
} elseif ($level === 'anggota') {
    header("Location: anggota/dashboard.php");
} else {
    session_destroy();
    header("Location: index.php?error=role_tidak_dikenali");
}
exit;
?>