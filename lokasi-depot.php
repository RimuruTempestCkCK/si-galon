<?php
// Set cookie session agar kompatibel dengan Cordova WebView
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '.depot-al-azzahra.site', // pastikan sesuai domain
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);
session_start();
include 'koneksi.php'; // koneksi database

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Aplikasi - AquaDelivery</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
        }

        .container {
            max-width: 1000px;
            margin: 24px auto;
            padding: 18px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            margin-bottom: 18px;
        }

        h1,
        h2 {
            color: #222;
        }

        p {
            line-height: 1.6;
            color: #444;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 14px;
            margin-top: 12px;
        }

        .feature {
            background: linear-gradient(180deg, #fff, #fbfcff);
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #eef2ff;
        }

        .feature h3 {
            margin-bottom: 8px;
            font-size: 16px;
        }

        .cta-row {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn {
            padding: 10px 14px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 700;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-muted {
            background: #f0f4ff;
            color: #333;
        }

        .note {
            font-size: 13px;
            color: #666;
            margin-top: 6px;
        }

        pre {
            background: #0f1724;
            color: #d1fae5;
            padding: 12px;
            border-radius: 8px;
            overflow: auto;
            font-size: 13px;
        }

        @media(max-width:700px) {
            .cta-row {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="navbar">
        <button class="back-btn" onclick="window.location.href='dashboard.php'">←</button>
        <div>
            <strong>Tentang Aplikasi</strong>
            <div style="font-size:12px;opacity:0.9">Layanan antar galon dan depot air terdekat</div>
        </div>
    </div>


    <div class="container">
        <div class="card">
            <h1>Tentang Aplikasi</h1>
            <p>
                AquaDelivery adalah aplikasi pemesanan galon air yang memudahkan pengguna memesan, membayar, dan melacak pengiriman galon dari depot-depot terdekat.
                Aplikasi ini dirancang untuk integrasi dengan Cordova WebView sehingga dapat dijalankan sebagai aplikasi mobile hybrid.
            </p>
            <div class="cta-row">
                <button class="btn btn-primary" onclick="window.location.href='dashboard.php'">Kembali ke Dashboard</button>
                <button class="btn btn-muted" onclick="window.open('mailto:support@aquadelivery.example','_blank')">Hubungi Tim Support</button>
            </div>
            <p class="note">Versi aplikasi: <strong>1.0.0</strong> — terakhir diperbarui: <strong><?= date('d F Y') ?></strong></p>
        </div>


        <div class="card">
            <h2>Fitur Utama</h2>
            <div class="features">
                <div class="feature">
                    <h3>🛒 Pemesanan Mudah</h3>
                    <p>Pilih produk galon, tentukan jumlah, dan lanjutkan ke verifikasi pembayaran—semua lewat antarmuka yang sederhana.</p>
                </div>
                <div class="feature">
                    <h3>📍 Lokasi & Rute</h3>
                    <p>Deteksi lokasi pengguna dan tampilkan depot terdekat beserta jarak dan rute menuju depot.</p>
                </div>
                <div class="feature">
                    <h3>🔔 Status Pesanan</h3>
                    <p>Pelacakan status pesanan mulai dari menunggu pembayaran sampai pesanan selesai atau dibatalkan.</p>
                </div>
                <!-- <div class="feature">
<h3>🔒 Keamanan</h3>
<p>Session cookie diatur agar kompatibel dengan Cordova, menggunakan setting Secure dan HttpOnly ketika memungkinkan.</p>
</div> -->
            </div>
        </div>


        <div class="card">
            <h2>Alur Penggunaan (User Flow)</h2>
            <ol style="margin-top:10px;padding-left:18px;">
                <li>Login / Registrasi akun pengguna.</li>
                <li>Pilih produk galon dari daftar produk.</li>
                <li>Masukkan jumlah dan lokasi pengantaran (manual atau gunakan GPS).</li>
                <li>Lakukan verifikasi pembayaran melalui halaman konfirmasi.</li>
                <li>Admin atau sistem memproses pesanan hingga pengiriman dan selesai.</li>
            </ol>
            <p class="note">Catatan: aplikasi menggunakan tabel <code>orders</code> untuk menyimpan pesanan; pastikan kolom opsional seperti <code>address</code> diatur sesuai skema database Anda.</p>
        </div>

</body>

</html>