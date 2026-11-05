<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$error = '';
$success = '';

// Ambil daftar dosen untuk pilihan default jika diperlukan
$stmt_dosen = $pdo->query("SELECT id FROM dosen ORDER BY id ASC LIMIT 1");
$default_dosen = $stmt_dosen->fetch();
$default_dosen_id = $default_dosen ? $default_dosen['id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['file_csv']['tmp_name'];
    $fileName = $_FILES['file_csv']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($fileExtension !== 'csv') {
        $error = "Hanya file dengan format .csv yang diperbolehkan!";
    } else {
        if (($handle = fopen($fileTmpPath, 'r')) !== FALSE) {
            // Ambil baris header
            $header = fgetcsv($handle, 1000, ",");
            
            $berhasil = 0;
            $gagal = 0;

            try {
                $pdo->beginTransaction();

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($data) < 6) {
                        $gagal++;
                        continue;
                    }

                    $nim = trim($data[0]);
                    $nama = trim($data[1]);
                    $fakultas = trim($data[2]);
                    $jurusan = trim($data[3]);
                    $prodi = trim($data[4]);
                    $angkatan = trim($data[5]);
                    
                    // Ambil ID Dosen PA dari CSV, jika kosong atau tidak valid, gunakan default dosen pertama
                    $dosen_pa_id = $default_dosen_id;
                    if (isset($data[6]) && trim($data[6]) !== '' && is_numeric(trim($data[6]))) {
                        $dosen_pa_id = (int) trim($data[6]);
                    }

                    // Cek apakah NIM sudah ada
                    $stmt_cek = $pdo->prepare("SELECT id FROM mahasiswa WHERE nim = ?");
                    $stmt_cek->execute([$nim]);
                    if ($stmt_cek->rowCount() > 0) {
                        $gagal++;
                        continue;
                    }

                    // 1. Buat akun di tabel users (3 placeholder, 3 variabel dikirim)
                    $username = $nim;
                    $password_default = password_hash($nim, PASSWORD_DEFAULT);

                    $stmt_user = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, 'mahasiswa')");
                    $stmt_user->execute([$username, $password_default, $nama]);
                    $user_id = $pdo->lastInsertId();

                    // 2. Buat data di tabel mahasiswa (9 placeholder, 9 variabel dikirim secara presisi)
                    $stmt_mhs = $pdo->prepare("INSERT INTO mahasiswa (nim, nama, fakultas, jurusan, prodi, angkatan, ipk, user_id, dosen_pa_id) VALUES (?, ?, ?, ?, ?, ?, 0.00, ?, ?)");
                    
                    $stmt_mhs->execute([
                        $nim, 
                        $nama, 
                        $fakultas, 
                        $jurusan, 
                        $prodi, 
                        $angkatan, 
                        $user_id, 
                        $dosen_pa_id
                    ]);

                    $berhasil++;
                }

                $pdo->commit();
                fclose($handle);
                $success = "Import selesai! Berhasil menambahkan $berhasil data mahasiswa. ($gagal data dilewati karena NIM sudah terdaftar atau format tidak lengkap).";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Terjadi kesalahan database: " . $e->getMessage();
            }
        } else {
            $error = "Gagal membuka file CSV.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Mahasiswa via CSV - EduSync Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container py-5" style="max-width: 700px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-primary fw-bold m-0">Import Data Mahasiswa (CSV)</h2>
            <a href="mahasiswa.php" class="btn btn-outline-secondary btn-sm">Kembali ke Daftar Mahasiswa</a>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <h5 class="card-title text-primary fw-bold mb-3">Panduan Format CSV</h5>
                <p class="text-muted small">Pastikan file CSV menggunakan pemisah koma (<code>,</code>). Jika kolom Dosen PA kosong di CSV, sistem akan otomatis mengisinya dengan dosen default dan dapat diedit kemudian.</p>
                <div class="bg-light p-3 rounded mb-3 font-monospace small text-dark border">
                    NIM,Nama Lengkap,Fakultas,Jurusan,Prodi,Angkatan,ID_Dosen_PA<br>
                    24150101,Budi Santoso,Fakultas Teknik,Pendidikan Teknik Elektro,Pendidikan Teknologi Informatika dan Komputer,2024,
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih File CSV Mahasiswa</label>
                        <input type="file" class="form-control" name="file_csv" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Upload & Import Data</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>