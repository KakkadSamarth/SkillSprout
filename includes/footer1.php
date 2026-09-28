<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/WorkPoint/');
}
?>
<footer>

    <table class="footer-table">

        <tr>

            <!-- Quick Links -->

            <td width="35%">

                <h3>Quick Links</h3>

                <a href="<?= BASE_URL ?>index.php">Home</a>

                <a href="<?= BASE_URL ?>tasks/tasks.php">Tasks</a>

                <a href="<?= BASE_URL ?>about.php">About</a>

                <a href="<?= BASE_URL ?>auth/login.php">Login</a>

                <a href="<?= BASE_URL ?>auth/register.php">Register</a>

            </td>


            <!-- Contact -->

            <td width="40%">

                <h3>Contact Us</h3>

                <p>Email: contact@skillsprout.com</p>

                <p>Phone: +91 98765 43210</p>

                <p>Location: India</p>

            </td>


            <!-- Information -->

            <td>

                <h3>Information</h3>

                <a href="#">Privacy Policy</a>

                <a href="#">Terms & Conditions</a>

                <a href="#">Help & Support</a>

            </td>

        </tr>

    </table>


    <div class="copyright">

        © 2026 SkillSprout. All Rights Reserved.

    </div>

</footer>
