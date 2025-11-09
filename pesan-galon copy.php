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

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = "";

// Ambil product_id dari query string (dashboard.php mengarahkan ke ?id=...)
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = null;
$pricePerGalon = 0;
$product_name = '';
$product_foto = '';

if ($product_id > 0) {
    $stmt = $conn->prepare("SELECT id, nama, deskripsi, harga, foto FROM galon WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $product = $res->fetch_assoc();
    if ($product) {
        $pricePerGalon = (int)$product['harga'];
        $product_name = $product['nama'];
        $product_foto = $product['foto'];
    } else {
        $error = "Produk tidak ditemukan.";
    }
} else {
    $error = "Produk tidak ditentukan.";
}

// Proses submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil product_id dari hidden input (tetap re-query untuk keamanan)
    $posted_product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    // Validasi dan ambil harga aktual dari DB berdasarkan posted_product_id
    $pricePerGalon = 0;
    $product_name = '';
    if ($posted_product_id > 0) {
        $stmt = $conn->prepare("SELECT id, nama, harga FROM galon WHERE id = ?");
        $stmt->bind_param("i", $posted_product_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $prod = $res->fetch_assoc();
        if ($prod) {
            $pricePerGalon = (int)$prod['harga'];
            $product_name = $prod['nama'];
            $product_id = $posted_product_id; // update current product id
        } else {
            $error = "Produk tidak ditemukan saat submit.";
        }
    } else {
        $error = "Produk tidak valid.";
    }

    if ($error === "") {
        $quantity = max(1, intval($_POST['quantity']));
        $recipient_name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $latitude = trim($_POST['latitude']);
        $longitude = trim($_POST['longitude']);
        $notes_user = trim($_POST['notes']);

        // Hitung total berdasarkan harga aktual dari DB
        $subtotal = $quantity * $pricePerGalon;
        $total = $subtotal; // tambahkan ongkir jika ada

        // Simpan product info di notes (karena tabel orders mungkin belum punya kolom product_id)
        $notes = "Produk: " . $product_name . " (ID:" . $product_id . ") - " . $notes_user;

        $order_code = 'ORD' . time();

        // Prepared statement insert (sesuaikan kolom orders di DB)
        $stmt = $conn->prepare("INSERT INTO orders (user_id, order_code, quantity, recipient_name, phone, address, latitude, longitude, notes, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            $error = "Prepare gagal: " . $conn->error;
        } else {
            // types: i = int (user_id), s = string (order_code), i = int(quantity), s,s,s,s,s,s,i
            $stmt->bind_param("isissssssi",
                $user_id,
                $order_code,
                $quantity,
                $recipient_name,
                $phone,
                $address,
                $latitude,
                $longitude,
                $notes,
                $total
            );

            if ($stmt->execute()) {
                header("Location: verifikasi-pembayaran.php?order_code=" . urlencode($order_code));
                exit;
            } else {
                $error = "Terjadi kesalahan saat menyimpan pesanan: " . $stmt->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesan Galon - AquaDelivery</title>
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
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .qty-btn {
            width: 45px;
            height: 45px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .qty-btn:hover {
            background: #5568d3;
        }

        .qty-btn:active {
            transform: scale(0.95);
        }

        .qty-display {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            min-width: 50px;
            text-align: center;
        }

        .location-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 10px;
        }

        .location-options {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .location-btn {
            flex: 1;
            padding: 12px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .location-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .map-container {
            width: 100%;
            height: 300px;
            background: #e0e0e0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 15px;
            position: relative;
            overflow: hidden;
        }

        .map-placeholder {
            text-align: center;
            color: #666;
        }

        .coordinates {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
        }

        .price-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 25px 0;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .price-row.total {
            font-size: 20px;
            font-weight: 700;
            padding-top: 15px;
            border-top: 2px solid rgba(255,255,255,0.3);
            margin-top: 15px;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .order-card {
                padding: 20px;
            }

            .location-options {
                flex-direction: column;
            }

            .coordinates {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <button class="back-btn" onclick="window.location.href='dashboard.php'">←</button>
        <h1>Pesan Galon</h1>
    </div>
<div class="container">

    <?php if($error): ?>
        <div style="background:#ffe6e6;padding:12px;border-radius:6px;margin-bottom:12px;color:#900;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($product): ?>
    <form method="post" id="orderForm">
        <input type="hidden" name="product_id" id="product_id" value="<?= htmlspecialchars($product_id) ?>">
        <div class="order-card">
            <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px">
                <?php
                    $foto_url = 'uploads/galon/default.png';
                    if (!empty($product_foto) && file_exists(__DIR__.'/uploads/galon/'.$product_foto)) {
                        $foto_url = 'uploads/galon/' . rawurlencode($product_foto);
                    }
                ?>
                <img src="<?= htmlspecialchars($foto_url) ?>" alt="<?= htmlspecialchars($product_name) ?>" style="width:100px;height:auto;border-radius:8px">
                <div>
                    <h2 style="margin:0"><?= htmlspecialchars($product_name) ?></h2>
                    <div style="color:#666">Harga per galon: <strong id="priceText">Rp <?= number_format($pricePerGalon,0,',','.') ?></strong></div>
                </div>
            </div>

            <!-- Jumlah Galon -->
            <div class="form-group">
                <label>Jumlah Galon</label>
                <div class="quantity-selector" style="display:flex;align-items:center;gap:10px">
                    <button type="button" onclick="changeQty(-1)" style="width:40px;height:40px">-</button>
                    <div class="qty-display" id="quantityDisplay">1</div>
                    <button type="button" onclick="changeQty(1)" style="width:40px;height:40px">+</button>
                </div>
                <input type="hidden" name="quantity" id="quantity" value="1">
            </div>

            <div class="form-group">
                <label>Nama Penerima</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Nomor Telepon</label>
                <input type="tel" name="phone" required>
            </div>
            <div class="form-group">
                <label>Alamat Lengkap</label>
                <textarea name="address" required></textarea>
            </div>


            <!-- Lokasi -->
            <div class="form-group">
                <label>Lokasi Pengantaran</label>
                <div class="location-section">
                    <div class="location-options">
                        <button type="button" class="location-btn active" onclick="setLocationType('manual')">📍 Manual</button>
                        <button type="button" class="location-btn" onclick="setLocationType('gps')">🗺️ Gunakan GPS</button>
                    </div>
                    <div id="manualLocation">
                        <div class="coordinates">
                            <div>
                                <input type="text" name="latitude" id="latitude" placeholder="Latitude" value="-7.7956">
                            </div>
                            <div>
                                <input type="text" name="longitude" id="longitude" placeholder="Longitude" value="110.3695">
                            </div>
                        </div>
                    </div>
                    <div class="map-container" id="mapContainer">
                        <div class="map-placeholder">
                            <div style="font-size: 40px; margin-bottom: 10px;">🗺️</div>
                            <div>Klik "Gunakan GPS" untuk menampilkan peta</div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="price-summary">
                <div class="price-row"><span>Harga per galon:</span><span id="pricePer">Rp <?= number_format($pricePerGalon,0,',','.') ?></span></div>
                <div class="price-row"><span>Jumlah galon:</span><span id="qtyDisplay">1</span></div>
                <div class="price-row total" style="font-weight:700;margin-top:10px"><span>Total Pembayaran:</span><span id="totalPrice">Rp <?= number_format($pricePerGalon,0,',','.') ?></span></div>
            </div>

            <div class="form-group">
                <label>Catatan (opsional)</label>
                <textarea name="notes"></textarea>
            </div>

            <button type="submit" class="submit-btn" style="padding:12px 18px;background:#667eea;border:none;color:#fff;border-radius:8px;cursor:pointer">Lanjutkan ke Pembayaran</button>
        </div>
    </form>
    <?php else: ?>
        <p>Produk tidak tersedia.</p>
    <?php endif; ?>
</div>

<script>
let qty = 1;
const pricePerGalon = <?= json_encode($pricePerGalon) ?>;

function changeQty(delta) {
    qty += delta;
    if (qty < 1) qty = 1;
    document.getElementById('quantityDisplay').innerText = qty;
    document.getElementById('quantity').value = qty;
    document.getElementById('qtyDisplay').innerText = qty;
    const total = qty * pricePerGalon;
    document.getElementById('totalPrice').innerText = 'Rp ' + total.toLocaleString('id-ID');
}
</script>
</body>
</html>
