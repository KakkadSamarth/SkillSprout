<?php
// api/index.php

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Normalize path
$file = __DIR__ . '/..' . $requestUri;

// If visiting the root '/', serve the main index.php
if ($requestUri === '/' || $requestUri === '') {
    require __DIR__ . '/../index.php';
    exit;
}

// Serve direct PHP files (e.g. /about.php, /auth/login.php)
if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    require $file;
    exit;
}

// Check if a matching .php file exists (for clean URLs like /about -> /about.php)
if (is_file($file . '.php')) {
    require $file . '.php';
    exit;
}

// If targeting a directory containing an index.php
if (is_dir($file) && is_file($file . '/index.php')) {
    require $file . '/index.php';
    exit;
}

// Fallback to root index.php
http_response_code(404);
if (is_file(__DIR__ . '/../index.php')) {
    require __DIR__ . '/../index.php';
} else {
    echo "404 Not Found";
}