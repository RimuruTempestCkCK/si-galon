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

// ===== REGISTER (plain text) =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    // Ambil & sederhana-sanitasi input
    $name     = trim(mysqli_real_escape_string($conn, $_POST['name'] ?? ''));
    $phone    = trim(mysqli_real_escape_string($conn, $_POST['phone'] ?? ''));
    $email    = trim(mysqli_real_escape_string($conn, $_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm'] ?? '');
    $address  = trim(mysqli_real_escape_string($conn, $_POST['address'] ?? ''));

    if ($password !== $confirm) {
        $_SESSION['error'] = "Password tidak cocok!";
    } elseif (strlen($password) < 6) {
        $_SESSION['error'] = "Password minimal 6 karakter!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Format email tidak valid!";
    } else {
        // Cek apakah email sudah terdaftar (prepared atau simple query)
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE email = '{$email}'");
        if ($cek && mysqli_num_rows($cek) > 0) {
            $_SESSION['error'] = "Email sudah terdaftar!";
        } else {
            // Simpan user (plain text password)
            $insert = mysqli_query($conn, "INSERT INTO users (name, phone, email, password, address) VALUES ('{$name}','{$phone}','{$email}','{$password}','{$address}')");
            if ($insert) {
                $_SESSION['success'] = "Registrasi berhasil! Silakan login.";
            } else {
                $_SESSION['error'] = "Registrasi gagal: " . mysqli_error($conn);
            }
        }
    }
    header('Location: index.php');
    exit;
}

// ===== LOGIN (plain text) =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim(mysqli_real_escape_string($conn, $_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    // Cek admin dulu
    $cek_admin = mysqli_query($conn, "SELECT * FROM admins WHERE email='$email'");
    if ($cek_admin && mysqli_num_rows($cek_admin) === 1) {
        $admin = mysqli_fetch_assoc($cek_admin);
        if ($password === $admin['password']) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['email'];
            header('Location: dashboard_admin.php');
            exit;
        } else {
            $_SESSION['error'] = "Password admin salah!";
        }
    } else {
        // Login user
        $cek_user = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
        if ($cek_user && mysqli_num_rows($cek_user) === 1) {
            $user = mysqli_fetch_assoc($cek_user);
            if ($password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                header('Location: dashboard.php');
                exit;
            } else {
                $_SESSION['error'] = "Password user salah!";
            }
        } else {
            $_SESSION['error'] = "Email tidak ditemukan!";
        }
    }

    header('Location: index.php');
    exit;
}





// Tangani pesan sukses / error
$message = '';
$message_type = '';
if (isset($_SESSION['error'])) {
    $message = $_SESSION['error'];
    $message_type = 'error';
    unset($_SESSION['error']);
} elseif (isset($_SESSION['success'])) {
    $message = $_SESSION['success'];
    $message_type = 'success';
    unset($_SESSION['success']);
}
?>



<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depot Al-Azzahra - Pesan Galon Air</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 100%;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }

        .form-container {
            padding: 30px;
        }

        .tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            color: #999;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }

        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            font-size: 14px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 480px) {
            .container {
                border-radius: 15px;
            }

            .header {
                padding: 30px 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .form-container {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">💧</div>
            <h1>Depot Al-Azzahra</h1>
            <p>Pesan galon air dengan mudah dan cepat</p>
        </div>

        <div class="form-container">
            <div class="tabs">
                <div class="tab active" onclick="switchTab('login')">Login</div>
                <div class="tab" onclick="switchTab('register')">Daftar</div>
            </div>

            <?php if ($message): ?>
                <div id="message" class="message <?php echo $message_type; ?>" style="display:block;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div id="login-form" class="tab-content active">
                <form action="index.php" method="POST">
                    <input type="hidden" name="login" value="1">
                    <div class="form-group">
                        <label for="login-email">Email</label>
                        <input type="email" name="email" id="login-email" required>
                    </div>
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" name="password" id="login-password" required>
                    </div>
                    <button type="submit" class="btn">Masuk</button>
                </form>
            </div>

            <!-- Register Form -->
            <div id="register-form" class="tab-content">
                <form action="index.php" method="POST">
                    <input type="hidden" name="register" value="1">
                    <div class="form-group">
                        <label for="reg-name">Nama Lengkap</label>
                        <input type="text" name="name" id="reg-name" required>
                    </div>
                    <div class="form-group">
                        <label for="reg-phone">Nomor Telepon</label>
                        <input type="tel" name="phone" id="reg-phone" required>
                    </div>
                    <div class="form-group">
                        <label for="reg-email">Email</label>
                        <input type="email" name="email" id="reg-email" required>
                    </div>
                    <div class="form-group">
                        <label for="reg-address">Alamat Lengkap</label>
                        <textarea name="address" id="reg-address" rows="3" style="width:100%;padding:12px;border:2px solid #e0e0e0;border-radius:10px;"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="reg-password">Password</label>
                        <input type="password" name="password" id="reg-password" required>
                    </div>
                    <div class="form-group">
                        <label for="reg-confirm">Konfirmasi Password</label>
                        <input type="password" name="confirm" id="reg-confirm" required>
                    </div>
                    <button type="submit" class="btn">Daftar Sekarang</button>
                </form>
            </div>

            <!-- Copyright -->
            <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
                &copy; <?= date('Y') ?> Yuni Aprianti. All rights reserved.
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            if (tab === 'login') {
                tabs[0].classList.add('active');
                document.getElementById('login-form').classList.add('active');
            } else {
                tabs[1].classList.add('active');
                document.getElementById('register-form').classList.add('active');
            }

            hideMessage();
        }

        function showMessage(text, type) {
            const msg = document.getElementById('message');
            msg.textContent = text;
            msg.className = `message ${type}`;
            msg.style.display = 'block';
        }

        function hideMessage() {
            document.getElementById('message').style.display = 'none';
        }
    </script>
</body>

</html>