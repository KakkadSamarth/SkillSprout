<?php
if (!defined('BASE_URL')) {
    $envBase = getenv('BASE_URL');
    if ($envBase !== false && $envBase !== '') {
        define('BASE_URL', rtrim($envBase, '/') . '/');
    } else {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if (strpos($uri, '/SkillSprout/') === 0 || strpos($scriptName, '/SkillSprout/') === 0) {
            define('BASE_URL', '/SkillSprout/');
        } else {
            define('BASE_URL', '/');
        }
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}
?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<header>

    <div class="logo">
        <a href="<?= BASE_URL ?>user/dashboard.php">SkillSprout</a>
    </div>

    <nav>
        <a href="<?= BASE_URL ?>user/dashboard.php">Home</a>
        <a href="<?= BASE_URL ?>tasks/tasks.php">Tasks</a>
        <a href="<?= BASE_URL ?>user/my_work.php">My Work</a>
        <a href="<?= BASE_URL ?>user/wallet.php">Wallet</a>
        <a href="<?= BASE_URL ?>user/profile.php">Profile</a>
    </nav>

    <div class="account">
        <a href="<?= BASE_URL ?>auth/logout.php">Logout</a>
    </div>

</header>
