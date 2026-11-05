<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Tangkap filter dari URL (jika ada)
$filter_fakultas = isset($_GET['fakultas']) ? trim($_GET['fakultas']) : '';
$filter_jurusan  = isset($_GET['jurusan']) ? trim($_GET['jurusan']) : '';
$filter_prodi    = isset($_GET['prodi']) ? trim($_GET['prodi']) : '';

// Proses Hapus Mahasiswa
if (isset($_GET['hapus'])) {
    $id_mhs = $_GET['hapus'];
    try {
        $stmt_user = $pdo->prepare("SELECT user_id FROM mahasiswa WHERE id = ?");
        $stmt_user->execute([$id_mhs]);
        $mhs = $stmt_user->fetch();

        if ($mhs && $mhs['user_id']) {
            $stmt_del_user = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt_del_user->execute([$mhs['user_id']]);
        }

        $query_params = [];
        if (!empty($filter_fakultas)) $query_params['fakultas'] = $filter_fakultas;
        if (!empty($filter_jurusan)) $query_params['jurusan'] = $filter_jurusan;
        if (!empty($filter_prodi)) $query_params['prodi'] = $filter_prodi;
        
        $redirect_url = "mahasiswa.php" . (!empty($query_params) ? '?' . http_build_query($query_params) : '');
        header("Location: " . $redirect_url . (strpos($redirect_url, '?') !== false ? '&' : '?') . "status=sukses_hapus");
        exit;
    } catch (PDOException $e) {
        $error = "Gagal menghapus data: " . $e->getMessage();
    }
}

// Ambil daftar unik untuk dropdown filter
$daftar_fakultas = $pdo->query("SELECT DISTINCT fakultas FROM mahasiswa ORDER BY fakultas ASC")->fetchAll(PDO::FETCH_COLUMN);
$daftar_jurusan  = $pdo->query("SELECT DISTINCT jurusan FROM mahasiswa ORDER BY jurusan ASC")->fetchAll(PDO::FETCH_COLUMN);
$daftar_prodi    = $pdo->query("SELECT DISTINCT prodi FROM mahasiswa ORDER BY prodi ASC")->fetchAll(PDO::FETCH_COLUMN);

// Buat Query dinamis berdasarkan filter yang dipilih
$sql = "SELECT m.*, d.nama AS nama_pa FROM mahasiswa m 
        LEFT JOIN dosen d ON m.dosen_pa_id = d.id WHERE 1=1";
$params = [];

if (!empty($filter_fakultas)) {
    $sql .= " AND m.fakultas = ?";
    $params[] = $filter_fakultas;
}
if (!empty($filter_jurusan)) {
    $sql .= " AND m.jurusan = ?";
    $params[] = $filter_jurusan;
}
if (!empty($filter_prodi)) {
    $sql .= " AND m.prodi = ?";
    $params[] = $filter_prodi;
}

$sql .= " ORDER BY m.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftar_mahasiswa = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa - EduSync Admin</title>
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
                <li><a href="mahasiswa.php" class="active">🎓 Manajemen Mahasiswa</a></li>
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
                <h4 class="text-dark fw-bold mb-1">Manajemen Data Mahasiswa</h4>
                <p class="text-muted mb-0 small">Filter, edit, dan kelola data mahasiswa beserta Dosen Pembimbing Akademik (PA).</p>
            </div>
            <div class="d-flex gap-2">
                <a href="tambah_mahasiswa.php" class="btn btn-primary btn-sm px-3">+ Tambah Mahasiswa</a>
                <a href="import_mahasiswa.php" class="btn btn-success btn-sm px-3">📥 Import CSV</a>
            </div>
        </div>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Data mahasiswa berhasil ditambahkan!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses_hapus'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Data mahasiswa berhasil dihapus!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Panel Filter Pencarian -->
        <div class="card bg-white p-3 mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Filter Fakultas</label>
                        <select name="fakultas" class="form-select form-select-sm">
                            <option value="">-- Semua Fakultas --</option>
                            <?php foreach ($daftar_fakultas as $f): ?>
                                <option value="<?= htmlspecialchars($f); ?>" <?= ($filter_fakultas === $f) ? 'selected' : ''; ?>><?= htmlspecialchars($f); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Filter Jurusan</label>
                        <select name="jurusan" class="form-select form-select-sm">
                            <option value="">-- Semua Jurusan --</option>
                            <?php foreach ($daftar_jurusan as $j): ?>
                                <option value="<?= htmlspecialchars($j); ?>" <?= ($filter_jurusan === $j) ? 'selected' : ''; ?>><?= htmlspecialchars($j); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Filter Program Studi</label>
                        <select name="prodi" class="form-select form-select-sm">
                            <option value="">-- Semua Prodi --</option>
                            <?php foreach ($daftar_prodi as $p): ?>
                                <option value="<?= htmlspecialchars($p); ?>" <?= ($filter_prodi === $p) ? 'selected' : ''; ?>><?= htmlspecialchars($p); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Filter Data</button>
                        <a href="mahasiswa.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Daftar Mahasiswa -->
        <div class="card bg-white p-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary">
                            <tr>
                                <th>No</th>
                                <th>NIM & Nama</th>
                                <th>Fakultas / Jurusan</th>
                                <th>Program Studi</th>
                                <th>Angkatan</th>
                                <th>IPK</th>
                                <th>Dosen PA</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($daftar_mahasiswa) > 0): ?>
                                <?php $no = 1; foreach ($daftar_mahasiswa as $mhs): ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($mhs['nim']); ?></strong><br>
                                            <span class="text-secondary"><?= htmlspecialchars($mhs['nama']); ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted d-block"><?= htmlspecialchars($mhs['fakultas']); ?></small>
                                            <strong><?= htmlspecialchars($mhs['jurusan']); ?></strong>
                                        </td>
                                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($mhs['prodi']); ?></span></td>
                                        <td><?= htmlspecialchars($mhs['angkatan']); ?></td>
                                        <td><span class="badge bg-success"><?= number_format($mhs['ipk'], 2); ?></span></td>
                                        <td><?= $mhs['nama_pa'] ? htmlspecialchars($mhs['nama_pa']) : '<span class="text-muted fst-italic">Belum diset</span>'; ?></td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="edit_mahasiswa.php?id=<?= $mhs['id']; ?>" class="btn btn-sm btn-warning py-0 px-2">Edit</a>
                                                <a href="mahasiswa.php?hapus=<?= $mhs['id']; ?><?= !empty($filter_fakultas) ? '&fakultas=' . urlencode($filter_fakultas) : ''; ?><?= !empty($filter_jurusan) ? '&jurusan=' . urlencode($filter_jurusan) : ''; ?><?= !empty($filter_prodi) ? '&prodi=' . urlencode($filter_prodi) : ''; ?>" class="btn btn-sm btn-danger py-0 px-2" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Belum ada data mahasiswa yang sesuai dengan filter.</td>
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