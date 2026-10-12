<?php
/**
 * Database connection (PDO singleton)
 */
declare(strict_types=1);

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'umalafatik');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * @return PDO
 */
function getDB(): PDO
{
    /** @var PDO|null */
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Never leak connection details to the client
            http_response_code(503);
            exit('The service is temporarily unavailable. Please try again later.');
        }
    }

    return $pdo;
}
