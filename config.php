<?php
declare(strict_types=1);

session_start();

const APP_NAME = 'Cafe';
const DB_HOST = '127.0.0.1';
const DB_NAME = 'food_ordering';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $exception) {
        http_response_code(500);
        exit(
            '<div style="font-family:Arial;max-width:680px;margin:60px auto;padding:28px;border:1px solid #fecaca;border-radius:16px">'
            . '<h2 style="color:#dc2626">Database connection failed</h2>'
            . '<p>Start Apache and MySQL in XAMPP, then import <code>database/food_ordering.sql</code> in phpMyAdmin.</p>'
            . '<small>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</small></div>'
        );
    }

    return $pdo;
}

function base_url(string $path = ''): string
{
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $base = $script === '/' ? '' : rtrim($script, '/');
    return $base . '/' . ltrim($path, '/');
}
