<?php
require_once __DIR__ . '/env.php';

define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'college_document_request'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

/**
 * Returns a shared PDO connection for the current request.
 * Uses an encrypted connection when the CA certificate file is present (TiDB Cloud and similar hosts).
 */
function getDBConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $caFile = dirname(__DIR__) . '/tidb-ca.pem';
        if (is_file($caFile)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $caFile;
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit('The database is unavailable. Check the .env settings or contact the system administrator.');
        }
    }

    return $pdo;
}