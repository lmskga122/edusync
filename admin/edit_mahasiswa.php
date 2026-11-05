<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$error = '';
$success = '';

// Ambil ID mahasiswa dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data mahasiswa berdasarkan ID
$stmt = $pdo->prepare("SELECT * FROM mahasiswa WHERE id = ?");
$stmt->execute([$id]);
$mhs = $stmt->fetch();

if (!$mhs) {
    header("Location: mahasiswa.php");
    exit;
}

// Ambil daftar seluruh dosen untuk pilihan Dosen PA
$dosen_list = $pdo->query("SELECT id, nidn, nama FROM dosen ORDER BY nama ASC")->fetchAll();

// Proses Update Data Mahasiswa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = trim($_POST['nim']);
    $nama = trim($_POST['nama']);
    $fakultas = trim($_POST['fakultas']);
    $jurusan = trim($_POST['jurusan']);
    $prodi = trim($_POST['prodi']);
    $angkatan = trim($_POST['angkatan']);
    $dosen_pa_id = !empty($_POST['dosen_pa_id']) ? (int)$_POST['dosen_pa_id'] : null;

    if (!empty($nim) && !empty($nama)) {
        try {
            $pdo->beginTransaction();

            // Update data di tabel mahasiswa
            $stmt_update = $pdo->prepare("UPDATE mahasiswa SET nim = ?, nama = ?, fakultas = ?, jurusan = ?, prodi = ?, angkatan = ?, dosen_pa_id = ? WHERE id = ?");
            $stmt_update->execute([$nim, $nama, $fakultas, $jurusan, $prodi, $angkatan, $dosen_pa_id, $id]);

            // Update juga nama_lengkap di tabel users jika terhubung
            if ($mhs['user_id']) {
                $stmt_user = $pdo->prepare("UPDATE users SET username = ?, nama_lengkap = ? WHERE id = ?");
                $stmt_user->execute([$nim, $nama, $mhs['user_id']]);
            }

            $pdo->commit();
            $success = "Data mahasiswa berhasil diperbarui!";
            
            // Refresh data mahasiswa terbaru
            $stmt->execute([$id]);
            $mhs = $stmt->fetch();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Gagal memperbarui data: " . $e->getMessage();
        }
    } else {
        $error = "NIM dan Nama Lengkap wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Mahasiswa - EduSync Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container py-5" style="max-width: 700px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-primary fw-bold m-0">Edit Data Mahasiswa</h2>
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

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">NIM</label>
                        <input type="text" class="form-control" name="nim" value="<?= htmlspecialchars($mhs['nim']); ?>" required>
                        <div class="form-text small">Mengubah NIM juga akan memperbarui username login mahasiswa.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($mhs['nama']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Fakultas</label>
                        <input type="text" class="form-control" name="fakultas" value="<?= htmlspecialchars($mhs['fakultas']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jurusan</label>
                        <input type="text" class="form-control" name="jurusan" value="<?= htmlspecialchars($mhs['jurusan']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Program Studi</label>
                        <input type="text" class="form-control" name="prodi" value="<?= htmlspecialchars($mhs['prodi']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Angkatan</label>
                        <input type="number" class="form-control" name="angkatan" value="<?= htmlspecialchars($mhs['angkatan']); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Dosen Pembimbing Akademik (PA)</label>
                        <select class="form-select" name="dosen_pa_id">
                            <option value="">-- Pilih Dosen PA --</option>
                            <?php foreach ($dosen_list as $d): ?>
                                <option value="<?= $d['id']; ?>" <?= ($mhs['dosen_pa_id'] == $d['id']) ? 'selected' : ''; ?>>
                                    [<?= $d['nidn']; ?>] <?= htmlspecialchars($d['nama']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small">Admin dapat mengubah atau menetapkan Dosen PA mahasiswa kapan saja di sini.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>