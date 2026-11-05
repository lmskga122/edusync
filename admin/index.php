<?php
session_start();
require_once '../config/database.php';

// Validasi akses admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Ambil statistik ringkas untuk admin
$total_mhs = $pdo->query("SELECT COUNT(*) FROM mahasiswa")->fetchColumn();
$total_dosen = $pdo->query("SELECT COUNT(*) FROM dosen")->fetchColumn();
$total_mk = $pdo->query("SELECT COUNT(*) FROM mata_kuliah")->fetchColumn();
$total_krs_pending = $pdo->query("SELECT COUNT(*) FROM krs WHERE status = 'Pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - EduSync</title>
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
                EduSync <span class="fs-6 text-muted d-block fw-normal">Portal Admin</span>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active">🏠 Dashboard Utama</a></li>
                <li><a href="mata_kuliah.php">📚 Manajemen Mata Kuliah</a></li>
                <li><a href="mahasiswa.php">🎓 Manajemen Mahasiswa</a></li>
                <li><a href="dosen.php">👨‍🏫 Manajemen Dosen & PA</a></li>
                <li><a href="krs_approval.php">📋 Validasi & Monitoring KRS</a></li>
                <li><a href="reset_data.php">⚙️ Reset & Bersihkan Data</a></li>
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
                <h4 class="text-dark fw-bold mb-1">Dashboard Pusat Kendali Akademik</h4>
                <p class="text-muted mb-0 small">Kelola seluruh modul, data master, dan fungsi akademik universitas dari satu tempat.</p>
            </div>
            <div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fw-semibold">Administrator</span>
            </div>
        </div>

        <!-- KARTU STATISTIK RINGKAS -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card bg-white p-3 h-100 border-start border-4 border-primary">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase fw-semibold small mb-2">Total Mahasiswa</h6>
                        <h3 class="fw-bold text-dark mb-0"><?= $total_mhs; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-white p-3 h-100 border-start border-4 border-success">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase fw-semibold small mb-2">Total Dosen</h6>
                        <h3 class="fw-bold text-dark mb-0"><?= $total_dosen; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-white p-3 h-100 border-start border-4 border-info">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase fw-semibold small mb-2">Mata Kuliah</h6>
                        <h3 class="fw-bold text-dark mb-0"><?= $total_mk; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-white p-3 h-100 border-start border-4 border-warning">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase fw-semibold small mb-2">KRS Pending</h6>
                        <h3 class="fw-bold text-dark mb-0"><?= $total_krs_pending; ?></h3>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>