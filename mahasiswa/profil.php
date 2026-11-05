<?php
session_start();
require_once '../config/database.php';

// Validasi akses mahasiswa
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Ambil data mahasiswa dan data users (akun login)
$stmt = $pdo->prepare("
    SELECT m.*, u.username 
    FROM mahasiswa m 
    JOIN users u ON m.user_id = u.id 
    WHERE m.user_id = ?
");
$stmt->execute([$user_id]);
$data = $stmt->fetch();

if (!$data) {
    die("Data tidak ditemukan.");
}

// Proses Update Password Saja (Username tetap menggunakan NIM)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            throw new Exception("Semua kolom password wajib diisi jika ingin mengganti password!");
        }

        if ($new_password !== $confirm_password) {
            throw new Exception("Konfirmasi password baru tidak cocok!");
        }

        // Ambil password lama dari database untuk verifikasi
        $stmt_pass = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt_pass->execute([$user_id]);
        $user_db = $stmt_pass->fetch();

        if (!password_verify($old_password, $user_db['password'])) {
            throw new Exception("Password lama yang kamu masukkan salah!");
        }

        // Update password baru (Username dibiarkan tetap menggunakan NIM)
        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt_update->execute([$hashed_new_password, $user_id]);

        $success = "Password berhasil diperbarui!";
        
        // Refresh data terbaru
        $stmt->execute([$user_id]);
        $data = $stmt->fetch();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun - EduSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container py-5" style="max-width: 600px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-primary fw-bold m-0">Ganti Password Akun</h2>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Kembali ke Dashboard</a>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form action="" method="POST">
                    <h5 class="text-secondary fs-6 fw-bold mb-3">Informasi Akun</h5>
                    <div class="mb-3">
                        <label class="form-label text-muted small">NIM / Username Login</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($data['username']); ?>" disabled>
                        <div class="form-text">Username login menggunakan NIM Anda dan bersifat tetap (tidak dapat diubah).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small">Nama Mahasiswa</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($data['nama']); ?>" disabled>
                    </div>

                    <hr class="my-4">

                    <h5 class="text-secondary fs-6 fw-bold mb-3">Ubah Password</h5>
                    <div class="mb-3">
                        <label class="form-label">Password Lama</label>
                        <input type="password" class="form-control" name="old_password" placeholder="Masukkan password lama..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" class="form-control" name="new_password" placeholder="Masukkan password baru..." required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Ulangi password baru..." required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Simpan Perubahan Password</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>