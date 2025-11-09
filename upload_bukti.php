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

$user_id = (int) $_SESSION['user_id'];
$order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;

if ($order_id <= 0) {
    die('Order tidak valid.');
}

// Ambil pesanan untuk verifikasi kepemilikan
$stmt = $conn->prepare("SELECT id, user_id, status, order_code FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
if (!$order) {
    die('Order tidak ditemukan.');
}
if ((int)$order['user_id'] !== $user_id) {
    die('Anda tidak berhak mengakses order ini.');
}

// Validasi file
if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
    die('File bukti belum diupload atau terjadi error upload. Error code: ' . ($_FILES['payment_proof']['error'] ?? 'N/A'));
}

// batas 5MB
if ($_FILES['payment_proof']['size'] > 5 * 1024 * 1024) {
    die('Ukuran file terlalu besar. Maks 5MB.');
}

// ekstensi yang diizinkan
$allowed = ['jpg','jpeg','png','pdf'];
$ext = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    die('Format file tidak diizinkan.');
}

// buat folder jika belum ada
$uploadDir = __DIR__ . '/uploads/bukti/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$newName = 'bukti_order_' . $order_id . '_' . time() . '.' . $ext;
$targetPath = $uploadDir . $newName;

if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $targetPath)) {
    die('Gagal menyimpan file upload. Pastikan permission folder uploads/bukti bisa ditulis.');
}

// simpan path relatif ke DB (sesuaikan jika Anda ingin path lain)
$payment_proof_db = 'uploads/bukti/' . $newName;

// Pilih status tujuan: umumnya 'menunggu_verifikasi'.
// Jika Anda ingin langsung otomatis 'selesai', ubah $newStatus = 'selesai';
$newStatus = 'menunggu_verifikasi';

$stmt2 = $conn->prepare("UPDATE orders SET status = ?, payment_proof = ?, payment_date = NOW() WHERE id = ? AND user_id = ?");
$stmt2->bind_param("ssii", $newStatus, $payment_proof_db, $order_id, $user_id);
if (!$stmt2->execute()) {
    // hapus file (opsional) jika update DB gagal
    @unlink($targetPath);
    die('Gagal update database: ' . $conn->error);
}

// sukses: redirect ke riwayat pesanan
header('Location: riwayat-pesanan.php?msg=upload_success');
exit;
