<?php
// ============================================================
// FILE: tasks/cancel_task.php
// PURPOSE: Allows the task creator to cancel an OPEN task.
//          Refunds the escrowed Work Points back to the creator
//          and rejects all pending applications atomically.
// ============================================================

include '../includes/header2.php';

$task_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

// Fetch the task — must be creator and status OPEN
$task_sql = "SELECT * FROM tasks WHERE task_id = ? AND creator_id = ? AND status = 'OPEN'";
$task_stmt = mysqli_prepare($conn, $task_sql);
mysqli_stmt_bind_param($task_stmt, "ii", $task_id, $session_user_id);
mysqli_stmt_execute($task_stmt);
$task = mysqli_fetch_assoc(mysqli_stmt_get_result($task_stmt));

if (!$task) {
    echo '<div class="container"><div class="alert alert-danger">Task not found, or it cannot be cancelled (only OPEN tasks can be cancelled).</div></div>';
    include '../includes/footer2.php';
    exit();
}

// --- Process Cancellation ---
mysqli_begin_transaction($conn);

try {
    // Step 1: Mark task as CANCELLED
    $cancel_sql = "UPDATE tasks SET status = 'CANCELLED' WHERE task_id = ?";
    $cancel_stmt = mysqli_prepare($conn, $cancel_sql);
    mysqli_stmt_bind_param($cancel_stmt, "i", $task_id);
    mysqli_stmt_execute($cancel_stmt);

    // Step 2: Reject all pending applications
    $reject_sql = "UPDATE applications SET status = 'REJECTED' WHERE task_id = ? AND status = 'PENDING'";
    $reject_stmt = mysqli_prepare($conn, $reject_sql);
    mysqli_stmt_bind_param($reject_stmt, "i", $task_id);
    mysqli_stmt_execute($reject_stmt);

    // Step 3: Refund escrowed WP to the creator
    $refund_sql = "UPDATE users SET wp_balance = wp_balance + ? WHERE user_id = ?";
    $refund_stmt = mysqli_prepare($conn, $refund_sql);
    mysqli_stmt_bind_param($refund_stmt, "ii", $task["reward"], $session_user_id);
    mysqli_stmt_execute($refund_stmt);

    // Step 4: Log the refund transaction
    $desc = "Refund of " . $task["reward"] . " WP for cancelled task: " . $task["title"];
    $trans_sql = "INSERT INTO transactions (user_id, type, amount_wp, description, reference_id) VALUES (?, 'REFUND', ?, ?, ?)";
    $trans_stmt = mysqli_prepare($conn, $trans_sql);
    mysqli_stmt_bind_param($trans_stmt, "iisi", $session_user_id, $task["reward"], $desc, $task_id);
    mysqli_stmt_execute($trans_stmt);

    mysqli_commit($conn);

    echo '<section class="section"><div class="container">';
    echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Task cancelled successfully. ' . $task["reward"] . ' WP has been refunded to your wallet.</div>';
    echo '<a href="' . BASE_URL . '/user/my_work.php" class="btn btn-primary">Back to My Work</a>';
    echo '</div></section>';

} catch (Exception $e) {
    mysqli_rollback($conn);

    echo '<section class="section"><div class="container">';
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Failed to cancel task. Please try again.</div>';
    echo '<a href="' . BASE_URL . '/tasks/task_details.php?id=' . $task_id . '" class="btn btn-outline">Back to Task</a>';
    echo '</div></section>';
}

include '../includes/footer2.php';
?>
