<?php
// ============================================================
// FILE: config/database.php
// PURPOSE: Establishes the MySQL database connection and
//          defines the BASE_URL constant used across all pages.
// ============================================================

// --- Database Credentials ---
// These variables store the information needed to connect to MySQL.
$host   = "localhost";       // The database server address (localhost for XAMPP)
$port   = 3306;              // The MySQL port (default 3306)
$user   = "root";            // The MySQL username (default "root" in XAMPP)
$pass   = "";                // The MySQL password (set your MySQL root password here)
$dbname = "skillsprout";     // The name of the database we created

// --- Create Connection with Exception Handling ---
// In PHP 8.1+, mysqli throws mysqli_sql_exception on connection errors.
// We catch this to display helpful setup guidance instead of an uncaught crash.
$conn = null;
try {
    $conn = mysqli_connect($host, $user, $pass, $dbname, $port);
} catch (mysqli_sql_exception $e) {
    $error_msg = $e->getMessage();

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
// This constant builds the root URL of our application dynamically.
// It detects whether we're on HTTP or HTTPS, gets the server name,
// and appends the project folder path.
// This way, all links in the app work correctly regardless of environment.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host_val = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base     = $protocol . "://" . $host_val . "/SkillSprout";
define("BASE_URL", $base);
?>
