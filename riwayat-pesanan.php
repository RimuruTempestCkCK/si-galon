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

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Jika request dari Ajax untuk batal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_code'])) {
    $order_code = $_POST['order_code'];

    $stmt = $conn->prepare("UPDATE orders SET status='dibatalkan' WHERE user_id=? AND order_code=?");
    $stmt->bind_param("is", $user_id, $order_code);
    $success = $stmt->execute();
    $stmt->close();

    if($success){
        echo json_encode(['success' => true, 'message' => 'Pesanan berhasil dibatalkan']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal membatalkan pesanan']);
    }
    exit; // Hentikan eksekusi agar HTML tidak ikut terkirim
}

// Fungsi status
function getStatusText($status) {
    $map = [
        'menunggu_pembayaran' => 'Menunggu Pembayaran',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'diproses' => 'Diproses',
        'sedang_dikirim' => 'Dikirim',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan'
    ];
    return $map[$status] ?? $status;
}

// Ambil daftar pesanan
$filter = $_GET['filter'] ?? 'semua';
$sql = "SELECT * FROM orders WHERE user_id = ?";
if ($filter !== 'semua') {
    $sql .= " AND status = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $filter);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Pesanan - AquaDelivery</title>
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
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .navbar h1 {
            font-size: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            color: #666;
        }

        .filter-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .order-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }

        .order-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .order-id {
            font-weight: 700;
            color: #333;
            font-size: 16px;
        }

        .order-date {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-menunggu_pembayaran {
            background: #fff8e1; /* soft yellow */
            color: #ffb300;      /* golden */
            border: 1px solid #ffecb3;
        }

        .status-menunggu_verifikasi {
            background: #e3f2fd; /* soft blue */
            color: #1565c0;      /* strong blue */
            border: 1px solid #90caf9;
        }

        .status-diproses {
            background: #e1f5fe; /* light cyan */
            color: #0288d1;      /* medium blue */
            border: 1px solid #81d4fa;
        }

        .status-sedang_dikirim {
            background: #e0f7fa; /* soft teal */
            color: #006064;      /* dark teal */
            border: 1px solid #4dd0e1;
        }

        .status-selesai {
            background: #e8f5e9; /* soft green */
            color: #2e7d32;      /* forest green */
            border: 1px solid #a5d6a7;
        }

        .status-dibatalkan {
            background: #ffebee; /* soft red/pink */
            color: #c62828;      /* dark red */
            border: 1px solid #ef9a9a;
        }


        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 14px;
            color: #333;
            font-weight: 600;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 2px solid #f0f0f0;
        }

        .order-total {
            font-size: 18px;
            font-weight: 700;
            color: #667eea;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-detail {
            background: #667eea;
            color: white;
        }

        .btn-detail:hover {
            background: #5568d3;
        }

        .btn-cancel {
            background: #dc3545;
            color: white;
        }

        .btn-cancel:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .empty-text {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }

        .empty-subtext {
            font-size: 14px;
            color: #999;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .order-card {
                padding: 20px;
            }

            .order-header {
                flex-direction: column;
                gap: 10px;
            }

            .order-details {
                grid-template-columns: 1fr;
            }

            .order-footer {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .filter-buttons {
                flex-direction: column;
            }

            .filter-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="navbar">
    <button class="back-btn" onclick="window.location.href='dashboard.php'">←</button>
    <h1>Riwayat Pesanan</h1>
</div>

<div class="filter-section">
<form method="get" class="filter-buttons">
    <button type="submit" name="filter" value="semua" class="filter-btn <?= ($filter=='semua')?'active':'' ?>">Semua</button>
    <button type="submit" name="filter" value="menunggu_pembayaran" class="filter-btn <?= ($filter=='menunggu_pembayaran')?'active':'' ?>">Menunggu Pembayaran</button>
    <button type="submit" name="filter" value="menunggu_verifikasi" class="filter-btn <?= ($filter=='menunggu_verifikasi')?'active':'' ?>">Menunggu Verifikasi</button>
    <button type="submit" name="filter" value="diproses" class="filter-btn <?= ($filter=='diproses')?'active':'' ?>">Diproses</button>
    <button type="submit" name="filter" value="sedang_dikirim" class="filter-btn <?= ($filter=='dikirim')?'active':'' ?>">Dikirim</button>
    <button type="submit" name="filter" value="selesai" class="filter-btn <?= ($filter=='selesai')?'active':'' ?>">Selesai</button>
</form>
</div>

<div class="order-list">
<?php if(count($orders)===0): ?>
<div class="empty-state">
    <div class="empty-icon">📦</div>
    <div class="empty-text">Belum Ada Pesanan</div>
    <div class="empty-subtext">Mulai pesan galon air sekarang!</div>
</div>
<?php else: ?>
<?php foreach($orders as $order): ?>
<div class="order-card">
    <div class="order-header">
        <div>
            <div class="order-id"><?= htmlspecialchars($order['order_code']) ?></div>
            <div class="order-date"><?= date('d M Y H:i',strtotime($order['created_at'])) ?></div>
        </div>
        <div class="status-badge status-<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars(getStatusText($order['status'])) ?></div>
    </div>

    <div class="order-details">
        <div class="detail-item"><div class="detail-label">Jumlah</div><div class="detail-value"><?= (int)$order['quantity'] ?> Galon</div></div>
        <div class="detail-item"><div class="detail-label">Penerima</div><div class="detail-value"><?= htmlspecialchars($order['recipient_name']) ?></div></div>
        <div class="detail-item"><div class="detail-label">Telepon</div><div class="detail-value"><?= htmlspecialchars($order['phone']) ?></div></div>
        <div class="detail-item"><div class="detail-label">Alamat</div><div class="detail-value"><?= htmlspecialchars($order['address']) ?></div></div>
    </div>

    <div class="order-footer">
        <div class="order-total">Rp <?= number_format($order['total'],0,',','.') ?></div>
        <?php if($order['status'] === 'menunggu_pembayaran'): ?>
        <div class="action-buttons">
            <a href="verifikasi-pembayaran.php?order_code=<?= urlencode($order['order_code']) ?>" class="btn btn-detail">Lanjutkan Pembayaran</a>
            <a href="#" class="btn btn-cancel" onclick="cancelOrder('<?= $order['order_code'] ?>'); return false;">Batalkan</a>
        </div>
        <?php elseif($order['status'] !== 'selesai' && $order['status'] !== 'dibatalkan' && $order['status'] !== 'menunggu_verifikasi'): ?>
            <!-- <div class="action-buttons">
                <a href="detail-order.php?order_code=<?= urlencode($order['order_code']) ?>" class="btn btn-detail">Detail</a>
            </div> -->
        <?php endif; ?>

    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<script>
function cancelOrder(orderCode){
    if(!confirm('Batalkan pesanan ini?')) return;

    fetch('', { // arahkan ke file ini sendiri
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'order_code=' + encodeURIComponent(orderCode)
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if(data.success){
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
    });
}
</script>

</body>
</html>
