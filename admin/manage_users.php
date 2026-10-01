<?php
// ============================================================
// FILE: admin/manage_users.php
// PURPOSE: Admin user management panel. Lists all users with
//          their status, WP balance, and role. Admins can
//          change account status (activate, warn, suspend, ban).
// ============================================================

include '../includes/header3.php';

$message = "";

// --- Process Status Change ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $target_user_id = intval($_POST["user_id"]);
    $new_status     = $_POST["action"];
    $valid_statuses = ['active', 'warned', 'suspended', 'banned'];

    if (in_array($new_status, $valid_statuses) && $target_user_id !== $admin_id) {
        $update_sql = "UPDATE users SET status = ? WHERE user_id = ? AND role = 'user'";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $new_status, $target_user_id);

        if (mysqli_stmt_execute($update_stmt)) {
            // Log admin action
            $action_desc = "Changed user #" . $target_user_id . " status to " . $new_status;
            $log_sql = "INSERT INTO admin_logs (admin_id, action, ip_address) VALUES (?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_sql);
            $ip = $_SERVER["REMOTE_ADDR"];
            mysqli_stmt_bind_param($log_stmt, "iss", $admin_id, $action_desc, $ip);
            mysqli_stmt_execute($log_stmt);

            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> User status updated to ' . htmlspecialchars($new_status) . '.</div>';
        }
    }
}

// --- Fetch All Users ---
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
$sql = "SELECT * FROM users WHERE role = 'user'";
if (!empty($search)) {
    $sql .= " AND (name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR email LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
}
$sql .= " ORDER BY created_at DESC";
$users = mysqli_query($conn, $sql);
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Manage Users</h1>

        <?php echo $message; ?>

        <!-- Search Bar -->
        <form class="search-bar" method="GET">
            <div class="search-input-group">
                <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            </div>
        </form>

        <!-- Users Table -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>WP Balance</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($users)): ?>
                        <tr>
                            <td>#<?php echo $user["user_id"]; ?></td>
                            <td><?php echo htmlspecialchars($user["name"]); ?></td>
                            <td><?php echo htmlspecialchars($user["email"]); ?></td>
                            <td><?php echo number_format($user["wp_balance"]); ?> WP</td>
                            <td>
                                <span class="badge badge-<?php echo $user["status"]; ?>">
                                    <?php echo ucfirst($user["status"]); ?>
                                </span>
                            </td>
                            <td><?php echo date("M d, Y", strtotime($user["created_at"])); ?></td>
                            <td>
                                <div class="admin-action-btns">
                                    <?php if ($user["status"] !== 'active'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user["user_id"]; ?>">
                                            <input type="hidden" name="action" value="active">
                                            <button type="submit" class="btn btn-xs btn-success" title="Activate"><i class="fas fa-check"></i></button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($user["status"] !== 'warned'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user["user_id"]; ?>">
                                            <input type="hidden" name="action" value="warned">
                                            <button type="submit" class="btn btn-xs btn-warning" title="Warn"><i class="fas fa-exclamation-triangle"></i></button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($user["status"] !== 'suspended'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user["user_id"]; ?>">
                                            <input type="hidden" name="action" value="suspended">
                                            <button type="submit" class="btn btn-xs btn-orange" title="Suspend"><i class="fas fa-pause"></i></button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($user["status"] !== 'banned'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user["user_id"]; ?>">
                                            <input type="hidden" name="action" value="banned">
                                            <button type="submit" class="btn btn-xs btn-danger" title="Ban" onclick="return confirm('Ban this user?');">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php include '../includes/footer3.php'; ?>
