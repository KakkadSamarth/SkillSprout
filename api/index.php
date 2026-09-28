<?php
ob_start();

require_once __DIR__ . "/../config/database.php";

$raw_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($raw_uri, PHP_URL_PATH);

if (strpos($path, BASE_URL) === 0) {
    $path = substr($path, strlen(BASE_URL));
}

$path = trim($path, '/');

if ($path === '' || $path === 'home') {
    $target = __DIR__ . '/../index.php';
} elseif ($path === 'about') {
    $target = __DIR__ . '/../about.php';
} else {
    $clean_routes = [
        'login' => 'auth/login.php',
        'register' => 'auth/register.php',
        'logout' => 'auth/logout.php',
        'dashboard' => 'user/dashboard.php',
        'my-work' => 'user/my_work.php',
        'my_work' => 'user/my_work.php',
        'wallet' => 'user/wallet.php',
        'purchase-wallet' => 'user/purchase_wallet.php',
        'purchase_wallet' => 'user/purchase_wallet.php',
        'purchase-points' => 'user/purchase_wallet.php',
        'purchase_points' => 'user/purchase_wallet.php',
        'buy-points' => 'user/purchase_wallet.php',
        'profile' => 'user/profile.php',
        'tasks' => 'tasks/tasks.php',
        'create-task' => 'tasks/create_task.php',
        'create_task' => 'tasks/create_task.php',
        'task-details' => 'tasks/task_details.php',
        'task_details' => 'tasks/task_details.php',
        'apply-task' => 'tasks/apply_task.php',
        'apply_task' => 'tasks/apply_task.php',
        'manage-applications' => 'tasks/manage_applications.php',
        'manage_applications' => 'tasks/manage_applications.php',
        'submit-work' => 'tasks/submit_work.php',
        'submit_work' => 'tasks/submit_work.php',
        'review-submission' => 'tasks/review_submission.php',
        'review_submission' => 'tasks/review_submission.php',
        'cancel-task' => 'tasks/cancel_task.php',
        'cancel_task' => 'tasks/cancel_task.php',
    ];

    if (isset($clean_routes[$path])) {
        $target = __DIR__ . '/../' . $clean_routes[$path];
    } else {
        $normalized = $path;
        if (!preg_match('/\.php$/i', $normalized) && file_exists(__DIR__ . '/../' . $normalized . '.php')) {
            $normalized .= '.php';
        }

        $direct_file = __DIR__ . '/../' . $normalized;

        if (file_exists($direct_file) && !is_dir($direct_file)) {
            $ext = strtolower(pathinfo($direct_file, PATHINFO_EXTENSION));
            if ($ext === 'php') {
                $target = $direct_file;
            } else {
                $mimes = [
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'svg' => 'image/svg+xml',
                    'ico' => 'image/x-icon',
                    'woff' => 'font/woff',
                    'woff2' => 'font/woff2',
                    'ttf' => 'font/ttf',
                    'json' => 'application/json'
                ];
                $mime = $mimes[$ext] ?? 'application/octet-stream';
                header('Content-Type: ' . $mime);
                header('Cache-Control: public, max-age=31536000');
                readfile($direct_file);
                exit();
            }
        } else {
            http_response_code(404);
            echo "<h1>404 Not Found</h1><p>The requested page <code>" . htmlspecialchars($path) . "</code> does not exist.</p><p><a href='" . BASE_URL . "'>Return Home</a></p>";
            exit();
        }
    }
}

if (isset($target) && file_exists($target)) {
    chdir(dirname($target));
    require $target;
} else {
    http_response_code(404);
    echo "<h1>404 Not Found</h1>";
}
