<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/WorkPoint/');
}
?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">

<header>

    <!-- Website name -->
    <div class="logo">
        <a href="<?= BASE_URL ?>index.php" style="text-decoration: none; color: inherit;">SkillSprout</a>
    </div>

    <!-- Navigation links -->
    <nav>
        <a href="<?= BASE_URL ?>index.php">Home</a>
        <a href="<?= BASE_URL ?>tasks/tasks.php">Tasks</a>
        <a href="<?= BASE_URL ?>about.php">About</a>
    </nav>

    <!-- Login and Register -->
    <div class="account">
        <a href="<?= BASE_URL ?>auth/login.php">Login</a>
        <a href="<?= BASE_URL ?>auth/register.php">Register</a>
    </div>

</header>
