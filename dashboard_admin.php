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
include 'koneksi.php';

// Tampilkan error untuk debugging sementara
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Ambil data admin
$admin_id = $_SESSION['admin_id'];
$query_admin = "SELECT * FROM admins WHERE id = ?";
$stmt = $conn->prepare($query_admin);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Ambil data depot
$query_depot = "SELECT * FROM depot LIMIT 1";
$depot = $conn->query($query_depot)->fetch_assoc();

// Proses update depot
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_depot'])) {
    $nama_depot = $_POST['nama_depot'];
    $alamat_depot = $_POST['alamat_depot'];

    $query_depot = "SELECT * FROM depot LIMIT 1";
    $depot = $conn->query($query_depot)->fetch_assoc();

    if ($depot) {
        $stmt = $conn->prepare("UPDATE depot SET nama_depot=?, alamat=? WHERE id=?");
        $stmt->bind_param("ssi", $nama_depot, $alamat_depot, $depot['id']);
    } else {
        $stmt = $conn->prepare("INSERT INTO depot (nama_depot, alamat) VALUES (?, ?)");
        $stmt->bind_param("ss", $nama_depot, $alamat_depot);
    }

    if (!$stmt->execute()) {
        die("Error update depot: " . $stmt->error);
    }

    header('Location: dashboard_admin.php?success=depot');
    exit;
}



// Proses update password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    
    if (password_verify($old_password, $admin['password'])) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update = "UPDATE admins SET password = ? WHERE id = ?"; // perbaiki nama tabel
        $stmt = $conn->prepare($update);
        $stmt->bind_param("si", $hashed, $admin_id);

        if (!$stmt->execute()) {
            die("Error update password: " . $stmt->error);
        }

        header('Location: dashboard_admin.php?success=password');
        exit;
    } else {
        $error = "Password lama salah!";
    }
}

// Hapus user
if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    $conn->query("DELETE FROM users WHERE id = $user_id");
    header('Location: dashboard_admin.php?success=delete_user');
    exit;
}

// Hapus pesanan
if (isset($_GET['delete_order'])) {
    $order_id = $_GET['delete_order'];
    $conn->query("DELETE FROM orders WHERE id = $order_id");
    header('Location: dashboard_admin.php?success=delete_order');
    exit;
}

// CRUD Metode Pembayaran
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_payment'])) {
    $nama = $_POST['nama_payment'];
    $nomor = $_POST['nomor_payment'] ?? '';  // Kosongkan jika COD
    $atas_nama = $_POST['atas_nama'] ?? '';  // Kosongkan jika COD
    $tipe = $_POST['tipe_payment'];  // Baru: transfer atau cod
    
    $insert = "INSERT INTO metode_pembayaran (nama_metode, nomor_rekening, atas_nama, tipe) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert);
    $stmt->bind_param("ssss", $nama, $nomor, $atas_nama, $tipe);
    
    if (!$stmt->execute()) {
        die("Error tambah metode pembayaran: " . $stmt->error);
    }
    
    header('Location: dashboard_admin.php?success=add_payment');
    exit;
}

if (isset($_GET['delete_payment'])) {
    $payment_id = $_GET['delete_payment'];
    $conn->query("DELETE FROM metode_pembayaran WHERE id = $payment_id");
    header('Location: dashboard_admin.php?success=delete_payment');
    exit;
}

// Ambil data statistik
$stats = [];
$stats['total_orders'] = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$stats['pending'] = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status='menunggu_verifikasi'")->fetch_assoc()['c'];
$stats['active'] = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status IN ('diproses','sedang_dikirim')")->fetch_assoc()['c'];
$stats['completed'] = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status='selesai'")->fetch_assoc()['c'];
$stats['revenue'] = $conn->query("SELECT SUM(total) as t FROM orders WHERE status='selesai'")->fetch_assoc()['t'] ?? 0;
$orders = $conn->query("
    SELECT o.*, m.nama_metode 
    FROM orders o
    LEFT JOIN metode_pembayaran m ON o.payment_method_id = m.id
    ORDER BY o.created_at DESC
");


// Ambil data users
$users = $conn->query("SELECT * FROM users ORDER BY id DESC");

// Ambil data metode pembayaran
$payments = $conn->query("SELECT * FROM metode_pembayaran ORDER BY id");


// Verifikasi pesanan
if (isset($_GET['verify_order'])) {
    $order_id = intval($_GET['verify_order']);
    $stmt = $conn->prepare("UPDATE orders SET status='diproses' WHERE id=? AND status='menunggu_verifikasi'");
    $stmt->bind_param("i", $order_id);
    
    if ($stmt->execute()) {
        header('Location: dashboard_admin.php?success=verify_order');
        exit;
    } else {
        die("Gagal verifikasi: " . $stmt->error);
    }
}
// Proses kirim pesanan
if (isset($_GET['ship_order'])) {
    $order_id = intval($_GET['ship_order']);
    $stmt = $conn->prepare("UPDATE orders SET status='sedang_dikirim' WHERE id=? AND status='diproses'");
    $stmt->bind_param("i", $order_id);
    if ($stmt->execute()) {
        header('Location: dashboard_admin.php?success=ship_order');
        exit;
    }
}

// Proses selesaikan pesanan
if (isset($_GET['complete_order'])) {
    $order_id = intval($_GET['complete_order']);
    $stmt = $conn->prepare("UPDATE orders SET status='selesai' WHERE id=? AND status='sedang_dikirim'");
    $stmt->bind_param("i", $order_id);
    if ($stmt->execute()) {
        header('Location: dashboard_admin.php?success=complete_order');
        exit;
    }
}
// Tambah galon
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_galon'])) {
    $nama = $_POST['nama_galon'];
    $deskripsi = $_POST['deskripsi'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'] ?? 0;
    
    // Upload foto
    $foto = '';
    if (isset($_FILES['foto']['name']) && $_FILES['foto']['name'] != '') {
        $target_dir = "uploads/galon/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $foto = time() . "_" . basename($_FILES["foto"]["name"]);
        move_uploaded_file($_FILES["foto"]["tmp_name"], $target_dir . $foto);
    }

    $stmt = $conn->prepare("INSERT INTO galon (nama, deskripsi, harga, stok, foto) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiss", $nama, $deskripsi, $harga, $stok, $foto);
    
    if ($stmt->execute()) {
        header('Location: dashboard_admin.php?success=add_galon');
        exit;
    } else {
        $error = "Gagal tambah galon: " . $stmt->error;
    }
}

// Hapus galon
if (isset($_GET['delete_galon'])) {
    $galon_id = intval($_GET['delete_galon']);
    $conn->query("DELETE FROM galon WHERE id=$galon_id");
    header('Location: dashboard_admin.php?success=delete_galon');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_galon'])) {
    $id = $_POST['id_galon'];
    $nama = $_POST['nama_galon'];
    $deskripsi = $_POST['deskripsi'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];

    // Jika ada foto baru
    if (isset($_FILES['foto']['name']) && $_FILES['foto']['name'] != '') {
        $target_dir = "uploads/galon/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $foto = time() . "_" . basename($_FILES["foto"]["name"]);
        move_uploaded_file($_FILES["foto"]["tmp_name"], $target_dir . $foto);

        $stmt = $conn->prepare("UPDATE galon SET nama=?, deskripsi=?, harga=?, stok=?, foto=? WHERE id=?");

        // pastikan tipe data sesuai dan variabel
        $harga = (int)$harga;
        $stok = (int)$stok;
        $id = (int)$id;

        $stmt->bind_param("ssissi", $nama, $deskripsi, $harga, $stok, $foto, $id);
    } else {
        $harga = (int)$harga;
        $stok = (int)$stok;
        $id = (int)$id;

        $stmt = $conn->prepare("UPDATE galon SET nama=?, deskripsi=?, harga=?, stok=? WHERE id=?");
        $stmt->bind_param("ssiii", $nama, $deskripsi, $harga, $stok, $id);
    }


    if ($stmt->execute()) {
        header('Location: dashboard_admin.php?success=update_galon');
        exit;
    } else {
        $error = "Gagal update galon: " . $stmt->error;
    }
}




$galons = $conn->query("SELECT * FROM galon ORDER BY id DESC");
$editGalon = null;
if (isset($_GET['edit_galon'])) {
    $id = intval($_GET['edit_galon']);
    $editGalon = $conn->query("SELECT * FROM galon WHERE id=$id")->fetch_assoc();
}
require __DIR__ . '/vendor/autoload.php';
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AquaDelivery</title>
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
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .navbar h1 {
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-badge {
            background: #ffc107;
            color: #333;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 700;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-avatar {
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
            background: rgba(255,255,255,0.2);
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
            color: #1e3c72;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.blue {
            background: #e3f2fd;
        }

        .stat-icon.green {
            background: #e8f5e9;
        }

        .stat-icon.orange {
            background: #fff3e0;
        }

        .stat-icon.red {
            background: #ffebee;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
        }

        .section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .filter-tab {
            padding: 10px 20px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
            color: #666;
        }

        .filter-tab.active {
            background: #2a5298;
            color: white;
            border-color: #2a5298;
        }

        .orders-table {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #666;
            border-bottom: 2px solid #e0e0e0;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }

        .status-menunggu_pembayaran {
            background: #fff3cd;
            color: #856404;
        }

        .status-menunggu_verifikasi {
            background: #cfe2ff;
            color: #084298;
        }

        .status-diproses {
            background: #e7f3ff;
            color: #0066cc;
        }

        .status-dikirim {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-selesai {
            background: #d4edda;
            color: #155724;
        }

        .status-dibatalkan {
            background: #f8d7da;
            color: #721c24;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-right: 5px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-verify {
            background: #28a745;
            color: white;
        }

        .btn-verify:hover {
            background: #218838;
        }

        .btn-process {
            background: #007bff;
            color: white;
        }

        .btn-process:hover {
            background: #0056b3;
        }

        .btn-ship {
            background: #17a2b8;
            color: white;
        }

        .btn-ship:hover {
            background: #138496;
        }

        .btn-complete {
            background: #28a745;
            color: white;
        }

        .btn-complete:hover {
            background: #218838;
        }

        .btn-reject, .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-reject:hover, .btn-delete:hover {
            background: #c82333;
        }

        .btn-view, .btn-edit {
            background: #6c757d;
            color: white;
        }

        .btn-view:hover, .btn-edit:hover {
            background: #5a6268;
        }

        .btn-add {
            background: #28a745;
            color: white;
        }

        .btn-add:hover {
            background: #218838;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #2a5298;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .tab-menu {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            overflow-x: auto; /* agar scroll di mobile */
            -webkit-overflow-scrolling: touch;
        }

        .tab-item {
            flex: 0 0 auto; /* agar tombol tidak mengecil */
            padding: 12px 24px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }


        .tab-item.active {
            color: #2a5298;
            border-bottom-color: #2a5298;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        @media (max-width: 768px) {
            .navbar h1 {
                font-size: 16px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .section {
                padding: 20px;
            }

            .orders-table {
                overflow-x: scroll;
            }

            table {
                min-width: 800px;
            }

            .filter-tabs {
                flex-direction: column;
            }

            .filter-tab {
                width: 100%;
            }
        }

        /* global box-sizing untuk konsistensi */
        * { box-sizing: border-box; }

        /* dasar form control agar input + select tampil seragam */
        .form-control {
        width: 100%;
        padding: 10px 12px;
        height: 44px;                 /* samakan tinggi */
        font-size: 14px;
        line-height: 1.4;
        color: #333;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
        display: inline-block;
        vertical-align: middle;
        }

        /* Focus state (saw nicer UX) */
        .form-control:focus {
        border-color: #7aa7ff;
        box-shadow: 0 0 0 3px rgba(122,167,255,0.12);
        }

        /* special: make select look consistent across browsers */
        .form-control[type="select"], .form-control.select, select.form-control {
        -webkit-appearance: none;     /* chrome/safari */
        -moz-appearance: none;        /* firefox */
        appearance: none;
        background-image: linear-gradient(45deg, transparent 50%, #666 50%),
                            linear-gradient(135deg, #666 50%, transparent 50%),
                            linear-gradient(to right, #fff, #fff);
        background-position: calc(100% - 18px) calc(1em + 2px),
                            calc(100% - 13px) calc(1em + 2px),
                            100% 0;
        background-size: 6px 6px, 6px 6px, 2.5em 2.5em;
        background-repeat: no-repeat;
        padding-right: 38px;          /* ruang untuk panah (custom) */
        }

        /* optional: jika ingin panah kecil di sebelah kanan, sesuaikan */
        select.form-control::-ms-expand { display: none; } /* hide default on IE/Edge */

        /* kecilkan jarak label -> field */
        .form-group { margin-bottom: 12px; }
        .form-group label { display:block; margin-bottom:6px; font-weight:600; color:#333; }

        /* tombol */
        .action-btn { padding: 8px 14px; border-radius: 8px; cursor: pointer; border: none; }
        .btn-add { background: #1e88e5; color: #fff; }
        .btn-delete { background: #f44336; color: #fff; margin-left:8px; }
        



           .status-sedang_dikirim {  /* Ubah dari .status-dikirim */
                background: #d1ecf1;
                color: #0c5460;
            }
        
            

        /* Styling untuk iframe peta di tabel */
        .map-iframe {
            width: 150px;
            height: 150px;
            border: 1px solid #ddd;
            border-radius: 8px;
            display: block;
            margin: 0 auto;
        }
        .map-link {
            display: block;
            text-align: center;
            margin-top: 5px;
            font-size: 12px;
            color: #007bff;
            text-decoration: none;
        }
        .map-link:hover {
            text-decoration: underline;
        }
        /* Responsif untuk mobile */
        @media (max-width: 768px) {
            .map-iframe {
                width: 150px;
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-left">
            <h1>👨‍💼 Admin Panel</h1>
            <span class="admin-badge">ADMINISTRATOR</span>
        </div>
        <div class="navbar-right">
            <div class="admin-avatar">👤</div>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Keluar</button>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                ✅ 
                <?php 
                    $messages = [
                        'depot' => 'Data depot berhasil diperbarui!',
                        'password' => 'Password berhasil diubah!',
                        'delete_user' => 'User berhasil dihapus!',
                        'delete_order' => 'Pesanan berhasil dihapus!',
                        'add_payment' => 'Metode pembayaran berhasil ditambahkan!',
                        'delete_payment' => 'Metode pembayaran berhasil dihapus!',
                        'verify_order' => 'Pesanan berhasil diverifikasi!',
                        'ship_order' => 'Pesanan berhasil dikirim!',
                        'complete_order' => 'Pesanan selesai!'
                    ];
                    echo $messages[$_GET['success']] ?? 'Berhasil!';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">❌ <?= $error ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['total_orders'] ?></div>
                        <div class="stat-label">Total Pesanan</div>
                    </div>
                    <div class="stat-icon blue">📦</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['pending'] ?></div>
                        <div class="stat-label">Perlu Verifikasi</div>
                    </div>
                    <div class="stat-icon orange">⏳</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['active'] ?></div>
                        <div class="stat-label">Pesanan Aktif</div>
                    </div>
                    <div class="stat-icon green">🚚</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['completed'] ?></div>
                        <div class="stat-label">Selesai</div>
                    </div>
                    <div class="stat-icon green">✅</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value">Rp <?= number_format($stats['revenue'], 0, ',', '.') ?></div>
                        <div class="stat-label">Total Pendapatan</div>
                    </div>
                    <div class="stat-icon blue">💰</div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="tab-menu">
                <button class="tab-item active" onclick="switchTab('orders')">📦 Pesanan</button>
                <button class="tab-item" onclick="switchTab('users')">👥 Data User</button>
                <button class="tab-item" onclick="switchTab('galon')">🍼 Produk Galon</button>
                <button class="tab-item" onclick="switchTab('payments')">💳 Metode Pembayaran</button>
                <button class="tab-item" onclick="switchTab('laporan')">📊 Laporan</button>
                <button class="tab-item" onclick="switchTab('settings')">⚙️ Pengaturan</button>
            </div>

            <!-- Tab Pesanan -->
            <div id="tab-orders" class="tab-content active">
                <div class="section-header">
                    <h2 class="section-title">Kelola Pesanan</h2>
                </div>

                   <div class="filter-tabs">
                        <button class="filter-tab active" onclick="filterOrders('semua')">Semua</button>
                        <button class="filter-tab" onclick="filterOrders('menunggu_pembayaran')">Menunggu Pembayaran</button>
                        <button class="filter-tab" onclick="filterOrders('menunggu_verifikasi')">Perlu Verifikasi</button>
                        <button class="filter-tab" onclick="filterOrders('diproses')">Diproses</button>
                        <button class="filter-tab" onclick="filterOrders('sedang_dikirim')">Dikirim</button>  <!-- Ubah dari 'dikirim' -->
                        <button class="filter-tab" onclick="filterOrders('selesai')">Selesai</button>
                    </div>
   


                <div class="orders-table">
                    <table id="ordersTable">
                        <thead>
                            <tr>
                                <th>Order Code</th>
                                <th>Quantity</th>
                                <th>Recipient Name</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <!-- <th>Latitude</th>
                                <th>Longitude</th> -->
                                <th>Peta Lokasi</th>
                                <th>Notes</th>
                                <th>Total</th>
                                <th>Payment Method</th>
                                <th>Payment Proof</th>
                                <th>Status</th>                              
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="ordersBody">
                            <?php
                            $orders = $conn->query("
                                SELECT o.*, m.nama_metode 
                                FROM orders o
                                LEFT JOIN metode_pembayaran m ON o.payment_method_id = m.id
                                ORDER BY o.created_at DESC
                            ");

                            while ($order = $orders->fetch_assoc()):
                            ?>
                            <tr data-status="<?= $order['status'] ?>">
                                <td><?= htmlspecialchars($order['order_code']) ?></td>
                                <td><?= $order['quantity'] ?></td>
                                <td><?= htmlspecialchars($order['recipient_name']) ?></td>
                                <td><?= htmlspecialchars($order['phone']) ?></td>
                                <td><?= htmlspecialchars($order['address']) ?></td>
                                <!-- <td><?= $order['latitude'] ?></td>
                                <td><?= $order['longitude'] ?></td> -->
                                <td>
                                    <?php if (!empty($order['latitude']) && !empty($order['longitude']) && is_numeric($order['latitude']) && is_numeric($order['longitude'])): ?>
                                        <iframe 
                                            src="https://maps.google.com/maps?q=<?= $order['latitude'] ?>,<?= $order['longitude'] ?>&output=embed&z=15" 
                                            class="map-iframe" 
                                            allowfullscreen="" 
                                            loading="lazy">
                                        </iframe>
                                        <a href="https://www.google.com/maps?q=<?= $order['latitude'] ?>,<?= $order['longitude'] ?>" 
                                        target="_blank" 
                                        class="map-link">
                                            Buka di Google Maps
                                        </a>
                                    <?php else: ?>
                                        Lokasi tidak tersedia
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($order['notes']) ?></td>
                                <td>Rp <?= number_format($order['total'], 0, ',', '.') ?></td>
                                <td><?= htmlspecialchars($order['nama_metode'] ?? 'Metode tidak diketahui') ?></td>
                                <td>
                                    <?php if (!empty($order['payment_proof'])): ?>
                                        <a href="uploads/<?= htmlspecialchars($order['payment_proof']) ?>" target="_blank">
                                            <img src="uploads/<?= htmlspecialchars($order['payment_proof']) ?>" alt="Bukti Pembayaran" width="80">
                                        </a>
                                    <?php else: ?>
                                        Pembayaran dengan COD
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="status-badge status-<?= $order['status'] ?>">
                                        <?= ucwords(str_replace('_', ' ', $order['status'])) ?>
                                    </span>
                                </td>
                                
                                   <td>
                                        <?php if ($order['status'] == 'menunggu_verifikasi'): ?>
                                            <a href="?verify_order=<?= $order['id'] ?>" class="action-btn btn-verify" onclick="return confirm('Verifikasi pesanan ini?')">✅ Verifikasi</a>
                                        <?php elseif ($order['status'] == 'diproses'): ?>
                                            <a href="?ship_order=<?= $order['id'] ?>" class="action-btn btn-ship" onclick="return confirm('Kirim pesanan ini?')">🚚 Kirim</a>
                                        <?php elseif ($order['status'] == 'sedang_dikirim'): ?>  <!-- Ubah dari 'dikirim' -->
                                            <a href="?complete_order=<?= $order['id'] ?>" class="action-btn btn-complete" onclick="return confirm('Tandai pesanan selesai?')">✅ Selesai</a>
                                        <?php endif; ?>
                                        <a href="?delete_order=<?= $order['id'] ?>" class="action-btn btn-delete" onclick="return confirm('Yakin hapus pesanan ini?')">🗑️ Hapus</a>
                                    </td>
   
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- Tab Users -->
            <div id="tab-users" class="tab-content">
                <div class="section-header">
                    <h2 class="section-title">Data User</h2>
                </div>
                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <!-- <th>ID</th> -->
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Alamat</th>  <!-- Kolom baru -->
                                <th>Tanggal Daftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users->fetch_assoc()): ?>
                            <tr>
                                <!-- <td><?= $user['id'] ?></td> -->
                                <td><?= htmlspecialchars($user['name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($user['address'] ?? '-') ?></td>  <!-- Sel baru untuk alamat -->
                                <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <a href="?delete_user=<?= $user['id'] ?>" class="action-btn btn-delete"
                                       onclick="return confirm('Yakin hapus user ini?')">🗑️ Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Metode Pembayaran -->
            <div id="tab-payments" class="tab-content">
                <div class="section-header">
                    <h2 class="section-title">Metode Pembayaran</h2>
                    <button class="action-btn btn-add" onclick="document.getElementById('formAddPayment').style.display='block'">
                        ➕ Tambah Metode
                    </button>
                </div>

                <div id="formAddPayment" style="display:none; margin-bottom: 20px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="margin-bottom: 15px;">Tambah Metode Pembayaran</h3>
                    <form method="POST">
                        <div class="form-group">
                        <label>Tipe Pembayaran</label>
                        <select name="tipe_payment" id="tipe_payment" class="form-control" required onchange="toggleFields()">
                            <option value="transfer">Transfer Bank/E-Wallet</option>
                            <option value="cod">COD (Cash on Delivery)</option>
                        </select>
                        </div>

                        <div class="form-group">
                        <label>Nama Bank/E-Wallet</label>
                        <input type="text" name="nama_payment" id="nama_payment" class="form-control" required>
                        </div>

                        <div class="form-group" id="nomor_group">
                        <label>Nomor Rekening/HP</label>
                        <input type="text" name="nomor_payment" id="nomor_payment" class="form-control">
                        </div>

                        <div class="form-group" id="atas_nama_group">
                        <label>Atas Nama</label>
                        <input type="text" name="atas_nama" id="atas_nama" class="form-control">
                        </div>

                        <button type="submit" name="add_payment" class="action-btn btn-add">Simpan</button>
                        <button type="button" class="action-btn btn-delete" onclick="document.getElementById('formAddPayment').style.display='none'">Batal</button>
                    </form>
                </div>


                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Tipe</th>
                                <th>Nama</th>
                                <th>Nomor</th>
                                <th>Atas Nama</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($payment = $payments->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($payment['tipe'] ?? 'transfer') ?></td>  <!-- Jika ada kolom tipe -->
                                <td><?= htmlspecialchars($payment['nama_metode']) ?></td>
                                <td><?= $payment['tipe'] === 'cod' ? '-' : htmlspecialchars($payment['nomor_rekening']) ?></td>
                                <td><?= $payment['tipe'] === 'cod' ? '-' : htmlspecialchars($payment['atas_nama']) ?></td>
                                <td>
                                    <a href="?delete_payment=<?= $payment['id'] ?>" class="action-btn btn-delete"
                                    onclick="return confirm('Yakin hapus metode ini?')">🗑️ Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Pengaturan -->
            <div id="tab-settings" class="tab-content">
                <div class="section-header">
                    <h2 class="section-title">Pengaturan</h2>
                </div>

                <h3 style="margin-bottom: 15px;">Informasi Depot</h3>
                <form method="POST" style="margin-bottom: 30px;">
                    <div class="form-group">
                        <label>Nama Depot</label>
                        <input type="text" name="nama_depot" value="<?= htmlspecialchars($depot['nama_depot'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Alamat Depot</label>
                        <textarea name="alamat_depot" rows="3" required><?= htmlspecialchars($depot['alamat'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" name="update_depot" class="action-btn btn-add">💾 Simpan Perubahan</button>
                </form>

                <h3 style="margin-bottom: 15px; margin-top: 30px;">Ubah Password</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Password Lama</label>
                        <input type="password" name="old_password" required>
                    </div>
                    <div class="form-group">
                        <label>Password Baru</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <button type="submit" name="update_password" class="action-btn btn-add">🔒 Ubah Password</button>
                </form>
            </div>

            <!-- Tab Laporan -->
            <div id="tab-laporan" class="tab-content">
                <div class="section-header">
                    <h2 class="section-title">Laporan</h2>
                </div>
                <div style="margin-bottom: 15px;">
                    <a href="laporan_export.php?type=pdf" class="action-btn btn-add" target="_blank">📄 Cetak PDF</a>
                    <a href="laporan_export.php?type=excel" class="action-btn btn-add" target="_blank">📊 Export Excel</a>
                </div>


                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Pesanan</th>
                                <th>Pelanggan</th>
                                <th>Tanggal</th>
                                <th>Jumlah</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $laporan = $conn->query("SELECT * FROM orders WHERE status='selesai' ORDER BY created_at DESC");
                            while ($row = $laporan->fetch_assoc()):
                            ?>
                            <tr>
                                <td>#<?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['recipient_name'] ?? '-') ?></td>
                                <td><?= isset($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : '-' ?></td>
                                <td><?= $row['quantity'] . ' galon' ?></td>
                                <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                                <td><?= ucwords(str_replace('_', ' ', $row['status'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Galon -->
            <div id="tab-galon" class="tab-content">
                <div class="section-header">
                    <h2 class="section-title">Kelola Produk Galon</h2>
                    <button class="action-btn btn-add" onclick="document.getElementById('formAddGalon').style.display='block'">
                        ➕ Tambah Galon
                    </button>
                </div>

                <!-- Form Tambah Galon -->
                <div id="formAddGalon" style="display:none; margin-bottom:20px; padding:20px; background:#f8f9fa; border-radius:10px;">
                    <h3 style="margin-bottom:15px;">Tambah Galon</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Nama Galon</label>
                            <input type="text" name="nama_galon" required>
                        </div>
                        <div class="form-group">
                            <label>Deskripsi</label>
                            <textarea name="deskripsi" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Harga</label>
                            <input type="number" name="harga" required>
                        </div>
                        <div class="form-group">
                            <label>Stok</label>
                            <input type="number" name="stok" min="0" value="0" required>
                        </div>

                        <div class="form-group">
                            <label>Foto</label>
                            <input type="file" name="foto" accept="image/*" required>
                        </div>
                        <button type="submit" name="add_galon" class="action-btn btn-add">Simpan</button>
                        <button type="button" class="action-btn btn-delete" onclick="document.getElementById('formAddGalon').style.display='none'">Batal</button>
                    </form>
                </div>

                <!-- Tabel Galon -->
                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Nama</th>
                                <th>Deskripsi</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($galon = $galons->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if($galon['foto']): ?>
                                        <img src="uploads/galon/<?= htmlspecialchars($galon['foto']) ?>" alt="" width="60">
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($galon['nama']) ?></td>
                                <td><?= htmlspecialchars($galon['deskripsi']) ?></td>
                                <td>Rp <?= number_format($galon['harga'],0,',','.') ?></td>
                                <td><?= $galon['stok'] ?></td>
                                <td>
                                    <a href="javascript:void(0)" class="action-btn btn-edit"
                                    onclick="openEditModal('<?= $galon['id'] ?>', '<?= htmlspecialchars($galon['nama'], ENT_QUOTES) ?>', '<?= htmlspecialchars($galon['deskripsi'], ENT_QUOTES) ?>', '<?= $galon['harga'] ?>', '<?= $galon['stok'] ?>')">✏️ Edit</a>

                                    <a href="?delete_galon=<?= $galon['id'] ?>" class="action-btn btn-delete"
                                    onclick="return confirm('Yakin hapus galon ini?')">🗑️ Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Galon -->
    <div id="modalEditGalon" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
        <div style="background:white; padding:20px; border-radius:10px; width:400px; max-width:90%;">
            <h3 style="margin-bottom:15px;">Edit Galon</h3>
                <form id="formEditGalonModal" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_galon" id="edit_id">
                    <div class="form-group">
                                <label>Nama Galon</label>
                                <input type="text" name="nama_galon" id="edit_nama" required>
                            </div>
                            <div class="form-group">
                                <label>Deskripsi</label>
                                <textarea name="deskripsi" id="edit_deskripsi" rows="3" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Harga</label>
                                <input type="number" name="harga" id="edit_harga" required>
                            </div>
                    <div class="form-group">
                                <label>Stok</label>
                                <input type="number" name="stok" id="edit_stok" min="0" value="0" required>
                    </div>
                    <div class="form-group">
                        <label>Foto (biarkan kosong jika tidak ingin ganti)</label>
                        <input type="file" name="foto" id="edit_foto" accept="image/*">
                    </div>
                <button type="submit" name="update_galon" class="action-btn btn-add">💾 Simpan</button>
                <button type="button" class="action-btn btn-delete" onclick="closeModal()">❌ Batal</button>
            </form>
        </div>
    </div>

    <script>
    function switchTab(tabName, event) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Remove active class from all tab items
        document.querySelectorAll('.tab-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById('tab-' + tabName).classList.add('active');

        // Tambahkan active pada tombol yang diklik
        if(event) event.currentTarget.classList.add('active');
    }

    function filterOrders(status, event) {
        // Remove active class dari semua filter tab
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Tambahkan active pada tombol yang diklik
        if(event) event.currentTarget.classList.add('active');

        // Filter tabel berdasarkan status
        const rows = document.querySelectorAll('#ordersTable tbody tr');
        rows.forEach(row => {
            const rowStatus = row.getAttribute('data-status');
            row.style.display = (status === 'semua' || rowStatus === status) ? '' : 'none';
        });
    }

    // ===== Notifikasi otomatis hilang =====
    window.addEventListener('DOMContentLoaded', () => {
        const alert = document.querySelector('.alert-success');
        if (alert) {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 3000); // notifikasi tampil 3 detik
        }
    });
    // Fungsi buka modal edit dengan data galon
    function openEditModal(id, nama, deskripsi, harga, stok) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_deskripsi').value = deskripsi;
        document.getElementById('edit_harga').value = harga;
        document.getElementById('edit_stok').value = stok;
        document.getElementById('modalEditGalon').style.display = 'flex';
    }


    // Tutup modal
    function closeModal() {
        document.getElementById('modalEditGalon').style.display = 'none';
    }

    function toggleFields() {
        const tipe = document.getElementById('tipe_payment').value;
        const nomorGroup = document.getElementById('nomor_group');
        const atasNamaGroup = document.getElementById('atas_nama_group');
        const namaInput = document.getElementById('nama_payment');
        
        if (tipe === 'cod') {
            nomorGroup.style.display = 'none';
            atasNamaGroup.style.display = 'none';
            namaInput.value = 'COD';  // Set otomatis nama menjadi COD
            document.getElementById('nomor_payment').required = false;
            document.getElementById('atas_nama').required = false;
        } else {
            nomorGroup.style.display = 'block';
            atasNamaGroup.style.display = 'block';
            namaInput.value = '';  // Reset
            document.getElementById('nomor_payment').required = true;
            document.getElementById('atas_nama').required = true;
        }
    }

    // Panggil sekali saat load untuk default
    toggleFields();

    function filterOrders(status) {
        const rows = document.querySelectorAll('#ordersBody tr');
        rows.forEach(row => {
            if(status === 'semua' || row.dataset.status === status){
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        // Update tab active
        document.querySelectorAll('.filter-tab').forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');
    }

    </script>

</body>
</html>