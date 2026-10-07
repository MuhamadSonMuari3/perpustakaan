<?php
session_start();
include 'koneksi.php';
$username = $_POST['username'];
$password = $_POST['password'];
$sql = "SELECT * FROM users WHERE username = '$username'";
$result = mysqli_query($koneksi, $sql);
if (mysqli_num_rows($result) == 1  ) {
    $row = mysqli_fetch_assoc($result);
    if (password_verify($password, $row['password'])) {
        $_SESSION['login'] = true;
        $_SESSION['nama'] = $row['nama'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['level'] = $row['level'];
        header("Location: dashboard.php");
    } else {
        echo "Password salah";
    }
} else {
    echo "Username tidak ditemukan";
}
?>