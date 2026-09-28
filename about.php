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
<!DOCTYPE html>
<html>
<head>
    <title>About Us - SkillSprout</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<?php include __DIR__ . "/includes/header1.php"; ?>

<main>
    <div class="card content-box">
        <h1>About SkillSprout</h1>
        <p>
            SkillSprout is a collaborative platform designed to bring creators and task solvers together.
            Users can post tasks, offer Work Points as rewards, and collaborate efficiently.
        </p>
        <h2>Our Mission</h2>
        <p>
            To provide a seamless, reward-based ecosystem where individuals can learn new skills,
            deliver quality work, and earn Work Points.
        </p>
    </div>
</main>

<?php include __DIR__ . "/includes/footer1.php"; ?>

</body>
</html>
