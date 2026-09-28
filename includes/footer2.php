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
