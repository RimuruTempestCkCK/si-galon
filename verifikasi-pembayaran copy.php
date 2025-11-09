<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '.depot-al-azzahra.site',
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

// Ambil order
if (!empty($_GET['order_code'])) {
    $order_code = $_GET['order_code'];
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id=? AND order_code=? LIMIT 1");
    $stmt->bind_param("is", $user_id, $order_code);
} else {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$currentOrder = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$currentOrder) {
    echo "<script>alert('Pesanan tidak ditemukan.'); window.location.href='riwayat-pesanan.php';</script>";
    exit;
}

// --- Pilih metode pembayaran ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_payment'])) {
    $order_id = (int)($_POST['order_id'] ?? $currentOrder['id']);
    $pm_id = (int)($_POST['payment_method_id'] ?? 0);

    $stmtPm = $conn->prepare("SELECT id, tipe FROM metode_pembayaran WHERE id=? LIMIT 1");
    $stmtPm->bind_param("i", $pm_id);
    $stmtPm->execute();
    $pmRes = $stmtPm->get_result()->fetch_assoc();
    $stmtPm->close();

    if (!$pmRes) {
        $_SESSION['flash_error'] = "Metode pembayaran tidak valid.";
        header("Location: ".$_SERVER['PHP_SELF']."?order_code=".urlencode($currentOrder['order_code']));
        exit;
    }

    $tipe = $pmRes['tipe'] ?? 'transfer';

    if ($tipe === 'cod') {
        $stmt = $conn->prepare("UPDATE orders SET payment_method_id=?, status='diproses', updated_at=NOW() WHERE id=? AND user_id=?");
        $stmt->bind_param("iii", $pm_id, $order_id, $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash_success'] = "Metode COD dipilih — pesanan otomatis diproses.";
        header("Location: riwayat-pesanan.php");
        exit;
    } else {
        $stmt = $conn->prepare("UPDATE orders SET payment_method_id=?, status='menunggu_verifikasi', updated_at=NOW() WHERE id=? AND user_id=?");
        $stmt->bind_param("iii", $pm_id, $order_id, $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash_success'] = "Metode pembayaran dipilih. Silakan unggah bukti transfer.";
        header("Location: ".$_SERVER['PHP_SELF']."?order_code=".urlencode($currentOrder['order_code']));
        exit;
    }
}

// --- Upload bukti transfer ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_transfer'])) {
    $order_id = (int)($_POST['order_id'] ?? $currentOrder['id']);
    $pm_id = (int)($_POST['payment_method_id'] ?? 0);

    if (!empty($_FILES['payment_proof']['name'])) {
        $file = $_FILES['payment_proof'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','pdf'];

        if (!in_array($ext, $allowed) || $file['size'] > 5*1024*1024) {
            $_SESSION['flash_error'] = "File tidak valid atau terlalu besar (max 5MB).";
            header("Location: ".$_SERVER['PHP_SELF']."?order_code=".urlencode($currentOrder['order_code']));
            exit;
        }

        if (!is_dir(__DIR__.'/uploads')) mkdir(__DIR__.'/uploads', 0755, true);
        $newName = "bukti_{$order_id}_".time().".".$ext;

        if (move_uploaded_file($file['tmp_name'], __DIR__.'/uploads/'.$newName)) {
            $stmt = $conn->prepare("UPDATE orders SET payment_method_id=?, status='menunggu_verifikasi', payment_proof=?, updated_at=NOW() WHERE id=? AND user_id=?");
            $stmt->bind_param("isii", $pm_id, $newName, $order_id, $user_id);
            $stmt->execute();
            $stmt->close();

            $_SESSION['flash_success'] = "Bukti transfer berhasil diunggah. Menunggu verifikasi.";
            header("Location: riwayat-pesanan.php");
            exit;
        } else {
            $_SESSION['flash_error'] = "Gagal mengunggah file.";
            header("Location: ".$_SERVER['PHP_SELF']."?order_code=".urlencode($currentOrder['order_code']));
            exit;
        }
    } else {
        $_SESSION['flash_error'] = "Silakan unggah bukti transfer.";
        header("Location: ".$_SERVER['PHP_SELF']."?order_code=".urlencode($currentOrder['order_code']));
        exit;
    }
}

// Ambil semua metode pembayaran
$paymentResult = $conn->query("SELECT * FROM metode_pembayaran");
$paymentMethods = $paymentResult ? $paymentResult->fetch_all(MYSQLI_ASSOC) : [];
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

        #transferInfo span {
            color: #000;
            font-weight: 600;
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


            <!-- Pilih Metode Pembayaran -->
            <div class="bank-info">
                <h3>💳 Pilih Metode Pembayaran</h3>
                <form method="post" id="selectPaymentForm">
                    <input type="hidden" name="select_payment" value="1">
                    <input type="hidden" name="order_id" value="<?= (int)$currentOrder['id'] ?>">

                    <?php foreach($paymentMethods as $method): 
                        $pmId = (int)$method['id'];
                        $pmName = htmlspecialchars($method['nama_metode']);
                        $pmType = htmlspecialchars($method['tipe'] ?? 'transfer'); 
                        $pmRek = htmlspecialchars($method['nomor_rekening']);
                        $pmAtasNama = htmlspecialchars($method['atas_nama']);
                    ?>
                    <label style="display:block; margin-bottom:10px; cursor:pointer;">
                        <input type="radio" 
                            name="payment_method_id" 
                            value="<?= $pmId ?>" 
                            data-type="<?= $pmType ?>" 
                            data-rek="<?= $pmRek ?>" 
                            data-nama="<?= $pmAtasNama ?>" 
                            required>
                        <strong style="margin-left:8px;"><?= $pmName ?> <?= $pmType === 'cod' ? '(COD)' : '' ?></strong>
                    </label>
                    <?php endforeach; ?>

                    <div style="margin-top:12px;">
                        <button type="submit" class="submit-btn" id="choosePaymentBtn">Lanjutkan</button>
                    </div>
                </form>
            </div>

            <!-- Info Rekening Transfer (letakkan setelah bank-info) -->
            <div id="transferInfo" style="display:none; margin-top:15px; padding:15px; background:#fff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                <div><strong>No. Rekening:</strong> <span id="accountNumber" style="color:#000;"></span></div>
                <div><strong>Atas Nama:</strong> <span id="accountName" style="color:#000;"></span></div>
            </div>
 



            <div class="warning">
                ⚠️ <strong>Perhatian:</strong> Pastikan Anda telah melakukan pembayaran sebelum mengunggah bukti transfer. Admin akan memverifikasi dalam 1x24 jam.
            </div>

            <!-- Upload Bukti Pembayaran -->
            <form method="post" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="payment_method_id" id="uploadPaymentMethod">
                <input type="hidden" name="upload_transfer" value="1">
                
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
document.addEventListener('DOMContentLoaded', function() {
    // --- Elemen DOM ---
    const fileInput = document.getElementById('fileInput');
    const fileUpload = document.getElementById('fileUpload');
    const uploadIcon = document.getElementById('uploadIcon');
    const uploadText = document.getElementById('uploadText');
    const previewContainer = document.getElementById('previewContainer');
    const previewImage = document.getElementById('previewImage');
    const submitBtn = document.getElementById('submitBtn');
    const radios = document.querySelectorAll('input[name="payment_method_id"]');
    const transferDiv = document.getElementById('transferInfo');
    const accountNumber = document.getElementById('accountNumber');
    const accountName = document.getElementById('accountName');
    const uploadMethodInput = document.getElementById('uploadPaymentMethod');
    const chooseBtn = document.getElementById('choosePaymentBtn');
    const form = document.getElementById('selectPaymentForm');

    // --- Fungsi Toggle Transfer & Upload Area ---
    function toggleTransferUI(show, rek = '', nama = '') {
        transferDiv.style.display = show ? 'block' : 'none';
        fileUpload.style.display = show ? 'block' : 'none';
        submitBtn.style.display = show ? 'block' : 'none';
        accountNumber.textContent = rek;
        accountName.textContent = nama;
    }

    // --- Hide area upload awal ---
    toggleTransferUI(false);

    // --- Listener Radio Button ---
    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            const type = this.dataset.type;
            uploadMethodInput.value = this.value;

            if (type === 'transfer') {
                toggleTransferUI(true, this.dataset.rek, this.dataset.nama);
            } else {
                toggleTransferUI(false);
            }
        });
    });

    // --- Listener File Input ---
    fileInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validasi ukuran max 5MB
        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran file terlalu besar. Maksimal 5MB.');
            return;
        }

        // Update tampilan upload
        fileUpload.classList.add('has-file');
        uploadIcon.textContent = '✅';
        uploadText.innerHTML = `<strong>${file.name}</strong><br><small>File berhasil dipilih</small>`;

        // Preview gambar
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = e => {
                previewImage.src = e.target.result;
                previewContainer.style.display = 'block';
            }
            reader.readAsDataURL(file);
        }

        submitBtn.disabled = false;
    });

    // --- Drag & Drop Support ---
    fileUpload.addEventListener('dragover', e => { e.preventDefault(); fileUpload.style.background = '#e8ebff'; });
    fileUpload.addEventListener('dragleave', () => { fileUpload.style.background = '#f8f9fa'; });
    fileUpload.addEventListener('drop', e => {
        e.preventDefault();
        fileUpload.style.background = '#f8f9fa';
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            fileInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    // --- Optional: tombol Lanjutkan ---
    if (form) {
        form.addEventListener('submit', () => {
            chooseBtn.textContent = 'Memproses...';
        });
    }
});
</script>


</body>
</html>