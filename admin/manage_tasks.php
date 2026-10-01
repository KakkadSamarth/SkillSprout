<?php
// ============================================================
// FILE: admin/manage_tasks.php
// PURPOSE: Admin task oversight panel. Lists all tasks across
//          the platform with filtering by status. Admins can
//          view details and take moderation actions.
// ============================================================

include '../includes/header3.php';

// Status filter
$status_filter = isset($_GET["status"]) ? $_GET["status"] : "";

$sql = "SELECT t.*, u.name as creator_name FROM tasks t JOIN users u ON t.creator_id = u.user_id";
if (!empty($status_filter)) {
    $sql .= " WHERE t.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}
$sql .= " ORDER BY t.created_at DESC";
$tasks = mysqli_query($conn, $sql);
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Manage Tasks</h1>

        <!-- Status Filter Tabs -->
        <div class="tabs">
            <a href="?status=" class="tab-btn <?php echo empty($status_filter) ? 'active' : ''; ?>">All</a>
            <a href="?status=OPEN" class="tab-btn <?php echo $status_filter === 'OPEN' ? 'active' : ''; ?>">Open</a>
            <a href="?status=ASSIGNED" class="tab-btn <?php echo $status_filter === 'ASSIGNED' ? 'active' : ''; ?>">Assigned</a>
            <a href="?status=SUBMITTED" class="tab-btn <?php echo $status_filter === 'SUBMITTED' ? 'active' : ''; ?>">Submitted</a>
            <a href="?status=COMPLETED" class="tab-btn <?php echo $status_filter === 'COMPLETED' ? 'active' : ''; ?>">Completed</a>
            <a href="?status=CANCELLED" class="tab-btn <?php echo $status_filter === 'CANCELLED' ? 'active' : ''; ?>">Cancelled</a>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Creator</th>
                        <th>Reward</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($tasks) > 0): ?>
                        <?php while ($task = mysqli_fetch_assoc($tasks)): ?>
                            <tr>
                                <td>#<?php echo $task["task_id"]; ?></td>
                                <td><?php echo htmlspecialchars(substr($task["title"], 0, 50)); ?></td>
                                <td><?php echo htmlspecialchars($task["creator_name"]); ?></td>
                                <td><?php echo $task["reward"]; ?> WP</td>
                                <td><span class="badge badge-<?php echo strtolower($task["status"]); ?>"><?php echo $task["status"]; ?></span></td>
                                <td><?php echo date("M d, Y", strtotime($task["created_at"])); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/tasks/task_details.php?id=<?php echo $task["task_id"]; ?>" class="btn btn-xs btn-outline" target="_blank">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted">No tasks found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php include '../includes/footer3.php'; ?>
