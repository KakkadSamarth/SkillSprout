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
$u_name = isset($u_cols['name']) ? 'users.name' : (isset($u_cols['username']) ? 'users.username AS creator_name' : 'users.email AS creator_name');

$sql = "SELECT tasks.task_id, $t_creator AS creator_id, tasks.title, tasks.description, $t_domain, $t_skills, $t_reward, 
          $t_deadline, $t_status, tasks.created_at, $u_name 
          AS creator_name FROM tasks 
          INNER JOIN users ON $t_creator = users.user_id  
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

                <?php if (isset($_GET['err']) && $_GET['err'] === 'own_task') { ?>
                    <div class="alert-box alert-warning">
                        You created this task. As the task creator, you cannot apply to your own task. You can manage incoming applications below.
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
                    <?php echo $task["reward_wp"]; ?> WP
                </p>

                <p>
                    <strong>Deadline:</strong>
                    <?php echo htmlspecialchars($task["deadline"]); ?>
                </p>

                <p>
                    <strong>Posted By:</strong>
                    <?php echo htmlspecialchars($task["creator_name"]); ?>
                </p>

                <hr>

                <h2>Description</h2>

                <p>
                    <?php echo nl2br(htmlspecialchars($task["description"])); ?>
                </p>

                <h2>Required Skills</h2>

                <p>
                    <?php
                    if ($task["required_skills"] != "") {
                        echo htmlspecialchars($task["required_skills"]);
                    } else {
                        echo "No specific skills mentioned.";
                    }
                    ?>
                </p>

                <h2>Task Status</h2>

                <p>
                    <?php echo htmlspecialchars($task["status"]); ?>
                </p>

<?php
$user_id = (int)$_SESSION["user_id"];
$user_applied = false;
$app_status = '';
$app_check = @mysqli_query($conn, "SELECT status FROM applications WHERE task_id = $task_id AND (user_id = $user_id OR applicant_id = $user_id) LIMIT 1");
if ($app_check && ($ar = mysqli_fetch_assoc($app_check))) {
    $user_applied = true;
    $app_status = $ar['status'];
}

if ((int)$task["creator_id"] === $user_id) {
    ?>
    <div class="alert-box alert-info mt-2 mb-2">
        <strong>You are the Creator of this Task:</strong> You posted this task offering <strong><?php echo (int) $task['reward_wp']; ?> WP</strong>. As the creator, you cannot apply to your own task. You can manage incoming applications or cancel the task below.
    </div>

    <?php if ($task["status"] == "OPEN") { ?>
        <p class="mt-2">
            <a href="<?= BASE_URL ?>tasks/manage_applications.php?id=<?php echo $task["task_id"]; ?>" class="btn btn-primary mr-1">
                Manage Applications
            </a>
            <form action="<?= BASE_URL ?>tasks/cancel_task.php" method="post" class="inline-block" onsubmit="return confirm('Are you sure you want to cancel this task? Your <?php echo (int) $task['reward_wp']; ?> WP will be refunded to your balance.');">
                <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                <button type="submit" class="btn btn-danger">
                    Cancel Task (Refund <?php echo (int) $task['reward_wp']; ?> WP)
                </button>
            </form>
        </p>
    <?php } else { ?>
        <p class="text-muted mt-2">
            This task is no longer accepting applications (Current status: <?php echo htmlspecialchars($task["status"]); ?>).
        </p>
    <?php } ?>

<?php } else { ?>

    <?php if ($user_applied) { ?>
        <div class="alert-box alert-success mt-2 mb-2">
            <strong>Application Submitted:</strong> You have applied for this task. Your application status is <strong><?php echo htmlspecialchars($app_status); ?></strong>.
            <br>
            <a href="<?= BASE_URL ?>user/my_work.php" class="btn btn-secondary mt-1">View in My Applications &rarr;</a>
        </div>
    <?php } elseif ($task["status"] == "OPEN") { ?>
        <p class="mt-2">
            <a href="<?= BASE_URL ?>tasks/apply_task.php?id=<?php echo $task["task_id"]; ?>" class="btn btn-primary">
                Apply for this Task &rarr;
            </a>
        </p>
    <?php } elseif ($task["status"] == "ASSIGNED") { ?>
        <div class="alert-box alert-warning mt-2">
            This task has already been assigned to another worker.
        </div>
    <?php } elseif ($task["status"] == "SUBMITTED") { ?>
        <div class="alert-box alert-warning mt-2">
            This task is waiting for the creator to review submitted work.
        </div>
    <?php } elseif ($task["status"] == "COMPLETED") { ?>
        <div class="alert-box alert-info mt-2">
            This task has been completed.
        </div>
    <?php } ?>

<?php } ?>

            </td>

        </tr>

    </table>

</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
