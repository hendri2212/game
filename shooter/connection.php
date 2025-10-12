<?php
/**
 * Simple PDO connection for MySQL.
 * DB: game | User: root | Pass: root
 * Note: For production, DO NOT use root/root. Create a least-privileged user.
 */

declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host    = getenv('DB_HOST') ?: '127.0.0.1';
    $port    = getenv('DB_PORT') ?: '8889';
    $dbname  = getenv('DB_NAME') ?: 'game';
    $user    = getenv('DB_USER') ?: 'root';
    $pass    = getenv('DB_PASS') ?: 'root';
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "DB_ERROR: " . $e->getMessage();
        exit;
    }

    return $pdo;
}

// Optional: quick health check if accessed directly (development only).
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $pdo = db();
        echo json_encode(['ok' => true, 'driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}