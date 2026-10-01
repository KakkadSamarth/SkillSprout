<?php
// ============================================================
// FILE: includes/header1.php
// PURPOSE: Public header shown to guests (not logged in).
//          Contains the site logo, navigation links for
//          public pages, and Login/Register buttons.
// ============================================================

// Start the session so we can check if user is already logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the database config to access BASE_URL
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Character encoding for proper text display -->
    <meta charset="UTF-8">
    <!-- Responsive design: scales page to device width -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Page title shown in browser tab -->
    <title>Skill Sprout - Turn Skills Into Currency</title>
    <!-- SEO meta description for search engines -->
    <meta name="description" content="Skill Sprout is a peer-to-peer skill exchange platform where you earn Work Points by completing tasks for others.">
    <!-- Link to the global stylesheet -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <!-- Google Fonts for modern typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <!-- ===== NAVIGATION BAR (PUBLIC) ===== -->
    <nav class="navbar">
        <div class="nav-container">
            <!-- Site logo/brand name linking to homepage -->
            <a href="<?php echo BASE_URL; ?>/" class="nav-logo">
                <i class="fas fa-seedling"></i> Skill Sprout
            </a>

            <!-- Hamburger menu button for mobile screens -->
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Navigation links -->
            <ul class="nav-links" id="navLinks">
                <li><a href="<?php echo BASE_URL; ?>/">Home</a></li>
                <li><a href="<?php echo BASE_URL; ?>/about.php">About</a></li>
                <li><a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-outline">Login</a></li>
                <li><a href="<?php echo BASE_URL; ?>/auth/register.php" class="btn btn-primary">Register</a></li>
            </ul>
        </div>
    </nav>
    <!-- ===== MAIN CONTENT STARTS ===== -->
    <main class="main-content">
