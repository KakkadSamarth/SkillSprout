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
?>
<footer>

    <table class="footer-table">

        <tr>

            <td width="35%">
                <h3>Contact Us</h3>
                <p>Email: support@skillsprout.com</p>
                <p>We are here to help you.</p>
            </td>

            <td width="40%">
                <h3>Quick Links</h3>
                <a href="<?= BASE_URL ?>user/dashboard.php">Home</a><br>
                <a href="<?= BASE_URL ?>tasks/tasks.php">Tasks</a><br>
                <a href="<?= BASE_URL ?>user/my_work.php">My Work</a><br>
                <a href="<?= BASE_URL ?>user/wallet.php">Wallet</a>
            </td>

            <td>
                <h3>Account</h3>
                <a href="<?= BASE_URL ?>user/profile.php">Profile</a><br>
                <a href="<?= BASE_URL ?>auth/logout.php">Logout</a>
            </td>

        </tr>

    </table>

    <div class="copyright">
        © 2026 SkillSprout. All rights reserved.
    </div>

</footer>
