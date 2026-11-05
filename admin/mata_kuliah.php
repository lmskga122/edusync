<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$error = '';
$success = '';

$filter_prodi = isset($_GET['prodi']) ? trim($_GET['prodi']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_manual'])) {
    $kode_mk = trim($_POST['kode_mk']);
    $nama_mk = trim($_POST['nama_mk']);
    $fakultas = trim($_POST['fakultas']);
    $jurusan = trim($_POST['jurusan']);
    $prodi = trim($_POST['prodi']);
    $sks = trim($_POST['sks']);
    $semester = trim($_POST['semester']);

    if (!empty($kode_mk) && !empty($nama_mk) && !empty($prodi)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO mata_kuliah (kode_mk, nama_mk, fakultas, jurusan, prodi, sks, semester) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$kode_mk, $nama_mk, $fakultas, $jurusan, $prodi, $sks, $semester]);
            $success = "Mata kuliah berhasil ditambahkan ke prodi $prodi!";
        } catch (PDOException $e) {
            $error = "Gagal menambah mata kuliah (Kode MK mungkin sudah ada di prodi tersebut).";
        }
    } else {
        $error = "Kolom wajib harus diisi!";
    }
}

if (isset($_GET['hapus'])) {
    $id_mk = $_GET['hapus'];
    try {
        $stmt = $pdo->prepare("DELETE FROM mata_kuliah WHERE id = ?");
        $stmt->execute([$id_mk]);
        header("Location: mata_kuliah.php" . (!empty($filter_prodi) ? "?prodi=" . urlencode($filter_prodi) : ""));
        exit;
    } catch (PDOException $e) {
        $error = "Gagal menghapus mata kuliah.";
    }
}

$stmt_prodi_list = $pdo->query("SELECT DISTINCT prodi FROM mata_kuliah ORDER BY prodi ASC");
$daftar_prodi = $stmt_prodi_list->fetchAll(PDO::FETCH_COLUMN);

if (!empty($filter_prodi)) {
    $stmt_mk = $pdo->prepare("SELECT * FROM mata_kuliah WHERE prodi = ? ORDER BY semester ASC, kode_mk ASC");
    $stmt_mk->execute([$filter_prodi]);
} else {
    $stmt_mk = $pdo->query("SELECT * FROM mata_kuliah ORDER BY prodi ASC, semester ASC, kode_mk ASC");
}
$daftar_mk = $stmt_mk->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Mata Kuliah - EduSync Admin</title>
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
                <li><a href="mata_kuliah.php" class="active">📚 Manajemen Mata Kuliah</a></li>
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
                <h4 class="text-dark fw-bold mb-1">Manajemen Mata Kuliah per Program Studi</h4>
                <p class="text-muted mb-0 small">Kelola kurikulum mata kuliah secara spesifik agar terpisah antar program studi.</p>
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

        <div class="row g-4">
            <!-- Form Input Mata Kuliah -->
            <div class="col-md-4">
                <div class="card bg-white p-3">
                    <div class="card-body">
                        <h5 class="card-title text-primary fw-bold mb-3">Tambah Mata Kuliah</h5>
                        <form action="" method="POST">
                            <input type="hidden" name="tambah_manual" value="1">
                            <div class="mb-2">
                                <label class="form-label small">Kode MK</label>
                                <input type="text" class="form-control form-control-sm" name="kode_mk" placeholder="cth: PTI1234" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Nama Mata Kuliah</label>
                                <input type="text" class="form-control form-control-sm" name="nama_mk" placeholder="cth: Kecerdasan Buatan" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Fakultas</label>
                                <input type="text" class="form-control form-control-sm" name="fakultas" value="Fakultas Teknik" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Jurusan</label>
                                <input type="text" class="form-control form-control-sm" name="jurusan" value="Pendidikan Teknik Informatika dan Komputer" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Program Studi (Prodi)</label>
                                <input type="text" class="form-control form-control-sm" name="prodi" placeholder="cth: Pendidikan Teknologi Informatika" required>
                                <div class="form-text text-muted" style="font-size: 0.75rem;">Pastikan penulisan nama prodi sama persis dengan data mahasiswa.</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label small">SKS</label>
                                    <input type="number" class="form-control form-control-sm" name="sks" min="1" max="6" value="3" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Semester</label>
                                    <input type="number" class="form-control form-control-sm" name="semester" min="1" max="8" value="5" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">Simpan Mata Kuliah</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tabel Daftar & Filter Mata Kuliah -->
            <div class="col-md-8">
                <div class="card bg-white p-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title text-primary fw-bold m-0">Daftar Mata Kuliah</h4>
                            
                            <!-- Filter Berdasarkan Prodi -->
                            <form method="GET" action="" class="d-flex gap-2">
                                <select name="prodi" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">-- Semua Program Studi --</option>
                                    <?php foreach ($daftar_prodi as $p): ?>
                                        <option value="<?= htmlspecialchars($p); ?>" <?= ($filter_prodi === $p) ? 'selected' : ''; ?>><?= htmlspecialchars($p); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (!empty($filter_prodi)): ?>
                                    <a href="mata_kuliah.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                                <?php endif; ?>
                            </form>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-secondary">
                                    <tr>
                                        <th>No</th>
                                        <th>Kode & MK</th>
                                        <th>Program Studi & Fakultas</th>
                                        <th>SKS</th>
                                        <th>Sem</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($daftar_mk) > 0): ?>
                                        <?php $no = 1; foreach ($daftar_mk as $mk): ?>
                                            <tr>
                                                <td><?= $no++; ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($mk['kode_mk']); ?></strong><br>
                                                    <span class="text-secondary"><?= htmlspecialchars($mk['nama_mk']); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info text-dark mb-1"><?= htmlspecialchars($mk['prodi']); ?></span><br>
                                                    <small class="text-muted"><?= htmlspecialchars($mk['fakultas']); ?></small>
                                                </td>
                                                <td><span class="badge bg-secondary"><?= $mk['sks']; ?> SKS</span></td>
                                                <td><?= $mk['semester']; ?></td>
                                                <td class="text-center">
                                                    <a href="mata_kuliah.php?hapus=<?= $mk['id']; ?><?= !empty($filter_prodi) ? '&prodi=' . urlencode($filter_prodi) : ''; ?>" class="btn btn-sm btn-danger py-0 px-2" onclick="return confirm('Yakin ingin menghapus mata kuliah ini?')">Hapus</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">Belum ada mata kuliah yang terdaftar<?= !empty($filter_prodi) ? ' untuk program studi ini' : ''; ?>.</td>
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