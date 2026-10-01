<?php
// ============================================================
// FILE: tasks/task_details.php
// PURPOSE: Full detail view of a single task. Shows all task
//          info and dynamically changes the action buttons
//          based on who is viewing (creator vs. worker) and
//          the current task status.
// ============================================================

include '../includes/header2.php';

// Get task ID from URL
$task_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($task_id <= 0) {
    echo '<div class="container"><div class="alert alert-danger">Invalid task ID.</div></div>';
    include '../includes/footer2.php';
    exit();
}

// Fetch task with creator info
$task_sql = "SELECT t.*, u.name as creator_name, u.email as creator_email 
             FROM tasks t JOIN users u ON t.creator_id = u.user_id 
             WHERE t.task_id = ?";
$task_stmt = mysqli_prepare($conn, $task_sql);
mysqli_stmt_bind_param($task_stmt, "i", $task_id);
mysqli_stmt_execute($task_stmt);
$task = mysqli_fetch_assoc(mysqli_stmt_get_result($task_stmt));

if (!$task) {
    echo '<div class="container"><div class="alert alert-danger">Task not found.</div></div>';
    include '../includes/footer2.php';
    exit();
}

// Determine viewer role
$is_creator = ($task["creator_id"] == $session_user_id);
$is_assigned = ($task["assigned_user_id"] == $session_user_id);

// Check if current user has already applied
$app_check_sql = "SELECT * FROM applications WHERE task_id = ? AND user_id = ?";
$app_check_stmt = mysqli_prepare($conn, $app_check_sql);
mysqli_stmt_bind_param($app_check_stmt, "ii", $task_id, $session_user_id);
mysqli_stmt_execute($app_check_stmt);
$existing_app = mysqli_fetch_assoc(mysqli_stmt_get_result($app_check_stmt));

// Fetch assigned worker name if exists
$worker_name = "";
if ($task["assigned_user_id"]) {
    $w_sql = "SELECT name FROM users WHERE user_id = ?";
    $w_stmt = mysqli_prepare($conn, $w_sql);
    mysqli_stmt_bind_param($w_stmt, "i", $task["assigned_user_id"]);
    mysqli_stmt_execute($w_stmt);
    $worker_name = mysqli_fetch_assoc(mysqli_stmt_get_result($w_stmt))["name"];
}

// Count applications
$app_count_sql = "SELECT COUNT(*) as count FROM applications WHERE task_id = ? AND status = 'PENDING'";
$app_count_stmt = mysqli_prepare($conn, $app_count_sql);
mysqli_stmt_bind_param($app_count_stmt, "i", $task_id);
mysqli_stmt_execute($app_count_stmt);
$app_count = mysqli_fetch_assoc(mysqli_stmt_get_result($app_count_stmt))["count"];
?>

<section class="section">
    <div class="container">
        <div class="task-detail">
            <!-- Task Header -->
            <div class="task-detail-header">
                <div>
                    <h1><?php echo htmlspecialchars($task["title"]); ?></h1>
                    <p class="text-muted">Posted by <?php echo htmlspecialchars($task["creator_name"]); ?> on <?php echo date("M d, Y", strtotime($task["created_at"])); ?></p>
                </div>
                <span class="badge badge-lg badge-<?php echo strtolower($task["status"]); ?>">
                    <?php echo $task["status"]; ?>
                </span>
            </div>

            <!-- Task Info Grid -->
            <div class="task-info-grid">
                <div class="info-item">
                    <i class="fas fa-coins"></i>
                    <span class="info-label">Reward</span>
                    <span class="info-value"><?php echo $task["reward"]; ?> WP <span class="text-muted" style="font-size:0.85rem; font-weight:normal;">(Rs. <?php echo $task["reward"]; ?>)</span></span>
                </div>
                <?php if ($task["domain"]): ?>
                <div class="info-item">
                    <i class="fas fa-tag"></i>
                    <span class="info-label">Category</span>
                    <span class="info-value"><?php echo htmlspecialchars($task["domain"]); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($task["deadline"]): ?>
                <div class="info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="info-label">Deadline</span>
                    <span class="info-value"><?php echo date("M d, Y", strtotime($task["deadline"])); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($worker_name): ?>
                <div class="info-item">
                    <i class="fas fa-user-check"></i>
                    <span class="info-label">Assigned To</span>
                    <span class="info-value"><?php echo htmlspecialchars($worker_name); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="task-section">
                <h2>Description</h2>
                <p><?php echo nl2br(htmlspecialchars($task["description"])); ?></p>
            </div>

            <!-- Skills -->
            <?php if ($task["skills_required"]): ?>
            <div class="task-section">
                <h2>Skills Required</h2>
                <div class="task-skills">
                    <?php foreach (explode(",", $task["skills_required"]) as $skill): ?>
                        <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Context-Aware Action Buttons -->
            <div class="task-actions-bar">
                <?php if ($is_creator): ?>
                    <!-- Creator sees: manage apps, cancel task, review -->
                    <?php if ($task["status"] === 'OPEN'): ?>
                        <a href="<?php echo BASE_URL; ?>/tasks/manage_applications.php?id=<?php echo $task_id; ?>" class="btn btn-primary">
                            <i class="fas fa-users"></i> Manage Applications (<?php echo $app_count; ?>)
                        </a>
                        <a href="<?php echo BASE_URL; ?>/tasks/cancel_task.php?id=<?php echo $task_id; ?>" class="btn btn-danger"
                           onclick="return confirm('Are you sure you want to cancel this task? Your WP will be refunded.');">
                            <i class="fas fa-times-circle"></i> Cancel Task
                        </a>
                    <?php elseif ($task["status"] === 'SUBMITTED'): ?>
                        <a href="<?php echo BASE_URL; ?>/tasks/review_submission.php?id=<?php echo $task_id; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> Review Submission
                        </a>
                    <?php endif; ?>
                <?php elseif ($is_assigned): ?>
                    <!-- Assigned worker sees: submit work -->
                    <?php if ($task["status"] === 'ASSIGNED'): ?>
                        <a href="<?php echo BASE_URL; ?>/tasks/submit_work.php?id=<?php echo $task_id; ?>" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Submit Work
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Other users see: apply button -->
                    <?php if ($task["status"] === 'OPEN' && !$existing_app): ?>
                        <a href="<?php echo BASE_URL; ?>/tasks/apply_task.php?id=<?php echo $task_id; ?>" class="btn btn-primary">
                            <i class="fas fa-hand-point-up"></i> Apply for This Task
                        </a>
                    <?php elseif ($existing_app): ?>
                        <span class="badge badge-<?php echo strtolower($existing_app["status"]); ?>">
                            Application: <?php echo $existing_app["status"]; ?>
                        </span>
                    <?php endif; ?>
                <?php endif; ?>

                <a href="<?php echo BASE_URL; ?>/tasks/tasks.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to Tasks
                </a>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer2.php'; ?>
