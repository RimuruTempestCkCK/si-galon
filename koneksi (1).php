<?php
// konfigurasi database
$host = "localhost";      // biasanya localhost
$user = "depz6154_depz6154_depot"; 
$pass = "al@azzahra969"; 
$db   = "depz6154_depot";  

// membuat koneksi
$conn = new mysqli($host, $user, $pass, $db);

// cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// echo "Koneksi berhasil"; // bisa diaktifkan untuk testing
?>
