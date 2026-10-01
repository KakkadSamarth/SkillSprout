<?php
// ============================================================
// FILE: admin/analytics.php
// PURPOSE: Platform analytics dashboard with key metrics,
//          growth indicators, and data summaries for admins.
// ============================================================

include '../includes/header3.php';

// --- Gather Analytics Data ---

// Users registered this month
$this_month = date("Y-m-01");
$new_users_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE created_at >= '$this_month'"))["c"];

// Tasks created this month
$new_tasks_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM tasks WHERE created_at >= '$this_month'"))["c"];

// Tasks completed this month
$completed_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM tasks WHERE status = 'COMPLETED' AND updated_at >= '$this_month'"))["c"];

// Cancelled tasks this month
$cancelled_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM tasks WHERE status = 'CANCELLED' AND updated_at >= '$this_month'"))["c"];

// Total WP in system
$total_wp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(wp_balance) as total FROM users"))["total"];

// Average WP per user
$avg_wp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(wp_balance) as avg FROM users WHERE role = 'user'"))["avg"];

// Top categories by task count
$top_cats = mysqli_query($conn, "SELECT domain, COUNT(*) as count FROM tasks WHERE domain IS NOT NULL AND domain != '' GROUP BY domain ORDER BY count DESC LIMIT 5");

// Most active users (by tasks completed as worker)
$top_workers = mysqli_query($conn, "SELECT u.name, COUNT(*) as completed FROM tasks t JOIN users u ON t.assigned_user_id = u.user_id WHERE t.status = 'COMPLETED' GROUP BY t.assigned_user_id ORDER BY completed DESC LIMIT 5");

// Dispute rate
$total_tasks_all = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM tasks"))["c"];
$total_disputes  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM disputes"))["c"];
$dispute_rate    = ($total_tasks_all > 0) ? round(($total_disputes / $total_tasks_all) * 100, 1) : 0;
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Platform Analytics</h1>
        <p class="page-subtitle">Data for <?php echo date("F Y"); ?></p>

        <!-- Monthly KPI Cards -->
        <div class="admin-stats-grid">
            <div class="stat-card accent-blue">
                <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo $new_users_month; ?></div>
                    <div class="stat-label">New Users (Month)</div>
                </div>
            </div>
            <div class="stat-card accent-green">
                <div class="stat-icon"><i class="fas fa-plus-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo $new_tasks_month; ?></div>
                    <div class="stat-label">Tasks Posted (Month)</div>
                </div>
            </div>
            <div class="stat-card accent-purple">
                <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo $completed_month; ?></div>
                    <div class="stat-label">Completed (Month)</div>
                </div>
            </div>
            <div class="stat-card accent-orange">
                <div class="stat-icon"><i class="fas fa-percentage"></i></div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo $dispute_rate; ?>%</div>
                    <div class="stat-label">Dispute Rate</div>
                </div>
            </div>
        </div>

        <!-- Detailed Panels -->
        <div class="admin-panels">
            <div class="admin-panel">
                <h3><i class="fas fa-chart-pie"></i> WP Economy</h3>
                <div class="breakdown-list">
                    <div class="breakdown-item">
                        <span>Total WP in Circulation</span>
                        <span class="breakdown-count"><?php echo number_format($total_wp ?? 0); ?> WP (Rs. <?php echo number_format($total_wp ?? 0); ?>)</span>
                    </div>
                    <div class="breakdown-item">
                        <span>Average WP per User</span>
                        <span class="breakdown-count"><?php echo number_format($avg_wp ?? 0); ?> WP (Rs. <?php echo number_format($avg_wp ?? 0); ?>)</span>
                    </div>
                    <div class="breakdown-item">
                        <span>Tasks Cancelled (Month)</span>
                        <span class="breakdown-count text-red"><?php echo $cancelled_month; ?></span>
                    </div>
                </div>
            </div>

            <div class="admin-panel">
                <h3><i class="fas fa-trophy"></i> Top Categories</h3>
                <div class="breakdown-list">
                    <?php if (mysqli_num_rows($top_cats) > 0): ?>
                        <?php while ($cat = mysqli_fetch_assoc($top_cats)): ?>
                            <div class="breakdown-item">
                                <span><?php echo htmlspecialchars($cat["domain"]); ?></span>
                                <span class="breakdown-count"><?php echo $cat["count"]; ?> tasks</span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted">No data yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Workers Table -->
        <div class="admin-panel" style="margin-top: 2rem;">
            <h3><i class="fas fa-medal"></i> Top Workers (by Completed Tasks)</h3>
            <?php if (mysqli_num_rows($top_workers) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th>Rank</th><th>User</th><th>Tasks Completed</th></tr></thead>
                        <tbody>
                            <?php $rank = 1; while ($w = mysqli_fetch_assoc($top_workers)): ?>
                                <tr>
                                    <td>#<?php echo $rank++; ?></td>
                                    <td><?php echo htmlspecialchars($w["name"]); ?></td>
                                    <td><?php echo $w["completed"]; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No completed tasks yet.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include '../includes/footer3.php'; ?>
