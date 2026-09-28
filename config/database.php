<?php

if (!isset($conn) || !$conn instanceof mysqli) {
    $db_url = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');

    if ($db_url) {
        $parsed_url = parse_url($db_url);
        $host = $parsed_url['host'] ?? 'localhost';
        $port = isset($parsed_url['port']) ? (int)$parsed_url['port'] : 3306;
        $username = $parsed_url['user'] ?? 'root';
        $password = $parsed_url['pass'] ?? '';
        $database = isset($parsed_url['path']) ? ltrim($parsed_url['path'], '/') : 'workpoint';
    } else {
        $host = getenv('DB_HOST') ?: (getenv('MYSQL_HOST') ?: 'localhost');
        $username = getenv('DB_USER') ?: (getenv('MYSQL_USER') ?: 'root');
        $password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('MYSQL_PASSWORD') !== false ? getenv('MYSQL_PASSWORD') : '');
        $database = getenv('DB_NAME') ?: (getenv('MYSQL_DATABASE') ?: 'workpoint');
        $port = (int)(getenv('DB_PORT') ?: (getenv('MYSQL_PORT') ?: 3306));
    }

    $conn = mysqli_init();

    $use_ssl = getenv('DB_SSL') === 'true' || getenv('MYSQL_SSL') === 'true';

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
        error_log("Database connection failed: " . mysqli_connect_error());
        if (getenv('VERCEL')) {
            die("Database connection failed. Please configure your MySQL database credentials in Vercel Project Settings (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT or DATABASE_URL).");
        } else {
            die("Database connection failed. Please check your database configuration.");
        }
    }

    $tbl_check = @mysqli_query($conn, "SHOW TABLES LIKE 'users'");
    if ($tbl_check && mysqli_num_rows($tbl_check) > 0) {
        $cols = [];
        $col_res = @mysqli_query($conn, "SHOW COLUMNS FROM users");
        if ($col_res) {
            while ($c = mysqli_fetch_assoc($col_res)) {
                $cols[$c['Field']] = true;
            }
        }
        if (!isset($cols['name'])) {
            $has_uname = isset($cols['username']);
            $has_fname = isset($cols['full_name']);
            if ($has_uname) {
                @mysqli_query($conn, "ALTER TABLE users ADD COLUMN `name` VARCHAR(100) NOT NULL DEFAULT '' AFTER `user_id`");
                @mysqli_query($conn, "UPDATE users SET `name` = `username` WHERE `name` = '' OR `name` IS NULL");
            } elseif ($has_fname) {
                @mysqli_query($conn, "ALTER TABLE users ADD COLUMN `name` VARCHAR(100) NOT NULL DEFAULT '' AFTER `user_id`");
                @mysqli_query($conn, "UPDATE users SET `name` = `full_name` WHERE `name` = '' OR `name` IS NULL");
            } else {
                @mysqli_query($conn, "ALTER TABLE users ADD COLUMN `name` VARCHAR(100) NOT NULL DEFAULT '' AFTER `user_id`");
                @mysqli_query($conn, "UPDATE users SET `name` = SUBSTRING_INDEX(`email`, '@', 1) WHERE `name` = '' OR `name` IS NULL");
            }
        }
        if (!isset($cols['wp_balance'])) {
            @mysqli_query($conn, "ALTER TABLE users ADD COLUMN `wp_balance` INT NOT NULL DEFAULT 100 AFTER `password`");
        }
    }

    require_once __DIR__ . "/session.php";
    init_skillsprout_session($conn);
}

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
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if (preg_match('#^/SkillSprout(/|$)#i', $uri) || preg_match('#^/SkillSprout(/|$)#i', $scriptName)) {
            define('BASE_URL', '/SkillSprout/');
        } else {
            define('BASE_URL', '/');
        }
    }
}
?>