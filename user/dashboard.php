<?php
// ============================================================
// FILE: user/dashboard.php
// PURPOSE: Main hub for logged-in users. Displays a welcome
//          message, WP balance summary, quick action cards,
//          and recent activity overview.
// ============================================================

include '../includes/header2.php';

// --- Fetch current user data from database ---
// We need the latest wp_balance and profile info
$user_sql  = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $session_user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user        = mysqli_fetch_assoc($user_result);

// --- Count tasks created by this user ---
$created_sql  = "SELECT COUNT(*) as count FROM tasks WHERE creator_id = ?";
$created_stmt = mysqli_prepare($conn, $created_sql);
mysqli_stmt_bind_param($created_stmt, "i", $session_user_id);
mysqli_stmt_execute($created_stmt);
$created_count = mysqli_fetch_assoc(mysqli_stmt_get_result($created_stmt))["count"];

// --- Count tasks assigned to this user (working on) ---
$assigned_sql  = "SELECT COUNT(*) as count FROM tasks WHERE assigned_user_id = ? AND status IN ('ASSIGNED','SUBMITTED')";
$assigned_stmt = mysqli_prepare($conn, $assigned_sql);
mysqli_stmt_bind_param($assigned_stmt, "i", $session_user_id);
mysqli_stmt_execute($assigned_stmt);
$assigned_count = mysqli_fetch_assoc(mysqli_stmt_get_result($assigned_stmt))["count"];

// --- Count pending applications by this user ---
$apps_sql  = "SELECT COUNT(*) as count FROM applications WHERE user_id = ? AND status = 'PENDING'";
$apps_stmt = mysqli_prepare($conn, $apps_sql);
mysqli_stmt_bind_param($apps_stmt, "i", $session_user_id);
mysqli_stmt_execute($apps_stmt);
$pending_apps = mysqli_fetch_assoc(mysqli_stmt_get_result($apps_stmt))["count"];
?>

<section class="section">
    <div class="container">
        <!-- Welcome Header -->
        <div class="dashboard-header">
            <h1>Welcome back, <?php echo htmlspecialchars($user["name"]); ?>! 👋</h1>
            <p class="text-muted">Here's your Skill Sprout overview</p>
        </div>

        <?php if ($user["status"] === 'warned'): ?>
            <div class="alert alert-warning" style="margin-bottom: 24px; border-left: 4px solid #f59e0b; background: rgba(245, 158, 11, 0.1); padding: 14px 18px; border-radius: 8px;">
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 1.25rem; color: #f59e0b; margin-top: 2px;"></i>
                    <div>
                        <strong style="color: #fbbf24; font-size: 0.95rem;">Account Warning Notice from Administration:</strong>
                        <p style="margin: 4px 0 0; color: var(--text-primary); font-size: 0.9rem;">
                            <?php echo !empty($user["status_reason"]) ? nl2br(htmlspecialchars($user["status_reason"])) : "Please ensure all platform rules, guidelines, and task commitments are followed."; ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats Cards Row -->
        <div class="dashboard-stats">
            <div class="stat-card accent-green">
                <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo number_format($user["wp_balance"]); ?></div>
                    <div class="stat-label">Work Points (Rs. <?php echo number_format($user["wp_balance"]); ?>)</div>
                </div>
            </div>

            <div class="stat-card accent-blue">
                <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo $created_count; ?></div>
                    <div class="stat-label">Tasks Created</div>
                </div>
            </div>

            <div class="stat-card accent-purple">
                <div class="stat-icon"><i class="fas fa-hammer"></i></div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo $assigned_count; ?></div>
                    <div class="stat-label">Active Work</div>
                </div>
            </div>

            <div class="stat-card accent-orange">
                <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo $pending_apps; ?></div>
                    <div class="stat-label">Pending Applications</div>
                </div>
            </div>
        </div>

        <!-- Quick Action Cards -->
        <h2 class="section-title">Quick Actions</h2>
        <div class="action-grid">
            <a href="<?php echo BASE_URL; ?>/tasks/create_task.php" class="action-card">
                <i class="fas fa-plus-circle"></i>
                <h3>Post a Task</h3>
                <p>Create a new task and find skilled helpers</p>
            </a>
            <a href="<?php echo BASE_URL; ?>/tasks/tasks.php" class="action-card">
                <i class="fas fa-search"></i>
                <h3>Browse Tasks</h3>
                <p>Find tasks that match your skills</p>
            </a>
            <a href="<?php echo BASE_URL; ?>/user/my_work.php" class="action-card">
                <i class="fas fa-briefcase"></i>
                <h3>My Work</h3>
                <p>Manage your tasks and applications</p>
            </a>
            <a href="<?php echo BASE_URL; ?>/user/wallet.php" class="action-card">
                <i class="fas fa-coins"></i>
                <h3>My Wallet</h3>
                <p>View balance and purchase Work Points</p>
            </a>
        </div>
    </div>
</section>

<?php include '../includes/footer2.php'; ?>
