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
    <title>SkillSprout</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<?php
include __DIR__."/includes/header1.php";
?>

<main>
    <div class="hero">
        <h1>Welcome to SkillSprout</h1>
        <p>
            Turn your skills into currency, one task at a time.
        </p>
        <a href="<?= BASE_URL ?>auth/register.php" class="button">
            Get Started
        </a>
    </div>

    <h2>How SkillSprout Works</h2>
    <table>
        <tr>
            <td>
                <h3>1. Create a Task</h3>
                <p>
                    Post a task and set the number of
                    Work Points you want to offer.
                </p>
            </td>

            <td>
                <h3>2. Find a Task</h3>
                <p>
                    Browse available tasks and apply
                    for a task that matches your skills.
                </p>
            </td>

            <td>
                <h3>3. Complete the Task</h3>
                <p>
                    Complete the assigned work and
                    submit it to the task creator.
                </p>
            </td>

            <td>
                <h3>4. Earn Work Points</h3>
                <p>
                    After the work is approved,
                    Work Points are added to your wallet.
                </p>
            </td>
        </tr>
    </table>

    <h2>Popular Domains</h2>
    <table>
        <tr>
            <td width="33%">
                <h3>Web Development</h3>
                <p>Create websites and web applications.</p>
            </td>

            <td width="33%">
                <h3>Graphic Design</h3>
                <p>Design posters, logos and graphics.</p>
            </td>

            <td width="34%">
                <h3>Content Writing</h3>
                <p>Write articles, descriptions and documents.</p>
            </td>
        </tr>

        <tr>
            <td width="33%">
                <h3>Data Entry</h3>
                <p>Enter and organize useful information.</p>
            </td>

            <td width="33%">
                <h3>Programming</h3>
                <p>Work on programming and coding tasks.</p>
            </td>

            <td width="34%">
                <h3>Other Skills</h3>
                <p>Find tasks based on your own skills.</p>
            </td>
        </tr>
    </table>

    <h2>Why Use SkillSprout?</h2>
    <table>
        <tr>
            <td width="33%" class="translates">
                <h3>Learn</h3>
                <p>
                    Improve your skills by working on
                    different types of tasks.
                </p>
            </td>

            <td width="33%">
                <h3>Earn Work Points</h3>
                <p>
                    Complete tasks and receive Work Points
                    after your work is approved.
                </p>
            </td>

            <td width="34%">
                <h3>Use Your Skills</h3>
                <p>
                    Find tasks related to your skills
                    and interests.
                </p>
            </td>
        </tr>

        <tr>
            <td width="33%">
                <h3>Build Experience</h3>
                <p>
                    Gain practical experience by completing
                    different tasks.
                </p>
            </td>

            <td width="33%">
                <h3>Help Others</h3>
                <p>
                    Use your knowledge to help other
                    SkillSprout users.
                </p>
            </td>

            <td width="34%">
                <h3>Simple Platform</h3>
                <p>
                    Easily find tasks, apply for work
                    and manage your Work Points.
                </p>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="text-center">
                <h2>Ready to Get Started?</h2>
                <p>
                    Create your SkillSprout account and
                    start exploring tasks.
                </p>
                <a href="<?= BASE_URL ?>auth/register.php" class="button">
                    Create Account
                </a>
            </td>
        </tr>
    </table>
</main>

<?php
include __DIR__."/includes/footer1.php";
?>

</body>
</html>
