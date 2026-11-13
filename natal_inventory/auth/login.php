<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
start_secure_session();

if (!empty($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf()) {
        $error = 'Permintaan tidak valid. Silakan coba lagi.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($username === '' || $password === '') {
            $error = 'Username dan password wajib diisi.';
        } else {
            try {
                $statement = db()->prepare('SELECT id, username, password, name FROM users WHERE username = :username LIMIT 1');
                $statement->execute(['username' => $username]);
                $user = $statement->fetch();
                if ($user && password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    log_activity(db(), (int) $user['id'], 'Login ke sistem');
                    header('Location: ../index.php'); exit;
                }
                $error = 'Username atau password tidak valid.';
            } catch (PDOException $exception) {
                error_log('Login failed: ' . $exception->getMessage());
                $error = 'Sistem sedang mengalami gangguan. Silakan coba lagi.';
            }
        }
    }
}
?><!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk | NATAL INVENTORY</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="auth-page"><main class="auth-card"><h1>NATAL INVENTORY</h1><p>Masuk sebagai administrator</p>
<?php if ($error): ?><div class="alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<form method="post" novalidate><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
<label for="username">Username</label><input id="username" name="username" autocomplete="username" required autofocus>
<label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required>
<button type="submit">Masuk</button></form><small>Belum ada admin? Jalankan pengaturan awal melalui <code>create_admin.php</code>.</small>
</main></body></html>
