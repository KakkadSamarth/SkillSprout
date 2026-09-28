<?php
if (!defined('BASE_URL')) {
    $envBase = getenv('BASE_URL');
    if ($envBase !== false && $envBase !== '') {
        define('BASE_URL', rtrim($envBase, '/') . '/');
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
?>
<link rel="stylesheet" href="/assets/css/style.css">

<header>
    <div class="logo">
        <a href="<?= BASE_URL ?>index.php">SkillSprout</a>
    </div>

    <nav>
        <a href="<?= BASE_URL ?>index.php">Home</a>
        <a href="<?= BASE_URL ?>tasks/tasks.php">Tasks</a>
        <a href="<?= BASE_URL ?>about.php">About</a>
    </nav>

    <div class="account">
        <a href="<?= BASE_URL ?>auth/login.php">Login</a>
        <a href="<?= BASE_URL ?>auth/register.php">Register</a>
    </div>
</header>
