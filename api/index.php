<?php
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$rootDir = realpath(__DIR__ . '/..');

function serveScript(string $filePath, string $rootDir): void {
    $realFile = realpath($filePath);
    if (!$realFile || !is_file($realFile)) {
        http_response_code(404);
        echo "404 Not Found";
        exit;
    }

    $scriptDir = dirname($realFile);

    // Add script directory and root directory to include_path
    set_include_path(
        $scriptDir . PATH_SEPARATOR .
        $rootDir . PATH_SEPARATOR .
        get_include_path()
    );

    // Switch working directory to script directory so relative includes (e.g. '../config/database.php') resolve correctly
    chdir($scriptDir);

    $_SERVER['SCRIPT_FILENAME'] = $realFile;

    require $realFile;
    exit;
}

// 1. Root index
if ($requestUri === '/' || $requestUri === '') {
    serveScript($rootDir . '/index.php', $rootDir);
}

$filePath = $rootDir . $requestUri;

// 2. Direct .php file requests (e.g. /about.php, /auth/login.php)
if (is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
    serveScript($filePath, $rootDir);
}

// 3. Extensionless URLs (e.g. /about -> /about.php)
if (is_file($filePath . '.php')) {
    serveScript($filePath . '.php', $rootDir);
}

// 4. Directory index (e.g. /admin/ -> /admin/dashboard.php or index.php)
if (is_dir($filePath)) {
    if (is_file($filePath . '/index.php')) {
        serveScript($filePath . '/index.php', $rootDir);
    }
    if (is_file($filePath . '/dashboard.php')) {
        serveScript($filePath . '/dashboard.php', $rootDir);
    }
}

// 5. 404 Fallback
http_response_code(404);
echo "404 Not Found";