<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$error = '';

// Ambil daftar dosen untuk pilihan Dosen PA
$stmt_dosen = $pdo->query("SELECT * FROM dosen");
$daftar_dosen = $stmt_dosen->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = trim($_POST['nim']);
    $nama = trim($_POST['nama']);
    $fakultas = trim($_POST['fakultas']);
    $jurusan = trim($_POST['jurusan']);
    $prodi = trim($_POST['prodi']);
    $angkatan = trim($_POST['angkatan']);
    $dosen_pa_id = !empty($_POST['dosen_pa_id']) ? $_POST['dosen_pa_id'] : null;
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($nim) && !empty($nama) && !empty($username) && !empty($password)) {
        try {
            $pdo->beginTransaction();

            // 1. Buat akun login
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_user = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, 'mahasiswa')");
            $stmt_user->execute([$username, $hashed_password, $nama]);
            $user_id = $pdo->lastInsertId();

            // 2. Masukkan data mahasiswa lengkap dengan Fakultas & Jurusan
            $stmt_mhs = $pdo->prepare("INSERT INTO mahasiswa (nim, nama, fakultas, jurusan, prodi, angkatan, ipk, user_id, dosen_pa_id) VALUES (?, ?, ?, ?, ?, ?, 0.00, ?, ?)");
            $stmt_mhs->execute([$nim, $nama, $fakultas, $jurusan, $prodi, $angkatan, $user_id, $dosen_pa_id]);

            $pdo->commit();
            header("Location: mahasiswa.php?status=sukses");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Terjadi kesalahan: " . $e->getMessage();
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
    <title>Tambah Mahasiswa - EduSync Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container py-5" style="max-width: 700px;">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h3 class="text-primary fw-bold mb-3">Tambah Mahasiswa Baru</h3>
                <p class="text-muted small mb-4">Lengkapi informasi akademik termasuk Fakultas, Jurusan, dan Program Studi mahasiswa.</p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= $error; ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <h5 class="text-secondary fs-6 fw-bold mb-3">Informasi Akun Login</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" placeholder="cth: mhs_yusuf" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" placeholder="Password akun..." required>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="text-secondary fs-6 fw-bold mb-3">Informasi Akademik</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">NIM</label>
                            <input type="text" class="form-control" name="nim" placeholder="cth: 24150100" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama" placeholder="Nama lengkap..." required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fakultas</label>
                        <input type="text" class="form-control" name="fakultas" value="Fakultas Teknik" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jurusan / Departemen</label>
                        <input type="text" class="form-control" name="jurusan" value="Pendidikan Teknik Informatika dan Komputer" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Program Studi</label>
                            <input type="text" class="form-control" name="prodi" value="Pendidikan Teknologi Informatika" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Angkatan</label>
                            <input type="number" class="form-control" name="angkatan" value="2024" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Dosen Pembimbing Akademik (PA)</label>
                        <select name="dosen_pa_id" class="form-select">
                            <option value="">-- Pilih Dosen PA --</option>
                            <?php foreach ($daftar_dosen as $dosen): ?>
                                <option value="<?= $dosen['id']; ?>"><?= htmlspecialchars($dosen['nama']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="mahasiswa.php" class="btn btn-outline-secondary">Kembali</a>
                        <button type="submit" class="btn btn-primary px-4">Simpan Mahasiswa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>