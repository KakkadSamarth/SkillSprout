<?php
// ============================================================
// FILE: config/database.php
// PURPOSE: Establishes the MySQL database connection and
//          defines the BASE_URL constant used across all pages.
// ============================================================

// --- Database Credentials ---
// Vercel deployments provide these values as project environment variables.
$isVercel = getenv('VERCEL') !== false;
$host     = getenv('DB_HOST');
$port     = getenv('DB_PORT');
$user     = getenv('DB_USER');
$pass     = getenv('DB_PASSWORD');
$dbname   = getenv('DB_NAME');

if ($isVercel) {
    $missingVariables = [];
    foreach ([
        'DB_HOST' => $host,
        'DB_PORT' => $port,
        'DB_USER' => $user,
        'DB_PASSWORD' => $pass,
        'DB_NAME' => $dbname,
    ] as $variable => $value) {
        if ($value === false || $value === '') {
            $missingVariables[] = $variable;
        }
    }

    if ($missingVariables) {
        http_response_code(500);
        error_log('SkillSprout is missing database environment variables: ' . implode(', ', $missingVariables));
        echo 'The application database is not configured. Set the required DB_* environment variables.';
        exit;
    }
} else {
    $host   = $host === false ? 'localhost' : $host;
    $port   = $port === false ? '3306' : $port;
    $user   = $user === false ? 'root' : $user;
    $pass   = $pass === false ? '' : $pass;
    $dbname = $dbname === false ? 'skillsprout' : $dbname;
}

$port = filter_var($port, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 65535],
]);
if ($port === false) {
    http_response_code(500);
    error_log('SkillSprout DB_PORT must be an integer between 1 and 65535.');
    echo 'The application database is not configured correctly. Check DB_PORT.';
    exit;
}

// --- Create Connection with Exception Handling ---
// In PHP 8.1+, mysqli throws mysqli_sql_exception on connection errors.
// We catch this to display helpful setup guidance instead of an uncaught crash.
$conn = null;
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = mysqli_connect($host, $user, $pass, $dbname, $port);
} catch (mysqli_sql_exception $e) {
    $error_msg = $e->getMessage();

    if ($isVercel || getenv('APP_ENV') === 'production') {
        http_response_code(500);
        error_log('SkillSprout database connection failed: ' . $error_msg);
        echo 'The application database is unavailable. Check the DB_* environment variables.';
        exit;
    }

    // Check specific common issues and provide clear instructions
    echo "<div style='font-family: Inter, -apple-system, sans-serif; max-width: 600px; margin: 3rem auto; padding: 2rem; background: #1e293b; color: #f1f5f9; border-radius: 12px; border: 1px solid #ef4444; box-shadow: 0 10px 25px rgba(0,0,0,0.5);'>";
    echo "<h2 style='color: #ef4444; margin-top: 0;'>⚠️ Database Connection Failed</h2>";
    echo "<p style='color: #94a3b8; font-size: 0.95rem; line-height: 1.6;'>SkillSprout could not connect to MySQL on <code>{$host}:{$port}</code>.</p>";
    
    echo "<div style='background: #0f172a; padding: 1rem; border-radius: 8px; border-left: 4px solid #ef4444; margin: 1rem 0; font-family: monospace; font-size: 0.85rem; color: #f87171; word-break: break-all;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($error_msg);
    echo "</div>";

    if (strpos($error_msg, "Access denied") !== false) {
        echo "<h3 style='color: #f59e0b; font-size: 1rem; margin-top: 1.5rem;'>🔑 How to fix this:</h3>";
        echo "<ol style='color: #cbd5e1; font-size: 0.9rem; line-height: 1.7; padding-left: 1.25rem;'>";
        echo "<li>Your MySQL server on port <code>{$port}</code> has a password set for user <code>root</code>.</li>";
        echo "<li>Open <code>config/database.php</code> in your editor.</li>";
        echo "<li>Update line 13: <code>\$pass = \"your_actual_password\";</code></li>";
        echo "<li>Save the file and refresh this page.</li>";
        echo "</ol>";
    } elseif (strpos($error_msg, "Unknown database") !== false) {
        echo "<h3 style='color: #3b82f6; font-size: 1rem; margin-top: 1.5rem;'>📦 How to fix this:</h3>";
        echo "<ol style='color: #cbd5e1; font-size: 0.9rem; line-height: 1.7; padding-left: 1.25rem;'>";
        echo "<li>The database <code>skillsprout</code> has not been created yet.</li>";
        echo "<li>Open phpMyAdmin or MySQL CLI and run: <code>CREATE DATABASE skillsprout;</code></li>";
        echo "<li>Import the file <code>database/database.sql</code> into the <code>skillsprout</code> database.</li>";
        echo "<li>Save and refresh this page.</li>";
        echo "</ol>";
    } else {
        echo "<h3 style='color: #f59e0b; font-size: 1rem; margin-top: 1.5rem;'>🔧 Troubleshooting:</h3>";
        echo "<p style='color: #cbd5e1; font-size: 0.9rem;'>Ensure MySQL service is running on port {$port} and that credentials in <code>config/database.php</code> are correct.</p>";
    }
    echo "</div>";
    exit;
}

// --- Set Character Encoding ---
// Ensures all data sent/received uses UTF-8 encoding.
// This prevents garbled text for special characters.
mysqli_set_charset($conn, "utf8mb4");

// --- Define BASE_URL ---
// Vercel serves the app from the domain root; retain the XAMPP subfolder locally.
$configuredUrl = getenv('APP_URL');
if ($configuredUrl !== false && $configuredUrl !== '') {
    $base = rtrim($configuredUrl, '/');
} else {
    $forwardedProtocol = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    $isHttps = $forwardedProtocol === 'https'
        || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $protocol = $isHttps ? 'https' : 'http';
    $hostValue = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $localPath = $isVercel ? '' : '/SkillSprout';
    $base = $protocol . '://' . $hostValue . $localPath;
}
define('BASE_URL', $base);
?>
    