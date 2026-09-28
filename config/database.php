<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "workpoint";
$port = 3306;

$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database,
    $port
);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/WorkPoint/');
}

?>