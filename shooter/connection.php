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
        // Try connecting directly to the target database
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // If the error is "Unknown database", attempt to create it, then reconnect
        $unknownDb = ($e->getCode() === 1049) || (strpos($e->getMessage(), 'Unknown database') !== false);
        if ($unknownDb) {
            try {
                // Connect without selecting a database
                $serverDsn = "mysql:host={$host};port={$port};charset={$charset}";
                $serverPdo = new PDO($serverDsn, $user, $pass, $options);
                // Create the database safely (escape backticks)
                $safeDb = str_replace('`', '``', $dbname);
                $collation = 'utf8mb4_unicode_ci';
                $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDb}` DEFAULT CHARACTER SET {$charset} COLLATE {$collation}");
                // Reconnect to the newly created database
                $pdo = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $inner) {
                http_response_code(500);
                header('Content-Type: text/plain; charset=utf-8');
                echo "DB_ERROR_CREATE: " . $inner->getMessage();
                exit;
            }
        } else {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo "DB_ERROR: " . $e->getMessage();
            exit;
        }
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