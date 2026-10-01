<?php
// ============================================================
// FILE: admin/disputes.php
// PURPOSE: Admin dispute resolution center. Shows all disputes
//          filed by users and provides arbitration actions:
//          pay worker, refund creator, split, or dismiss.
// ============================================================

include '../includes/header3.php';

$message = "";

// --- Process Dispute Resolution ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["resolve"])) {
    $dispute_id = intval($_POST["dispute_id"]);
    $resolution = $_POST["resolve"];
    $admin_notes = trim($_POST["admin_notes"] ?? "");

    $valid_resolutions = ['WORKER_PAID', 'CREATOR_REFUNDED', 'SPLIT', 'DISMISSED'];

    if (in_array($resolution, $valid_resolutions)) {
        // Get dispute details
        $d_sql = "SELECT d.*, t.reward, t.creator_id, t.assigned_user_id, t.title FROM disputes d JOIN tasks t ON d.task_id = t.task_id WHERE d.dispute_id = ?";
        $d_stmt = mysqli_prepare($conn, $d_sql);
        mysqli_stmt_bind_param($d_stmt, "i", $dispute_id);
        mysqli_stmt_execute($d_stmt);
        $dispute = mysqli_fetch_assoc(mysqli_stmt_get_result($d_stmt));

        if ($dispute) {
            mysqli_begin_transaction($conn);
            try {
                // Handle WP based on resolution
                if ($resolution === 'WORKER_PAID' && $dispute["assigned_user_id"]) {
                    // Pay full reward to worker
                    $pay_sql = "UPDATE users SET wp_balance = wp_balance + ? WHERE user_id = ?";
                    $pay_stmt = mysqli_prepare($conn, $pay_sql);
                    mysqli_stmt_bind_param($pay_stmt, "ii", $dispute["reward"], $dispute["assigned_user_id"]);
                    mysqli_stmt_execute($pay_stmt);
                } elseif ($resolution === 'CREATOR_REFUNDED') {
                    // Refund full amount to creator
                    $refund_sql = "UPDATE users SET wp_balance = wp_balance + ? WHERE user_id = ?";
                    $refund_stmt = mysqli_prepare($conn, $refund_sql);
                    mysqli_stmt_bind_param($refund_stmt, "ii", $dispute["reward"], $dispute["creator_id"]);
                    mysqli_stmt_execute($refund_stmt);
                } elseif ($resolution === 'SPLIT' && $dispute["assigned_user_id"]) {
                    // Split 50/50
                    $half = intval($dispute["reward"] / 2);
                    $other_half = $dispute["reward"] - $half;

                    $pay1 = "UPDATE users SET wp_balance = wp_balance + ? WHERE user_id = ?";
                    $stmt1 = mysqli_prepare($conn, $pay1);
                    mysqli_stmt_bind_param($stmt1, "ii", $half, $dispute["assigned_user_id"]);
                    mysqli_stmt_execute($stmt1);

                    $pay2 = "UPDATE users SET wp_balance = wp_balance + ? WHERE user_id = ?";
                    $stmt2 = mysqli_prepare($conn, $pay2);
                    mysqli_stmt_bind_param($stmt2, "ii", $other_half, $dispute["creator_id"]);
                    mysqli_stmt_execute($stmt2);
                }

                // Update dispute record
                $resolve_sql = "UPDATE disputes SET status = 'RESOLVED', resolution = ?, admin_notes = ?, resolved_by = ?, resolved_at = NOW() WHERE dispute_id = ?";
                $resolve_stmt = mysqli_prepare($conn, $resolve_sql);
                mysqli_stmt_bind_param($resolve_stmt, "ssii", $resolution, $admin_notes, $admin_id, $dispute_id);
                mysqli_stmt_execute($resolve_stmt);

                // Mark task as completed/cancelled
                $task_status = ($resolution === 'CREATOR_REFUNDED' || $resolution === 'DISMISSED') ? 'CANCELLED' : 'COMPLETED';
                $task_sql = "UPDATE tasks SET status = ? WHERE task_id = ?";
                $task_stmt = mysqli_prepare($conn, $task_sql);
                mysqli_stmt_bind_param($task_stmt, "si", $task_status, $dispute["task_id"]);
                mysqli_stmt_execute($task_stmt);

                // Log admin action
                $action = "Resolved dispute #" . $dispute_id . " as " . $resolution;
                $log_sql = "INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
                $log_stmt = mysqli_prepare($conn, $log_sql);
                $ip = $_SERVER["REMOTE_ADDR"];
                mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $action, $admin_notes, $ip);
                mysqli_stmt_execute($log_stmt);

                mysqli_commit($conn);
                $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Dispute resolved successfully.</div>';
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $message = '<div class="alert alert-danger">Failed to resolve dispute.</div>';
            }
        }
    }
}

// Fetch all disputes
$disputes = mysqli_query($conn, "SELECT d.*, t.title as task_title, t.reward, u.name as filed_by_name FROM disputes d JOIN tasks t ON d.task_id = t.task_id JOIN users u ON d.filed_by = u.user_id ORDER BY d.filed_at DESC");
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Dispute Resolution Center</h1>

        <?php echo $message; ?>

        <?php if (mysqli_num_rows($disputes) > 0): ?>
            <?php while ($d = mysqli_fetch_assoc($disputes)): ?>
                <div class="dispute-card <?php echo ($d["status"] === 'RESOLVED') ? 'dispute-resolved' : ''; ?>">
                    <div class="dispute-header">
                        <h3>Dispute #<?php echo $d["dispute_id"]; ?> — <?php echo htmlspecialchars($d["task_title"]); ?></h3>
                        <span class="badge badge-<?php echo strtolower(str_replace('_', '', $d["status"])); ?>">
                            <?php echo $d["status"]; ?>
                        </span>
                    </div>
                    <p><strong>Filed by:</strong> <?php echo htmlspecialchars($d["filed_by_name"]); ?></p>
                    <p><strong>Reward at stake:</strong> <?php echo $d["reward"]; ?> WP</p>
                    <p><strong>Reason:</strong> <?php echo nl2br(htmlspecialchars($d["reason"])); ?></p>
                    <?php if ($d["evidence"]): ?>
                        <p><strong>Evidence:</strong> <?php echo nl2br(htmlspecialchars($d["evidence"])); ?></p>
                    <?php endif; ?>

                    <?php if ($d["status"] !== 'RESOLVED'): ?>
                        <form method="POST" class="dispute-resolve-form">
                            <input type="hidden" name="dispute_id" value="<?php echo $d["dispute_id"]; ?>">
                            <div class="form-group">
                                <label>Admin Notes:</label>
                                <textarea name="admin_notes" rows="2" placeholder="Reason for decision..."></textarea>
                            </div>
                            <div class="dispute-actions">
                                <button type="submit" name="resolve" value="WORKER_PAID" class="btn btn-sm btn-primary">Pay Worker</button>
                                <button type="submit" name="resolve" value="CREATOR_REFUNDED" class="btn btn-sm btn-outline">Refund Creator</button>
                                <button type="submit" name="resolve" value="SPLIT" class="btn btn-sm btn-warning">Split 50/50</button>
                                <button type="submit" name="resolve" value="DISMISSED" class="btn btn-sm btn-danger">Dismiss</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <p class="text-muted"><strong>Resolution:</strong> <?php echo str_replace("_", " ", $d["resolution"]); ?></p>
                        <?php if ($d["admin_notes"]): ?>
                            <p class="text-muted"><strong>Admin Notes:</strong> <?php echo htmlspecialchars($d["admin_notes"]); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-gavel"></i>
                <p>No disputes filed yet. The community is harmonious! 🎉</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer3.php'; ?>
