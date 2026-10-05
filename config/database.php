<?php
// ============================================================
// FILE: config/database.php
// PURPOSE: Establishes the MySQL database connection and
//          defines the BASE_URL constant used across all pages.
// ============================================================

// --- Database Credentials ---
// Support Railway, Vercel, and standard environment variables.
$dbUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');
if ($dbUrl) {
    $parsedUrl = parse_url($dbUrl);
    $host   = $parsedUrl['host'] ?? null;
    $port   = $parsedUrl['port'] ?? 3306;
    $user   = $parsedUrl['user'] ?? null;
    $pass   = $parsedUrl['pass'] ?? null;
    $dbname = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : null;
} else {
    $host   = getenv('DB_HOST') !== false ? getenv('DB_HOST') : getenv('MYSQLHOST');
    $port   = getenv('DB_PORT') !== false ? getenv('DB_PORT') : getenv('MYSQLPORT');
    $user   = getenv('DB_USER') !== false ? getenv('DB_USER') : getenv('MYSQLUSER');
    $pass   = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : getenv('MYSQLPASSWORD');
    $dbname = getenv('DB_NAME') !== false ? getenv('DB_NAME') : getenv('MYSQLDATABASE');
}

$isVercel = getenv('VERCEL') !== false;

if ($isVercel) {
    $missingVariables = [];
    if ($host === false || $host === '' || $host === null)     $missingVariables[] = 'DB_HOST / MYSQLHOST';
    if ($port === false || $port === '' || $port === null)     $missingVariables[] = 'DB_PORT / MYSQLPORT';
    if ($user === false || $user === '' || $user === null)     $missingVariables[] = 'DB_USER / MYSQLUSER';
    if ($pass === false || $pass === null)                     $missingVariables[] = 'DB_PASSWORD / MYSQLPASSWORD';
    if ($dbname === false || $dbname === '' || $dbname === null) $missingVariables[] = 'DB_NAME / MYSQLDATABASE';

    if (!empty($missingVariables) && empty($dbUrl)) {
        http_response_code(500);
        error_log('SkillSprout is missing database environment variables: ' . implode(', ', $missingVariables));
        echo 'The application database is not configured. Set the required DB_* or MYSQL* environment variables (or MYSQL_URL / DATABASE_URL).';
        exit;
    }
} else {
    $host   = ($host !== false && $host !== null) ? $host : 'localhost';
    $port   = ($port !== false && $port !== null) ? $port : '3306';
    $user   = ($user !== false && $user !== null) ? $user : 'root';
    $pass   = ($pass !== false && $pass !== null) ? $pass : '';
    $dbname = ($dbname !== false && $dbname !== null) ? $dbname : 'skillsprout';
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

// --- Ensure Database Tables & Required Columns Exist (Auto-bootstrap & Migrations) ---
if (!function_exists('ensureColumnExists')) {
    function ensureColumnExists($conn, string $table, string $column, string $definition): void {
        try {
            $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
            if ($check && mysqli_num_rows($check) === 0) {
                mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            }
        } catch (Throwable $e) {
            error_log("Failed adding column $column to $table: " . $e->getMessage());
        }
    }
}

if (!function_exists('ensureDatabaseTablesExist')) {
    function ensureDatabaseTablesExist($conn) {
        // 1. Column migrations MUST run first individually
        ensureColumnExists($conn, 'transactions', 'type', "ENUM('SIGNUP_BONUS','PURCHASE','ESCROW_LOCK','ESCROW_RELEASE','PAYOUT','REFUND','ADMIN_ADJUST') NOT NULL DEFAULT 'SIGNUP_BONUS'");
        ensureColumnExists($conn, 'transactions', 'amount_wp', "INT NOT NULL DEFAULT 0");
        ensureColumnExists($conn, 'transactions', 'description', "VARCHAR(500) DEFAULT NULL");
        ensureColumnExists($conn, 'transactions', 'reference_id', "INT DEFAULT NULL");
        ensureColumnExists($conn, 'transactions', 'price_paid', "DECIMAL(10,2) DEFAULT NULL");
        ensureColumnExists($conn, 'transactions', 'payment_method', "VARCHAR(50) DEFAULT NULL");

        ensureColumnExists($conn, 'users', 'wp_balance', "INT NOT NULL DEFAULT 100");
        ensureColumnExists($conn, 'users', 'role', "ENUM('user','moderator','admin') NOT NULL DEFAULT 'user'");
        ensureColumnExists($conn, 'users', 'status', "ENUM('active','warned','suspended','banned') NOT NULL DEFAULT 'active'");
        ensureColumnExists($conn, 'users', 'status_reason', "TEXT DEFAULT NULL");

        ensureColumnExists($conn, 'tasks', 'reward', "INT NOT NULL DEFAULT 0");
        ensureColumnExists($conn, 'tasks', 'status', "ENUM('OPEN','ASSIGNED','SUBMITTED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'OPEN'");
        ensureColumnExists($conn, 'tasks', 'domain', "VARCHAR(100) DEFAULT NULL");
        ensureColumnExists($conn, 'tasks', 'skills_required', "VARCHAR(500) DEFAULT NULL");
        ensureColumnExists($conn, 'tasks', 'deadline', "DATE DEFAULT NULL");
        ensureColumnExists($conn, 'tasks', 'assigned_user_id', "INT DEFAULT NULL");
        ensureColumnExists($conn, 'tasks', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        ensureColumnExists($conn, 'tasks', 'updated_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

        ensureColumnExists($conn, 'users', 'bio', "TEXT DEFAULT NULL");
        ensureColumnExists($conn, 'users', 'skills', "VARCHAR(500) DEFAULT NULL");
        ensureColumnExists($conn, 'users', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        ensureColumnExists($conn, 'users', 'updated_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

        ensureColumnExists($conn, 'applications', 'pitch', "TEXT DEFAULT NULL");
        ensureColumnExists($conn, 'applications', 'status', "ENUM('PENDING','ACCEPTED','REJECTED') NOT NULL DEFAULT 'PENDING'");
        ensureColumnExists($conn, 'applications', 'applied_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

        ensureColumnExists($conn, 'submissions', 'file_path', "VARCHAR(500) DEFAULT NULL");
        ensureColumnExists($conn, 'submissions', 'status', "ENUM('SUBMITTED','APPROVED','REJECTED') NOT NULL DEFAULT 'SUBMITTED'");
        ensureColumnExists($conn, 'submissions', 'submitted_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        ensureColumnExists($conn, 'submissions', 'reviewed_at', "TIMESTAMP NULL DEFAULT NULL");

        ensureColumnExists($conn, 'disputes', 'evidence', "TEXT DEFAULT NULL");
        ensureColumnExists($conn, 'disputes', 'status', "ENUM('OPEN','UNDER_REVIEW','RESOLVED') NOT NULL DEFAULT 'OPEN'");
        ensureColumnExists($conn, 'disputes', 'resolution', "ENUM('WORKER_PAID','CREATOR_REFUNDED','SPLIT','DISMISSED') DEFAULT NULL");
        ensureColumnExists($conn, 'disputes', 'admin_notes', "TEXT DEFAULT NULL");
        ensureColumnExists($conn, 'disputes', 'resolved_by', "INT DEFAULT NULL");
        ensureColumnExists($conn, 'disputes', 'filed_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        ensureColumnExists($conn, 'disputes', 'resolved_at', "TIMESTAMP NULL DEFAULT NULL");

        ensureColumnExists($conn, 'reviews', 'task_id', "INT DEFAULT NULL");
        ensureColumnExists($conn, 'reviews', 'rating', "TINYINT NOT NULL DEFAULT 5");
        ensureColumnExists($conn, 'reviews', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

        ensureColumnExists($conn, 'admin_logs', 'details', "TEXT DEFAULT NULL");
        ensureColumnExists($conn, 'admin_logs', 'ip_address', "VARCHAR(45) DEFAULT NULL");
        ensureColumnExists($conn, 'admin_logs', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

        // 2. Execute table creation & seed statements individually
        $sqlFile = __DIR__ . '/../database/database.sql';
        if (file_exists($sqlFile)) {
            $sqlContent = file_get_contents($sqlFile);
            $sqlContent = preg_replace('/--.*$/m', '', $sqlContent);
            $statements = explode(';', $sqlContent);

            foreach ($statements as $statement) {
                $stmt = trim($statement);
                if (empty($stmt)) continue;
                if (preg_match('/^(CREATE\s+DATABASE|USE\s+)/i', $stmt)) continue;

                if (stripos($stmt, 'INSERT INTO') === 0) {
                    $stmt = preg_replace('/^INSERT\s+INTO/i', 'INSERT IGNORE INTO', $stmt);
                }

                try {
                    mysqli_query($conn, $stmt);
                } catch (Throwable $e) {
                    // Ignore individual statement failures (e.g. duplicate key or table already exists)
                }
            }
        }
    }
}
ensureDatabaseTablesExist($conn);

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