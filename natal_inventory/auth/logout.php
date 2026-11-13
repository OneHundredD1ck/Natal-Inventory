<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
start_secure_session();
if (!empty($_SESSION['user_id'])) log_activity(db(), (int) $_SESSION['user_id'], 'Logout dari sistem');
$_SESSION = [];
if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']); }
session_destroy();
header('Location: login.php');
exit;