<?php
session_start();
require_once '../config/database.php';

// Validasi akses mahasiswa
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Ambil data mahasiswa beserta informasi Dosen PA-nya menggunakan JOIN
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
$prodi_mhs = $mahasiswa['prodi'];

// Inisialisasi awal variabel grouped_mk
$grouped_mk = [];

// 2. Ambil seluruh daftar mata kuliah dari semester 1 sampai akhir berdasarkan Prodi
$stmt_mk_all = $pdo->prepare("SELECT * FROM mata_kuliah WHERE prodi = ? ORDER BY semester ASC, kode_mk ASC");
$stmt_mk_all->execute([$prodi_mhs]);
$daftar_mk_semua = $stmt_mk_all->fetchAll();

if ($daftar_mk_semua) {
    foreach ($daftar_mk_semua as $mk) {
        $grouped_mk[$mk['semester']][] = $mk;
    }
}

// 3. Ambil KRS mahasiswa (baik yang Pending maupun Disetujui) - DIPERBAIKI KE mata_kuliah_id
$stmt_krs = $pdo->prepare("
    SELECT krs.id AS krs_id, krs.status, mk.* FROM krs 
    JOIN mata_kuliah mk ON krs.mata_kuliah_id = mk.id 
    WHERE krs.mahasiswa_id = ?
");
$stmt_krs->execute([$mahasiswa_id]);
$krs_diambil = $stmt_krs->fetchAll();

// Hitung total SKS yang diambil
$total_sks = 0;
foreach ($krs_diambil as $item) {
    $total_sks += (int)$item['sks'];
}

// Tentukan menu aktif berdasarkan parameter GET (default: beranda)
$menu = isset($_GET['menu']) ? $_GET['menu'] : 'beranda';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - EduSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: #ffffff;
            border-right: 1px solid #dee2e6;
            position: fixed;
            width: 260px;
            top: 0;
            left: 0;
            z-index: 100;
        }
        .main-content {
            margin-left: 260px;
            padding: 30px;
        }
        .sidebar-brand {
            padding: 20px;
            font-size: 1.25rem;
            font-weight: bold;
            color: #0d6efd;
            border-bottom: 1px solid #dee2e6;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        .sidebar-menu li a {
            display: block;
            padding: 12px 20px;
            color: #495057;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        .sidebar-menu li a:hover, .sidebar-menu li a.active {
            background-color: #e7f1ff;
            color: #0d6efd;
            border-left: 4px solid #0d6efd;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR DI SEBELAH KIRI -->
    <div class="sidebar d-flex flex-column justify-content-between">
        <div>
            <div class="sidebar-brand">
                EduSync <span class="fs-6 text-muted d-block fw-normal">Portal Mahasiswa</span>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php?menu=beranda" class="<?= ($menu == 'beranda') ? 'active' : ''; ?>">🏠 Beranda & Profil</a></li>
                <li><a href="index.php?menu=krs" class="<?= ($menu == 'krs') ? 'active' : ''; ?>">📋 Kelola KRS</a></li>
                <li><a href="index.php?menu=krs_sementara" class="<?= ($menu == 'krs_sementara') ? 'active' : ''; ?>">⏳ KRS Sementara (Pending)</a></li>
                <li><a href="index.php?menu=kurikulum" class="<?= ($menu == 'kurikulum') ? 'active' : ''; ?>">📚 Mata Kuliah Ditawarkan</a></li>
                <li><a href="profil.php">🔑 Ganti Password</a></li>
            </ul>
        </div>
        <div class="p-3 border-top">
            <a href="../auth/logout.php" class="btn btn-outline-danger w-100 btn-sm">Keluar (Logout)</a>
        </div>
    </div>

    <!-- KONTEN UTAMA DI SEBELAH KANAN -->
    <div class="main-content">
        
        <!-- Header Atas -->
        <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
            <div>
                <h4 class="text-primary fw-bold mb-0">Sistem Informasi Akademik</h4>
                <small class="text-muted">Halo, <strong><?= htmlspecialchars($mahasiswa['nama']); ?></strong> (NIM: <?= htmlspecialchars($mahasiswa['nim']); ?>)</small>
            </div>
            <div>
                <span class="badge bg-success px-3 py-2 fs-6">IPK: <?= number_format($mahasiswa['ipk'], 2); ?></span>
            </div>
        </div>

        <!-- ISI KONTEN BERDASARKAN MENU YANG DIPILIH -->
        
        <?php if ($menu == 'beranda'): ?>
            <!-- MENU 1: BERANDA & PROFIL -->
            <div class="row g-4">
                <div class="col-md-7">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <h5 class="card-title text-primary fw-bold mb-3">Identitas Akademik Mahasiswa</h5>
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" style="width: 35%;">NIM</td>
                                    <td class="fw-bold">: <?= htmlspecialchars($mahasiswa['nim']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nama Lengkap</td>
                                    <td class="fw-bold">: <?= htmlspecialchars($mahasiswa['nama']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Fakultas / Jurusan</td>
                                    <td class="fw-bold">: <?= htmlspecialchars($mahasiswa['fakultas']); ?> / <?= htmlspecialchars($mahasiswa['jurusan']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Program Studi</td>
                                    <td class="fw-bold">: <span class="badge bg-info text-dark"><?= htmlspecialchars($mahasiswa['prodi']); ?></span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Angkatan</td>
                                    <td class="fw-bold">: <?= htmlspecialchars($mahasiswa['angkatan']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card shadow-sm border-0 h-100 bg-white">
                        <div class="card-body">
                            <h5 class="card-title text-success fw-bold mb-3">Dosen Pembimbing Akademik (PA)</h5>
                            <?php if (!empty($mahasiswa['nama_pa'])): ?>
                                <div class="mt-3">
                                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($mahasiswa['nama_pa']); ?></h6>
                                    <p class="text-muted small mb-2">NIDN: <?= htmlspecialchars($mahasiswa['nidn_pa']); ?></p>
                                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1">Dosen PA Aktif</span>
                                </div>
                            <?php else: ?>
                                <div class="text-muted fst-italic py-3">
                                    Belum ada Dosen PA yang ditetapkan oleh Admin.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($menu == 'krs'): ?>
            <!-- MENU 2: KELOLA KRS -->
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title text-primary fw-bold m-0">Kelola Pengambilan KRS</h4>
                        <a href="krs.php" class="btn btn-primary btn-sm">Buka Halaman Form KRS Penuh</a>
                    </div>
                    <p class="text-muted">Ringkasan mata kuliah yang sudah kamu ambil dalam KRS:</p>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-primary">
                                <tr>
                                    <th>Kode MK</th>
                                    <th>Mata Kuliah</th>
                                    <th>SKS</th>
                                    <th>Semester</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($krs_diambil) > 0): ?>
                                    <?php foreach ($krs_diambil as $krs): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($krs['kode_mk']); ?></strong></td>
                                            <td><?= htmlspecialchars($krs['nama_mk']); ?></td>
                                            <td><?= $krs['sks']; ?> SKS</td>
                                            <td>Sem <?= $krs['semester']; ?></td>
                                            <td>
                                                <?php if ($krs['status'] === 'Disetujui'): ?>
                                                    <span class="badge bg-success">Disetujui</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted py-3">Belum ada mata kuliah diambil.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($menu == 'krs_sementara'): ?>
            <!-- MENU 3: KRS SEMENTARA (PENDING) -->
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h4 class="card-title text-warning fw-bold mb-3">KRS Sementara (Belum Di-approve Dosen PA)</h4>
                    <p class="text-muted">Berikut adalah daftar mata kuliah di KRS kamu yang statusnya masih menunggu persetujuan dari Dosen Pembimbing Akademik:</p>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-warning">
                                <tr>
                                    <th>Kode MK</th>
                                    <th>Mata Kuliah</th>
                                    <th>SKS</th>
                                    <th>Semester</th>
                                    <th>Status Persetujuan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $pending_ada = false;
                                foreach ($krs_diambil as $krs) {
                                    if ($krs['status'] !== 'Disetujui') {
                                        $pending_ada = true;
                                        echo "<tr>
                                            <td><strong>{$krs['kode_mk']}</strong></td>
                                            <td>{$krs['nama_mk']}</td>
                                            <td>{$krs['sks']} SKS</td>
                                            <td>Sem {$krs['semester']}</td>
                                            <td><span class='badge bg-warning text-dark'>Pending</span></td>
                                        </tr>";
                                    }
                                }
                                if (!$pending_ada) {
                                    echo "<tr><td colspan='5' class='text-center text-muted py-3'>Tidak ada mata kuliah berstatus Pending saat ini.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($menu == 'kurikulum'): ?>
            <!-- MENU 4: MATA KULIAH DITAWARKAN -->
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h4 class="card-title text-secondary fw-bold mb-3">Kurikulum Mata Kuliah Ditawarkan (Semester 1 - Akhir)</h4>
                    <?php if (!empty($grouped_mk) && count($grouped_mk) > 0): ?>
                        <?php foreach ($grouped_mk as $sem => $mks): ?>
                            <div class="mb-4">
                                <h6 class="fw-bold text-dark bg-light p-2 rounded border-start border-4 border-primary">Semester <?= $sem; ?></h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 15%;">Kode MK</th>
                                                <th>Nama Mata Kuliah</th>
                                                <th style="width: 10%;">SKS</th>
                                                <th style="width: 25%;">Program Studi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($mks as $item): ?>
                                                <tr>
                                                    <td><code><?= htmlspecialchars($item['kode_mk']); ?></code></td>
                                                    <td><?= htmlspecialchars($item['nama_mk']); ?></td>
                                                    <td><?= $item['sks']; ?> SKS</td>
                                                    <td><small class="text-muted"><?= htmlspecialchars($item['prodi']); ?></small></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">Belum ada data mata kuliah yang ditawarkan untuk Program Studi ini.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>