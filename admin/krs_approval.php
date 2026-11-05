<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$stmt_krs = $pdo->query("
    SELECT m.id AS mahasiswa_id, m.nama, m.nim, m.prodi, 
           GROUP_CONCAT(mk.nama_mk SEPARATOR ', ') AS daftar_matkul,
           SUM(mk.sks) AS total_sks,
           MAX(krs.status) AS status_krs
    FROM krs
    JOIN mahasiswa m ON krs.mahasiswa_id = m.id
    JOIN mata_kuliah mk ON krs.mata_kuliah_id = mk.id
    GROUP BY m.id, m.nama, m.nim, m.prodi
    ORDER BY m.nama ASC
");
$daftar_krs = $stmt_krs->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi & Monitoring KRS - Admin EduSync</title>
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
                <li><a href="krs_approval.php" class="active">📋 Validasi & Monitoring KRS</a></li>
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
                <h4 class="text-dark fw-bold mb-1">Monitoring & Validasi KRS Mahasiswa</h4>
                <p class="text-muted mb-0 small">Pantau status pengajuan Kartu Rencana Studi (KRS) seluruh mahasiswa secara real-time.</p>
            </div>
        </div>

        <!-- TABEL MONITORING KRS -->
        <div class="card bg-white p-4">
            <div class="card-body">
                <h5 class="card-title text-dark fw-bold mb-3">Rekapitulasi Pengajuan KRS Mahasiswa</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary">
                            <tr>
                                <th>Mahasiswa (NIM)</th>
                                <th>Program Studi</th>
                                <th>Mata Kuliah yang Diambil</th>
                                <th class="text-center">Total SKS</th>
                                <th class="text-center">Status KRS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($daftar_krs) > 0): ?>
                                <?php foreach ($daftar_krs as $row): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($row['nama']); ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($row['nim']); ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($row['prodi']); ?></td>
                                        <td>
                                            <span class="text-dark small"><?= htmlspecialchars($row['daftar_matkul']); ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold <?= ($row['total_sks'] > 24) ? 'text-danger' : 'text-primary'; ?>">
                                                <?= $row['total_sks']; ?> SKS
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['status_krs'] === 'Disetujui'): ?>
                                                <span class="badge bg-success px-3 py-2">✔ Disetujui</span>
                                            <?php elseif ($row['status_krs'] === 'Ditolak'): ?>
                                                <span class="badge bg-danger px-3 py-2">✖ Ditolak</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark px-3 py-2">⏳ Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada data pengajuan KRS dari mahasiswa.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>