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

$user_id = $_SESSION['user_id'];

if (!empty($_GET['order_code'])) {
    $order_code = $_GET['order_code'];

    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? AND order_code = ? AND status='menunggu_pembayaran' LIMIT 1");
    $stmt->bind_param("is", $user_id, $order_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentOrder = $result->fetch_assoc();
    $stmt->close();
} else {
    // fallback
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? AND status='menunggu_pembayaran' ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentOrder = $result->fetch_assoc();
    $stmt->close();
}


if (!$currentOrder) {
    echo "<script>alert('Pesanan tidak ditemukan atau sudah bukan status menunggu pembayaran.'); window.location.href='riwayat-pesanan.php';</script>";
    exit;
}



// Ambil semua metode pembayaran
$paymentQuery = "SELECT * FROM metode_pembayaran"; 
$paymentResult = $conn->query($paymentQuery);

$paymentMethods = [];
if ($paymentResult) {
    while($row = $paymentResult->fetch_assoc()) {
        $paymentMethods[] = $row;
    }
}

?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran - AquaDelivery</title>
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

        .payment-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .order-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .summary-row strong {
            color: #333;
        }

        .summary-row.total {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
            padding-top: 15px;
            border-top: 2px solid #e0e0e0;
            margin-top: 10px;
        }

        .bank-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .bank-info h3 {
            margin-bottom: 15px;
            font-size: 18px;
        }

        .bank-detail {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .bank-detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .account-number {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 2px;
        }

        .copy-btn {
            background: white;
            color: #667eea;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }

        .upload-section {
            margin: 25px 0;
        }

        .upload-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .file-upload {
            border: 3px dashed #667eea;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .file-upload:hover {
            background: #e8ebff;
            border-color: #5568d3;
        }

        .file-upload.has-file {
            border-style: solid;
            background: #e8f5e9;
            border-color: #4caf50;
        }

        .upload-icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .upload-text {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .file-input {
            display: none;
        }

        .preview-container {
            margin-top: 20px;
            display: none;
        }

        .preview-image {
            max-width: 100%;
            max-height: 400px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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

        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin-bottom: 20px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .payment-card {
                padding: 20px;
            }

            .summary-row {
                font-size: 13px;
            }

            .file-upload {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <button class="back-btn" onclick="window.location.href='dashboard.php'">←</button>
        <h1>Verifikasi Pembayaran</h1>
    </div>

    <div class="container">
        <div class="payment-card">
            <div class="order-summary">
                <h3 style="margin-bottom: 15px; color: #333;">Ringkasan Pesanan</h3>
                <div class="summary-row">
                    <span>ID Pesanan:</span>
                    <strong id="orderId"><?= htmlspecialchars($currentOrder['id']) ?></strong>
                </div>
                <div class="summary-row">
                    <span>Jumlah Galon:</span>
                    <strong id="orderQty"><?= (int)$currentOrder['quantity'] ?> galon</strong>
                </div>
                <div class="summary-row">
                    <span>Alamat Pengiriman:</span>
                    <strong id="orderAddress"><?= htmlspecialchars($currentOrder['address']) ?></strong>
                </div>
                <div class="summary-row total">
                    <span>Total Pembayaran:</span>
                    <span id="orderTotal">Rp <?= number_format($currentOrder['total'],0,',','.') ?></span>
                </div>
            </div>

            <!-- <div class="bank-info">
                <h3>💳 Informasi Pembayaran</h3>
                <div class="bank-detail">
                    <div class="bank-detail-row">
                        <span>Bank:</span>
                        <strong>BCA</strong>
                    </div>
                    <div class="bank-detail-row">
                        <span>Atas Nama:</span>
                        <strong>AquaDelivery Indonesia</strong>
                    </div>
                    <div class="bank-detail-row" style="margin-top: 10px;">
                        <div style="width: 100%;">
                            <div style="font-size: 12px; margin-bottom: 5px;">Nomor Rekening:</div>
                            <div class="account-number">1234567890</div>
                            <button class="copy-btn" onclick="copyAccountNumber()">📋 Salin Nomor</button>
                        </div>
                    </div>
                </div>
            </div> -->
            <div class="bank-info">
                <h3>💳 Informasi Pembayaran</h3>
                <?php foreach($paymentMethods as $method): ?>
                <div class="bank-detail">
                    <div class="bank-detail-row">
                        <span>Metode:</span>
                        <strong><?= htmlspecialchars($method['nama_metode']) ?></strong>
                    </div>
                    <div class="bank-detail-row">
                        <span>Atas Nama:</span>
                        <strong><?= htmlspecialchars($method['atas_nama'] ?? 'AquaDelivery Indonesia') ?></strong>
                    </div>
                    <div class="bank-detail-row" style="margin-top: 10px;">
                        <div style="width: 100%;">
                            <div style="font-size: 12px; margin-bottom: 5px;">Nomor Rekening:</div>
                            <div class="account-number"><?= htmlspecialchars($method['nomor_rekening']) ?></div>
                            <button class="copy-btn" onclick="copyAccountNumber('<?= htmlspecialchars($method['nomor_rekening']) ?>')">📋 Salin Nomor</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>


            <div class="warning">
                ⚠️ <strong>Perhatian:</strong> Pastikan Anda telah melakukan pembayaran sebelum mengunggah bukti transfer. Admin akan memverifikasi dalam 1x24 jam.
            </div>

            <!-- <div class="upload-section">
                <h3>Upload Bukti Pembayaran</h3>
                <div class="file-upload" id="fileUpload" onclick="document.getElementById('fileInput').click()">
                    <div class="upload-icon" id="uploadIcon">📤</div>
                    <div class="upload-text" id="uploadText">
                        Klik untuk memilih file atau drag & drop<br>
                        <small>Format: JPG, PNG, PDF (Max 5MB)</small>
                    </div>
                </div>
                <input type="file" id="fileInput" class="file-input" accept="image/*,.pdf" onchange="handleFileSelect(event)">
                
                <div class="preview-container" id="previewContainer">
                    <img id="previewImage" class="preview-image" alt="Preview">
                </div>
            </div> -->
            <form action="upload_bukti.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="order_id" value="<?= $currentOrder['id'] ?>">
                
                <div class="file-upload" id="fileUpload" onclick="document.getElementById('fileInput').click()">
                    <div class="upload-icon" id="uploadIcon">📤</div>
                    <div class="upload-text" id="uploadText">
                        Klik untuk memilih file atau drag & drop<br>
                        <small>Format: JPG, PNG, PDF (Max 5MB)</small>
                    </div>
                </div>
                <input type="file" id="fileInput" name="payment_proof" class="file-input" accept="image/*,.pdf" onchange="handleFileSelect(event)">
                
                <div class="preview-container" id="previewContainer">
                    <img id="previewImage" class="preview-image" alt="Preview">
                </div>
                <br>

                <button type="submit" class="submit-btn" id="submitBtn" disabled>Kirim Bukti Pembayaran</button>
            </form>
        </div>
    </div>
<script>
const fileInput = document.getElementById('fileInput');
const fileUpload = document.getElementById('fileUpload');
const uploadIcon = document.getElementById('uploadIcon');
const uploadText = document.getElementById('uploadText');
const previewContainer = document.getElementById('previewContainer');
const previewImage = document.getElementById('previewImage');
const submitBtn = document.getElementById('submitBtn');

function handleFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;

    // Validasi ukuran file max 5MB
    if (file.size > 5 * 1024 * 1024) {
        alert('Ukuran file terlalu besar. Maksimal 5MB.');
        return;
    }

    // Update tampilan upload
    fileUpload.classList.add('has-file');
    uploadIcon.textContent = '✅';
    uploadText.innerHTML = `<strong>${file.name}</strong><br><small>File berhasil dipilih</small>`;

    // Preview jika gambar
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewContainer.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }

    // Aktifkan tombol submit
    submitBtn.disabled = false;
}

// Drag & drop support (opsional)
fileUpload.addEventListener('dragover', e => {
    e.preventDefault();
    fileUpload.style.background = '#e8ebff';
});
fileUpload.addEventListener('dragleave', () => {
    fileUpload.style.background = '#f8f9fa';
});
fileUpload.addEventListener('drop', e => {
    e.preventDefault();
    fileUpload.style.background = '#f8f9fa';
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        handleFileSelect({ target: { files: files } });
    }
});
</script>

</body>
</html>