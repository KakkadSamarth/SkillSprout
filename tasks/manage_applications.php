<?php
// ============================================================
// FILE: tasks/manage_applications.php
// PURPOSE: Task creator portal to view all applications for
//          their task and accept one worker. When a worker is
//          accepted, the task becomes ASSIGNED and all other
//          pending applications are automatically rejected.
// ============================================================

include '../includes/header2.php';

$task_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$message = "";

// Fetch the task (only creator can manage)
$task_sql = "SELECT * FROM tasks WHERE task_id = ? AND creator_id = ?";
$task_stmt = mysqli_prepare($conn, $task_sql);
mysqli_stmt_bind_param($task_stmt, "ii", $task_id, $session_user_id);
mysqli_stmt_execute($task_stmt);
$task = mysqli_fetch_assoc(mysqli_stmt_get_result($task_stmt));

if (!$task) {
    echo '<div class="container"><div class="alert alert-danger">Task not found or you are not the creator.</div></div>';
    include '../includes/footer2.php';
    exit();
}

// --- Process Accept/Reject ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $app_id = intval($_POST["application_id"]);

    if ($_POST["action"] === "accept") {
        // Start transaction: assign worker, update task, reject others
        mysqli_begin_transaction($conn);
        try {
            // Get the applicant's user_id
            $get_app = "SELECT user_id FROM applications WHERE application_id = ? AND task_id = ?";
            $get_stmt = mysqli_prepare($conn, $get_app);
            mysqli_stmt_bind_param($get_stmt, "ii", $app_id, $task_id);
            mysqli_stmt_execute($get_stmt);
            $applicant = mysqli_fetch_assoc(mysqli_stmt_get_result($get_stmt));

            // Accept this application
            $accept_sql = "UPDATE applications SET status = 'ACCEPTED' WHERE application_id = ?";
            $accept_stmt = mysqli_prepare($conn, $accept_sql);
            mysqli_stmt_bind_param($accept_stmt, "i", $app_id);
            mysqli_stmt_execute($accept_stmt);

            // Reject all other pending applications for this task
            $reject_sql = "UPDATE applications SET status = 'REJECTED' WHERE task_id = ? AND application_id != ? AND status = 'PENDING'";
            $reject_stmt = mysqli_prepare($conn, $reject_sql);
            mysqli_stmt_bind_param($reject_stmt, "ii", $task_id, $app_id);
            mysqli_stmt_execute($reject_stmt);

            // Assign the worker to the task
            $assign_sql = "UPDATE tasks SET assigned_user_id = ?, status = 'ASSIGNED' WHERE task_id = ?";
            $assign_stmt = mysqli_prepare($conn, $assign_sql);
            mysqli_stmt_bind_param($assign_stmt, "ii", $applicant["user_id"], $task_id);
            mysqli_stmt_execute($assign_stmt);

            mysqli_commit($conn);
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Worker assigned successfully!</div>';
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = '<div class="alert alert-danger">Failed to assign worker.</div>';
        }
    } elseif ($_POST["action"] === "reject") {
        $reject_sql = "UPDATE applications SET status = 'REJECTED' WHERE application_id = ? AND task_id = ?";
        $reject_stmt = mysqli_prepare($conn, $reject_sql);
        mysqli_stmt_bind_param($reject_stmt, "ii", $app_id, $task_id);
        mysqli_stmt_execute($reject_stmt);
        $message = '<div class="alert alert-success">Application rejected.</div>';
    }
}

// Fetch all applications for this task
$apps_sql = "SELECT a.*, u.name, u.email, u.skills FROM applications a JOIN users u ON a.user_id = u.user_id WHERE a.task_id = ? ORDER BY a.applied_at DESC";
$apps_stmt = mysqli_prepare($conn, $apps_sql);
mysqli_stmt_bind_param($apps_stmt, "i", $task_id);
mysqli_stmt_execute($apps_stmt);
$applications = mysqli_stmt_get_result($apps_stmt);
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Manage Applications</h1>
        <p class="page-subtitle">Task: <?php echo htmlspecialchars($task["title"]); ?></p>

        <?php echo $message; ?>

        <?php if (mysqli_num_rows($applications) > 0): ?>
            <div class="applications-list">
                <?php while ($app = mysqli_fetch_assoc($applications)): ?>
                    <div class="application-card">
                        <div class="application-header">
                            <div>
                                <h3><i class="fas fa-user"></i> <?php echo htmlspecialchars($app["name"]); ?></h3>
                                <p class="text-muted"><?php echo htmlspecialchars($app["email"]); ?></p>
                                <?php if ($app["skills"]): ?>
                                    <div class="task-skills">
                                        <?php foreach (explode(",", $app["skills"]) as $skill): ?>
                                            <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="badge badge-<?php echo strtolower($app["status"]); ?>">
                                <?php echo $app["status"]; ?>
                            </span>
                        </div>

                        <div class="application-pitch">
                            <h4>Pitch:</h4>
                            <p><?php echo nl2br(htmlspecialchars($app["pitch"])); ?></p>
                        </div>

                        <?php if ($app["status"] === 'PENDING' && $task["status"] === 'OPEN'): ?>
                            <div class="application-actions">
                                <form action="" method="POST" style="display:inline;">
                                    <input type="hidden" name="application_id" value="<?php echo $app["application_id"]; ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Accept this applicant?');">
                                        <i class="fas fa-check"></i> Accept
                                    </button>
                                </form>
                                <form action="" method="POST" style="display:inline;">
                                    <input type="hidden" name="application_id" value="<?php echo $app["application_id"]; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No applications yet. Share your task to attract workers!</p>
            </div>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/tasks/task_details.php?id=<?php echo $task_id; ?>" class="btn btn-outline" style="margin-top: 1rem;">
            <i class="fas fa-arrow-left"></i> Back to Task
        </a>
    </div>
</section>

<?php include '../includes/footer2.php'; ?>
