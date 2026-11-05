<?php
session_start();
require_once '../config/database.php';

// Validasi akses dosen
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dosen') {
    header("Location: ../auth/login.php");
    exit;
}

$success = '';
$error = '';

// Proses Simpan / Update Nilai
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_nilai'])) {
    $mahasiswa_id = $_POST['mahasiswa_id'];
    $mata_kuliah_id = $_POST['mata_kuliah_id'];
    $nilai_tugas = floatval($_POST['nilai_tugas']);
    $nilai_uts = floatval($_POST['nilai_uts']);
    $nilai_uas = floatval($_POST['nilai_uas']);

    // Hitung Nilai Akhir (Contoh bobot: Tugas 30%, UTS 30%, UAS 40%)
    $nilai_akhir = ($nilai_tugas * 0.3) + ($nilai_uts * 0.3) + ($nilai_uas * 0.4);

    // Tentukan Grade
    if ($nilai_akhir >= 85) {
        $grade = 'A';
    } elseif ($nilai_akhir >= 75) {
        $grade = 'B';
    } elseif ($nilai_akhir >= 65) {
        $grade = 'C';
    } elseif ($nilai_akhir >= 50) {
        $grade = 'D';
    } else {
        $grade = 'E';
    }

    try {
        // Cek apakah data nilai untuk mahasiswa & MK ini sudah ada
        $stmt_cek = $pdo->prepare("SELECT id FROM nilai WHERE mahasiswa_id = ? AND kelas_perkuliahan_id = ?");
        $stmt_cek->execute([$mahasiswa_id, $mata_kuliah_id]);
        
        if ($stmt_cek->rowCount() > 0) {
            // Update jika sudah ada
            $stmt_update = $pdo->prepare("UPDATE nilai SET nilai_tugas = ?, nilai_uts = ?, nilai_uas = ?, nilai_akhir = ?, grade = ? WHERE mahasiswa_id = ? AND kelas_perkuliahan_id = ?");
            $stmt_update->execute([$nilai_tugas, $nilai_uts, $nilai_uas, $nilai_akhir, $grade, $mahasiswa_id, $mata_kuliah_id]);
        } else {
            // Insert jika belum ada
            $stmt_insert = $pdo->prepare("INSERT INTO nilai (mahasiswa_id, kelas_perkuliahan_id, nilai_tugas, nilai_uts, nilai_uas, nilai_akhir, grade) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_insert->execute([$mahasiswa_id, $mata_kuliah_id, $nilai_tugas, $nilai_uts, $nilai_uas, $nilai_akhir, $grade]);
        }
        $success = "Nilai mahasiswa berhasil disimpan!";
    } catch (PDOException $e) {
        $error = "Gagal menyimpan nilai: " . $e->getMessage();
    }
}

// Ambil daftar mahasiswa untuk diinput nilainya
$stmt_mhs = $pdo->query("SELECT * FROM mahasiswa ORDER BY nim ASC");
$daftar_mhs = $stmt_mhs->fetchAll();

// Ambil daftar mata kuliah
$stmt_mk = $pdo->query("SELECT * FROM mata_kuliah ORDER BY nama_mk ASC");
$daftar_mk = $stmt_mk->fetchAll();

// Ambil data nilai yang sudah tersimpan
$stmt_nilai = $pdo->query("
    SELECT n.*, m.nama AS nama_mhs, m.nim, mk.nama_mk, mk.kode_mk 
    FROM nilai n 
    JOIN mahasiswa m ON n.mahasiswa_id = m.id 
    JOIN mata_kuliah mk ON n.kelas_perkuliahan_id = mk.id 
    ORDER BY m.nim ASC
");
$daftar_nilai = $stmt_nilai->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nilai Mahasiswa - EduSync Dosen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="text-primary fw-bold">Input & Penilaian Mahasiswa</h2>
                <p class="text-muted mb-0">Kelola komponen nilai tugas, UTS, UAS, serta konversi nilai akhir dan grade.</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-outline-secondary">Kembali ke Dashboard</a>
            </div>
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

        <div class="row">
            <!-- Form Input Nilai -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h4 class="card-title text-primary fw-bold mb-3">Form Input Nilai</h4>
                        <form action="" method="POST">
                            <input type="hidden" name="simpan_nilai" value="1">
                            
                            <div class="mb-3">
                                <label class="form-label small">Pilih Mahasiswa</label>
                                <select name="mahasiswa_id" class="form-select form-select-sm" required>
                                    <option value="">-- Mahasiswa --</option>
                                    <?php foreach ($daftar_mhs as $mhs): ?>
                                        <option value="<?= $mhs['id']; ?>"><?= htmlspecialchars($mhs['nim'] . ' - ' . $mhs['nama']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small">Pilih Mata Kuliah</label>
                                <select name="mata_kuliah_id" class="form-select form-select-sm" required>
                                    <option value="">-- Mata Kuliah --</option>
                                    <?php foreach ($daftar_mk as $mk): ?>
                                        <option value="<?= $mk['id']; ?>"><?= htmlspecialchars($mk['kode_mk'] . ' - ' . $mk['nama_mk']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row mb-3">
                                <div class="col-4">
                                    <label class="form-label small">Tugas (30%)</label>
                                    <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm" name="nilai_tugas" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label small">UTS (30%)</label>
                                    <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm" name="nilai_uts" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label small">UAS (40%)</label>
                                    <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm" name="nilai_uas" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm w-100">Simpan / Update Nilai</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tabel Rekap Nilai -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h4 class="card-title text-primary fw-bold mb-3">Rekapitulasi Nilai Mahasiswa</h4>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Mahasiswa</th>
                                        <th>Mata Kuliah</th>
                                        <th>Tugas/UTS/UAS</th>
                                        <th>Akhir</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($daftar_nilai) > 0): ?>
                                        <?php foreach ($daftar_nilai as $n): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($n['nama_mhs']); ?></strong><br>
                                                    <small class="text-muted"><?= $n['nim']; ?></small>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($n['nama_mk']); ?><br>
                                                    <small class="text-muted"><?= $n['kode_mk']; ?></small>
                                                </td>
                                                <td>
                                                    <small>T: <?= $n['nilai_tugas']; ?></small><br>
                                                    <small>UTS: <?= $n['nilai_uts']; ?></small><br>
                                                    <small>UAS: <?= $n['nilai_uas']; ?></small>
                                                </td>
                                                <td><strong><?= number_format($n['nilai_akhir'], 2); ?></strong></td>
                                                <td>
                                                    <span class="badge bg-<?= ($n['grade'] == 'A' ? 'success' : ($n['grade'] == 'B' ? 'primary' : 'warning')); ?> fs-6">
                                                        <?= $n['grade']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Belum ada data nilai yang diinput.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>