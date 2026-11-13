<?php
declare(strict_types=1);

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('natal_inventory_session');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function csrf_token(): string
{
    start_secure_session();
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function valid_csrf(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token']);
}

function require_login(): void
{
    start_secure_session();
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . APP_URL . '/auth/login.php');
        exit;
    }
}

function log_activity(PDO $pdo, int $userId, string $activity): void
{
    try {
        $statement = $pdo->prepare('INSERT INTO activity_logs (user_id, activity, created_at) VALUES (:user_id, :activity, NOW())');
        $statement->execute(['user_id' => $userId, 'activity' => $activity]);
    } catch (PDOException $exception) {
        error_log('Activity log failed: ' . $exception->getMessage());
    }
}
