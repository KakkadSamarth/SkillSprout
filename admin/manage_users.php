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
    $reason         = isset($_POST["reason"]) ? trim($_POST["reason"]) : "";
    $valid_statuses = ['active', 'warned', 'suspended', 'banned'];

    if (in_array($new_status, $valid_statuses) && $target_user_id !== $admin_id) {
        // If activating, clear previous reason. For warning, suspension, or ban, record message
        $reason_to_store = ($new_status === 'active') ? null : (!empty($reason) ? $reason : 'Administrative action applied by platform moderator.');

        $update_sql = "UPDATE users SET status = ?, status_reason = ? WHERE user_id = ? AND role = 'user'";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ssi", $new_status, $reason_to_store, $target_user_id);

        if (mysqli_stmt_execute($update_stmt)) {
            // Log admin action with reason in details
            $action_desc = "Changed user #" . $target_user_id . " status to " . $new_status;
            $log_sql = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_sql);
            $ip = $_SERVER["REMOTE_ADDR"];
            mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $action_desc, $reason_to_store, $ip);
            mysqli_stmt_execute($log_stmt);

            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> User status updated to ' . htmlspecialchars($new_status) . ' with notice message recorded.</div>';
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
                                <?php if (!empty($user["status_reason"]) && $user["status"] !== 'active'): ?>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 5px; max-width: 200px; line-height: 1.3;" title="<?php echo htmlspecialchars($user["status_reason"]); ?>">
                                        <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars(mb_strimwidth($user["status_reason"], 0, 45, '...')); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date("M d, Y", strtotime($user["created_at"])); ?></td>
                            <td>
                                <div class="admin-action-btns">
                                    <?php if ($user["status"] !== 'active'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user["user_id"]; ?>">
                                            <input type="hidden" name="action" value="active">
                                            <button type="submit" class="btn btn-xs btn-success" title="Activate Account (Clears notice)"><i class="fas fa-check"></i></button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($user["status"] !== 'warned'): ?>
                                        <button type="button" class="btn btn-xs btn-warning" title="Send Warning" onclick="openActionModal(<?php echo $user['user_id']; ?>, <?php echo htmlspecialchars(json_encode($user['name'])); ?>, 'warned')">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($user["status"] !== 'suspended'): ?>
                                        <button type="button" class="btn btn-xs btn-orange" title="Suspend Account" onclick="openActionModal(<?php echo $user['user_id']; ?>, <?php echo htmlspecialchars(json_encode($user['name'])); ?>, 'suspended')">
                                            <i class="fas fa-pause"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($user["status"] !== 'banned'): ?>
                                        <button type="button" class="btn btn-xs btn-danger" title="Ban Account" onclick="openActionModal(<?php echo $user['user_id']; ?>, <?php echo htmlspecialchars(json_encode($user['name'])); ?>, 'banned')">
                                            <i class="fas fa-ban"></i>
                                        </button>
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

<!-- Reason / Message Modal Dialog -->
<div id="statusModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modalTitle" style="margin: 0; font-size: 1.1rem; color: #fff;">Account Action</h3>
            <button type="button" class="modal-close" onclick="closeActionModal()">&times;</button>
        </div>
        <form method="POST" id="statusForm">
            <input type="hidden" name="user_id" id="modalUserId">
            <input type="hidden" name="action" id="modalAction">
            
            <p id="modalUserDesc" style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 14px; line-height: 1.4;"></p>

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="modalReason" style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 0.85rem; color: var(--text-secondary);">
                    Reason / Message to User <span style="color: #ef4444;">*</span>
                </label>
                <textarea name="reason" id="modalReason" rows="3" class="form-control" placeholder="Explain the reason for this warning, suspension, or ban..." required style="width: 100%; box-sizing: border-box; resize: vertical;"></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeActionModal()">Cancel</button>
                <button type="submit" id="modalSubmitBtn" class="btn btn-primary btn-sm">Confirm & Send</button>
            </div>
        </form>
    </div>
</div>

<script>
function openActionModal(userId, userName, action) {
    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalAction').value = action;
    const titleEl = document.getElementById('modalTitle');
    const descEl = document.getElementById('modalUserDesc');
    const submitBtn = document.getElementById('modalSubmitBtn');
    const reasonInput = document.getElementById('modalReason');
    
    reasonInput.value = '';

    if (action === 'warned') {
        titleEl.textContent = 'Issue Warning Notice: ' + userName;
        descEl.textContent = 'This warning message will be sent and displayed to ' + userName + ' on their dashboard upon login.';
        submitBtn.className = 'btn btn-warning btn-sm';
        submitBtn.textContent = 'Send Warning';
    } else if (action === 'suspended') {
        titleEl.textContent = 'Suspend User: ' + userName;
        descEl.textContent = 'User will be suspended from logging in. This reason/message will be shown when they attempt to sign in.';
        submitBtn.className = 'btn btn-orange btn-sm';
        submitBtn.textContent = 'Suspend & Send Notice';
    } else if (action === 'banned') {
        titleEl.textContent = 'Ban User: ' + userName;
        descEl.textContent = 'User will be banned from the platform. This reason/message will be shown when they attempt to sign in.';
        submitBtn.className = 'btn btn-danger btn-sm';
        submitBtn.textContent = 'Ban & Send Notice';
    }

    document.getElementById('statusModal').style.display = 'flex';
    setTimeout(() => reasonInput.focus(), 50);
}

function closeActionModal() {
    document.getElementById('statusModal').style.display = 'none';
}

window.addEventListener('click', function(e) {
    const modal = document.getElementById('statusModal');
    if (e.target === modal) {
        closeActionModal();
    }
});
</script>

<?php include '../includes/footer3.php'; ?>
