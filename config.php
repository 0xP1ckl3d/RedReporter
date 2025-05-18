<?php
// config.php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'redreporter2');
define('DB_USER', 'redreporter');
define('DB_PASS', 'R3dT34m5R3p0rt');

try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    // In production, log rather than echo
    exit('Database connection failed.');
}
