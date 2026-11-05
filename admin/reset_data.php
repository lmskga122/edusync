<?php
session_start();
require_once '../config/database.php';

// Validasi akses admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$success = '';
$error = '';

// Proses Hapus Data Berdasarkan Aksi
if (isset($_GET['aksi'])) {
    $aksi = $_GET['aksi'];

    try {
        if ($aksi === 'semua_mahasiswa') {
            // Hapus akun user yang role-nya mahasiswa (tabel mahasiswa dan krs akan ikut terhapus karena ON DELETE CASCADE)
            $stmt = $pdo->prepare("DELETE FROM users WHERE role = 'mahasiswa'");
            $stmt->execute();
            $success = "Semua data mahasiswa beserta akun loginnya berhasil dibersihkan!";
        } 
        elseif ($aksi === 'semua_dosen') {
            // Hapus akun user yang role-nya dosen
            $stmt = $pdo->prepare("DELETE FROM users WHERE role = 'dosen'");
            $stmt->execute();
            $success = "Semua data dosen beserta akun loginnya berhasil dibersihkan!";
        }
    } catch (PDOException $e) {
        $error = "Gagal menghapus data: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset & Bersihkan Data - Admin EduSync</title>
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
                <li><a href="index.php">🏠 Dashboard Utama</a></li>
                <li><a href="mata_kuliah.php">📚 Manajemen Mata Kuliah</a></li>
                <li><a href="mahasiswa.php">🎓 Manajemen Mahasiswa</a></li>
                <li><a href="dosen.php">👨‍🏫 Manajemen Dosen & PA</a></li>
                <li><a href="krs_approval.php">📋 Validasi & Monitoring KRS</a></li>
                <li><a href="reset_data.php" class="active">⚙️ Reset & Bersihkan Data</a></li>
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
                <h4 class="text-dark fw-bold mb-1">Pusat Reset & Pembersihan Data Master</h4>
                <p class="text-muted mb-0 small">Hapus seluruh data mahasiswa atau dosen beserta akun loginnya secara masal.</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-outline-secondary btn-sm px-3">&larr; Kembali</a>
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

        <!-- KARTU PILIHAN RESET -->
        <div class="row g-4">
            <!-- Reset Mahasiswa -->
            <div class="col-md-6">
                <div class="card bg-white p-4 h-100 border-start border-4 border-danger">
                    <div class="card-body">
                        <h5 class="fw-bold text-danger mb-3">⚠️ Bersihkan Seluruh Data Mahasiswa</h5>
                        <p class="text-muted small mb-4">
                            Tindakan ini akan menghapus <strong>seluruh data mahasiswa, riwayat KRS, beserta akun login (users)</strong> mahasiswa secara permanen dari database. Tindakan ini tidak dapat dibatalkan.
                        </p>
                        <a href="reset_data.php?aksi=semua_mahasiswa" class="btn btn-danger btn-sm px-4 py-2 fw-semibold" onclick="return confirm('PERINGATAN: Apakah Anda yakin ingin menghapus SEMUA data mahasiswa dan akunnya secara permanen?')">
                            Hapus Semua Mahasiswa & Akun
                        </a>
                    </div>
                </div>
            </div>

            <!-- Reset Dosen -->
            <div class="col-md-6">
                <div class="card bg-white p-4 h-100 border-start border-4 border-danger">
                    <div class="card-body">
                        <h5 class="fw-bold text-danger mb-3">⚠️ Bersihkan Seluruh Data Dosen</h5>
                        <p class="text-muted small mb-4">
                            Tindakan ini akan menghapus <strong>seluruh data dosen beserta akun login (users)</strong> dosen secara permanen dari database. Pastikan tidak ada relasi bimbingan aktif sebelum menghapus.
                        </p>
                        <a href="reset_data.php?aksi=semua_dosen" class="btn btn-danger btn-sm px-4 py-2 fw-semibold" onclick="return confirm('PERINGATAN: Apakah Anda yakin ingin menghapus SEMUA data dosen dan akunnya secara permanen?')">
                            Hapus Semua Dosen & Akun
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>