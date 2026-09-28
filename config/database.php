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
        if (getenv('VERCEL')) {
            die("Database connection failed: " . mysqli_connect_error() . "<br><br><strong>Tip for Vercel Deployment:</strong> Please configure your MySQL database credentials in Vercel Project Settings (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT or DATABASE_URL).");
        } else {
            die("Database connection failed: " . mysqli_connect_error());
        }
    }

    require_once __DIR__ . "/session.php";
    init_skillsprout_session($conn);
}

if (!defined('BASE_URL')) {
    $envBase = getenv('BASE_URL');
    if ($envBase !== false && $envBase !== '') {
        define('BASE_URL', rtrim($envBase, '/') . '/');
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