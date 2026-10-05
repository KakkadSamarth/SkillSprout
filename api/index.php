<?php
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$filePath = __DIR__ . '/..' . $requestUri;

// 1. Root index
if ($requestUri === '/' || $requestUri === '') {
    require __DIR__ . '/../index.php';
    exit;
}

// 2. Direct .php file requests (e.g. /about.php, /auth/login.php)
if (is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
    require $filePath;
    exit;
}

// 3. Extensionless URLs (e.g. /about -> /about.php)
if (is_file($filePath . '.php')) {
    require $filePath . '.php';
    exit;
}

// 4. Directory index (e.g. /admin/ -> /admin/dashboard.php or index.php)
if (is_dir($filePath) && is_file($filePath . '/index.php')) {
    require $filePath . '/index.php';
    exit;
}

// 5. 404 Fallback
http_response_code(404);
echo "404 Not Found";