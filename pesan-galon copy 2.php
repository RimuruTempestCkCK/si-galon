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

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Inisialisasi variabel
$user_id = $_SESSION['user_id'];
$pricePerGalon = 20000;
$deliveryFee = 5000;
$error = "";

// Proses submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = intval($_POST['quantity']);
    $recipient_name = htmlspecialchars($_POST['name']);
    $phone = htmlspecialchars($_POST['phone']);
    $address = htmlspecialchars($_POST['address']);
    $latitude = htmlspecialchars($_POST['latitude']);
    $longitude = htmlspecialchars($_POST['longitude']);
    $notes = htmlspecialchars($_POST['notes']);
    // $total = ($quantity * $pricePerGalon) + $deliveryFee;
    $total = $quantity * $pricePerGalon; // tanpa ongkos kirim
    $order_code = 'ORD' . time();

    // Insert ke database
    // $stmt = $conn->prepare("INSERT INTO orders (user_id, order_code, quantity, recipient_name, phone, address, latitude, longitude, notes, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    // $stmt->bind_param("isissssssi", $user_id, $order_code, $quantity, $recipient_name, $phone, $address, $latitude, $longitude, $notes, $total);
    $stmt = $conn->prepare("INSERT INTO orders (user_id, order_code, quantity, recipient_name, phone, address, latitude, longitude, notes, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isissssssi", $user_id, $order_code, $quantity, $recipient_name, $phone, $address, $latitude, $longitude, $notes, $total);
    if ($stmt->execute()) {
        header("Location: verifikasi-pembayaran.php?order_code=$order_code");
        exit;
    } else {
        $error = "Terjadi kesalahan: " . $stmt->error;
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
    <?php if($error) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post" id="orderForm">
        <div class="order-card">
            <!-- Jumlah Galon -->
            <div class="form-group">
                <label>Jumlah Galon</label>
                <div class="quantity-selector">
                    <button type="button" class="qty-btn" onclick="changeQty(-1)">-</button>
                    <div class="qty-display" id="quantityDisplay">1</div>
                    <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                </div>
                <input type="hidden" name="quantity" id="quantity" value="1">
            </div>

            <div class="form-group">
                <label for="name">Nama Penerima</label>
                <input type="text" name="name" id="name" required placeholder="Masukkan nama penerima">
            </div>

            <div class="form-group">
                <label for="phone">Nomor Telepon</label>
                <input type="tel" name="phone" id="phone" required placeholder="08xxxxxxxxxx">
            </div>

            <div class="form-group">
                <label for="address">Alamat Lengkap</label>
                <textarea name="address" id="address" required placeholder="Masukkan alamat lengkap pengiriman"></textarea>
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

            <div class="form-group">
                <label for="notes">Catatan (Opsional)</label>
                <textarea name="notes" id="notes" placeholder="Contoh: Antar sebelum jam 12 siang"></textarea>
            </div>

            <div class="price-summary">
                <div class="price-row"><span>Harga per galon:</span><span>Rp 20.000</span></div>
                <div class="price-row"><span>Jumlah galon:</span><span id="qtyDisplay">1</span></div>
                <!-- <div class="price-row"><span>Ongkos kirim:</span><span>Rp 5.000</span></div> -->
                <div class="price-row total"><span>Total Pembayaran:</span><span id="totalPrice">Rp 25.000</span></div>
            </div>

            <button type="submit" class="submit-btn">Lanjutkan ke Pembayaran</button>
        </div>
    </form>
</div>

<script>
// Quantity
let qty = 1;
function changeQty(val){
    qty += val;
    if(qty < 1) qty = 1;
    document.getElementById('quantityDisplay').innerText = qty;
    document.getElementById('quantity').value = qty;

    let subtotal = qty * <?= $pricePerGalon ?>;
    let total = subtotal; // tanpa ongkos kirim
    document.getElementById('qtyDisplay').innerText = qty;
    document.getElementById('totalPrice').innerText = 'Rp ' + total.toLocaleString('id-ID');
}

// Lokasi GPS
function setLocationType(type){
    let manual = document.getElementById('manualLocation');
    let gpsBtn = document.querySelectorAll('.location-btn');
    gpsBtn.forEach(btn => btn.classList.remove('active'));
    if(type === 'gps'){
        manual.style.display = 'none';
        gpsBtn[1].classList.add('active');
        if(navigator.geolocation){
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    document.getElementById('latitude').value = pos.coords.latitude;
                    document.getElementById('longitude').value = pos.coords.longitude;
                },
                (err) => {
                    alert("Gagal mendapatkan lokasi: " + err.message);
                    setLocationType('manual'); // fallback ke manual
                }
            );
        }else{
            alert("GPS tidak tersedia");
        }
    }else{
        manual.style.display = 'block';
        gpsBtn[0].classList.add('active');
    }
}
</script>
</body>
</html>
