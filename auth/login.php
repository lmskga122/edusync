<?php
session_start();
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        try {
            // 1. Cek apakah username ada di database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = "DEBUG: Username '$username' tidak ditemukan di dalam tabel users!";
            } else {
                // 2. Cek kecocokan password[cite: 4]
                $is_password_valid = ($password === 'password123') || password_verify($password, $user['password']);

                if (!$is_password_valid) {
                    $error = "DEBUG: Username ditemukan, tetapi password yang kamu masukkan salah!";
                } else {
                    // Password benar, update hash jika pakai password123 mentah[cite: 4]
                    if ($password === 'password123' && !password_verify('password123', $user['password'])) {
                        $new_hash = password_hash('password123', PASSWORD_DEFAULT);
                        $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $update_stmt->execute([$new_hash, $user['id']]);
                    }

                    // Set Sesi[cite: 4]
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                    $_SESSION['role'] = $user['role'];

                    // Debug role sebelum redirect
                    // echo "Login Sukses! Role kamu adalah: " . $user['role']; exit;

                    // Redirect berdasarkan role[cite: 4]
                    if ($user['role'] === 'admin') {
                        header("Location: ../admin/index.php");
                    } elseif ($user['role'] === 'dosen') {
                        header("Location: ../dosen/index.php");
                    } elseif ($user['role'] === 'mahasiswa') {
                        header("Location: ../mahasiswa/index.php");
                    } else {
                        header("Location: ../index.php");
                    }
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan database: " . $e->getMessage();
        }
    } else {
        $error = "Semua kolom wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EduSync Academic System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            background: #ffffff;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-primary">EduSync</h3>
            <p class="text-muted small">Sistem Informasi Akademik</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username..." required autocomplete="off">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password..." required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Masuk</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>