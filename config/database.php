<?php
// ============================================================
// FILE: config/database.php
// PURPOSE: Establishes the MySQL database connection and
//          defines the BASE_URL constant used across all pages.
// ============================================================

// --- Database Credentials ---
// Supports DATABASE_URL / MYSQL_URL (Railway, Supabase) and individual env vars with local XAMPP fallbacks.
$databaseUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');

if ($databaseUrl) {
    $parsed_url = parse_url($databaseUrl);
    $host       = $parsed_url['host'] ?? 'localhost';
    $port       = (int)($parsed_url['port'] ?? 3306);
    $user       = isset($parsed_url['user']) ? rawurldecode($parsed_url['user']) : 'root';
    $password   = isset($parsed_url['pass']) ? rawurldecode($parsed_url['pass']) : '';
    $raw_db     = isset($parsed_url['path']) ? ltrim($parsed_url['path'], '/') : 'skillsprout';
    $database   = rawurldecode(explode('?', $raw_db)[0]);
} else {
    $host     = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: (getenv('MYSQL_HOST') ?: 'localhost');
    $user     = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: (getenv('MYSQL_USER') ?: 'root');
    $password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : ''));
    $database = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: (getenv('MYSQL_DATABASE') ?: 'skillsprout');
    $port     = (int)(getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: (getenv('MYSQL_PORT') ?: 3306));
}

// Validate port range
if ($port < 1 || $port > 65535) {
    $port = 3306;
}

// --- Initialize MySQLi ---
$conn = mysqli_init();

if (!$conn) {
    die("Database initialization failed: " . mysqli_connect_error());
}

// Optional SSL support for cloud databases
if (getenv('MYSQL_SSL_CA') || getenv('DB_SSL_CA')) {
    $ca = getenv('MYSQL_SSL_CA') ?: getenv('DB_SSL_CA');
    $conn->ssl_set(NULL, NULL, $ca, NULL, NULL);
}

// --- Establish Database Connection with Error Handling ---
mysqli_report(MYSQLI_REPORT_OFF);
$connected = @$conn->real_connect($host, $user, $password, $database, $port);

if (!$connected) {
    $error_msg = mysqli_connect_error();
    $isVercel  = getenv('VERCEL') !== false;

    if ($isVercel || getenv('APP_ENV') === 'production') {
        http_response_code(500);
        error_log('SkillSprout database connection failed: ' . $error_msg);
        echo 'The application database is unavailable. Please check the DB_* environment variables.';
        exit;
    }

    // Friendly local troubleshooting UI
    echo "<div style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; max-width: 600px; margin: 3rem auto; padding: 2rem; background: #1e293b; color: #f1f5f9; border-radius: 12px; border: 1px solid #ef4444; box-shadow: 0 10px 25px rgba(0,0,0,0.5);'>";
    echo "<h2 style='color: #ef4444; margin-top: 0;'>⚠️ Database Connection Failed</h2>";
    echo "<p style='color: #94a3b8; font-size: 0.95rem; line-height: 1.6;'>SkillSprout could not connect to MySQL on <code>{$host}:{$port}</code>.</p>";
    echo "<div style='background: #0f172a; padding: 1rem; border-radius: 8px; border-left: 4px solid #ef4444; margin: 1rem 0; font-family: monospace; font-size: 0.85rem; color: #f87171; word-break: break-all;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($error_msg);
    echo "</div>";

    if (strpos($error_msg, "Access denied") !== false) {
        echo "<h3 style='color: #f59e0b; font-size: 1rem; margin-top: 1.5rem;'>🔑 How to fix this:</h3>";
        echo "<ol style='color: #cbd5e1; font-size: 0.9rem; line-height: 1.7; padding-left: 1.25rem;'>";
        echo "<li>Your MySQL server on port <code>{$port}</code> has a password set for user <code>root</code>.</li>";
        echo "<li>Update the password in <code>config/database.php</code> or set the <code>DB_PASS</code> environment variable.</li>";
        echo "<li>Save and refresh this page.</li>";
        echo "</ol>";
    } elseif (strpos($error_msg, "Unknown database") !== false) {
        echo "<h3 style='color: #3b82f6; font-size: 1rem; margin-top: 1.5rem;'>📦 How to fix this:</h3>";
        echo "<ol style='color: #cbd5e1; font-size: 0.9rem; line-height: 1.7; padding-left: 1.25rem;'>";
        echo "<li>The database <code>" . htmlspecialchars($database) . "</code> has not been created yet.</li>";
        echo "<li>Run: <code>CREATE DATABASE " . htmlspecialchars($database) . ";</code></li>";
        echo "<li>Import the file <code>database/database.sql</code> into the database.</li>";
        echo "<li>Save and refresh this page.</li>";
        echo "</ol>";
    } else {
        echo "<h3 style='color: #f59e0b; font-size: 1rem; margin-top: 1.5rem;'>🔧 Troubleshooting:</h3>";
        echo "<p style='color: #cbd5e1; font-size: 0.9rem;'>Ensure the MySQL service is running in XAMPP on port {$port} and that credentials in <code>config/database.php</code> are correct.</p>";
    }
    echo "</div>";
    exit;
}

// --- Set Character Set ---
$conn->set_charset('utf8mb4');

// --- Define BASE_URL ---
if (!defined('BASE_URL')) {
    $configuredUrl = getenv('APP_URL') ?: getenv('BASE_URL');
    if ($configuredUrl && !preg_match('#^(mysql|postgres)#i', $configuredUrl)) {
        $base = rtrim($configuredUrl, '/');
    } else {
        $isVercel = (getenv('VERCEL') !== false);
        $forwardedProtocol = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        $isHttps = $forwardedProtocol === 'https'
            || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        $protocol = $isHttps ? 'https' : 'http';
        $hostValue = $_SERVER['HTTP_HOST'] ?? 'localhost';

        if ($isVercel) {
            $localPath = '';
        } else {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            if (stripos($requestUri, '/SkillSprout') === 0 || stripos($scriptName, '/SkillSprout') === 0) {
                $localPath = '/SkillSprout';
            } else {
                $localPath = '';
            }
        }
        $base = $protocol . '://' . $hostValue . $localPath;
    }
    define('BASE_URL', $base);
}
?>
