<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$error = '';
$success = '';

// Proses Tambah Dosen Manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_dosen'])) {
    $nidn = trim($_POST['nidn']);
    $nama = trim($_POST['nama']);
    $fakultas = trim($_POST['fakultas']);
    $prodi = trim($_POST['prodi']);

    if (!empty($nidn) && !empty($nama)) {
        try {
            $stmt_cek = $pdo->prepare("SELECT id FROM dosen WHERE nidn = ?");
            $stmt_cek->execute([$nidn]);
            if ($stmt_cek->rowCount() > 0) {
                throw new Exception("NIDN tersebut sudah terdaftar!");
            }

            $username = $nidn;
            $password_default = password_hash($nidn, PASSWORD_DEFAULT);

            $stmt_user = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, 'dosen')");
            $stmt_user->execute([$username, $password_default]);
            $user_id = $pdo->lastInsertId();

            $stmt_dosen = $pdo->prepare("INSERT INTO dosen (nidn, nama, fakultas, prodi, user_id) VALUES (?, ?, ?, ?, ?)");
            $stmt_dosen->execute([$nidn, $nama, $fakultas, $prodi, $user_id]);

            $success = "Dosen berhasil ditambahkan! Akun login dibuat dengan username & password awal: <strong>$nidn</strong>";
        } catch (Exception $e) {
            $error = "Gagal menambah dosen: " . $e->getMessage();
        }
    } else {
        $error = "NIDN dan Nama Dosen wajib diisi!";
    }
}

// Proses Hapus Dosen
if (isset($_GET['hapus'])) {
    $id_dosen = $_GET['hapus'];
    try {
        $stmt_user = $pdo->prepare("SELECT user_id FROM dosen WHERE id = ?");
        $stmt_user->execute([$id_dosen]);
        $dosen = $stmt_user->fetch();

        if ($dosen && $dosen['user_id']) {
            $stmt_del_user = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt_del_user->execute([$dosen['user_id']]);
        }

        header("Location: dosen.php?status=sukses_hapus");
        exit;
    } catch (PDOException $e) {
        $error = "Gagal menghapus data dosen.";
    }
}

// Ambil daftar dosen
$daftar_dosen = $pdo->query("SELECT * FROM dosen ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Dosen - EduSync Admin</title>
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
                <li><a href="dosen.php" class="active">👨‍🏫 Manajemen Dosen & PA</a></li>
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
                <h4 class="text-dark fw-bold mb-1">Manajemen Data Dosen</h4>
                <p class="text-muted mb-0 small">Kelola data dosen, NIDN, program studi, dan akun login Pembimbing Akademik.</p>
            </div>
            <div>
                <a href="import_dosen.php" class="btn btn-success btn-sm px-3">📥 Import CSV</a>
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

        <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses_hapus'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Data dosen berhasil dihapus!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Form Tambah Dosen -->
            <div class="col-md-4">
                <div class="card bg-white p-3">
                    <div class="card-body">
                        <h5 class="card-title text-primary fw-bold mb-3">Tambah Dosen Baru</h5>
                        <form action="" method="POST">
                            <input type="hidden" name="tambah_dosen" value="1">
                            <div class="mb-2">
                                <label class="form-label small">NIDN</label>
                                <input type="text" class="form-control form-control-sm" name="nidn" placeholder="cth: 0012345678" required>
                                <div class="form-text small">Digunakan sebagai username & password awal login.</div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Nama Lengkap & Gelar</label>
                                <input type="text" class="form-control form-control-sm" name="nama" placeholder="cth: Dr. Nama Dosen, M.Kom." required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Fakultas</label>
                                <input type="text" class="form-control form-control-sm" name="fakultas" value="Fakultas Teknik" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Program Studi</label>
                                <input type="text" class="form-control form-control-sm" name="prodi" placeholder="cth: Pendidikan Teknologi Informatika" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">Simpan Dosen</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tabel Daftar Dosen -->
            <div class="col-md-8">
                <div class="card bg-white p-4">
                    <div class="card-body">
                        <h4 class="card-title text-primary fw-bold mb-3">Daftar Dosen Terdaftar</h4>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-secondary">
                                    <tr>
                                        <th>No</th>
                                        <th>NIDN & Nama</th>
                                        <th>Fakultas / Prodi</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($daftar_dosen) > 0): ?>
                                        <?php $no = 1; foreach ($daftar_dosen as $d): ?>
                                            <tr>
                                                <td><?= $no++; ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($d['nidn']); ?></strong><br>
                                                    <span class="text-secondary"><?= htmlspecialchars($d['nama']); ?></span>
                                                </td>
                                                <td>
                                                    <small class="text-muted d-block"><?= htmlspecialchars($d['fakultas']); ?></small>
                                                    <span class="badge bg-info text-dark"><?= htmlspecialchars($d['prodi']); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="dosen.php?hapus=<?= $d['id']; ?>" class="btn btn-sm btn-danger py-0 px-2" onclick="return confirm('Yakin ingin menghapus dosen ini?')">Hapus</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Belum ada data dosen terdaftar.</td>
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