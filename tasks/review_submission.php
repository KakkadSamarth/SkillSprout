<?php
// ============================================================
// FILE: tasks/review_submission.php
// PURPOSE: Task creator reviews the worker's submitted work.
//          Two actions available:
//          - APPROVE: Release escrowed WP to worker, mark COMPLETED
//          - REJECT: Reset task to ASSIGNED for revision
// ============================================================

include '../includes/header2.php';

$task_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$message = "";

// Fetch the task — must be the creator and status SUBMITTED
$task_sql = "SELECT * FROM tasks WHERE task_id = ? AND creator_id = ? AND status = 'SUBMITTED'";
$task_stmt = mysqli_prepare($conn, $task_sql);
mysqli_stmt_bind_param($task_stmt, "ii", $task_id, $session_user_id);
mysqli_stmt_execute($task_stmt);
$task = mysqli_fetch_assoc(mysqli_stmt_get_result($task_stmt));

if (!$task) {
    echo '<div class="container"><div class="alert alert-danger">No submission to review, or you are not the task creator.</div></div>';
    include '../includes/footer2.php';
    exit();
}

// Fetch the submission
$sub_sql = "SELECT s.*, u.name as worker_name FROM submissions s JOIN users u ON s.user_id = u.user_id WHERE s.task_id = ? AND s.status = 'SUBMITTED' ORDER BY s.submitted_at DESC LIMIT 1";
$sub_stmt = mysqli_prepare($conn, $sub_sql);
mysqli_stmt_bind_param($sub_stmt, "i", $task_id);
mysqli_stmt_execute($sub_stmt);
$submission = mysqli_fetch_assoc(mysqli_stmt_get_result($sub_stmt));

// --- Process Review Decision ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["decision"])) {
    if ($_POST["decision"] === "approve") {
        // --- APPROVE: Pay the worker ---
        mysqli_begin_transaction($conn);
        try {
            // Transfer reward WP to the worker
            $pay_sql = "UPDATE users SET wp_balance = wp_balance + ? WHERE user_id = ?";
            $pay_stmt = mysqli_prepare($conn, $pay_sql);
            mysqli_stmt_bind_param($pay_stmt, "ii", $task["reward"], $task["assigned_user_id"]);
            mysqli_stmt_execute($pay_stmt);

            // Mark submission as APPROVED
            $sub_update = "UPDATE submissions SET status = 'APPROVED', reviewed_at = NOW() WHERE submission_id = ?";
            $sub_stmt2 = mysqli_prepare($conn, $sub_update);
            mysqli_stmt_bind_param($sub_stmt2, "i", $submission["submission_id"]);
            mysqli_stmt_execute($sub_stmt2);

            // Mark task as COMPLETED
            $task_update = "UPDATE tasks SET status = 'COMPLETED' WHERE task_id = ?";
            $task_stmt2 = mysqli_prepare($conn, $task_update);
            mysqli_stmt_bind_param($task_stmt2, "i", $task_id);
            mysqli_stmt_execute($task_stmt2);

            // Log the payout transaction for the worker
            $desc = "Earned " . $task["reward"] . " WP for completing: " . $task["title"];
            $trans_sql = "INSERT INTO transactions (user_id, type, amount_wp, description, reference_id) VALUES (?, 'PAYOUT', ?, ?, ?)";
            $trans_stmt = mysqli_prepare($conn, $trans_sql);
            mysqli_stmt_bind_param($trans_stmt, "iisi", $task["assigned_user_id"], $task["reward"], $desc, $task_id);
            mysqli_stmt_execute($trans_stmt);

            mysqli_commit($conn);
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Work approved! ' . $task["reward"] . ' WP has been paid to the worker.</div>';
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = '<div class="alert alert-danger">Failed to process approval.</div>';
        }
    } elseif ($_POST["decision"] === "reject") {
        // --- REJECT: Send back for revision ---
        mysqli_begin_transaction($conn);
        try {
            $sub_update = "UPDATE submissions SET status = 'REJECTED', reviewed_at = NOW() WHERE submission_id = ?";
            $sub_stmt2 = mysqli_prepare($conn, $sub_update);
            mysqli_stmt_bind_param($sub_stmt2, "i", $submission["submission_id"]);
            mysqli_stmt_execute($sub_stmt2);

            // Reset task to ASSIGNED so worker can resubmit
            $task_update = "UPDATE tasks SET status = 'ASSIGNED' WHERE task_id = ?";
            $task_stmt2 = mysqli_prepare($conn, $task_update);
            mysqli_stmt_bind_param($task_stmt2, "i", $task_id);
            mysqli_stmt_execute($task_stmt2);

            mysqli_commit($conn);
            $message = '<div class="alert alert-success">Submission rejected. The worker can revise and resubmit.</div>';
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = '<div class="alert alert-danger">Failed to reject submission.</div>';
        }
    }
}
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Review Submission</h1>
        <p class="page-subtitle">Task: <?php echo htmlspecialchars($task["title"]); ?></p>

        <?php echo $message; ?>

        <?php if ($submission): ?>
            <div class="submission-review">
                <div class="submission-info">
                    <p><strong>Worker:</strong> <?php echo htmlspecialchars($submission["worker_name"]); ?></p>
                    <p><strong>Submitted:</strong> <?php echo date("M d, Y H:i", strtotime($submission["submitted_at"])); ?></p>
                    <p><strong>Reward:</strong> <?php echo $task["reward"]; ?> WP</p>
                </div>

                <div class="submission-content">
                    <h3>Deliverable Details</h3>
                    <p><?php echo nl2br(htmlspecialchars($submission["work_details"])); ?></p>

                    <?php if ($submission["file_path"]): ?>
                        <p><strong>Attached File:</strong> 
                            <a href="<?php echo BASE_URL . '/' . $submission["file_path"]; ?>" target="_blank" class="btn btn-sm btn-outline">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </p>
                    <?php endif; ?>
                </div>

                <?php if ($submission["status"] === 'SUBMITTED'): ?>
                    <div class="review-actions">
                        <form action="" method="POST" style="display: inline;">
                            <input type="hidden" name="decision" value="approve">
                            <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('Approve and pay <?php echo $task["reward"]; ?> WP to the worker?');">
                                <i class="fas fa-check-circle"></i> Approve & Pay
                            </button>
                        </form>
                        <form action="" method="POST" style="display: inline;">
                            <input type="hidden" name="decision" value="reject">
                            <button type="submit" class="btn btn-danger btn-lg" onclick="return confirm('Reject this submission? The worker can revise and resubmit.');">
                                <i class="fas fa-times-circle"></i> Reject & Request Revision
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No submission found to review.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer2.php'; ?>
