<?php
require_once 'config/database.php';

$username = 'admin';
// Password baru yang di-hash: 'password123'
$password_baru = password_hash('password123', PASSWORD_DEFAULT);
$nama = 'Administrator Utama';
$role = 'admin';

try {
    // 1. Cek apakah username 'admin' sudah ada di tabel users
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        // Jika sudah ada, update password dan pastikan rolenya 'admin'
        $stmt_update = $pdo->prepare("UPDATE users SET password = ?, role = ?, nama_lengkap = ? WHERE username = ?");
        $stmt_update->execute([$password_baru, $role, $nama, $username]);
        echo "<h3 style='color: green;'>✅ Akun admin berhasil di-reset ulang!</h3>";
    } else {
        // Jika belum ada, masukkan sebagai data baru
        $stmt_insert = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
        $stmt_insert->execute([$username, $password_baru, $nama, $role]);
        echo "<h3 style='color: green;'>✅ Akun admin berhasil dibuat baru!</h3>";
    }

    echo "<p>Sekarang kamu bisa login menggunakan:</p>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> admin</li>";
    echo "<li><strong>Password:</strong> password123</li>";
    echo "</ul>";
    echo "<br><a href='auth/login.php' style='padding: 10px 15px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px;'>Menuju Halaman Login</a>";

} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Terjadi kesalahan database: " . $e->getMessage() . "</h3>";
}
?>