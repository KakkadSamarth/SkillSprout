<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$t_cols = [];
$tcol_res = @mysqli_query($conn, "SHOW COLUMNS FROM tasks");
if ($tcol_res) {
    while ($tc = mysqli_fetch_assoc($tcol_res)) {
        $t_cols[$tc['Field']] = true;
    }
}
$f_domain = isset($t_cols['domain']) ? 'domain' : (isset($t_cols['category']) ? 'category AS domain' : "'General' AS domain");
$f_skills = isset($t_cols['required_skills']) ? 'required_skills' : (isset($t_cols['skills']) ? 'skills AS required_skills' : "'' AS required_skills");
$f_reward = isset($t_cols['reward_wp']) ? 'reward_wp' : (isset($t_cols['reward']) ? 'reward AS reward_wp' : (isset($t_cols['points']) ? 'points AS reward_wp' : (isset($t_cols['budget']) ? 'budget AS reward_wp' : '10 AS reward_wp')));
$f_deadline = isset($t_cols['deadline']) ? 'deadline' : 'CURRENT_DATE AS deadline';
$f_status_select = isset($t_cols['status']) ? 'status' : "'OPEN' AS status";
$f_status_where = isset($t_cols['status']) ? "status = 'OPEN'" : "1=1";
$t_creator = isset($t_cols['creator_id']) ? 'creator_id' : (isset($t_cols['client_id']) ? 'client_id' : (isset($t_cols['user_id']) ? 'user_id' : 'creator_id'));
$t_order = isset($t_cols['created_at']) ? 'created_at' : 'task_id';

$user_id = (int)$_SESSION["user_id"];

$sql = "SELECT
            task_id,
            $t_creator AS creator_id,
            title,
            description,
            $f_domain,
            $f_skills,
            $f_reward,
            $f_deadline,
            $f_status_select
        FROM tasks
        WHERE $f_status_where
        ORDER BY $t_order DESC";

$result = mysqli_query($conn, $sql);

$app_cols = [];
$appcol_res = @mysqli_query($conn, "SHOW COLUMNS FROM applications");
if ($appcol_res) {
    while ($ac = mysqli_fetch_assoc($appcol_res)) {
        $app_cols[$ac['Field']] = true;
    }
}
$app_uid_col = isset($app_cols['user_id']) ? 'user_id' : (isset($app_cols['applicant_id']) ? 'applicant_id' : 'user_id');

$applied_map = [];
$app_res = @mysqli_query($conn, "SELECT task_id, status FROM applications WHERE $app_uid_col = $user_id");
if ($app_res) {
    while ($ar = mysqli_fetch_assoc($app_res)) {
        $applied_map[(int)$ar['task_id']] = $ar['status'];
    }
}

include __DIR__ . "/../includes/header2.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tasks - SkillSprout</title>
</head>
<body>

<main>

    <h1>Available Tasks</h1>

    <p>
        Browse available tasks and find work you can complete to earn Work Points.
    </p>

    <?php if (mysqli_num_rows($result) > 0) { ?>

        <table class="tasks-table">

            <tr>
                <th>Task</th>
                <th>Domain</th>
                <th>Reward</th>
                <th>Deadline</th>
                <th>Action</th>
            </tr>

            <?php while ($task = mysqli_fetch_assoc($result)) { ?>

                <tr>

                    <td>
                        <strong><?php echo htmlspecialchars($task["title"]); ?></strong>
                        <p><?php echo htmlspecialchars($task["description"]); ?></p>
                        <?php if ($task["required_skills"] != "") { ?>
                            <small>Skills: <?php echo htmlspecialchars($task["required_skills"]); ?></small>
                        <?php } ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($task["domain"]); ?>
                    </td>

                    <td>
                        <?php echo $task["reward_wp"]; ?> WP
                    </td>

                    <td>
                        <?php echo htmlspecialchars($task["deadline"]); ?>
                    </td>

                    <td>
                        <?php if ((int)$task["creator_id"] === (int)$user_id) { ?>
                            <span class="badge badge-warning">Your Task</span>
                            <div class="mt-1">
                                <a href="<?= BASE_URL ?>tasks/manage_applications.php?id=<?php echo $task["task_id"]; ?>" class="btn btn-sm btn-primary">
                                    Manage
                                </a>
                                <a href="<?= BASE_URL ?>tasks/task_details.php?id=<?php echo $task["task_id"]; ?>" class="btn btn-sm btn-secondary">
                                    Details
                                </a>
                            </div>
                        <?php } elseif (isset($applied_map[(int)$task["task_id"]])) { ?>
                            <span class="badge badge-success">Applied (<?php echo htmlspecialchars($applied_map[(int)$task["task_id"]]); ?>)</span>
                            <div class="mt-1">
                                <a href="<?= BASE_URL ?>tasks/task_details.php?id=<?php echo $task["task_id"]; ?>" class="btn btn-sm btn-secondary">
                                    View Details
                                </a>
                            </div>
                        <?php } else { ?>
                            <a href="<?= BASE_URL ?>tasks/apply_task.php?id=<?php echo $task["task_id"]; ?>" class="btn btn-sm btn-primary">
                                Apply Now &rarr;
                            </a>
                            <div class="mt-1">
                                <a href="<?= BASE_URL ?>tasks/task_details.php?id=<?php echo $task["task_id"]; ?>" class="btn btn-sm btn-secondary">
                                    View Details
                                </a>
                            </div>
                        <?php } ?>
                    </td>

                </tr>

            <?php } ?>

        </table>

    <?php } else { ?>

        <p>
            No tasks available right now. Please check back later.
        </p>

    <?php } ?>

</main>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
