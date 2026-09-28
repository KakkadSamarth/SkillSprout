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
                <h3>Quick Links</h3>
                <a href="/index.php">Home</a><br>
                <a href="/tasks/tasks.php">Tasks</a><br>
                <a href="/about.php">About</a><br>
                <a href="/auth/login.php">Login</a><br>
                <a href="/auth/register.php">Register</a>
            </td>

            <td width="40%">
                <h3>Contact Us</h3>
                <p>Email: contact@skillsprout.com</p>
                <p>Phone: +91 98765 43210</p>
                <p>Location: India</p><br>
            </td>

            <td>
                <h3>Information</h3>
                <a href="#">Privacy Policy</a><br>
                <a href="#">Terms & Conditions</a><br>
                <a href="#">Help & Support</a>
            </td>
        </tr>
    </table>

    <div class="copyright">
        © 2026 SkillSprout. All Rights Reserved.
    </div>
</footer>