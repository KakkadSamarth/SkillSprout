<?php
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
