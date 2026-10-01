<?php
// ============================================================
// FILE: tasks/apply_task.php
// PURPOSE: Allows a worker to apply for an open task by
//          submitting a pitch message. Guards prevent users
//          from applying to their own tasks or applying twice.
// ============================================================

include '../includes/header2.php';

$task_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$error   = "";
$success = "";

// Fetch the task
$task_sql = "SELECT * FROM tasks WHERE task_id = ? AND status = 'OPEN'";
$task_stmt = mysqli_prepare($conn, $task_sql);
mysqli_stmt_bind_param($task_stmt, "i", $task_id);
mysqli_stmt_execute($task_stmt);
$task = mysqli_fetch_assoc(mysqli_stmt_get_result($task_stmt));

if (!$task) {
    echo '<div class="container"><div class="alert alert-danger">Task not found or is no longer open.</div></div>';
    include '../includes/footer2.php';
    exit();
}

// --- Guard: Cannot apply to own task ---
if ($task["creator_id"] == $session_user_id) {
    echo '<div class="container"><div class="alert alert-danger">You cannot apply to your own task.</div></div>';
    include '../includes/footer2.php';
    exit();
}

// --- Guard: Cannot apply twice ---
$dup_sql = "SELECT application_id FROM applications WHERE task_id = ? AND user_id = ?";
$dup_stmt = mysqli_prepare($conn, $dup_sql);
mysqli_stmt_bind_param($dup_stmt, "ii", $task_id, $session_user_id);
mysqli_stmt_execute($dup_stmt);
if (mysqli_num_rows(mysqli_stmt_get_result($dup_stmt)) > 0) {
    echo '<div class="container"><div class="alert alert-danger">You have already applied for this task.</div></div>';
    include '../includes/footer2.php';
    exit();
}

// --- Process Application ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $pitch = trim($_POST["pitch"]);

    if (empty($pitch)) {
        $error = "Please write a pitch message explaining why you're a good fit.";
    } else {
        $insert_sql = "INSERT INTO applications (task_id, user_id, pitch) VALUES (?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "iis", $task_id, $session_user_id, $pitch);

        if (mysqli_stmt_execute($insert_stmt)) {
            $success = "Application submitted successfully! The task creator will review it.";
        } else {
            $error = "Failed to submit application. Please try again.";
        }
    }
}
?>

<section class="section">
    <div class="container">
        <div class="form-container">
            <h1 class="form-title">Apply for Task</h1>
            <h2 class="text-muted"><?php echo htmlspecialchars($task["title"]); ?></h2>
            <p><strong>Reward:</strong> <?php echo $task["reward"]; ?> WP</p>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <a href="<?php echo BASE_URL; ?>/tasks/tasks.php" class="btn btn-primary">Browse More Tasks</a>
            <?php else: ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="form-group">
                        <label for="pitch"><i class="fas fa-comment-dots"></i> Your Pitch *</label>
                        <textarea id="pitch" name="pitch" rows="6" placeholder="Explain why you're the right person for this task. Mention relevant skills, experience, and your approach..." required><?php echo isset($pitch) ? htmlspecialchars($pitch) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-paper-plane"></i> Submit Application
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include '../includes/footer2.php'; ?>
