<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/WorkPoint/');
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
