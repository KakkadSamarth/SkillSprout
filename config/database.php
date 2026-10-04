<?php
// Determine database configuration from Railway/standard environment variables
$db_url = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');

if ($db_url) {
    $parsed_url = parse_url($db_url);
    $host     = $parsed_url['host'] ?? '127.0.0.1';
    $port     = isset($parsed_url['port']) ? (int)$parsed_url['port'] : 3306;
    $username = isset($parsed_url['user']) ? rawurldecode($parsed_url['user']) : 'root';
    $password = isset($parsed_url['pass']) ? rawurldecode($parsed_url['pass']) : '';
    $raw_db   = isset($parsed_url['path']) ? ltrim($parsed_url['path'], '/') : 'railway';
    $database = rawurldecode(explode('?', $raw_db)[0]);
} else {
    // Check Railway's standard variables (MYSQLHOST, MYSQLUSER, etc.) alongside DB_*
    $host     = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: (getenv('MYSQL_HOST') ?: '127.0.0.1'));
    $username = getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: (getenv('MYSQL_USER') ?: 'root'));
    $password = getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('MYSQL_PASSWORD') !== false ? getenv('MYSQL_PASSWORD') : ''));
    $database = getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: (getenv('MYSQL_DATABASE') ?: 'railway'));
    $port     = (int)(getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: (getenv('MYSQL_PORT') ?: 3306)));
}

// 1. Initialize PDO
try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log("PDO Connection notice: " . $e->getMessage());
}

// 2. Initialize MySQLi
if (!isset($conn) || !$conn instanceof mysqli) {
    $conn = mysqli_init();

    $is_remote = ($host !== 'localhost' && $host !== '127.0.0.1' && $host !== '');
    $use_ssl   = getenv('DB_SSL') === 'true' || getenv('MYSQL_SSL') === 'true';

    if ($use_ssl && defined('MYSQLI_CLIENT_SSL')) {
        mysqli_ssl_set($conn, NULL, NULL, getenv('DB_SSL_CA') ?: NULL, NULL, NULL);
        $connected = @mysqli_real_connect($conn, $host, $username, $password, $database, $port, NULL, MYSQLI_CLIENT_SSL);
        if (!$connected) {
            $connected = @mysqli_real_connect($conn, $host, $username, $password, $database, $port);
        }
    } else {
        $connected = @mysqli_real_connect($conn, $host, $username, $password, $database, $port);
    }

    if (!$connected) {
        $conn_err = mysqli_connect_error();
        error_log("Database connection failed: " . $conn_err);
        die("Database connection failed (" . htmlspecialchars($conn_err) . "). Please check your Railway environment variables.");
    }

    if (function_exists('mysqli_report')) {
        @mysqli_report(MYSQLI_REPORT_OFF);
    }

    require_once __DIR__ . "/session.php";
    init_skillsprout_session($conn);
}

// 3. Define BASE_URL
if (!defined('BASE_URL')) {
    $rawBase = getenv('BASE_URL') ?: getenv('APP_URL');
    $validBase = null;
    if ($rawBase !== false && $rawBase !== '') {
        $rawBase = trim($rawBase);
        $isDbScheme = preg_match('#^(mysql|mysqli|postgres|postgresql|sqlite|mongodb|redis)://#i', $rawBase);
        $hasAuth = strpos($rawBase, '@') !== false;
        $isHttpOrPath = preg_match('#^(https?://|/)#i', $rawBase);
        if (!$isDbScheme && !$hasAuth && $isHttpOrPath) {
            $validBase = rtrim($rawBase, '/') . '/';
        }
    }
    if ($validBase !== null) {
        define('BASE_URL', $validBase);
    } else {
        define('BASE_URL', '/');
    }
}
