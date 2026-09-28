<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: " . BASE_URL . "tasks/tasks.php");
    exit();
}

$task_id = (int) $_GET["id"];

$t_cols = [];
$tcol_res = @mysqli_query($conn, "SHOW COLUMNS FROM tasks");
if ($tcol_res) {
    while ($tc = mysqli_fetch_assoc($tcol_res)) {
        $t_cols[$tc['Field']] = true;
    }
}
$t_domain = isset($t_cols['domain']) ? 'tasks.domain' : (isset($t_cols['category']) ? 'tasks.category AS domain' : "'General' AS domain");
$t_skills = isset($t_cols['required_skills']) ? 'tasks.required_skills' : (isset($t_cols['skills']) ? 'tasks.skills AS required_skills' : "'' AS required_skills");
$t_reward = isset($t_cols['reward_wp']) ? 'tasks.reward_wp' : (isset($t_cols['reward']) ? 'tasks.reward AS reward_wp' : (isset($t_cols['points']) ? 'tasks.points AS reward_wp' : (isset($t_cols['budget']) ? 'tasks.budget AS reward_wp' : '10 AS reward_wp')));
$t_deadline = isset($t_cols['deadline']) ? 'tasks.deadline' : 'CURRENT_DATE AS deadline';
$t_status = isset($t_cols['status']) ? 'tasks.status' : "'OPEN' AS status";
$t_creator = isset($t_cols['creator_id']) ? 'tasks.creator_id' : (isset($t_cols['client_id']) ? 'tasks.client_id' : (isset($t_cols['user_id']) ? 'tasks.user_id' : 'tasks.creator_id'));

$u_cols = [];
$ucol_res = @mysqli_query($conn, "SHOW COLUMNS FROM users");
if ($ucol_res) {
    while ($uc = mysqli_fetch_assoc($ucol_res)) {
        $u_cols[$uc['Field']] = true;
    }
}
$u_pk = isset($u_cols['user_id']) ? 'users.user_id' : (isset($u_cols['id']) ? 'users.id' : 'users.user_id');
$u_name = isset($u_cols['name']) ? 'users.name' : (isset($u_cols['username']) ? 'users.username' : 'users.email');

$t_assigned = isset($t_cols['assigned_user_id']) ? 'tasks.assigned_user_id' : (isset($t_cols['worker_id']) ? 'tasks.worker_id' : (isset($t_cols['freelancer_id']) ? 'tasks.freelancer_id' : 'NULL'));

$sql = "SELECT tasks.task_id, $t_creator AS creator_id, $t_assigned AS assigned_user_id, tasks.title, tasks.description, $t_domain, $t_skills, $t_reward, 
          $t_deadline, $t_status, tasks.created_at, $u_name AS creator_name FROM tasks 
          LEFT JOIN users ON $t_creator = $u_pk  
          WHERE tasks.task_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $task_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$task = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$task) {
    header("Location: " . BASE_URL . "tasks/tasks.php");
    exit();
}

$task_status = strtoupper(trim($task["status"] ?? 'OPEN'));
if (empty($task_status)) {
    $task_status = 'OPEN';
}

include __DIR__ . "/../includes/header2.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($task["title"]); ?> - SkillSprout</title>
</head>
<body>

<main>

    <table class="task-details-layout">

        <tr>

            <td class="task-details-box">

                <?php if (isset($_GET['msg']) && $_GET['msg'] === 'applied') { ?>
                    <div class="alert-box alert-success mb-2" style="background: #e8f5e9; border-left: 4px solid #4CAF50; padding: 12px; margin-bottom: 15px;">
                        <strong>Success!</strong> Your application has been submitted. The creator can now review your proposal.
                    </div>
                <?php } ?>

                <h1>
                    <?php echo htmlspecialchars($task["title"]); ?>
                </h1>

                <p>
                    <strong>Domain:</strong>
                    <?php echo htmlspecialchars($task["domain"]); ?>
                </p>

                <p>
                    <strong>Reward:</strong>
                    <span class="badge badge-success" style="font-size: 15px; font-weight: bold; background: #28a745; color: #fff; padding: 4px 10px; border-radius: 4px;">
                        <?php echo $task["reward_wp"]; ?> WP
                    </span>
                </p>

                <p>
                    <strong>Deadline:</strong>
                    <?php echo htmlspecialchars($task["deadline"]); ?>
                </p>

                <p>
                    <strong>Posted By:</strong>
                    <?php echo htmlspecialchars($task["creator_name"] ?: 'SkillSprout Community'); ?>
                </p>

                <hr>

                <h2>Description</h2>

                <p>
                    <?php echo nl2br(htmlspecialchars($task["description"])); ?>
                </p>

                <h2>Required Skills</h2>

                <p>
                    <?php
                    if (!empty($task["required_skills"])) {
                        echo htmlspecialchars($task["required_skills"]);
                    } else {
                        echo "No specific skills mentioned.";
                    }
                    ?>
                </p>

                <h2>Task Status</h2>

                <p>
                    <span class="badge badge-info" style="font-weight: bold; text-transform: uppercase;">
                        <?php echo htmlspecialchars($task_status); ?>
                    </span>
                </p>

<?php
$user_id = (int)$_SESSION["user_id"];
$user_applied = false;
$app_status = '';
$existing_pitch = '';

$app_cols = [];
$app_col_res = @mysqli_query($conn, "SHOW COLUMNS FROM applications");
if ($app_col_res) {
    while ($ac = mysqli_fetch_assoc($app_col_res)) {
        $app_cols[$ac['Field']] = true;
    }
}
$a_user_cond = isset($app_cols['user_id']) ? "user_id = $user_id" : (isset($app_cols['applicant_id']) ? "applicant_id = $user_id" : "user_id = $user_id");
$a_msg_field = isset($app_cols['message']) ? 'message' : (isset($app_cols['proposal']) ? 'proposal' : 'status');

$app_check = @mysqli_query($conn, "SELECT status, $a_msg_field AS pitch FROM applications WHERE task_id = $task_id AND $a_user_cond LIMIT 1");
if ($app_check && ($ar = mysqli_fetch_assoc($app_check))) {
    $user_applied = true;
    $app_status = strtoupper($ar['status'] ?? 'PENDING');
    $existing_pitch = $ar['pitch'] ?? '';
}

$is_creator = ((int)$task["creator_id"] === $user_id);
$is_assigned_worker = (!empty($task["assigned_user_id"]) && (int)$task["assigned_user_id"] === $user_id);
?>

<hr style="margin: 25px 0;">

<?php if ($is_creator) { ?>
    <div class="alert-box alert-info mb-3" style="background: #e7f3fe; border-left: 4px solid #2196F3; padding: 15px; border-radius: 4px;">
        <strong>You are the Creator of this Task:</strong> Offering <strong><?php echo (int)$task['reward_wp']; ?> WP</strong>.
        <div style="margin-top: 10px;">
            <a href="<?= BASE_URL ?>tasks/manage_applications.php?id=<?php echo $task["task_id"]; ?>" class="btn btn-primary" style="padding: 9px 18px; font-weight: bold; background: #000; color: #fff; text-decoration: none; border-radius: 4px; display: inline-block; margin-right: 8px;">
                Manage Applications
            </a>
            <?php if ($task_status === 'OPEN') { ?>
                <form action="<?= BASE_URL ?>tasks/cancel_task.php" method="post" style="display: inline-block;" onsubmit="return confirm('Cancel this task? Your <?php echo (int)$task['reward_wp']; ?> WP will be refunded.');">
                    <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                    <button type="submit" class="btn btn-danger" style="padding: 9px 18px; background: #dc3545; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
                        Cancel Task (Refund WP)
                    </button>
                </form>
            <?php } ?>
        </div>
    </div>
<?php } ?>

<?php if ($task_status === 'ASSIGNED' && $is_assigned_worker) { ?>
    <div class="alert-box alert-success mb-3" style="background: #e8f5e9; border-left: 4px solid #4CAF50; padding: 15px; border-radius: 4px;">
        <h3 style="margin-top: 0; color: #2e7d32;">You are Assigned to this Task!</h3>
        <p>Your application was accepted. When you finish the deliverables, submit your work to receive your <strong><?php echo (int)$task['reward_wp']; ?> WP</strong>.</p>
        <a href="<?= BASE_URL ?>tasks/submit_work.php?id=<?php echo $task['task_id']; ?>" class="btn btn-success" style="padding: 12px 24px; font-size: 16px; font-weight: bold; background: #28a745; color: #fff; text-decoration: none; border-radius: 4px; display: inline-block;">
            Submit Completed Work &rarr;
        </a>
    </div>
<?php } elseif ($task_status === 'SUBMITTED' && $is_creator) { ?>
    <div class="alert-box alert-warning mb-3" style="background: #fff8e1; border-left: 4px solid #ff9800; padding: 15px; border-radius: 4px;">
        <h3 style="margin-top: 0; color: #e65100;">Deliverables Awaiting Your Review</h3>
        <p>The assigned worker has submitted their deliverables for this task.</p>
        <a href="<?= BASE_URL ?>tasks/review_submission.php?id=<?php echo $task['task_id']; ?>" class="btn btn-warning" style="padding: 12px 24px; font-size: 16px; font-weight: bold; background: #ff9800; color: #fff; text-decoration: none; border-radius: 4px; display: inline-block;">
            Review Submitted Work &rarr;
        </a>
    </div>
<?php } ?>

<?php if ($user_applied) { ?>
    <div class="alert-box alert-info mb-3" style="background: #e8f5e9; border-left: 4px solid #4CAF50; padding: 15px; border-radius: 4px;">
        <strong>Application Active:</strong> You have applied for this task. Current Status: <span style="font-weight: bold; text-transform: uppercase;"><?php echo htmlspecialchars($app_status); ?></span>.
        <div style="margin-top: 10px;">
            <a href="<?= BASE_URL ?>tasks/apply_task.php?id=<?php echo $task['task_id']; ?>" class="btn btn-secondary" style="padding: 8px 16px; display: inline-block; text-decoration: none; border: 1px solid #ccc; border-radius: 4px; margin-right: 8px;">
                Edit Application Note
            </a>
            <a href="<?= BASE_URL ?>user/my_work.php" class="btn btn-secondary" style="padding: 8px 16px; display: inline-block; text-decoration: none; border: 1px solid #ccc; border-radius: 4px;">
                View My Applications &rarr;
            </a>
        </div>
    </div>
<?php } ?>

<?php if ($task_status === 'OPEN') { ?>
    <div style="margin-top: 20px; padding: 25px; border: 2px solid #000; border-radius: 8px; background: #fafafa;">
        <h2 style="margin-top: 0; font-size: 20px;">
            Apply for this Task
            <?php if ($is_creator) { ?>
                <span style="font-size: 13px; font-weight: normal; color: #666;">(Testing / Peer Application Mode)</span>
            <?php } ?>
        </h2>
        <p style="color: #555; margin-bottom: 15px;">
            Earn <strong><?php echo (int)$task['reward_wp']; ?> Work Points</strong> by completing this task. Enter a brief note or pitch for the task creator below.
        </p>

        <form action="<?= BASE_URL ?>tasks/apply_task.php?id=<?php echo $task_id; ?>" method="post">
            <div style="margin-bottom: 15px;">
                <label for="apply_message" style="display: block; font-weight: bold; margin-bottom: 6px;">
                    Your Application Pitch / Note:
                </label>
                <textarea 
                    name="message" 
                    id="apply_message" 
                    rows="4" 
                    required 
                    style="width: 100%; box-sizing: border-box; padding: 12px; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; font-size: 15px;"
                    placeholder="Describe your skills, relevant experience, or how quickly you can complete this task..."
                ><?php echo htmlspecialchars($existing_pitch); ?></textarea>
            </div>
            
            <div>
                <button type="submit" class="btn btn-primary" style="padding: 14px 32px; font-size: 16px; font-weight: bold; background: #000; color: #fff; border: none; cursor: pointer; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
                    <?php echo $user_applied ? 'Update Application &rarr;' : 'Submit Application &rarr;'; ?>
                </button>
                <a href="<?= BASE_URL ?>tasks/apply_task.php?id=<?php echo $task_id; ?>" style="margin-left: 15px; font-weight: bold; color: #333;">
                    Or open full apply page &rarr;
                </a>
            </div>
        </form>
    </div>
<?php } elseif ($task_status === 'ASSIGNED') { ?>
    <div class="alert-box alert-warning mt-2">
        This task has been assigned to a worker and is currently in progress.
    </div>
<?php } elseif ($task_status === 'SUBMITTED') { ?>
    <div class="alert-box alert-warning mt-2">
        Work deliverables have been submitted and are pending creator approval.
    </div>
<?php } elseif ($task_status === 'COMPLETED') { ?>
    <div class="alert-box alert-info mt-2">
        This task has been completed and payment has been released.
    </div>
<?php } ?>

            </td>

        </tr>

    </table>

</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
