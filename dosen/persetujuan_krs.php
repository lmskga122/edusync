<?php
session_start();
require_once '../config/database.php';

// Validasi akses dosen
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dosen') {
    header("Location: ../auth/login.php");
    exit;
}

// Ambil data dosen yang sedang login
$user_id = $_SESSION['user_id'];
$stmt_dosen = $pdo->prepare("SELECT * FROM dosen WHERE user_id = ?");
$stmt_dosen->execute([$user_id]);
$dosen = $stmt_dosen->fetch();

$success = '';
$error = '';

// Proses Ubah Status Seluruh KRS milik Mahasiswa (Setujui / Tolak Paket)
if (isset($_GET['action']) && isset($_GET['mhs_id'])) {
    $mahasiswa_id = $_GET['mhs_id'];
    $action = $_GET['action']; // 'setujui' atau 'tolak'
    
    $status_baru = ($action === 'setujui') ? 'Disetujui' : 'Ditolak';

    try {
        $stmt_update = $pdo->prepare("UPDATE krs SET status = ? WHERE mahasiswa_id = ?");
        $stmt_update->execute([$status_baru, $mahasiswa_id]);
        $success = "KRS Mahasiswa berhasil diperbarui menjadi status: $status_baru!";
    } catch (PDOException $e) {
        $error = "Gagal memperbarui status KRS: " . $e->getMessage();
    }
}

// Ambil daftar mahasiswa yang mengajukan KRS
$stmt_mhs_krs = $pdo->query("
    SELECT DISTINCT m.id AS mahasiswa_id, m.nama, m.nim, m.prodi
    FROM krs
    JOIN mahasiswa m ON krs.mahasiswa_id = m.id
    ORDER BY m.nama ASC
");
$daftar_mahasiswa = $stmt_mhs_krs->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi KRS Mahasiswa - EduSync Dosen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: all 0.2s ease-in-out;
        }
        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="py-5 bg-light">

    <div class="container" style="max-width: 1000px;">
        <!-- Header & Navigasi -->
        <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-4 rounded shadow-sm">
            <div>
                <h3 class="text-primary fw-bold mb-1">Validasi & Persetujuan KRS Paket</h3>
                <p class="text-muted mb-0">Dosen Pembimbing Akademik: <strong><?= htmlspecialchars($dosen['nama'] ?? $_SESSION['nama_lengkap']); ?></strong></p>
            </div>
            <div>
                <a href="index.php" class="btn btn-outline-secondary btn-sm px-3">&larr; Kembali ke Dashboard</a>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                ✨ <?= htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                ⚠️ <?= htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Daftar Mahasiswa -->
        <?php if (count($daftar_mahasiswa) > 0): ?>
            <?php foreach ($daftar_mahasiswa as $mhs): ?>
                <?php
                    // Ambil detail mata kuliah per mahasiswa
                    $stmt_detail = $pdo->prepare("
                        SELECT krs.id AS krs_id, krs.status, mk.kode_mk, mk.nama_mk, mk.sks, mk.semester
                        FROM krs
                        JOIN mata_kuliah mk ON krs.mata_kuliah_id = mk.id
                        WHERE krs.mahasiswa_id = ?
                    ");
                    $stmt_detail->execute([$mhs['mahasiswa_id']]);
                    $list_mk = $stmt_detail->fetchAll();

                    $total_sks = 0;
                    $status_paket = 'Pending';
                    if (count($list_mk) > 0) {
                        $status_paket = $list_mk[0]['status'];
                        foreach ($list_mk as $item) {
                            $total_sks += (int)$item['sks'];
                        }
                    }
                ?>
                <div class="card mb-4 rounded-3 overflow-hidden">
                    <div class="card-header bg-white px-4 py-3 d-flex justify-content-between align-items-center border-bottom">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($mhs['nama']); ?></h5>
                            <small class="text-muted">NIM: <strong><?= htmlspecialchars($mhs['nim']); ?></strong> | Prodi: <?= htmlspecialchars($mhs['prodi']); ?></small>
                        </div>
                        <div>
                            <?php if ($status_paket === 'Disetujui'): ?>
                                <span class="badge bg-success px-3 py-2 fs-6">✔ Disetujui</span>
                            <?php elseif ($status_paket === 'Ditolak'): ?>
                                <span class="badge bg-danger px-3 py-2 fs-6">✖ Ditolak</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark px-3 py-2 fs-6">⏳ Pending (Menunggu Validasi)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <h6 class="text-secondary fw-bold mb-3">Daftar Mata Kuliah yang Diajukan:</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-hover table-bordered align-middle mb-0">
                                <thead class="table-light text-secondary">
                                    <tr>
                                        <th style="width: 15%;">Kode MK</th>
                                        <th>Nama Mata Kuliah</th>
                                        <th style="width: 15%;" class="text-center">Semester</th>
                                        <th style="width: 15%;" class="text-center">SKS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($list_mk as $mk): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($mk['kode_mk']); ?></code></td>
                                            <td><?= htmlspecialchars($mk['nama_mk']); ?></td>
                                            <td class="text-center">Sem <?= $mk['semester']; ?></td>
                                            <td class="text-center fw-bold"><?= $mk['sks']; ?> SKS</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Total SKS Paket:</td>
                                        <td class="text-center">
                                            <span class="fw-bold fs-6 <?= ($total_sks > 24) ? 'text-danger' : 'text-primary'; ?>">
                                                <?= $total_sks; ?> SKS
                                            </span>
                                            <?php if ($total_sks > 24): ?>
                                                <br><small class="text-danger">⚠️ Melebihi batas (24 SKS)</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Tombol Aksi Persetujuan -->
                        <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                            <a href="persetujuan_krs.php?action=setujui&mhs_id=<?= $mhs['mahasiswa_id']; ?>" class="btn btn-success btn-sm px-4 py-2 fw-semibold">✓ Setujui Paket KRS</a>
                            <a href="persetujuan_krs.php?action=tolak&mhs_id=<?= $mhs['mahasiswa_id']; ?>" class="btn btn-outline-danger btn-sm px-4 py-2 fw-semibold" onclick="return confirm('Apakah Anda yakin ingin menolak seluruh pengajuan KRS mahasiswa ini?')">✕ Tolak KRS</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card shadow-sm text-center py-5">
                <div class="card-body">
                    <p class="text-muted fs-5 mb-0">Belum ada pengajuan KRS dari mahasiswa saat ini.</p>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>