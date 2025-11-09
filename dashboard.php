<?php
// Set cookie session agar kompatibel dengan Cordova WebView
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '.depot-al-azzahra.site',
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

// Ambil data depot (ambil 1 depot saja)
$depot_query = "SELECT * FROM depot LIMIT 1";
$result = $conn->query($depot_query);

if (!$result) {
    die("Query gagal: " . $conn->error);
}

$depot = $result->fetch_assoc();

// Ambil data galon dari database
$galon_query = "SELECT * FROM galon";
$galon_result = $conn->query($galon_query);

if (!$galon_result) {
    die("Query galon gagal: " . $conn->error);
}

$galons = [];
while ($row = $galon_result->fetch_assoc()) {
    $galons[] = $row;
}





$user_id = $_SESSION['user_id'];

// Total pesanan
$sqlTotal = "SELECT COUNT(*) AS total FROM orders WHERE user_id = ?";
$stmt = $conn->prepare($sqlTotal);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$totalOrders = $result->fetch_assoc()['total'];

// Pesanan aktif (status selain 'selesai' atau 'dibatalkan')
$sqlActive = "SELECT COUNT(*) AS active FROM orders WHERE user_id = ? AND status NOT IN ('selesai', 'dibatalkan')";
$stmt = $conn->prepare($sqlActive);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$activeOrders = $result->fetch_assoc()['active'];

// Pesanan selesai
$sqlCompleted = "SELECT COUNT(*) AS completed FROM orders WHERE user_id = ? AND status = 'selesai'";
$stmt = $conn->prepare($sqlCompleted);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$completedOrders = $result->fetch_assoc()['completed'];

?>



<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AquaDelivery</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar h1 {
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid white;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: white;
            color: #667eea;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .welcome-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .welcome-card h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }

        .welcome-card p {
            color: #666;
            font-size: 14px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .menu-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-decoration: none;
            color: inherit;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .menu-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }

        .menu-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 20px;
        }

        .menu-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .stat-card h4 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .stat-card .value {
            color: #667eea;
            font-size: 28px;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .navbar h1 {
                font-size: 18px;
            }

            .user-info {
                gap: 10px;
            }

            .user-avatar {
                width: 35px;
                height: 35px;
                font-size: 16px;
            }

            .logout-btn {
                padding: 6px 12px;
                font-size: 12px;
            }

            .menu-grid {
                grid-template-columns: 1fr;
            }

            .welcome-card h2 {
                font-size: 20px;
            }

            .menu-icon {
                width: 70px;
                height: 70px;
                font-size: 35px;
            }
        }

        /* ===== Card Produk Galon ===== */
        .menu-grid.produk-galon {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            /* lebih banyak card per baris */
            gap: 15px;
        }

        .menu-grid.produk-galon .menu-card {
            padding: 15px;
            border-radius: 12px;
        }

        .menu-grid.produk-galon .menu-card img {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            margin-bottom: 8px;
        }

        .menu-grid.produk-galon .menu-card h3 {
            font-size: 16px;
            margin-bottom: 6px;
        }

        .menu-grid.produk-galon .menu-card p {
            font-size: 12px;
            line-height: 1.3;
        }

        .menu-grid.produk-galon .menu-card a {
            display: inline-block;
            margin-top: 6px;
            padding: 6px 10px;
            font-size: 12px;
            background: #667eea;
            color: white;
            border-radius: 6px;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="navbar">
        <h1>💧 <?= htmlspecialchars($depot['nama_depot'] ?? 'Nama Depot') ?></h1>
        <div class="user-info">
            <div class="user-avatar">👤</div>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Keluar</button>
        </div>
    </div>


    <div class="container">
        <div class="welcome-card">
            <h2>Selamat Datang, <span id="userName"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>! 👋</h2>
            <p>Silakan pilih menu di bawah untuk melakukan pemesanan atau melihat riwayat pesanan Anda.</p>
        </div>

        <div class="menu-grid">

            <a href="riwayat-pesanan.php" class="menu-card">
                <div class="menu-icon">📋</div>
                <h3>Riwayat Pesanan</h3>
                <p>Lihat semua pesanan Anda, status pengiriman, dan detail transaksi.</p>
            </a>

            <a href="lokasi-depot.php" class="menu-card">
                <div class="menu-icon">📍</div>
                <h3>Tentang Aplikasi</h3>
                <p>
                    Aplikasi pemesanan galon air yang memudahkan pengguna.
                </p>
            </a>
        </div>
        <!-- Section Daftar Galon -->
        <h2>Daftar Galon Tersedia</h2>
        <br>
        <div class="menu-grid produk-galon">
            <?php foreach ($galons as $galon): ?>
                <?php
                // Pastikan nama kolom sesuai dengan DB: id, nama, deskripsi, harga, foto, stok
                $id        = isset($galon['id']) ? (int)$galon['id'] : 0;
                $nama      = isset($galon['nama']) ? (string)$galon['nama'] : 'Tidak ada nama';
                $deskripsi = isset($galon['deskripsi']) ? (string)$galon['deskripsi'] : '-';
                $harga     = isset($galon['harga']) ? (int)$galon['harga'] : 0;
                $stok      = isset($galon['stok']) ? (int)$galon['stok'] : 0;
                $foto      = isset($galon['foto']) ? (string)$galon['foto'] : '';

                // Path folder upload fisik di server (sesuaikan jika berbeda)
                $uploads_dir = __DIR__ . '/uploads/galon/';   // -> C:\laragon\www\si_galon\uploads\galon\
                $file_path = $uploads_dir . $foto;

                // Jika file ada gunakan file, kalau tidak pakai default.png (letakkan default di uploads/galon/default.png)
                if ($foto !== '' && file_exists($file_path)) {
                    // rawurlencode agar spasi/tanda kurung aman untuk URL
                    $foto_url = 'uploads/galon/' . rawurlencode($foto);
                } else {
                    $foto_url = 'uploads/galon/default.png';
                }
                ?>
                <div class="menu-card">
                    <img src="<?= htmlspecialchars($foto_url) ?>"
                        alt="<?= htmlspecialchars($nama) ?>"
                        style="width:100px; height:auto; border-radius:10px; margin-bottom:10px;">
                    <h3><?= htmlspecialchars($nama) ?></h3>
                    <p>
                        <?= nl2br(htmlspecialchars($deskripsi)) ?><br>
                        Harga: Rp <?= number_format($harga, 0, ',', '.') ?><br>
                        Stok: <?= htmlspecialchars((string)$stok) ?>
                    </p>
                    <a href="pesan-galon.php?id=<?= $id ?>"
                        style="display:inline-block; margin-top:10px; padding:8px 12px; background:#667eea; color:white; border-radius:8px; text-decoration:none;">
                        Pesan Sekarang
                    </a>
                </div>
            <?php endforeach; ?>


        </div>


        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Pesanan</h4>
                <div class="value" id="totalOrders"><?= $totalOrders ?></div>
            </div>
            <div class="stat-card">
                <h4>Pesanan Aktif</h4>
                <div class="value" id="activeOrders"><?= $activeOrders ?></div>
            </div>
            <div class="stat-card">
                <h4>Pesanan Selesai</h4>
                <div class="value" id="completedOrders"><?= $completedOrders ?></div>
            </div>
        </div>

    </div>


</body>

</html>