<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_csv'])) {
    $fileTmpPath = $_FILES['file_csv']['tmp_name'];
    $fileName = $_FILES['file_csv']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($fileExtension !== 'csv') {
        $error = "Hanya file dengan format .csv yang diperbolehkan!";
    } else {
        if (($handle = fopen($fileTmpPath, 'r')) !== FALSE) {
            // Lewati baris header pertama
            $header = fgetcsv($handle, 1000, ",");
            
            $berhasil = 0;
            $gagal = 0;

            try {
                $pdo->beginTransaction();

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    // Pastikan baris memiliki minimal 4 kolom (NIDN, Nama, Fakultas, Prodi)
                    if (count($data) < 4) {
                        $gagal++;
                        continue;
                    }

                    $nidn = trim($data[0]);
                    $nama = trim($data[1]);
                    $fakultas = trim($data[2]);
                    $prodi = trim($data[3]);

                    // Cek apakah NIDN sudah terdaftar
                    $stmt_cek = $pdo->prepare("SELECT id FROM dosen WHERE nidn = ?");
                    $stmt_cek->execute([$nidn]);
                    if ($stmt_cek->rowCount() > 0) {
                        $gagal++;
                        continue;
                    }

                    // 1. Buat akun login di tabel users (3 placeholder, 3 variabel)
                    $username = $nidn;
                    $password_default = password_hash($nidn, PASSWORD_DEFAULT);

                    $stmt_user = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, 'dosen')");
                    $stmt_user->execute([$username, $password_default, $nama]);
                    $user_id = $pdo->lastInsertId();

                    // 2. Simpan data ke tabel dosen (5 placeholder, 5 variabel)
                    $stmt_dosen = $pdo->prepare("INSERT INTO dosen (nidn, nama, fakultas, prodi, user_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt_dosen->execute([$nidn, $nama, $fakultas, $prodi, $user_id]);

                    $berhasil++;
                }

                $pdo->commit();
                fclose($handle);
                $success = "Import selesai! Berhasil menambahkan $berhasil data dosen, $gagal data dilewati (mungkin NIDN sudah terdaftar).";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Terjadi kesalahan saat memproses file CSV: " . $e->getMessage();
            }
        } else {
            $error = "Gagal membaca file CSV.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Dosen via CSV - EduSync Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container py-5" style="max-width: 700px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-primary fw-bold m-0">Import Data Dosen (CSV)</h2>
            <a href="dosen.php" class="btn btn-outline-secondary btn-sm">Kembali ke Daftar Dosen</a>
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
                <h5 class="card-title text-primary fw-bold mb-3">Panduan Format CSV Dosen</h5>
                <p class="text-muted small">Pastikan file CSV menggunakan pemisah koma (<code>,</code>) dan urutan kolom pada baris data adalah sebagai berikut:</p>
                
                <div class="bg-light p-3 rounded mb-3 font-monospace small text-dark border">
                    NIDN,Nama Lengkap & Gelar,Fakultas,Program Studi<br>
                    0012345601,Dr. Amirhud Dalimunthe S.T. M.Kom.,Fakultas Teknik,Pendidikan Teknologi Informatika<br>
                    0012345602,Rosma Siregar M.Kom.,Fakultas Teknik,Pendidikan Teknologi Informatika
                </div>
                
                <p class="text-muted small mb-0">
                    <em>Catatan:</em> Username dan password awal login dosen akan otomatis disamakan dengan **NIDN** mereka.
                </p>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih File CSV Dosen</label>
                        <input type="file" class="form-control" name="file_csv" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Upload & Import Data Dosen</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>