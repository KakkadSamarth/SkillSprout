<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/SkillSprout/');
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
        <a href="<?= BASE_URL ?>user/dashboard.php" style="text-decoration: none; color: inherit;">SkillSprout</a>
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
