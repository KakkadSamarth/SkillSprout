<?php
// ============================================================
// FILE: includes/header2.php
// PURPOSE: Authenticated user header. This file is included on
//          every page that requires the user to be logged in.
//          It checks the session, and if not logged in, redirects
//          the visitor to the login page. Shows user nav with
//          dashboard, tasks, wallet, and profile links.
// ============================================================

// Start the session (only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config for BASE_URL and $conn
require_once __DIR__ . '/../config/database.php';

// --- SESSION GUARD ---
// If user_id is not set in the session, the user is not logged in.
// Redirect them to the login page immediately.
if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit(); // Stop further execution after redirect
}

// Store session data in convenient variables for use in the page
$session_user_id   = $_SESSION["user_id"];
$session_user_name = $_SESSION["name"];
$session_user_role = isset($_SESSION["role"]) ? $_SESSION["role"] : "user";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Sprout - Dashboard</title>
    <meta name="description" content="Your Skill Sprout dashboard. Manage tasks, track Work Points, and grow your skills.">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Apply saved theme before render to avoid flash -->
    <script>(function(){var t=localStorage.getItem('ss-theme');if(t==='light')document.documentElement.setAttribute('data-theme-pending','light');})();</script>
</head>
<body>
    <!-- ===== NAVIGATION BAR (AUTHENTICATED USER) ===== -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="nav-logo">
                <i class="fas fa-seedling"></i> Skill Sprout
            </a>

            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>

            <ul class="nav-links" id="navLinks">
                <li><a href="<?php echo BASE_URL; ?>/user/dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="<?php echo BASE_URL; ?>/tasks/tasks.php"><i class="fas fa-list-check"></i> Browse Tasks</a></li>
                <li><a href="<?php echo BASE_URL; ?>/tasks/create_task.php"><i class="fas fa-plus-circle"></i> Post Task</a></li>
                <li><a href="<?php echo BASE_URL; ?>/user/my_work.php"><i class="fas fa-briefcase"></i> My Work</a></li>
                <li><a href="<?php echo BASE_URL; ?>/user/wallet.php"><i class="fas fa-wallet"></i> Wallet</a></li>
                <li class="nav-user-section">
                    <a href="<?php echo BASE_URL; ?>/user/profile.php" class="nav-user">
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars($session_user_name); ?>
                    </a>
                </li>
                <li><a href="<?php echo BASE_URL; ?>/auth/logout.php" class="btn btn-outline btn-sm">Logout</a></li>
                <li>
                    <button class="theme-toggle-btn" id="themeToggle" aria-label="Toggle light/dark mode" title="Toggle light mode">
                        <i class="fas fa-sun" id="themeIcon"></i>
                    </button>
                </li>
            </ul>
        </div>
    </nav>
    <!-- ===== MAIN CONTENT STARTS ===== -->
    <main class="main-content">
