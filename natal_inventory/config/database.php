<?php
declare(strict_types=1);

/* Edit these local XAMPP settings when required. Do not commit real production credentials. */
const DB_HOST = '127.0.0.1';
const DB_PORT = '3307';
const DB_NAME = 'natal_inventory'; // Matches the supplied SQL dump. Change to natal_inventory if that is your actual database.
const DB_USER = 'root';
const DB_PASS = '';
const APP_URL = '/natal_inventory';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch (PDOException $exception) {
        error_log('NATAL INVENTORY database connection failed: ' . $exception->getMessage());
        http_response_code(500);
        exit('Sistem sedang tidak dapat terhubung ke database. Silakan hubungi administrator.');
    }
}
