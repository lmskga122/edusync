<?php
session_start();
require_once '../config/database.php';

// Validasi akses mahasiswa
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../auth/login.php");
    exit;
}

// Ambil data mahasiswa yang sedang login berdasarkan user_id
$user_id = $_SESSION['user_id'];
$stmt_mhs = $pdo->prepare("SELECT * FROM mahasiswa WHERE user_id = ?");
$stmt_mhs->execute([$user_id]);
$mahasiswa = $stmt_mhs->fetch();

if (!$mahasiswa) {
    die("Data mahasiswa tidak ditemukan.");
}

$mahasiswa_id = $mahasiswa['id'];
$prodi_mhs = $mahasiswa['prodi'];
$success = '';
$error = '';

// Proses Pilih / Ajukan Mata Kuliah ke KRS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ambil_mk'])) {
    $mata_kuliah_id = isset($_POST['mata_kuliah_id']) ? trim($_POST['mata_kuliah_id']) : null;

    if (!empty($mata_kuliah_id)) {
        try {
            // Cek apakah mata kuliah ini sudah pernah diambil mahasiswa ini
            $stmt_cek = $pdo->prepare("SELECT * FROM krs WHERE mahasiswa_id = ? AND mata_kuliah_id = ?");
            $stmt_cek->execute([$mahasiswa_id, $mata_kuliah_id]);
            
            if ($stmt_cek->rowCount() > 0) {
                $error = "Mata kuliah ini sudah ada di KRS kamu!";
            } else {
                // Masukkan langsung ke krs dengan mata_kuliah_id
                $stmt_insert = $pdo->prepare("INSERT INTO krs (mahasiswa_id, mata_kuliah_id, status) VALUES (?, ?, 'Pending')");
                $stmt_insert->execute([$mahasiswa_id, $mata_kuliah_id]);
                $success = "Mata kuliah berhasil diajukan ke KRS!";
            }
        } catch (PDOException $e) {
            $error = "Gagal mengambil mata kuliah: " . $e->getMessage();
        }
    } else {
        $error = "Pilihan mata kuliah tidak valid!";
    }
}

// Proses Batalkan Mata Kuliah di KRS
if (isset($_GET['batalkan'])) {
    $krs_id = $_GET['batalkan'];
    try {
        $stmt_del = $pdo->prepare("DELETE FROM krs WHERE id = ? AND mahasiswa_id = ?");
        $stmt_del->execute([$krs_id, $mahasiswa_id]);
        header("Location: krs.php?status=sukses_hapus");
        exit;
    } catch (PDOException $e) {
        $error = "Gagal membatalkan mata kuliah.";
    }
}

// Ambil daftar mata kuliah langsung berdasarkan prodi mahasiswa
$stmt_mk = $pdo->prepare("SELECT * FROM mata_kuliah WHERE prodi = ? ORDER BY semester ASC");
$stmt_mk->execute([$prodi_mhs]);
$daftar_mk = $stmt_mk->fetchAll();

// Ambil KRS yang sudah diambil mahasiswa (Join langsung ke mata_kuliah)
$stmt_krs = $pdo->prepare("
    SELECT krs.id AS krs_id, krs.status, mk.kode_mk, mk.nama_mk, mk.sks 
    FROM krs 
    JOIN mata_kuliah mk ON krs.mata_kuliah_id = mk.id 
    WHERE krs.mahasiswa_id = ?
");
$stmt_krs->execute([$mahasiswa_id]);
$krs_diambil = $stmt_krs->fetchAll();

// Hitung total SKS
$total_sks = 0;
foreach ($krs_diambil as $item) {
    $total_sks += (int)$item['sks'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan KRS - EduSync Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="text-primary fw-bold">Pengajuan Kartu Rencana Studi (KRS)</h2>
                <p class="text-muted mb-0">Mahasiswa: <strong><?= htmlspecialchars($mahasiswa['nama']); ?></strong> (NIM: <?= htmlspecialchars($mahasiswa['nim']); ?>) | Prodi: <?= htmlspecialchars($mahasiswa['prodi']); ?></p>
            </div>
            <div class="d-flex gap-2">
                <a href="cetak_krs.php" class="btn btn-success" target="_blank">📄 Cetak KRS (PDF/Print)</a>
                <a href="index.php" class="btn btn-outline-secondary">Kembali ke Dashboard</a>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Daftar Mata Kuliah Tersedia Berdasarkan Prodi -->
            <div class="col-md-7 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h4 class="card-title text-primary fw-bold mb-3">Mata Kuliah Tersedia</h4>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Kode / Nama MK</th>
                                        <th>SKS</th>
                                        <th>Sem</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($daftar_mk) > 0): ?>
                                        <?php foreach ($daftar_mk as $mk): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($mk['nama_mk']); ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($mk['kode_mk']); ?></small>
                                                </td>
                                                <td><span class="badge bg-secondary"><?= $mk['sks']; ?> SKS</span></td>
                                                <td><?= $mk['semester']; ?></td>
                                                <td>
                                                    <form action="" method="POST" class="d-inline">
                                                        <input type="hidden" name="ambil_mk" value="1">
                                                        <input type="hidden" name="mata_kuliah_id" value="<?= $mk['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-primary py-0 px-2">+ Ambil</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada mata kuliah untuk prodi ini.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KRS yang Dipilih Mahasiswa -->
            <div class="col-md-5">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title text-success fw-bold m-0">KRS Saya</h4>
                            <span class="badge bg-success fs-6">Total: <?= $total_sks; ?> SKS</span>
                        </div>
                        <p class="text-muted small mt-1">Catatan: Menunggu persetujuan Dosen Pembimbing Akademik (PA).</p>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-success">
                                    <tr>
                                        <th>Mata Kuliah</th>
                                        <th>SKS</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($krs_diambil) > 0): ?>
                                        <?php foreach ($krs_diambil as $krs): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($krs['nama_mk']); ?></strong><br>
                                                    <small class="text-muted"><?= $krs['kode_mk']; ?></small>
                                                </td>
                                                <td><?= $krs['sks']; ?> SKS</td>
                                                <td>
                                                    <?php if ($krs['status'] === 'Disetujui'): ?>
                                                        <span class="badge bg-success">Disetujui</span>
                                                    <?php elseif ($krs['status'] === 'Ditolak'): ?>
                                                        <span class="badge bg-danger">Ditolak</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="krs.php?batalkan=<?= $krs['krs_id']; ?>" class="btn btn-sm btn-danger py-0 px-1" onclick="return confirm('Batalkan mata kuliah ini?')">Hapus</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada mata kuliah dipilih dalam KRS.</td>
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