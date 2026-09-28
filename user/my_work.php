<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

$t_cols = [];
$tcol_res = @mysqli_query($conn, "SHOW COLUMNS FROM tasks");
if ($tcol_res) {
    while ($tc = mysqli_fetch_assoc($tcol_res)) {
        $t_cols[$tc['Field']] = true;
    }
}
$t_reward = isset($t_cols['reward_wp']) ? 'tasks.reward_wp' : (isset($t_cols['reward']) ? 'tasks.reward AS reward_wp' : (isset($t_cols['points']) ? 'tasks.points AS reward_wp' : (isset($t_cols['budget']) ? 'tasks.budget AS reward_wp' : '10 AS reward_wp')));
$t_deadline = isset($t_cols['deadline']) ? 'tasks.deadline' : 'CURRENT_DATE AS deadline';
$t_status = isset($t_cols['status']) ? 'tasks.status' : "'OPEN' AS status";
$t_creator = isset($t_cols['creator_id']) ? 'tasks.creator_id' : (isset($t_cols['client_id']) ? 'tasks.client_id' : (isset($t_cols['user_id']) ? 'tasks.user_id' : 'tasks.creator_id'));
$t_assigned = isset($t_cols['assigned_user_id']) ? 'tasks.assigned_user_id' : (isset($t_cols['worker_id']) ? 'tasks.worker_id' : (isset($t_cols['freelancer_id']) ? 'tasks.freelancer_id' : 'tasks.task_id'));

$app_cols = [];
$appcol_res = @mysqli_query($conn, "SHOW COLUMNS FROM applications");
if ($appcol_res) {
    while ($ac = mysqli_fetch_assoc($appcol_res)) {
        $app_cols[$ac['Field']] = true;
    }
}
$a_id = isset($app_cols['application_id']) ? 'applications.application_id' : (isset($app_cols['id']) ? 'applications.id AS application_id' : '1 AS application_id');
$a_count = isset($app_cols['application_id']) ? 'applications.application_id' : (isset($app_cols['id']) ? 'applications.id' : '*');
$a_task = isset($app_cols['task_id']) ? 'applications.task_id' : 'tasks.task_id';
$a_user = isset($app_cols['user_id']) ? 'applications.user_id' : (
    isset($app_cols['applicant_id']) ? 'applications.applicant_id' : (
    isset($app_cols['worker_id']) ? 'applications.worker_id' : (
    isset($app_cols['candidate_id']) ? 'applications.candidate_id' : (
    isset($app_cols['student_id']) ? 'applications.student_id' : 'applications.user_id'))));
$a_status = isset($app_cols['status']) ? 'applications.status' : "'PENDING'";
$a_created = isset($app_cols['created_at']) ? 'applications.created_at' : (isset($app_cols['applied_at']) ? 'applications.applied_at' : 'tasks.created_at');

$sql = "SELECT
            tasks.task_id,
            tasks.title,
            $t_reward,
            $t_deadline,
            $t_status,
            COUNT($a_count) AS applicant_count
        FROM tasks
        LEFT JOIN applications
            ON tasks.task_id = $a_task
        WHERE $t_creator = ?
        GROUP BY
            tasks.task_id,
            tasks.title,
            $t_reward,
            $t_deadline,
            $t_status
        ORDER BY tasks.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$created_tasks = mysqli_stmt_get_result($stmt);

$sql = "SELECT
            $a_id,
            $a_status AS application_status,
            tasks.task_id,
            tasks.title,
            $t_reward,
            $t_deadline
        FROM applications
        INNER JOIN tasks
            ON $a_task = tasks.task_id
        WHERE $a_user = ?
        AND $a_status = 'PENDING'
        ORDER BY $a_created DESC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$my_applications = mysqli_stmt_get_result($stmt);

$sql = "SELECT
            tasks.task_id,
            tasks.title,
            $t_reward,
            $t_deadline,
            $t_status
        FROM tasks
        WHERE $t_assigned = ?
        AND $t_status = 'ASSIGNED'
        ORDER BY tasks.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$active_work = mysqli_stmt_get_result($stmt);

include __DIR__ . "/../includes/header2.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Work - SkillSprout</title>
</head>
<body>

<main>

    <h1>My Work</h1>

    <p>
        Manage your created tasks, applications and active work.
    </p>

    <?php if (isset($_GET["cancelled"]) && isset($_GET["refund"])) { ?>
        <div class="alert-success">
            Task cancelled successfully! <?php echo (int) $_GET["refund"]; ?> Work Points have been refunded to your wallet.
        </div>
    <?php } elseif (isset($_GET["err"])) { ?>
        <div class="alert-error">
            Unable to cancel task. The task may have already been assigned or completed.
        </div>
    <?php } ?>

    <h2>My Created Tasks</h2>

    <?php if (mysqli_num_rows($created_tasks) > 0) { ?>

        <table class="my-work-table">

            <tr>
                <th>Task</th>
                <th>Reward</th>
                <th>Deadline</th>
                <th>Status</th>
                <th>Applicants</th>
                <th>Action</th>
            </tr>

            <?php while ($task = mysqli_fetch_assoc($created_tasks)) { ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($task["title"]); ?>
                    </td>

                    <td>
                        <?php echo $task["reward_wp"]; ?> WP
                    </td>

                    <td>
                        <?php echo htmlspecialchars($task["deadline"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($task["status"]); ?>
                    </td>

                    <td>
                        <?php echo $task["applicant_count"]; ?>
                    </td>

                    <td>

                        <?php if ($task["status"] == "OPEN") { ?>

                            <a href="<?= BASE_URL ?>tasks/manage_applications.php?id=<?php echo $task["task_id"]; ?>">
                                Manage Applications
                            </a>
                            <br>
                            <form action="<?= BASE_URL ?>tasks/cancel_task.php" method="post" class="inline-form" onsubmit="return confirm('Are you sure you want to cancel this task? Your <?php echo (int) $task['reward_wp']; ?> WP will be refunded.');">
                                <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                                <button type="submit" class="btn-link-danger">
                                    Cancel &amp; Refund
                                </button>
                            </form>

                        <?php } elseif ($task["status"] == "SUBMITTED") { ?>
                        
                            <a href="<?= BASE_URL ?>tasks/review_submission.php?id=<?php echo $task["task_id"]; ?>">
                                Review Submission
                            </a>
                        
                        <?php } else { ?>
                        
                            <a href="<?= BASE_URL ?>tasks/task_details.php?id=<?php echo $task["task_id"]; ?>">
                                View Task
                            </a>
                        
                        <?php } ?>

                    </td>

                </tr>

            <?php } ?>

        </table>

    <?php } else { ?>

        <p>
            You have not created any tasks yet.
        </p>

    <?php } ?>

    <h2>My Applications</h2>

    <?php if (mysqli_num_rows($my_applications) > 0) { ?>

        <table class="my-work-table">

            <tr>
                <th>Task</th>
                <th>Reward</th>
                <th>Deadline</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

            <?php while ($application = mysqli_fetch_assoc($my_applications)) { ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($application["title"]); ?>
                    </td>

                    <td>
                        <?php echo $application["reward_wp"]; ?> WP
                    </td>

                    <td>
                        <?php echo htmlspecialchars($application["deadline"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars(
                            $application["application_status"]
                        ); ?>
                    </td>

                    <td>
                        <a href="<?= BASE_URL ?>tasks/task_details.php?id=<?php echo $application["task_id"]; ?>">
                            View Task
                        </a>
                    </td>

                </tr>

            <?php } ?>

        </table>

    <?php } else { ?>

        <p>
            You have no pending applications.
        </p>

    <?php } ?>

    <h2>Active Work</h2>

    <?php if (mysqli_num_rows($active_work) > 0) { ?>

        <table class="my-work-table">

            <tr>
                <th>Task</th>
                <th>Reward</th>
                <th>Deadline</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

            <?php while ($work = mysqli_fetch_assoc($active_work)) { ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($work["title"]); ?>
                    </td>

                    <td>
                        <?php echo $work["reward_wp"]; ?> WP
                    </td>

                    <td>
                        <?php echo htmlspecialchars($work["deadline"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($work["status"]); ?>
                    </td>

                    <td>
                        <a href="<?= BASE_URL ?>tasks/submit_work.php?id=<?php echo $work["task_id"]; ?>">
                            Submit Work
                        </a>
                    </td>

                </tr>

             <?php } ?> 

        </table>

    <?php } else { ?>

        <p>
            You have no active work.
        </p>

    <?php } ?>

</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
