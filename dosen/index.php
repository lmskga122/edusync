<?php
session_start();
require_once '../config/database.php';

// Validasi akses dosen
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dosen') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data dosen yang sedang login
$stmt_dosen = $pdo->prepare("SELECT * FROM dosen WHERE user_id = ?");
$stmt_dosen->execute([$user_id]);
$dosen = $stmt_dosen->fetch();

if (!$dosen) {
    die("Data dosen tidak ditemukan.");
}

$dosen_id = $dosen['id'];

// Hitung statistik ringkas untuk dosen
$total_pending = $pdo->query("SELECT COUNT(DISTINCT mahasiswa_id) FROM krs WHERE status = 'Pending'")->fetchColumn();

$stmt_mhs_bimbingan = $pdo->prepare("SELECT COUNT(*) FROM mahasiswa WHERE dosen_pa_id = ?");
$stmt_mhs_bimbingan->execute([$dosen_id]);
$total_bimbingan = $stmt_mhs_bimbingan->fetchColumn();

// Tentukan menu aktif berdasarkan parameter GET (default: beranda)
$menu = isset($_GET['menu']) ? $_GET['menu'] : 'beranda';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen - EduSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d6efd;
            --bg-body: #f4f6f9;
        }
        body {
            background-color: var(--bg-body);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            min-height: 100vh;
            background: #ffffff;
            border-right: 1px solid #e3e6f0;
            position: fixed;
            width: 260px;
            top: 0;
            left: 0;
            z-index: 100;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05);
        }
        .main-content {
            margin-left: 260px;
            padding: 35px;
        }
        .sidebar-brand {
            padding: 24px 20px;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary-color);
            border-bottom: 1px solid #e3e6f0;
            letter-spacing: 0.5px;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            color: #5a5c69;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.25s ease;
        }
        .sidebar-menu li a:hover, .sidebar-menu li a.active {
            background-color: #f8f9fc;
            color: var(--primary-color);
            border-left: 4px solid var(--primary-color);
        }
        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.075);
            border-radius: 0.5rem;
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: translateY(-2px);
        }
        .top-header {
            background: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05);
        }
    </style>
</head>
<body>

    <!-- SIDEBAR KIRI -->
    <div class="sidebar d-flex flex-column justify-content-between">
        <div>
            <div class="sidebar-brand">
                EduSync <span class="fs-6 text-muted d-block fw-normal">Portal Dosen</span>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php?menu=beranda" class="<?= ($menu == 'beranda') ? 'active' : ''; ?>">🏠 Beranda & Profil</a></li>
                <li><a href="persetujuan_krs.php">📋 Validasi KRS Mahasiswa</a></li>
                <li><a href="index.php?menu=bimbingan" class="<?= ($menu == 'bimbingan') ? 'active' : ''; ?>">👥 Mahasiswa Bimbingan</a></li>
                <li><a href="profil.php">🔑 Ganti Password</a></li>
            </ul>
        </div>
        <div class="p-3 border-top">
            <a href="../auth/logout.php" class="btn btn-outline-danger w-100 btn-sm py-2 fw-semibold">Keluar (Logout)</a>
        </div>
    </div>

    <!-- KONTEN UTAMA -->
    <div class="main-content">
        
        <!-- Header Atas -->
        <div class="d-flex justify-content-between align-items-center mb-4 top-header p-4">
            <div>
                <h4 class="text-dark fw-bold mb-1">Dashboard Dosen Pembimbing Akademik</h4>
                <p class="text-muted mb-0 small">Selamat datang kembali, <strong><?= htmlspecialchars($dosen['nama']); ?></strong> (NIDN: <?= htmlspecialchars($dosen['nidn']); ?>)</p>
            </div>
            <div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fw-semibold">Dosen PA Aktif</span>
            </div>
        </div>

        <!-- KONTEN BERDASARKAN MENU -->
        <?php if ($menu == 'beranda'): ?>
            <!-- MENU 1: BERANDA & STATISTIK -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card bg-white p-3 h-100 border-start border-4 border-warning">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase fw-semibold small mb-2">Pengajuan KRS Pending</h6>
                            <h2 class="fw-bold text-dark mb-3"><?= $total_pending; ?> <span class="fs-6 text-muted fw-normal">Mahasiswa</span></h2>
                            <a href="persetujuan_krs.php" class="btn btn-warning btn-sm text-dark px-3 fw-semibold shadow-sm">Validasi KRS Sekarang &rarr;</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-white p-3 h-100 border-start border-4 border-success">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase fw-semibold small mb-2">Mahasiswa Bimbingan (PA)</h6>
                            <h2 class="fw-bold text-dark mb-3"><?= $total_bimbingan; ?> <span class="fs-6 text-muted fw-normal">Orang</span></h2>
                            <a href="index.php?menu=bimbingan" class="btn btn-success btn-sm px-3 fw-semibold shadow-sm">Lihat Daftar Bimbingan &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PROFIL DOSEN CARD -->
            <div class="card bg-white p-4">
                <div class="card-body">
                    <h5 class="card-title text-dark fw-bold mb-4">Identitas Akademik Dosen</h5>
                    <div class="row">
                        <div class="col-md-8">
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" style="width: 30%;">NIDN</td>
                                    <td class="fw-bold">: <?= htmlspecialchars($dosen['nidn']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nama Lengkap</td>
                                    <td class="fw-bold">: <?= htmlspecialchars($dosen['nama']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Program Studi</td>
                                    <td class="fw-bold">: <span class="badge bg-info text-dark"><?= htmlspecialchars($dosen['prodi'] ?? 'Pendidikan Teknologi Informatika dan Komputer'); ?></span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($menu == 'bimbingan'): ?>
            <!-- MENU 2: MAHASISWA BIMBINGAN -->
            <div class="card bg-white p-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title text-dark fw-bold m-0">Daftar Mahasiswa Bimbingan Akademik (PA)</h4>
                        <span class="badge bg-success px-3 py-2">Total: <?= $total_bimbingan; ?> Mahasiswa</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th>NIM</th>
                                    <th>Nama Mahasiswa</th>
                                    <th>Program Studi</th>
                                    <th>Angkatan</th>
                                    <th class="text-center">IPK</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt_list_mhs = $pdo->prepare("SELECT * FROM mahasiswa WHERE dosen_pa_id = ? ORDER BY nama ASC");
                                $stmt_list_mhs->execute([$dosen_id]);
                                $mhs_bimbingan = $stmt_list_mhs->fetchAll();
                                ?>
                                <?php if (count($mhs_bimbingan) > 0): ?>
                                    <?php foreach ($mhs_bimbingan as $mhs): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($mhs['nim']); ?></code></td>
                                            <td><strong><?= htmlspecialchars($mhs['nama']); ?></strong></td>
                                            <td><?= htmlspecialchars($mhs['prodi']); ?></td>
                                            <td><?= htmlspecialchars($mhs['angkatan']); ?></td>
                                            <td class="text-center"><span class="badge bg-secondary"><?= number_format($mhs['ipk'], 2); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada mahasiswa bimbingan yang ditetapkan untuk Anda.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>