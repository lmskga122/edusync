<?php
session_start();
require_once '../config/database.php';

// Validasi akses mahasiswa
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data mahasiswa beserta Dosen PA
$stmt_mhs = $pdo->prepare("
    SELECT m.*, d.nama AS nama_pa, d.nidn AS nidn_pa 
    FROM mahasiswa m 
    LEFT JOIN dosen d ON m.dosen_pa_id = d.id 
    WHERE m.user_id = ?
");
$stmt_mhs->execute([$user_id]);
$mahasiswa = $stmt_mhs->fetch();

if (!$mahasiswa) {
    die("Data mahasiswa tidak ditemukan.");
}

$mahasiswa_id = $mahasiswa['id'];

// Ambil daftar KRS yang diambil mahasiswa (Join langsung ke mata_kuliah)
$stmt_krs = $pdo->prepare("
    SELECT krs.id AS krs_id, krs.status, mk.kode_mk, mk.nama_mk, mk.sks, mk.semester 
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
    <title>Cetak Kartu Rencana Studi (KRS) - EduSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #ffffff;
            color: #000000;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body class="py-5" onload="window.print()">

    <div class="container" style="max-width: 800px;">
        <!-- Tombol Kembali / Cetak Manual (Hilang saat diprint) -->
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <a href="krs.php" class="btn btn-secondary btn-sm">&larr; Kembali ke Pengisian KRS</a>
            <button onclick="window.print()" class="btn btn-primary btn-sm">🖨 Cetak Ulang / Simpan PDF</button>
        </div>

        <!-- Kop Surat / Header KRS -->
        <div class="text-center border-bottom pb-3 mb-4">
            <h3 class="fw-bold text-uppercase m-0">Universitas Negeri Medan</h3>
            <h5 class="fw-semibold text-muted m-0">Portal Akademik EduSync</h5>
            <p class="small text-secondary mb-0">Jl. Willem Iskandar / Pasar V, Medan Estate, Deli Serdang</p>
            <h4 class="fw-bold mt-3 text-uppercase">Kartu Rencana Studi (KRS)</h4>
        </div>

        <!-- Informasi Mahasiswa -->
        <div class="row mb-4">
            <div class="col-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td style="width: 35%;" class="text-muted">NIM</td>
                        <td class="fw-bold">: <?= htmlspecialchars($mahasiswa['nim']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Nama Mahasiswa</td>
                        <td class="fw-bold">: <?= htmlspecialchars($mahasiswa['nama']); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td style="width: 35%;" class="text-muted">Program Studi</td>
                        <td class="fw-bold">: <?= htmlspecialchars($mahasiswa['prodi']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Angkatan</td>
                        <td class="fw-bold">: <?= htmlspecialchars($mahasiswa['angkatan']); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Tabel Mata Kuliah KRS -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle">
                <thead class="table-light text-center">
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 15%;">Kode MK</th>
                        <th>Mata Kuliah</th>
                        <th style="width: 10%;">Semester</th>
                        <th style="width: 10%;">SKS</th>
                        <th style="width: 15%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($krs_diambil) > 0): ?>
                        <?php $no = 1; foreach ($krs_diambil as $krs): ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td class="text-center"><code><?= htmlspecialchars($krs['kode_mk']); ?></code></td>
                                <td><?= htmlspecialchars($krs['nama_mk']); ?></td>
                                <td class="text-center"><?= $krs['semester']; ?></td>
                                <td class="text-center"><?= $krs['sks']; ?></td>
                                <td class="text-center">
                                    <?php if ($krs['status'] === 'Disetujui'): ?>
                                        <span class="badge bg-success">Disetujui</span>
                                    <?php elseif ($krs['status'] === 'Ditolak'): ?>
                                        <span class="badge bg-danger">Ditolak</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">KRS Sementara (Pending)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Belum ada mata kuliah yang diambil dalam KRS.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <td colspan="4" class="text-end fw-bold">Total SKS yang Diambil:</td>
                        <td colspan="2" class="fw-bold text-primary"><?= $total_sks; ?> SKS</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Tanda Tangan / Validasi -->
        <div class="row mt-5">
            <div class="col-6 text-center">
                <p class="mb-5">Mengetahui,<br>Mahasiswa Ybs,</p>
                <br><br>
                <p class="fw-bold mb-0 text-decoration-underline"><?= htmlspecialchars($mahasiswa['nama']); ?></p>
                <p class="text-muted small">NIM. <?= htmlspecialchars($mahasiswa['nim']); ?></p>
            </div>
            <div class="col-6 text-center">
                <p class="mb-5">Medan, <?= date('d F Y'); ?><br>Dosen Pembimbing Akademik (PA),</p>
                <br><br>
                <p class="fw-bold mb-0 text-decoration-underline"><?= htmlspecialchars($mahasiswa['nama_pa'] ?? '___________________________'); ?></p>
                <p class="text-muted small">NIDN. <?= htmlspecialchars($mahasiswa['nidn_pa'] ?? '-'); ?></p>
            </div>
        </div>

    </div>

</body>
</html>