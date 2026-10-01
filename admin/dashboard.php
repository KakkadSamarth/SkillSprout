<?php
// ============================================================
// FILE: admin/dashboard.php
// PURPOSE: Admin dashboard overview with platform-wide KPIs:
//          total users, tasks by status, WP circulation,
//          open disputes, and recent activity summary.
// ============================================================

include '../includes/header3.php';

// --- Fetch Platform Statistics ---

// Total users
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role = 'user'"))["c"];

// Users by status
$active_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE status = 'active' AND role = 'user'"))["c"];
$suspended_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE status IN ('suspended','banned')"))["c"];

// Tasks by status
$open_tasks      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM tasks WHERE status = 'OPEN'"))["c"];
$assigned_tasks  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM tasks WHERE status = 'ASSIGNED'"))["c"];
$completed_tasks = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM tasks WHERE status = 'COMPLETED'"))["c"];
$total_tasks     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM tasks"))["c"];

// Total WP in circulation
$total_wp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(wp_balance) as total FROM users"))["total"];

// Open disputes
$open_disputes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM disputes WHERE status IN ('OPEN','UNDER_REVIEW')"))["c"];

// Recent admin logs
$logs = mysqli_query($conn, "SELECT al.*, u.name FROM admin_logs al JOIN users u ON al.admin_id = u.user_id ORDER BY al.created_at DESC LIMIT 10");
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Admin Dashboard</h1>
        <p class="page-subtitle">Platform overview and key metrics</p>

        <!-- KPI Stats Grid -->
        <div class="admin-stats-grid">
            <div class="stat-card accent-blue">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>

            <div class="stat-card accent-green">
                <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo $total_tasks; ?></div>
                    <div class="stat-label">Total Tasks</div>
                </div>
            </div>

            <div class="stat-card accent-purple">
                <div class="stat-icon"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo number_format($total_wp ?? 0); ?></div>
                    <div class="stat-label">WP in Circulation</div>
                </div>
            </div>

            <div class="stat-card accent-orange">
                <div class="stat-icon"><i class="fas fa-gavel"></i></div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo $open_disputes; ?></div>
                    <div class="stat-label">Open Disputes</div>
                </div>
            </div>
        </div>

        <!-- Task Breakdown -->
        <div class="admin-panels">
            <div class="admin-panel">
                <h3><i class="fas fa-chart-bar"></i> Task Breakdown</h3>
                <div class="breakdown-list">
                    <div class="breakdown-item">
                        <span class="badge badge-open">OPEN</span>
                        <span class="breakdown-count"><?php echo $open_tasks; ?></span>
                    </div>
                    <div class="breakdown-item">
                        <span class="badge badge-assigned">ASSIGNED</span>
                        <span class="breakdown-count"><?php echo $assigned_tasks; ?></span>
                    </div>
                    <div class="breakdown-item">
                        <span class="badge badge-completed">COMPLETED</span>
                        <span class="breakdown-count"><?php echo $completed_tasks; ?></span>
                    </div>
                </div>
            </div>

            <div class="admin-panel">
                <h3><i class="fas fa-user-check"></i> User Status</h3>
                <div class="breakdown-list">
                    <div class="breakdown-item">
                        <span>Active Users</span>
                        <span class="breakdown-count text-green"><?php echo $active_users; ?></span>
                    </div>
                    <div class="breakdown-item">
                        <span>Suspended/Banned</span>
                        <span class="breakdown-count text-red"><?php echo $suspended_users; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Admin Actions -->
        <h2 class="section-title">Quick Actions</h2>
        <div class="action-grid">
            <a href="<?php echo BASE_URL; ?>/admin/manage_users.php" class="action-card">
                <i class="fas fa-users-cog"></i>
                <h3>Manage Users</h3>
                <p>View, suspend, or ban user accounts</p>
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/manage_tasks.php" class="action-card">
                <i class="fas fa-tasks"></i>
                <h3>Manage Tasks</h3>
                <p>Oversee all platform tasks</p>
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/disputes.php" class="action-card">
                <i class="fas fa-gavel"></i>
                <h3>Disputes</h3>
                <p>Resolve escalated conflicts</p>
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/transactions.php" class="action-card">
                <i class="fas fa-receipt"></i>
                <h3>Transactions</h3>
                <p>Monitor WP flow and purchases</p>
            </a>
        </div>

        <!-- Recent Admin Logs -->
        <h2 class="section-title">Recent Admin Activity</h2>
        <?php if (mysqli_num_rows($logs) > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Time</th><th>Admin</th><th>Action</th><th>IP</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($log = mysqli_fetch_assoc($logs)): ?>
                            <tr>
                                <td><?php echo date("M d, H:i", strtotime($log["created_at"])); ?></td>
                                <td><?php echo htmlspecialchars($log["name"]); ?></td>
                                <td><?php echo htmlspecialchars($log["action"]); ?></td>
                                <td><?php echo htmlspecialchars($log["ip_address"] ?? "—"); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No admin activity logged yet.</p>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer3.php'; ?>
