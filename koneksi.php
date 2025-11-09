<?php
// konfigurasi database
$host = "localhost";      // biasanya localhost
$user = "root";           // username database
$pass = "";               // password database, biasanya kosong di XAMPP
$db   = "depot";          // nama database

// membuat koneksi
$conn = new mysqli($host, $user, $pass, $db);

// cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// echo "Koneksi berhasil"; // bisa diaktifkan untuk testing
?>
