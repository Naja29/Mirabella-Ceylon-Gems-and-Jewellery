<?php

define('DB_HOST',    'localhost');
define('DB_PORT',    '3307');   
define('DB_USER',    'root');   
define('DB_PASS',    '');       
define('DB_NAME',    'mirabella_ceylon'); 
define('DB_CHARSET', 'utf8mb4');

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Don't expose DB details in production
        error_log('DB connection failed: ' . $e->getMessage());
        die(json_encode(['error' => 'Database connection failed.']));
    }

    return $pdo;
}
