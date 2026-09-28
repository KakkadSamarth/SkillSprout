<?php
ob_start();

$raw_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($raw_uri, PHP_URL_PATH) ?? '/';

if (!defined('BASE_URL')) {
    $envBase = getenv('BASE_URL');
    if ($envBase !== false && $envBase !== '') {
        define('BASE_URL', rtrim($envBase, '/') . '/');
    } else {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if (preg_match('#^/SkillSprout(/|$)#i', $raw_uri) || preg_match('#^/SkillSprout(/|$)#i', $scriptName)) {
            define('BASE_URL', '/SkillSprout/');
        } else {
            define('BASE_URL', '/');
        }
    }
}

$base_path = parse_url(BASE_URL, PHP_URL_PATH) ?: '/';
if ($base_path !== '/' && strpos($path, $base_path) === 0) {
    $path = substr($path, strlen($base_path));
}

$path = preg_replace('#^/?SkillSprout(/|$)#i', '', $path);
$path = trim($path, '/');

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$static_mimes = [
    'css'   => 'text/css',
    'js'    => 'application/javascript',
    'jpg'   => 'image/jpeg',
    'jpeg'  => 'image/jpeg',
    'png'   => 'image/png',
    'gif'   => 'image/gif',
    'svg'   => 'image/svg+xml',
    'ico'   => 'image/x-icon',
    'webp'  => 'image/webp',
    'woff'  => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf'   => 'font/ttf',
    'json'  => 'application/json'
];

if (isset($static_mimes[$ext])) {
    $candidates = [
        __DIR__ . '/../' . $path,
        __DIR__ . '/../public/' . $path,
        __DIR__ . '/../' . ltrim($path, '/'),
        __DIR__ . '/../assets/' . preg_replace('#^assets/#i', '', $path)
    ];
    foreach ($candidates as $candidate) {
        if (file_exists($candidate) && !is_dir($candidate)) {
            header('Content-Type: ' . $static_mimes[$ext]);
            header('Cache-Control: public, max-age=31536000');
            readfile($candidate);
            exit();
        }
    }
}

$route_map = [
    ''                            => 'index.php',
    'index'                       => 'index.php',
    'index.php'                   => 'index.php',
    'home'                        => 'index.php',
    'about'                       => 'about.php',
    'about.php'                   => 'about.php',

    'auth'                        => 'auth/login.php',
    'auth/'                       => 'auth/login.php',
    'login'                       => 'auth/login.php',
    'login.php'                   => 'auth/login.php',
    'auth/login'                  => 'auth/login.php',
    'auth/login.php'              => 'auth/login.php',

    'register'                    => 'auth/register.php',
    'register.php'                => 'auth/register.php',
    'auth/register'               => 'auth/register.php',
    'auth/register.php'           => 'auth/register.php',

    'logout'                      => 'auth/logout.php',
    'logout.php'                  => 'auth/logout.php',
    'auth/logout'                 => 'auth/logout.php',
    'auth/logout.php'             => 'auth/logout.php',

    'user'                        => 'user/dashboard.php',
    'user/'                       => 'user/dashboard.php',
    'dashboard'                   => 'user/dashboard.php',
    'dashboard.php'               => 'user/dashboard.php',
    'user/dashboard'              => 'user/dashboard.php',
    'user/dashboard.php'          => 'user/dashboard.php',

    'my-work'                     => 'user/my_work.php',
    'my_work'                     => 'user/my_work.php',
    'my_work.php'                 => 'user/my_work.php',
    'user/my-work'                => 'user/my_work.php',
    'user/my_work'                => 'user/my_work.php',
    'user/my_work.php'            => 'user/my_work.php',

    'wallet'                      => 'user/wallet.php',
    'wallet.php'                  => 'user/wallet.php',
    'user/wallet'                 => 'user/wallet.php',
    'user/wallet.php'             => 'user/wallet.php',

    'purchase-wallet'             => 'user/purchase_wallet.php',
    'purchase_wallet'             => 'user/purchase_wallet.php',
    'purchase_wallet.php'         => 'user/purchase_wallet.php',
    'purchase-points'             => 'user/purchase_wallet.php',
    'purchase_points'             => 'user/purchase_wallet.php',
    'purchase_points.php'         => 'user/purchase_wallet.php',
    'buy-points'                  => 'user/purchase_wallet.php',
    'user/purchase-wallet'        => 'user/purchase_wallet.php',
    'user/purchase_wallet'        => 'user/purchase_wallet.php',
    'user/purchase_wallet.php'    => 'user/purchase_wallet.php',

    'profile'                     => 'user/profile.php',
    'profile.php'                 => 'user/profile.php',
    'user/profile'                => 'user/profile.php',
    'user/profile.php'            => 'user/profile.php',

    'tasks'                       => 'tasks/tasks.php',
    'tasks/'                      => 'tasks/tasks.php',
    'tasks.php'                   => 'tasks/tasks.php',
    'tasks/tasks'                 => 'tasks/tasks.php',
    'tasks/tasks.php'             => 'tasks/tasks.php',

    'create-task'                 => 'tasks/create_task.php',
    'create_task'                 => 'tasks/create_task.php',
    'create_task.php'             => 'tasks/create_task.php',
    'tasks/create-task'           => 'tasks/create_task.php',
    'tasks/create_task'           => 'tasks/create_task.php',
    'tasks/create_task.php'       => 'tasks/create_task.php',

    'task-details'                => 'tasks/task_details.php',
    'task_details'                => 'tasks/task_details.php',
    'task_details.php'            => 'tasks/task_details.php',
    'tasks/task-details'          => 'tasks/task_details.php',
    'tasks/task_details'          => 'tasks/task_details.php',
    'tasks/task_details.php'      => 'tasks/task_details.php',

    'apply-task'                  => 'tasks/apply_task.php',
    'apply_task'                  => 'tasks/apply_task.php',
    'apply_task.php'              => 'tasks/apply_task.php',
    'tasks/apply-task'            => 'tasks/apply_task.php',
    'tasks/apply_task'            => 'tasks/apply_task.php',
    'tasks/apply_task.php'        => 'tasks/apply_task.php',

    'manage-applications'         => 'tasks/manage_applications.php',
    'manage_applications'         => 'tasks/manage_applications.php',
    'manage_applications.php'     => 'tasks/manage_applications.php',
    'tasks/manage-applications'   => 'tasks/manage_applications.php',
    'tasks/manage_applications'   => 'tasks/manage_applications.php',
    'tasks/manage_applications.php' => 'tasks/manage_applications.php',

    'submit-work'                 => 'tasks/submit_work.php',
    'submit_work'                 => 'tasks/submit_work.php',
    'submit_work.php'             => 'tasks/submit_work.php',
    'tasks/submit-work'           => 'tasks/submit_work.php',
    'tasks/submit_work'           => 'tasks/submit_work.php',
    'tasks/submit_work.php'       => 'tasks/submit_work.php',

    'review-submission'           => 'tasks/review_submission.php',
    'review_submission'           => 'tasks/review_submission.php',
    'review_submission.php'       => 'tasks/review_submission.php',
    'tasks/review-submission'     => 'tasks/review_submission.php',
    'tasks/review_submission'     => 'tasks/review_submission.php',
    'tasks/review_submission.php' => 'tasks/review_submission.php',

    'cancel-task'                 => 'tasks/cancel_task.php',
    'cancel_task'                 => 'tasks/cancel_task.php',
    'cancel_task.php'             => 'tasks/cancel_task.php',
    'tasks/cancel-task'           => 'tasks/cancel_task.php',
    'tasks/cancel_task'           => 'tasks/cancel_task.php',
    'tasks/cancel_task.php'       => 'tasks/cancel_task.php',
];

$target = null;
$lookup = strtolower($path);

if (isset($route_map[$lookup])) {
    $target = __DIR__ . '/../' . $route_map[$lookup];
} elseif (isset($route_map[$path])) {
    $target = __DIR__ . '/../' . $route_map[$path];
} else {
    $normalized = $path;
    if (!preg_match('/\.php$/i', $normalized) && file_exists(__DIR__ . '/../' . $normalized . '.php')) {
        $normalized .= '.php';
    }

    $direct_file = __DIR__ . '/../' . $normalized;
    if (file_exists($direct_file) && !is_dir($direct_file)) {
        $target = $direct_file;
    }
}

if ($target && file_exists($target)) {
    require_once __DIR__ . "/../config/database.php";
    chdir(dirname($target));
    require $target;
    exit();
}

http_response_code(404);
echo "<!DOCTYPE html><html><head><title>404 Not Found - SkillSprout</title>";
echo "<link rel='stylesheet' href='" . BASE_URL . "assets/css/style.css'>";
echo "</head><body><main><div class='card content-box text-center'>";
echo "<h1>404 Not Found</h1>";
echo "<p>The requested page <code>" . htmlspecialchars($path) . "</code> could not be found.</p>";
echo "<a href='" . BASE_URL . "' class='btn btn-primary'>Return to Home</a>";
echo "</div></main></body></html>";
exit();
