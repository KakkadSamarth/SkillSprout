<?php
// ============================================================
// FILE: tasks/submit_work.php
// PURPOSE: Assigned worker submits their completed work.
//          The worker provides a description of deliverables
//          and optionally uploads a file. Task status changes
//          from ASSIGNED to SUBMITTED.
// ============================================================

include '../includes/header2.php';

$task_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$error   = "";
$success = "";

// Fetch the task — must be assigned to current user
$task_sql = "SELECT * FROM tasks WHERE task_id = ? AND assigned_user_id = ? AND status = 'ASSIGNED'";
$task_stmt = mysqli_prepare($conn, $task_sql);
mysqli_stmt_bind_param($task_stmt, "ii", $task_id, $session_user_id);
mysqli_stmt_execute($task_stmt);
$task = mysqli_fetch_assoc(mysqli_stmt_get_result($task_stmt));

if (!$task) {
    echo '<div class="container"><div class="alert alert-danger">Task not found, not assigned to you, or not in ASSIGNED status.</div></div>';
    include '../includes/footer2.php';
    exit();
}

// --- Process Submission ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $work_details = trim($_POST["work_details"]);
    $file_path    = null;

    if (empty($work_details)) {
        $error = "Please describe the work you've completed.";
    } else {
        // Handle optional file upload
        if (isset($_FILES["work_file"]) && $_FILES["work_file"]["error"] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . "/../assets/uploads/";
            // Create uploads directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            // Generate a unique filename to prevent collisions
            $file_name = time() . "_" . basename($_FILES["work_file"]["name"]);
            $target    = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES["work_file"]["tmp_name"], $target)) {
                $file_path = "assets/uploads/" . $file_name;
            }
        }

        mysqli_begin_transaction($conn);
        try {
            // Insert the submission record
            $sub_sql = "INSERT INTO submissions (task_id, user_id, work_details, file_path) VALUES (?, ?, ?, ?)";
            $sub_stmt = mysqli_prepare($conn, $sub_sql);
            mysqli_stmt_bind_param($sub_stmt, "iiss", $task_id, $session_user_id, $work_details, $file_path);
            mysqli_stmt_execute($sub_stmt);

            // Update task status to SUBMITTED
            $update_sql = "UPDATE tasks SET status = 'SUBMITTED' WHERE task_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $task_id);
            mysqli_stmt_execute($update_stmt);

            mysqli_commit($conn);
            $success = "Work submitted successfully! The task creator will review your submission.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Failed to submit work. Please try again.";
        }
    }
}
?>

<section class="section">
    <div class="container">
        <div class="form-container">
            <h1 class="form-title">Submit Your Work</h1>
            <p class="form-subtitle">Task: <?php echo htmlspecialchars($task["title"]); ?></p>
            <p><strong>Reward:</strong> <?php echo $task["reward"]; ?> WP</p>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <a href="<?php echo BASE_URL; ?>/user/my_work.php" class="btn btn-primary">Back to My Work</a>
            <?php else: ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="work_details"><i class="fas fa-file-alt"></i> Work Description *</label>
                        <textarea id="work_details" name="work_details" rows="8" placeholder="Describe what you've done. Include links to deliverables, code repositories, documents, etc." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="work_file"><i class="fas fa-upload"></i> Upload File (Optional)</label>
                        <input type="file" id="work_file" name="work_file">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-paper-plane"></i> Submit Deliverables
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include '../includes/footer2.php'; ?>
