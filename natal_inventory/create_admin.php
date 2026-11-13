<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
start_secure_session();
$message = ''; $error = '';
try { $hasAdmin = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0; }
catch (PDOException $exception) { error_log('Admin setup check failed: ' . $exception->getMessage()); $hasAdmin = true; $error = 'Database tidak dapat diakses.'; }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$hasAdmin) {
    if (!valid_csrf()) $error = 'Permintaan tidak valid.';
    else {
        $name = trim((string) ($_POST['name'] ?? '')); $username = trim((string) ($_POST['username'] ?? '')); $password = (string) ($_POST['password'] ?? '');
        if ($name === '' || $username === '' || strlen($username) > 50 || strlen($name) > 100 || strlen($password) < 8) $error = 'Nama, username (maks. 50 karakter), dan password minimal 8 karakter wajib diisi.';
        else try {
            $stmt = db()->prepare('INSERT INTO users (username, password, name, created_at) VALUES (:username, :password, :name, NOW())');
            $stmt->execute(['username' => $username, 'password' => password_hash($password, PASSWORD_DEFAULT), 'name' => $name]);
            $message = 'Admin berhasil dibuat. Hapus atau lindungi create_admin.php, lalu masuk.';
        } catch (PDOException $exception) { error_log('Admin creation failed: ' . $exception->getMessage()); $error = 'Admin tidak dapat dibuat. Pastikan username belum digunakan.'; }
    }
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Pengaturan Admin</title><link rel="stylesheet" href="assets/css/style.css"></head><body class="auth-page"><main class="auth-card"><h1>Pengaturan Admin Awal</h1>
<?php if ($message): ?><div class="success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php elseif ($hasAdmin): ?><div class="alert">Admin sudah tersedia. Halaman ini hanya dapat digunakan sekali.</div><a href="auth/login.php">Ke halaman masuk</a><?php else: ?>
<?php if ($error): ?><div class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><label>Nama</label><input name="name" required><label>Username</label><input name="username" required><label>Password</label><input name="password" type="password" minlength="8" required><button>Buat Admin</button></form><?php endif; ?></main></body></html>