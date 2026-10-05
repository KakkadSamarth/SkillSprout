<?php
// ============================================================
// FILE: includes/header3.php
// PURPOSE: Admin header with session guard checking for admin
//          role. Only users with role 'admin' or 'moderator'
//          can access admin pages. Shows admin-specific navigation.
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// --- ADMIN SESSION GUARD ---
// Check if user is logged in AND has admin/moderator role
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"]) || 
    ($_SESSION["role"] !== 'admin' && $_SESSION["role"] !== 'moderator')) {
    // Not authorized — redirect to admin login
    header("Location: " . BASE_URL . "/admin/login.php");
    exit();
}

$admin_id   = $_SESSION["user_id"];
$admin_name = $_SESSION["name"];
$admin_role = $_SESSION["role"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Sprout - Admin Panel</title>
    <meta name="description" content="Skill Sprout Administration Panel">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
    <!-- ===== ADMIN NAVIGATION BAR ===== -->
    <nav class="navbar admin-navbar">
        <div class="nav-container">
            <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="nav-logo">
                <i class="fas fa-shield-halved"></i> Skill Sprout Admin
            </a>

            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>

            <ul class="nav-links" id="navLinks">
                <li><a href="<?php echo BASE_URL; ?>/admin/dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="<?php echo BASE_URL; ?>/admin/manage_users.php"><i class="fas fa-users-cog"></i> Users</a></li>
                <li><a href="<?php echo BASE_URL; ?>/admin/manage_tasks.php"><i class="fas fa-tasks"></i> Tasks</a></li>
                <li><a href="<?php echo BASE_URL; ?>/admin/disputes.php"><i class="fas fa-gavel"></i> Disputes</a></li>
                <li><a href="<?php echo BASE_URL; ?>/admin/transactions.php"><i class="fas fa-receipt"></i> Transactions</a></li>
                <?php if ($admin_role === 'admin'): ?>
                <li><a href="<?php echo BASE_URL; ?>/admin/settings.php"><i class="fas fa-tags"></i> Categories</a></li>
                <?php endif; ?>
                <li><a href="<?php echo BASE_URL; ?>/admin/analytics.php"><i class="fas fa-chart-pie"></i> Analytics</a></li>
                <li class="nav-user-section">
                    <span class="nav-admin-badge">
                        <i class="fas fa-user-shield"></i>
                        <?php echo htmlspecialchars($admin_name); ?>
                        (<?php echo ucfirst($admin_role); ?>)
                    </span>
                </li>
                <li><a href="<?php echo BASE_URL; ?>/admin/logout.php" class="btn btn-outline btn-sm">Logout</a></li>
            </ul>
        </div>
    </nav>
    <!-- ===== ADMIN MAIN CONTENT STARTS ===== -->
    <main class="main-content admin-content">
